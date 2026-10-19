<script setup lang="ts">
import { computed } from 'vue';
import VideoPoster from '@/components/playlists/VideoPoster.vue';
import { isImageMedia, isVideoMedia } from '@/lib/media';
import {
    slideTemplateLabel,
    slideYoutubeEmbedUrl,
    type SlideItem,
    type SlideMedia,
} from '@/lib/slide';
import { renderTemplateDocument } from '@/lib/templateRenderer';

const props = defineProps<{
    item: SlideItem | null;
    mediaAssets: SlideMedia[];
    compact?: boolean;
}>();

const isCompact = computed(() => props.compact === true);

const media = computed<SlideMedia | undefined>(() =>
    props.mediaAssets.find((asset) => asset.id === props.item?.media_asset_id),
);

const templateKey = computed(() => props.item?.template ?? 'media');
const templateLabel = computed(() => slideTemplateLabel(templateKey.value));

const fitClass = computed(() => {
    if (props.item?.config?.fit === 'contain') {
        return 'object-contain';
    }

    if (props.item?.config?.fit === 'fill') {
        return 'object-fill';
    }

    return 'object-cover';
});

const splitScreen = computed(() => props.item?.config?.split_screen ?? null);

const leftImage = computed(() => {
    const id = splitScreen.value?.left_media_asset_id;

    return id ? props.mediaAssets.find((asset) => asset.id === id) : undefined;
});

const rightMedia = computed(() => {
    const id = splitScreen.value?.right_media_asset_id;

    return id ? props.mediaAssets.find((asset) => asset.id === id) : undefined;
});

const rightEmbedUrl = computed(() =>
    slideYoutubeEmbedUrl(splitScreen.value?.right_url ?? ''),
);

const contentTitle = computed(() => {
    const value = props.item?.content?.title;

    return typeof value === 'string' && value.trim() !== '' ? value : '';
});

const contentSubtitle = computed(() => {
    const value = props.item?.content?.subtitle;

    return typeof value === 'string' && value.trim() !== '' ? value : '';
});

const bodyRows = computed(() => {
    const body = props.item?.content?.body;

    if (Array.isArray(body)) {
        return body
            .slice(0, isCompact.value ? 3 : 5)
            .map((row) =>
                typeof row === 'object' ? JSON.stringify(row) : String(row),
            );
    }

    if (typeof body === 'string') {
        return body
            .split('\n')
            .map((row) => row.trim())
            .filter(Boolean)
            .slice(0, isCompact.value ? 3 : 5);
    }

    return [];
});

const customDocument = computed(() => {
    if (templateKey.value !== 'custom' || !props.item?.template_definition) {
        return '';
    }

    return renderTemplateDocument(
        props.item.template_definition,
        props.item.content ?? {},
    );
});

const placeholderClass = computed(() =>
    isCompact.value ? 'gap-1.5 p-4' : 'gap-3 p-8',
);

const chipClass = computed(() =>
    isCompact.value
        ? 'px-1.5 py-0.5 text-[7px] tracking-[0.14em]'
        : 'px-2.5 py-0.5 text-[10px] tracking-[0.2em]',
);

const titleClass = computed(() =>
    isCompact.value ? 'text-lg leading-tight' : 'text-3xl leading-tight',
);

const subtitleClass = computed(() =>
    isCompact.value ? 'text-[10px]' : 'text-base',
);

const rowsClass = computed(() =>
    isCompact.value
        ? 'mt-0.5 gap-0 w-full space-y-0.5 text-[9px]'
        : 'mt-2 w-full space-y-1 text-sm',
);

const rowClass = computed(() =>
    isCompact.value
        ? 'truncate border-b border-white/10 pb-0.5'
        : 'truncate border-b border-white/10 pb-1',
);
</script>

