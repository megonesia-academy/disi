<script setup lang="ts">
import { ChevronLeft, ChevronRight, Pause, Play, X } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import PlaylistPreview from '@/components/playlists/PlaylistPreview.vue';
import { Button } from '@/components/ui/button';
import { type OverlayEntry } from '@/lib/overlays';
import {
    slideTemplateLabel,
    type SlideItem,
    type SlideMedia,
} from '@/lib/slide';

const props = defineProps<{
    items: SlideItem[];
    mediaAssets: SlideMedia[];
    globalOverlays: OverlayEntry[];
    brandLabel: string;
    startIndex: number;
}>();

const emit = defineEmits<{ close: [] }>();

const index = ref(
    Math.min(Math.max(props.startIndex, 0), props.items.length - 1),
);
const playing = ref(true);
const elapsed = ref(0);

let ticker: number | undefined;
let lastTick = 0;

const current = computed<SlideItem | null>(
    () => props.items[index.value] ?? null,
);

const durationMs = computed(
    () => Math.max(1, current.value?.duration_seconds ?? 5) * 1000,
);

const progress = computed(() =>
    Math.min(100, (elapsed.value / durationMs.value) * 100),
);

const overlays = computed<OverlayEntry[]>(() => {
    const itemOverlays = (current.value?.config?.overlays ?? []).map(
        (overlay, overlayIndex): OverlayEntry => ({
            id: `slideshow-item-${index.value}-${overlayIndex}`,
            type: 'ticker',
            config: overlay,
        }),
    );

    return [...props.globalOverlays, ...itemOverlays];
});

const nextTitle = computed(() => {
    if (props.items.length < 2) {
        return null;
    }

    const next = props.items[(index.value + 1) % props.items.length];

    return slideTitle(next);
});

function slideTitle(item: SlideItem | null | undefined): string {
    if (!item) {
        return '';
    }

    const title = item.content?.title;

    if (typeof title === 'string' && title.trim() !== '') {
        return title;
    }

    const media = item.media_asset_id
        ? props.mediaAssets.find((asset) => asset.id === item.media_asset_id)
        : undefined;

    return media?.name ?? slideTemplateLabel(item.template);
}

function goTo(nextIndex: number): void {
    if (props.items.length === 0) {
        return;
    }

    index.value =
        ((nextIndex % props.items.length) + props.items.length) %
        props.items.length;
    elapsed.value = 0;
    lastTick = performance.now();
}

function next(): void {
    goTo(index.value + 1);
}

function previous(): void {
    goTo(index.value - 1);
}

function toggle(): void {
    playing.value = !playing.value;
    lastTick = performance.now();
}

function tick(): void {
    const now = performance.now();
    const delta = now - lastTick;
    lastTick = now;

    if (!playing.value || props.items.length < 2) {
        return;
    }

    elapsed.value += delta;

    if (elapsed.value >= durationMs.value) {
        next();
    }
}

function onKeydown(event: KeyboardEvent): void {
    if (event.key === 'Escape') {
        emit('close');
        return;
    }

    if (event.key === 'ArrowRight') {
        event.preventDefault();
        next();
        return;
    }

    if (event.key === 'ArrowLeft') {
        event.preventDefault();
        previous();
        return;
    }

    if (event.key === ' ') {
        event.preventDefault();
        toggle();
    }
}

watch(index, () => {
    elapsed.value = 0;
    lastTick = performance.now();
});

onMounted(() => {
    lastTick = performance.now();
    ticker = window.setInterval(tick, 100);
    window.addEventListener('keydown', onKeydown);
    document.body.style.overflow = 'hidden';
});

onBeforeUnmount(() => {
    if (ticker) {
        window.clearInterval(ticker);
    }

    window.removeEventListener('keydown', onKeydown);
    document.body.style.overflow = '';
});
</script>

<template>
    <div
        class="fixed inset-0 z-[100] flex flex-col bg-zinc-950/95 text-white backdrop-blur-sm"
        role="dialog"
        aria-modal="true"
        aria-label="Pratinjau playlist"
    >
        <header
            class="flex items-center justify-between gap-3 px-4 py-3 sm:px-6"
        >
            <div class="min-w-0">
                <p
                    class="text-[10px] font-semibold tracking-[0.25em] text-white/45 uppercase"
                >
                    Pratinjau playlist
                </p>
                <p class="truncate text-sm font-medium">
                    {{ slideTitle(current) || 'Slide' }}
                </p>
            </div>
            <div class="flex items-center gap-2">
                <span class="text-xs text-white/50 tabular-nums"
                    >{{ items.length === 0 ? 0 : index + 1 }} /
                    {{ items.length }}</span
                >
                <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    class="border-white/20 bg-white/5 text-white hover:bg-white/10"
                    title="Tutup (Esc)"
                    @click="emit('close')"
                    ><X class="size-4"
                /></Button>
            </div>
        </header>

        <div
            class="flex min-h-0 flex-1 items-center justify-center px-3 sm:px-6"
        >
            <div class="w-full max-w-6xl space-y-3">
                <PlaylistPreview
                    :item="current"
                    :media-assets="mediaAssets"
                    :overlays="overlays"
                    :brand-label="brandLabel"
                    :title="slideTitle(current)"
                    :next-title="nextTitle"
                    :index="index"
                    :total="items.length"
                />
                <div
                    class="h-1 w-full overflow-hidden rounded-full bg-white/10"
                >
                    <div
                        class="h-full rounded-full bg-cyan-400 transition-[width] duration-100 ease-linear"
                        :style="{ width: `${progress}%` }"
                    />
                </div>
            </div>
        </div>

        <footer
            class="flex flex-wrap items-center justify-center gap-2 px-4 py-4 sm:justify-between sm:px-6"
        >
            <p class="hidden text-[11px] text-white/35 sm:block">
                ← → navigasi · spasi jeda · Esc tutup
            </p>
            <div class="flex items-center gap-1.5">
                <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    class="border-white/20 bg-white/5 text-white hover:bg-white/10"
                    title="Slide sebelumnya"
                    @click="previous"
                    ><ChevronLeft class="size-4"
                /></Button>
                <Button
                    type="button"
                    size="icon"
                    class="size-10"
                    :title="playing ? 'Jeda (spasi)' : 'Mainkan (spasi)'"
                    @click="toggle"
                    ><Pause v-if="playing" class="size-4" /><Play
                        v-else
                        class="size-4"
                /></Button>
                <Button
                    type="button"
                    size="icon"
                    variant="outline"
                    class="border-white/20 bg-white/5 text-white hover:bg-white/10"
                    title="Slide berikutnya"
                    @click="next"
                    ><ChevronRight class="size-4"
                /></Button>
            </div>
            <p class="text-[11px] text-white/35">
                {{ current ? slideTemplateLabel(current.template) : 'Kosong' }}
            </p>
        </footer>
    </div>
</template>
