<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowDown,
    ArrowUp,
    CalendarDays,
    Copy,
    Eye,
    GripVertical,
    Library,
    Pencil,
    Plus,
    Save,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type MediaAsset = {
    id: string;
    name: string;
    type: string;
    url: string;
    duration_seconds: number | null;
};

type TickerOverlay = {
    type: 'ticker';
    text: string;
    position: 'top' | 'bottom';
    speed: number;
    text_color: string;
    background_color: string;
    source?: 'static' | 'array';
    items?: string[];
    mode?: 'loop' | 'rotate';
    item_duration_seconds?: number;
    format?: '24h' | '12h';
};

type PlaylistOverlay = {
    id?: string;
    type: 'ticker' | 'clock';
    config: TickerOverlay;
    is_active: boolean;
};

type PlaylistItem = {
    id?: string;
    media_asset_id: string | null;
    template?: string;
    content?: Record<string, unknown>;
    sort_order?: number;
    duration_seconds: number;
    transition: string | null;
    config?: {
        fit?: 'cover' | 'contain' | 'fill';
        overlays?: TickerOverlay[];
    } | null;
    media?: {
        id: string;
        name: string;
        type: string;
        url?: string;
    };
};

type Playlist = {
    id: string;
    name: string;
    description: string | null;
    is_active: boolean;
    status: 'draft' | 'published';
    overlays: PlaylistOverlay[];
    items: PlaylistItem[];
};

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
    };
    playlists: Playlist[];
    mediaAssets: MediaAsset[];
}>();

const editingId = ref<string | null>(null);
const draggedItemIndex = ref<number | null>(null);
const dropTargetIndex = ref<number | null>(null);
const previewMedia = ref<PlaylistItem['media'] | null>(null);

const form = useForm<{
    name: string;
    description: string;
    is_active: boolean;
    status: 'draft' | 'published';
    overlays: PlaylistOverlay[];
    items: PlaylistItem[];
}>({
    name: '',
    description: '',
    is_active: true,
    status: 'draft',
    overlays: [],
    items: [],
});

const editingPlaylist = computed(() => {
    return props.playlists.find((playlist) => playlist.id === editingId.value);
});

const totalItems = computed(() => {
    return props.playlists.reduce(
        (total, playlist) => total + playlist.items.length,
        0,
    );
});

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.is_active = true;
    form.status = 'draft';
    form.overlays = [];
    form.items = [];
}

function editPlaylist(playlist: Playlist): void {
    editingId.value = playlist.id;
    form.clearErrors();
    form.name = playlist.name;
    form.description = playlist.description ?? '';
    form.is_active = playlist.is_active;
    form.status = playlist.status;
    form.overlays = playlist.overlays.map((overlay) => ({
        id: overlay.id,
        type: overlay.type,
        is_active: overlay.is_active,
        config: {
            ...overlay.config,
            position: overlay.config.position ?? 'bottom',
            speed: overlay.config.speed ?? 60,
            text_color: overlay.config.text_color ?? '#ffffff',
            background_color: overlay.config.background_color ?? '#111827',
            format: overlay.config.format ?? '24h',
        },
    }));
    form.items = playlist.items.map((item) => ({
        media_asset_id: item.media_asset_id,
        template: item.template ?? 'media',
        content: item.content ?? {},
        duration_seconds: item.duration_seconds,
        transition: item.transition ?? 'fade',
        config: {
            fit: item.config?.fit ?? 'cover',
            overlays: item.config?.overlays ?? [],
        },
    }));
}

function addItem(): void {
    const firstMedia = props.mediaAssets[0];

    if (!firstMedia) {
        return;
    }

    form.items.push({
        media_asset_id: firstMedia.id,
        template: 'media',
        content: {},
        duration_seconds: firstMedia.duration_seconds ?? 10,
        transition: 'fade',
        config: { fit: 'cover', overlays: [] },
    });
}

function removeItem(index: number): void {
    form.items.splice(index, 1);
}

function hasTickerOverlay(item: PlaylistItem): boolean {
    return (item.config?.overlays ?? []).some(
        (overlay) => overlay.type === 'ticker',
    );
}

