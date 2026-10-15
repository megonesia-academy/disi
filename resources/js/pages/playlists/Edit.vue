<script setup lang="ts">
import PlaylistEditor from '@/components/playlists/PlaylistEditor.vue';

defineProps<{
    tenant: { id: string; name: string };
    playlist: any;
    mediaAssets: Array<{ id: string; name: string; type: string; url: string; duration_seconds: number | null }>;
    templates: Array<{ id: string; name: string; slug: string; html: string; css: string | null; schema: Array<Record<string, unknown>>; is_active: boolean }>;
    apiConnections: Array<{ id: string; name: string; provider: string }>;
}>();
</script>

<template>
    <PlaylistEditor mode="edit" :tenant="tenant" :playlist="playlist" :media-assets="mediaAssets" :templates="templates" :api-connections="apiConnections" />
</template>
