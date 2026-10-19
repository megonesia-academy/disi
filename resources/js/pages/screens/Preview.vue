<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import OverlayLayer from '@/components/player/OverlayLayer.vue';
import { type MediaAssetType } from '@/lib/media';
import { type OverlayConfig, type OverlayEntry } from '@/lib/overlays';
import { renderTemplateDocument } from '@/lib/templateRenderer';
import {
    computeMediaBox,
    initSignageCore,
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

type TickerOverlay = OverlayConfig & { type: 'ticker' };

type PlaylistOverlay = {
    id: string;
    type: string;
    config: OverlayConfig;
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
    schedule: {
        id: string;
        priority: number;
        starts_at?: string | null;
        ends_at?: string | null;
        days_of_week?: number[] | null;
        start_time?: string | null;
        end_time?: string | null;
    };
};

const props = defineProps<{
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
    manifest: Manifest | null;
    error: string | null;
}>();

const activeIndex = ref(0);
const mediaError = ref<string | null>(null);
const wasmRevision = ref(0);
const transitionName = ref('none');
const currentTime = ref(new Date());
const itemStartedAt = ref<number | null>(null);

watch(activeIndex, () => {
    itemStartedAt.value = Date.now();
});

let itemTimer: number | undefined;
let clockTimer: number | undefined;

const activeItem = computed(() => {
    return props.manifest?.playlist.items[activeIndex.value] ?? null;
});

const screenStyle = computed(() => {
    const resolution =
        props.manifest?.screen.resolution ?? props.screen.resolution;

    return {
        aspectRatio: `${resolution.width} / ${resolution.height}`,
    };
});

const brandColor = computed(() => {
    return props.manifest?.branding.brand_color ?? '#0891b2';
});

const brandLabel = computed(() => {
    return props.manifest?.branding.label ?? props.screen.name;
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

const overlayEntries = computed<OverlayEntry[]>(() => {
    const globalOverlays = (props.manifest?.playlist.overlays ?? []).map(
        (overlay): OverlayEntry => ({
            id: overlay.id,
            type: overlay.type,
            config: overlay.config,
        }),
    );
    const itemOverlays = (activeItem.value?.config?.overlays ?? []).map(
        (overlay, index): OverlayEntry => ({
            id: `item-${activeIndex.value}-${index}`,
            type: 'ticker',
            config: overlay,
        }),
    );

    return [...globalOverlays, ...itemOverlays];
});

const overlayItem = computed(() => {
    const item = activeItem.value;

    if (!item) {
        return null;
    }

    const items = props.manifest?.playlist.items ?? [];
    const nextItem =
        items.length > 0 ? items[(activeIndex.value + 1) % items.length] : null;
    const durationSeconds = Math.max(
        item.duration_seconds ?? item.media?.duration_seconds ?? 10,
        1,
    );
    const elapsedSeconds = itemStartedAt.value
        ? Math.max(
              0,
              (currentTime.value.getTime() - itemStartedAt.value) / 1000,
          )
        : 0;

    return {
        title: itemTitle(item),
        nextTitle: nextItem ? itemTitle(nextItem) : null,
        elapsedSeconds: Math.min(elapsedSeconds, durationSeconds),
        durationSeconds,
    };
});

function itemTitle(item: PlaylistItem | null): string {
    if (!item) {
        return '';
    }

    const contentTitle = item.content?.title;

    if (typeof contentTitle === 'string' && contentTitle.trim() !== '') {
        return contentTitle;
    }

    return item.media?.name ?? templateTitle(item);
}

const mediaBoxStyle = computed(() => {
    wasmRevision.value;

    const resolution =
        props.manifest?.screen.resolution ?? props.screen.resolution;
    const item = activeItem.value;

    if (!item || !item.media) {
        return {};
    }

    const box = computeMediaBox(
        resolution.width,
        resolution.height,
        item.media.width,
        item.media.height,
        (item.config?.fit ?? 'cover') as MediaFit,
    );

    return {
        left: `${(box.x / resolution.width) * 100}%`,
        top: `${(box.y / resolution.height) * 100}%`,
        width: `${(box.width / resolution.width) * 100}%`,
        height: `${(box.height / resolution.height) * 100}%`,
    };
});

const shouldShowOverlay = computed(() => {
    return props.manifest?.branding.behavior.show_player_overlay ?? true;
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
        positions[props.manifest?.branding.logo_position ?? 'top-right'] ??
        positions['top-right']
    );
});

const logoStyle = computed(() => {
    const position = props.manifest?.branding.logo_position ?? 'top-right';
    const offsetX = Number(props.manifest?.branding.logo_offset_x ?? 0);
    const offsetY = Number(props.manifest?.branding.logo_offset_y ?? 0);
    const style: Record<string, string> = {
        width: `${props.manifest?.branding.logo_width ?? 120}px`,
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

function scheduleNextItem(): void {
    if (itemTimer) {
        window.clearTimeout(itemTimer);
    }

    if (!activeItem.value || !props.manifest?.playlist.items.length) {
        return;
    }

    mediaError.value = null;

    const duration =
        Math.max(
            activeItem.value.duration_seconds ??
                activeItem.value.media?.duration_seconds ??
                10,
            1,
        ) * 1000;

    itemTimer = window.setTimeout(showNextItem, duration);
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

function showNextItem(): void {
    const itemCount = props.manifest?.playlist.items.length ?? 0;

    if (itemCount === 0) {
        return;
    }

    const nextIndex = (activeIndex.value + 1) % itemCount;
    transitionName.value = normalizeTransition(
        props.manifest?.playlist.items[nextIndex] ?? null,
    );
    activeIndex.value = nextIndex;
    scheduleNextItem();
}

function onMediaError(): void {
    if (!activeItem.value) {
        return;
    }

    mediaError.value = `${activeItem.value.media?.name ?? 'Konten'} tidak bisa dimuat.`;

    if (itemTimer) {
        window.clearTimeout(itemTimer);
    }

    itemTimer = window.setTimeout(showNextItem, 2_000);
}

function templateTitle(item: PlaylistItem): string {
    return String(
        item.content?.title ??
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
    return Array.isArray(body)
        ? body.map((value) => String(value))
        : String(body ?? '')
              .split('\n')
              .map((row) => row.trim())
              .filter(Boolean);
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
    return renderTemplateDocument(item.template_definition, item.content ?? {});
}

onMounted(() => {
    void initSignageCore().then(() => {
        wasmRevision.value += 1;
    });

    itemStartedAt.value = Date.now();
    scheduleNextItem();
    clockTimer = window.setInterval(() => {
        currentTime.value = new Date();
    }, 1000);
});

onBeforeUnmount(() => {
    if (itemTimer) {
        window.clearTimeout(itemTimer);
    }

    if (clockTimer) {
        window.clearInterval(clockTimer);
    }
});
</script>

<template>
    <Head :title="`Preview ${screen.name}`" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300"
                >
                    Screen preview
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ screen.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ screen.location ?? 'No location' }} ·
                    {{ screen.resolution.width }} x
                    {{ screen.resolution.height }} · {{ screen.orientation }}
                </p>
            </div>

            <div class="flex gap-2">
                <Link
                    href="/screens"
                    class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Screens
                </Link>
                <Link
                    href="/schedules"
                    class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Schedules
                </Link>
            </div>
        </section>

        <section class="grid gap-4 xl:grid-cols-[minmax(0,1fr)_340px]">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="mx-auto grid max-h-[72vh] min-h-[420px] w-full place-items-center rounded-lg bg-zinc-950 p-4"
                >
                    <div
                        class="relative max-h-full w-full overflow-hidden rounded-md bg-black text-white shadow-2xl"
                        :style="screenStyle"
                    >
                        <div
                            v-if="error"
                            class="grid h-full w-full place-items-center bg-zinc-950"
                        >
                            <div class="max-w-lg px-8 text-center">
                                <div class="text-xl font-semibold">
                                    Preview belum tersedia
                                </div>
                                <div class="mt-3 text-sm text-zinc-400">
                                    {{ error }}
                                </div>
                            </div>
                        </div>

                        <div
                            v-else-if="!activeItem"
                            class="grid h-full w-full place-items-center bg-zinc-950"
                        >
                            <div class="text-center">
                                <div class="text-xl font-semibold">
                                    Playlist kosong
                                </div>
                                <div class="mt-2 text-sm text-zinc-400">
                                    Tambahkan item ke playlist aktif.
                                </div>
                            </div>
                        </div>

                        <template v-else>
                            <Transition :name="transitionName" mode="out-in">
                                <div
                                    :key="activeItem.id"
                                    class="absolute inset-0"
                                >
                                    <div
                                        v-if="activeItem.template === 'custom'"
                                        class="h-full w-full overflow-hidden bg-zinc-950 text-white"
                                    >
                                        <iframe
                                            :srcdoc="
                                                renderCustomTemplate(activeItem)
                                            "
                                            sandbox="allow-same-origin"
                                            class="h-full w-full border-0"
                                            title="Custom content"
                                        />
                                    </div>
                                    <div
                                        v-else-if="
                                            activeItem.template ===
                                            'flight-board'
                                        "
                                        class="flex h-full w-full flex-col bg-slate-950 px-[5%] py-[4%] text-white"
                                    >
                                        <div
                                            class="border-b border-cyan-400/30 pb-4"
                                        >
                                            <div
                                                class="text-xs tracking-[0.25em] text-cyan-300 uppercase"
                                            >
                                                Flight information
                                            </div>
                                            <div
                                                class="mt-2 text-4xl font-semibold"
                                            >
                                                {{ templateTitle(activeItem) }}
                                            </div>
                                        </div>
                                        <div
                                            class="mt-4 grid grid-cols-[1.1fr_1.5fr_0.8fr_0.7fr_1fr] gap-2 px-3 pb-2 text-xs text-slate-400 uppercase"
                                        >
                                            <span>Flight</span
                                            ><span>Destination</span
                                            ><span>Time</span><span>Gate</span
                                            ><span>Status</span>
                                        </div>
                                        <div
                                            class="space-y-1 overflow-hidden text-sm"
                                        >
                                            <div
                                                v-for="(
                                                    row, rowIndex
                                                ) in objectRows(activeItem)"
                                                :key="rowIndex"
                                                class="grid grid-cols-[1.1fr_1.5fr_0.8fr_0.7fr_1fr] gap-2 rounded bg-white/[0.06] px-3 py-3"
                                            >
                                                <span
                                                    class="font-semibold text-cyan-200"
                                                    >{{
                                                        row.flight ??
                                                        row.number ??
                                                        '-'
                                                    }}</span
                                                ><span>{{
                                                    row.destination ?? '-'
                                                }}</span
                                                ><span>{{
                                                    row.time ?? '-'
                                                }}</span
                                                ><span>{{
                                                    row.gate ?? '-'
                                                }}</span
                                                ><span
                                                    class="text-emerald-300"
                                                    >{{
                                                        row.status ?? '-'
                                                    }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        v-else-if="
                                            activeItem.template === 'data-table'
                                        "
                                        class="flex h-full w-full flex-col bg-zinc-950 px-[5%] py-[5%] text-white"
                                    >
                                        <div
                                            class="border-b border-white/15 pb-4"
                                        >
                                            <div
                                                class="text-xs tracking-[0.25em] text-cyan-300 uppercase"
                                            >
                                                Live data
                                            </div>
                                            <div
                                                class="mt-2 text-4xl font-semibold"
                                            >
                                                {{ templateTitle(activeItem) }}
                                            </div>
                                        </div>
                                        <div
                                            class="mt-5 overflow-hidden rounded border border-white/15"
                                        >
                                            <div
                                                class="grid gap-2 bg-white/10 px-3 py-3 text-sm font-semibold"
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
                                                v-for="(
                                                    row, rowIndex
                                                ) in tableValues(activeItem)"
                                                :key="rowIndex"
                                                class="grid gap-2 border-t border-white/10 px-3 py-3 text-sm"
                                                :style="{
                                                    gridTemplateColumns: `repeat(${Math.max(tableColumns(activeItem).length, 1)}, minmax(0, 1fr))`,
                                                }"
                                            >
                                                <span
                                                    v-for="(
                                                        value, valueIndex
                                                    ) in row"
                                                    :key="valueIndex"
                                                    >{{ value }}</span
                                                >
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        v-else-if="
                                            activeItem.template === 'menu'
                                        "
                                        class="flex h-full w-full flex-col bg-stone-950 px-[6%] py-[5%] text-white"
                                    >
                                        <div class="text-center">
                                            <div
                                                class="text-xs tracking-[0.3em] text-amber-300 uppercase"
                                            >
                                                Menu
                                            </div>
                                            <div
                                                class="mt-2 text-4xl font-semibold"
                                            >
                                                {{ templateTitle(activeItem) }}
                                            </div>
                                        </div>
                                        <div
                                            class="mt-5 grid flex-1 grid-cols-2 gap-3 overflow-hidden"
                                        >
                                            <div
                                                v-for="(
                                                    menuItem, menuIndex
                                                ) in menuItems(activeItem)"
                                                :key="menuIndex"
                                                class="overflow-hidden rounded border border-white/10 bg-white/[0.08]"
                                            >
                                                <img
                                                    v-if="menuItem.image"
                                                    :src="
                                                        String(menuItem.image)
                                                    "
                                                    :alt="
                                                        String(
                                                            menuItem.name ?? '',
                                                        )
                                                    "
                                                    class="h-20 w-full object-cover"
                                                />
                                                <div class="p-3">
                                                    <div
                                                        class="flex justify-between gap-2 text-sm font-semibold"
                                                    >
                                                        <span>{{
                                                            menuItem.name ?? '-'
                                                        }}</span
                                                        ><span
                                                            class="text-amber-300"
                                                            >{{
                                                                menuItem.price ??
                                                                ''
                                                            }}</span
                                                        >
                                                    </div>
                                                    <div
                                                        class="mt-1 text-xs text-stone-300"
                                                    >
                                                        {{
                                                            menuItem.description ??
                                                            ''
                                                        }}
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div
                                        v-else-if="
                                            (activeItem.template ?? 'media') !==
                                            'media'
                                        "
                                        class="flex h-full w-full flex-col justify-center bg-zinc-950 px-[8%] py-[6%] text-white"
                                    >
                                        <div
                                            class="text-5xl font-bold tracking-tight"
                                        >
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
                                                activeItem.template ===
                                                'announcement'
                                            "
                                            class="mt-10 max-w-5xl text-4xl leading-tight whitespace-pre-line"
                                        >
                                            {{ activeItem.content?.body }}
                                        </div>
                                        <div
                                            v-else
                                            class="mt-10 grid gap-3 text-2xl"
                                        >
                                            <div
                                                v-for="(
                                                    row, rowIndex
                                                ) in templateRows(activeItem)"
                                                :key="rowIndex"
                                                class="rounded-lg border border-white/15 bg-white/10 px-5 py-4"
                                            >
                                                {{ row }}
                                            </div>
                                        </div>
                                    </div>
                                    <img
                                        v-else-if="
                                            (activeItem.media?.type ===
                                                'image' ||
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
                                            (activeItem.media?.type ===
                                                'video' ||
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
                                        @ended="showNextItem"
                                        @error="onMediaError"
                                    />
                                    <audio
                                        v-else-if="
                                            activeItem.media?.type ===
                                                'audio' && activeItem.media.url
                                        "
                                        :src="activeItem.media.url"
                                        class="absolute bottom-6 left-1/2 w-3/4 -translate-x-1/2"
                                        controls
                                        autoplay
                                    />

                                    <iframe
                                        v-else-if="activeItem.media?.url"
                                        :src="activeItem.media.url"
                                        :title="activeItem.media.name"
                                        class="h-full w-full border-0"
                                    />
                                </div>
                            </Transition>

                            <OverlayLayer
                                :overlays="overlayEntries"
                                :now="currentTime"
                                :brand-label="brandLabel"
                                :screen="screen"
                                :item="overlayItem"
                                :schedule="manifest?.schedule ?? null"
                            />

                            <div
                                v-if="mediaError"
                                class="absolute inset-0 grid place-items-center bg-zinc-950"
                            >
                                <div class="max-w-lg px-8 text-center">
                                    <div class="text-xl font-semibold">
                                        Media unavailable
                                    </div>
                                    <div class="mt-3 text-sm text-zinc-400">
                                        {{ mediaError }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="pointer-events-none absolute top-0 left-0 h-1 w-full"
                                :style="{ backgroundColor: brandColor }"
                            />

                            <img
                                v-if="manifest?.branding.logo_url"
                                :src="manifest.branding.logo_url"
                                alt=""
                                class="pointer-events-none absolute z-40 h-auto object-contain"
                                :class="logoPositionClass"
                                :style="logoStyle"
                            />

                            <div
                                v-if="shouldShowOverlay"
                                class="pointer-events-none absolute right-4 bottom-4 rounded border border-white/10 bg-black/60 px-3 py-2 text-right text-xs text-white/80 shadow-lg"
                            >
                                <div
                                    class="flex items-center justify-end gap-2"
                                >
                                    <span>{{ brandLabel }}</span>
                                </div>
                                <div class="text-white/55">
                                    {{ screen.name }} ·
                                    {{ manifest?.playlist.name }}
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border bg-background p-4 shadow-sm">
                    <h2 class="font-semibold tracking-tight">Now playing</h2>
                    <div v-if="manifest" class="mt-3 space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-muted-foreground">
                                Playlist
                            </div>
                            <div class="font-medium">
                                {{ manifest.playlist.name }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground">
                                Schedule priority
                            </div>
                            <div class="font-medium">
                                {{ manifest.schedule.priority }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground">
                                Generated
                            </div>
                            <div class="font-medium">
                                {{ manifest.generated_at }}
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-muted-foreground">
                        Tidak ada manifest aktif untuk screen ini.
                    </p>
                </div>

                <div class="rounded-lg border bg-background p-4 shadow-sm">
                    <h2 class="font-semibold tracking-tight">Playlist items</h2>
                    <ol
                        v-if="manifest?.playlist.items.length"
                        class="mt-3 divide-y text-sm"
                    >
                        <li
                            v-for="(item, index) in manifest.playlist.items"
                            :key="item.id"
                            class="flex gap-3 py-3"
                        >
                            <div
                                class="grid size-8 shrink-0 place-items-center rounded-md text-xs font-semibold"
                                :class="
                                    index === activeIndex
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{ item.sort_order }}
                            </div>
                            <div class="min-w-0">
                                <div class="truncate font-medium">
                                    {{
                                        item.media?.name ??
                                        item.template ??
                                        'content'
                                    }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{
                                        item.template &&
                                        item.template !== 'media'
                                            ? item.template
                                            : item.media?.type
                                    }}
                                    · {{ item.duration_seconds }}s
                                </div>
                            </div>
                        </li>
                    </ol>
                    <p v-else class="mt-3 text-sm text-muted-foreground">
                        Belum ada item playlist.
                    </p>
                </div>
            </aside>
        </section>
    </div>
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
</style>