function toggleTickerOverlay(item: PlaylistItem, enabled: boolean): void {
    if (!item.config) {
        item.config = { fit: 'cover', overlays: [] };
    }

    if (!item.config.overlays) {
        item.config.overlays = [];
    }

    if (enabled && !hasTickerOverlay(item)) {
        item.config.overlays.push({
            type: 'ticker',
            text: 'Informasi terbaru untuk pengunjung',
            position: 'bottom',
            speed: 60,
            text_color: '#ffffff',
            background_color: '#111827',
        });
    }

    if (!enabled) {
        item.config.overlays = item.config.overlays.filter(
            (overlay) => overlay.type !== 'ticker',
        );
    }
}

function onTickerToggle(item: PlaylistItem, event: Event): void {
    toggleTickerOverlay(
        item,
        (event.currentTarget as HTMLInputElement).checked,
    );
}

function tickerOverlay(item: PlaylistItem): TickerOverlay | undefined {
    return item.config?.overlays?.find(
        (overlay): overlay is TickerOverlay => overlay.type === 'ticker',
    );
}

function hasGlobalTicker(): boolean {
    return form.overlays.some(
        (overlay) => overlay.type === 'ticker' && overlay.is_active,
    );
}

function toggleGlobalTicker(enabled: boolean): void {
    const ticker = form.overlays.find((overlay) => overlay.type === 'ticker');

    if (ticker) {
        ticker.is_active = enabled;
        return;
    }

    if (enabled) {
        form.overlays.push({
            type: 'ticker',
            is_active: true,
            config: {
                type: 'ticker',
                text: 'Informasi terbaru untuk pengunjung',
                position: 'bottom',
                speed: 60,
                text_color: '#ffffff',
                background_color: '#111827',
                source: 'static',
                items: [],
                mode: 'rotate',
                item_duration_seconds: 8,
            },
        });
    }
}

function onGlobalTickerToggle(event: Event): void {
    toggleGlobalTicker(
        (event.currentTarget as HTMLInputElement).checked,
    );
}

function hasGlobalClock(): boolean {
    return form.overlays.some(
        (overlay) => overlay.type === 'clock' && overlay.is_active,
    );
}

function toggleGlobalClock(enabled: boolean): void {
    const clock = form.overlays.find((overlay) => overlay.type === 'clock');

    if (clock) {
        clock.is_active = enabled;
        return;
    }

    if (enabled) {
        form.overlays.push({
            type: 'clock',
            is_active: true,
            config: {
                type: 'ticker',
                text: '',
                position: 'top',
                speed: 60,
                text_color: '#ffffff',
                background_color: '#111827',
                format: '24h',
            },
        });
    }
}

function onGlobalClockToggle(event: Event): void {
    toggleGlobalClock(
        (event.currentTarget as HTMLInputElement).checked,
    );
}

function moveItem(index: number, direction: -1 | 1): void {
    const target = index + direction;

    if (target < 0 || target >= form.items.length) {
        return;
    }

    reorderItems(index, target);
}

function reorderItems(fromIndex: number, toIndex: number): void {
    if (
        fromIndex === toIndex ||
        fromIndex < 0 ||
        toIndex < 0 ||
        fromIndex >= form.items.length ||
        toIndex >= form.items.length
    ) {
        return;
    }

    const [item] = form.items.splice(fromIndex, 1);
    form.items.splice(toIndex, 0, item);
}

function startDragging(index: number, event: DragEvent): void {
    draggedItemIndex.value = index;
    dropTargetIndex.value = index;

    if (event.dataTransfer) {
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', String(index));
    }
}

function updateDropTarget(index: number, event: DragEvent): void {
    event.preventDefault();

    if (draggedItemIndex.value !== null) {
        dropTargetIndex.value = index;
    }
}

function dropItem(index: number, event: DragEvent): void {
    event.preventDefault();

    if (draggedItemIndex.value !== null) {
        reorderItems(draggedItemIndex.value, index);
    }

    stopDragging();
}

function stopDragging(): void {
    draggedItemIndex.value = null;
    dropTargetIndex.value = null;
}

function mediaAsset(mediaAssetId: string): MediaAsset | undefined {
    return props.mediaAssets.find((asset) => asset.id === mediaAssetId);
}

function mediaName(mediaAssetId: string): string {
    return mediaAsset(mediaAssetId)?.name ?? 'Unknown media';
}

