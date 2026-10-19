<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowLeft,
    ArrowUp,
    CalendarDays,
    ChevronLeft,
    ChevronRight,
    Clock3,
    CloudSun,
    Database,
    FlaskConical,
    Gauge,
    Image as ImageIcon,
    LayoutGrid,
    MapPin,
    MonitorPlay,
    Newspaper,
    Play,
    Plus,
    Rss,
    Save,
    SkipForward,
    Sparkles,
    Store,
    Timer,
    Trash2,
    Type,
} from '@lucide/vue';
import type { Component } from 'vue';
import { computed, ref, watch } from 'vue';
import OverlayCard from '@/components/playlists/OverlayCard.vue';
import PlaylistPreview from '@/components/playlists/PlaylistPreview.vue';
import SlideThumbnail from '@/components/playlists/SlideThumbnail.vue';
import SlideshowPlayer from '@/components/playlists/SlideshowPlayer.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { isImageMedia, isVideoMedia } from '@/lib/media';
import {
    createOverlay,
    MAX_OVERLAYS,
    OVERLAY_LABELS,
    OVERLAY_TYPES,
    type MediaAssetOption,
    type OverlayEntry,
    type OverlayType,
} from '@/lib/overlays';

type MediaAsset = {
    id: string;
    name: string;
    type: string;
    url: string;
    duration_seconds: number | null;
};

type ContentTemplate = {
    id: string;
    name: string;
    slug: string;
    html: string;
    css: string | null;
    schema: Array<{
        name: string;
        label?: string;
        type?: string;
        default?: any;
    }>;
    is_active: boolean;
};
type ApiConnection = { id: string; name: string; provider: string };

type Item = {
    id?: string;
    media_asset_id: string | null;
    template?: string;
    template_id?: string | null;
    api_connection_id?: string | null;
    template_definition?: ContentTemplate | null;
    content?: Record<string, any>;
    data_source?: {
        mode: 'static' | 'api';
        url?: string;
        refresh_seconds?: number;
        timeout_seconds?: number;
        auth_type?: 'none' | 'bearer' | 'api_key';
        auth_value?: string;
        mapping?: Record<string, string>;
        mapping_json?: string;
    };
    duration_seconds: number;
    transition: string | null;
    config: {
        fit: 'cover' | 'contain' | 'fill';
        overlays?: any[];
        audio_asset_id?: string | null;
        audio_loop?: boolean;
        split_screen?: {
            left_media_asset_id: string | null;
            right_media_asset_id: string | null;
            right_source: 'media' | 'youtube' | 'url';
            right_url: string;
            left_width: number;
            right_width: number;
        };
    };
};

type Playlist = {
    id: string;
    name: string;
    description: string | null;
    is_active: boolean;
    status: 'draft' | 'published';
    overlays: OverlayEntry[];
    items: Item[];
};

const props = defineProps<{
    mode: 'create' | 'edit';
    tenant: { id: string; name: string };
    mediaAssets: MediaAsset[];
    templates: ContentTemplate[];
    apiConnections: ApiConnection[];
    playlist?: Playlist;
    freeItems?: number;
    itemCost?: number;
    balance?: number;
}>();

const overlayIcons: Record<string, Component> = {
    ticker: Rss,
    clock: Clock3,
    weather: CloudSun,
    date: CalendarDays,
    location: MapPin,
    greeting: Sparkles,
    next: SkipForward,
    badge: ImageIcon,
    countdown: Timer,
    hours: Store,
    title: Type,
    progress: Gauge,
    api: Database,
    rss: Newspaper,
};

function normalizedOverlay(overlay: OverlayEntry): OverlayEntry {
    const defaults = createOverlay(
        overlay.type as OverlayType,
        props.apiConnections,
        props.mediaAssets,
    );

    return {
        ...overlay,
        config: {
            ...defaults.config,
            ...overlay.config,
            schedule: overlay.config?.schedule ?? {},
            offset_x: overlay.config?.offset_x ?? 0,
            offset_y: overlay.config?.offset_y ?? 0,
        },
    };
}

const form = useForm({
    name: props.playlist?.name ?? '',
    description: props.playlist?.description ?? '',
    is_active: props.playlist?.is_active ?? true,
    status: props.playlist?.status ?? ('draft' as const),
    overlays: (props.playlist?.overlays ?? []).map(normalizedOverlay),
    items: (props.playlist?.items ?? []).map((item) => ({
        ...item,
        template: item.template ?? 'media',
        api_connection_id: item.api_connection_id ?? null,
        content: item.content ?? {},
        data_source: {
            mode: item.data_source?.mode ?? 'static',
            url: item.data_source?.url ?? '',
            refresh_seconds: item.data_source?.refresh_seconds ?? 60,
            timeout_seconds: item.data_source?.timeout_seconds ?? 5,
            auth_type: item.data_source?.auth_type ?? 'none',
            auth_value: '',
            mapping: item.data_source?.mapping ?? {},
            mapping_json: JSON.stringify(
                item.data_source?.mapping ?? {},
                null,
                2,
            ),
        },
        config: {
            fit: item.config?.fit ?? 'cover',
            overlays: item.config?.overlays ?? [],
            audio_asset_id: item.config?.audio_asset_id ?? null,
            audio_loop: item.config?.audio_loop ?? false,
            split_screen: item.config?.split_screen ?? {
                left_media_asset_id: null,
                right_media_asset_id: null,
                right_source: 'media',
                right_url: '',
                left_width: 33,
                right_width: 67,
            },
        },
    })),
});

const draggedIndex = ref<number | null>(null);
const dropIndex = ref<number | null>(null);
const apiTestMessage = ref<string | null>(null);
const apiTestData = ref<Record<string, unknown> | null>(null);
const isEditing = computed(() => props.mode === 'edit');
const formItems = computed(
    () =>
        (
            form as unknown as {
                items: Array<{ duration_seconds?: number }>;
            }
        ).items ?? [],
);
const formOverlays = computed(
    () =>
        (
            form as unknown as {
                overlays: Array<{ is_active?: boolean }>;
            }
        ).overlays ?? [],
);
const totalDuration = computed(() =>
    formItems.value.reduce(
        (total: number, item: { duration_seconds?: number }) =>
            total + (item.duration_seconds ?? 0),
        0,
    ),
);
const activeOverlayCount = computed(
    () =>
        formOverlays.value.filter(
            (overlay: { is_active?: boolean }) => overlay.is_active,
        ).length,
);

const page = usePage();
const billingError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.[
            'billing'
        ] ?? null,
);

const freeItemLimit = computed(() => props.freeItems ?? 3);
const extraItemCost = computed(() => props.itemCost ?? 0);
const previouslySavedItems = computed(() => props.playlist?.items.length ?? 0);
const estimatedItemCharge = computed(() => {
    if (extraItemCost.value <= 0) {
        return 0;
    }

    const free = freeItemLimit.value;
    const billableBefore = Math.max(previouslySavedItems.value, free);
    const billableNow = Math.max(formItems.value.length, free);

    return Math.max(0, billableNow - billableBefore) * extraItemCost.value;
});

const selectedIndex = ref(0);
const inspectorTab = ref<'item' | 'overlay' | 'playlist'>('item');
const viewMode = ref<'editor' | 'grid'>('editor');
const slideshowOpen = ref(false);

const selectedItem = computed<Item | null>(
    () => form.items[selectedIndex.value] ?? null,
);

const nextSlideTitle = computed(() => {
    const items = form.items;

    if (items.length === 0) {
        return null;
    }

    const next = items[(selectedIndex.value + 1) % items.length];

    return itemTitle(next);
});

