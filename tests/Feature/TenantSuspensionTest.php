<?php

use App\Models\Device;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

function createSuspendedTenantWithOwner(): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Suspended Workspace',
        'slug' => 'suspended-'.Str::random(6),
        'status' => Tenant::STATUS_SUSPENDED,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

it('forbids subscribers from a suspended tenant workspace', function (): void {
    [$tenant, $user] = createSuspendedTenantWithOwner();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertForbidden();

    $this->actingAs($user)
        ->get(route('screens.index'))
        ->assertForbidden();

    expect($tenant->status)->toBe(Tenant::STATUS_SUSPENDED);
});

it('lets application admins bypass tenant suspension', function (): void {
    [$tenant] = createSuspendedTenantWithOwner();

    $admin = User::factory()->appAdmin()->create();
    $tenant->users()->attach($admin->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk();
});

it('serves no manifest for devices of suspended tenants', function (): void {
    [$tenant] = createSuspendedTenantWithOwner();

    $device = Device::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Suspended Player',
        'key' => 'suspended-player-'.Str::random(6),
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);

    $this->getJson(route('api.player.manifest', [
        'device' => $device->key,
        'token' => $device->player_secret,
    ]))->assertNotFound();

    $this->postJson(route('api.player.heartbeat', [
        'device' => $device->key,
        'token' => $device->player_secret,
    ]))->assertNotFound();
});
