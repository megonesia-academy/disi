<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { ArrowDown, ArrowLeft, ArrowUp, CloudSun, Clock3, GripVertical, Plus, Rss, Save, Trash2 } from '@lucide/vue';
import { computed, ref } from 'vue';

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
    schema: Array<{ name: string; label?: string; type?: string; default?: string }>;
    is_active: boolean;
};
type ApiConnection = { id: string; name: string; provider: string };

type Overlay = {
    id?: string;
    type: 'ticker' | 'clock' | 'weather';
    is_active: boolean;
    config: {
        type?: 'ticker' | 'weather';
        text?: string;
        source?: 'static' | 'array';
        items?: string[];
        position: 'top' | 'bottom' | 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';
        speed: number;
        text_color: string;
        background_color: string;
        format?: '24h' | '12h';
        offset_x?: number;
        offset_y?: number;
        api_connection_id?: string | null;
        endpoint?: string;
        refresh_seconds?: number;
    };
};

type Item = {
    id?: string;
    media_asset_id: string | null;
    template?: string;
    template_id?: string | null;
    api_connection_id?: string | null;
    template_definition?: ContentTemplate | null;
    content?: Record<string, unknown>;
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
        overlays?: unknown[];
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
    overlays: Overlay[];
    items: Item[];
};

const props = defineProps<{
    mode: 'create' | 'edit';
    tenant: { id: string; name: string };
    mediaAssets: MediaAsset[];
    templates: ContentTemplate[];
    apiConnections: ApiConnection[];
    playlist?: Playlist;
}>();

function normalizedOverlay(overlay: Overlay): Overlay {
    return {
        ...overlay,
        config: {
            ...overlay.config,
            position: overlay.config.position ?? (overlay.type === 'clock' ? 'top-right' : 'bottom'),
            speed: overlay.config.speed ?? 60,
            text_color: overlay.config.text_color ?? '#ffffff',
            background_color: overlay.config.background_color ?? '#111827',
            format: overlay.config.format ?? '24h',
            api_connection_id: overlay.config.api_connection_id ?? null,
            endpoint: overlay.config.endpoint ?? '/v1/forecast?latitude=-6.2&longitude=106.8&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Asia%2FJakarta',
            refresh_seconds: overlay.config.refresh_seconds ?? 900,
            offset_x: overlay.config.offset_x ?? 0,
            offset_y: overlay.config.offset_y ?? 0,
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
        data_source: { mode: item.data_source?.mode ?? 'static', url: item.data_source?.url ?? '', refresh_seconds: item.data_source?.refresh_seconds ?? 60, timeout_seconds: item.data_source?.timeout_seconds ?? 5, auth_type: item.data_source?.auth_type ?? 'none', auth_value: '', mapping: item.data_source?.mapping ?? {}, mapping_json: JSON.stringify(item.data_source?.mapping ?? {}, null, 2) },
        config: { fit: item.config?.fit ?? 'cover', overlays: item.config?.overlays ?? [], audio_asset_id: item.config?.audio_asset_id ?? null, audio_loop: item.config?.audio_loop ?? false, split_screen: item.config?.split_screen ?? { left_media_asset_id: null, right_media_asset_id: null, right_source: 'media', right_url: '', left_width: 33, right_width: 67 } },
    })),
});

const draggedIndex = ref<number | null>(null);
const dropIndex = ref<number | null>(null);
const apiTestMessage = ref<string | null>(null);
const apiTestData = ref<Record<string, unknown> | null>(null);
const isEditing = computed(() => props.mode === 'edit');

function mediaAsset(mediaAssetId: string): MediaAsset | undefined {
    return props.mediaAssets.find((asset) => asset.id === mediaAssetId);
}

