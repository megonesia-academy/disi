<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import { isImageMedia, isVideoMedia, type MediaAssetType } from '@/lib/media';
import {
    computeMediaBox,
    initSignageCore,
    isSignageCoreWasmReady,
    type MediaFit,
} from '@/wasm/signageCore';

type Media = {
    id: string;
    name: string;
    type: MediaAssetType;
    url: string | null;
    mime_type: string | null;
    duration_seconds: number | null;
    width: number | null;
    height: number | null;
    metadata: Record<string, unknown> | null;
};

type TickerOverlay = {
    type: 'ticker';
    text: string;
    position?:
        | 'top'
        | 'bottom'
        | 'top-left'
        | 'top-right'
        | 'bottom-left'
        | 'bottom-right';
    speed?: number;
    text_color?: string;
    background_color?: string;
    source?: 'static' | 'array';
    items?: string[];
    format?: '24h' | '12h';
    weather?: {
        temperature?: unknown;
        humidity?: unknown;
        weather_code?: unknown;
        wind_speed?: unknown;
        location?: unknown;
        updated_at?: unknown;
    };
};

type PlaylistOverlay = {
    id: string;
    type: 'ticker' | 'clock' | 'weather';
    config: TickerOverlay;
    sort_order: number;
};

type PlaylistItem = {
    id: string;
    sort_order: number;
    duration_seconds: number;
    transition: string | null;
    template?: string;
    content?: Record<string, unknown>;
    template_id?: string | null;
    template_definition?: {
        name: string;
        html: string;
        css: string | null;
        schema: Array<Record<string, unknown>>;
    } | null;
    config: {
        fit?: MediaFit;
        overlays?: TickerOverlay[];
    } | null;
    split_screen?: {
        left: Media | null;
        right: Media | null;
        right_source: 'media' | 'youtube' | 'url';
        right_url: string | null;
        left_width: number;
        right_width: number;
    } | null;
    audio?: {
        id: string;
        name: string;
        url: string;
        mime_type: string | null;
        loop: boolean;
    } | null;
    media: Media | null;
};

type Manifest = {
    generated_at: string;
    branding: {
        label: string;
        brand_color: string;
        logo_url: string | null;
        logo_width: number;
        logo_position: string;
        logo_offset_x: number;
        logo_offset_y: number;
        behavior: {
            default_media_fit: 'cover' | 'contain' | 'fill';
            manifest_refresh_seconds: number;
            heartbeat_interval_seconds: number;
            show_player_overlay: boolean;
        };
    };
    device: {
        id: string;
        name: string;
        key: string;
        status: string;
        capabilities: Record<string, unknown> | null;
    };
    screen: {
        id: string;
        name: string;
        location: string | null;
        orientation: string;
        resolution: {
            width: number;
            height: number;
        };
    };
    playlist: {
        id: string;
        name: string;
        overlays?: PlaylistOverlay[];
        items: PlaylistItem[];
    };
};

type PlayerCommand = {
    id: string;
    type: 'reload' | 'refresh_manifest' | 'identify' | 'clear_cache';
    payload: Record<string, unknown>;
    created_at: string | null;
    ack_url: string;
};

type MediaReadiness = {
    total: number;
    ready: number;
    failed: number;
    pending: number;
    unsupported: number;
    status: 'unknown' | 'ready' | 'warming' | 'degraded' | 'empty';
    updated_at: string | null;
};

const props = defineProps<{
    deviceKey: string;
    manifestUrl: string;
    heartbeatUrl: string;
}>();

const manifest = ref<Manifest | null>(null);
const activeIndex = ref(0);
const isLoading = ref(true);
const errorMessage = ref<string | null>(null);
const mediaErrorMessage = ref<string | null>(null);
const identifyMessage = ref<string | null>(null);
const wasmRevision = ref(0);
const wasmCoreReady = ref(false);
const manifestSource = ref<'live' | 'cache' | 'none'>('none');
const manifestCachedAt = ref<string | null>(null);
const transitionName = ref('none');
const networkOnline = ref(
    typeof navigator === 'undefined' ? true : navigator.onLine,
);
const mediaReadiness = ref<MediaReadiness>({
    total: 0,
    ready: 0,
    failed: 0,
    pending: 0,
    unsupported: 0,
    status: 'unknown',
    updated_at: null,
});
const serviceWorkerReady = ref(false);
const currentTime = ref(new Date());

let itemTimer: number | undefined;
let refreshTimer: number | undefined;
let heartbeatTimer: number | undefined;
let identifyTimer: number | undefined;
let preloadRevision = 0;
let clockTimer: number | undefined;

const activeItem = computed(() => {
    return manifest.value?.playlist.items[activeIndex.value] ?? null;
});

const hasItems = computed(() => {
    return (manifest.value?.playlist.items.length ?? 0) > 0;
});

const screenStyle = computed(() => {
    const screen = manifest.value?.screen;

    if (!screen) {
        return {};
    }

    return {
        aspectRatio: `${screen.resolution.width} / ${screen.resolution.height}`,
    };
});

const brandColor = computed(() => {
    return manifest.value?.branding.brand_color ?? '#0891b2';
});

const brandLabel = computed(() => {
    return (
        manifest.value?.branding.label ??
        manifest.value?.screen.name ??
        props.deviceKey
    );
});

const mediaFitClass = computed(() => {
    const fit = activeItem.value?.config?.fit ?? 'cover';

    if (fit === 'contain') {
        return 'object-contain';
    }

    if (fit === 'fill') {
        return 'object-fill';
    }

    return 'object-cover';
});

const tickerOverlays = computed(() => {
    const globalOverlays = (manifest.value?.playlist.overlays ?? [])
        .filter((overlay) => overlay.type === 'ticker')
        .map((overlay) => overlay.config);
    const itemOverlays = (activeItem.value?.config?.overlays ?? []).filter(
        (overlay) => overlay.type === 'ticker',
    );

    return [...globalOverlays, ...itemOverlays];
});

const clockOverlays = computed(() => {
    return (manifest.value?.playlist.overlays ?? []).filter(
        (overlay) => overlay.type === 'clock',
    );
});

const weatherOverlays = computed(() => {
    return (manifest.value?.playlist.overlays ?? []).filter(
        (overlay) => overlay.type === 'weather',
    );
});

function weatherOverlayData(overlay: PlaylistOverlay): {
    temperature: string;
    humidity: string;
    condition: string;
    wind: string | null;
    location: string;
} {
    const weather = overlay.config.weather ?? {};
    const wind = weather.wind_speed;

    return {
        temperature:
            weather.temperature === null || weather.temperature === undefined
                ? '-'
                : `${weather.temperature} °C`,
        humidity:
            weather.humidity === null || weather.humidity === undefined
                ? '-'
                : `${weather.humidity}%`,
        condition: weatherCondition(weather.weather_code),
        wind:
            wind === null || wind === undefined || wind === ''
                ? null
                : `${wind} km/h`,
        location: String(weather.location ?? 'Weather'),
    };
}

function weatherOverlayPositionClass(position?: string): string {
    return (
        {
            'top-left': 'top-4 left-4',
            'top-right': 'top-4 right-4',
            'bottom-left': 'bottom-4 left-4',
            'bottom-right': 'bottom-4 right-4',
        }[position ?? 'top-right'] ?? 'top-4 right-4'
    );
}

