<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Screen;
use App\Models\Tenant;
use App\Services\TenantBilling;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DeviceController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ActivityLogger $activityLogger,
        private readonly TenantBilling $billing,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        return Inertia::render('devices/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'devices' => Device::query()
                ->with(['screen' => fn ($query) => $query->withCount([
                    'schedules as active_schedules_count' => fn ($query) => $query->where('is_active', true),
                ])])
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get()
                ->map(fn (Device $device): array => [
                    'id' => $device->id,
                    'screen_id' => $device->screen_id,
                    'name' => $device->name,
                    'key' => $device->key,
                    'player_secret' => $device->player_secret,
                    'status' => $device->displayStatus(),
                    'stored_status' => $device->status,
                    'app_version' => $device->app_version,
                    'last_seen_at' => $device->last_seen_at?->diffForHumans(),
                    'is_active' => $device->is_active,
                    'screen' => $device->screen ? [
                        'id' => $device->screen->id,
                        'name' => $device->screen->name,
                        'location' => $device->screen->location,
                    ] : null,
                    'health' => $this->deviceHealth($device),
                    'runtime' => $this->deviceRuntime($device),
                    'monitor_url' => route('devices.show', $device),
                    'player_url' => route('player.show', [
                        'device' => $device->key,
                        'token' => $device->player_secret,
                    ]),
                ]),
            'screens' => Screen::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'location']),
            'statuses' => [
                Device::STATUS_ONLINE,
                Device::STATUS_OFFLINE,
            ],
            'usage' => [
                'devices' => $tenant->devices()->count(),
                'device_limit' => $this->billing->deviceLimit($tenant),
                'free_devices' => $this->billing->freeLimit(TenantBilling::FEATURE_EXTRA_DEVICE),
                'can_add_device' => $this->billing->canAddDevice($tenant),
                'balance' => $tenant->token_balance,
            ],
        ]);
    }

    /**
     * Display a specific device monitoring page.
     */
    public function show(Request $request, Device $device): Response
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $device);

        $device->load([
            'commands' => fn ($query) => $query->latest()->limit(10),
            'screen' => fn ($query) => $query->withCount([
                'schedules as active_schedules_count' => fn ($query) => $query->where('is_active', true),
            ]),
        ]);

        return Inertia::render('devices/Show', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'device' => [
                'id' => $device->id,
                'screen_id' => $device->screen_id,
                'name' => $device->name,
                'key' => $device->key,
                'status' => $device->displayStatus(),
                'stored_status' => $device->status,
                'app_version' => $device->app_version,
                'last_seen_at' => $device->last_seen_at?->diffForHumans(),
                'last_seen_iso' => $device->last_seen_at?->toISOString(),
                'is_active' => $device->is_active,
                'capabilities' => $device->capabilities ?? [],
                'runtime' => $this->deviceRuntime($device),
                'health' => $this->deviceHealth($device),
                'player_url' => route('player.show', [
                    'device' => $device->key,
                    'token' => $device->player_secret,
                ]),
                'manifest_url' => route('api.player.manifest', [
                    'device' => $device->key,
                    'token' => $device->player_secret,
                ]),
                'heartbeat_url' => route('api.player.heartbeat', [
                    'device' => $device->key,
                    'token' => $device->player_secret,
                ]),
                'screen' => $device->screen ? [
                    'id' => $device->screen->id,
                    'name' => $device->screen->name,
                    'location' => $device->screen->location,
                    'orientation' => $device->screen->orientation,
                    'resolution' => [
                        'width' => $device->screen->resolution_width,
                        'height' => $device->screen->resolution_height,
                    ],
                    'active_schedules_count' => $device->screen->active_schedules_count,
                    'preview_url' => route('screens.preview', $device->screen),
                ] : null,
                'commands' => $device->commands->map(fn (DeviceCommand $command): array => [
                    'id' => $command->id,
                    'type' => $command->type,
                    'status' => $command->status,
                    'payload' => $command->payload ?? [],
                    'created_at' => $command->created_at?->diffForHumans(),
                    'sent_at' => $command->sent_at?->diffForHumans(),
                    'acknowledged_at' => $command->acknowledged_at?->diffForHumans(),
                ]),
            ],
            'commandTypes' => [
                [
                    'type' => DeviceCommand::TYPE_REFRESH_MANIFEST,
                    'label' => 'Refresh manifest',
                    'description' => 'Minta player mengambil manifest terbaru.',
                ],
                [
                    'type' => DeviceCommand::TYPE_IDENTIFY,
                    'label' => 'Identify screen',
                    'description' => 'Tampilkan overlay identitas singkat di TV.',
                ],
                [
                    'type' => DeviceCommand::TYPE_RELOAD,
                    'label' => 'Reload player',
                    'description' => 'Muat ulang halaman player di TV.',
                ],
                [
                    'type' => DeviceCommand::TYPE_CLEAR_CACHE,
                    'label' => 'Clear player cache',
                    'description' => 'Hapus manifest dan media cache di TV.',
                ],
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        if (! $this->billing->canAddDevice($tenant)) {
            return back()->withErrors([
                'billing' => 'Batas device paket Anda tercapai ('.$this->billing->deviceLimit($tenant).' device). Beli slot device tambahan di halaman Billing.',
            ]);
        }

        $validated = $this->validated($request, $tenant);

        $device = Device::query()->create([
            ...$validated,
            'tenant_id' => $tenant->id,
            'key' => ($validated['key'] ?? '') ?: Str::slug($validated['name']).'-'.Str::lower(Str::random(6)),
            'player_secret' => Str::random(48),
        ]);

        $this->activityLogger->log($request, $tenant, 'device.created', $device, [
            'device_name' => $device->name,
            'device_key' => $device->key,
            'screen_id' => $device->screen_id,
        ]);

        return to_route('devices.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Device $device): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $device);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $validated = $this->validated($request, $tenant, $device);

        $device->update([
            ...$validated,
            'key' => ($validated['key'] ?? '') ?: $device->key,
        ]);

        $this->activityLogger->log($request, $tenant, 'device.updated', $device, [
            'device_name' => $device->name,
            'device_key' => $device->key,
            'screen_id' => $device->screen_id,
            'is_active' => $device->is_active,
        ]);

        return to_route('devices.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Device $device): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $device);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $properties = [
            'device_name' => $device->name,
            'device_key' => $device->key,
            'screen_id' => $device->screen_id,
        ];

        $this->activityLogger->log($request, $tenant, 'device.deleted', $device, $properties);

        $device->delete();

        return to_route('devices.index');
    }

    /**
     * @return array{screen_id: string|null, name: string, key: string|null, status: string, app_version: string|null, is_active: bool}
     */
    private function validated(Request $request, Tenant $tenant, ?Device $device = null): array
    {
        return $request->validate([
            'screen_id' => [
                'nullable',
                Rule::exists('screens', 'id')->where('tenant_id', $tenant->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'key' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('devices', 'key')->ignore($device),
            ],
            'status' => ['required', Rule::in([Device::STATUS_ONLINE, Device::STATUS_OFFLINE])],
            'app_version' => ['nullable', 'string', 'max:255'],
            'is_active' => ['required', 'boolean'],
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
                'message' => 'Device dinonaktifkan dan tidak akan menerima konten.',
            ];
        }

        if (! $device->screen) {
            return [
                'status' => 'unassigned',
                'label' => 'Need screen',
                'message' => 'Assign device ke screen sebelum player bisa membaca playlist.',
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
                'message' => 'Player belum mengirim heartbeat terbaru.',
            ];
        }

        return [
            'status' => 'ready',
            'label' => 'Ready',
            'message' => 'Display online dan siap menampilkan konten.',
        ];
    }

    /**
     * @return array{wasm_supported: bool|null, rust_wasm: bool|null, layout_engine: string, manifest_source: string, manifest_cached: bool|null, manifest_cached_at: string|null, network_online: bool|null, service_worker_cache: bool|null, media_readiness: array{total: int, ready: int, failed: int, pending: int, unsupported: int, status: string, updated_at: string|null}, viewport: array<string, mixed>|null, user_agent: string|null}
     */
    private function deviceRuntime(Device $device): array
    {
        $capabilities = $device->capabilities ?? [];
        $layoutEngine = $capabilities['layout_engine'] ?? null;
        $manifestSource = $capabilities['manifest_source'] ?? null;
        $manifestCachedAt = $capabilities['manifest_cached_at'] ?? null;

        return [
            'wasm_supported' => is_bool($capabilities['wasm'] ?? null) ? $capabilities['wasm'] : null,
            'rust_wasm' => is_bool($capabilities['rust_wasm'] ?? null) ? $capabilities['rust_wasm'] : null,
            'layout_engine' => is_string($layoutEngine) && $layoutEngine !== '' ? $layoutEngine : 'unknown',
            'manifest_source' => is_string($manifestSource) && $manifestSource !== '' ? $manifestSource : 'unknown',
            'manifest_cached' => is_bool($capabilities['manifest_cached'] ?? null) ? $capabilities['manifest_cached'] : null,
            'manifest_cached_at' => is_string($manifestCachedAt) && $manifestCachedAt !== '' ? $manifestCachedAt : null,
            'network_online' => is_bool($capabilities['network_online'] ?? null) ? $capabilities['network_online'] : null,
            'service_worker_cache' => is_bool($capabilities['service_worker_cache'] ?? null) ? $capabilities['service_worker_cache'] : null,
            'media_readiness' => $this->mediaReadiness($capabilities['media_readiness'] ?? null),
            'viewport' => is_array($capabilities['viewport'] ?? null) ? $capabilities['viewport'] : null,
            'user_agent' => is_string($capabilities['user_agent'] ?? null) ? $capabilities['user_agent'] : null,
        ];
    }

    /**
     * @return array{total: int, ready: int, failed: int, pending: int, unsupported: int, status: string, updated_at: string|null}
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
                'updated_at' => null,
            ];
        }

        $status = $payload['status'] ?? null;
        $updatedAt = $payload['updated_at'] ?? null;

        return [
            'total' => max((int) ($payload['total'] ?? 0), 0),
            'ready' => max((int) ($payload['ready'] ?? 0), 0),
            'failed' => max((int) ($payload['failed'] ?? 0), 0),
            'pending' => max((int) ($payload['pending'] ?? 0), 0),
            'unsupported' => max((int) ($payload['unsupported'] ?? 0), 0),
            'status' => is_string($status) && $status !== '' ? $status : 'unknown',
            'updated_at' => is_string($updatedAt) && $updatedAt !== '' ? $updatedAt : null,
        ];
    }
}
