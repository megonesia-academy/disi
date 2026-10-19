export type OverlayTheme = 'dark' | 'light' | 'blur';

export type OverlayPosition =
    | 'top'
    | 'bottom'
    | 'top-left'
    | 'top-right'
    | 'bottom-left'
    | 'bottom-right';

export type OverlayType =
    | 'ticker'
    | 'clock'
    | 'weather'
    | 'date'
    | 'location'
    | 'greeting'
    | 'next'
    | 'badge'
    | 'countdown'
    | 'hours'
    | 'title'
    | 'progress'
    | 'api'
    | 'rss';

export type OverlaySchedule = {
    days?: number[];
    start?: string;
    end?: string;
};

export type OverlayField = {
    label?: string;
    path?: string;
    prefix?: string;
    suffix?: string;
};

export type OverlayValue = {
    label?: string;
    value?: string | null;
    prefix?: string;
    suffix?: string;
};

export type OverlayFeedEntry = {
    title?: string;
    summary?: string;
};

export type OverlayConfig = {
    type?: string;
    text?: string;
    source?: 'static' | 'array';
    items?: string[];
    mode?: 'loop' | 'rotate';
    item_duration_seconds?: number;
    speed?: number;
    text_color?: string;
    background_color?: string;
    format?: string;
    locale?: 'id' | 'en';
    position?: OverlayPosition;
    offset_x?: number;
    offset_y?: number;
    theme?: OverlayTheme;
    schedule?: OverlaySchedule;
    label?: string;
    separator?: string;
    template?: string;
    name?: 'tenant' | 'screen';
    next_mode?: 'next' | 'now';
    media_asset_id?: string | null;
    size?: number;
    image_url?: string | null;
    image_name?: string | null;
    target_at?: string;
    prefix?: string;
    suffix?: string;
    finished_text?: string;
    title?: string;
    subtitle?: string;
    open_label?: string;
    closed_label?: string;
    show_times?: boolean;
    align?: 'left' | 'center' | 'right';
    color?: string;
    track_color?: string;
    height?: number;
    layout?: 'list' | 'ticker';
    item_limit?: number;
    fields?: OverlayField[];
    mapping?: Record<string, string>;
    values?: OverlayValue[];
    entries?: OverlayFeedEntry[];
    weather?: {
        temperature?: string | number | null;
        humidity?: string | number | null;
        weather_code?: string | number | null;
        wind_speed?: string | number | null;
        location?: string | null;
        updated_at?: string | null;
    };
    api_connection_id?: string | null;
    endpoint?: string;
    refresh_seconds?: number;
};

export type OverlayEntry = {
    id?: string;
    type: string;
    config: OverlayConfig;
    sort_order?: number;
    is_active?: boolean;
};

export type ApiConnectionOption = {
    id: string;
    name: string;
    provider: string;
};

export type MediaAssetOption = {
    id: string;
    name: string;
    type: string;
    url: string;
};

export const MAX_OVERLAYS = 8;