function tickerText(overlay: TickerOverlay): string {
    return overlay.source === 'array' && overlay.items?.length
        ? overlay.items.join('   •   ')
        : (overlay.text ?? '');
}

function tickerStyle(overlay: TickerOverlay): Record<string, string> {
    return {
        color: overlay.text_color ?? '#ffffff',
        backgroundColor: overlay.background_color ?? '#111827',
    };
}

function tickerAnimationStyle(overlay: TickerOverlay): Record<string, string> {
    const speed = Math.min(Math.max(Number(overlay.speed ?? 60), 10), 200);

    return {
        animationDuration: `${Math.max(8, 40 - speed * 0.16)}s`,
    };
}

function clockPositionClass(position?: string): string {
    return (
        {
            'top-left': 'top-4 left-4',
            'top-right': 'top-4 right-4',
            'bottom-left': 'bottom-4 left-4',
            'bottom-right': 'bottom-4 right-4',
        }[position ?? 'top-right'] ?? 'top-4 right-4'
    );
}

function overlayOffsetStyle(overlay: TickerOverlay): Record<string, string> {
    const position = overlay.position ?? 'top-right';
    const offsetX = Number(overlay.offset_x ?? 0);
    const offsetY = Number(overlay.offset_y ?? 0);
    const style: Record<string, string> = {};

    if (position.includes('left')) style.left = `calc(1rem + ${offsetX}px)`;
    if (position.includes('right')) style.right = `calc(1rem - ${offsetX}px)`;
    if (position.includes('top')) style.top = `calc(1rem + ${offsetY}px)`;
    if (position.includes('bottom')) style.bottom = `calc(1rem - ${offsetY}px)`;

    return style;
}

const mediaBoxStyle = computed(() => {
    wasmRevision.value;

    const screen = manifest.value?.screen;
    const item = activeItem.value;

    if (!screen || !item || !item.media) {
        return {};
    }

    const box = computeMediaBox(
        screen.resolution.width,
        screen.resolution.height,
        item.media.width,
        item.media.height,
        (item.config?.fit ?? 'cover') as MediaFit,
    );

    return {
        left: `${(box.x / screen.resolution.width) * 100}%`,
        top: `${(box.y / screen.resolution.height) * 100}%`,
        width: `${(box.width / screen.resolution.width) * 100}%`,
        height: `${(box.height / screen.resolution.height) * 100}%`,
    };
});

const shouldShowOverlay = computed(() => {
    return manifest.value?.branding.behavior.show_player_overlay ?? true;
});

const logoPositionClass = computed(() => {
    const positions: Record<string, string> = {
        'top-left': 'top-4 left-4',
        'top-center': 'top-4 left-1/2 -translate-x-1/2',
        'top-right': 'top-4 right-4',
        'center-left': 'top-1/2 left-4 -translate-y-1/2',
        center: 'top-1/2 left-1/2 -translate-x-1/2 -translate-y-1/2',
        'center-right': 'top-1/2 right-4 -translate-y-1/2',
        'bottom-left': 'bottom-4 left-4',
        'bottom-center': 'bottom-4 left-1/2 -translate-x-1/2',
        'bottom-right': 'right-4 bottom-4',
    };

    return (
        positions[manifest.value?.branding.logo_position ?? 'top-right'] ??
        positions['top-right']
    );
});

const logoStyle = computed(() => {
    const position = manifest.value?.branding.logo_position ?? 'top-right';
    const offsetX = Number(manifest.value?.branding.logo_offset_x ?? 0);
    const offsetY = Number(manifest.value?.branding.logo_offset_y ?? 0);
    const style: Record<string, string> = {
        width: `${manifest.value?.branding.logo_width ?? 120}px`,
        maxWidth: 'calc(100% - 2rem)',
        maxHeight: 'calc(100% - 2rem)',
    };

    if (position.includes('left')) style.left = `calc(1rem + ${offsetX}px)`;
    if (position.includes('right')) style.right = `calc(1rem - ${offsetX}px)`;
    if (position.includes('top')) style.top = `calc(1rem + ${offsetY}px)`;
    if (position.includes('bottom')) style.bottom = `calc(1rem - ${offsetY}px)`;
    if (position.includes('center') || position === 'center') {
        if (!position.includes('left') && !position.includes('right'))
            style.marginLeft = `${offsetX}px`;
        if (!position.includes('top') && !position.includes('bottom'))
            style.marginTop = `${offsetY}px`;
    }

    return style;
});

const cacheKey = computed(() => {
    return `disi:player:${props.deviceKey}:manifest`;
});

const syncOnlineState = (): void => {
    networkOnline.value = navigator.onLine;
};