<template>
    <div class="absolute inset-0 overflow-hidden bg-zinc-950">
        <template v-if="item">
            <!-- Split screen -->
            <div
                v-if="templateKey === 'split-screen'"
                class="absolute inset-0 flex"
            >
                <div
                    class="h-full overflow-hidden bg-zinc-900"
                    :style="{ width: `${splitScreen?.left_width ?? 33}%` }"
                >
                    <img
                        v-if="leftImage"
                        :src="leftImage.url"
                        :alt="leftImage.name"
                        class="h-full w-full object-cover"
                    />
                    <div
                        v-else
                        class="grid h-full place-items-center text-[11px] text-white/40"
                    >
                        Left image
                    </div>
                </div>
                <div
                    class="h-full overflow-hidden bg-zinc-950"
                    :style="{ width: `${splitScreen?.right_width ?? 67}%` }"
                >
                    <template v-if="splitScreen?.right_source === 'media'">
                        <div
                            v-if="
                                isCompact &&
                                rightMedia &&
                                isVideoMedia(rightMedia.type)
                            "
                            class="relative h-full w-full"
                        >
                            <VideoPoster :src="rightMedia.url" />
                        </div>
                        <template
                            v-else-if="
                                rightMedia && isVideoMedia(rightMedia.type)
                            "
                        >
                            <div class="relative h-full w-full">
                                <video
                                    :src="rightMedia.url"
                                    class="h-full w-full object-cover"
                                    autoplay
                                    muted
                                    loop
                                    playsinline
                                />
                            </div>
                        </template>
                        <img
                            v-else-if="rightMedia"
                            :src="rightMedia.url"
                            :alt="rightMedia.name"
                            class="h-full w-full object-cover"
                        />
                        <div
                            v-else
                            class="grid h-full place-items-center text-[11px] text-white/40"
                        >
                            Right media
                        </div>
                    </template>
                    <iframe
                        v-else-if="
                            splitScreen?.right_source === 'youtube' &&
                            rightEmbedUrl &&
                            !isCompact
                        "
                        :src="rightEmbedUrl"
                        class="h-full w-full border-0"
                        title="Right source"
                    />
                    <iframe
                        v-else-if="
                            splitScreen?.right_source === 'url' &&
                            splitScreen?.right_url &&
                            !isCompact
                        "
                        :src="splitScreen.right_url"
                        class="h-full w-full border-0"
                        title="Right source"
                    />
                    <div
                        v-else
                        class="grid h-full place-items-center px-2 text-center text-[10px] text-white/40"
                    >
                        {{
                            splitScreen?.right_source === 'youtube'
                                ? 'YouTube'
                                : 'URL source'
                        }}
                    </div>
                </div>
            </div>

            <!-- Custom template (isolated iframe so template CSS cannot leak) -->
            <iframe
                v-else-if="templateKey === 'custom' && customDocument"
                :srcdoc="customDocument"
                sandbox="allow-same-origin"
                class="absolute inset-0 h-full w-full border-0"
                title="Custom template preview"
            />
            <!-- Media asset -->
            <template v-else-if="media">
                <img
                    v-if="isImageMedia(media.type)"
                    :src="media.url"
                    :alt="media.name"
                    class="absolute inset-0 h-full w-full"
                    :class="fitClass"
                />
                <template v-else-if="isVideoMedia(media.type)">
                    <VideoPoster v-if="isCompact" :src="media.url" />
                    <video
                        v-else
                        :src="media.url"
                        class="absolute inset-0 h-full w-full"
                        :class="fitClass"
                        autoplay
                        muted
                        loop
                        playsinline
                    />
                    <span
                        v-if="isCompact"
                        class="absolute bottom-1 left-1 rounded bg-black/70 px-1.5 py-0.5 text-[8px] font-semibold text-white"
                        >VIDEO</span
                    >
                </template>
                <iframe
                    v-else-if="!isCompact"
                    :src="media.url"
                    :title="media.name"
                    class="h-full w-full border-0"
                />
                <div
                    v-else
                    class="grid h-full place-items-center bg-gradient-to-br from-zinc-800 to-zinc-900 px-2 text-center text-[9px] font-semibold tracking-wide text-white/50 uppercase"
                >
                    {{ media.type }}
                </div>
            </template>

            <!-- Built-in template placeholder -->
            <div
                v-else
                class="absolute inset-0 flex flex-col items-center justify-center bg-gradient-to-br from-zinc-900 via-zinc-900 to-zinc-800 text-center"
                :class="placeholderClass"
            >
                <span
                    class="rounded-full border border-white/15 bg-white/5 font-semibold text-white/60 uppercase"
                    :class="chipClass"
                >
                    {{ templateLabel }}
                </span>
                <div
                    class="max-w-xl font-semibold text-white"
                    :class="titleClass"
                >
                    {{ contentTitle || templateLabel }}
                </div>
                <div
                    v-if="contentSubtitle"
                    class="text-white/70"
                    :class="subtitleClass"
                >
                    {{ contentSubtitle }}
                </div>
                <div
                    v-if="bodyRows.length"
                    class="max-w-xl text-left text-white/80"
                    :class="rowsClass"
                >
                    <div
                        v-for="(row, rowIndex) in bodyRows"
                        :key="rowIndex"
                        :class="rowClass"
                    >
                        {{ row }}
                    </div>
                </div>
            </div>
        </template>
    </div>
</template>
