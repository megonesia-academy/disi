<script setup lang="ts">
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';
import OverlayLayer from '@/components/player/OverlayLayer.vue';
import SlideContent from '@/components/playlists/SlideContent.vue';
import { type OverlayEntry } from '@/lib/overlays';
import { type SlideItem, type SlideMedia } from '@/lib/slide';

const props = defineProps<{
    item: SlideItem | null;
    mediaAssets: SlideMedia[];
    overlays: OverlayEntry[];
    brandLabel: string;
    title: string;
    nextTitle: string | null;
    index: number;
    total: number;
}>();

const now = ref(new Date());
const mounted = ref(false);

let clockTimer: number | undefined;

const overlayItem = computed(() => {
    if (!props.item) {
        return null;
    }

    return {
        title: props.title || null,
        nextTitle: props.nextTitle,
        elapsedSeconds: 0,
        durationSeconds: Math.max(props.item.duration_seconds ?? 0, 1),
    };
});

onMounted(() => {
    mounted.value = true;
    clockTimer = window.setInterval(() => {
        now.value = new Date();
    }, 1000);
});

onBeforeUnmount(() => {
    if (clockTimer) {
        window.clearInterval(clockTimer);
    }
});
</script>

<template>
    <div
        class="relative aspect-video w-full overflow-hidden rounded-lg border border-white/10 bg-black text-white shadow-2xl ring-1 ring-black/5"
        data-playlist-preview
    >
        <template v-if="item">
            <SlideContent :item="item" :media-assets="mediaAssets" />

            <OverlayLayer
                v-if="mounted"
                :overlays="overlays"
                :now="now"
                :brand-label="brandLabel"
                :screen="{ name: 'Playlist preview', location: null }"
                :item="overlayItem"
                :schedule="null"
            />

            <div
                class="pointer-events-none absolute inset-0 rounded-lg ring-1 ring-white/5 ring-inset"
            />
        </template>

        <div
            v-else
            class="grid h-full place-items-center gap-1 text-center text-sm text-white/40"
        >
            <span>Belum ada slide</span>
            <span class="text-xs">Tambahkan item untuk memulai playlist.</span>
        </div>
    </div>
</template>
