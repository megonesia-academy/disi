<?php

namespace App\Support;

use Carbon\CarbonInterface;

/**
 * Single source of truth for playlist overlay types, their default config and
 * the normalisation used when an overlay is persisted.
 */
class OverlayCatalog
{
    public const MAX_OVERLAYS = 8;

    public const POSITIONS = [
        'top',
        'bottom',
        'top-left',
        'top-right',
        'bottom-left',
        'bottom-right',
    ];

    public const THEMES = ['dark', 'light', 'blur'];

    public const TYPES = [
        'ticker',
        'clock',
        'weather',
        'date',
        'location',
        'greeting',
        'next',
        'badge',
        'countdown',
        'hours',
        'title',
        'progress',
        'api',
        'rss',
    ];

    /**
     * Overlay types that read data from an API connection.
     */
    public const REMOTE_TYPES = ['weather', 'api', 'rss'];

    /** @return array<int, string> */
    public static function types(): array
    {
        return self::TYPES;
    }

    /** @return array<int, string> */
    public static function positions(): array
    {
        return self::POSITIONS;
    }

    public static function isKnownType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    public static function defaultPosition(string $type): string
    {
        return match ($type) {
            'ticker', 'progress' => 'bottom',
            'title', 'next' => 'bottom-left',
            'hours' => 'top-left',
            default => 'top-right',
        };
    }

    /**
     * Normalise an overlay list coming from JSON storage (tenant settings or a
     * request payload) and drop entries with unknown types.
     *
     * @return array<int, array{type: string, config: array<string, mixed>, is_active: bool}>
     */
    public static function normalizeList(mixed $overlays): array
    {
        if (! is_array($overlays)) {
            return [];
        }

        $normalized = [];

        foreach ($overlays as $overlay) {
            if (! is_array($overlay) || ! is_string($overlay['type'] ?? null) || ! self::isKnownType($overlay['type'])) {
                continue;
            }

            $normalized[] = [
                'type' => $overlay['type'],
                'config' => self::normalize($overlay['type'], is_array($overlay['config'] ?? null) ? $overlay['config'] : []),
                'is_active' => (bool) ($overlay['is_active'] ?? true),
            ];
        }

        return $normalized;
    }

