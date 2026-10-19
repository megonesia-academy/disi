<script setup lang="ts">
import EmptyState from '@/components/EmptyState.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    CalendarDays,
    Clock3,
    Copy,
    Eye,
    Layers,
    Library,
    Pencil,
    Plus,
    Search,
    Sparkles,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';
import { isImageMedia, isVideoMedia } from '@/lib/media';

type Media = {
    id: string | null;
    name: string | null;
    type: string | null;
    url: string | null;
};

type ItemOverlay = {
    type: 'ticker' | 'clock' | 'weather';
    is_active?: boolean;
};

type PlaylistItem = {
    id: string;
    media_asset_id: string | null;
    template: string;
    template_id: string | null;
    api_connection_id: string | null;
    template_definition: { id: string; name: string } | null;
    content: Record<string, unknown> | null;
    sort_order: number;
    duration_seconds: number;
    transition: string | null;
    config: {
        fit?: 'cover' | 'contain' | 'fill';
        overlays?: ItemOverlay[];
    } | null;
    media: Media | null;
};

type PlaylistOverlay = {
    id: string;
    type: 'ticker' | 'clock' | 'weather';
    config: Record<string, unknown> | null;
    sort_order: number;
    is_active: boolean;
};

type Playlist = {
    id: string;
    name: string;
    description: string | null;
    is_active: boolean;
    status: 'draft' | 'published';
    schedules_count: number;
    updated_at: string | null;
    updated_at_iso: string | null;
    overlays: PlaylistOverlay[];
    items: PlaylistItem[];
};

type PlaylistUsage = {
    playlists: number;
    playlist_limit: number;
    free_playlists: number;
    free_items: number;
    item_cost: number;
    balance: number;
    can_add_playlist: boolean;
};

const props = defineProps<{
    tenant: { id: string; name: string };
    playlists: Playlist[];
    usage: PlaylistUsage;
}>();

const form = useForm({});

const search = ref('');
const statusFilter = ref<'all' | 'published' | 'draft'>('all');
const stateFilter = ref<'all' | 'active' | 'inactive'>('all');
const sortBy = ref<'name' | 'newest' | 'items' | 'duration'>('name');
const expandedIds = ref<Set<string>>(new Set());
const deletingId = ref<string | null>(null);
const duplicatingId = ref<string | null>(null);
const previewOpen = ref(false);
const previewMedia = ref<Media | null>(null);
const previewLabel = ref('');

const publishedCount = computed(
    () =>
        props.playlists.filter((playlist) => playlist.status === 'published')
            .length,
);

const draftCount = computed(
    () =>
        props.playlists.filter((playlist) => playlist.status === 'draft')
            .length,
);

const totalItems = computed(() =>
    props.playlists.reduce(
        (total, playlist) => total + playlist.items.length,
        0,
    ),
);

const totalDuration = computed(() =>
    props.playlists.reduce(
        (total, playlist) => total + itemDuration(playlist),
        0,
    ),
);

const visiblePlaylists = computed(() => {
    const term = search.value.trim().toLowerCase();

    const list = props.playlists.filter((playlist) => {
        const matchesTerm =
            term === '' ||
            playlist.name.toLowerCase().includes(term) ||
            (playlist.description ?? '').toLowerCase().includes(term);
        const matchesStatus =
            statusFilter.value === 'all' ||
            playlist.status === statusFilter.value;
        const matchesState =
            stateFilter.value === 'all' ||
            (stateFilter.value === 'active'
                ? playlist.is_active
                : !playlist.is_active);

        return matchesTerm && matchesStatus && matchesState;
    });

    return [...list].sort((a, b) => {
        if (sortBy.value === 'newest') {
            return String(b.updated_at_iso ?? '').localeCompare(
                String(a.updated_at_iso ?? ''),
            );
        }

        if (sortBy.value === 'items') {
            return (
                b.items.length - a.items.length || a.name.localeCompare(b.name)
            );
        }

        if (sortBy.value === 'duration') {
            return (
                itemDuration(b) - itemDuration(a) ||
                a.name.localeCompare(b.name)
            );
        }

        return a.name.localeCompare(b.name);
    });
});

function isExpanded(playlist: Playlist): boolean {
    return expandedIds.value.has(playlist.id);
}

function toggleExpanded(playlist: Playlist): void {
    const next = new Set(expandedIds.value);

    if (next.has(playlist.id)) {
        next.delete(playlist.id);
    } else {
        next.add(playlist.id);
    }

    expandedIds.value = next;
}

function itemDuration(playlist: Playlist): number {
    return playlist.items.reduce(
        (total, item) => total + (item.duration_seconds ?? 0),
        0,
    );
}

