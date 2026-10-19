<script setup lang="ts">
import { ref } from 'vue';

const props = defineProps<{ src: string }>();

const poster = ref<string | null>(null);
const failed = ref(false);

function handleLoadedData(event: Event): void {
    const video = event.target as HTMLVideoElement;

    if (video.videoWidth === 0) {
        return;
    }

    const target =
        Number.isFinite(video.duration) && video.duration > 0.4
            ? Math.min(0.2, video.duration / 2)
            : 0;

    if (target > 0) {
        try {
            video.currentTime = target;
            return;
        } catch {
            // Fall through and capture the current frame.
        }
    }

    capture(video);
}

function handleSeeked(event: Event): void {
    capture(event.target as HTMLVideoElement);
}

function capture(video: HTMLVideoElement): void {
    if (poster.value || video.videoWidth === 0) {
        return;
    }

    try {
        const scale = Math.min(1, 640 / video.videoWidth);
        const canvas = document.createElement('canvas');
        canvas.width = Math.max(1, Math.round(video.videoWidth * scale));
        canvas.height = Math.max(1, Math.round(video.videoHeight * scale));

        const context = canvas.getContext('2d');

        if (!context) {
            failed.value = true;
            return;
        }

        context.drawImage(video, 0, 0, canvas.width, canvas.height);
        poster.value = canvas.toDataURL('image/jpeg', 0.72);
    } catch {
        // Cross-origin videos taint the canvas; fall back to the first frame.
        failed.value = true;
    }
}
</script>

<template>
    <div class="absolute inset-0 overflow-hidden bg-zinc-950">
        <img
            v-if="poster"
            :src="poster"
            alt=""
            class="h-full w-full object-cover"
        />
        <video
            v-else
            :src="props.src"
            class="h-full w-full object-cover"
            muted
            playsinline
            preload="metadata"
            @loadeddata="handleLoadedData"
            @seeked="handleSeeked"
            @error="failed = true"
        />
    </div>
</template>