const globalPreviewOverlays = computed<OverlayEntry[]>(() =>
    form.overlays.map((overlay): OverlayEntry => {
        const entry: OverlayEntry = {
            id: overlay.id,
            type: overlay.type,
            config: overlay.config,
        };

        if (overlay.type !== 'badge') {
            return entry;
        }

        const asset = props.mediaAssets.find(
            (candidate) => candidate.id === overlay.config.media_asset_id,
        );

        return {
            ...entry,
            config: {
                ...overlay.config,
                image_url: asset?.url ?? null,
            },
        };
    }),
);

const previewOverlays = computed<OverlayEntry[]>(() => [
    ...globalPreviewOverlays.value,
    ...(selectedItem.value?.config?.overlays ?? []).map(
        (overlay, index): OverlayEntry => ({
            id: `item-${selectedIndex.value}-${index}`,
            type: 'ticker',
            config: overlay,
        }),
    ),
]);

watch(
    () => form.items.length,
    (count: number) => {
        if (count === 0) {
            selectedIndex.value = 0;
            return;
        }

        if (selectedIndex.value > count - 1) {
            selectedIndex.value = count - 1;
        }
    },
);

function itemTitle(item: Item | null | undefined): string {
    if (!item) {
        return '';
    }

    const title = item.content?.title;

    if (typeof title === 'string' && title.trim() !== '') {
        return title;
    }

    const asset = item.media_asset_id
        ? props.mediaAssets.find(
              (candidate) => candidate.id === item.media_asset_id,
          )
        : undefined;

    return asset?.name ?? templateLabel(item.template);
}

function selectItem(index: number): void {
    if (index < 0 || index >= form.items.length) {
        return;
    }

    selectedIndex.value = index;
    inspectorTab.value = 'item';
}

function formatLoopDuration(seconds: number): string {
    if (seconds <= 0) {
        return '0s';
    }

    const minutes = Math.floor(seconds / 60);
    const remainder = seconds % 60;

    if (minutes === 0) {
        return `${remainder}s`;
    }

    return remainder > 0 ? `${minutes}m ${remainder}s` : `${minutes}m`;
}

function addItem(): void {
    const asset = props.mediaAssets[0];

    form.items.push({
        media_asset_id: asset?.id ?? null,
        template: asset ? 'media' : 'announcement',
        api_connection_id: null,
        content: asset ? {} : { title: 'Announcement', subtitle: '', body: '' },
        data_source: {
            mode: 'static',
            url: '',
            refresh_seconds: 60,
            timeout_seconds: 5,
            auth_type: 'none',
            auth_value: '',
            mapping: {},
            mapping_json: '{}',
        },
        duration_seconds: asset?.duration_seconds ?? 10,
        transition: 'fade',
        config: {
            fit: 'cover',
            overlays: [],
            audio_asset_id: null,
            audio_loop: false,
            split_screen: {
                left_media_asset_id:
                    props.mediaAssets.find((asset) => isImageMedia(asset.type))
                        ?.id ?? null,
                right_media_asset_id:
                    props.mediaAssets.find((asset) => isVideoMedia(asset.type))
                        ?.id ?? null,
                right_source: 'media',
                right_url: '',
                left_width: 33,
                right_width: 67,
            },
        },
    });

    selectedIndex.value = form.items.length - 1;
    inspectorTab.value = 'item';
}

function setTemplate(item: Item, template: string): void {
    if (template.startsWith('custom:')) {
        setCustomTemplate(item, template.slice(7));
        return;
    }
    item.template = template;
    item.template_id = null;
    item.api_connection_id = null;
    item.config.split_screen ??= {
        left_media_asset_id:
            props.mediaAssets.find((asset) => isImageMedia(asset.type))?.id ??
            null,
        right_media_asset_id:
            props.mediaAssets.find((asset) => isVideoMedia(asset.type))?.id ??
            null,
        right_source: 'media',
        right_url: '',
        left_width: 33,
        right_width: 67,
    };
    if (template !== 'media') {
        item.media_asset_id = null;
    } else if (!item.media_asset_id) {
        item.media_asset_id = props.mediaAssets[0]?.id ?? null;
    }
    item.content = item.content ?? {};
    item.data_source = {
        mode: 'static',
        url: '',
        refresh_seconds: 60,
        timeout_seconds: 5,
        auth_type: 'none',
        auth_value: '',
        mapping: {},
        mapping_json: '{}',
    };
}

function setCustomTemplate(item: Item, templateId: string): void {
    const template = props.templates.find(
        (candidate) => candidate.id === templateId,
    );
    item.template = 'custom';
    item.template_id = templateId || null;
    item.media_asset_id = null;
    item.template_definition = template ?? null;
    item.content = item.content ?? {};
    item.data_source = item.data_source ?? {
        mode: 'static',
        url: '',
        refresh_seconds: 60,
        timeout_seconds: 5,
        auth_type: 'none',
        auth_value: '',
        mapping: {},
        mapping_json: '{}',
    };
    for (const field of template?.schema ?? []) {
        if (
            item.content[field.name] === undefined &&
            field.default !== undefined
        ) {
            item.content[field.name] = field.default;
        }
    }
}

function templateLabel(template?: string): string {
    return (
        {
            media: 'Standard media',
            announcement: 'Announcement',
            'data-table': 'Data table',
            'patient-queue': 'Patient queue',
            'flight-board': 'Flight board',
            menu: 'Restaurant menu',
            weather: 'Weather',
            'split-screen': 'Split screen',
        }[template ?? 'media'] ?? 'Standard media'
    );
}

function templateOption(item: Item): string {
    return item.template === 'custom' && item.template_id
        ? `custom:${item.template_id}`
        : (item.template ?? 'media');
}

function mappingPlaceholder(item: Item): string {
    return item.template === 'weather'
        ? '{"title":"current.temperature_2m","subtitle":"current.relative_humidity_2m","body":"current.temperature_2m"}'
        : '{"title":"queue.title","number":"queue.number"}';
}

function structuredContentJson(item: Item): string {
    const content = item.content ?? {};
    const value =
        item.template === 'menu'
            ? { items: content.items ?? [] }
            : item.template === 'flight-board'
              ? { rows: content.rows ?? [] }
              : { columns: content.columns ?? [], rows: content.rows ?? [] };

    return JSON.stringify(value, null, 2);
}

function structuredPlaceholder(item: Item): string {
    if (item.template === 'menu')
        return '{"items":[{"name":"Nasi Goreng","description":"Ayam dan telur","price":"35.000","image":""}]}';
    if (item.template === 'flight-board')
        return '{"rows":[{"flight":"GA-204","destination":"Bali","time":"10:30","gate":"A12","status":"Boarding"}]}';

    return '{"columns":["No","Name","Status"],"rows":[["001","Customer A","Waiting"]]}';
}

function updateStructuredContent(item: Item, event: Event): void {
    try {
        const parsed = JSON.parse((event.target as HTMLTextAreaElement).value);
        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed))
            return;
        item.content = { ...item.content, ...parsed };
    } catch {
        // Keep the previous valid content while the user is typing JSON.
    }
}

function customFieldJson(item: Item, fieldName: string): string {
    return JSON.stringify(item.content?.[fieldName] ?? [], null, 2);
}

function updateCustomJsonField(
    item: Item,
    fieldName: string,
    event: Event,
): void {
    try {
        const value = JSON.parse((event.target as HTMLTextAreaElement).value);

        item.content ??= {};
        item.content[fieldName] = value;
    } catch {
        // Keep the last valid JSON value while the user is editing.
    }
}

function isCustomImageValue(item: Item, fieldName: string): boolean {
    const value = item.content?.[fieldName];

    return (
        typeof value === 'string' &&
        value !== '' &&
        !props.mediaAssets.some((asset) => asset.url === value)
    );
}

function setImageValue(item: Item, fieldName: string, event: Event): void {
    item.content ??= {};
    item.content[fieldName] = (event.target as HTMLInputElement).value;
}