function formatDuration(seconds: number): string {
    if (seconds <= 0) {
        return '0s';
    }

    const minutes = Math.floor(seconds / 60);
    const remainingSeconds = seconds % 60;

    if (minutes === 0) {
        return `${remainingSeconds}s`;
    }

    return remainingSeconds > 0
        ? `${minutes}m ${remainingSeconds}s`
        : `${minutes}m`;
}

function templateLabel(item: PlaylistItem): string {
    if (item.template === 'custom' || item.template_id) {
        return item.template_definition?.name ?? 'Custom template';
    }

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
        }[item.template] ?? 'Standard media'
    );
}

function itemLabel(item: PlaylistItem): string {
    if (item.media?.name) {
        return item.media.name;
    }

    return templateLabel(item);
}

function itemTypeLabel(item: PlaylistItem): string {
    if (item.media?.type) {
        return item.media.type;
    }

    return item.template === 'custom' ? 'template' : templateLabel(item);
}

function hasItemTicker(item: PlaylistItem): boolean {
    return (item.config?.overlays ?? []).some(
        (overlay) => overlay.type === 'ticker' && overlay.is_active !== false,
    );
}

function overlayLabel(type: string): string {
    return (
        { ticker: 'Ticker', clock: 'Clock', weather: 'Weather' }[type] ?? type
    );
}

function activeOverlays(playlist: Playlist): PlaylistOverlay[] {
    return playlist.overlays.filter((overlay) => overlay.is_active);
}

function openPreview(item: PlaylistItem): void {
    if (!item.media?.url) {
        return;
    }

    previewMedia.value = item.media;
    previewLabel.value = `${itemLabel(item)} · ${item.duration_seconds}s · ${templateLabel(item)}`;
    previewOpen.value = true;
}