    /**
     * @param  array<string, mixed>  $config
     * @return array<string, mixed>
     */
    public static function normalize(string $type, array $config): array
    {
        $normalized = [
            'position' => self::normalizePosition($type, $config['position'] ?? null),
            'offset_x' => self::clampInt($config['offset_x'] ?? 0, -500, 500),
            'offset_y' => self::clampInt($config['offset_y'] ?? 0, -500, 500),
            'theme' => self::normalizeTheme($config['theme'] ?? null),
            'text_color' => self::color($config['text_color'] ?? null, '#ffffff'),
            'background_color' => self::color($config['background_color'] ?? null, '#111827'),
            'schedule' => self::normalizeSchedule($config['schedule'] ?? []),
        ];

        return array_merge($normalized, match ($type) {
            'ticker' => [
                'text' => self::string($config['text'] ?? null, 1000),
                'source' => in_array($config['source'] ?? null, ['static', 'array'], true) ? $config['source'] : 'static',
                'items' => self::stringList($config['items'] ?? [], 100, 1000),
                'mode' => in_array($config['mode'] ?? null, ['loop', 'rotate'], true) ? $config['mode'] : 'loop',
                'item_duration_seconds' => self::clampInt($config['item_duration_seconds'] ?? 8, 2, 3600),
                'speed' => self::clampInt($config['speed'] ?? 60, 10, 200),
            ],
            'clock' => [
                'format' => in_array($config['format'] ?? null, ['24h', '12h'], true) ? $config['format'] : '24h',
            ],
            'weather' => [
                'api_connection_id' => self::string($config['api_connection_id'] ?? null, 64),
                'endpoint' => self::string($config['endpoint'] ?? null, 2048),
                'refresh_seconds' => self::clampInt($config['refresh_seconds'] ?? 900, 10, 86400),
                'mapping' => self::mapping($config['mapping'] ?? []),
            ],
            'date' => [
                'format' => in_array($config['format'] ?? null, ['long', 'short', 'iso'], true) ? $config['format'] : 'long',
                'locale' => in_array($config['locale'] ?? null, ['id', 'en'], true) ? $config['locale'] : 'id',
            ],
            'location' => [
                'label' => self::string($config['label'] ?? null, 120),
                'separator' => self::string($config['separator'] ?? null, 10) ?? '·',
            ],
            'greeting' => [
                'template' => self::string($config['template'] ?? null, 255) ?? 'Selamat {greeting}',
                'name' => in_array($config['name'] ?? null, ['tenant', 'screen'], true) ? $config['name'] : 'tenant',
            ],
            'next' => [
                'next_mode' => in_array($config['next_mode'] ?? null, ['next', 'now'], true) ? $config['next_mode'] : 'next',
                'label' => self::string($config['label'] ?? null, 60) ?? 'Berikutnya',
            ],
            'badge' => [
                'media_asset_id' => self::string($config['media_asset_id'] ?? null, 64),
                'size' => self::clampInt($config['size'] ?? 96, 16, 600),
            ],
            'countdown' => [
                'target_at' => self::string($config['target_at'] ?? null, 40),
                'prefix' => self::string($config['prefix'] ?? null, 120),
                'suffix' => self::string($config['suffix'] ?? null, 120),
                'finished_text' => self::string($config['finished_text'] ?? null, 120),
            ],
            'hours' => [
                'title' => self::string($config['title'] ?? null, 120),
                'open_label' => self::string($config['open_label'] ?? null, 60) ?? 'BUKA',
                'closed_label' => self::string($config['closed_label'] ?? null, 60) ?? 'TUTUP',
                'show_times' => (bool) ($config['show_times'] ?? true),
            ],
            'title' => [
                'title' => self::string($config['title'] ?? null, 120),
                'subtitle' => self::string($config['subtitle'] ?? null, 200),
                'align' => in_array($config['align'] ?? null, ['left', 'center', 'right'], true) ? $config['align'] : 'left',
            ],
            'progress' => [
                'color' => self::color($config['color'] ?? null, '#22d3ee'),
                'track_color' => self::color($config['track_color'] ?? null, '#111827'),
                'height' => self::clampInt($config['height'] ?? 6, 2, 40),
            ],
            'api' => [
                'api_connection_id' => self::string($config['api_connection_id'] ?? null, 64),
                'endpoint' => self::string($config['endpoint'] ?? null, 2048),
                'refresh_seconds' => self::clampInt($config['refresh_seconds'] ?? 300, 10, 86400),
                'title' => self::string($config['title'] ?? null, 120),
                'layout' => in_array($config['layout'] ?? null, ['list', 'ticker'], true) ? $config['layout'] : 'list',
                'fields' => self::fields($config['fields'] ?? []),
            ],
            'rss' => [
                'api_connection_id' => self::string($config['api_connection_id'] ?? null, 64),
                'endpoint' => self::string($config['endpoint'] ?? null, 2048),
                'refresh_seconds' => self::clampInt($config['refresh_seconds'] ?? 900, 10, 86400),
                'title' => self::string($config['title'] ?? null, 120),
                'layout' => in_array($config['layout'] ?? null, ['list', 'ticker'], true) ? $config['layout'] : 'ticker',
                'item_limit' => self::clampInt($config['item_limit'] ?? 8, 1, 20),
                'item_duration_seconds' => self::clampInt($config['item_duration_seconds'] ?? 8, 2, 120),
            ],
            default => [],
        });
    }

