<?php

use App\Models\ApiConnection;
use App\Models\Device;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\PlaylistOverlay;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use App\Support\OverlayCatalog;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * @return array{0: Tenant, 1: User}
 */
function createOverlayTenant(array $settings = []): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Overlay Workspace',
        'slug' => 'overlay-workspace-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
        'settings' => [
            'brand_color' => '#0891b2',
            ...$settings,
        ],
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

function createOverlayConnection(Tenant $tenant, string $provider = 'jsonplaceholder', ?string $baseUrl = null): ApiConnection
{
    $connection = ApiConnection::query()->create([
        'tenant_id' => $tenant->id,
        'name' => Str::headline($provider),
        'provider' => $provider,
        'base_url' => $baseUrl ?? config("signage.api_providers.{$provider}.base_url"),
        'auth_type' => 'none',
        'is_active' => true,
    ]);

    $connection->forceFill(['is_approved' => true])->save();

    return $connection;
}

/**
 * @return array{0: Tenant, 1: Device, 2: Playlist}
 */
function createOverlayPlayer(Tenant $tenant): array
{
    $screen = Screen::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby',
        'location' => 'Lantai 1',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    $device = Device::query()->create([
        'tenant_id' => $tenant->id,
        'screen_id' => $screen->id,
        'name' => 'Lobby Player',
        'key' => 'overlay-player-'.Str::random(6),
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);

    $playlist = Playlist::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Overlay Playlist',
        'is_active' => true,
        'status' => Playlist::STATUS_PUBLISHED,
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

    return [$tenant, $device, $playlist];
}

/**
 * @return array<string, mixed>
 */
function overlayManifest(Tenant $tenant, Device $device): array
{
    return test()->getJson(route('api.player.manifest', [
        'device' => $device->key,
        'token' => $device->player_secret,
    ]))->assertOk()->json();
}

it('stores every supported overlay type with normalised config', function (): void {
    [$tenant, $user] = createOverlayTenant();

    $media = MediaAsset::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Promo Badge',
        'type' => MediaAsset::TYPE_IMAGE,
        'disk' => 'public',
        'path' => 'overlays/badge.png',
        'duration_seconds' => null,
    ]);

    $connection = createOverlayConnection($tenant);

    $this->actingAs($user)
        ->post(route('playlists.store'), [
            'name' => 'Overlay Playlist',
            'description' => null,
            'is_active' => true,
            'status' => Playlist::STATUS_DRAFT,
            'overlays' => [
                [
                    'type' => 'countdown',
                    'config' => [
                        'target_at' => '2026-12-31T20:00',
                        'prefix' => 'Menuju acara',
                        'finished_text' => 'Acara dimulai',
                        'theme' => 'blur',
                        'schedule' => ['days' => [1, 2, 2], 'start' => '08:00', 'end' => '17:00'],
                    ],
                ],
                [
                    'type' => 'date',
                    'config' => ['format' => 'short', 'locale' => 'en', 'theme' => 'light'],
                ],
                [
                    'type' => 'location',
                    'config' => ['label' => 'Lobby Utama', 'separator' => '·'],
                ],
                [
                    'type' => 'greeting',
                    'config' => ['template' => 'Selamat {greeting}, {tenant}'],
                ],
                [
                    'type' => 'hours',
                    'config' => ['title' => 'Jam operasional', 'open_label' => 'BUKA'],
                ],
                [
                    'type' => 'badge',
                    'config' => ['media_asset_id' => $media->id, 'size' => 120],
                ],
                [
                    'type' => 'api',
                    'config' => [
                        'api_connection_id' => $connection->id,
                        'endpoint' => '/todos/1',
                        'title' => 'Data',
                        'fields' => [
                            ['label' => 'ID', 'path' => 'id'],
                            ['label' => 'Judul', 'path' => 'title', 'suffix' => '!'],
                        ],
                    ],
                ],
                [
                    'type' => 'rss',
                    'config' => [
                        'api_connection_id' => $connection->id,
                        'endpoint' => '/feed.xml',
                        'layout' => 'ticker',
                        'item_limit' => 5,
                    ],
                ],
            ],
            'items' => [],
        ])
        ->assertRedirect(route('playlists.index'))
        ->assertSessionHasNoErrors();

    $overlays = $tenant->playlists()->sole()->overlays()->get()->keyBy('type');

    expect($overlays)->toHaveCount(8)
        ->and($overlays['countdown']->config['target_at'])->toBe('2026-12-31T20:00')
        ->and($overlays['countdown']->config['theme'])->toBe('blur')
        ->and($overlays['countdown']->config['schedule']['days'])->toBe([1, 2])
        ->and($overlays['countdown']->config['schedule']['start'])->toBe('08:00')
        ->and($overlays['date']->config['locale'])->toBe('en')
        ->and($overlays['location']->config['schedule'])->toBe([])
        ->and($overlays['badge']->config['media_asset_id'])->toBe($media->id)
        ->and($overlays['badge']->config['position'])->toBe('top-right')
        ->and($overlays['api']->config['fields'])->toHaveCount(2)
        ->and($overlays['api']->config['fields'][1]['suffix'])->toBe('!')
        ->and($overlays['rss']->config['item_limit'])->toBe(5)
        ->and($overlays['rss']->config['theme'])->toBe('dark')
        ->and($overlays['rss']->config['is_active'] ?? null)->toBeNull()
        ->and($overlays['rss']->is_active)->toBeTrue();
});

