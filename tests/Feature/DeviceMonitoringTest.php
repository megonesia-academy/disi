<?php

use App\Models\Device;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createDeviceMonitoringTenant(string $slug): array
{
    $tenant = Tenant::query()->create([
        'name' => Str::title($slug),
        'slug' => $slug.'-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => CurrentTenant::ROLE_OWNER,
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

it('renders the device monitoring page for a tenant device', function (): void {
    [$tenant, $user] = createDeviceMonitoringTenant('monitor');

    $screen = Screen::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    $device = Device::query()->create([
        'tenant_id' => $tenant->id,
        'screen_id' => $screen->id,
        'name' => 'Lobby TV',
        'key' => 'lobby-tv-'.Str::random(6),
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'app_version' => 'browser-player',
        'capabilities' => [
            'web' => true,
            'video' => true,
            'wasm' => true,
            'rust_wasm' => true,
            'layout_engine' => 'rust-wasm',
            'manifest_source' => 'cache',
            'manifest_cached' => true,
            'manifest_cached_at' => '2026-10-14T01:00:00.000Z',
            'network_online' => false,
            'media_readiness' => [
                'total' => 3,
                'ready' => 2,
                'failed' => 1,
                'pending' => 0,
                'unsupported' => 1,
                'status' => 'degraded',
                'updated_at' => '2026-10-14T01:01:00.000Z',
            ],
            'viewport' => [
                'width' => 1920,
                'height' => 1080,
                'device_pixel_ratio' => 1,
            ],
        ],
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->get(route('devices.show', $device))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('devices/Show')
            ->where('device.name', 'Lobby TV')
            ->where('device.screen.name', 'Lobby')
            ->where('device.capabilities.web', true)
            ->where('device.runtime.rust_wasm', true)
            ->where('device.runtime.layout_engine', 'rust-wasm')
            ->where('device.runtime.manifest_source', 'cache')
            ->where('device.runtime.manifest_cached', true)
            ->where('device.runtime.network_online', false)
            ->where('device.runtime.media_readiness.total', 3)
            ->where('device.runtime.media_readiness.ready', 2)
            ->where('device.runtime.media_readiness.status', 'degraded')
            ->where('device.runtime.viewport.width', 1920)
            ->has('device.player_url')
            ->has('device.manifest_url')
            ->has('device.heartbeat_url'));
});

it('prevents monitoring a device from another tenant', function (): void {
    [, $userA] = createDeviceMonitoringTenant('tenant-a');
    [$tenantB] = createDeviceMonitoringTenant('tenant-b');

    $device = Device::query()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B TV',
        'key' => 'tenant-b-tv-'.Str::random(6),
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);

    $this->actingAs($userA)
        ->get(route('devices.show', $device))
        ->assertNotFound();
});
