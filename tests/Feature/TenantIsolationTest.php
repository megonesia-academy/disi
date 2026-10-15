<?php

use App\Models\Device;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;

function createTenantWithUser(string $slug): array
{
    $tenant = Tenant::query()->create([
        'name' => Str::title($slug),
        'slug' => $slug,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

it('does not list screens from another tenant', function (): void {
    [$tenantA, $userA] = createTenantWithUser('tenant-a');
    [$tenantB] = createTenantWithUser('tenant-b');

    Screen::query()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Tenant A Screen',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    Screen::query()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B Screen',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    $this->actingAs($userA)
        ->get(route('screens.index'))
        ->assertOk()
        ->assertSee('Tenant A Screen')
        ->assertDontSee('Tenant B Screen');
});

it('prevents updating a screen from another tenant', function (): void {
    [, $userA] = createTenantWithUser('tenant-a');
    [$tenantB] = createTenantWithUser('tenant-b');

    $screen = Screen::query()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B Screen',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    $this->actingAs($userA)
        ->put(route('screens.update', $screen), [
            'name' => 'Changed',
            'location' => null,
            'orientation' => Screen::ORIENTATION_LANDSCAPE,
            'resolution_width' => 1920,
            'resolution_height' => 1080,
            'is_active' => true,
        ])
        ->assertNotFound();
});

it('prevents assigning a device to a screen from another tenant', function (): void {
    [$tenantA, $userA] = createTenantWithUser('tenant-a');
    [$tenantB] = createTenantWithUser('tenant-b');

    $device = Device::query()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Tenant A Device',
        'key' => 'tenant-a-device',
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);

    $screen = Screen::query()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B Screen',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    $this->actingAs($userA)
        ->put(route('devices.update', $device), [
            'screen_id' => $screen->id,
            'name' => $device->name,
            'key' => $device->key,
            'status' => Device::STATUS_OFFLINE,
            'app_version' => null,
            'is_active' => true,
        ])
        ->assertSessionHasErrors('screen_id');
});