function addItem(): void {
    const asset = props.mediaAssets[0];

    form.items.push({
        media_asset_id: asset?.id ?? null,
        template: asset ? 'media' : 'announcement',
        api_connection_id: null,
        content: asset ? {} : { title: 'Announcement', subtitle: '', body: '' },
        data_source: { mode: 'static', url: '', refresh_seconds: 60, timeout_seconds: 5, auth_type: 'none', auth_value: '', mapping: {}, mapping_json: '{}' },
        duration_seconds: asset?.duration_seconds ?? 10,
        transition: 'fade',
        config: { fit: 'cover', overlays: [], audio_asset_id: null, audio_loop: false, split_screen: { left_media_asset_id: props.mediaAssets.find((asset) => asset.type === 'image')?.id ?? null, right_media_asset_id: props.mediaAssets.find((asset) => asset.type === 'video')?.id ?? null, right_source: 'media', right_url: '', left_width: 33, right_width: 67 } },
    });
}

function setTemplate(item: Item, template: string): void {
    if (template.startsWith('custom:')) {
        setCustomTemplate(item, template.slice(7));
        return;
    }
    item.template = template;
    item.template_id = null;
    item.api_connection_id = null;
    item.config.split_screen ??= { left_media_asset_id: props.mediaAssets.find((asset) => asset.type === 'image')?.id ?? null, right_media_asset_id: props.mediaAssets.find((asset) => asset.type === 'video')?.id ?? null, right_source: 'media', right_url: '', left_width: 33, right_width: 67 };
    if (template !== 'media') {
        item.media_asset_id = null;
    } else if (!item.media_asset_id) {
        item.media_asset_id = props.mediaAssets[0]?.id ?? null;
    }
    item.content = item.content ?? {};
    item.data_source = { mode: 'static', url: '', refresh_seconds: 60, timeout_seconds: 5, auth_type: 'none', auth_value: '', mapping: {}, mapping_json: '{}' };
}

function setCustomTemplate(item: Item, templateId: string): void {
    const template = props.templates.find((candidate) => candidate.id === templateId);
    item.template = 'custom';
    item.template_id = templateId || null;
    item.media_asset_id = null;
    item.template_definition = template ?? null;
    item.content = item.content ?? {};
    item.data_source = item.data_source ?? { mode: 'static', url: '', refresh_seconds: 60, timeout_seconds: 5, auth_type: 'none', auth_value: '', mapping: {}, mapping_json: '{}' };
    for (const field of template?.schema ?? []) {
        if (item.content[field.name] === undefined && field.default !== undefined) {
            item.content[field.name] = field.default;
        }
    }
}

function templateLabel(template?: string): string {
    return {
        media: 'Standard media',
        announcement: 'Announcement',
        'data-table': 'Data table',
        'patient-queue': 'Patient queue',
        'flight-board': 'Flight board',
        menu: 'Restaurant menu',
        weather: 'Weather',
        'split-screen': 'Split screen',
    }[template ?? 'media'] ?? 'Standard media';
}

function templateOption(item: Item): string {
    return item.template === 'custom' && item.template_id ? `custom:${item.template_id}` : (item.template ?? 'media');
}

function mappingPlaceholder(item: Item): string {
    return item.template === 'weather'
        ? '{"title":"current.temperature_2m","subtitle":"current.relative_humidity_2m","body":"current.temperature_2m"}'
        : '{"title":"queue.title","number":"queue.number"}';
}

function structuredContentJson(item: Item): string {
    const content = item.content ?? {};
    const value = item.template === 'menu' ? { items: content.items ?? [] } : item.template === 'flight-board' ? { rows: content.rows ?? [] } : { columns: content.columns ?? [], rows: content.rows ?? [] };

    return JSON.stringify(value, null, 2);
}

function structuredPlaceholder(item: Item): string {
    if (item.template === 'menu') return '{"items":[{"name":"Nasi Goreng","description":"Ayam dan telur","price":"35.000","image":""}]}';
    if (item.template === 'flight-board') return '{"rows":[{"flight":"GA-204","destination":"Bali","time":"10:30","gate":"A12","status":"Boarding"}]}';

    return '{"columns":["No","Name","Status"],"rows":[["001","Customer A","Waiting"]]}';
}

