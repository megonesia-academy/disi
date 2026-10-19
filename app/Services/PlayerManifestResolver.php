<?php

namespace App\Services;

use App\Models\ApiConnection;
use App\Models\Device;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\Tenant;
use App\Support\OverlayCatalog;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PlayerManifestResolver
{
    /**
     * @return array<string, mixed>
     */
    public function resolve(Device $device, ?Carbon $now = null): array
    {
        if (! $device->is_active || ! $device->screen_id) {
            abort(404);
        }

        $now ??= Carbon::now();

        $device->load('screen');

        $schedule = $this->activeSchedule($device->screen, $now);

        if (! $schedule || ! $schedule->playlist->is_active || $schedule->playlist->status !== Playlist::STATUS_PUBLISHED) {
            abort(404);
        }

        return [
            ...$this->screenManifest($device->screen, $schedule, $now),
            'device' => [
                'id' => $device->id,
                'name' => $device->name,
                'key' => $device->key,
                'status' => $device->displayStatus(),
                'capabilities' => $device->capabilities,
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function resolveScreen(Screen $screen, ?Carbon $now = null): array
    {
        if (! $screen->is_active) {
            abort(404);
        }

        $now ??= Carbon::now();

        $schedule = $this->activeSchedule($screen, $now);

        if (! $schedule || ! $schedule->playlist->is_active || $schedule->playlist->status !== Playlist::STATUS_PUBLISHED) {
            abort(404);
        }

        return [
            ...$this->screenManifest($screen, $schedule, $now),
            'device' => null,
        ];
    }

    private function activeSchedule(Screen $screen, Carbon $now): ?Schedule
    {
        return Schedule::query()
            ->with(['playlist.items.mediaAsset', 'playlist.items.contentTemplate', 'playlist.items.apiConnection', 'playlist.overlays', 'screen.tenant'])
            ->where('tenant_id', $screen->tenant_id)
            ->where('screen_id', $screen->id)
            ->where('is_active', true)
            ->where(function ($query) use ($now): void {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', $now);
            })
            ->where(function ($query) use ($now): void {
                $query->whereNull('ends_at')
                    ->orWhere('ends_at', '>=', $now);
            })
            ->orderByDesc('priority')
            ->latest()
            ->get()
            ->first(fn (Schedule $schedule): bool => $this->scheduleMatches($schedule, $now));
    }

    /**
     * @return array<string, mixed>
     */
    private function screenManifest(Screen $screen, Schedule $schedule, Carbon $now): array
    {
        $screen->loadMissing('tenant');
        $tenantSettings = $screen->tenant->settings ?? [];

        return [
            'generated_at' => $now->toISOString(),
            'tenant_id' => $screen->tenant_id,
            'branding' => [
                'label' => $tenantSettings['player_accent_label'] ?? $screen->tenant->name,
                'brand_color' => $tenantSettings['brand_color'] ?? '#0891b2',
                'logo_url' => $this->logoUrl($tenantSettings['logo_path'] ?? null),
                'logo_width' => (int) ($tenantSettings['logo_width'] ?? 120),
                'logo_position' => $tenantSettings['logo_position'] ?? 'top-right',
                'logo_offset_x' => (int) ($tenantSettings['logo_offset_x'] ?? 0),
                'logo_offset_y' => (int) ($tenantSettings['logo_offset_y'] ?? 0),
                'behavior' => [
                    'default_media_fit' => $tenantSettings['player_behavior']['default_media_fit'] ?? 'cover',
                    'manifest_refresh_seconds' => $tenantSettings['player_behavior']['manifest_refresh_seconds'] ?? 60,
                    'heartbeat_interval_seconds' => $tenantSettings['player_behavior']['heartbeat_interval_seconds'] ?? 30,
                    'show_player_overlay' => $tenantSettings['player_behavior']['show_player_overlay'] ?? true,
                ],
            ],
            'screen' => [
                'id' => $screen->id,
                'name' => $screen->name,
                'location' => $screen->location,
                'orientation' => $screen->orientation,
                'resolution' => [
                    'width' => $screen->resolution_width,
                    'height' => $screen->resolution_height,
                ],
            ],
            'schedule' => [
                'id' => $schedule->id,
                'priority' => $schedule->priority,
                'starts_at' => $schedule->starts_at?->toISOString(),
                'ends_at' => $schedule->ends_at?->toISOString(),
                'days_of_week' => $schedule->days_of_week,
                'start_time' => $schedule->start_time,
                'end_time' => $schedule->end_time,
            ],
            'playlist' => [
                'id' => $schedule->playlist->id,
                'name' => $schedule->playlist->name,
                'overlays' => $this->playlistOverlays($screen, $schedule, $now),
                'items' => $schedule->playlist->items->map(fn ($item): array => [
                    'id' => $item->id,
                    'sort_order' => $item->sort_order,
                    'template' => $item->template ?? 'media',
                    'template_id' => $item->template_id,
                    'template_definition' => $item->contentTemplate ? [
                        'name' => $item->contentTemplate->name,
                        'html' => $item->contentTemplate->html,
                        'css' => $item->contentTemplate->css,
                        'schema' => $item->contentTemplate->schema ?? [],
                    ] : null,
                    'content' => $this->resolveItemContent($item),
                    'duration_seconds' => $item->duration_seconds,
                    'transition' => $item->transition,
                    'config' => $item->config,
                    'audio' => $this->audioManifest($item),
                    'split_screen' => $this->splitScreenManifest($item),
                    'media' => $item->mediaAsset ? [
                        'id' => $item->mediaAsset->id,
                        'name' => $item->mediaAsset->name,
                        'type' => $item->mediaAsset->type,
                        'disk' => $item->mediaAsset->disk,
                        'path' => $item->mediaAsset->path,
                        'url' => $this->mediaUrl($item->mediaAsset),
                        'mime_type' => $item->mediaAsset->mime_type,
                        'duration_seconds' => $item->mediaAsset->duration_seconds,
                        'width' => $item->mediaAsset->width,
                        'height' => $item->mediaAsset->height,
                        'metadata' => $item->mediaAsset->metadata,
                    ] : null,
                ])->values(),
            ],
        ];
    }

    private function scheduleMatches(Schedule $schedule, Carbon $now): bool
    {
        if (! $this->matchesDay($schedule, $now)) {
            return false;
        }

        return $this->matchesTimeWindow($schedule, $now);
    }

    private function matchesDay(Schedule $schedule, Carbon $now): bool
    {
        if (! $schedule->days_of_week || $schedule->days_of_week === []) {
            return true;
        }

        return in_array($now->isoWeekday(), $schedule->days_of_week, true);
    }

    private function matchesTimeWindow(Schedule $schedule, Carbon $now): bool
    {
        if (! $schedule->start_time && ! $schedule->end_time) {
            return true;
        }

        $currentTime = $now->format('H:i:s');
        $startTime = $schedule->start_time ?? '00:00:00';
        $endTime = $schedule->end_time ?? '23:59:59';

        if ($startTime <= $endTime) {
            return $currentTime >= $startTime && $currentTime <= $endTime;
        }

        return $currentTime >= $startTime || $currentTime <= $endTime;
    }

    private function mediaUrl(MediaAsset $mediaAsset): string
    {
        if ($mediaAsset->usesExternalUrl()) {
            return $mediaAsset->path;
        }

        if ($mediaAsset->disk === 'public') {
            return asset('storage/'.ltrim($mediaAsset->path, '/'));
        }

        return Storage::disk($mediaAsset->disk)->url($mediaAsset->path);
    }

    private function logoUrl(mixed $logoPath): ?string
    {
        if (! is_string($logoPath) || $logoPath === '') {
            return null;
        }

        return asset('storage/'.ltrim($logoPath, '/'));
    }

    /** @return array<string, mixed>|null */
    private function splitScreenManifest($item): ?array
    {
        if ($item->template !== 'split-screen') {
            return null;
        }

        $config = is_array($item->config['split_screen'] ?? null) ? $item->config['split_screen'] : [];
        $ids = array_values(array_filter([
            $config['left_media_asset_id'] ?? null,
            $config['right_media_asset_id'] ?? null,
        ]));
        $assets = MediaAsset::query()
            ->where('tenant_id', $item->tenant_id)
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $assetData = function (mixed $id) use ($assets): ?array {
            $asset = $assets->get($id);
            if (! $asset) {
                return null;
            }

            return [
                'id' => $asset->id,
                'name' => $asset->name,
                'type' => $asset->type,
                'url' => $this->mediaUrl($asset),
                'mime_type' => $asset->mime_type,
            ];
        };

        return [
            'left' => $assetData($config['left_media_asset_id'] ?? null),
            'right' => $assetData($config['right_media_asset_id'] ?? null),
            'right_source' => $config['right_source'] ?? 'media',
            'right_url' => $config['right_url'] ?? null,
            'left_width' => (int) ($config['left_width'] ?? 33),
            'right_width' => (int) ($config['right_width'] ?? 67),
        ];
    }

    /** @return array<string, mixed>|null */
    private function audioManifest($item): ?array
    {
        $audioId = $item->config['audio_asset_id'] ?? null;
        if (! $audioId && $item->template === 'announcement' && $item->mediaAsset?->type === MediaAsset::TYPE_AUDIO) {
            $audioId = $item->mediaAsset->id;
        }

        if (! $audioId) {
            return null;
        }

        $audio = MediaAsset::query()
            ->where('tenant_id', $item->tenant_id)
            ->whereKey($audioId)
            ->where('type', MediaAsset::TYPE_AUDIO)
            ->first();

        return $audio ? [
            'id' => $audio->id,
            'name' => $audio->name,
            'url' => $this->mediaUrl($audio),
            'mime_type' => $audio->mime_type,
            'loop' => (bool) ($item->config['audio_loop'] ?? false),
        ] : null;
    }

    /** @return array<string, mixed> */
    private function resolveItemContent($item): array
    {
        $fallback = is_array($item->content) ? $item->content : [];
        $source = is_array($item->data_source) ? $item->data_source : [];

        $connection = $item->apiConnection;

        if (($source['mode'] ?? 'static') !== 'api' || ! $connection?->is_active || ! $connection?->is_approved) {
            return $fallback;
        }

        $provider = config('signage.api_providers.'.$connection->provider);
        if (! is_array($provider) || ! filled($provider['base_url'] ?? null)) {
            return $fallback;
        }

        $endpoint = trim((string) ($source['url'] ?? ''));
        if ($endpoint !== '' && ! str_starts_with($endpoint, '/')) {
            $endpoint = '/'.$endpoint;
        }
        if (str_contains($endpoint, '://') || str_starts_with($endpoint, '//')) {
            return $fallback;
        }

        $url = rtrim((string) $provider['base_url'], '/').$endpoint;

        $refreshSeconds = min(max((int) ($source['refresh_seconds'] ?? 60), 10), 86400);
        $timeoutSeconds = min(max((int) ($source['timeout_seconds'] ?? 5), 1), 15);
        try {
            $credential = $connection->credential ? Crypt::decryptString($connection->credential) : null;
        } catch (\Throwable) {
            $credential = null;
        }
        $headers = match ($connection->auth_type) {
            'bearer' => $credential ? ['Authorization' => 'Bearer '.$credential] : [],
            'api_key' => $credential ? ['X-API-Key' => $credential] : [],
            default => [],
        };

        $apiData = Cache::remember(
            'playlist-item-api:'.$item->id.':'.$connection->updated_at?->timestamp,
            now()->addSeconds($refreshSeconds),
            function () use ($url, $timeoutSeconds, $headers, $source): array {
                try {
                    $response = Http::acceptJson()->withHeaders($headers)->timeout($timeoutSeconds)->get($url);
                    if (! $response->successful()) {
                        return [];
                    }

                    $payload = $response->json();
                    if (is_array($payload['data'] ?? null)) {
                        $payload = $payload['data'];
                    }

                    $payload = is_array($payload) ? $payload : [];
                    $mapping = is_array($source['mapping'] ?? null) ? $source['mapping'] : [];
                    if ($mapping === []) {
                        return $payload;
                    }

                    $mapped = [];
                    foreach ($mapping as $target => $path) {
                        $value = $payload;
                        foreach (explode('.', (string) $path) as $segment) {
                            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                                $value = null;
                                break;
                            }
                            $value = $value[$segment];
                        }
                        $mapped[$target] = $value;
                    }

                    return $mapped;
                } catch (\Throwable) {
                    return [];
                }
            },
        );

        return array_replace($fallback, $apiData);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function playlistOverlays(Screen $screen, Schedule $schedule, CarbonInterface $now): array
    {
        $overlays = [];

        foreach ($this->tenantOverlayDefaults($screen->tenant) as $index => $overlay) {
            $overlays[] = [
                'id' => 'tenant-'.$index.'-'.$overlay['type'],
                'type' => $overlay['type'],
                'config' => $overlay['config'],
                'sort_order' => $index + 1,
            ];
        }

        foreach ($schedule->playlist->overlays->where('is_active', true) as $overlay) {
            $overlays[] = [
                'id' => $overlay->id,
                'type' => $overlay->type,
                'config' => $overlay->config,
                'sort_order' => $overlay->sort_order,
            ];
        }

        $resolved = [];

        foreach ($overlays as $overlay) {
            if (! OverlayCatalog::isKnownType($overlay['type'])) {
                continue;
            }

            $config = is_array($overlay['config']) ? $overlay['config'] : [];

            if (! OverlayCatalog::matchesSchedule(OverlayCatalog::normalizeSchedule($config['schedule'] ?? []), $now)) {
                continue;
            }

            $resolved[] = [
                'id' => $overlay['id'],
                'type' => $overlay['type'],
                'config' => $this->resolveOverlayConfig($overlay['type'], $config, (string) $screen->tenant_id, (string) $overlay['id']),
                'sort_order' => $overlay['sort_order'],
            ];
        }

        return $resolved;
    }

    /**
     * @return array<int, array{type: string, config: array<string, mixed>}>
     */
    private function tenantOverlayDefaults(Tenant $tenant): array
    {
        $overlays = [];

        foreach (OverlayCatalog::normalizeList($tenant->settings['overlay_defaults'] ?? []) as $overlay) {
            if (! $overlay['is_active']) {
                continue;
            }

            $overlays[] = ['type' => $overlay['type'], 'config' => $overlay['config']];
        }

        return $overlays;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function resolveOverlayConfig(string $type, array $config, string $tenantId, string $overlayId): array
    {
        if ($type === 'badge') {
            return $this->resolveBadgeOverlay($config, $tenantId);
        }

        if (! in_array($type, OverlayCatalog::REMOTE_TYPES, true)) {
            return $config;
        }

        if (! filled($config['api_connection_id'] ?? null) || ! filled($config['endpoint'] ?? null)) {
            return $config;
        }

        $connection = ApiConnection::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($config['api_connection_id'])
            ->where('is_active', true)
            ->where('is_approved', true)
            ->first();

        if (! $connection) {
            return $config;
        }

        $baseUrl = $this->overlayBaseUrl($connection);

        if ($baseUrl === null) {
            return $config;
        }

        $endpoint = trim((string) $config['endpoint']);
        if ($endpoint !== '' && ! str_starts_with($endpoint, '/')) {
            $endpoint = '/'.$endpoint;
        }
        if (str_contains($endpoint, '://') || str_starts_with($endpoint, '//')) {
            return $config;
        }

        $url = $baseUrl.$endpoint;
        $refreshSeconds = min(max((int) ($config['refresh_seconds'] ?? 900), 10), 86400);

        try {
            $credential = $connection->credential ? Crypt::decryptString($connection->credential) : null;
        } catch (\Throwable) {
            $credential = null;
        }
        $headers = match ($connection->auth_type) {
            'bearer' => $credential ? ['Authorization' => 'Bearer '.$credential] : [],
            'api_key' => $credential ? ['X-API-Key' => $credential] : [],
            default => [],
        };

        $cacheKey = 'playlist-overlay-'.$type.':'.$overlayId.':'.md5($url).':'.$connection->updated_at?->timestamp;

        $payload = Cache::remember($cacheKey, now()->addSeconds($refreshSeconds), function () use ($type, $url, $headers, $config): array {
            try {
                $response = Http::withHeaders($headers)->timeout(10)->get($url);

                if (! $response->successful()) {
                    return [];
                }

                return match ($type) {
                    'weather' => $this->weatherPayload($response->json(), $config),
                    'api' => $this->apiOverlayPayload($response->json(), $config),
                    'rss' => $this->feedEntries($response->body(), (int) ($config['item_limit'] ?? 8)),
                    default => [],
                };
            } catch (\Throwable) {
                return [];
            }
        });

        return match ($type) {
            'weather' => array_merge($config, ['weather' => $payload]),
            'api' => array_merge($config, ['values' => $payload]),
            'rss' => array_merge($config, ['entries' => $payload]),
            default => $config,
        };
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function resolveBadgeOverlay(array $config, string $tenantId): array
    {
        $asset = MediaAsset::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($config['media_asset_id'] ?? null)
            ->first();

        $isImage = $asset !== null && in_array($asset->type, [MediaAsset::TYPE_IMAGE, MediaAsset::TYPE_IMAGE_URL], true);

        return array_merge($config, [
            'image_url' => $isImage ? $this->mediaUrl($asset) : null,
            'image_name' => $isImage ? $asset->name : null,
        ]);
    }

    private function overlayBaseUrl(ApiConnection $connection): ?string
    {
        $provider = config('signage.api_providers.'.$connection->provider);

        if (is_array($provider) && filled($provider['base_url'] ?? null)) {
            return rtrim((string) $provider['base_url'], '/');
        }

        if (blank($connection->base_url)) {
            return null;
        }

        $host = parse_url((string) $connection->base_url, PHP_URL_HOST);

        if (! is_string($host) || ! collect(config('signage.api_allowed_hosts', []))->contains(fn (string $allowed): bool => Str::is($allowed, $host))) {
            return null;
        }

        return rtrim((string) $connection->base_url, '/');
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    private function weatherPayload(mixed $payload, array $config): array
    {
        $payload = is_array($payload) ? $payload : [];
        $payload = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        $paths = is_array($config['mapping'] ?? null) ? $config['mapping'] : [];
        $paths = $paths ?: [
            'temperature' => 'current.temperature_2m',
            'humidity' => 'current.relative_humidity_2m',
            'weather_code' => 'current.weather_code',
            'wind_speed' => 'current.wind_speed_10m',
            'location' => 'timezone',
            'updated_at' => 'current.time',
        ];

        $result = [];

        foreach ($paths as $target => $path) {
            $result[$target] = $this->valueAtPath($payload, (string) $path);
        }

        return $result;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<int, array{label: string, prefix: string, suffix: string, value: string|null}>
     */
    private function apiOverlayPayload(mixed $payload, array $config): array
    {
        $payload = is_array($payload) ? $payload : [];
        $payload = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;

        $fields = is_array($config['fields'] ?? null) ? $config['fields'] : [];
        $values = [];

        foreach ($fields as $field) {
            if (! is_array($field) || ! is_string($field['path'] ?? null)) {
                continue;
            }

            $value = $this->valueAtPath($payload, $field['path']);

            if (is_float($value)) {
                $value = rtrim(rtrim(number_format($value, 2, '.', ''), '0'), '.');
            }

            $values[] = [
                'label' => (string) ($field['label'] ?? 'Value'),
                'prefix' => (string) ($field['prefix'] ?? ''),
                'suffix' => (string) ($field['suffix'] ?? ''),
                'value' => match (true) {
                    is_bool($value) => $value ? 'true' : 'false',
                    is_scalar($value) => (string) $value,
                    $value === null => null,
                    default => json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: null,
                },
            ];
        }

        return $values;
    }

    /**
     * @return array<int, array{title: string, summary: string}>
     */
    private function feedEntries(string $body, int $limit): array
    {
        $limit = max(1, min(20, $limit));
        $trimmed = ltrim($body);

        if (str_starts_with($trimmed, '{')) {
            $json = json_decode($trimmed, true);
            $items = is_array($json['items'] ?? null) ? $json['items'] : [];

            return array_slice(array_map(fn (mixed $item): array => [
                'title' => Str::limit(trim(strip_tags((string) (is_array($item) ? ($item['title'] ?? '') : ''))), 200),
                'summary' => Str::limit(trim(strip_tags((string) (is_array($item) ? ($item['summary'] ?? $item['content_text'] ?? '') : ''))), 280),
            ], $items), 0, $limit);
        }

        $document = new \DOMDocument;

        if (! @$document->loadXML($body, LIBXML_NONET)) {
            return [];
        }

        $xpath = new \DOMXPath($document);
        $nodes = $xpath->query('//*[local-name()="item"] | //*[local-name()="entry"]');

        if ($nodes === false) {
            return [];
        }

        $entries = [];

        foreach ($nodes as $node) {
            $title = $xpath->query('*[local-name()="title"]', $node)?->item(0)?->textContent ?? '';
            $summary = '';

            foreach (['description', 'summary', 'content'] as $tag) {
                $found = $xpath->query('*[local-name()="'.$tag.'"]', $node);

                if ($found !== false && $found->length > 0) {
                    $summary = $found->item(0)?->textContent ?? '';
                    break;
                }
            }

            $entries[] = [
                'title' => Str::limit(trim(strip_tags($title)), 200),
                'summary' => Str::limit(trim(strip_tags($summary)), 280),
            ];

            if (count($entries) >= $limit) {
                break;
            }
        }

        return $entries;
    }

    private function valueAtPath(mixed $payload, string $path): mixed
    {
        if ($path === '' || $path === 'this') {
            return $payload;
        }

        $value = $payload;

        foreach (explode('.', $path) as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }

            $value = $value[$segment];
        }

        return $value;
    }
}
