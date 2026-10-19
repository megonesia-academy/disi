<script setup lang="ts">
import { onBeforeUnmount, onMounted, ref } from 'vue';
import SlideContent from '@/components/playlists/SlideContent.vue';
import type { SlideItem, SlideMedia } from '@/lib/slide';

const BASE_WIDTH = 320;
const BASE_HEIGHT = 180;

const props = defineProps<{
    item: SlideItem | null;
    mediaAssets: SlideMedia[];
}>();

const root = ref<HTMLElement | null>(null);
const scale = ref(0.5);

let observer: ResizeObserver | undefined;

function updateScale(): void {
    const width = root.value?.clientWidth ?? 0;

    if (width > 0) {
        scale.value = width / BASE_WIDTH;
    }
}

onMounted(() => {
    updateScale();

    if (typeof ResizeObserver === 'undefined' || !root.value) {
        return;
    }

    observer = new ResizeObserver(updateScale);
    observer.observe(root.value);
});

onBeforeUnmount(() => {
    observer?.disconnect();
});
</script>

<template>
    <div
        ref="root"
        class="relative h-full w-full overflow-hidden bg-zinc-950"
        data-slide-thumbnail
    >
        <div
            class="absolute top-0 left-0 origin-top-left"
            :style="{
                width: `${BASE_WIDTH}px`,
                height: `${BASE_HEIGHT}px`,
                transform: `scale(${scale})`,
            }"
        >
            <SlideContent :item="item" :media-assets="mediaAssets" compact />
        </div>
    </div>
</template>
