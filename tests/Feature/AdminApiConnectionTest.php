<?php

use App\Models\ApiConnection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createAdminApiConnection(): ApiConnection
{
    $connection = ApiConnection::query()->create([
        'tenant_id' => null,
        'name' => 'Open-Meteo Weather',
        'provider' => 'open-meteo',
        'base_url' => config('signage.api_providers.open-meteo.base_url'),
        'auth_type' => 'none',
        'is_active' => true,
    ]);

    $connection->forceFill(['is_approved' => true])->save();

    return $connection;
}

it('lists platform api connections for admins', function (): void {
    $admin = User::factory()->appAdmin()->create();
    createAdminApiConnection();

    $this->actingAs($admin)
        ->get(route('admin.api-connections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/ApiConnections')
            ->has('connections', 1)
            ->has('providers')
            ->where('connections.0.name', 'Open-Meteo Weather')
        );
});

it('lets admins create a platform api connection', function (): void {
    $admin = User::factory()->appAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.api-connections.store'), [
            'name' => 'Demo API',
            'provider' => 'jsonplaceholder',
            'auth_type' => 'bearer',
            'credential' => 'plain-secret',
            'is_active' => true,
        ])
        ->assertRedirect(route('admin.api-connections.index'));

    $connection = ApiConnection::query()->sole();

    expect($connection->tenant_id)->toBeNull()
        ->and($connection->is_approved)->toBeTrue()
        ->and($connection->base_url)->toBe(config('signage.api_providers.jsonplaceholder.base_url'))
        ->and($connection->credential)->not->toBe('plain-secret')
        ->and(Crypt::decryptString($connection->credential))->toBe('plain-secret');
});

it('keeps the credential when updating a connection without a new one', function (): void {
    $admin = User::factory()->appAdmin()->create();

    $connection = ApiConnection::query()->create([
        'tenant_id' => null,
        'name' => 'Secret API',
        'provider' => 'jsonplaceholder',
        'base_url' => config('signage.api_providers.jsonplaceholder.base_url'),
        'auth_type' => 'bearer',
        'credential' => Crypt::encryptString('keep-me'),
        'is_active' => true,
    ]);
    $connection->forceFill(['is_approved' => true])->save();

    $this->actingAs($admin)
        ->put(route('admin.api-connections.update', $connection), [
            'name' => 'Secret API Updated',
            'provider' => 'jsonplaceholder',
            'auth_type' => 'bearer',
            'credential' => '',
            'is_active' => false,
        ])
        ->assertRedirect(route('admin.api-connections.index'));

    $connection->refresh();

    expect($connection->name)->toBe('Secret API Updated')
        ->and($connection->is_active)->toBeFalse()
        ->and(Crypt::decryptString($connection->credential))->toBe('keep-me');
});

it('lets admins delete a platform api connection', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $connection = createAdminApiConnection();

    $this->actingAs($admin)
        ->delete(route('admin.api-connections.destroy', $connection))
        ->assertRedirect(route('admin.api-connections.index'));

    expect(ApiConnection::query()->count())->toBe(0);
});

it('lets admins test a platform api connection', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $connection = createAdminApiConnection();

    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $this->actingAs($admin)
        ->postJson(route('admin.api-connections.test', $connection))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', 200);
});

it('prevents subscribers from managing platform api connections', function (): void {
    $subscriber = User::factory()->create();
    $connection = createAdminApiConnection();

    $this->actingAs($subscriber)
        ->get(route('admin.api-connections.index'))
        ->assertForbidden();

    $this->actingAs($subscriber)
        ->post(route('admin.api-connections.store'), [
            'name' => 'Nope',
            'provider' => 'open-meteo',
            'auth_type' => 'none',
            'is_active' => true,
        ])
        ->assertForbidden();

    $this->actingAs($subscriber)
        ->put(route('admin.api-connections.update', $connection), [
            'name' => 'Nope',
            'provider' => 'open-meteo',
            'auth_type' => 'none',
            'is_active' => false,
        ])
        ->assertForbidden();
});

it('does not expose tenant connections to the admin catalog', function (): void {
    $admin = User::factory()->appAdmin()->create();

    $tenant = Tenant::query()->create([
        'name' => 'Tenant API',
        'slug' => 'tenant-api-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantConnection = ApiConnection::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Tenant Connection',
        'provider' => 'open-meteo',
        'base_url' => config('signage.api_providers.open-meteo.base_url'),
        'auth_type' => 'none',
        'is_active' => true,
    ]);

    $this->actingAs($admin)
        ->get(route('admin.api-connections.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('connections', 0));

    $this->actingAs($admin)
        ->put(route('admin.api-connections.update', $tenantConnection), [
            'name' => 'Hacked',
            'provider' => 'open-meteo',
            'auth_type' => 'none',
            'is_active' => false,
        ])
        ->assertNotFound();
});