async function loadManifest(): Promise<void> {
    try {
        errorMessage.value = null;

        const response = await fetch(props.manifestUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Manifest request failed: ${response.status}`);
        }

        applyManifest((await response.json()) as Manifest, 'live');
        persistManifest();
    } catch (error) {
        if (restoreCachedManifest()) {
            errorMessage.value = null;
            return;
        }

        errorMessage.value =
            error instanceof Error ? error.message : 'Manifest tidak tersedia.';
    } finally {
        isLoading.value = false;
    }
}

function applyManifest(nextManifest: Manifest, source: 'live' | 'cache'): void {
    manifest.value = nextManifest;
    manifestSource.value = source;

    if (activeIndex.value >= nextManifest.playlist.items.length) {
        activeIndex.value = 0;
    }

    configurePlayerTimers();
    scheduleNextItem();
    void preloadManifestMedia(nextManifest);

    if (serviceWorkerReady.value) {
        void navigator.serviceWorker.ready.then((registration) => {
            cacheManifestMedia(nextManifest, registration.active);
        });
    }
}

function persistManifest(): void {
    if (!manifest.value) {
        return;
    }

    const cachedAt = new Date().toISOString();

    try {
        localStorage.setItem(
            cacheKey.value,
            JSON.stringify({
                cached_at: cachedAt,
                manifest: manifest.value,
            }),
        );
        manifestCachedAt.value = cachedAt;
    } catch {
        // Storage can be unavailable on locked-down TV browsers.
    }
}

function restoreCachedManifest(): boolean {
    try {
        const cached = localStorage.getItem(cacheKey.value);

        if (!cached) {
            return false;
        }

        const payload = JSON.parse(cached) as {
            cached_at?: unknown;
            manifest?: unknown;
        };

        if (!payload.manifest || typeof payload.manifest !== 'object') {
            return false;
        }

        manifestCachedAt.value =
            typeof payload.cached_at === 'string' ? payload.cached_at : null;
        applyManifest(payload.manifest as Manifest, 'cache');

        return true;
    } catch {
        return false;
    }
}

function configurePlayerTimers(): void {
    if (refreshTimer) {
        window.clearInterval(refreshTimer);
    }

    if (heartbeatTimer) {
        window.clearInterval(heartbeatTimer);
    }

    const refreshSeconds =
        manifest.value?.branding.behavior.manifest_refresh_seconds ?? 60;
    const heartbeatSeconds =
        manifest.value?.branding.behavior.heartbeat_interval_seconds ?? 30;

    refreshTimer = window.setInterval(
        () => {
            void loadManifest();
        },
        Math.max(refreshSeconds, 10) * 1000,
    );

    heartbeatTimer = window.setInterval(
        () => {
            void sendHeartbeat();
        },
        Math.max(heartbeatSeconds, 5) * 1000,
    );
}

async function preloadManifestMedia(nextManifest: Manifest): Promise<void> {
    const currentRevision = ++preloadRevision;
    const mediaItems = uniquePreloadableMedia(nextManifest);

    mediaReadiness.value = {
        total: mediaItems.length,
        ready: 0,
        failed: 0,
        pending: mediaItems.length,
        unsupported: nextManifest.playlist.items.length - mediaItems.length,
        status: mediaItems.length === 0 ? 'empty' : 'warming',
        updated_at: new Date().toISOString(),
    };

    if (mediaItems.length === 0) {
        return;
    }

    await Promise.all(
        mediaItems.map(async (media) => {
            const isReady = await preloadMedia(media);

            if (currentRevision !== preloadRevision) {
                return;
            }

            mediaReadiness.value = summarizeMediaReadiness(
                mediaReadiness.value,
                isReady,
            );
        }),
    );
}

function uniquePreloadableMedia(nextManifest: Manifest): Media[] {
    const mediaByUrl = new Map<string, Media>();

    for (const item of nextManifest.playlist.items) {
        if (
            !item.media ||
            !item.media.url ||
            !(isImageMedia(item.media.type) || isVideoMedia(item.media.type))
        ) {
            continue;
        }

        mediaByUrl.set(item.media.url, item.media);
    }

    return Array.from(mediaByUrl.values());
}

function preloadMedia(media: Media): Promise<boolean> {
    if (isImageMedia(media.type)) {
        return preloadImage(media.url);
    }

    if (isVideoMedia(media.type)) {
        return preloadVideo(media.url);
    }

    return Promise.resolve(false);
}

function preloadImage(url: string): Promise<boolean> {
    return new Promise((resolve) => {
        const image = new Image();

        image.onload = () => resolve(true);
        image.onerror = () => resolve(false);
        image.src = url;
    });
}

function preloadVideo(url: string): Promise<boolean> {
    return new Promise((resolve) => {
        const video = document.createElement('video');
        const cleanup = (): void => {
            video.removeAttribute('src');
            video.load();
        };

        video.preload = 'metadata';
        video.muted = true;
        video.playsInline = true;
        video.onloadedmetadata = () => {
            cleanup();
            resolve(true);
        };
        video.onerror = () => {
            cleanup();
            resolve(false);
        };
        video.src = url;
        video.load();
    });
}

function summarizeMediaReadiness(
    current: MediaReadiness,
    isReady: boolean,
): MediaReadiness {
    const ready = current.ready + (isReady ? 1 : 0);
    const failed = current.failed + (isReady ? 0 : 1);
    const pending = Math.max(current.total - ready - failed, 0);

    return {
        ...current,
        ready,
        failed,
        pending,
        status: failed > 0 ? 'degraded' : pending > 0 ? 'warming' : 'ready',
        updated_at: new Date().toISOString(),
    };
}

function cacheManifestMedia(
    nextManifest: Manifest,
    worker: ServiceWorker | null,
): void {
    if (!worker) {
        return;
    }

    worker.postMessage({
        type: 'CACHE_MEDIA',
        urls: nextManifest.playlist.items
            .filter(
                (item) =>
                    item.media &&
                    item.media.url &&
                    (isImageMedia(item.media.type) ||
                        isVideoMedia(item.media.type)),
            )
            .map((item) => item.media!.url!),
    });
}

async function registerPlayerServiceWorker(): Promise<void> {
    if (!('serviceWorker' in navigator)) {
        return;
    }

    try {
        const registration = await navigator.serviceWorker.register(
            '/player-sw.js',
            {
                scope: '/player/',
            },
        );
        await navigator.serviceWorker.ready;
        serviceWorkerReady.value = true;
        if (manifest.value) {
            cacheManifestMedia(manifest.value, registration.active);
        }
    } catch {
        serviceWorkerReady.value = false;
    }
}

async function clearPlayerCache(): Promise<void> {
    if ('serviceWorker' in navigator) {
        const registration = await navigator.serviceWorker.ready;
        registration.active?.postMessage({ type: 'CLEAR_CACHE' });
    }

    try {
        localStorage.removeItem(cacheKey.value);
    } catch {
        // Storage can be unavailable on locked-down TV browsers.
    }
}

async function sendHeartbeat(): Promise<void> {
    try {
        const response = await fetch(props.heartbeatUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                app_version: 'web-player-0.1.0',
                capabilities: {
                    web: true,
                    video: true,
                    wasm: typeof WebAssembly !== 'undefined',
                    rust_wasm: wasmCoreReady.value,
                    layout_engine: wasmCoreReady.value
                        ? 'rust-wasm'
                        : 'js-fallback',
                    manifest_source: manifestSource.value,
                    manifest_cached: manifestSource.value === 'cache',
                    manifest_cached_at: manifestCachedAt.value,
                    network_online: networkOnline.value,
                    service_worker_cache: serviceWorkerReady.value,
                    media_readiness: mediaReadiness.value,
                    user_agent: navigator.userAgent,
                    viewport: {
                        width: window.innerWidth,
                        height: window.innerHeight,
                        device_pixel_ratio: window.devicePixelRatio,
                    },
                },
            }),
        });

        if (!response.ok) {
            return;
        }

        const payload = (await response.json()) as {
            commands?: PlayerCommand[];
        };

        await handleCommands(payload.commands ?? []);
    } catch {
        // Heartbeat failures should not interrupt playback.
    }
}

async function handleCommands(commands: PlayerCommand[]): Promise<void> {
    for (const command of commands) {
        if (command.type === 'reload') {
            void acknowledgeCommand(command);
            window.location.reload();
            return;
        }

        if (command.type === 'refresh_manifest') {
            await loadManifest();
            void acknowledgeCommand(command);
        }

        if (command.type === 'identify') {
            const duration =
                typeof command.payload.duration_seconds === 'number'
                    ? command.payload.duration_seconds
                    : 10;

            identifyMessage.value = `${brandLabel.value} · ${props.deviceKey}`;

            if (identifyTimer) {
                window.clearTimeout(identifyTimer);
            }

            identifyTimer = window.setTimeout(
                () => {
                    identifyMessage.value = null;
                },
                Math.max(duration, 1) * 1000,
            );

            void acknowledgeCommand(command);
        }

        if (command.type === 'clear_cache') {
            await clearPlayerCache();
            void acknowledgeCommand(command);
        }
    }
}

async function acknowledgeCommand(command: PlayerCommand): Promise<void> {
    try {
        await fetch(command.ack_url, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
            },
        });
    } catch {
        // A missed acknowledgement should not interrupt playback.
    }
}

function scheduleNextItem(): void {
    if (itemTimer) {
        window.clearTimeout(itemTimer);
    }

    if (!hasItems.value || !activeItem.value) {
        return;
    }

    mediaErrorMessage.value = null;

    const duration =
        Math.max(
            activeItem.value.duration_seconds ??
                activeItem.value.media?.duration_seconds ??
                10,
            1,
        ) * 1000;

    itemTimer = window.setTimeout(() => {
        showNextItem();
    }, duration);
}

function normalizeTransition(item: PlaylistItem | null): string {
    const transition = item?.transition?.trim().toLowerCase();

    return [
        'fade',
        'slide-left',
        'slide-right',
        'slide-up',
        'slide-down',
        'zoom',
    ].includes(transition ?? '')
        ? transition!
        : 'none';
}

function onVideoEnded(): void {
    showNextItem();
}

function onMediaError(): void {
    if (!activeItem.value) {
        return;
    }

    mediaErrorMessage.value = `${activeItem.value.media?.name ?? 'Konten'} tidak bisa dimuat.`;

    if (itemTimer) {
        window.clearTimeout(itemTimer);
    }

    itemTimer = window.setTimeout(showNextItem, 2_000);
}

function templateTitle(item: PlaylistItem): string {
    const content = item.content ?? {};

    return String(
        content.title ??
            {
                announcement: 'Announcement',
                'data-table': 'Data table',
                'patient-queue': 'Patient queue',
                'flight-board': 'Flight board',
                menu: 'Menu',
                weather: 'Weather',
            }[item.template ?? ''] ??
            'Content',
    );
}

function templateRows(item: PlaylistItem): string[] {
    const body = item.content?.body;

    if (Array.isArray(body)) {
        return body.map((value) => String(value));
    }

    return String(body ?? '')
        .split('\n')
        .map((row) => row.trim())
        .filter(Boolean);
}

function weatherCondition(code: unknown): string {
    const value = Number(code);

    if (value === 0) return 'Cerah';
    if (value <= 3) return 'Berawan';
    if (value <= 57) return 'Gerimis';
    if (value <= 67) return 'Hujan';
    if (value <= 77) return 'Salju';
    if (value <= 82) return 'Hujan deras';
    if (value <= 99) return 'Badai petir';

    return 'Kondisi tidak diketahui';
}

function weatherBackgroundClass(item: PlaylistItem): string {
    const value = Number(item.content?.weather_code);

    if (value === 0) return 'weather-clear';
    if (value <= 3) return 'weather-cloudy';
    if (value <= 67 || (value >= 80 && value <= 82)) return 'weather-rain';
    if (value >= 95) return 'weather-storm';

    return 'weather-cloudy';
}

function weatherData(item: PlaylistItem): {
    temperature: string;
    humidity: string;
    condition: string;
    wind: string | null;
    location: string;
    updatedAt: string;
} {
    const content = item.content ?? {};
    const windSpeed = content.wind_speed;

    return {
        temperature: `${content.temperature ?? content.title ?? '-'} °C`,
        humidity: `${content.humidity ?? content.body ?? '-'}%`,
        condition: weatherCondition(content.weather_code),
        wind:
            windSpeed === undefined || windSpeed === null || windSpeed === ''
                ? null
                : `${windSpeed} km/h`,
        location: String(
            content.location ?? content.subtitle ?? 'Weather update',
        ),
        updatedAt: String(content.updated_at ?? ''),
    };
}

function youtubeEmbedUrl(url: string | null): string {
    if (!url) return '';
    const match = url.match(
        /(?:youtu\.be\/|youtube\.com\/(?:watch\?v=|embed\/))([^?&/]+)/i,
    );

    return match
        ? `https://www.youtube.com/embed/${match[1]}?autoplay=1&mute=1&controls=0&loop=1&playlist=${match[1]}`
        : url;
}

function isVideoUrl(url: string | null): boolean {
    return Boolean(url && /\.(mp4|webm|m3u8)(?:[?#].*)?$/i.test(url));
}

function objectRows(item: PlaylistItem): Array<Record<string, unknown>> {
    const rows = item.content?.rows;

    return Array.isArray(rows)
        ? rows.filter((row): row is Record<string, unknown> =>
              Boolean(row && typeof row === 'object' && !Array.isArray(row)),
          )
        : [];
}

function tableColumns(item: PlaylistItem): string[] {
    const columns = item.content?.columns;

    if (Array.isArray(columns)) return columns.map((column) => String(column));

    const firstRow = objectRows(item)[0];

    return firstRow ? Object.keys(firstRow) : [];
}

function tableValues(item: PlaylistItem): Array<Array<string>> {
    const rows = item.content?.rows;

    if (!Array.isArray(rows)) return [];

    const columns = tableColumns(item);

    return rows.map((row) =>
        Array.isArray(row)
            ? row.map((value) => String(value ?? ''))
            : columns.map((column) =>
                  String((row as Record<string, unknown>)[column] ?? ''),
              ),
    );
}

function menuItems(item: PlaylistItem): Array<Record<string, unknown>> {
    const items = item.content?.items;

    return Array.isArray(items)
        ? items.filter((entry): entry is Record<string, unknown> =>
              Boolean(
                  entry && typeof entry === 'object' && !Array.isArray(entry),
              ),
          )
        : [];
}

function renderCustomTemplate(item: PlaylistItem): string {
    const html = item.template_definition?.html ?? '';
    const data = item.content ?? {};
    const escaped = (value: unknown): string =>
        String(value ?? '').replace(
            /[&<>"']/g,
            (character) =>
                ({
                    '&': '&amp;',
                    '<': '&lt;',
                    '>': '&gt;',
                    '"': '&quot;',
                    "'": '&#039;',
                })[character] ?? character,
        );
    const valueAtPath = (source: unknown, path: string): unknown => {
        if (path === 'this') return source;

        return path
            .split('.')
            .reduce<unknown>(
                (value, segment) =>
                    value && typeof value === 'object' && segment in value
                        ? (value as Record<string, unknown>)[segment]
                        : undefined,
                source,
            );
    };
    const replaceFields = (markup: string, source: unknown): string =>
        markup.replace(
            /{{\s*(?:this\.)?([\w.-]+)\s*}}/g,
            (_match, key: string) => escaped(valueAtPath(source, key)),
        );
    const renderedHtml = html.replace(
        /{{#each\s+([\w.-]+)}}([\s\S]*?){{\/each}}/g,
        (_match, path: string, block: string) => {
            const rows = valueAtPath(data, path);

            return Array.isArray(rows)
                ? rows.map((row) => replaceFields(block, row)).join('')
                : '';
        },
    );

    return `<style>${item.template_definition?.css ?? ''}</style>${renderedHtml}`
        .replace(/<script[\s\S]*?<\/script>/gi, '')
        .replace(/\son\w+\s*=\s*(['"]).*?\1/gi, '')
        .replace(/javascript:/gi, '')
        .replace(/{{\s*([\w.-]+)\s*}}/g, (_match, key: string) =>
            escaped(valueAtPath(data, key)),
        );
}

function showNextItem(): void {
    const itemCount = manifest.value?.playlist.items.length ?? 0;

    if (itemCount > 0) {
        const nextIndex = (activeIndex.value + 1) % itemCount;
        transitionName.value = normalizeTransition(
            manifest.value?.playlist.items[nextIndex] ?? null,
        );
        activeIndex.value = nextIndex;
        scheduleNextItem();
    }
}

onMounted(() => {
    window.addEventListener('online', syncOnlineState);
    window.addEventListener('offline', syncOnlineState);
    void initSignageCore().then((isReady) => {
        wasmCoreReady.value = isReady || isSignageCoreWasmReady();
        wasmRevision.value += 1;
    });
    void registerPlayerServiceWorker();
    void loadManifest();
    void sendHeartbeat();
    clockTimer = window.setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onBeforeUnmount(() => {
    window.removeEventListener('online', syncOnlineState);
    window.removeEventListener('offline', syncOnlineState);

    if (itemTimer) {
        window.clearTimeout(itemTimer);
    }

    if (refreshTimer) {
        window.clearInterval(refreshTimer);
    }

    if (heartbeatTimer) {
        window.clearInterval(heartbeatTimer);
    }

    if (identifyTimer) {
        window.clearTimeout(identifyTimer);
    }

    if (clockTimer) {
        window.clearInterval(clockTimer);
    }
});
</script>

<template>
    <Head :title="`Player ${deviceKey}`" />

    <main class="fixed inset-0 overflow-hidden bg-black text-white">
        <section
            class="flex h-screen w-screen items-center justify-center bg-black"
        >
            <div
                class="relative h-full w-full overflow-hidden bg-zinc-950"
                :style="screenStyle"
            >
                <div
                    v-if="isLoading"
                    class="grid h-full w-full place-items-center"
                >
                    <div class="text-center">
                        <div class="text-lg font-medium">Loading player</div>
                        <div class="mt-2 text-sm text-zinc-400">
                            {{ deviceKey }}
                        </div>
                    </div>
                </div>

                <div
                    v-else-if="errorMessage"
                    class="grid h-full w-full place-items-center bg-red-950"
                >
                    <div class="max-w-xl px-8 text-center">
                        <div class="text-xl font-semibold">
                            Manifest unavailable
                        </div>
                        <div class="mt-3 text-sm text-red-100">
                            {{ errorMessage }}
                        </div>
                    </div>
                </div>

                <div
                    v-else-if="!activeItem"
                    class="grid h-full w-full place-items-center"
                >
                    <div class="text-center">
                        <div class="text-xl font-semibold">Playlist kosong</div>
                        <div class="mt-2 text-sm text-zinc-400">
                            {{ manifest?.playlist.name }}
                        </div>
                    </div>
                </div>

                <template v-else>
                    <Transition :name="transitionName" mode="out-in">
                        <div :key="activeItem.id" class="absolute inset-0">
                            <audio
                                v-if="activeItem.audio?.url"
                                :src="activeItem.audio.url"
                                :loop="activeItem.audio.loop"
                                autoplay
                                class="hidden"
                                @error="onMediaError"
                            />
                            <div
                                v-if="activeItem.template === 'custom'"
                                class="h-full w-full overflow-hidden bg-zinc-950 text-white"
                            >
                                <div
                                    v-html="renderCustomTemplate(activeItem)"
                                />
                            </div>
                            <div
                                v-else-if="activeItem.template === 'weather'"
                                class="weather-panel relative flex h-full w-full flex-col justify-between overflow-hidden px-[8%] py-[7%] text-white"
                                :class="weatherBackgroundClass(activeItem)"
                            >
                                <svg
                                    class="weather-art"
                                    viewBox="0 0 1200 700"
                                    preserveAspectRatio="none"
                                    aria-hidden="true"
                                >
                                    <defs>
                                        <linearGradient
                                            id="weather-sky-clear"
                                            x1="0"
                                            y1="0"
                                            x2="1"
                                            y2="1"
                                        >
                                            <stop
                                                offset="0"
                                                stop-color="#38bdf8"
                                            />
                                            <stop
                                                offset="1"
                                                stop-color="#0f766e"
                                            />
                                        </linearGradient>
                                        <linearGradient
                                            id="weather-sky-cloudy"
                                            x1="0"
                                            y1="0"
                                            x2="1"
                                            y2="1"
                                        >
                                            <stop
                                                offset="0"
                                                stop-color="#64748b"
                                            />
                                            <stop
                                                offset="1"
                                                stop-color="#334155"
                                            />
                                        </linearGradient>
                                        <linearGradient
                                            id="weather-sky-rain"
                                            x1="0"
                                            y1="0"
                                            x2="1"
                                            y2="1"
                                        >
                                            <stop
                                                offset="0"
                                                stop-color="#315b83"
                                            />
                                            <stop
                                                offset="1"
                                                stop-color="#172554"
                                            />
                                        </linearGradient>
                                    </defs>
                                    <rect
                                        width="1200"
                                        height="700"
                                        :fill="
                                            weatherBackgroundClass(
                                                activeItem,
                                            ) === 'weather-clear'
                                                ? 'url(#weather-sky-clear)'
                                                : weatherBackgroundClass(
                                                        activeItem,
                                                    ) === 'weather-cloudy'
                                                  ? 'url(#weather-sky-cloudy)'
                                                  : 'url(#weather-sky-rain)'
                                        "
                                    >
                                        <animate
                                            attributeName="opacity"
                                            values="0.92;1;0.92"
                                            dur="8s"
                                            repeatCount="indefinite"
                                        />
                                    </rect>
                                    <g
                                        v-if="
                                            weatherBackgroundClass(
                                                activeItem,
                                            ) === 'weather-clear'
                                        "
                                        class="weather-svg-sun"
                                    >
                                        <circle
                                            cx="970"
                                            cy="145"
                                            r="76"
                                            fill="#fde68a"
                                        >
                                            <animate
                                                attributeName="r"
                                                values="76;84;76"
                                                dur="5s"
                                                repeatCount="indefinite"
                                            />
                                            <animate
                                                attributeName="opacity"
                                                values="0.86;1;0.86"
                                                dur="5s"
                                                repeatCount="indefinite"
                                            />
                                        </circle>
                                        <circle
                                            cx="970"
                                            cy="145"
                                            r="112"
                                            fill="none"
                                            stroke="#fef3c7"
                                            stroke-width="5"
                                            stroke-dasharray="3 26"
                                            opacity="0.8"
                                        >
                                            <animateTransform
                                                attributeName="transform"
                                                type="rotate"
                                                from="0 970 145"
                                                to="360 970 145"
                                                dur="24s"
                                                repeatCount="indefinite"
                                            />
                                            <animate
                                                attributeName="r"
                                                values="106;120;106"
                                                dur="5s"
                                                repeatCount="indefinite"
                                            />
                                        </circle>
                                    </g>
                                    <g
                                        v-if="
                                            [
                                                'weather-cloudy',
                                                'weather-rain',
                                                'weather-storm',
                                            ].includes(
                                                weatherBackgroundClass(
                                                    activeItem,
                                                ),
                                            )
                                        "
                                        class="weather-svg-clouds"
                                        fill="#e2e8f0"
                                    >
                                        <path
                                            d="M80 250c0-48 39-87 87-87 20-55 72-93 134-93 73 0 133 52 146 121 10-3 20-5 31-5 54 0 98 44 98 98H80c0-12 0-23 0-34Z"
                                            opacity="0.5"
                                        />
                                        <path
                                            d="M710 490c0-37 30-67 67-67 16-42 55-70 102-70 56 0 102 40 112 92 8-2 16-4 24-4 41 0 75 34 75 75H710v-26Z"
                                            opacity="0.24"
                                        />
                                    </g>
                                    <g
                                        v-if="
                                            [
                                                'weather-rain',
                                                'weather-storm',
                                            ].includes(
                                                weatherBackgroundClass(
                                                    activeItem,
                                                ),
                                            )
                                        "
                                        class="weather-svg-rain"
                                        stroke="#bae6fd"
                                        stroke-width="5"
                                        stroke-linecap="round"
                                        opacity="0.5"
                                    >
                                        <path
                                            d="M190 390l-25 70M300 390l-25 70M410 390l-25 70M520 390l-25 70M630 390l-25 70M740 390l-25 70M850 390l-25 70M960 390l-25 70"
                                        />
                                        <path
                                            d="M245 510l-25 70M355 510l-25 70M465 510l-25 70M575 510l-25 70M685 510l-25 70M795 510l-25 70M905 510l-25 70"
                                        />
                                    </g>
                                </svg>
                                <div
                                    class="flex items-start justify-between gap-8"
                                >
                                    <div>
                                        <div
                                            class="text-sm font-medium tracking-[0.25em] text-cyan-300 uppercase"
                                        >
                                            Live weather
                                        </div>
                                        <div
                                            class="mt-3 text-4xl font-semibold tracking-tight"
                                        >
                                            {{
                                                weatherData(activeItem).location
                                            }}
                                        </div>
                                    </div>
                                    <div
                                        class="text-right text-sm text-slate-400"
                                    >
                                        {{ weatherData(activeItem).updatedAt }}
                                    </div>
                                </div>
                                <div
                                    class="grid gap-10 md:grid-cols-[1.2fr_1fr] md:items-end"
                                >
                                    <div>
                                        <div
                                            class="text-[clamp(5rem,14vw,12rem)] leading-none font-semibold tracking-[-0.04em]"
                                        >
                                            {{
                                                weatherData(activeItem)
                                                    .temperature
                                            }}
                                        </div>
                                        <div
                                            class="mt-5 text-3xl font-medium text-slate-200"
                                        >
                                            {{
                                                weatherData(activeItem)
                                                    .condition
                                            }}
                                        </div>
                                    </div>
                                    <div class="grid grid-cols-2 gap-3 text-lg">
                                        <div
                                            class="border-l-2 border-cyan-400/70 pl-4"
                                        >
                                            <div class="text-sm text-slate-400">
                                                Kelembapan
                                            </div>
                                            <div
                                                class="mt-1 text-3xl font-semibold"
                                            >
                                                {{
                                                    weatherData(activeItem)
                                                        .humidity
                                                }}
                                            </div>
                                        </div>
                                        <div
                                            v-if="weatherData(activeItem).wind"
                                            class="border-l-2 border-cyan-400/70 pl-4"
                                        >
                                            <div class="text-sm text-slate-400">
                                                Angin
                                            </div>
                                            <div
                                                class="mt-1 text-3xl font-semibold"
                                            >
                                                {{
                                                    weatherData(activeItem).wind
                                                }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else-if="
                                    activeItem.template === 'flight-board'
                                "
                                class="flex h-full w-full flex-col bg-slate-950 px-[5%] py-[4%] text-white"
                            >
                                <div
                                    class="flex items-end justify-between border-b border-cyan-400/30 pb-5"
                                >
                                    <div>
                                        <div
                                            class="text-sm font-medium tracking-[0.25em] text-cyan-300 uppercase"
                                        >
                                            Flight information
                                        </div>
                                        <div
                                            class="mt-2 text-5xl font-semibold"
                                        >
                                            {{ templateTitle(activeItem) }}
                                        </div>
                                    </div>
                                    <div
                                        class="text-right text-sm text-slate-400"
                                    >
                                        {{ activeItem.content?.subtitle }}
                                    </div>
                                </div>
                                <div
                                    class="mt-6 grid grid-cols-[1.1fr_1.5fr_0.8fr_0.7fr_1fr] gap-3 border-b border-white/10 px-5 pb-3 text-sm font-medium tracking-wider text-slate-400 uppercase"
                                >
                                    <span>Flight</span><span>Destination</span
                                    ><span>Time</span><span>Gate</span
                                    ><span>Status</span>
                                </div>
                                <div
                                    class="mt-2 space-y-2 overflow-hidden text-2xl"
                                >
                                    <div
                                        v-for="(row, rowIndex) in objectRows(
                                            activeItem,
                                        )"
                                        :key="rowIndex"
                                        class="grid grid-cols-[1.1fr_1.5fr_0.8fr_0.7fr_1fr] gap-3 rounded-md border border-white/10 bg-white/[0.06] px-5 py-4"
                                    >
                                        <span
                                            class="font-semibold text-cyan-200"
                                            >{{
                                                row.flight ?? row.number ?? '-'
                                            }}</span
                                        ><span>{{
                                            row.destination ?? '-'
                                        }}</span
                                        ><span>{{ row.time ?? '-' }}</span
                                        ><span>{{ row.gate ?? '-' }}</span
                                        ><span class="text-emerald-300">{{
                                            row.status ?? '-'
                                        }}</span>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else-if="activeItem.template === 'data-table'"
                                class="flex h-full w-full flex-col bg-zinc-950 px-[5%] py-[5%] text-white"
                            >
                                <div class="border-b border-white/15 pb-5">
                                    <div
                                        class="text-sm font-medium tracking-[0.25em] text-cyan-300 uppercase"
                                    >
                                        Live data
                                    </div>
                                    <div class="mt-2 text-5xl font-semibold">
                                        {{ templateTitle(activeItem) }}
                                    </div>
                                    <div class="mt-2 text-xl text-slate-400">
                                        {{ activeItem.content?.subtitle }}
                                    </div>
                                </div>
                                <div
                                    class="mt-6 overflow-hidden rounded-md border border-white/15"
                                >
                                    <div
                                        class="grid gap-3 bg-white/10 px-5 py-4 text-xl font-semibold"
                                        :style="{
                                            gridTemplateColumns: `repeat(${Math.max(tableColumns(activeItem).length, 1)}, minmax(0, 1fr))`,
                                        }"
                                    >
                                        <span
                                            v-for="column in tableColumns(
                                                activeItem,
                                            )"
                                            :key="column"
                                            >{{ column }}</span
                                        >
                                    </div>
                                    <div
                                        v-for="(row, rowIndex) in tableValues(
                                            activeItem,
                                        )"
                                        :key="rowIndex"
                                        class="grid gap-3 border-t border-white/10 px-5 py-4 text-xl"
                                        :style="{
                                            gridTemplateColumns: `repeat(${Math.max(tableColumns(activeItem).length, 1)}, minmax(0, 1fr))`,
                                        }"
                                    >
                                        <span
                                            v-for="(value, valueIndex) in row"
                                            :key="valueIndex"
                                            >{{ value }}</span
                                        >
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else-if="activeItem.template === 'menu'"
                                class="flex h-full w-full flex-col bg-stone-950 px-[6%] py-[5%] text-white"
                            >
                                <div class="text-center">
                                    <div
                                        class="text-sm font-medium tracking-[0.3em] text-amber-300 uppercase"
                                    >
                                        Menu
                                    </div>
                                    <div class="mt-2 text-5xl font-semibold">
                                        {{ templateTitle(activeItem) }}
                                    </div>
                                    <div class="mt-2 text-xl text-stone-300">
                                        {{ activeItem.content?.subtitle }}
                                    </div>
                                </div>
                                <div
                                    class="mt-8 grid flex-1 grid-cols-2 gap-5 overflow-hidden xl:grid-cols-3"
                                >
                                    <div
                                        v-for="(
                                            menuItem, menuIndex
                                        ) in menuItems(activeItem)"
                                        :key="menuIndex"
                                        class="overflow-hidden rounded-md border border-white/10 bg-white/[0.08]"
                                    >
                                        <img
                                            v-if="menuItem.image"
                                            :src="String(menuItem.image)"
                                            :alt="String(menuItem.name ?? '')"
                                            class="h-32 w-full object-cover"
                                        />
                                        <div class="p-5">
                                            <div
                                                class="flex items-start justify-between gap-3"
                                            >
                                                <div
                                                    class="text-2xl font-semibold"
                                                >
                                                    {{ menuItem.name ?? '-' }}
                                                </div>
                                                <div
                                                    class="shrink-0 text-xl font-semibold text-amber-300"
                                                >
                                                    {{ menuItem.price ?? '' }}
                                                </div>
                                            </div>
                                            <div
                                                class="mt-2 text-base text-stone-300"
                                            >
                                                {{ menuItem.description ?? '' }}
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else-if="
                                    activeItem.template === 'split-screen' &&
                                    activeItem.split_screen
                                "
                                class="flex h-full w-full overflow-hidden bg-zinc-950"
                            >
                                <div
                                    class="relative h-full overflow-hidden bg-zinc-900"
                                    :style="{
                                        width: `${activeItem.split_screen.left_width}%`,
                                    }"
                                >
                                    <img
                                        v-if="activeItem.split_screen.left?.url"
                                        :src="activeItem.split_screen.left.url"
                                        :alt="activeItem.split_screen.left.name"
                                        class="h-full w-full object-cover"
                                        @error="onMediaError"
                                    />
                                    <div
                                        v-else
                                        class="grid h-full place-items-center text-sm text-zinc-400"
                                    >
                                        Image unavailable
                                    </div>
                                </div>
                                <div
                                    class="relative h-full overflow-hidden bg-black"
                                    :style="{
                                        width: `${activeItem.split_screen.right_width}%`,
                                    }"
                                >
                                    <iframe
                                        v-if="
                                            activeItem.split_screen
                                                .right_source === 'youtube'
                                        "
                                        :src="
                                            youtubeEmbedUrl(
                                                activeItem.split_screen
                                                    .right_url,
                                            )
                                        "
                                        class="h-full w-full border-0"
                                        allow="
                                            autoplay;
                                            fullscreen;
                                            picture-in-picture;
                                        "
                                        allowfullscreen
                                    />
                                    <video
                                        v-else-if="
                                            activeItem.split_screen
                                                .right_source === 'media' &&
                                            (activeItem.split_screen.right
                                                ?.type === 'video' ||
                                                activeItem.split_screen.right
                                                    ?.type === 'video_url')
                                        "
                                        :src="
                                            activeItem.split_screen.right.url ??
                                            undefined
                                        "
                                        class="h-full w-full object-cover"
                                        autoplay
                                        muted
                                        loop
                                        playsinline
                                        @error="onMediaError"
                                    />
                                    <iframe
                                        v-else-if="
                                            activeItem.split_screen
                                                .right_source === 'media' &&
                                            activeItem.split_screen.right
                                                ?.type === 'web'
                                        "
                                        :src="
                                            activeItem.split_screen.right.url ??
                                            undefined
                                        "
                                        class="h-full w-full border-0"
                                        allow="autoplay; fullscreen"
                                    />
                                    <video
                                        v-else-if="
                                            activeItem.split_screen
                                                .right_source === 'url' &&
                                            isVideoUrl(
                                                activeItem.split_screen
                                                    .right_url,
                                            )
                                        "
                                        :src="
                                            activeItem.split_screen.right_url ??
                                            undefined
                                        "
                                        class="h-full w-full object-cover"
                                        autoplay
                                        muted
                                        loop
                                        playsinline
                                        @error="onMediaError"
                                    />
                                    <iframe
                                        v-else-if="
                                            activeItem.split_screen
                                                .right_source === 'url'
                                        "
                                        :src="
                                            activeItem.split_screen.right_url ??
                                            undefined
                                        "
                                        class="h-full w-full border-0"
                                        allow="autoplay; fullscreen"
                                    />
                                    <div
                                        v-else
                                        class="grid h-full place-items-center text-sm text-zinc-400"
                                    >
                                        Video unavailable
                                    </div>
                                </div>
                            </div>
                            <div
                                v-else-if="
                                    (activeItem.template ?? 'media') !== 'media'
                                "
                                class="flex h-full w-full flex-col justify-center bg-zinc-950 px-[8%] py-[6%] text-white"
                            >
                                <div class="text-5xl font-bold tracking-tight">
                                    {{ templateTitle(activeItem) }}
                                </div>
                                <div
                                    v-if="activeItem.content?.subtitle"
                                    class="mt-3 text-2xl text-zinc-300"
                                >
                                    {{ activeItem.content.subtitle }}
                                </div>
                                <div
                                    v-if="
                                        activeItem.template === 'announcement'
                                    "
                                    class="mt-10 max-w-5xl text-4xl leading-tight whitespace-pre-line"
                                >
                                    {{ activeItem.content?.body }}
                                </div>
                                <div v-else class="mt-10 grid gap-3 text-2xl">
                                    <div
                                        v-for="(row, rowIndex) in templateRows(
                                            activeItem,
                                        )"
                                        :key="rowIndex"
                                        class="rounded-lg border border-white/15 bg-white/10 px-5 py-4"
                                    >
                                        {{ row }}
                                    </div>
                                </div>
                            </div>
                            <img
                                v-else-if="
                                    (activeItem.media?.type === 'image' ||
                                        activeItem.media?.type ===
                                            'image_url') &&
                                    activeItem.media.url
                                "
                                :src="activeItem.media.url"
                                :alt="activeItem.media.name"
                                class="absolute"
                                :class="mediaFitClass"
                                :style="mediaBoxStyle"
                                @error="onMediaError"
                            />

                            <video
                                v-else-if="
                                    (activeItem.media?.type === 'video' ||
                                        activeItem.media?.type ===
                                            'video_url') &&
                                    activeItem.media.url
                                "
                                :src="activeItem.media.url"
                                class="absolute"
                                :class="mediaFitClass"
                                :style="mediaBoxStyle"
                                autoplay
                                muted
                                playsinline
                                @ended="onVideoEnded"
                                @error="onMediaError"
                            />

                            <iframe
                                v-else-if="activeItem.media?.url"
                                :src="activeItem.media.url"
                                :title="activeItem.media.name"
                                class="h-full w-full border-0"
                            />
                        </div>
                    </Transition>

                    <div
                        v-for="overlay in tickerOverlays"
                        :key="`ticker-${overlay.text}`"
                        class="pointer-events-none absolute right-0 left-0 z-20 overflow-hidden px-4 py-3 text-lg font-medium"
                        :class="
                            overlay.position === 'top' ? 'top-0' : 'bottom-0'
                        "
                        :style="tickerStyle(overlay)"
                    >
                        <div
                            class="ticker-track w-max whitespace-nowrap"
                            :style="tickerAnimationStyle(overlay)"
                        >
                            {{ tickerText(overlay) }}
                        </div>
                    </div>

                    <div
                        v-for="overlay in clockOverlays"
                        :key="`clock-${overlay.id}`"
                        class="pointer-events-none absolute z-20 rounded-md px-3 py-2 text-xl font-semibold tabular-nums"
                        :class="clockPositionClass(overlay.config.position)"
                        :style="{
                            ...tickerStyle(overlay.config),
                            ...overlayOffsetStyle(overlay.config),
                        }"
                    >
                        {{
                            currentTime.toLocaleTimeString([], {
                                hour: '2-digit',
                                minute: '2-digit',
                                second: '2-digit',
                                hour12: overlay.config.format === '12h',
                            })
                        }}
                    </div>

                    <div
                        v-for="overlay in weatherOverlays"
                        :key="`weather-${overlay.id}`"
                        class="pointer-events-none absolute z-30 min-w-56 rounded-lg border border-white/15 bg-slate-950/85 px-4 py-3 text-white shadow-xl backdrop-blur-sm"
                        :class="
                            weatherOverlayPositionClass(overlay.config.position)
                        "
                        :style="{
                            ...tickerStyle(overlay.config),
                            ...overlayOffsetStyle(overlay.config),
                        }"
                    >
                        <div class="flex items-center justify-between gap-5">
                            <div>
                                <div
                                    class="text-[10px] font-semibold tracking-[0.2em] text-cyan-300 uppercase"
                                >
                                    Weather
                                </div>
                                <div
                                    class="mt-1 text-sm font-medium text-white/75"
                                >
                                    {{ weatherOverlayData(overlay).location }}
                                </div>
                            </div>
                            <div class="text-3xl leading-none font-semibold">
                                {{ weatherOverlayData(overlay).temperature }}
                            </div>
                        </div>
                        <div
                            class="mt-3 flex items-center gap-3 border-t border-white/10 pt-2 text-xs text-white/70"
                        >
                            <span>{{
                                weatherOverlayData(overlay).condition
                            }}</span>
                            <span
                                >Humidity
                                {{ weatherOverlayData(overlay).humidity }}</span
                            >
                            <span v-if="weatherOverlayData(overlay).wind"
                                >Wind
                                {{ weatherOverlayData(overlay).wind }}</span
                            >
                        </div>
                    </div>

                    <div
                        v-if="mediaErrorMessage"
                        class="absolute inset-0 grid place-items-center bg-zinc-950"
                    >
                        <div class="max-w-xl px-8 text-center">
                            <div class="text-xl font-semibold">
                                Media unavailable
                            </div>
                            <div class="mt-3 text-sm text-zinc-400">
                                {{ mediaErrorMessage }}
                            </div>
                        </div>
                    </div>

                    <img
                        v-if="manifest?.branding.logo_url"
                        :src="manifest.branding.logo_url"
                        alt=""
                        class="pointer-events-none absolute z-40 h-auto object-contain"
                        :class="logoPositionClass"
                        :style="logoStyle"
                    />

                    <div
                        class="pointer-events-none absolute top-0 left-0 h-1 w-full"
                        :style="{ backgroundColor: brandColor }"
                    />

                    <div
                        v-if="shouldShowOverlay"
                        class="pointer-events-none absolute right-4 bottom-4 rounded border border-white/10 bg-black/60 px-3 py-2 text-right text-xs text-white/80 shadow-lg"
                    >
                        <div class="flex items-center justify-end gap-2">
                            <span>{{ brandLabel }}</span>
                        </div>
                        <div class="text-white/55">
                            {{ manifest?.screen.name }} ·
                            {{ manifest?.playlist.name }}
                        </div>
                        <div
                            v-if="manifestSource === 'cache' || !networkOnline"
                            class="mt-1 text-[11px] text-amber-200"
                        >
                            {{
                                manifestSource === 'cache'
                                    ? 'Cached manifest'
                                    : 'Network offline'
                            }}
                        </div>
                    </div>

                    <div
                        v-if="identifyMessage"
                        class="pointer-events-none absolute inset-0 grid place-items-center bg-black/35"
                    >
                        <div
                            class="rounded-lg border border-white/30 bg-black/80 px-8 py-6 text-center shadow-2xl"
                        >
                            <img
                                v-if="manifest?.branding.logo_url"
                                :src="manifest.branding.logo_url"
                                alt=""
                                class="mx-auto mb-4 max-w-52 border border-white/20 object-contain"
                                :style="logoStyle"
                            />
                            <div
                                v-else
                                class="mx-auto mb-4 grid size-14 place-items-center rounded-full text-lg font-semibold"
                                :style="{ backgroundColor: brandColor }"
                            >
                                {{ brandLabel.charAt(0).toUpperCase() }}
                            </div>
                            <div class="text-3xl font-semibold">
                                {{ identifyMessage }}
                            </div>
                            <div class="mt-3 text-sm text-white/65">
                                Display identification
                            </div>
                        </div>
                    </div>
                </template>
            </div>
        </section>
    </main>
</template>

<style scoped>
.fade-enter-active,
.fade-leave-active {
    transition: opacity 700ms ease;
}

.fade-enter-from,
.fade-leave-to {
    opacity: 0;
}

.slide-left-enter-active,
.slide-left-leave-active,
.slide-right-enter-active,
.slide-right-leave-active,
.slide-up-enter-active,
.slide-up-leave-active,
.slide-down-enter-active,
.slide-down-leave-active,
.zoom-enter-active,
.zoom-leave-active {
    transition:
        transform 700ms ease,
        opacity 700ms ease;
}

.slide-left-enter-from {
    opacity: 0;
    transform: translateX(4%);
}

.slide-left-leave-to {
    opacity: 0;
    transform: translateX(-4%);
}

.slide-right-enter-from {
    opacity: 0;
    transform: translateX(-4%);
}

.slide-right-leave-to {
    opacity: 0;
    transform: translateX(4%);
}

.slide-up-enter-from {
    opacity: 0;
    transform: translateY(4%);
}

.slide-up-leave-to {
    opacity: 0;
    transform: translateY(-4%);
}

.slide-down-enter-from {
    opacity: 0;
    transform: translateY(-4%);
}

.slide-down-leave-to {
    opacity: 0;
    transform: translateY(4%);
}

.zoom-enter-from {
    opacity: 0;
    transform: scale(1.06);
}

.zoom-leave-to {
    opacity: 0;
    transform: scale(0.96);
}

.ticker-track {
    animation: ticker-scroll linear infinite;
}

@keyframes ticker-scroll {
    from {
        transform: translateX(100vw);
    }

    to {
        transform: translateX(-100%);
    }
}

.weather-panel {
    isolation: isolate;
    transition: background-color 500ms ease;
}

.weather-panel::after {
    position: absolute;
    inset: 0;
    z-index: -1;
    background: linear-gradient(
        135deg,
        rgb(255 255 255 / 10%),
        transparent 48%,
        rgb(15 23 42 / 28%)
    );
    content: '';
}

.weather-clear {
    background-color: #0ea5e9;
}

.weather-cloudy {
    background-color: #475569;
}

.weather-rain {
    background-color: #1e3a5f;
}

.weather-storm {
    background-color: #172033;
}

.weather-art {
    position: absolute;
    inset: 0;
    width: 100%;
    height: 100%;
    z-index: -1;
    overflow: hidden;
}

.weather-svg-clouds {
    animation: weather-cloud-drift 18s ease-in-out infinite alternate;
}

.weather-svg-rain {
    animation: weather-rain-fall 1.8s linear infinite;
}

@keyframes weather-cloud-drift {
    to {
        transform: translateX(24px);
    }
}

@keyframes weather-rain-fall {
    to {
        transform: translateY(24px);
    }
}
</style>
