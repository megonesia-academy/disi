<?php

use App\Models\Device;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\PlaylistOverlay;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\Tenant;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createPlayableDevice(): Device
{
    $tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
        'settings' => [
            'brand_color' => '#16a34a',
            'player_accent_label' => 'Acme Screens',
        ],
    ]);

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
        'name' => 'Lobby Player',
        'key' => 'lobby-player-'.Str::random(6),
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);

    $mediaAsset = MediaAsset::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Welcome',
        'type' => MediaAsset::TYPE_WEB,
        'disk' => 'public',
        'path' => 'https://example.com',
        'duration_seconds' => 10,
    ]);

    $playlist = Playlist::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Default',
        'is_active' => true,
    ]);

    PlaylistItem::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'media_asset_id' => $mediaAsset->id,
        'sort_order' => 1,
        'duration_seconds' => 10,
        'transition' => 'fade',
        'config' => [
            'fit' => 'contain',
            'overlays' => [[
                'type' => 'ticker',
                'text' => 'Welcome to Acme',
                'position' => 'bottom',
                'speed' => 60,
                'text_color' => '#ffffff',
                'background_color' => '#111827',
            ]],
        ],
    ]);

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'ticker',
        'config' => [
            'text' => 'Global welcome',
            'position' => 'bottom',
            'speed' => 60,
            'text_color' => '#ffffff',
            'background_color' => '#111827',
        ],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    Schedule::query()->create([
        'tenant_id' => $tenant->id,
        'screen_id' => $screen->id,
        'playlist_id' => $playlist->id,
        'days_of_week' => [now()->isoWeekday()],
        'start_time' => '00:00',
        'end_time' => '23:59',
        'priority' => 10,
        'is_active' => true,
    ]);

    return $device;
}

it('rejects manifest requests without the player token', function (): void {
    $device = createPlayableDevice();

    $this->getJson(route('api.player.manifest', ['device' => $device->key]))
        ->assertNotFound();
});

it('returns manifest with a valid player token', function (): void {
    $device = createPlayableDevice();

    $this->getJson(route('api.player.manifest', [
        'device' => $device->key,
        'token' => $device->player_secret,
    ]))
        ->assertOk()
        ->assertJsonPath('branding.label', 'Acme Screens')
        ->assertJsonPath('branding.brand_color', '#16a34a')
        ->assertJsonPath('branding.logo_width', 120)
        ->assertJsonPath('branding.logo_position', 'top-right')
        ->assertJsonPath('device.key', $device->key)
        ->assertJsonPath('playlist.items.0.transition', 'fade')
        ->assertJsonPath('playlist.items.0.config.fit', 'contain')
        ->assertJsonPath('playlist.items.0.config.overlays.0.type', 'ticker')
        ->assertJsonPath('playlist.items.0.config.overlays.0.text', 'Welcome to Acme')
        ->assertJsonPath('playlist.overlays.0.type', 'ticker')
        ->assertJsonPath('playlist.overlays.0.config.text', 'Global welcome')
        ->assertJsonPath('playlist.items.0.media.name', 'Welcome');
});

it('serves external media urls directly in the manifest', function (string $type, string $url): void {
    $device = createPlayableDevice();

    $mediaAsset = MediaAsset::query()
        ->where('tenant_id', $device->tenant_id)
        ->sole();

    $mediaAsset->update([
        'type' => $type,
        'path' => $url,
    ]);

    $this->getJson(route('api.player.manifest', [
        'device' => $device->key,
        'token' => $device->player_secret,
    ]))
        ->assertOk()
        ->assertJsonPath('playlist.items.0.media.type', $type)
        ->assertJsonPath('playlist.items.0.media.url', $url);
})->with([
    'image url' => [MediaAsset::TYPE_IMAGE_URL, 'https://cdn.example.com/poster.jpg'],
    'video url' => [MediaAsset::TYPE_VIDEO_URL, 'https://cdn.example.com/promo.mp4'],
]);

it('renders the player screen with a valid player token', function (): void {
    $device = createPlayableDevice();

    $this->get(route('player.show', [
        'device' => $device->key,
        'token' => $device->player_secret,
    ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('player/Show')
            ->where('deviceKey', $device->key)
            ->has('manifestUrl')
            ->has('heartbeatUrl'));
});

it('rejects the player screen without the player token', function (): void {
    $device = createPlayableDevice();

    $this->get(route('player.show', ['device' => $device->key]))
        ->assertNotFound();
});

it('updates heartbeat with a valid player token', function (): void {
    $device = createPlayableDevice();

    $this->postJson(route('api.player.heartbeat', [
        'device' => $device->key,
        'token' => $device->player_secret,
    ]), [
        'app_version' => 'test-player',
        'capabilities' => [
            'web' => true,
            'wasm' => true,
            'rust_wasm' => false,
            'layout_engine' => 'js-fallback',
            'manifest_source' => 'cache',
            'manifest_cached' => true,
            'manifest_cached_at' => '2026-10-14T01:00:00.000Z',
            'network_online' => false,
            'media_readiness' => [
                'total' => 2,
                'ready' => 1,
                'failed' => 0,
                'pending' => 1,
                'unsupported' => 1,
                'status' => 'warming',
                'updated_at' => '2026-10-14T01:01:00.000Z',
            ],
        ],
    ])->assertOk();

    $device->refresh();

    expect($device->status)->toBe(Device::STATUS_ONLINE)
        ->and($device->app_version)->toBe('test-player')
        ->and($device->capabilities['layout_engine'])->toBe('js-fallback')
        ->and($device->capabilities['manifest_source'])->toBe('cache')
        ->and($device->capabilities['manifest_cached'])->toBeTrue()
        ->and($device->capabilities['network_online'])->toBeFalse()
        ->and($device->capabilities['media_readiness']['status'])->toBe('warming')
        ->and($device->capabilities['media_readiness']['ready'])->toBe(1)
        ->and($device->last_seen_at)->not->toBeNull();
});

it('rejects heartbeat without the player token', function (): void {
    $device = createPlayableDevice();

    $this->postJson(route('api.player.heartbeat', ['device' => $device->key]))
        ->assertNotFound();
});
