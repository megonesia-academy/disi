<?php

namespace App\Http\Controllers;

use App\Models\ApiConnection;
use App\Models\ContentTemplate;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\PlaylistOverlay;
use App\Models\Tenant;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class PlaylistController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        return Inertia::render('playlists/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'playlists' => Playlist::query()
                ->with(['items.mediaAsset', 'items.contentTemplate', 'items.apiConnection', 'overlays'])
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get()
                ->map(fn (Playlist $playlist): array => [
                    'id' => $playlist->id,
                    'name' => $playlist->name,
                    'description' => $playlist->description,
                    'is_active' => $playlist->is_active,
                    'status' => $playlist->status,
                    'overlays' => $playlist->overlays->map(fn (PlaylistOverlay $overlay): array => [
                        'id' => $overlay->id,
                        'type' => $overlay->type,
                        'config' => $overlay->config,
                        'sort_order' => $overlay->sort_order,
                        'is_active' => $overlay->is_active,
                    ])->values(),
                    'items' => $playlist->items->map(fn (PlaylistItem $item): array => [
                        'id' => $item->id,
                        'media_asset_id' => $item->media_asset_id,
                        'template' => $item->template,
                        'template_id' => $item->template_id,
                        'api_connection_id' => $item->api_connection_id,
                        'template_definition' => $item->contentTemplate ? [
                            'id' => $item->contentTemplate->id,
                            'name' => $item->contentTemplate->name,
                            'html' => $item->contentTemplate->html,
                            'css' => $item->contentTemplate->css,
                            'schema' => $item->contentTemplate->schema,
                        ] : null,
                        'content' => $item->content,
                        'data_source' => $this->dataSourceForEditor($item->data_source),
                        'sort_order' => $item->sort_order,
                        'duration_seconds' => $item->duration_seconds,
                        'transition' => $item->transition,
                        'config' => $item->config ?? ['fit' => 'cover'],
                        'media' => [
                            'id' => $item->mediaAsset?->id,
                            'name' => $item->mediaAsset?->name,
                            'type' => $item->mediaAsset?->type,
                            'url' => $item->mediaAsset ? $this->mediaUrl($item->mediaAsset) : null,
                        ],
                    ])->values(),
                ]),
            'mediaAssets' => MediaAsset::query()
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get()
                ->map(fn (MediaAsset $asset): array => [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'type' => $asset->type,
                    'url' => $this->mediaUrl($asset),
                    'duration_seconds' => $asset->duration_seconds,
                ]),
            'templates' => $this->templatesFor($tenant),
            'apiConnections' => $this->apiConnectionsFor($tenant),
        ]);
    }

    public function create(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        return Inertia::render('playlists/Create', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'mediaAssets' => $this->mediaAssetsFor($tenant),
            'templates' => $this->templatesFor($tenant),
            'apiConnections' => $this->apiConnectionsFor($tenant),
        ]);
    }

    public function edit(Request $request, Playlist $playlist): Response
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $playlist);

        $playlist->load(['items.mediaAsset', 'items.contentTemplate', 'items.apiConnection', 'overlays']);

        return Inertia::render('playlists/Edit', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'playlist' => [
                'id' => $playlist->id,
                'name' => $playlist->name,
                'description' => $playlist->description,
                'is_active' => $playlist->is_active,
                'status' => $playlist->status,
                'overlays' => $playlist->overlays->map(fn (PlaylistOverlay $overlay): array => [
                    'id' => $overlay->id,
                    'type' => $overlay->type,
                    'config' => $overlay->config,
                    'is_active' => $overlay->is_active,
                ])->values(),
                'items' => $playlist->items->map(fn (PlaylistItem $item): array => [
                    'id' => $item->id,
                    'media_asset_id' => $item->media_asset_id,
                    'template' => $item->template,
                    'template_id' => $item->template_id,
                    'api_connection_id' => $item->api_connection_id,
                    'template_definition' => $item->contentTemplate ? [
                        'id' => $item->contentTemplate->id,
                        'name' => $item->contentTemplate->name,
                        'html' => $item->contentTemplate->html,
                        'css' => $item->contentTemplate->css,
                        'schema' => $item->contentTemplate->schema,
                    ] : null,
                    'content' => $item->content,
                    'data_source' => $this->dataSourceForEditor($item->data_source),
                    'duration_seconds' => $item->duration_seconds,
                    'transition' => $item->transition,
                    'config' => $item->config ?? ['fit' => 'cover', 'overlays' => []],
                ])->values(),
            ],
            'mediaAssets' => $this->mediaAssetsFor($tenant),
            'templates' => $this->templatesFor($tenant),
            'apiConnections' => $this->apiConnectionsFor($tenant),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $validated = $this->validated($request, $tenant);

        $playlist = DB::transaction(function () use ($tenant, $validated): Playlist {
            $playlist = Playlist::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'],
                'status' => $validated['status'],
            ]);

            $this->syncItems($playlist, $tenant, $validated['items'] ?? []);
            $this->syncOverlays($playlist, $tenant, $validated['overlays'] ?? []);

            return $playlist;
        });

        $this->activityLogger->log($request, $tenant, 'playlist.created', $playlist, [
            'playlist_name' => $playlist->name,
            'items_count' => count($validated['items'] ?? []),
        ]);

        return to_route('playlists.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Playlist $playlist): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $playlist);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $validated = $this->validated($request, $tenant);

        DB::transaction(function () use ($playlist, $tenant, $validated): void {
            $playlist->update([
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'is_active' => $validated['is_active'],
                'status' => $validated['status'],
            ]);

            $playlist->items()->delete();
            $this->syncItems($playlist, $tenant, $validated['items'] ?? []);
            $playlist->overlays()->delete();
            $this->syncOverlays($playlist, $tenant, $validated['overlays'] ?? []);
        });

        $this->activityLogger->log($request, $tenant, 'playlist.updated', $playlist, [
            'playlist_name' => $playlist->name,
            'items_count' => count($validated['items'] ?? []),
            'is_active' => $playlist->is_active,
        ]);

        return to_route('playlists.edit', $playlist);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Playlist $playlist): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $playlist);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $properties = [
            'playlist_name' => $playlist->name,
            'is_active' => $playlist->is_active,
        ];

        $this->activityLogger->log($request, $tenant, 'playlist.deleted', $playlist, $properties);

        $playlist->delete();

        return to_route('playlists.index');
    }

    public function duplicate(Request $request, Playlist $playlist): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $playlist);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $copy = DB::transaction(function () use ($playlist, $tenant): Playlist {
            $copy = Playlist::query()->create([
                'tenant_id' => $tenant->id,
                'name' => $playlist->name.' Copy',
                'description' => $playlist->description,
                'is_active' => false,
                'status' => Playlist::STATUS_DRAFT,
            ]);

            foreach ($playlist->items as $item) {
                $copy->items()->create([
                    'tenant_id' => $tenant->id,
                    'media_asset_id' => $item->media_asset_id,
                    'template' => $item->template,
                    'template_id' => $item->template_id,
                    'api_connection_id' => $item->api_connection_id,
                    'content' => $item->content,
                    'data_source' => $this->dataSourceForEditor($item->data_source),
                    'sort_order' => $item->sort_order,
                    'duration_seconds' => $item->duration_seconds,
                    'transition' => $item->transition,
                    'config' => $item->config,
                ]);
            }

            foreach ($playlist->overlays as $overlay) {
                $copy->overlays()->create([
                    'tenant_id' => $tenant->id,
                    'type' => $overlay->type,
                    'config' => $overlay->config,
                    'sort_order' => $overlay->sort_order,
                    'is_active' => false,
                ]);
            }

            return $copy;
        });

        $this->activityLogger->log($request, $tenant, 'playlist.duplicated', $copy, [
            'playlist_name' => $copy->name,
            'source_playlist_id' => $playlist->id,
        ]);

        return to_route('playlists.index');
    }

    public function testDataSource(Request $request): JsonResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $validated = $request->validate([
            'url' => ['required', 'url', 'max:2048'],
            'timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:15'],
            'auth_type' => ['nullable', Rule::in(['none', 'bearer', 'api_key'])],
            'auth_value' => ['nullable', 'string', 'max:1000'],
            'mapping' => ['nullable', 'array'],
            'mapping.*' => ['string', 'max:255'],
        ]);

        $host = parse_url($validated['url'], PHP_URL_HOST);
        abort_unless(is_string($host) && collect(config('signage.api_allowed_hosts', []))->contains(fn (string $allowed): bool => Str::is($allowed, $host)), 422, 'Host API belum diizinkan.');

        $headers = [];
        if (($validated['auth_type'] ?? 'none') === 'bearer' && filled($validated['auth_value'] ?? null)) {
            $headers['Authorization'] = 'Bearer '.$validated['auth_value'];
        }
        if (($validated['auth_type'] ?? 'none') === 'api_key' && filled($validated['auth_value'] ?? null)) {
            $headers['X-API-Key'] = $validated['auth_value'];
        }

        try {
            $response = Http::acceptJson()->withHeaders($headers)->timeout((int) ($validated['timeout_seconds'] ?? 5))->get($validated['url']);
        } catch (\Throwable $exception) {
            return response()->json(['message' => 'API tidak dapat dihubungi.', 'detail' => $exception->getMessage()], 422);
        }

        if (! $response->successful()) {
            return response()->json(['message' => 'API mengembalikan HTTP '.$response->status(), 'status' => $response->status()], 422);
        }

        $payload = $response->json();
        if (is_array($payload['data'] ?? null)) {
            $payload = $payload['data'];
        }

        $mapped = $this->mapApiData(is_array($payload) ? $payload : [], $validated['mapping'] ?? []);

        return response()->json(['message' => 'API connected.', 'data' => $mapped, 'raw' => $payload]);
    }

    /**
     * @return array{name: string, description?: string|null, is_active: bool, status: string, items?: array<int, array{media_asset_id: string, duration_seconds: int, transition?: string|null}>}
     */
    private function validated(Request $request, Tenant $tenant): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
            'status' => ['required', Rule::in([Playlist::STATUS_DRAFT, Playlist::STATUS_PUBLISHED])],
            'overlays' => ['array', 'max:4'],
            'overlays.*.type' => ['required', Rule::in(['ticker', 'clock', 'weather'])],
            'overlays.*.config.text' => ['nullable', 'string', 'max:1000'],
            'overlays.*.config.source' => ['nullable', Rule::in(['static', 'array'])],
            'overlays.*.config.items' => ['nullable', 'array', 'max:100'],
            'overlays.*.config.items.*' => ['string', 'max:1000'],
            'overlays.*.config.mode' => ['nullable', Rule::in(['loop', 'rotate'])],
            'overlays.*.config.item_duration_seconds' => ['nullable', 'integer', 'min:2', 'max:3600'],
            'overlays.*.config.format' => ['nullable', Rule::in(['24h', '12h'])],
            'overlays.*.config.position' => ['nullable', Rule::in(['top', 'bottom', 'top-left', 'top-right', 'bottom-left', 'bottom-right'])],
            'overlays.*.config.offset_x' => ['nullable', 'integer', 'min:-500', 'max:500'],
            'overlays.*.config.offset_y' => ['nullable', 'integer', 'min:-500', 'max:500'],
            'overlays.*.config.speed' => ['nullable', 'integer', 'min:10', 'max:200'],
            'overlays.*.config.text_color' => ['nullable', 'string', 'max:20'],
            'overlays.*.config.background_color' => ['nullable', 'string', 'max:20'],
            'overlays.*.config.api_connection_id' => [
                'nullable',
                'required_if:overlays.*.type,weather',
                Rule::exists('api_connections', 'id')->where('tenant_id', $tenant->id)->where('is_active', true)->where('is_approved', true),
            ],
            'overlays.*.config.endpoint' => ['nullable', 'string', 'max:2048', 'required_if:overlays.*.type,weather'],
            'overlays.*.config.refresh_seconds' => ['nullable', 'integer', 'min:10', 'max:86400'],
            'items' => ['array'],
            'items.*.template' => ['nullable', Rule::in(['media', 'announcement', 'data-table', 'patient-queue', 'flight-board', 'menu', 'weather', 'split-screen', 'custom'])],
            'items.*.template_id' => [
                'nullable',
                'required_if:items.*.template,custom',
                Rule::exists('content_templates', 'id')->where('tenant_id', $tenant->id),
            ],
            'items.*.api_connection_id' => [
                'nullable',
                Rule::exists('api_connections', 'id')->where('tenant_id', $tenant->id)->where('is_active', true)->where('is_approved', true),
            ],
            'items.*.media_asset_id' => [
                'nullable',
                'required_if:items.*.template,media',
                Rule::exists('media_assets', 'id')->where('tenant_id', $tenant->id),
            ],
            'items.*.content' => ['nullable', 'array'],
            'items.*.data_source' => ['nullable', 'array'],
            'items.*.data_source.mode' => ['nullable', Rule::in(['static', 'api'])],
            'items.*.data_source.url' => ['nullable', 'string', 'max:2048', 'required_if:items.*.data_source.mode,api'],
            'items.*.data_source.refresh_seconds' => ['nullable', 'integer', 'min:10', 'max:86400'],
            'items.*.data_source.timeout_seconds' => ['nullable', 'integer', 'min:1', 'max:15'],
            'items.*.data_source.mapping' => ['nullable', 'array'],
            'items.*.data_source.mapping.*' => ['string', 'max:255'],
            'items.*.duration_seconds' => ['required', 'integer', 'min:1', 'max:86400'],
            'items.*.transition' => ['nullable', 'string', 'max:255'],
            'items.*.config' => ['nullable', 'array'],
            'items.*.config.fit' => ['nullable', Rule::in(['cover', 'contain', 'fill'])],
            'items.*.config.audio_asset_id' => ['nullable', Rule::exists('media_assets', 'id')->where('tenant_id', $tenant->id)->where('type', MediaAsset::TYPE_AUDIO)],
            'items.*.config.audio_loop' => ['nullable', 'boolean'],
            'items.*.config.split_screen' => ['nullable', 'array'],
            'items.*.config.split_screen.left_media_asset_id' => ['nullable', Rule::exists('media_assets', 'id')->where('tenant_id', $tenant->id)],
            'items.*.config.split_screen.right_media_asset_id' => ['nullable', Rule::exists('media_assets', 'id')->where('tenant_id', $tenant->id)],
            'items.*.config.split_screen.right_source' => ['nullable', Rule::in(['media', 'youtube', 'url'])],
            'items.*.config.split_screen.right_url' => ['nullable', 'string', 'max:2048'],
            'items.*.config.split_screen.left_width' => ['nullable', 'integer', 'min:20', 'max:80'],
            'items.*.config.split_screen.right_width' => ['nullable', 'integer', 'min:20', 'max:80'],
            'items.*.config.overlays' => ['nullable', 'array', 'max:4'],
            'items.*.config.overlays.*.type' => ['required', Rule::in(['ticker'])],
            'items.*.config.overlays.*.text' => ['required', 'string', 'max:1000'],
            'items.*.config.overlays.*.position' => ['nullable', Rule::in(['top', 'bottom'])],
            'items.*.config.overlays.*.speed' => ['nullable', 'integer', 'min:10', 'max:200'],
            'items.*.config.overlays.*.text_color' => ['nullable', 'string', 'max:20'],
            'items.*.config.overlays.*.background_color' => ['nullable', 'string', 'max:20'],
        ]);
    }

    /**
     * @param  array<int, array{media_asset_id: string, duration_seconds: int, transition?: string|null, config?: array<string, mixed>}>  $items
     */
    private function syncItems(Playlist $playlist, Tenant $tenant, array $items): void
    {
        foreach (array_values($items) as $index => $item) {
            PlaylistItem::query()->create([
                'tenant_id' => $tenant->id,
                'playlist_id' => $playlist->id,
                'media_asset_id' => $item['media_asset_id'],
                'template' => $item['template'] ?? 'media',
                'template_id' => $item['template_id'] ?? null,
                'api_connection_id' => $item['api_connection_id'] ?? null,
                'content' => $item['content'] ?? [],
                'data_source' => $this->normalizeDataSource($item['data_source'] ?? ['mode' => 'static']),
                'sort_order' => $index + 1,
                'duration_seconds' => $item['duration_seconds'],
                'transition' => $item['transition'] ?? null,
                'config' => [
                    'fit' => $item['config']['fit'] ?? 'cover',
                    'overlays' => array_values($item['config']['overlays'] ?? []),
                    'audio_asset_id' => $item['config']['audio_asset_id'] ?? null,
                    'audio_loop' => (bool) ($item['config']['audio_loop'] ?? false),
                    'split_screen' => [
                        'left_media_asset_id' => $item['config']['split_screen']['left_media_asset_id'] ?? null,
                        'right_media_asset_id' => $item['config']['split_screen']['right_media_asset_id'] ?? null,
                        'right_source' => $item['config']['split_screen']['right_source'] ?? 'media',
                        'right_url' => $item['config']['split_screen']['right_url'] ?? null,
                        'left_width' => (int) ($item['config']['split_screen']['left_width'] ?? 33),
                        'right_width' => (int) ($item['config']['split_screen']['right_width'] ?? 67),
                    ],
                ],
            ]);
        }
    }

    /**
     * @param  array<int, array{type: string, config: array<string, mixed>}>  $overlays
     */
    private function syncOverlays(Playlist $playlist, Tenant $tenant, array $overlays): void
    {
        foreach (array_values($overlays) as $index => $overlay) {
            $playlist->overlays()->create([
                'tenant_id' => $tenant->id,
                'type' => $overlay['type'],
                'config' => [
                    'text' => $overlay['config']['text'] ?? null,
                    'source' => $overlay['config']['source'] ?? 'static',
                    'items' => array_values($overlay['config']['items'] ?? []),
                    'mode' => $overlay['config']['mode'] ?? 'loop',
                    'item_duration_seconds' => $overlay['config']['item_duration_seconds'] ?? 8,
                    'format' => $overlay['config']['format'] ?? '24h',
                    'position' => $overlay['config']['position'] ?? ($overlay['type'] === 'clock' ? 'top-right' : 'bottom'),
                    'offset_x' => (int) ($overlay['config']['offset_x'] ?? 0),
                    'offset_y' => (int) ($overlay['config']['offset_y'] ?? 0),
                    'speed' => $overlay['config']['speed'] ?? 60,
                    'text_color' => $overlay['config']['text_color'] ?? '#ffffff',
                    'background_color' => $overlay['config']['background_color'] ?? '#111827',
                    'api_connection_id' => $overlay['config']['api_connection_id'] ?? null,
                    'endpoint' => $overlay['config']['endpoint'] ?? null,
                    'refresh_seconds' => $overlay['config']['refresh_seconds'] ?? 900,
                ],
                'sort_order' => $index + 1,
                'is_active' => $overlay['is_active'] ?? true,
            ]);
        }
    }

    private function mediaUrl(MediaAsset $mediaAsset): string
    {
        if ($mediaAsset->type === MediaAsset::TYPE_WEB) {
            return $mediaAsset->path;
        }

        if ($mediaAsset->disk === 'public') {
            return asset('storage/'.ltrim($mediaAsset->path, '/'));
        }

        return Storage::disk($mediaAsset->disk)->url($mediaAsset->path);
    }

    /** @return array<int, array{id: string, name: string, type: string, url: string, duration_seconds: int|null}> */
    private function mediaAssetsFor(Tenant $tenant): array
    {
        return MediaAsset::query()
            ->where('tenant_id', $tenant->id)
            ->orderBy('name')
            ->get()
            ->map(fn (MediaAsset $asset): array => [
                'id' => $asset->id,
                'name' => $asset->name,
                'type' => $asset->type,
                'url' => $this->mediaUrl($asset),
                'duration_seconds' => $asset->duration_seconds,
            ])
            ->all();
    }

    /** @return array<int, array{id: string, name: string, slug: string, html: string, css: string|null, schema: array<mixed>, is_active: bool}> */
    private function templatesFor(Tenant $tenant): array
    {
        return ContentTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ContentTemplate $template): array => [
                'id' => $template->id,
                'name' => $template->name,
                'slug' => $template->slug,
                'html' => $template->html,
                'css' => $template->css,
                'schema' => $template->schema ?? [],
                'is_active' => $template->is_active,
            ])->all();
    }

    /** @param array<string, mixed> $source */
    private function normalizeDataSource(array $source): array
    {
        return [
            'mode' => $source['mode'] ?? 'static',
            'url' => $source['url'] ?? null,
            'refresh_seconds' => (int) ($source['refresh_seconds'] ?? 60),
            'timeout_seconds' => (int) ($source['timeout_seconds'] ?? 5),
            'mapping' => $source['mapping'] ?? [],
        ];
    }

    /** @param array<string, mixed>|null $source @return array<string, mixed> */
    private function dataSourceForEditor(?array $source): array
    {
        return [
            'mode' => $source['mode'] ?? 'static',
            'url' => $source['url'] ?? '',
            'refresh_seconds' => $source['refresh_seconds'] ?? 60,
            'timeout_seconds' => $source['timeout_seconds'] ?? 5,
            'auth_type' => $source['auth_type'] ?? 'none',
            'auth_value' => '',
            'mapping' => $source['mapping'] ?? [],
        ];
    }

    /** @param array<string, mixed> $payload @param array<string, string> $mapping @return array<string, mixed> */
    private function mapApiData(array $payload, array $mapping): array
    {
        if ($mapping === []) {
            return $payload;
        }

        $mapped = [];
        foreach ($mapping as $target => $path) {
            $value = $payload;
            foreach (explode('.', $path) as $segment) {
                if (! is_array($value) || ! array_key_exists($segment, $value)) {
                    $value = null;
                    break;
                }
                $value = $value[$segment];
            }
            $mapped[$target] = $value;
        }

        return $mapped;
    }

    /** @return array<int, array{id: string, name: string, provider: string}> */
    private function apiConnectionsFor(Tenant $tenant): array
    {
        return ApiConnection::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where('is_approved', true)
            ->orderBy('name')
            ->get()
            ->map(fn (ApiConnection $connection): array => [
                'id' => $connection->id,
                'name' => $connection->name,
                'provider' => $connection->provider,
            ])->all();
    }
}
