<?php

use App\Models\ApiConnection;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createApiConnectionTenant(): array
{
    $tenant = Tenant::query()->create([
        'name' => 'API Workspace',
        'slug' => 'api-workspace-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

function createPlatformConnection(string $provider = 'jsonplaceholder'): ApiConnection
{
    $connection = ApiConnection::query()->create([
        'tenant_id' => null,
        'name' => 'Platform Demo',
        'provider' => $provider,
        'base_url' => config("signage.api_providers.{$provider}.base_url"),
        'auth_type' => 'none',
        'is_active' => true,
    ]);

    $connection->forceFill(['is_approved' => true])->save();

    return $connection;
}

function createApprovedConnection(Tenant $tenant, string $provider = 'jsonplaceholder'): ApiConnection
{
    $connection = ApiConnection::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Demo Connection',
        'provider' => $provider,
        'base_url' => config("signage.api_providers.{$provider}.base_url"),
        'auth_type' => 'bearer',
        'credential' => Crypt::encryptString('secret-token'),
        'is_active' => true,
    ]);

    $connection->forceFill(['is_approved' => true])->save();

    return $connection;
}

it('lists platform connections for the workspace page', function (): void {
    [$tenant, $user] = createApiConnectionTenant();
    createPlatformConnection();
    createApprovedConnection($tenant);

    $this->actingAs($user)
        ->get(route('integrations.api.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('integrations/ApiConnections')
            ->has('connections', 2)
            ->where('connections.0.source', 'workspace')
            ->where('connections.1.source', 'platform')
        );
});

it('tests a platform connection from the workspace', function (): void {
    [$tenant, $user] = createApiConnectionTenant();
    $connection = createPlatformConnection();

    Http::fake(['*' => Http::response(['ok' => true], 200)]);

    $this->actingAs($user)
        ->postJson(route('integrations.api.test', $connection))
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('status', 200);

    Http::assertSent(fn ($request): bool => str_contains(
        $request->url(),
        config('signage.api_providers.jsonplaceholder.healthcheck_url'),
    ));
});

it('reports provider failures when testing a platform connection', function (): void {
    [$tenant, $user] = createApiConnectionTenant();
    $connection = createPlatformConnection();

    Http::fake(['*' => Http::response('down', 500)]);

    $this->actingAs($user)
        ->postJson(route('integrations.api.test', $connection))
        ->assertStatus(422)
        ->assertJsonPath('success', false)
        ->assertJsonPath('status', 500);
});

it('rejects testing an inactive platform connection', function (): void {
    [$tenant, $user] = createApiConnectionTenant();
    $connection = createPlatformConnection();
    $connection->forceFill(['is_active' => false])->save();

    $this->actingAs($user)
        ->postJson(route('integrations.api.test', $connection))
        ->assertStatus(422);
});

it('hides tenant management routes for api connections', function (): void {
    [$tenant, $user] = createApiConnectionTenant();

    $this->actingAs($user)
        ->post('/integrations/api', [
            'name' => 'Tenant Connection',
            'provider' => 'open-meteo',
            'auth_type' => 'none',
            'is_active' => true,
        ])
        ->assertStatus(405);

    expect(ApiConnection::query()->where('tenant_id', $tenant->id)->count())->toBe(0);
});

it('allows platform connections to be used by playlists', function (): void {
    [$tenant, $user] = createApiConnectionTenant();
    $connection = createPlatformConnection();

    $this->actingAs($user)
        ->post(route('playlists.store'), [
            'name' => 'Data Loop',
            'description' => null,
            'is_active' => true,
            'status' => 'draft',
            'overlays' => [],
            'items' => [
                [
                    'media_asset_id' => null,
                    'template' => 'data-table',
                    'api_connection_id' => $connection->id,
                    'content' => [],
                    'duration_seconds' => 15,
                ],
            ],
        ])
        ->assertRedirect(route('playlists.index'))
        ->assertSessionHasNoErrors();

    $playlistItem = $tenant->playlists()->sole()->items()->sole();

    expect($playlistItem->api_connection_id)->toBe($connection->id);
});