function destroyPlaylist(playlist: Playlist): void {
    if (!window.confirm(`Delete playlist "${playlist.name}"?`)) {
        return;
    }

    deletingId.value = playlist.id;
    form.delete(`/playlists/${playlist.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

function duplicatePlaylist(playlist: Playlist): void {
    duplicatingId.value = playlist.id;
    router.post(
        `/playlists/${playlist.id}/duplicate`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                duplicatingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Playlists" />

    <div
        class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-workspace p-4 pb-24 md:p-6 md:pb-24"
    >
        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
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
                        Susun urutan konten untuk {{ tenant.name }} dan pantau
                        isi playlist tanpa membuka editor.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <Button variant="outline" as-child>
                        <Link href="/media-assets">
                            <Library class="size-4" />
                            Media library
                        </Link>
                    </Button>
                    <Button variant="outline" as-child>
                        <Link href="/schedules">
                            <CalendarDays class="size-4" />
                            Schedules
                        </Link>
                    </Button>
                    <Button v-if="usage.can_add_playlist" as-child>
                        <Link href="/playlists/create">
                            <Plus class="size-4" />
                            New playlist
                        </Link>
                    </Button>
                    <Button
                        v-else
                        disabled
                        title="Batas playlist tercapai. Beli slot playlist tambahan di halaman Billing."
                    >
                        <Plus class="size-4" />
                        New playlist
                    </Button>
                </div>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="rounded-md border bg-background p-4">
                    <div class="text-sm text-muted-foreground">
                        Playlists
                        <span class="text-xs"
                            >({{ usage.playlists }}/{{
                                usage.playlist_limit
                            }})</span
                        >
                    </div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ playlists.length }}
                    </div>
                </div>
                <div class="rounded-md border bg-background p-4">
                    <div class="text-sm text-muted-foreground">Published</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ publishedCount }}
                    </div>
                </div>
                <div class="rounded-md border bg-background p-4">
                    <div class="text-sm text-muted-foreground">Draft</div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ draftCount }}
                    </div>
                </div>
                <div class="rounded-md border bg-background p-4">
                    <div class="text-sm text-muted-foreground">
                        Items · duration
                    </div>
                    <div class="mt-2 text-2xl font-semibold">
                        {{ totalItems }}
                        <span class="text-sm font-normal text-muted-foreground">
                            · {{ formatDuration(totalDuration) }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <div
            v-if="!usage.can_add_playlist"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
        >
            <span class="min-w-0 flex-1">
                Batas playlist paket Anda tercapai ({{ usage.playlists }}/{{
                    usage.playlist_limit
                }}). Beli slot playlist tambahan untuk membuat playlist baru.
                Item ke-{{ usage.free_items + 1 }} dan seterusnya didebit
                {{ usage.item_cost }} token per item.
            </span>
            <Link
                href="/workspace/billing"
                class="shrink-0 font-semibold underline underline-offset-4"
            >
                Buka billing
            </Link>
        </div>

        <section
            class="flex flex-wrap items-center gap-2 rounded-lg border bg-background p-3 shadow-sm"
        >
            <div class="relative min-w-56 flex-1">
                <Search
                    class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Cari nama atau deskripsi playlist..."
                />
            </div>
            <select
                v-model="statusFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua status</option>
                <option value="published">Published</option>
                <option value="draft">Draft</option>
            </select>
            <select
                v-model="stateFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua aktif</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select
                v-model="sortBy"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="name">Nama A–Z</option>
                <option value="newest">Terbaru</option>
                <option value="items">Item terbanyak</option>
                <option value="duration">Durasi terpanjang</option>
            </select>
            <span class="ml-auto text-xs text-muted-foreground">
                {{ visiblePlaylists.length }} dari
                {{ playlists.length }} playlist
            </span>
        </section>

        <section v-if="visiblePlaylists.length" class="space-y-4">
            <article
                v-for="playlist in visiblePlaylists"
                :key="playlist.id"
                class="overflow-hidden rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
            >
                <div
                    class="flex flex-wrap items-start justify-between gap-3 border-b p-4"
                >
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-2">
                            <h2
                                class="truncate text-lg font-semibold tracking-tight"
                            >
                                {{ playlist.name }}
                            </h2>
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="
                                    playlist.status === 'published'
                                        ? 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300'
                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400'
                                "
                            >
                                {{
                                    playlist.status === 'published'
                                        ? 'Published'
                                        : 'Draft'
                                }}
                            </span>
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="
                                    playlist.is_active
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400'
                                "
                            >
                                <span
                                    class="size-1.5 rounded-full"
                                    :class="
                                        playlist.is_active
                                            ? 'bg-emerald-500'
                                            : 'bg-zinc-400'
                                    "
                                />
                                {{ playlist.is_active ? 'Active' : 'Inactive' }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-muted-foreground">
                            {{
                                playlist.description ??
                                'Belum ada deskripsi untuk playlist ini.'
                            }}
                        </p>

                        <div
                            class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-1 text-[11px] text-muted-foreground"
                        >
                            <span class="inline-flex items-center gap-1">
                                <Layers class="size-3.5" />
                                {{ playlist.items.length }} item
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <Clock3 class="size-3.5" />
                                {{ formatDuration(itemDuration(playlist)) }}
                            </span>
                            <span class="inline-flex items-center gap-1">
                                <CalendarDays class="size-3.5" />
                                {{
                                    playlist.schedules_count
                                        ? `${playlist.schedules_count} schedule`
                                        : 'Belum dijadwalkan'
                                }}
                            </span>
                            <span
                                v-for="overlay in activeOverlays(playlist)"
                                :key="overlay.id"
                                class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5"
                            >
                                <Sparkles class="size-3" />
                                {{ overlayLabel(overlay.type) }}
                            </span>
                            <span
                                >· diperbarui
                                {{ playlist.updated_at ?? '-' }}</span
                            >
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="`/playlists/${playlist.id}/edit`">
                                <Pencil class="size-4" />
                                Edit
                            </Link>
                        </Button>
                        <SubmitButton
                            type="button"
                            size="sm"
                            variant="outline"
                            title="Duplicate playlist"
                            loading-label="Menduplikasi..."
                            :processing="duplicatingId === playlist.id"
                            @click="duplicatePlaylist(playlist)"
                        >
                            <Copy class="size-4" />
                            Duplicate
                        </SubmitButton>
                        <SubmitButton
                            type="button"
                            size="sm"
                            variant="destructive"
                            title="Delete playlist"
                            aria-label="Delete playlist"
                            loading-label="Menghapus..."
                            :processing="deletingId === playlist.id"
                            @click="destroyPlaylist(playlist)"
                        >
                            <Trash2 class="size-4" />
                            Delete
                        </SubmitButton>
                    </div>
                </div>

                <div
                    class="flex flex-wrap items-center justify-between gap-2 border-b bg-workspace px-4 py-2 text-xs text-muted-foreground"
                >
                    <button
                        type="button"
                        class="inline-flex items-center gap-1.5 font-medium text-foreground hover:underline"
                        @click="toggleExpanded(playlist)"
                    >
                        {{
                            isExpanded(playlist)
                                ? 'Sembunyikan item'
                                : `Lihat ${playlist.items.length} item`
                        }}
                    </button>
                    <span>Diputar dari atas ke bawah</span>
                </div>

                <div
                    v-if="!playlist.items.length"
                    class="p-6 text-sm text-muted-foreground"
                >
                    Playlist ini belum punya item. Buka editor untuk menambah
                    media atau template.
                </div>

                <ol v-else-if="isExpanded(playlist)" class="divide-y">
                    <li
                        v-for="(item, index) in playlist.items"
                        :key="item.id"
                        class="flex items-center gap-3 p-3"
                    >
                        <span
                            class="grid size-8 shrink-0 place-items-center rounded-md bg-muted text-xs font-semibold"
                        >
                            {{ index + 1 }}
                        </span>

                        <button
                            type="button"
                            class="group relative grid h-14 w-24 shrink-0 place-items-center overflow-hidden rounded-md border bg-muted text-left"
                            :class="
                                item.media?.url
                                    ? 'cursor-pointer'
                                    : 'cursor-default'
                            "
                            :aria-label="`Preview ${itemLabel(item)}`"
                            :title="
                                item.media?.url
                                    ? 'Preview media'
                                    : 'Preview tidak tersedia'
                            "
                            @click="openPreview(item)"
                        >
                            <img
                                v-if="
                                    isImageMedia(item.media?.type) &&
                                    item.media?.url
                                "
                                :src="item.media.url"
                                :alt="itemLabel(item)"
                                class="h-full w-full object-cover"
                            />
                            <span
                                v-else
                                class="px-1 text-center text-[10px] font-semibold text-muted-foreground uppercase"
                            >
                                {{ itemTypeLabel(item) }}
                            </span>
                            <span
                                v-if="item.media?.url"
                                class="absolute inset-0 grid place-items-center bg-black/45 text-white opacity-0 transition-opacity group-hover:opacity-100"
                            >
                                <Eye class="size-4" />
                            </span>
                        </button>

                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-medium">
                                {{ itemLabel(item) }}
                            </p>
                            <div
                                class="mt-0.5 flex flex-wrap items-center gap-x-2 gap-y-1 text-[11px] text-muted-foreground"
                            >
                                <span>{{ templateLabel(item) }}</span>
                                <span>·</span>
                                <span>{{ item.duration_seconds }}s</span>
                                <span>·</span>
                                <span>
                                    fit {{ item.config?.fit ?? 'cover' }}
                                </span>
                                <span v-if="item.transition">
                                    · {{ item.transition }}
                                </span>
                                <span
                                    v-if="hasItemTicker(item)"
                                    class="inline-flex rounded-full bg-amber-100 px-1.5 font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                                >
                                    ticker
                                </span>
                                <span
                                    v-if="item.api_connection_id"
                                    class="inline-flex rounded-full bg-cyan-100 px-1.5 font-medium text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300"
                                >
                                    API
                                </span>
                            </div>
                        </div>

                        <Button
                            v-if="item.media?.url"
                            type="button"
                            size="icon"
                            variant="ghost"
                            title="Preview media"
                            :aria-label="`Preview ${itemLabel(item)}`"
                            @click="openPreview(item)"
                        >
                            <Eye class="size-4" />
                        </Button>
                    </li>
                </ol>
            </article>
        </section>

        <section
            v-else-if="playlists.length"
            class="rounded-lg border border-dashed bg-background p-10 text-center"
        >
            <div
                class="mx-auto grid size-12 place-items-center rounded-full bg-muted"
            >
                <Search class="size-6 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">
                Tidak ada playlist yang cocok
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Coba ubah kata kunci atau filter status.
            </p>
            <div class="mt-5 flex justify-center">
                <Button
                    type="button"
                    variant="outline"
                    @click="
                        search = '';
                        statusFilter = 'all';
                        stateFilter = 'all';
                    "
                >
                    <X class="size-4" /> Reset filter
                </Button>
            </div>
        </section>

        <section v-else class="rounded-lg border bg-background shadow-sm">
            <EmptyState
                title="Buat playlist pertama"
                description="Playlist adalah urutan konten yang diputar di display. Tambahkan media, atur durasi, lalu schedule ke screen."
                action-label="Buat playlist"
                action-href="/playlists/create"
                secondary-label="Upload media"
                secondary-href="/media-assets"
            />
        </section>

        <Dialog v-model:open="previewOpen">
            <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>
                        {{ previewMedia?.name ?? 'Media preview' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ previewLabel }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="previewMedia"
                    class="grid max-h-[64vh] min-h-64 place-items-center overflow-hidden rounded-md bg-black p-2"
                >
                    <img
                        v-if="isImageMedia(previewMedia?.type)"
                        :src="previewMedia?.url ?? undefined"
                        :alt="previewMedia?.name ?? 'Media preview'"
                        class="max-h-[60vh] max-w-full object-contain"
                    />
                    <video
                        v-else-if="isVideoMedia(previewMedia?.type)"
                        :src="previewMedia?.url ?? undefined"
                        class="max-h-[60vh] w-full"
                        controls
                        autoplay
                    />
                    <audio
                        v-else-if="previewMedia?.type === 'audio'"
                        :src="previewMedia?.url ?? undefined"
                        class="w-full max-w-xl"
                        controls
                        autoplay
                    />
                    <iframe
                        v-else
                        :src="previewMedia?.url ?? undefined"
                        :title="previewMedia?.name ?? 'Media preview'"
                        class="h-[60vh] w-full border-0 bg-white"
                    />
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