function parseMapping(value?: string): Record<string, string> {
    try {
        const parsed = JSON.parse(value || '{}');
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed)
            ? (parsed as Record<string, string>)
            : {};
    } catch {
        return {};
    }
}

function valueAtPath(payload: unknown, path: string): unknown {
    return path.split('.').reduce<unknown>((value, segment) => {
        if (!value || typeof value !== 'object' || !(segment in value)) {
            return undefined;
        }

        return (value as Record<string, unknown>)[segment];
    }, payload);
}

function applyApiDataToContent(
    item: Item,
    payload: Record<string, unknown>,
): void {
    const defaultMapping =
        item.template === 'weather'
            ? {
                  title: 'current.temperature_2m',
                  subtitle: 'timezone',
                  body: 'current.relative_humidity_2m',
                  temperature: 'current.temperature_2m',
                  humidity: 'current.relative_humidity_2m',
                  weather_code: 'current.weather_code',
                  wind_speed: 'current.wind_speed_10m',
              }
            : {};
    const mapping = parseMapping(item.data_source?.mapping_json);

    if (
        Object.keys(mapping).length === 0 &&
        Object.keys(defaultMapping).length > 0
    ) {
        item.data_source!.mapping_json = JSON.stringify(
            defaultMapping,
            null,
            2,
        );
    }

    const effectiveMapping =
        Object.keys(mapping).length > 0 ? mapping : defaultMapping;

    if (Object.keys(effectiveMapping).length === 0) {
        return;
    }

    item.content ??= {};
    Object.entries(effectiveMapping).forEach(([target, path]) => {
        const value = valueAtPath(payload, path);

        if (value !== undefined && value !== null) {
            item.content![target] = ['title', 'subtitle', 'body'].includes(
                target,
            )
                ? Array.isArray(value)
                    ? value.join('\n')
                    : String(value)
                : value;
        }
    });
}

async function testApi(item: Item): Promise<void> {
    apiTestMessage.value = 'Testing API...';
    apiTestData.value = null;
    if (!item.api_connection_id) {
        apiTestMessage.value = 'Pilih API connection terlebih dahulu.';
        return;
    }
    const response = await fetch(
        `/integrations/api/${item.api_connection_id}/test`,
        {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                Accept: 'application/json',
                'X-CSRF-TOKEN':
                    document
                        .querySelector('meta[name="csrf-token"]')
                        ?.getAttribute('content') ?? '',
            },
        },
    );
    const payload = await response.json();
    apiTestMessage.value =
        payload.message ??
        (response.ok ? 'API connected.' : 'API test failed.');
    apiTestData.value = response.ok ? payload.data : null;
    if (
        response.ok &&
        payload.data &&
        typeof payload.data === 'object' &&
        !Array.isArray(payload.data)
    ) {
        applyApiDataToContent(item, payload.data as Record<string, unknown>);
    }
}

function removeItem(index: number): void {
    form.items.splice(index, 1);

    if (form.items.length === 0) {
        selectedIndex.value = 0;
        return;
    }

    selectedIndex.value = Math.min(index, form.items.length - 1);
}

function moveItem(index: number, direction: -1 | 1): void {
    const target = index + direction;
    if (target < 0 || target >= form.items.length) return;
    const [item] = form.items.splice(index, 1);
    form.items.splice(target, 0, item);

    if (selectedIndex.value === index) {
        selectedIndex.value = target;
    } else if (selectedIndex.value === target) {
        selectedIndex.value = index;
    }
}

function startDrag(index: number, event: DragEvent): void {
    draggedIndex.value = index;
    dropIndex.value = index;
    event.dataTransfer?.setData('text/plain', String(index));
    if (event.dataTransfer) event.dataTransfer.effectAllowed = 'move';
}

function dropItem(index: number, event: DragEvent): void {
    event.preventDefault();
    if (draggedIndex.value !== null && draggedIndex.value !== index) {
        const selected = form.items[selectedIndex.value];
        const [item] = form.items.splice(draggedIndex.value, 1);
        form.items.splice(index, 0, item);

        if (selected) {
            selectedIndex.value = Math.max(0, form.items.indexOf(selected));
        }
    }
    draggedIndex.value = null;
    dropIndex.value = null;
}

function addOverlay(type: OverlayType): void {
    if (form.overlays.length >= MAX_OVERLAYS) return;

    form.overlays.push(
        createOverlay(type, props.apiConnections, props.mediaAssets),
    );
}

function duplicateOverlay(overlay: OverlayEntry): void {
    if (form.overlays.length >= MAX_OVERLAYS) return;

    const index = form.overlays.indexOf(overlay);
    const copy = JSON.parse(JSON.stringify(overlay)) as OverlayEntry;
    delete copy.id;
    form.overlays.splice(index + 1, 0, copy);
}

function moveOverlay(overlay: OverlayEntry, direction: 'up' | 'down'): void {
    const index = form.overlays.indexOf(overlay);

    if (index < 0) return;

    const target = direction === 'up' ? index - 1 : index + 1;

    if (target < 0 || target >= form.overlays.length) return;

    const [moved] = form.overlays.splice(index, 1);
    form.overlays.splice(target, 0, moved);
}

function removeOverlay(overlay: OverlayEntry): void {
    const index = form.overlays.indexOf(overlay);
    if (index >= 0) form.overlays.splice(index, 1);
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        items: data.items.map((item) => ({
            ...item,
            data_source: item.data_source
                ? {
                      ...item.data_source,
                      mapping: parseMapping(item.data_source.mapping_json),
                  }
                : undefined,
        })),
    }));
    const options = { preserveScroll: true };
    if (props.mode === 'edit' && props.playlist) {
        form.put(`/playlists/${props.playlist.id}`, options);
    } else {
        form.post('/playlists', options);
    }
}
</script>