function openMediaPreview(item: PlaylistItem): void {
    previewMedia.value = item.media ?? null;
}

function closeMediaPreview(): void {
    previewMedia.value = null;
}

function itemDuration(playlist: Playlist): number {
    return playlist.items.reduce(
        (total, item) => total + item.duration_seconds,
        0,
    );
}

function formatDuration(seconds: number): string {
    if (seconds < 60) {
        return `${seconds}s`;
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    return remainingSeconds > 0
        ? `${minutes}m ${remainingSeconds}s`
        : `${minutes}m`;
}

function submit(): void {
    if (editingId.value) {
        form.put(`/playlists/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: resetForm,
        });

        return;
    }

    form.post('/playlists', {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}

function destroyPlaylist(playlist: Playlist): void {
    if (!window.confirm(`Delete playlist "${playlist.name}"?`)) {
        return;
    }

    form.delete(`/playlists/${playlist.id}`, {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}

function duplicatePlaylist(playlist: Playlist): void {
    router.post(`/playlists/${playlist.id}/duplicate`, {}, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Playlists" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-muted/20 p-4 md:p-6"
    >
        <section class="overflow-hidden rounded-lg border bg-background shadow-sm">
            <div
                class="flex flex-wrap items-end justify-between gap-5 border-b bg-linear-to-br from-background via-background to-muted/60 p-6"
            >
                <div class="max-w-2xl">
                    <div
                        class="mb-3 inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                    >
                        Content loop
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight">
                        Playlists
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Susun urutan konten untuk {{ tenant.name }} dan lihat
                        item playlist tanpa harus membuka edit.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Link
                        href="/media-assets"
                        class="inline-flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                    >
                        <Library class="size-4" />
                        Media library
                    </Link>
                    <Link
                        href="/schedules"
                        class="inline-flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                    >
                        <CalendarDays class="size-4" />
                        Schedules
                    </Link>
                    <Link
                        href="/playlists/create"
                        class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                    >
                        <Plus class="size-4" />
                        New playlist
                    </Link>
                </div>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-3">
                <div class="rounded-md border bg-background p-4">
                    <div class="text-sm text-muted-foreground">Playlists</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ playlists.length }}
                    </div>
                </div>
                <div class="rounded-md border bg-background p-4">
                    <div class="text-sm text-muted-foreground">Items</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ totalItems }}
                    </div>
                </div>
                <div class="rounded-md border bg-background p-4">
                    <div class="text-sm text-muted-foreground">Media ready</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ mediaAssets.length }}
                    </div>
                </div>
            </div>
        </section>

        <section
            class="block"
        >
            <form
                class="hidden"
                @submit.prevent="submit"
            >
                <div class="mb-5 flex items-start justify-between gap-3">
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight">
                            {{
                                editingPlaylist
                                    ? 'Edit playlist'
                                    : 'New playlist'
                            }}
                        </h2>
                        <p class="text-sm text-muted-foreground">
                            Tambahkan media sesuai urutan tampil di layar.
                        </p>
                    </div>
                    <span
                        class="rounded-full bg-muted px-2.5 py-1 text-xs font-medium text-muted-foreground"
                    >
                        {{ form.items.length }} item
                    </span>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Name
                        </label>
                        <Input
                            v-model="form.name"
                            placeholder="Lobby morning loop"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Description
                        </label>
                        <textarea
                            v-model="form.description"
                            class="border-input min-h-20 w-full rounded-md border bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            placeholder="Catatan singkat untuk tim operasional"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <label
                        class="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm"
                    >
                        <span>
                            <span class="font-medium">Active</span>
                            <span class="block text-xs text-muted-foreground">
                                Playlist aktif bisa dipakai schedule.
                            </span>
                        </span>
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        />
                    </label>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Publishing status
                        </label>
                        <select
                            v-model="form.status"
                            class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="draft">Draft</option>
                            <option value="published">Published</option>
                        </select>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Draft tidak akan diputar di display.
                        </p>
                    </div>

                    <div class="rounded-md border bg-muted/20 p-3">
                        <label class="flex items-center gap-2 text-sm font-medium">
                            <input
                                type="checkbox"
                                :checked="hasGlobalTicker()"
                                class="size-4 rounded border-input"
                                @change="onGlobalTickerToggle($event)"
                            />
                            Global ticker overlay
                        </label>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Ticker ini tampil di semua item playlist.
                        </p>

                        <label class="mt-3 flex items-center gap-2 text-sm font-medium">
                            <input
                                type="checkbox"
                                :checked="hasGlobalClock()"
                                class="size-4 rounded border-input"
                                @change="onGlobalClockToggle($event)"
                            />
                            Clock overlay
                        </label>

                        <template v-for="overlay in form.overlays" :key="overlay.id ?? overlay.type">
                            <div
                                v-if="overlay.type === 'ticker' && overlay.is_active"
                                class="mt-3 grid gap-3 sm:grid-cols-2"
                            >
                                <div
                                    v-if="overlay.config.source !== 'array'"
                                    class="sm:col-span-2"
                                >
                                    <label class="mb-1 block text-xs font-medium">
                                        Text
                                    </label>
                                    <Input v-model="overlay.config.text" />
                                </div>
                                <div>
                                <label class="mb-1 block text-xs font-medium">
                                    Source
                                </label>
                                <select
                                    v-model="overlay.config.source"
                                    class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option value="static">Static text</option>
                                    <option value="array">Text array</option>
                                </select>
                                </div>
                                <div v-if="overlay.config.source === 'array'" class="sm:col-span-2">
                                    <label class="mb-1 block text-xs font-medium">
                                        Text array (one message per line)
                                    </label>
                                    <textarea
                                        :value="(overlay.config.items ?? []).join('\n')"
                                        class="border-input min-h-20 w-full rounded-md border bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        @input="
                                            overlay.config.items = (
                                                ($event.target as HTMLTextAreaElement).value
                                            ).split('\n').filter(Boolean)
                                        "
                                    />
                                </div>
                                <div>
                                <label class="mb-1 block text-xs font-medium">
                                    Position
                                </label>
                                <select
                                    v-model="overlay.config.position"
                                    class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option value="bottom">Bottom</option>
                                    <option value="top">Top</option>
                                </select>
                                </div>
                                <div>
                                <label class="mb-1 block text-xs font-medium">
                                    Speed
                                </label>
                                <Input
                                    v-model="overlay.config.speed"
                                    type="number"
                                    min="10"
                                    max="200"
                                />
                                </div>
                                <div>
                                <label class="mb-1 block text-xs font-medium">
                                    Text color
                                </label>
                                <Input
                                    v-model="overlay.config.text_color"
                                    type="color"
                                    class="h-9 p-1"
                                />
                                </div>
                                <div>
                                <label class="mb-1 block text-xs font-medium">
                                    Background color
                                </label>
                                <Input
                                    v-model="overlay.config.background_color"
                                    type="color"
                                    class="h-9 p-1"
                                />
                             </div>
                             </div>
                             <div
                                 v-if="overlay.type === 'clock' && overlay.is_active"
                                 class="mt-3 grid gap-3 sm:grid-cols-2"
                             >
                                 <div>
                                     <label class="mb-1 block text-xs font-medium">
                                         Clock format
                                     </label>
                                     <select
                                         v-model="overlay.config.format"
                                         class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                     >
                                         <option value="24h">24 hour</option>
                                         <option value="12h">12 hour (AM/PM)</option>
                                     </select>
                                 </div>
                             </div>
                         </template>
                    </div>

                    <div class="overflow-hidden rounded-lg border">
                        <div
                            class="flex items-center justify-between border-b bg-muted/35 px-3 py-2"
                        >
                            <div>
                                <div class="text-sm font-medium">
                                    Playlist items
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    Urutan dari atas ke bawah.
                                </div>
                            </div>
                            <Button
                                type="button"
                                size="sm"
                                variant="outline"
                                :disabled="mediaAssets.length === 0"
                                @click="addItem"
                            >
                                <Plus class="size-4" />
                                Add item
                            </Button>
                        </div>

                        <div
                            v-if="mediaAssets.length === 0"
                            class="px-4 py-8 text-sm text-muted-foreground"
                        >
                            Upload media terlebih dahulu sebelum membuat
                            playlist.
                        </div>

                        <div
                            v-else-if="form.items.length === 0"
                            class="px-4 py-8 text-sm text-muted-foreground"
                        >
                            Belum ada item. Klik Add item untuk mulai menyusun
                            playlist.
                        </div>

                        <div
                            v-for="(item, index) in form.items"
                            :key="index"
                            class="space-y-3 border-b p-3 transition-colors last:border-b-0"
                            :class="
                                dropTargetIndex === index &&
                                draggedItemIndex !== index
                                    ? 'bg-primary/10'
                                    : ''
                            "
                            @dragover="updateDropTarget(index, $event)"
                            @drop="dropItem(index, $event)"
                        >
                            <div class="flex items-center justify-between gap-3">
                                <div class="flex min-w-0 items-center gap-3">
                                    <span
                                        draggable="true"
                                        class="grid size-8 shrink-0 cursor-grab place-items-center rounded-md border border-dashed text-muted-foreground transition-colors hover:border-primary hover:text-primary active:cursor-grabbing"
                                        title="Drag untuk mengubah urutan"
                                        aria-label="Drag untuk mengubah urutan"
                                        @dragstart="startDragging(index, $event)"
                                        @dragend="stopDragging"
                                    >
                                        <GripVertical class="size-4" />
                                    </span>
                                    <div
                                        class="grid size-9 shrink-0 place-items-center rounded-md bg-primary text-sm font-semibold text-primary-foreground"
                                    >
                                        {{ index + 1 }}
                                    </div>
                                    <div class="min-w-0">
                                        <div class="truncate text-sm font-medium">
                                            {{
                                                mediaName(item.media_asset_id)
                                            }}
                                        </div>
                                        <div class="text-xs text-muted-foreground">
                                            {{
                                                mediaAsset(item.media_asset_id)
                                                    ?.type ?? 'media'
                                            }}
                                        </div>
                                    </div>
                                </div>
                                <div class="flex gap-1">
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        :disabled="index === 0"
                                        @click="moveItem(index, -1)"
                                    >
                                        <ArrowUp class="size-4" />
                                        Up
                                    </Button>
                                    <Button
                                        type="button"
                                        size="sm"
                                        variant="outline"
                                        :disabled="
                                            index === form.items.length - 1
                                        "
                                        @click="moveItem(index, 1)"
                                    >
                                        <ArrowDown class="size-4" />
                                        Down
                                    </Button>
                                </div>
                            </div>

                            <select
                                v-model="item.media_asset_id"
                                class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option
                                    v-for="asset in mediaAssets"
                                    :key="asset.id"
                                    :value="asset.id"
                                >
                                    {{ asset.name }} · {{ asset.type }}
                                </option>
                            </select>

                            <div class="grid grid-cols-[1fr_1fr_1fr_auto] gap-3">
                                <div>
                                    <label class="mb-1 block text-xs font-medium">
                                        Duration
                                    </label>
                                    <Input
                                        v-model="item.duration_seconds"
                                        type="number"
                                        min="1"
                                    />
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium">
                                        Transition
                                    </label>
                                    <select
                                        v-model="item.transition"
                                        class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    >
                                        <option value="none">None</option>
                                        <option value="fade">Fade</option>
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
                                        <option value="zoom">Zoom</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="mb-1 block text-xs font-medium">
                                        Media fit
                                    </label>
                                    <select
                                        v-model="item.config!.fit"
                                        class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    >
                                        <option value="cover">Cover</option>
                                        <option value="contain">Contain</option>
                                        <option value="fill">Fill</option>
                                    </select>
                                </div>
                                <div class="flex items-end">
                                    <Button
                                        type="button"
                                        variant="destructive"
                                        @click="removeItem(index)"
                                    >
                                        <Trash2 class="size-4" />
                                        Remove
                                    </Button>
                                </div>
                            </div>

                            <div class="rounded-md border bg-muted/20 p-3">
                                <label class="flex items-center gap-2 text-sm font-medium">
                                    <input
                                        type="checkbox"
                                        :checked="hasTickerOverlay(item)"
                                        class="size-4 rounded border-input"
                                        @change="onTickerToggle(item, $event)"
                                    />
                                    Ticker overlay
                                </label>

                                <div
                                    v-if="hasTickerOverlay(item)"
                                    class="mt-3 grid gap-3 sm:grid-cols-2"
                                >
                                    <template
                                        v-for="overlay in item.config?.overlays ?? []"
                                        :key="overlay.type"
                                    >
                                        <template v-if="overlay.type === 'ticker'">
                                            <div class="sm:col-span-2">
                                                <label class="mb-1 block text-xs font-medium">
                                                    Text
                                                </label>
                                                <Input v-model="overlay.text" />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium">
                                                    Position
                                                </label>
                                                <select
                                                    v-model="overlay.position"
                                                    class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                                >
                                                    <option value="bottom">Bottom</option>
                                                    <option value="top">Top</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium">
                                                    Speed
                                                </label>
                                                <Input
                                                    v-model="overlay.speed"
                                                    type="number"
                                                    min="10"
                                                    max="200"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium">
                                                    Text color
                                                </label>
                                                <Input
                                                    v-model="overlay.text_color"
                                                    type="color"
                                                    class="h-9 p-1"
                                                />
                                            </div>
                                            <div>
                                                <label class="mb-1 block text-xs font-medium">
                                                    Background color
                                                </label>
                                                <Input
                                                    v-model="overlay.background_color"
                                                    type="color"
                                                    class="h-9 p-1"
                                                />
                                            </div>
                                        </template>
                                    </template>
                                </div>
                            </div>
                        </div>
                    </div>

                    <InputError :message="form.errors.items" />

                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing">
                            <Save class="size-4" />
                            {{ editingPlaylist ? 'Update' : 'Create' }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="resetForm"
                        >
                            <X class="size-4" />
                            Cancel
                        </Button>
                    </div>
                </div>
            </form>

            <div class="space-y-4">
                <article
                    v-for="playlist in playlists"
                    :key="playlist.id"
                    class="overflow-hidden rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        class="flex flex-wrap items-start justify-between gap-3 border-b p-4"
                    >
                        <div class="min-w-0">
                            <div class="flex flex-wrap items-center gap-2">
                                <h2
                                    class="truncate text-lg font-semibold tracking-tight"
                                >
                                    {{ playlist.name }}
                                </h2>
                                <span
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        playlist.is_active
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                    "
                                >
                                    {{
                                        playlist.is_active
                                            ? 'active'
                                            : 'inactive'
                                    }}
                                </span>
                            </div>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ playlist.description ?? 'No description' }}
                            </p>
                        </div>

                        <div class="flex gap-2">
                            <Link
                                :href="`/playlists/${playlist.id}/edit`"
                                class="inline-flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                            >
                                <Pencil class="size-4" />
                                Edit
                            </Link>
                            <Button
                                size="sm"
                                variant="destructive"
                                @click="destroyPlaylist(playlist)"
                            >
                                <Trash2 class="size-4" />
                                Delete
                            </Button>
                            <Button
                                size="sm"
                                variant="outline"
                                title="Duplicate playlist"
                                @click="duplicatePlaylist(playlist)"
                            >
                                <Copy class="size-4" />
                                Duplicate
                            </Button>
                        </div>
                    </div>

                    <div
                        class="grid gap-3 border-b bg-muted/25 px-4 py-3 text-sm sm:grid-cols-3"
                    >
                        <div>
                            <div class="text-xs text-muted-foreground">
                                Items
                            </div>
                            <div class="font-medium">
                                {{ playlist.items.length }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground">
                                Total duration
                            </div>
                            <div class="font-medium">
                                {{ formatDuration(itemDuration(playlist)) }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground">
                                Playback
                            </div>
                            <div class="font-medium">Top to bottom</div>
                        </div>
                    </div>

                    <div
                        v-if="playlist.items.length === 0"
                        class="p-6 text-sm text-muted-foreground"
                    >
                        Playlist ini belum punya item.
                    </div>

                    <ol v-else class="divide-y">
                        <li
                            v-for="item in playlist.items"
                            :key="item.id"
                            class="flex gap-3 p-4"
                        >
                            <div
                                class="grid size-10 shrink-0 place-items-center rounded-md bg-muted text-sm font-semibold"
                            >
                                {{ item.sort_order }}
                            </div>

                            <button
                                type="button"
                                class="group relative grid size-16 shrink-0 place-items-center overflow-hidden rounded-md border bg-muted text-left"
                                :aria-label="`Preview ${item.media?.name ?? 'media'}`"
                                title="Preview media"
                                @click="openMediaPreview(item)"
                            >
                                <img
                                    v-if="
                                        item.media?.type === 'image' &&
                                        item.media.url
                                    "
                                    :src="item.media.url"
                                    :alt="item.media.name"
                                    class="h-full w-full object-cover"
                                />
                                <span
                                    v-else
                                    class="text-xs font-medium uppercase text-muted-foreground"
                                >
                                    {{ item.template && item.template !== 'media' ? item.template : (item.media?.type ?? 'media') }}
                                </span>
                                <span
                                    class="absolute inset-0 grid place-items-center bg-black/45 text-white opacity-0 transition-opacity group-hover:opacity-100"
                                >
                                    <Eye class="size-5" />
                                </span>
                            </button>

                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium">
                                    {{ item.media?.name ?? 'Unknown media' }}
                                </div>
                                <div
                                    class="mt-1 flex flex-wrap gap-2 text-xs text-muted-foreground"
                                >
                                    <span>{{ item.template && item.template !== 'media' ? item.template : item.media?.type }}</span>
                                    <span>·</span>
                                    <span>
                                        {{ item.duration_seconds }} seconds
                                    </span>
                                    <span>· fit {{ item.config?.fit ?? 'cover' }}</span>
                                    <span v-if="item.transition">
                                        · {{ item.transition }}
                                    </span>
                                </div>
                            </div>
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                title="Preview media"
                                :aria-label="`Preview ${item.media?.name ?? 'media'}`"
                                @click="openMediaPreview(item)"
                            >
                                <Eye class="size-4" />
                            </Button>
                        </li>
                    </ol>
                </article>

                <div
                    v-if="playlists.length === 0"
                    class="rounded-lg border bg-background shadow-sm"
                >
                    <EmptyState
                        title="Buat playlist pertama"
                        description="Playlist adalah urutan konten yang akan diputar di display. Tambahkan media, atur durasi, lalu schedule ke screen."
                        action-label="Isi form playlist"
                        action-href="/playlists"
                        secondary-label="Upload media"
                        secondary-href="/media-assets"
                    />
                </div>
            </div>
        </section>

        <div
            v-if="previewMedia"
            class="fixed inset-0 z-50 grid place-items-center bg-black/70 p-4"
            role="dialog"
            aria-modal="true"
            @click.self="closeMediaPreview"
        >
            <div
                class="relative max-h-[90vh] w-full max-w-4xl overflow-hidden rounded-lg border bg-background shadow-2xl"
            >
                <div
                    class="flex items-center justify-between border-b px-4 py-3"
                >
                    <div class="min-w-0">
                        <div class="truncate font-medium">
                            {{ previewMedia.name }}
                        </div>
                        <div class="text-xs text-muted-foreground">
                            {{ previewMedia.type }}
                        </div>
                    </div>
                    <Button
                        type="button"
                        size="icon"
                        variant="ghost"
                        title="Close preview"
                        aria-label="Close preview"
                        @click="closeMediaPreview"
                    >
                        <X class="size-4" />
                    </Button>
                </div>

                <div class="grid max-h-[calc(90vh-73px)] min-h-80 place-items-center bg-black p-4">
                    <img
                        v-if="previewMedia.type === 'image'"
                        :src="previewMedia.url"
                        :alt="previewMedia.name"
                        class="max-h-[calc(90vh-110px)] max-w-full object-contain"
                    />
                    <video
                        v-else-if="previewMedia.type === 'video'"
                        :src="previewMedia.url"
                        class="max-h-[calc(90vh-110px)] max-w-full"
                        controls
                        autoplay
                    />
                    <audio
                        v-else-if="previewMedia.type === 'audio'"
                        :src="previewMedia.url"
                        class="w-full max-w-xl"
                        controls
                        autoplay
                    />
                    <iframe
                        v-else
                        :src="previewMedia.url"
                        :title="previewMedia.name"
                        class="h-[70vh] w-full border-0 bg-white"
                    />
                </div>
            </div>
        </div>
    </div>
</template>