it('rejects unknown overlay types and too many overlays', function (): void {
    [$tenant, $user] = createOverlayTenant();

    $this->actingAs($user)
        ->post(route('playlists.store'), [
            'name' => 'Unknown Overlay',
            'description' => null,
            'is_active' => true,
            'status' => Playlist::STATUS_DRAFT,
            'overlays' => [
                ['type' => 'hologram', 'config' => []],
            ],
            'items' => [],
        ])
        ->assertSessionHasErrors('overlays.0.type');

    $overlays = [];

    for ($index = 0; $index < OverlayCatalog::MAX_OVERLAYS + 1; $index++) {
        $overlays[] = ['type' => 'clock', 'config' => ['format' => '24h']];
    }

    $this->actingAs($user)
        ->post(route('playlists.store'), [
            'name' => 'Too Many Overlays',
            'description' => null,
            'is_active' => true,
            'status' => Playlist::STATUS_DRAFT,
            'overlays' => $overlays,
            'items' => [],
        ])
        ->assertSessionHasErrors('overlays');

    expect(Playlist::query()->count())->toBe(0);
});

it('requires an approved connection and endpoint for remote overlays', function (): void {
    [$tenant, $user] = createOverlayTenant();

    $this->actingAs($user)
        ->post(route('playlists.store'), [
            'name' => 'Remote Without Connection',
            'description' => null,
            'is_active' => true,
            'status' => Playlist::STATUS_DRAFT,
            'overlays' => [
                ['type' => 'api', 'config' => []],
            ],
            'items' => [],
        ])
        ->assertSessionHasErrors([
            'overlays.0.config.api_connection_id',
            'overlays.0.config.endpoint',
        ]);
});

it('filters overlays by their configured schedule in the manifest', function (): void {
    [$tenant] = createOverlayTenant();
    [$tenant, $device, $playlist] = createOverlayPlayer($tenant);

    $tomorrow = now()->isoWeekday() === 7 ? 1 : now()->isoWeekday() + 1;

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'clock',
        'config' => [
            'format' => '24h',
            'schedule' => ['days' => [now()->isoWeekday()], 'start' => '00:00', 'end' => '23:59'],
        ],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'date',
        'config' => [
            'format' => 'long',
            'schedule' => ['days' => [$tomorrow], 'start' => '00:00', 'end' => '23:59'],
        ],
        'sort_order' => 2,
        'is_active' => true,
    ]);

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'greeting',
        'config' => ['template' => 'Halo {greeting}'],
        'sort_order' => 3,
        'is_active' => true,
    ]);

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'ticker',
        'config' => ['text' => 'Nonaktif'],
        'sort_order' => 4,
        'is_active' => false,
    ]);

    $manifest = overlayManifest($tenant, $device);

    expect($manifest['playlist']['overlays'])->toHaveCount(2)
        ->and($manifest['playlist']['overlays'][0]['type'])->toBe('clock')
        ->and($manifest['playlist']['overlays'][1]['type'])->toBe('greeting');
});

it('merges active tenant overlay defaults before playlist overlays', function (): void {
    [$tenant] = createOverlayTenant([
        'overlay_defaults' => [
            ['type' => 'clock', 'config' => ['format' => '12h', 'position' => 'top-left'], 'is_active' => true],
            ['type' => 'ticker', 'config' => ['text' => 'Nonaktif'], 'is_active' => false],
        ],
    ]);

    [$tenant, $device, $playlist] = createOverlayPlayer($tenant);

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'date',
        'config' => ['format' => 'long'],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $overlays = overlayManifest($tenant, $device)['playlist']['overlays'];

    expect($overlays)->toHaveCount(2)
        ->and($overlays[0]['type'])->toBe('clock')
        ->and($overlays[0]['id'])->toStartWith('tenant-')
        ->and($overlays[0]['config']['format'])->toBe('12h')
        ->and($overlays[1]['type'])->toBe('date');
});

it('resolves badge image urls in the manifest', function (): void {
    [$tenant] = createOverlayTenant();
    [$tenant, $device, $playlist] = createOverlayPlayer($tenant);

    $media = MediaAsset::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Promo Badge',
        'type' => MediaAsset::TYPE_IMAGE,
        'disk' => 'public',
        'path' => 'overlays/badge.png',
        'duration_seconds' => null,
    ]);

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'badge',
        'config' => ['media_asset_id' => $media->id, 'size' => 128],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $overlay = overlayManifest($tenant, $device)['playlist']['overlays'][0];

    expect($overlay['config']['image_url'])->toContain('storage/overlays/badge.png')
        ->and($overlay['config']['image_name'])->toBe('Promo Badge');
});

