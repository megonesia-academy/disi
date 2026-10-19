<script setup lang="ts">
import PlaylistEditor from '@/components/playlists/PlaylistEditor.vue';

defineProps<{
    tenant: { id: string; name: string };
    mediaAssets: Array<{
        id: string;
        name: string;
        type: string;
        url: string;
        duration_seconds: number | null;
    }>;
    templates: Array<{
        id: string;
        name: string;
        slug: string;
        html: string;
        css: string | null;
        schema: Array<Record<string, unknown>>;
        is_active: boolean;
    }>;
    apiConnections: Array<{ id: string; name: string; provider: string }>;
    usage: {
        playlists: number;
        playlist_limit: number;
        free_playlists: number;
        free_items: number;
        item_cost: number;
        balance: number;
        can_add_playlist: boolean;
    };
}>();
</script>

<template>
    <PlaylistEditor
        mode="create"
        :tenant="tenant"
        :media-assets="mediaAssets"
        :templates="templates"
        :api-connections="apiConnections"
        :free-items="usage.free_items"
        :item-cost="usage.item_cost"
        :balance="usage.balance"
    />
</template>