export const OVERLAY_TYPES: OverlayType[] = [
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

export const OVERLAY_LABELS: Record<string, string> = {
    ticker: 'Ticker',
    clock: 'Clock',
    weather: 'Weather',
    date: 'Date',
    location: 'Location',
    greeting: 'Greeting',
    next: 'Next up',
    badge: 'Badge',
    countdown: 'Countdown',
    hours: 'Open status',
    title: 'Title card',
    progress: 'Progress',
    api: 'API data',
    rss: 'RSS feed',
};

export const OVERLAY_POSITIONS: Array<{
    value: OverlayPosition;
    label: string;
}> = [
    { value: 'top-left', label: 'Top left' },
    { value: 'top-right', label: 'Top right' },
    { value: 'bottom-left', label: 'Bottom left' },
    { value: 'bottom-right', label: 'Bottom right' },
    { value: 'top', label: 'Top' },
    { value: 'bottom', label: 'Bottom' },
];

export const OVERLAY_THEMES: Array<{ value: OverlayTheme; label: string }> = [
    { value: 'dark', label: 'Dark' },
    { value: 'light', label: 'Light' },
    { value: 'blur', label: 'Blur glass' },
];

const DEFAULT_WEATHER_ENDPOINT =
    '/v1/forecast?latitude=-6.2&longitude=106.8&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Asia%2FJakarta';

export const API_OVERLAY_PRESETS: Array<{
    key: string;
    label: string;
    endpoint: string;
    fields: OverlayField[];
}> = [
    {
        key: 'air-quality',
        label: 'Kualitas udara (Open-Meteo)',
        endpoint:
            '/v1/air-quality?latitude=-6.2&longitude=106.8&current=pm2_5,pm10,european_aqi&timezone=Asia%2FJakarta',
        fields: [
            { label: 'AQI', path: 'current.european_aqi' },
            { label: 'PM2.5', path: 'current.pm2_5', suffix: ' µg/m³' },
        ],
    },
    {
        key: 'prayer',
        label: 'Jadwal sholat (Aladhan)',
        endpoint: '/v1/timings?latitude=-6.2&longitude=106.8&method=20',
        fields: [
            { label: 'Subuh', path: 'timings.Fajr' },
            { label: 'Dzuhur', path: 'timings.Dhuhr' },
            { label: 'Ashar', path: 'timings.Asr' },
            { label: 'Maghrib', path: 'timings.Maghrib' },
            { label: 'Isya', path: 'timings.Isha' },
        ],
    },
];

export function overlayTypeLabel(type: string): string {
    return OVERLAY_LABELS[type] ?? type;
}

export function createOverlay(
    type: OverlayType,
    apiConnections: ApiConnectionOption[] = [],
    mediaAssets: MediaAssetOption[] = [],
): OverlayEntry {
    const connection = apiConnections[0]?.id ?? null;
    const firstImage = mediaAssets.find(
        (asset) => asset.type === 'image' || asset.type === 'image_url',
    );

    const base = {
        is_active: true,
        config: {
            position: defaultPosition(type),
            offset_x: 0,
            offset_y: 0,
            theme: 'dark' as OverlayTheme,
            text_color: '#ffffff',
            background_color: '#111827',
            schedule: {},
        } as OverlayConfig,
    };

    const config: OverlayConfig = matchConfig(type, {
        connection,
        firstImageId: firstImage?.id ?? null,
    });

    return {
        type,
        ...base,
        config: { ...base.config, ...config },
    };
}

function defaultPosition(type: OverlayType): OverlayPosition {
    switch (type) {
        case 'ticker':
        case 'progress':
            return 'bottom';
        case 'title':
        case 'next':
            return 'bottom-left';
        case 'hours':
            return 'top-left';
        default:
            return 'top-right';
    }
}

function matchConfig(
    type: OverlayType,
    context: { connection: string | null; firstImageId: string | null },
): OverlayConfig {
    switch (type) {
        case 'ticker':
            return {
                type,
                text: 'Informasi terbaru untuk pengunjung',
                source: 'static',
                items: [],
                mode: 'loop',
                item_duration_seconds: 8,
                speed: 60,
            };
        case 'clock':
            return { type, format: '24h' };
        case 'weather':
            return {
                type,
                api_connection_id: context.connection,
                endpoint: DEFAULT_WEATHER_ENDPOINT,
                refresh_seconds: 900,
            };
        case 'date':
            return { type, format: 'long', locale: 'id' };
        case 'location':
            return { type, label: '', separator: '·' };
        case 'greeting':
            return { type, template: 'Selamat {greeting}', name: 'tenant' };
        case 'next':
            return { type, next_mode: 'next', label: 'Berikutnya' };
        case 'badge':
            return { type, media_asset_id: context.firstImageId, size: 96 };
        case 'countdown':
            return {
                type,
                target_at: '',
                prefix: 'Menuju acara',
                suffix: '',
                finished_text: 'Acara dimulai',
            };
        case 'hours':
            return {
                type,
                title: '',
                open_label: 'BUKA',
                closed_label: 'TUTUP',
                show_times: true,
            };
        case 'title':
            return {
                type,
                title: 'Judul acara',
                subtitle: 'Subjudul atau keterangan singkat',
                align: 'left',
            };
        case 'progress':
            return {
                type,
                color: '#22d3ee',
                track_color: '#111827',
                height: 6,
            };
        case 'api':
            return {
                type,
                api_connection_id: context.connection,
                endpoint: '',
                refresh_seconds: 300,
                title: '',
                layout: 'list',
                fields: [{ label: 'Nilai', path: 'value' }],
            };
        case 'rss':
            return {
                type,
                api_connection_id: context.connection,
                endpoint: '',
                refresh_seconds: 900,
                title: '',
                layout: 'ticker',
                item_limit: 8,
                item_duration_seconds: 8,
            };
        default:
            return { type };
    }
}

export function overlayThemeClasses(theme?: string): {
    panel: string;
    muted: string;
    accent: string;
} {
    switch (theme) {
        case 'light':
            return {
                panel: 'border-slate-900/10 bg-white/90 text-slate-900',
                muted: 'text-slate-600',
                accent: 'text-cyan-700',
            };
        case 'blur':
            return {
                panel: 'border-white/10 bg-black/35 text-white backdrop-blur-md',
                muted: 'text-white/60',
                accent: 'text-cyan-200',
            };
        default:
            return {
                panel: 'border-white/15 bg-slate-950/85 text-white',
                muted: 'text-white/60',
                accent: 'text-cyan-300',
            };
    }
}

export function overlayPositionClass(position?: string): string {
    return (
        {
            'top-left': 'top-4 left-4',
            'top-right': 'top-4 right-4',
            'bottom-left': 'bottom-4 left-4',
            'bottom-right': 'bottom-4 right-4',
            top: 'top-4 left-4',
            bottom: 'bottom-4 left-4',
        }[position ?? 'top-right'] ?? 'top-4 right-4'
    );
}

export function overlayBarPositionClass(position?: string): string {
    return position?.startsWith('top') ? 'top-0' : 'bottom-0';
}

export function overlayOffsetStyle(
    config: OverlayConfig,
): Record<string, string> {
    const offsetX = Number(config.offset_x ?? 0);
    const offsetY = Number(config.offset_y ?? 0);

    if (offsetX === 0 && offsetY === 0) {
        return {};
    }

    return { transform: `translate(${offsetX}px, ${offsetY}px)` };
}

export function overlayTickerText(config: OverlayConfig): string {
    return config.source === 'array' && config.items?.length
        ? config.items.join('   •   ')
        : (config.text ?? '');
}

export function overlayTickerStyle(
    config: OverlayConfig,
): Record<string, string> {
    return {
        color: config.text_color ?? '#ffffff',
        backgroundColor: config.background_color ?? '#111827',
    };
}

export function overlayTickerAnimationStyle(
    config: OverlayConfig,
): Record<string, string> {
    const speed = Math.min(Math.max(Number(config.speed ?? 60), 10), 200);

    return {
        animationDuration: `${Math.max(8, 40 - speed * 0.16)}s`,
    };
}

export function overlayClockText(now: Date, format?: string): string {
    return now.toLocaleTimeString([], {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: format === '12h',
    });
}

export function overlayDateText(
    now: Date,
    config: Pick<OverlayConfig, 'format' | 'locale'>,
): string {
    const locale = config.locale === 'en' ? 'en-US' : 'id-ID';

    if (config.format === 'iso') {
        return now.toISOString().slice(0, 10);
    }

    if (config.format === 'short') {
        return now.toLocaleDateString(locale, {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }

    return now.toLocaleDateString(locale, {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    });
}

export function greetingWord(now: Date, locale?: string): string {
    const hour = now.getHours();

    if (locale === 'en') {
        if (hour < 12) {
            return 'morning';
        }

        return hour < 18 ? 'afternoon' : 'evening';
    }

    if (hour < 11) {
        return 'pagi';
    }

    if (hour < 15) {
        return 'siang';
    }

    return hour < 19 ? 'sore' : 'malam';
}

export function overlayGreetingText(
    config: OverlayConfig,
    now: Date,
    values: { tenant: string; screen: string },
): string {
    return (config.template ?? 'Selamat {greeting}')
        .replace(/\{greeting\}/g, greetingWord(now, config.locale))
        .replace(/\{tenant\}/g, values.tenant)
        .replace(/\{screen\}/g, values.screen)
        .replace(/\{date\}/g, overlayDateText(now, { locale: config.locale }))
        .replace(/\{time\}/g, overlayClockText(now));
}

export function overlayCountdown(
    config: OverlayConfig,
    now: Date,
): { text: string; finished: boolean } {
    if (!config.target_at) {
        return { text: config.finished_text ?? '', finished: true };
    }

    const target = new Date(config.target_at);

    if (Number.isNaN(target.getTime())) {
        return { text: config.finished_text ?? '', finished: true };
    }

    const diff = target.getTime() - now.getTime();

    if (diff <= 0) {
        return { text: config.finished_text ?? '', finished: true };
    }

    const totalSeconds = Math.floor(diff / 1000);
    const days = Math.floor(totalSeconds / 86_400);
    const hours = Math.floor((totalSeconds % 86_400) / 3_600);
    const minutes = Math.floor((totalSeconds % 3_600) / 60);
    const seconds = totalSeconds % 60;
    const pad = (value: number): string => String(value).padStart(2, '0');
    const clock = `${pad(hours)}:${pad(minutes)}:${pad(seconds)}`;

    return {
        text: days > 0 ? `${days} hari ${clock}` : clock,
        finished: false,
    };
}

function toMinutes(value?: string | null): number | null {
    if (!value) {
        return null;
    }

    const [hours, minutes] = value.split(':').map((part) => Number(part));

    if (Number.isNaN(hours) || Number.isNaN(minutes)) {
        return null;
    }

    return hours * 60 + minutes;
}

function isWithinWindow(
    start: number | null,
    end: number | null,
    now: Date,
): boolean {
    if (start === null && end === null) {
        return true;
    }

    const minutes = now.getHours() * 60 + now.getMinutes();

    if (start !== null && end !== null) {
        return start <= end
            ? minutes >= start && minutes <= end
            : minutes >= start || minutes <= end;
    }

    if (start !== null) {
        return minutes >= start;
    }

    return end !== null && minutes <= end;
}

function isoWeekday(now: Date): number {
    return now.getDay() === 0 ? 7 : now.getDay();
}

export function overlayScheduleMatches(
    schedule: OverlaySchedule | undefined,
    now: Date,
): boolean {
    if (!schedule) {
        return true;
    }

    const days = schedule.days ?? [];

    if (days.length > 0 && !days.includes(isoWeekday(now))) {
        return false;
    }

    return isWithinWindow(
        toMinutes(schedule.start),
        toMinutes(schedule.end),
        now,
    );
}

export function scheduleIsActive(
    schedule:
        | {
              start_time?: string | null;
              end_time?: string | null;
              days_of_week?: number[] | null;
          }
        | null
        | undefined,
    now: Date,
): boolean {
    if (!schedule) {
        return true;
    }

    const days = schedule.days_of_week ?? [];

    if (days.length > 0 && !days.includes(isoWeekday(now))) {
        return false;
    }

    return isWithinWindow(
        toMinutes(schedule.start_time),
        toMinutes(schedule.end_time),
        now,
    );
}

export function overlayWeatherCondition(code: unknown): string {
    const value = Number(code);

    if (value === 0) {
        return 'Cerah';
    }

    if (value <= 3) {
        return 'Berawan';
    }

    if (value <= 67 || (value >= 80 && value <= 82)) {
        return 'Hujan';
    }

    if (value >= 95) {
        return 'Badai petir';
    }

    return 'Kondisi tidak diketahui';
}

export function overlayApiTickerText(config: OverlayConfig): string {
    const values = config.values ?? [];

    return values
        .map((value) =>
            `${value.label ?? ''} ${value.prefix ?? ''}${value.value ?? '-'}${value.suffix ?? ''}`.trim(),
        )
        .join('   •   ');
}

export function overlayRssText(config: OverlayConfig, now: Date): string {
    const entries = config.entries ?? [];

    if (entries.length === 0) {
        return '';
    }

    const duration = Math.max(2, Number(config.item_duration_seconds ?? 8));
    const index = Math.floor(now.getTime() / 1000 / duration) % entries.length;

    return entries[index]?.title ?? '';
}

export function overlayProgressPercent(
    elapsedSeconds: number,
    durationSeconds: number,
): number {
    if (durationSeconds <= 0) {
        return 0;
    }

    return Math.min(100, Math.max(0, (elapsedSeconds / durationSeconds) * 100));
}