it('resolves generic api overlay values with json path mapping', function (): void {
    [$tenant] = createOverlayTenant();
    [$tenant, $device, $playlist] = createOverlayPlayer($tenant);
    $connection = createOverlayConnection($tenant);

    Http::fake([
        'https://jsonplaceholder.typicode.com/todos/1' => Http::response(['id' => 1, 'title' => 'demo'], 200),
    ]);

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'api',
        'config' => [
            'api_connection_id' => $connection->id,
            'endpoint' => '/todos/1',
            'refresh_seconds' => 300,
            'layout' => 'list',
            'fields' => [
                ['label' => 'ID', 'path' => 'id'],
                ['label' => 'Judul', 'path' => 'title', 'suffix' => '!'],
            ],
        ],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $values = overlayManifest($tenant, $device)['playlist']['overlays'][0]['config']['values'];

    expect($values)->toHaveCount(2)
        ->and($values[0]['label'])->toBe('ID')
        ->and($values[0]['value'])->toBe('1')
        ->and($values[1]['value'])->toBe('demo')
        ->and($values[1]['suffix'])->toBe('!');
});

it('parses rss feeds through an allow-listed connection', function (): void {
    config()->set('signage.api_allowed_hosts', ['feeds.example.com']);

    [$tenant] = createOverlayTenant();
    [$tenant, $device, $playlist] = createOverlayPlayer($tenant);
    $connection = createOverlayConnection($tenant, 'custom-rss', 'https://feeds.example.com');

    Http::fake([
        'https://feeds.example.com/feed.xml' => Http::response(
            '<rss><channel><item><title>Berita Satu</title><description>Ringkasan satu</description></item>'
            .'<item><title>Berita Dua</title></item></channel></rss>',
            200,
        ),
    ]);

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'rss',
        'config' => [
            'api_connection_id' => $connection->id,
            'endpoint' => 'feed.xml',
            'layout' => 'list',
            'item_limit' => 2,
            'refresh_seconds' => 900,
        ],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $entries = overlayManifest($tenant, $device)['playlist']['overlays'][0]['config']['entries'];

    expect($entries)->toHaveCount(2)
        ->and($entries[0]['title'])->toBe('Berita Satu')
        ->and($entries[0]['summary'])->toBe('Ringkasan satu')
        ->and($entries[1]['title'])->toBe('Berita Dua');
});

it('blocks rss connections whose host is not allow-listed', function (): void {
    config()->set('signage.api_allowed_hosts', []);

    [$tenant] = createOverlayTenant();
    [$tenant, $device, $playlist] = createOverlayPlayer($tenant);
    $connection = createOverlayConnection($tenant, 'custom-rss', 'https://feeds.example.com');

    Http::fake();

    PlaylistOverlay::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'type' => 'rss',
        'config' => [
            'api_connection_id' => $connection->id,
            'endpoint' => 'feed.xml',
        ],
        'sort_order' => 1,
        'is_active' => true,
    ]);

    $config = overlayManifest($tenant, $device)['playlist']['overlays'][0]['config'];

    expect($config)->not->toHaveKey('entries');
    Http::assertNothingSent();
});

it('stores tenant overlay defaults from workspace settings', function (): void {
    [$tenant, $user] = createOverlayTenant();

    $payload = [
        'name' => $tenant->name,
        'slug' => $tenant->slug,
        'billing_email' => null,
        'brand_color' => '#0891b2',
        'player_accent_label' => 'Overlay Workspace',
        'logo_width' => 120,
        'logo_position' => 'top-right',
        'logo_offset_x' => 0,
        'logo_offset_y' => 0,
        'manifest_refresh_seconds' => 60,
        'heartbeat_interval_seconds' => 30,
        'show_player_overlay' => true,
        'overlay_defaults' => [
            [
                'type' => 'clock',
                'config' => ['format' => '12h', 'theme' => 'light'],
                'is_active' => true,
            ],
            [
                'type' => 'ticker',
                'config' => ['text' => 'Promo'],
                'is_active' => false,
            ],
        ],
    ];

    $this->actingAs($user)
        ->put(route('workspace.settings.update'), $payload)
        ->assertRedirect(route('workspace.settings.edit'))
        ->assertSessionHasNoErrors();

    $tenant->refresh();

    expect($tenant->settings['overlay_defaults'])->toHaveCount(2)
        ->and($tenant->settings['overlay_defaults'][0]['type'])->toBe('clock')
        ->and($tenant->settings['overlay_defaults'][0]['config']['format'])->toBe('12h')
        ->and($tenant->settings['overlay_defaults'][0]['config']['theme'])->toBe('light')
        ->and($tenant->settings['overlay_defaults'][1]['is_active'])->toBeFalse();

    $this->actingAs($user)
        ->put(route('workspace.settings.update'), [
            ...$payload,
            'overlay_defaults' => [
                ['type' => 'hologram', 'config' => []],
            ],
        ])
        ->assertSessionHasErrors('overlay_defaults.0.type');
});
