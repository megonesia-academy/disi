<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Screen;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request): Response
    {
        $tenant = app(CurrentTenant::class)->resolve($request);

        $devices = Device::query()
            ->with(['screen' => fn ($query) => $query->withCount([
                'schedules as active_schedules_count' => fn ($query) => $query->where('is_active', true),
            ])])
            ->where('tenant_id', $tenant->id)
            ->latest('last_seen_at')
            ->get();
        $screens = Screen::query()
            ->withCount([
                'devices',
                'schedules as active_schedules_count' => fn ($query) => $query->where('is_active', true),
            ])
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->limit(6)
            ->get();

        $onlineDeviceCount = $devices->filter->isOnline()->count();
        $screenCount = Screen::query()->where('tenant_id', $tenant->id)->count();
        $deviceCount = Device::query()->where('tenant_id', $tenant->id)->count();
        $mediaAssetCount = MediaAsset::query()->where('tenant_id', $tenant->id)->count();
        $playlistCount = Playlist::query()->where('tenant_id', $tenant->id)->count();
        $playlistWithItemsCount = Playlist::query()
            ->where('tenant_id', $tenant->id)
            ->whereHas('items')
            ->count();
        $activeScheduleCount = Schedule::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->count();

        $devices = $devices
            ->map(fn (Device $device): array => [
                'id' => $device->id,
                'name' => $device->name,
                'key' => $device->key,
                'status' => $device->displayStatus(),
                'is_active' => $device->is_active,
                'last_seen_at' => $device->last_seen_at?->diffForHumans(),
                'last_seen_iso' => $device->last_seen_at?->toISOString(),
                'screen' => $device->screen ? [
                    'id' => $device->screen->id,
                    'name' => $device->screen->name,
                    'location' => $device->screen->location,
                ] : null,
                'health' => $this->deviceHealth($device),
                'runtime' => $this->deviceRuntime($device),
                'player_url' => route('player.show', [
                    'device' => $device->key,
                    'token' => $device->player_secret,
                ]),
            ]);

        return Inertia::render('Dashboard', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status,
            ],
            'stats' => [
                'screens' => $screenCount,
                'devices' => $deviceCount,
                'online_devices' => $onlineDeviceCount,
                'media_assets' => $mediaAssetCount,
                'playlists' => $playlistCount,
                'active_schedules' => $activeScheduleCount,
            ],
            'setup' => [
                [
                    'key' => 'screens',
                    'title' => 'Create a screen',
                    'description' => 'Tentukan lokasi display seperti Lobby, Kasir, atau Ruang Tunggu.',
                    'done' => $screenCount > 0,
                    'href' => '/screens',
                    'action' => $screenCount > 0 ? 'Manage screens' : 'Create screen',
                ],
                [
                    'key' => 'media',
                    'title' => 'Upload media',
                    'description' => 'Tambahkan gambar, video, atau URL web untuk konten signage.',
                    'done' => $mediaAssetCount > 0,
                    'href' => '/media-assets',
                    'action' => $mediaAssetCount > 0 ? 'Manage media' : 'Upload media',
                ],
                [
                    'key' => 'playlist',
                    'title' => 'Build a playlist',
                    'description' => 'Susun media menjadi urutan tayang yang siap diputar.',
                    'done' => $playlistWithItemsCount > 0,
                    'href' => '/playlists',
                    'action' => $playlistWithItemsCount > 0 ? 'Manage playlists' : 'Create playlist',
                ],
                [
                    'key' => 'schedule',
                    'title' => 'Schedule to screen',
                    'description' => 'Hubungkan playlist ke screen supaya player tahu konten yang aktif.',
                    'done' => $activeScheduleCount > 0,
                    'href' => '/schedules',
                    'action' => $activeScheduleCount > 0 ? 'Manage schedules' : 'Create schedule',
                ],
                [
                    'key' => 'device',
                    'title' => 'Pair a display',
                    'description' => 'Buka /display di TV lalu pairing device ke screen.',
                    'done' => $onlineDeviceCount > 0,
                    'href' => '/devices',
                    'action' => $onlineDeviceCount > 0 ? 'View devices' : 'Pair display',
                ],
            ],
            'devices' => $devices,
            'screens' => $screens->map(fn (Screen $screen): array => [
                'id' => $screen->id,
                'name' => $screen->name,
                'location' => $screen->location,
                'orientation' => $screen->orientation,
                'resolution' => [
                    'width' => $screen->resolution_width,
                    'height' => $screen->resolution_height,
                ],
                'is_active' => $screen->is_active,
                'devices_count' => $screen->devices_count,
                'active_schedules_count' => $screen->active_schedules_count,
                'preview_url' => route('screens.preview', $screen),
                'health' => $this->screenHealth($screen),
            ]),
        ]);
    }

    /**
     * @return array{status: string, label: string, message: string}
     */
    private function deviceHealth(Device $device): array
    {
        if (! $device->is_active) {
            return [
                'status' => 'inactive',
                'label' => 'Inactive',
                'message' => 'Device dinonaktifkan.',
            ];
        }

        if (! $device->screen) {
            return [
                'status' => 'unassigned',
                'label' => 'Need screen',
                'message' => 'Belum assign ke screen.',
            ];
        }

        if (($device->screen->active_schedules_count ?? 0) === 0) {
            return [
                'status' => 'no_schedule',
                'label' => 'Need schedule',
                'message' => 'Screen belum punya schedule aktif.',
            ];
        }

        if (! $device->isOnline()) {
            return [
                'status' => 'offline',
                'label' => 'Offline',
                'message' => 'Menunggu heartbeat dari player.',
            ];
        }

        return [
            'status' => 'ready',
            'label' => 'Ready',
            'message' => 'Online dan siap menampilkan konten.',
        ];
    }

    /**
     * @return array{wasm_supported: bool|null, rust_wasm: bool|null, layout_engine: string, manifest_source: string, manifest_cached: bool|null, network_online: bool|null, media_readiness: array{total: int, ready: int, failed: int, pending: int, unsupported: int, status: string}}
     */
    private function deviceRuntime(Device $device): array
    {
        $capabilities = $device->capabilities ?? [];
        $layoutEngine = $capabilities['layout_engine'] ?? null;
        $manifestSource = $capabilities['manifest_source'] ?? null;

        return [
            'wasm_supported' => is_bool($capabilities['wasm'] ?? null) ? $capabilities['wasm'] : null,
            'rust_wasm' => is_bool($capabilities['rust_wasm'] ?? null) ? $capabilities['rust_wasm'] : null,
            'layout_engine' => is_string($layoutEngine) && $layoutEngine !== '' ? $layoutEngine : 'unknown',
            'manifest_source' => is_string($manifestSource) && $manifestSource !== '' ? $manifestSource : 'unknown',
            'manifest_cached' => is_bool($capabilities['manifest_cached'] ?? null) ? $capabilities['manifest_cached'] : null,
            'network_online' => is_bool($capabilities['network_online'] ?? null) ? $capabilities['network_online'] : null,
            'media_readiness' => $this->mediaReadiness($capabilities['media_readiness'] ?? null),
        ];
    }

    /**
     * @return array{total: int, ready: int, failed: int, pending: int, unsupported: int, status: string}
     */
    private function mediaReadiness(mixed $payload): array
    {
        if (! is_array($payload)) {
            return [
                'total' => 0,
                'ready' => 0,
                'failed' => 0,
                'pending' => 0,
                'unsupported' => 0,
                'status' => 'unknown',
            ];
        }

        $status = $payload['status'] ?? null;

        return [
            'total' => max((int) ($payload['total'] ?? 0), 0),
            'ready' => max((int) ($payload['ready'] ?? 0), 0),
            'failed' => max((int) ($payload['failed'] ?? 0), 0),
            'pending' => max((int) ($payload['pending'] ?? 0), 0),
            'unsupported' => max((int) ($payload['unsupported'] ?? 0), 0),
            'status' => is_string($status) && $status !== '' ? $status : 'unknown',
        ];
    }

    /**
     * @return array{status: string, label: string, message: string}
     */
    private function screenHealth(Screen $screen): array
    {
        if (! $screen->is_active) {
            return [
                'status' => 'inactive',
                'label' => 'Inactive',
                'message' => 'Screen sedang dinonaktifkan.',
            ];
        }

        if (($screen->active_schedules_count ?? 0) === 0) {
            return [
                'status' => 'no_schedule',
                'label' => 'Need schedule',
                'message' => 'Belum ada schedule aktif untuk dipreview.',
            ];
        }

        if (($screen->devices_count ?? 0) === 0) {
            return [
                'status' => 'no_device',
                'label' => 'Ready to pair',
                'message' => 'Konten siap, tinggal pair TV ke screen ini.',
            ];
        }

        return [
            'status' => 'ready',
            'label' => 'Ready',
            'message' => 'Screen punya schedule dan device tersambung.',
        ];
    }
}
