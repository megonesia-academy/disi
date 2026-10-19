<?php

namespace App\Services;

use App\Models\ApiConnection;
use App\Models\Device;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Screen;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

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
                'overlays' => $schedule->playlist->overlays
                    ->where('is_active', true)
                    ->map(fn ($overlay): array => [
                        'id' => $overlay->id,
                        'type' => $overlay->type,
                        'config' => $this->resolveOverlayConfig($overlay->config, (string) $screen->tenant_id, (string) $overlay->id),
                        'sort_order' => $overlay->sort_order,
                    ])->values(),
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

    /** @param array<string, mixed> $config @return array<string, mixed> */
    private function resolveOverlayConfig(array $config, string $tenantId, string $overlayId): array
    {
        if (($config['api_connection_id'] ?? null) === null || ! filled($config['endpoint'] ?? null)) {
            return $config;
        }

        $connection = ApiConnection::query()
            ->where('tenant_id', $tenantId)
            ->whereKey($config['api_connection_id'])
            ->where('is_active', true)
            ->where('is_approved', true)
            ->first();
        $provider = $connection ? config('signage.api_providers.'.$connection->provider) : null;

        if (! $connection || ! is_array($provider) || ! filled($provider['base_url'] ?? null)) {
            return $config;
        }

        $endpoint = trim((string) $config['endpoint']);
        if ($endpoint !== '' && ! str_starts_with($endpoint, '/')) {
            $endpoint = '/'.$endpoint;
        }
        if (str_contains($endpoint, '://') || str_starts_with($endpoint, '//')) {
            return $config;
        }

        $url = rtrim((string) $provider['base_url'], '/').$endpoint;
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
        $payload = Cache::remember('playlist-overlay-weather:'.$overlayId.':'.$connection->updated_at?->timestamp, now()->addSeconds($refreshSeconds), function () use ($url, $headers, $config): array {
            try {
                $response = Http::acceptJson()->withHeaders($headers)->timeout(10)->get($url);
                $payload = $response->successful() ? $response->json() : [];
                $payload = is_array($payload['data'] ?? null) ? $payload['data'] : $payload;
                $mapping = is_array($config['mapping'] ?? null) ? $config['mapping'] : [];
                $paths = $mapping ?: [
                    'temperature' => 'current.temperature_2m',
                    'humidity' => 'current.relative_humidity_2m',
                    'weather_code' => 'current.weather_code',
                    'wind_speed' => 'current.wind_speed_10m',
                    'location' => 'timezone',
                    'updated_at' => 'current.time',
                ];
                $result = [];
                foreach ($paths as $target => $path) {
                    $value = $payload;
                    foreach (explode('.', (string) $path) as $segment) {
                        if (! is_array($value) || ! array_key_exists($segment, $value)) {
                            $value = null;
                            break;
                        }
                        $value = $value[$segment];
                    }
                    $result[$target] = $value;
                }

                return $result;
            } catch (\Throwable) {
                return [];
            }
        });

        return array_merge($config, ['weather' => $payload]);
    }
}