function updateStructuredContent(item: Item, event: Event): void {
    try {
        const parsed = JSON.parse((event.target as HTMLTextAreaElement).value);
        if (!parsed || typeof parsed !== 'object' || Array.isArray(parsed)) return;
        item.content = { ...item.content, ...parsed };
    } catch {
        // Keep the previous valid content while the user is typing JSON.
    }
}

function parseMapping(value?: string): Record<string, string> {
    try {
        const parsed = JSON.parse(value || '{}');
        return parsed && typeof parsed === 'object' && !Array.isArray(parsed) ? parsed as Record<string, string> : {};
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

function applyApiDataToContent(item: Item, payload: Record<string, unknown>): void {
    const defaultMapping = item.template === 'weather'
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

    if (Object.keys(mapping).length === 0 && Object.keys(defaultMapping).length > 0) {
        item.data_source!.mapping_json = JSON.stringify(defaultMapping, null, 2);
    }

    const effectiveMapping = Object.keys(mapping).length > 0 ? mapping : defaultMapping;

    if (Object.keys(effectiveMapping).length === 0) {
        return;
    }

    item.content ??= {};
    Object.entries(effectiveMapping).forEach(([target, path]) => {
        const value = valueAtPath(payload, path);

        if (value !== undefined && value !== null) {
            item.content![target] = ['title', 'subtitle', 'body'].includes(target)
                ? Array.isArray(value) ? value.join('\n') : String(value)
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
    const response = await fetch(`/integrations/api/${item.api_connection_id}/test`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
        },
    });
    const payload = await response.json();
    apiTestMessage.value = payload.message ?? (response.ok ? 'API connected.' : 'API test failed.');
    apiTestData.value = response.ok ? payload.data : null;
    if (response.ok && payload.data && typeof payload.data === 'object' && !Array.isArray(payload.data)) {
        applyApiDataToContent(item, payload.data as Record<string, unknown>);
    }
}

function removeItem(index: number): void {
    form.items.splice(index, 1);
}

function moveItem(index: number, direction: -1 | 1): void {
    const target = index + direction;
    if (target < 0 || target >= form.items.length) return;
    const [item] = form.items.splice(index, 1);
    form.items.splice(target, 0, item);
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
        const [item] = form.items.splice(draggedIndex.value, 1);
        form.items.splice(index, 0, item);
    }
    draggedIndex.value = null;
    dropIndex.value = null;
}

function addGlobalTicker(): void {
    if (form.overlays.some((overlay) => overlay.type === 'ticker')) return;
    form.overlays.push({
        type: 'ticker',
        is_active: true,
        config: {
            type: 'ticker',
            text: 'Informasi terbaru untuk pengunjung',
            source: 'static',
            items: [],
            position: 'bottom',
            speed: 60,
            text_color: '#ffffff',
            background_color: '#111827',
            offset_x: 0,
            offset_y: 0,
        },
    });
}

function addGlobalClock(): void {
    if (form.overlays.some((overlay) => overlay.type === 'clock')) return;
    form.overlays.push({
        type: 'clock',
        is_active: true,
        config: {
            type: 'ticker',
            text: '',
            position: 'top-right',
            speed: 60,
            text_color: '#ffffff',
            background_color: '#111827',
            format: '24h',
            offset_x: 0,
            offset_y: 0,
        },
    });
}

function addGlobalWeather(): void {
    if (form.overlays.some((overlay) => overlay.type === 'weather')) return;
    form.overlays.push({
        type: 'weather',
        is_active: true,
        config: {
            type: 'weather',
            api_connection_id: props.apiConnections[0]?.id ?? null,
            endpoint: '/v1/forecast?latitude=-6.2&longitude=106.8&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Asia%2FJakarta',
            refresh_seconds: 900,
            position: 'top-right',
            speed: 60,
            text_color: '#ffffff',
            background_color: '#0f172a',
            offset_x: 0,
            offset_y: 0,
        },
    });
}

function removeOverlay(overlay: Overlay): void {
    const index = form.overlays.indexOf(overlay);
    if (index >= 0) form.overlays.splice(index, 1);
}

function submit(): void {
    form.transform((data) => ({
        ...data,
        items: data.items.map((item) => ({
            ...item,
            data_source: item.data_source ? { ...item.data_source, mapping: parseMapping(item.data_source.mapping_json) } : undefined,
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

    <div class="flex min-h-full flex-1 flex-col gap-6 overflow-x-clip bg-muted/20 p-4 pb-24 md:p-6 md:pb-24">
        <section class="sticky top-0 z-50 flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background/95 p-5 shadow-lg backdrop-blur supports-[backdrop-filter]:bg-background/80">
            <div>
                <Link href="/playlists" class="text-sm text-muted-foreground hover:text-foreground">
                    Playlists
                </Link>
                <h1 class="mt-2 text-2xl font-semibold tracking-tight">
                    {{ isEditing ? 'Edit playlist' : 'New playlist' }}
                </h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Atur konten, urutan, transition, dan overlay dalam satu workspace yang fokus.
                </p>
            </div>
            <div class="flex gap-2">
                <Link href="/playlists" class="inline-flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm font-medium hover:bg-accent">
                    <ArrowLeft class="size-4" />
                    {{ isEditing ? 'Back' : 'Cancel' }}
                </Link>
                <Button type="button" :disabled="form.processing" @click="submit">
                    <Save class="size-4" />
                    {{ isEditing ? 'Save changes' : 'Create playlist' }}
                </Button>
            </div>
        </section>

        <form class="grid gap-6 xl:grid-cols-[minmax(0,360px)_minmax(0,1fr)]" @submit.prevent="submit">
            <aside class="space-y-4">
                <section class="rounded-lg border bg-background p-5 shadow-sm">
                    <h2 class="font-semibold">Playlist details</h2>
                    <div class="mt-4 space-y-4">
                        <div>
                            <label class="mb-1 block text-sm font-medium">Name</label>
                            <Input v-model="form.name" placeholder="Lobby morning loop" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Description</label>
                            <textarea v-model="form.description" class="border-input min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm outline-none focus-visible:ring-[3px] focus-visible:ring-ring/50" placeholder="Catatan untuk tim operasional" />
                        </div>
                        <label class="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm">
                            <span>
                                <span class="font-medium">Active</span>
                                <span class="block text-xs text-muted-foreground">Bisa dipakai schedule.</span>
                            </span>
                            <input v-model="form.is_active" type="checkbox" class="size-4 rounded border-input" />
                        </label>
                        <div>
                            <label class="mb-1 block text-sm font-medium">Publishing status</label>
                            <select v-model="form.status" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="draft">Draft</option>
                                <option value="published">Published</option>
                            </select>
                            <p class="mt-1 text-xs text-muted-foreground">Draft tidak akan diputar di display.</p>
                        </div>
                    </div>
                </section>

                <section class="rounded-lg border bg-background p-5 shadow-sm">
                    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <div class="flex items-center gap-2"><Rss class="size-4 text-cyan-600" /><h2 class="font-semibold">Global overlays</h2></div>
                            <p class="mt-1 text-xs text-muted-foreground">Tampil di semua item playlist.</p>
                        </div>
                        <div class="flex flex-wrap gap-2 sm:justify-end">
                            <Button type="button" size="sm" variant="outline" title="Add ticker" @click="addGlobalTicker"><Rss class="size-4" /> Ticker</Button>
                            <Button type="button" size="sm" variant="outline" title="Add clock" @click="addGlobalClock"><Clock3 class="size-4" /> Clock</Button>
                            <Button type="button" size="sm" variant="outline" title="Add weather" @click="addGlobalWeather"><CloudSun class="size-4" /> Weather</Button>
                        </div>
                    </div>
                    <div v-if="form.overlays.length === 0" class="mt-4 rounded-md border border-dashed p-4 text-sm text-muted-foreground">Belum ada overlay global.</div>
                    <div v-for="overlay in form.overlays" :key="overlay.id ?? overlay.type" class="mt-4 rounded-md border bg-muted/20 p-3">
                        <div class="flex items-center justify-between gap-2">
                            <span class="text-sm font-medium">{{ overlay.type === 'clock' ? 'Clock' : overlay.type === 'weather' ? 'Weather' : 'Ticker' }}</span>
                            <Button type="button" size="icon" variant="ghost" title="Remove overlay" @click="removeOverlay(overlay)"><Trash2 class="size-4" /></Button>
                        </div>
                        <div v-if="overlay.type === 'ticker'" class="mt-3 space-y-3">
                            <Input v-if="overlay.config.source !== 'array'" v-model="overlay.config.text" placeholder="Teks berjalan" />
                            <select v-model="overlay.config.source" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="static">Static text</option><option value="array">Text array</option></select>
                            <textarea v-if="overlay.config.source === 'array'" :value="(overlay.config.items ?? []).join('\n')" class="min-h-20 w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Satu pesan per baris" @input="overlay.config.items = (($event.target as HTMLTextAreaElement).value).split('\n').filter(Boolean)" />
                            <div>
                                <label class="mb-1 block text-xs font-medium">Position</label>
                                <select v-model="overlay.config.position" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                    <option value="bottom">Bottom</option>
                                    <option value="top">Top</option>
                                </select>
                            </div>
                        </div>
                        <div v-else-if="overlay.type === 'clock'" class="mt-3">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block text-xs font-medium">Clock format</label>
                                    <select v-model="overlay.config.format" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="24h">24 hour</option><option value="12h">12 hour (AM/PM)</option></select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium">Position</label>
                                    <select v-model="overlay.config.position" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                        <option value="top-left">Top left</option>
                                        <option value="top-right">Top right</option>
                                        <option value="bottom-left">Bottom left</option>
                                        <option value="bottom-right">Bottom right</option>
                                    </select>
                                </div>
                                <div><label class="mb-1 block text-xs font-medium">Horizontal offset</label><Input v-model="overlay.config.offset_x" type="number" min="-500" max="500" /><p class="mt-1 text-[11px] text-muted-foreground">Positif ke kanan.</p></div>
                                <div><label class="mb-1 block text-xs font-medium">Vertical offset</label><Input v-model="overlay.config.offset_y" type="number" min="-500" max="500" /><p class="mt-1 text-[11px] text-muted-foreground">Positif ke bawah.</p></div>
                            </div>
                        </div>
                        <div v-else class="mt-3 space-y-3">
                            <div>
                                <label class="mb-1 block text-xs font-medium">API connection</label>
                                <select v-model="overlay.config.api_connection_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="">Pilih API connection</option><option v-for="connection in apiConnections" :key="connection.id" :value="connection.id">{{ connection.name }} · {{ connection.provider }}</option></select>
                            </div>
                            <div>
                                <label class="mb-1 block text-xs font-medium">Endpoint relatif</label>
                                <Input v-model="overlay.config.endpoint" placeholder="/v1/forecast?latitude=-6.2&longitude=106.8&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Asia%2FJakarta" />
                                <p class="mt-1 text-xs text-muted-foreground">Endpoint harus relatif dan memakai provider yang sudah approved.</p>
                            </div>
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div><label class="mb-1 block text-xs font-medium">Refresh (detik)</label><Input v-model="overlay.config.refresh_seconds" type="number" min="10" max="86400" /></div>
                                <div><label class="mb-1 block text-xs font-medium">Position</label><select v-model="overlay.config.position" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="top-left">Top left</option><option value="top-right">Top right</option><option value="bottom-left">Bottom left</option><option value="bottom-right">Bottom right</option></select></div>
                                <div><label class="mb-1 block text-xs font-medium">Horizontal offset</label><Input v-model="overlay.config.offset_x" type="number" min="-500" max="500" /><p class="mt-1 text-[11px] text-muted-foreground">Positif ke kanan.</p></div>
                                <div><label class="mb-1 block text-xs font-medium">Vertical offset</label><Input v-model="overlay.config.offset_y" type="number" min="-500" max="500" /><p class="mt-1 text-[11px] text-muted-foreground">Positif ke bawah.</p></div>
                            </div>
                        </div>
                    </div>
                </section>
            </aside>

            <section class="rounded-lg border bg-background shadow-sm">
                <div class="flex items-center justify-between border-b p-5">
                    <div>
                        <h2 class="font-semibold">Playlist items</h2>
                        <p class="mt-1 text-sm text-muted-foreground">Susun urutan dan properti tiap media.</p>
                    </div>
                    <Button type="button" size="sm" variant="outline" @click="addItem"><Plus class="size-4" /> Add item</Button>
                </div>
                <div v-if="mediaAssets.length === 0 && form.items.length === 0" class="p-8 text-sm text-muted-foreground">Tambahkan item template atau upload media untuk memakai template standar.</div>
                <div v-else-if="form.items.length === 0" class="p-8 text-sm text-muted-foreground">Belum ada item. Klik Add item untuk mulai.</div>
                <div v-for="(item, index) in form.items" :key="item.id ?? index" class="border-b p-4 last:border-b-0" :class="dropIndex === index && draggedIndex !== index ? 'bg-primary/10' : ''" @dragover.prevent="dropIndex = index" @drop="dropItem(index, $event)">
                    <div class="flex items-start gap-3">
                        <span draggable="true" class="grid size-8 shrink-0 cursor-grab place-items-center rounded-md border border-dashed text-muted-foreground" title="Drag to reorder" @dragstart="startDrag(index, $event)" @dragend="draggedIndex = null; dropIndex = null"><GripVertical class="size-4" /></span>
                        <div class="grid size-8 shrink-0 place-items-center rounded-md bg-primary text-sm font-semibold text-primary-foreground">{{ index + 1 }}</div>
                        <div class="grid size-20 shrink-0 place-items-center overflow-hidden rounded-md border bg-muted">
                            <img
                                v-if="item.template === 'media' && mediaAsset(item.media_asset_id ?? '')?.type === 'image'"
                                :src="mediaAsset(item.media_asset_id ?? '')?.url"
                                :alt="mediaAsset(item.media_asset_id ?? '')?.name"
                                class="h-full w-full object-cover"
                            />
                            <span v-else class="px-1 text-center text-[10px] font-semibold uppercase text-muted-foreground">
                                {{ item.template === 'media' ? (mediaAsset(item.media_asset_id ?? '')?.type ?? 'media') : templateLabel(item.template) }}
                            </span>
                        </div>
                        <div class="min-w-0 flex-1">
                            <select :value="templateOption(item)" class="mb-2 w-full rounded-md border bg-background px-3 py-2 text-sm" @change="setTemplate(item, ($event.target as HTMLSelectElement).value)">
                                <option value="media">Standard media</option>
                                <option value="announcement">Announcement</option>
                                <option value="data-table">Data table</option>
                                <option value="patient-queue">Patient queue</option>
                                <option value="flight-board">Flight board</option>
                                <option value="menu">Restaurant menu</option>
                                <option value="weather">Weather</option>
                                <option value="split-screen">Split screen</option>
                                <option v-for="template in templates" :key="template.id" :value="`custom:${template.id}`">{{ template.name }}</option>
                            </select>
                            <select v-if="(item.template ?? 'media') === 'media'" v-model="item.media_asset_id" class="w-full rounded-md border bg-background px-3 py-2 text-sm"><option v-for="asset in mediaAssets" :key="asset.id" :value="asset.id">{{ asset.name }} · {{ asset.type }}</option></select>
                            <div class="mt-2 grid gap-2 sm:grid-cols-[minmax(0,1fr)_auto]">
                                <select v-model="item.config.audio_asset_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="">Tanpa audio</option><option v-for="asset in mediaAssets.filter((candidate) => candidate.type === 'audio')" :key="asset.id" :value="asset.id">Audio: {{ asset.name }}</option></select>
                                <label class="flex h-9 items-center gap-2 whitespace-nowrap text-xs"><input v-model="item.config.audio_loop" type="checkbox" class="size-4" /> Loop</label>
                            </div>
                            <div v-if="item.template === 'split-screen'" class="mt-2 grid gap-2 rounded-md border bg-muted/30 p-3 text-xs sm:grid-cols-2">
                                <div>
                                    <label class="mb-1 block font-medium">Left image · 1/3</label>
                                    <select v-model="item.config.split_screen!.left_media_asset_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="">Pilih gambar</option><option v-for="asset in mediaAssets.filter((candidate) => candidate.type === 'image')" :key="asset.id" :value="asset.id">{{ asset.name }}</option></select>
                                </div>
                                <div>
                                    <label class="mb-1 block font-medium">Right source · 2/3</label>
                                    <select v-model="item.config.split_screen!.right_source" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="media">Video asset</option><option value="youtube">YouTube</option><option value="url">URL video / web</option></select>
                                </div>
                                <div v-if="item.config.split_screen?.right_source === 'media'" class="sm:col-span-2">
                                    <select v-model="item.config.split_screen!.right_media_asset_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="">Pilih video</option><option v-for="asset in mediaAssets.filter((candidate) => candidate.type === 'video' || candidate.type === 'web')" :key="asset.id" :value="asset.id">{{ asset.name }} · {{ asset.type }}</option></select>
                                </div>
                                <div v-else class="sm:col-span-2">
                                    <Input v-model="item.config.split_screen!.right_url" :placeholder="item.config.split_screen?.right_source === 'youtube' ? 'https://www.youtube.com/watch?v=...' : 'https://example.com/video.m3u8'" />
                                </div>
                                <div><label class="mb-1 block font-medium">Left width (%)</label><Input v-model="item.config.split_screen!.left_width" type="number" min="20" max="80" /></div>
                                <div><label class="mb-1 block font-medium">Right width (%)</label><Input v-model="item.config.split_screen!.right_width" type="number" min="20" max="80" /></div>
                            </div>
                            <div class="mt-1 truncate text-xs text-muted-foreground">
                                {{ item.template === 'media' ? (mediaAsset(item.media_asset_id ?? '')?.name ?? 'Media belum dipilih') : (item.template === 'custom' ? (item.template_definition?.name ?? 'Custom template') : templateLabel(item.template)) }}
                            </div>
                        </div>
                        <div class="flex shrink-0 gap-1">
                            <Button type="button" size="icon" variant="ghost" :disabled="index === 0" title="Move up" @click="moveItem(index, -1)"><ArrowUp class="size-4" /></Button>
                            <Button type="button" size="icon" variant="ghost" :disabled="index === form.items.length - 1" title="Move down" @click="moveItem(index, 1)"><ArrowDown class="size-4" /></Button>
                            <Button type="button" size="icon" variant="ghost" title="Remove item" @click="removeItem(index)"><Trash2 class="size-4" /></Button>
                        </div>
                    </div>
                    <div class="mt-3 grid gap-3 sm:grid-cols-3">
                        <div><label class="mb-1 block text-xs font-medium">Duration seconds</label><Input v-model="item.duration_seconds" type="number" min="1" /></div>
                        <div><label class="mb-1 block text-xs font-medium">Transition</label><select v-model="item.transition" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="none">None</option><option value="fade">Fade</option><option value="slide-left">Slide left</option><option value="slide-right">Slide right</option><option value="slide-up">Slide up</option><option value="slide-down">Slide down</option><option value="zoom">Zoom</option></select></div>
                        <div><label class="mb-1 block text-xs font-medium">Media fit</label><select v-model="item.config.fit" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="cover">Cover</option><option value="contain">Contain</option><option value="fill">Fill</option></select></div>
                    </div>
                    <div v-if="(item.template ?? 'media') !== 'media'" class="mt-3 grid gap-3 rounded-md border bg-muted/20 p-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium">Title</label>
                            <Input v-model="item.content!.title" :placeholder="templateLabel(item.template)" />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium">Subtitle / location</label>
                            <Input v-model="item.content!.subtitle" placeholder="Informasi tambahan" />
                        </div>
                        <div v-if="!['flight-board', 'data-table', 'menu'].includes(item.template ?? '')" class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium">Data</label>
                            <textarea v-model="item.content!.body" class="min-h-24 w-full rounded-md border bg-background px-3 py-2 text-sm" placeholder="Tulis konten template atau satu baris per data" />
                            <p class="mt-1 text-xs text-muted-foreground">Konten awal template. Integrasi API dapat ditambahkan pada konfigurasi berikutnya.</p>
                        </div>
                        <div v-else class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium">Structured data</label>
                            <textarea :value="structuredContentJson(item)" class="min-h-40 w-full rounded-md border bg-background px-3 py-2 font-mono text-xs" :placeholder="structuredPlaceholder(item)" @input="updateStructuredContent(item, $event)" />
                            <p class="mt-1 text-xs text-muted-foreground">Data ini bisa diisi manual atau dipetakan dari API melalui mapping.</p>
                        </div>
                        <template v-if="item.template === 'custom' && item.template_definition">
                            <div v-for="field in item.template_definition.schema" :key="field.name">
                                <label class="mb-1 block text-xs font-medium">{{ field.label ?? field.name }}</label>
                                <select v-if="field.type === 'image'" v-model="item.content![field.name]" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                    <option value="">Pilih gambar</option>
                                    <option v-for="asset in mediaAssets.filter((candidate) => candidate.type === 'image')" :key="asset.id" :value="asset.url">{{ asset.name }}</option>
                                </select>
                                <textarea v-if="field.type === 'textarea'" v-model="item.content![field.name]" class="min-h-20 w-full rounded-md border bg-background px-3 py-2 text-sm" />
                                <Input v-else-if="field.type !== 'image'" v-model="item.content![field.name]" :type="field.type === 'number' ? 'number' : 'text'" />
                            </div>
                        </template>
                        <div v-if="['custom', 'weather', 'flight-board', 'data-table', 'menu'].includes(item.template ?? '')" class="sm:col-span-2">
                            <label class="mb-1 block text-xs font-medium">Data source</label>
                            <select v-model="item.data_source!.mode" class="h-9 w-full rounded-md border bg-background px-3 text-sm">
                                <option value="static">Manual data</option>
                                <option value="api">API endpoint</option>
                            </select>
                            <div v-if="item.data_source?.mode === 'api'" class="mt-2 space-y-2">
                                <select v-model="item.api_connection_id" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="">Pilih API connection</option><option v-for="connection in apiConnections" :key="connection.id" :value="connection.id">{{ connection.name }}</option></select>
                                <Input v-model="item.data_source.url" :placeholder="item.template === 'weather' ? '/v1/forecast?latitude=-6.2&longitude=106.8&current=temperature_2m,relative_humidity_2m' : '/v1/queue'" />
                                <div class="grid gap-2 sm:grid-cols-2">
                                    <Input v-model="item.data_source.refresh_seconds" type="number" min="10" placeholder="Refresh seconds" />
                                    <Input v-model="item.data_source.timeout_seconds" type="number" min="1" max="15" placeholder="Timeout" />
                                </div>
                                <div class="flex flex-wrap items-center gap-2">
                                    <Button type="button" variant="outline" size="sm" @click="testApi(item)">Test API</Button>
                                    <span class="self-center text-xs text-muted-foreground">{{ apiTestMessage }}</span>
                                </div>
                                <textarea v-model="item.data_source.mapping_json" class="min-h-20 w-full rounded-md border bg-background px-3 py-2 font-mono text-xs" :placeholder="mappingPlaceholder(item)" />
                                <pre v-if="apiTestData" class="max-h-32 overflow-auto rounded-md bg-zinc-950 p-2 text-[11px] text-emerald-300">{{ JSON.stringify(apiTestData, null, 2) }}</pre>
                            </div>
                            <p class="mt-1 text-xs text-muted-foreground">Gunakan endpoint relatif seperti <code>/v1/queue</code>. Credential dikelola di API Connections dan data manual tetap menjadi fallback.</p>
                        </div>
                    </div>
                </div>
            </section>
        </form>
    </div>
</template>
