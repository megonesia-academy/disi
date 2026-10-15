<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

type Activity = {
    id: string;
    event: string;
    subject_type: string | null;
    subject_id: string | null;
    properties: Record<string, unknown>;
    ip_address: string | null;
    created_at: string | null;
    created_at_iso: string | null;
    user: {
        id: string;
        name: string;
        email: string;
    } | null;
};

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
    };
    activities: Activity[];
}>();

const eventLabels: Record<string, string> = {
    'device.command_queued': 'Device command queued',
    'device.created': 'Device created',
    'device.deleted': 'Device deleted',
    'device.updated': 'Device updated',
    'display.paired': 'Display paired',
    'media.created': 'Media created',
    'media.deleted': 'Media deleted',
    'media.updated': 'Media updated',
    'member.removed': 'Member removed',
    'member.role_updated': 'Member role updated',
    'playlist.created': 'Playlist created',
    'playlist.deleted': 'Playlist deleted',
    'playlist.updated': 'Playlist updated',
    'schedule.created': 'Schedule created',
    'schedule.deleted': 'Schedule deleted',
    'schedule.updated': 'Schedule updated',
    'screen.created': 'Screen created',
    'screen.deleted': 'Screen deleted',
    'screen.updated': 'Screen updated',
    'workspace.updated': 'Workspace updated',
};

const recentCount = computed(() => props.activities.length);

function eventLabel(event: string): string {
    return eventLabels[event] ?? event.replace('.', ' ');
}

function propertyPreview(properties: Record<string, unknown>): string {
    const entries = Object.entries(properties).slice(0, 4);

    if (entries.length === 0) {
        return '-';
    }

    return entries
        .map(([key, value]) => `${key}: ${String(value)}`)
        .join(' · ');
}
</script>

<template>
    <Head title="Activity" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-muted/20 p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300"
                >
                    Audit trail
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Activity
                </h1>
                <p class="text-sm text-muted-foreground">
                    Riwayat aktivitas penting untuk {{ tenant.name }}.
                </p>
            </div>

            <div class="flex gap-2">
                <Link
                    href="/dashboard"
                    class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Dashboard
                </Link>
                <Link
                    href="/devices"
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground shadow-xs"
                >
                    Devices
                </Link>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Recent events</div>
                <div class="mt-2 text-3xl font-semibold">
                    {{ recentCount }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Tracked actions</div>
                <div class="mt-2 text-3xl font-semibold">
                    {{ Object.keys(eventLabels).length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Tenant scope</div>
                <div class="mt-2 truncate text-lg font-semibold">
                    {{ tenant.name }}
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div class="border-b px-4 py-3">
                <h2 class="font-semibold tracking-tight">Event log</h2>
                <p class="text-xs text-muted-foreground">
                    Menampilkan 100 aktivitas terbaru.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[920px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Event</th>
                            <th class="px-4 py-3 font-medium">Actor</th>
                            <th class="px-4 py-3 font-medium">Details</th>
                            <th class="px-4 py-3 font-medium">IP</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Time
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="activity in activities"
                            :key="activity.id"
                            class="border-t transition-colors hover:bg-muted/35"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ eventLabel(activity.event) }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ activity.event }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ activity.user?.name ?? 'System' }}
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ activity.user?.email ?? '-' }}
                                </div>
                            </td>
                            <td class="max-w-lg px-4 py-3">
                                <div class="truncate text-muted-foreground">
                                    {{ propertyPreview(activity.properties) }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ activity.ip_address ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                {{ activity.created_at ?? '-' }}
                            </td>
                        </tr>
                        <tr v-if="activities.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                Belum ada aktivitas tercatat.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