<template>
    <Head :title="isEditing ? `Edit ${form.name}` : 'New playlist'" />

    <div
        class="flex min-h-full min-w-0 flex-1 flex-col gap-4 overflow-x-clip bg-muted/20 p-3 pb-28 sm:p-4 md:gap-6 md:p-6 md:pb-24"
    >
        <div
            v-if="billingError"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
        >
            <span class="min-w-0 flex-1">{{ billingError }}</span>
            <Link
                href="/workspace/billing"
                class="shrink-0 font-semibold underline underline-offset-4"
            >
                Topup token
            </Link>
        </div>

        <section
            class="z-40 flex items-end justify-between gap-3 rounded-lg border bg-background/95 p-4 shadow-sm backdrop-blur supports-[backdrop-filter]:bg-background/80 md:sticky md:top-0 md:gap-4 md:p-5 md:shadow-lg"
        >
            <div class="min-w-0">
                <Link
                    href="/playlists"
                    class="text-sm text-muted-foreground hover:text-foreground"
                >
                    Playlists
                </Link>
                <h1
                    class="mt-1 truncate text-xl font-semibold tracking-tight md:mt-2 md:text-2xl"
                >
                    {{ isEditing ? 'Playlist editor' : 'New playlist' }}
                </h1>
                <p class="mt-1 hidden text-sm text-muted-foreground sm:block">
                    Kelola slide seperti deck presentasi: pilih slide, atur
                    properti di inspector, dan lihat preview langsung.
                </p>
                <div
                    class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px] text-muted-foreground"
                >
                    <span
                        class="rounded-full bg-muted px-2 py-0.5 font-medium text-foreground"
                    >
                        {{ formItems.length }} slide
                    </span>
                    <span
                        class="rounded-full bg-muted px-2 py-0.5 font-medium text-foreground"
                    >
                        {{ formatLoopDuration(totalDuration) }}
                    </span>
                    <span
                        v-if="activeOverlayCount"
                        class="rounded-full bg-muted px-2 py-0.5 font-medium text-foreground"
                    >
                        {{ activeOverlayCount }} overlay
                    </span>
                    <span
                        v-if="estimatedItemCharge > 0"
                        class="rounded-full bg-amber-100 px-2 py-0.5 font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                    >
                        +{{ estimatedItemCharge }} token
                    </span>
                </div>
            </div>
            <div class="hidden shrink-0 gap-2 md:flex">
                <Link
                    href="/playlists"
                    class="inline-flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm font-medium hover:bg-accent"
                >
                    <ArrowLeft class="size-4" />
                    {{ isEditing ? 'Back' : 'Cancel' }}
                </Link>
                <Button
                    type="button"
                    variant="outline"
                    class="gap-2"
                    :title="viewMode === 'grid' ? 'Editor view' : 'Grid view'"
                    @click="viewMode = viewMode === 'grid' ? 'editor' : 'grid'"
                >
                    <LayoutGrid v-if="viewMode === 'editor'" class="size-4" />
                    <MonitorPlay v-else class="size-4" />
                    {{ viewMode === 'grid' ? 'Editor view' : 'Grid view' }}
                </Button>
                <Button
                    type="button"
                    class="gap-2"
                    :disabled="form.items.length === 0"
                    title="Play slideshow"
                    @click="slideshowOpen = true"
                >
                    <Play class="size-4" />
                    Play
                </Button>
                <SubmitButton
                    type="button"
                    :processing="form.processing"
                    :label="isEditing ? 'Save changes' : 'Create playlist'"
                    loading-label="Menyimpan..."
                    @click="submit"
                >
                    <Save class="size-4" />
                </SubmitButton>
            </div>
        </section>

        <form
            class="grid min-w-0 items-start gap-4 lg:gap-5"
            :class="
                viewMode === 'grid'
                    ? 'xl:grid-cols-1'
                    : 'xl:grid-cols-[13.5rem_minmax(0,1fr)_23rem]'
            "
            @submit.prevent="submit"
        >
            <!-- Grid view -->
            <template v-if="viewMode === 'grid'">
                <section
                    class="rounded-xl border bg-background p-3 shadow-sm sm:p-4"
                >
                    <div
                        class="mb-3 flex flex-wrap items-center justify-between gap-2"
                    >
                        <div class="flex items-center gap-2">
                            <LayoutGrid class="size-4 text-muted-foreground" />
                            <h2 class="text-sm font-semibold">Semua slide</h2>
                            <span class="text-xs text-muted-foreground"
                                >{{ form.items.length }} slide ·
                                {{ formatLoopDuration(totalDuration) }}</span
                            >
                        </div>
                        <div class="flex items-center gap-2">
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                class="gap-1.5"
                                @click="viewMode = 'editor'"
                            >
                                <MonitorPlay class="size-4" /> Editor view
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                class="gap-1.5"
                                :disabled="form.items.length === 0"
                                @click="slideshowOpen = true"
                            >
                                <Play class="size-4" /> Play
                            </Button>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                class="gap-1.5"
                                @click="addItem"
                            >
                                <Plus class="size-4" /> Add slide
                            </Button>
                        </div>
                    </div>

                    <div
                        v-if="form.items.length === 0"
                        class="rounded-lg border border-dashed p-10 text-center text-sm text-muted-foreground"
                    >
                        Belum ada slide. Klik Add slide untuk memulai.
                    </div>
                    <div
                        v-else
                        class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4 2xl:grid-cols-6"
                        data-slide-grid
                    >
                        <div
                            v-for="(item, index) in form.items"
                            :key="item.id ?? index"
                            draggable="true"
                            role="button"
                            tabindex="0"
                            class="group relative overflow-hidden rounded-lg border bg-background text-left transition outline-none focus-visible:ring-2 focus-visible:ring-primary/40"
                            :class="
                                selectedIndex === index
                                    ? 'border-primary ring-2 ring-primary/25'
                                    : 'border-border hover:border-primary/40'
                            "
                            @click="
                                selectItem(index);
                                viewMode = 'editor';
                            "
                            @keydown.enter="
                                selectItem(index);
                                viewMode = 'editor';
                            "
                            @dragstart="startDrag(index, $event)"
                            @dragend="
                                draggedIndex = null;
                                dropIndex = null;
                            "
                            @dragover.prevent="dropIndex = index"
                            @drop="dropItem(index, $event)"
                        >
                            <div
                                class="relative aspect-video w-full overflow-hidden bg-zinc-950"
                            >
                                <SlideThumbnail
                                    :item="item"
                                    :media-assets="mediaAssets"
                                />
                                <span
                                    class="absolute top-1 left-1 grid size-5 place-items-center rounded bg-black/70 text-[10px] font-semibold text-white"
                                    >{{ index + 1 }}</span
                                >
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    class="absolute top-1 right-1 size-6 bg-black/60 text-white opacity-100 transition sm:opacity-0 sm:group-hover:opacity-100"
                                    title="Remove item"
                                    @click.stop="removeItem(index)"
                                    ><Trash2 class="size-3.5"
                                /></Button>
                            </div>
                            <div class="px-2 py-1.5">
                                <div class="truncate text-xs font-medium">
                                    {{
                                        itemTitle(item) || `Slide ${index + 1}`
                                    }}
                                </div>
                                <div
                                    class="mt-0.5 flex items-center justify-between gap-2 text-[10px] text-muted-foreground"
                                >
                                    <span class="truncate">{{
                                        templateLabel(item.template)
                                    }}</span>
                                    <span class="tabular-nums"
                                        >{{ item.duration_seconds }}s</span
                                    >
                                </div>
                            </div>
                            <div
                                v-if="
                                    dropIndex === index &&
                                    draggedIndex !== index
                                "
                                class="pointer-events-none absolute inset-0 rounded-lg border-2 border-primary"
                            />
                        </div>
                        <button
                            type="button"
                            class="grid aspect-video place-items-center gap-1 rounded-lg border border-dashed text-xs font-medium text-muted-foreground transition hover:border-primary/40 hover:text-foreground"
                            @click="addItem"
                        >
                            <Plus class="size-5" />
                            Add slide
                        </button>
                    </div>
                </section>
            </template>

            <template v-else>
                <!-- Slide rail -->
                <aside class="min-w-0">
                    <section
                        class="rounded-xl border bg-background p-2 shadow-sm xl:sticky xl:top-24"
                    >
                        <div
                            class="flex items-center justify-between px-2 pt-1 pb-2"
                        >
                            <h2
                                class="text-[11px] font-semibold tracking-wide text-muted-foreground uppercase"
                            >
                                Slides
                            </h2>
                            <div class="flex items-center gap-1">
                                <span
                                    class="text-[11px] text-muted-foreground tabular-nums"
                                    >{{ form.items.length }}</span
                                >
                                <button
                                    type="button"
                                    class="rounded p-0.5 text-muted-foreground transition hover:text-foreground"
                                    title="Grid view"
                                    @click="viewMode = 'grid'"
                                >
                                    <LayoutGrid class="size-3.5" />
                                </button>
                            </div>
                        </div>
                        <div
                            class="flex gap-2 overflow-x-auto pb-1 xl:flex-col xl:overflow-visible"
                        >
                            <button
                                v-for="(item, index) in form.items"
                                :key="item.id ?? index"
                                type="button"
                                draggable="true"
                                class="group relative w-40 shrink-0 overflow-hidden rounded-lg border bg-background text-left transition xl:w-full"
                                :class="
                                    selectedIndex === index
                                        ? 'border-primary ring-2 ring-primary/25'
                                        : 'border-border hover:border-primary/40'
                                "
                                @click="selectItem(index)"
                                @dragstart="startDrag(index, $event)"
                                @dragend="
                                    draggedIndex = null;
                                    dropIndex = null;
                                "
                                @dragover.prevent="dropIndex = index"
                                @drop="dropItem(index, $event)"
                            >
                                <div
                                    class="relative aspect-video w-full overflow-hidden bg-zinc-950"
                                >
                                    <SlideThumbnail
                                        :item="item"
                                        :media-assets="mediaAssets"
                                    />
                                    <span
                                        class="absolute top-1 left-1 grid size-5 place-items-center rounded bg-black/70 text-[10px] font-semibold text-white"
                                        >{{ index + 1 }}</span
                                    >
                                    <span
                                        v-if="selectedIndex === index"
                                        class="absolute right-1 bottom-1 rounded bg-primary px-1.5 py-0.5 text-[9px] font-semibold text-primary-foreground"
                                        >SLIDE</span
                                    >
                                </div>
                                <div class="px-2 py-1.5">
                                    <div class="truncate text-xs font-medium">
                                        {{
                                            itemTitle(item) ||
                                            `Slide ${index + 1}`
                                        }}
                                    </div>
                                    <div
                                        class="mt-0.5 flex items-center justify-between gap-2 text-[10px] text-muted-foreground"
                                    >
                                        <span class="truncate">{{
                                            templateLabel(item.template)
                                        }}</span>
                                        <span class="tabular-nums"
                                            >{{ item.duration_seconds }}s</span
                                        >
                                    </div>
                                </div>
                                <div
                                    v-if="
                                        dropIndex === index &&
                                        draggedIndex !== index
                                    "
                                    class="pointer-events-none absolute inset-0 rounded-lg border-2 border-primary"
                                />
                            </button>
                        </div>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="mt-2 w-full"
                            @click="addItem"
                            ><Plus class="size-4" /> Add slide</Button
                        >
                        <p
                            class="mt-2 px-1 text-[10px] leading-snug text-muted-foreground"
                        >
                            Drag untuk mengurutkan. Klik slide untuk mengedit
                            propertinya.
                        </p>
                    </section>
                </aside>

                <!-- Canvas -->
                <section class="min-w-0 space-y-3">
                    <div
                        class="rounded-xl border bg-background p-3 shadow-sm sm:p-4"
                    >
                        <div
                            class="mb-3 flex flex-wrap items-center justify-between gap-2"
                        >
                            <div
                                class="flex items-center gap-2 text-xs font-medium text-muted-foreground"
                            >
                                <MonitorPlay class="size-4" />
                                Preview kanvas
                            </div>
                            <div class="flex items-center gap-1.5">
                                <Button
                                    type="button"
                                    size="icon"
                                    class="size-8"
                                    :disabled="form.items.length === 0"
                                    title="Play slideshow"
                                    @click="slideshowOpen = true"
                                    ><Play class="size-4"
                                /></Button>
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="outline"
                                    class="size-8"
                                    :disabled="
                                        selectedIndex <= 0 ||
                                        form.items.length === 0
                                    "
                                    title="Slide sebelumnya"
                                    @click="selectItem(selectedIndex - 1)"
                                    ><ChevronLeft class="size-4"
                                /></Button>
                                <span
                                    class="min-w-16 text-center text-xs text-muted-foreground tabular-nums"
                                    >{{
                                        form.items.length
                                            ? selectedIndex + 1
                                            : 0
                                    }}
                                    / {{ form.items.length }}</span
                                >
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="outline"
                                    class="size-8"
                                    :disabled="
                                        form.items.length === 0 ||
                                        selectedIndex >= form.items.length - 1
                                    "
                                    title="Slide berikutnya"
                                    @click="selectItem(selectedIndex + 1)"
                                    ><ChevronRight class="size-4"
                                /></Button>
                            </div>
                        </div>
                        <PlaylistPreview
                            :item="selectedItem"
                            :media-assets="mediaAssets"
                            :overlays="previewOverlays"
                            :brand-label="tenant.name"
                            :title="selectedItem ? itemTitle(selectedItem) : ''"
                            :next-title="nextSlideTitle"
                            :index="selectedIndex"
                            :total="form.items.length"
                        />
                        <div
                            class="mt-3 flex flex-wrap items-center justify-between gap-2 text-[11px] text-muted-foreground"
                        >
                            <span class="truncate">{{
                                selectedItem
                                    ? templateLabel(selectedItem.template)
                                    : 'Belum ada slide'
                            }}</span>
                            <span v-if="selectedItem" class="tabular-nums"
                                >{{ selectedItem.duration_seconds }} detik ·
                                {{ selectedItem.transition ?? 'none' }} · fit
                                {{ selectedItem.config.fit }}</span
                            >
                        </div>
                        <p
                            v-if="
                                form.overlays.some((overlay) =>
                                    ['weather', 'api', 'rss'].includes(
                                        overlay.type,
                                    ),
                                )
                            "
                            class="mt-1.5 text-[10px] text-muted-foreground/80"
                        >
                            Cuaca dan data API tampil setelah nilainya
                            ter-resolve saat playlist diputar.
                        </p>
                    </div>

                    <div
                        v-if="
                            estimatedItemCharge > 0 ||
                            (extraItemCost > 0 &&
                                form.items.length > freeItemLimit)
                        "
                        class="rounded-xl border bg-background px-3 py-2.5 text-[11px] text-muted-foreground"
                    >
                        {{ freeItemLimit }} item pertama gratis · item
                        berikutnya {{ extraItemCost }} token/item.
                        <span
                            v-if="estimatedItemCharge > 0"
                            class="font-semibold text-amber-600 dark:text-amber-400"
                        >
                            Estimasi biaya simpan:
                            {{ estimatedItemCharge }} token.
                        </span>
                        <span
                            v-if="(balance ?? 0) < estimatedItemCharge"
                            class="font-semibold text-red-600 dark:text-red-400"
                        >
                            Saldo token tidak cukup.
                        </span>
                    </div>

                    <div
                        v-if="form.items.length === 0"
                        class="rounded-xl border border-dashed bg-background p-8 text-center text-sm text-muted-foreground"
                    >
                        <p class="font-medium text-foreground">
                            Playlist masih kosong
                        </p>
                        <p class="mt-1">
                            Tambahkan slide pertama dari panel kiri, lalu atur
                            template dan propertinya di inspector.
                        </p>
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            class="mt-3"
                            @click="addItem"
                            ><Plus class="size-4" /> Add slide</Button
                        >
                    </div>
                </section>

                <!-- Inspector -->
                <aside class="min-w-0">
                    <section
                        class="rounded-xl border bg-background shadow-sm xl:sticky xl:top-24"
                    >
                        <div class="flex gap-1 border-b p-1.5">
                            <button
                                type="button"
                                class="flex-1 rounded-md px-2 py-1.5 text-xs font-medium transition"
                                :class="
                                    inspectorTab === 'item'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted'
                                "
                                @click="inspectorTab = 'item'"
                            >
                                Slide
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-md px-2 py-1.5 text-xs font-medium transition"
                                :class="
                                    inspectorTab === 'overlay'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted'
                                "
                                @click="inspectorTab = 'overlay'"
                            >
                                Overlay
                            </button>
                            <button
                                type="button"
                                class="flex-1 rounded-md px-2 py-1.5 text-xs font-medium transition"
                                :class="
                                    inspectorTab === 'playlist'
                                        ? 'bg-primary text-primary-foreground'
                                        : 'text-muted-foreground hover:bg-muted'
                                "
                                @click="inspectorTab = 'playlist'"
                            >
                                Playlist
                            </button>
                        </div>
                        <div
                            class="max-h-[calc(100vh-15rem)] overflow-y-auto p-3 sm:p-4"
                        >
                            <!-- Slide inspector -->
                            <template v-if="inspectorTab === 'item'">
                                <template v-if="selectedItem">
                                    <div
                                        class="flex items-start justify-between gap-2"
                                    >
                                        <div class="min-w-0">
                                            <p
                                                class="text-[10px] font-semibold tracking-[0.15em] text-muted-foreground uppercase"
                                            >
                                                Slide {{ selectedIndex + 1 }}
                                            </p>
                                            <p
                                                class="truncate text-sm font-semibold"
                                            >
                                                {{
                                                    itemTitle(selectedItem) ||
                                                    'Tanpa judul'
                                                }}
                                            </p>
                                        </div>
                                        <div class="flex shrink-0 gap-0.5">
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                class="size-8"
                                                :disabled="selectedIndex === 0"
                                                title="Move up"
                                                @click="
                                                    moveItem(selectedIndex, -1)
                                                "
                                                ><ArrowUp class="size-4"
                                            /></Button>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                class="size-8"
                                                :disabled="
                                                    selectedIndex ===
                                                    form.items.length - 1
                                                "
                                                title="Move down"
                                                @click="
                                                    moveItem(selectedIndex, 1)
                                                "
                                                ><ArrowDown class="size-4"
                                            /></Button>
                                            <Button
                                                type="button"
                                                size="icon"
                                                variant="ghost"
                                                class="size-8"
                                                title="Remove item"
                                                @click="
                                                    removeItem(selectedIndex)
                                                "
                                                ><Trash2 class="size-4"
                                            /></Button>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <label
                                            class="mb-1 block text-xs font-medium"
                                            >Template / media</label
                                        >
                                        <select
                                            :value="
                                                templateOption(selectedItem)
                                            "
                                            class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                            @change="
                                                setTemplate(
                                                    selectedItem,
                                                    (
                                                        $event.target as HTMLSelectElement
                                                    ).value,
                                                )
                                            "
                                        >
                                            <option value="media">
                                                Standard media
                                            </option>
                                            <option value="announcement">
                                                Announcement
                                            </option>
                                            <option value="data-table">
                                                Data table
                                            </option>
                                            <option value="patient-queue">
                                                Patient queue
                                            </option>
                                            <option value="flight-board">
                                                Flight board
                                            </option>
                                            <option value="menu">
                                                Restaurant menu
                                            </option>
                                            <option value="weather">
                                                Weather
                                            </option>
                                            <option value="split-screen">
                                                Split screen
                                            </option>
                                            <option
                                                v-for="template in templates"
                                                :key="template.id"
                                                :value="`custom:${template.id}`"
                                            >
                                                {{ template.name }}
                                            </option>
                                        </select>
                                        <select
                                            v-if="
                                                (selectedItem.template ??
                                                    'media') === 'media'
                                            "
                                            v-model="
                                                selectedItem.media_asset_id
                                            "
                                            class="mt-2 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                        >
                                            <option
                                                v-for="asset in mediaAssets"
                                                :key="asset.id"
                                                :value="asset.id"
                                            >
                                                {{ asset.name }} ·
                                                {{ asset.type }}
                                            </option>
                                        </select>
                                    </div>

                                    <div class="mt-3 grid grid-cols-2 gap-2">
                                        <select
                                            v-model="
                                                selectedItem.config
                                                    .audio_asset_id
                                            "
                                            class="h-9 w-full rounded-md border bg-background px-2 text-sm"
                                        >
                                            <option value="">
                                                Tanpa audio
                                            </option>
                                            <option
                                                v-for="asset in mediaAssets.filter(
                                                    (candidate) =>
                                                        candidate.type ===
                                                        'audio',
                                                )"
                                                :key="asset.id"
                                                :value="asset.id"
                                            >
                                                {{ asset.name }}
                                            </option>
                                        </select>
                                        <label
                                            class="flex h-9 items-center gap-2 text-xs whitespace-nowrap"
                                            ><input
                                                v-model="
                                                    selectedItem.config
                                                        .audio_loop
                                                "
                                                type="checkbox"
                                                class="size-4"
                                            />
                                            Loop audio</label
                                        >
                                    </div>

                                    <div
                                        v-if="
                                            selectedItem.template ===
                                            'split-screen'
                                        "
                                        class="mt-3 grid gap-2 rounded-md border bg-muted/30 p-3 text-xs sm:grid-cols-2"
                                    >
                                        <div>
                                            <label
                                                class="mb-1 block font-medium"
                                                >Left image · 1/3</label
                                            >
                                            <select
                                                v-model="
                                                    selectedItem.config
                                                        .split_screen!
                                                        .left_media_asset_id
                                                "
                                                class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="">
                                                    Pilih gambar
                                                </option>
                                                <option
                                                    v-for="asset in mediaAssets.filter(
                                                        (candidate) =>
                                                            isImageMedia(
                                                                candidate.type,
                                                            ),
                                                    )"
                                                    :key="asset.id"
                                                    :value="asset.id"
                                                >
                                                    {{ asset.name }}
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1 block font-medium"
                                                >Right source · 2/3</label
                                            >
                                            <select
                                                v-model="
                                                    selectedItem.config
                                                        .split_screen!
                                                        .right_source
                                                "
                                                class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="media">
                                                    Video asset
                                                </option>
                                                <option value="youtube">
                                                    YouTube
                                                </option>
                                                <option value="url">
                                                    URL video / web
                                                </option>
                                            </select>
                                        </div>
                                        <div
                                            v-if="
                                                selectedItem.config.split_screen
                                                    ?.right_source === 'media'
                                            "
                                            class="sm:col-span-2"
                                        >
                                            <select
                                                v-model="
                                                    selectedItem.config
                                                        .split_screen!
                                                        .right_media_asset_id
                                                "
                                                class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="">
                                                    Pilih video
                                                </option>
                                                <option
                                                    v-for="asset in mediaAssets.filter(
                                                        (candidate) =>
                                                            isVideoMedia(
                                                                candidate.type,
                                                            ) ||
                                                            candidate.type ===
                                                                'web',
                                                    )"
                                                    :key="asset.id"
                                                    :value="asset.id"
                                                >
                                                    {{ asset.name }} ·
                                                    {{ asset.type }}
                                                </option>
                                            </select>
                                        </div>
                                        <div v-else class="sm:col-span-2">
                                            <Input
                                                v-model="
                                                    selectedItem.config
                                                        .split_screen!.right_url
                                                "
                                                :placeholder="
                                                    selectedItem.config
                                                        .split_screen
                                                        ?.right_source ===
                                                    'youtube'
                                                        ? 'https://www.youtube.com/watch?v=...'
                                                        : 'https://example.com/video.m3u8'
                                                "
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1 block font-medium"
                                                >Left width (%)</label
                                            ><Input
                                                v-model="
                                                    selectedItem.config
                                                        .split_screen!
                                                        .left_width
                                                "
                                                type="number"
                                                min="20"
                                                max="80"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1 block font-medium"
                                                >Right width (%)</label
                                            ><Input
                                                v-model="
                                                    selectedItem.config
                                                        .split_screen!
                                                        .right_width
                                                "
                                                type="number"
                                                min="20"
                                                max="80"
                                            />
                                        </div>
                                    </div>

                                    <div class="mt-3 grid grid-cols-3 gap-2">
                                        <div>
                                            <label
                                                class="mb-1 block text-xs font-medium"
                                                >Durasi</label
                                            ><Input
                                                v-model="
                                                    selectedItem.duration_seconds
                                                "
                                                type="number"
                                                min="1"
                                            />
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1 block text-xs font-medium"
                                                >Transition</label
                                            ><select
                                                v-model="
                                                    selectedItem.transition
                                                "
                                                class="h-9 w-full rounded-md border bg-background px-2 text-sm"
                                            >
                                                <option value="none">
                                                    None
                                                </option>
                                                <option value="fade">
                                                    Fade
                                                </option>
                                                <option value="slide-left">
                                                    Slide left
                                                </option>
                                                <option value="slide-right">
                                                    Slide right
                                                </option>
                                                <option value="slide-up">
                                                    Slide up
                                                </option>
                                                <option value="slide-down">
                                                    Slide down
                                                </option>
                                                <option value="zoom">
                                                    Zoom
                                                </option>
                                            </select>
                                        </div>
                                        <div>
                                            <label
                                                class="mb-1 block text-xs font-medium"
                                                >Fit</label
                                            ><select
                                                v-model="
                                                    selectedItem.config.fit
                                                "
                                                class="h-9 w-full rounded-md border bg-background px-2 text-sm"
                                            >
                                                <option value="cover">
                                                    Cover
                                                </option>
                                                <option value="contain">
                                                    Contain
                                                </option>
                                                <option value="fill">
                                                    Fill
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div
                                        v-if="
                                            (selectedItem.template ??
                                                'media') !== 'media'
                                        "
                                        class="mt-3 space-y-3 rounded-md border bg-muted/20 p-3"
                                    >
                                        <div class="grid gap-2 sm:grid-cols-2">
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium"
                                                    >Title</label
                                                >
                                                <Input
                                                    v-model="
                                                        selectedItem.content!
                                                            .title
                                                    "
                                                    :placeholder="
                                                        templateLabel(
                                                            selectedItem.template,
                                                        )
                                                    "
                                                />
                                            </div>
                                            <div>
                                                <label
                                                    class="mb-1 block text-xs font-medium"
                                                    >Subtitle / location</label
                                                >
                                                <Input
                                                    v-model="
                                                        selectedItem.content!
                                                            .subtitle
                                                    "
                                                    placeholder="Informasi tambahan"
                                                />
                                            </div>
                                        </div>
                                        <div
                                            v-if="
                                                ![
                                                    'flight-board',
                                                    'data-table',
                                                    'menu',
                                                ].includes(
                                                    selectedItem.template ?? '',
                                                )
                                            "
                                        >
                                            <label
                                                class="mb-1 block text-xs font-medium"
                                                >Data</label
                                            >
                                            <textarea
                                                v-model="
                                                    selectedItem.content!.body
                                                "
                                                class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                                placeholder="Tulis konten template atau satu baris per data"
                                            />
                                        </div>
                                        <div v-else>
                                            <label
                                                class="mb-1 block text-xs font-medium"
                                                >Structured data</label
                                            >
                                            <textarea
                                                :value="
                                                    structuredContentJson(
                                                        selectedItem,
                                                    )
                                                "
                                                class="min-h-40 w-full rounded-md border bg-background px-3 py-2 font-mono text-xs"
                                                :placeholder="
                                                    structuredPlaceholder(
                                                        selectedItem,
                                                    )
                                                "
                                                @input="
                                                    updateStructuredContent(
                                                        selectedItem,
                                                        $event,
                                                    )
                                                "
                                            />
                                        </div>
                                        <template
                                            v-if="
                                                selectedItem.template ===
                                                    'custom' &&
                                                selectedItem.template_definition
                                            "
                                        >
                                            <div
                                                v-for="field in selectedItem
                                                    .template_definition.schema"
                                                :key="field.name"
                                            >
                                                <label
                                                    class="mb-1 block text-xs font-medium"
                                                    >{{
                                                        field.label ??
                                                        field.name
                                                    }}</label
                                                >
                                                <select
                                                    v-if="
                                                        field.type === 'image'
                                                    "
                                                    v-model="
                                                        selectedItem.content![
                                                            field.name
                                                        ]
                                                    "
                                                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                                >
                                                    <option value="">
                                                        Pilih gambar
                                                    </option>
                                                    <option
                                                        v-if="
                                                            isCustomImageValue(
                                                                selectedItem,
                                                                field.name,
                                                            )
                                                        "
                                                        :value="
                                                            selectedItem
                                                                .content![
                                                                field.name
                                                            ]
                                                        "
                                                    >
                                                        URL saat ini
                                                    </option>
                                                    <option
                                                        v-for="asset in mediaAssets.filter(
                                                            (candidate) =>
                                                                isImageMedia(
                                                                    candidate.type,
                                                                ),
                                                        )"
                                                        :key="asset.id"
                                                        :value="asset.url"
                                                    >
                                                        {{ asset.name }}
                                                    </option>
                                                </select>
                                                <input
                                                    v-if="
                                                        field.type === 'image'
                                                    "
                                                    type="text"
                                                    class="mt-1 h-8 w-full rounded-md border border-input bg-background px-2 text-xs"
                                                    :value="
                                                        String(
                                                            selectedItem
                                                                .content?.[
                                                                field.name
                                                            ] ?? '',
                                                        )
                                                    "
                                                    placeholder="atau tempel URL gambar"
                                                    @input="
                                                        setImageValue(
                                                            selectedItem,
                                                            field.name,
                                                            $event,
                                                        )
                                                    "
                                                />
                                                <textarea
                                                    v-else-if="
                                                        field.type ===
                                                        'textarea'
                                                    "
                                                    v-model="
                                                        selectedItem.content![
                                                            field.name
                                                        ]
                                                    "
                                                    class="min-h-20 w-full rounded-md border bg-background px-3 py-2 text-sm"
                                                />
                                                <textarea
                                                    v-else-if="
                                                        field.type === 'json'
                                                    "
                                                    :value="
                                                        customFieldJson(
                                                            selectedItem,
                                                            field.name,
                                                        )
                                                    "
                                                    class="min-h-48 w-full rounded-md border bg-background px-3 py-2 font-mono text-xs"
                                                    spellcheck="false"
                                                    @input="
                                                        updateCustomJsonField(
                                                            selectedItem,
                                                            field.name,
                                                            $event,
                                                        )
                                                    "
                                                />
                                                <Input
                                                    v-else
                                                    v-model="
                                                        selectedItem.content![
                                                            field.name
                                                        ]
                                                    "
                                                    :type="
                                                        field.type === 'number'
                                                            ? 'number'
                                                            : 'text'
                                                    "
                                                />
                                            </div>
                                        </template>
                                        <div
                                            v-if="
                                                [
                                                    'custom',
                                                    'weather',
                                                    'flight-board',
                                                    'data-table',
                                                    'menu',
                                                ].includes(
                                                    selectedItem.template ?? '',
                                                )
                                            "
                                        >
                                            <label
                                                class="mb-1 block text-xs font-medium"
                                                >Data source</label
                                            >
                                            <select
                                                v-model="
                                                    selectedItem.data_source!
                                                        .mode
                                                "
                                                class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                            >
                                                <option value="static">
                                                    Manual data
                                                </option>
                                                <option value="api">
                                                    API endpoint
                                                </option>
                                            </select>
                                            <div
                                                v-if="
                                                    selectedItem.data_source
                                                        ?.mode === 'api'
                                                "
                                                class="mt-2 space-y-2"
                                            >
                                                <select
                                                    v-model="
                                                        selectedItem.api_connection_id
                                                    "
                                                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                                >
                                                    <option value="">
                                                        Pilih API connection
                                                    </option>
                                                    <option
                                                        v-for="connection in apiConnections"
                                                        :key="connection.id"
                                                        :value="connection.id"
                                                    >
                                                        {{ connection.name }}
                                                    </option>
                                                </select>
                                                <Input
                                                    v-model="
                                                        selectedItem
                                                            .data_source!.url
                                                    "
                                                    :placeholder="
                                                        selectedItem.template ===
                                                        'weather'
                                                            ? '/v1/forecast?latitude=-6.2&longitude=106.8&current=temperature_2m,relative_humidity_2m'
                                                            : '/v1/queue'
                                                    "
                                                />
                                                <div
                                                    class="grid grid-cols-2 gap-2"
                                                >
                                                    <Input
                                                        v-model="
                                                            selectedItem
                                                                .data_source!
                                                                .refresh_seconds
                                                        "
                                                        type="number"
                                                        min="10"
                                                        placeholder="Refresh seconds"
                                                    />
                                                    <Input
                                                        v-model="
                                                            selectedItem
                                                                .data_source!
                                                                .timeout_seconds
                                                        "
                                                        type="number"
                                                        min="1"
                                                        max="15"
                                                        placeholder="Timeout"
                                                    />
                                                </div>
                                                <div
                                                    class="flex flex-wrap items-center gap-2"
                                                >
                                                    <Button
                                                        type="button"
                                                        variant="outline"
                                                        size="sm"
                                                        @click="
                                                            testApi(
                                                                selectedItem,
                                                            )
                                                        "
                                                        ><FlaskConical
                                                            class="size-4"
                                                        />Test API</Button
                                                    >
                                                    <span
                                                        class="self-center text-xs text-muted-foreground"
                                                        >{{
                                                            apiTestMessage
                                                        }}</span
                                                    >
                                                </div>
                                                <textarea
                                                    v-model="
                                                        selectedItem
                                                            .data_source!
                                                            .mapping_json
                                                    "
                                                    class="min-h-20 w-full rounded-md border bg-background px-3 py-2 font-mono text-xs"
                                                    :placeholder="
                                                        mappingPlaceholder(
                                                            selectedItem,
                                                        )
                                                    "
                                                />
                                                <pre
                                                    v-if="apiTestData"
                                                    class="max-h-32 overflow-auto rounded-md bg-zinc-950 p-2 text-[11px] text-emerald-300"
                                                    >{{
                                                        JSON.stringify(
                                                            apiTestData,
                                                            null,
                                                            2,
                                                        )
                                                    }}</pre>
                                            </div>
                                            <p
                                                class="mt-1 text-[11px] text-muted-foreground"
                                            >
                                                Gunakan endpoint relatif.
                                                Credential dikelola di API
                                                Connections dan data manual
                                                tetap menjadi fallback.
                                            </p>
                                        </div>
                                    </div>
                                </template>
                                <p v-else class="text-sm text-muted-foreground">
                                    Pilih slide di panel kiri, atau tambahkan
                                    slide baru untuk mulai mengedit.
                                </p>
                            </template>

                            <!-- Global overlays -->
                            <template v-else-if="inspectorTab === 'overlay'">
                                <div class="flex items-center gap-2">
                                    <Rss class="size-4 text-cyan-600" />
                                    <h2 class="text-sm font-semibold">
                                        Global overlays
                                    </h2>
                                </div>
                                <p
                                    class="mt-1 text-[11px] text-muted-foreground"
                                >
                                    Tampil di semua item playlist. Maksimal
                                    {{ MAX_OVERLAYS }} overlay.
                                </p>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <Button
                                        v-for="type in OVERLAY_TYPES"
                                        :key="type"
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        class="min-w-0 justify-start px-2"
                                        :disabled="
                                            form.overlays.length >= MAX_OVERLAYS
                                        "
                                        @click="addOverlay(type)"
                                    >
                                        <component
                                            :is="overlayIcons[type]"
                                            class="size-4 shrink-0"
                                        />
                                        <span class="truncate">{{
                                            OVERLAY_LABELS[type] ?? type
                                        }}</span>
                                    </Button>
                                </div>
                                <p
                                    v-if="form.overlays.length === 0"
                                    class="mt-3 rounded-md border border-dashed p-3 text-xs text-muted-foreground"
                                >
                                    Belum ada overlay global.
                                </p>
                                <OverlayCard
                                    v-for="(
                                        overlay, overlayIndex
                                    ) in form.overlays"
                                    :key="
                                        overlay.id ?? `overlay-${overlayIndex}`
                                    "
                                    :overlay="overlay"
                                    :api-connections="apiConnections"
                                    :media-assets="mediaAssets"
                                    :index="overlayIndex"
                                    :total="form.overlays.length"
                                    @remove="removeOverlay(overlay)"
                                    @duplicate="duplicateOverlay(overlay)"
                                    @move="
                                        (direction) =>
                                            moveOverlay(overlay, direction)
                                    "
                                />
                            </template>

                            <!-- Playlist settings -->
                            <template v-else>
                                <h2 class="text-sm font-semibold">
                                    Playlist details
                                </h2>
                                <div class="mt-3 space-y-3">
                                    <div>
                                        <label
                                            class="mb-1 block text-xs font-medium"
                                            >Name</label
                                        >
                                        <Input
                                            v-model="form.name"
                                            placeholder="Lobby morning loop"
                                        />
                                    </div>
                                    <div>
                                        <label
                                            class="mb-1 block text-xs font-medium"
                                            >Description</label
                                        >
                                        <textarea
                                            v-model="form.description"
                                            class="min-h-20 w-full rounded-md border border-input bg-background px-3 py-2 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                            placeholder="Catatan untuk tim operasional"
                                        />
                                    </div>
                                    <label
                                        class="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm"
                                    >
                                        <span>
                                            <span class="font-medium"
                                                >Active</span
                                            >
                                            <span
                                                class="block text-xs text-muted-foreground"
                                                >Bisa dipakai schedule.</span
                                            >
                                        </span>
                                        <input
                                            v-model="form.is_active"
                                            type="checkbox"
                                            class="size-4 rounded border-input"
                                        />
                                    </label>
                                    <div>
                                        <label
                                            class="mb-1 block text-xs font-medium"
                                            >Publishing status</label
                                        >
                                        <select
                                            v-model="form.status"
                                            class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                                        >
                                            <option value="draft">Draft</option>
                                            <option value="published">
                                                Published
                                            </option>
                                        </select>
                                        <p
                                            class="mt-1 text-xs text-muted-foreground"
                                        >
                                            Draft tidak akan diputar di display.
                                        </p>
                                    </div>
                                    <div
                                        class="rounded-md border bg-muted/20 p-3 text-xs text-muted-foreground"
                                    >
                                        <div class="flex justify-between">
                                            <span>Slide</span>
                                            <span
                                                class="font-medium text-foreground"
                                                >{{ form.items.length }}</span
                                            >
                                        </div>
                                        <div class="mt-1 flex justify-between">
                                            <span>Durasi loop</span>
                                            <span
                                                class="font-medium text-foreground"
                                                >{{
                                                    formatLoopDuration(
                                                        totalDuration,
                                                    )
                                                }}</span
                                            >
                                        </div>
                                        <div class="mt-1 flex justify-between">
                                            <span>Overlay aktif</span>
                                            <span
                                                class="font-medium text-foreground"
                                                >{{ activeOverlayCount }}</span
                                            >
                                        </div>
                                    </div>
                                </div>
                            </template>
                        </div>
                    </section>
                </aside>
            </template>
        </form>

        <div
            class="fixed inset-x-0 bottom-0 z-50 border-t bg-background/95 p-3 pb-[max(0.75rem,env(safe-area-inset-bottom))] shadow-[0_-8px_24px_rgba(0,0,0,0.08)] backdrop-blur md:hidden"
        >
            <div class="mx-auto flex max-w-lg gap-2">
                <Link
                    href="/playlists"
                    class="inline-flex h-10 flex-1 items-center justify-center gap-2 rounded-md border bg-background px-3 text-sm font-medium hover:bg-accent"
                >
                    <ArrowLeft class="size-4" />
                    {{ isEditing ? 'Back' : 'Cancel' }}
                </Link>
                <SubmitButton
                    type="button"
                    class="h-10 flex-1"
                    :processing="form.processing"
                    :label="isEditing ? 'Save changes' : 'Create'"
                    loading-label="Menyimpan..."
                    @click="submit"
                >
                    <Save class="size-4" />
                </SubmitButton>
            </div>
        </div>

        <SlideshowPlayer
            v-if="slideshowOpen"
            :items="form.items"
            :media-assets="mediaAssets"
            :global-overlays="globalPreviewOverlays"
            :brand-label="tenant.name"
            :start-index="selectedIndex"
            @close="slideshowOpen = false"
        />
    </div>
</template>