    /**
     * @return array{days: array<int, int>, start: string|null, end: string|null}|array{}
     */
    public static function normalizeSchedule(mixed $schedule): array
    {
        if (! is_array($schedule)) {
            return [];
        }

        $days = array_values(array_unique(array_filter(array_map(
            static fn (mixed $day): int => (int) $day,
            is_array($schedule['days'] ?? null) ? $schedule['days'] : [],
        ), static fn (int $day): bool => $day >= 1 && $day <= 7)));
        sort($days);

        $start = self::time($schedule['start'] ?? null);
        $end = self::time($schedule['end'] ?? null);

        if ($days === [] && $start === null && $end === null) {
            return [];
        }

        return ['days' => $days, 'start' => $start, 'end' => $end];
    }

    /**
     * @param  array<string, mixed>  $schedule
     */
    public static function matchesSchedule(array $schedule, CarbonInterface $now): bool
    {
        if ($schedule === []) {
            return true;
        }

        $days = is_array($schedule['days'] ?? null) ? $schedule['days'] : [];

        if ($days !== [] && ! in_array($now->isoWeekday(), array_map('intval', $days), true)) {
            return false;
        }

        $start = is_string($schedule['start'] ?? null) ? $schedule['start'] : null;
        $end = is_string($schedule['end'] ?? null) ? $schedule['end'] : null;

        if ($start === null && $end === null) {
            return true;
        }

        $time = $now->format('H:i');

        return match (true) {
            $start !== null && $end !== null => $start <= $end
                ? ($time >= $start && $time <= $end)
                : ($time >= $start || $time <= $end),
            $start !== null => $time >= $start,
            default => $time <= (string) $end,
        };
    }

    private static function normalizePosition(string $type, mixed $position): string
    {
        return is_string($position) && in_array($position, self::POSITIONS, true)
            ? $position
            : self::defaultPosition($type);
    }

    private static function normalizeTheme(mixed $theme): string
    {
        return is_string($theme) && in_array($theme, self::THEMES, true) ? $theme : 'dark';
    }

    private static function string(mixed $value, int $max): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : mb_substr($value, 0, $max);
    }

    private static function time(mixed $value): ?string
    {
        if (! is_string($value) || preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', trim($value)) !== 1) {
            return null;
        }

        return trim($value);
    }

    private static function color(mixed $value, string $fallback): string
    {
        return is_string($value) && preg_match('/^#[0-9A-Fa-f]{3,8}$/', trim($value)) === 1
            ? trim($value)
            : $fallback;
    }

    private static function clampInt(mixed $value, int $min, int $max): int
    {
        $value = is_numeric($value) ? (int) $value : $min;

        return max($min, min($max, $value));
    }

    /**
     * @return array<int, string>
     */
    private static function stringList(mixed $value, int $limit, int $maxLength): array
    {
        if (! is_array($value)) {
            return [];
        }

        $items = [];

        foreach (array_slice(array_values($value), 0, $limit) as $item) {
            if (is_string($item) && trim($item) !== '') {
                $items[] = mb_substr(trim($item), 0, $maxLength);
            }
        }

        return $items;
    }

    /**
     * @return array<string, string>
     */
    private static function mapping(mixed $mapping): array
    {
        if (! is_array($mapping)) {
            return [];
        }

        $result = [];

        foreach ($mapping as $target => $path) {
            if (is_string($target) && is_string($path) && trim($target) !== '' && trim($path) !== '') {
                $result[mb_substr(trim($target), 0, 60)] = mb_substr(trim($path), 0, 255);
            }
        }

        return $result;
    }

    /**
     * @return array<int, array{label: string, path: string, prefix: string, suffix: string}>
     */
    private static function fields(mixed $fields): array
    {
        if (! is_array($fields)) {
            return [];
        }

        $result = [];

        foreach (array_slice(array_values($fields), 0, 8) as $field) {
            if (! is_array($field) || ! is_string($field['path'] ?? null) || trim($field['path']) === '') {
                continue;
            }

            $result[] = [
                'label' => self::string($field['label'] ?? null, 60) ?? 'Value',
                'path' => mb_substr(trim($field['path']), 0, 255),
                'prefix' => self::string($field['prefix'] ?? null, 20) ?? '',
                'suffix' => self::string($field['suffix'] ?? null, 20) ?? '',
            ];
        }

        return $result;
    }
}
