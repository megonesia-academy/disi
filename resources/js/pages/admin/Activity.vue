<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link } from '@inertiajs/vue3';
import { ArrowLeft, ClipboardList, Search, ShieldCheck } from '@lucide/vue';
import { computed, ref } from 'vue';

type ActivityRow = {
    id: string;
    event: string;
    subject_type: string | null;
    subject_id: string | null;
    properties: Record<string, unknown>;
    ip_address: string | null;
    created_at: string | null;
    created_at_iso: string | null;
    tenant: { id: string; name: string; slug: string } | null;
    user: { id: number; name: string; email: string } | null;
};

const props = defineProps<{
    activities: ActivityRow[];
    tenants: Array<{ id: string; name: string }>;
    events: string[];
}>();

const search = ref('');
const tenantFilter = ref('all');
const eventFilter = ref('all');

const visibleActivities = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.activities.filter((activity) => {
        const matchesTerm =
            term === '' ||
            activity.event.toLowerCase().includes(term) ||
            (activity.tenant?.name ?? '').toLowerCase().includes(term) ||
            (activity.user?.name ?? '').toLowerCase().includes(term) ||
            (activity.user?.email ?? '').toLowerCase().includes(term) ||
            JSON.stringify(activity.properties).toLowerCase().includes(term);
        const matchesTenant =
            tenantFilter.value === 'all' ||
            activity.tenant?.id === tenantFilter.value;
        const matchesEvent =
            eventFilter.value === 'all' || activity.event === eventFilter.value;

        return matchesTerm && matchesTenant && matchesEvent;
    });
});

function prettyProperties(properties: Record<string, unknown>): string {
    return JSON.stringify(properties, null, 2);
}

function subjectLabel(activity: ActivityRow): string {
    if (!activity.subject_type) {
        return '-';
    }

    const short = activity.subject_type.split('\\').pop() ?? '';

    return activity.subject_id ? `${short} · ${activity.subject_id}` : short;
}
</script>

<template>
    <Head title="Platform Activity" />

    <div
        class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                >
                    <ShieldCheck class="size-3.5" /> Platform admin
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Activity log
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Audit aktivitas seluruh tenant di platform. Hanya admin
                    aplikasi yang dapat melihat halaman ini.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link href="/admin">
                    <ArrowLeft class="size-4" /> Admin dashboard
                </Link>
            </Button>
        </section>

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    Aktivitas tampil
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ visibleActivities.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    Tenant terpantau
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ tenants.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Jenis event</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ events.length }}
                </div>
            </div>
        </section>

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
                    placeholder="Cari event, tenant, user, atau properti..."
                />
            </div>
            <select
                v-model="tenantFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua tenant</option>
                <option
                    v-for="tenant in tenants"
                    :key="tenant.id"
                    :value="tenant.id"
                >
                    {{ tenant.name }}
                </option>
            </select>
            <select
                v-model="eventFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua event</option>
                <option v-for="event in events" :key="event" :value="event">
                    {{ event }}
                </option>
            </select>
            <span class="ml-auto text-xs text-muted-foreground">
                200 aktivitas terbaru
            </span>
        </section>

        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[1080px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Waktu</th>
                            <th class="px-4 py-3 font-medium">Tenant</th>
                            <th class="px-4 py-3 font-medium">User</th>
                            <th class="px-4 py-3 font-medium">Event</th>
                            <th class="px-4 py-3 font-medium">Subject</th>
                            <th class="px-4 py-3 font-medium">IP</th>
                            <th class="px-4 py-3 font-medium">Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="activity in visibleActivities"
                            :key="activity.id"
                            class="border-t align-top"
                        >
                            <td
                                class="px-4 py-3 whitespace-nowrap text-muted-foreground"
                                :title="activity.created_at_iso ?? ''"
                            >
                                {{ activity.created_at ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <template v-if="activity.tenant">
                                    <div class="font-medium">
                                        {{ activity.tenant.name }}
                                    </div>
                                    <div
                                        class="font-mono text-xs text-muted-foreground"
                                    >
                                        {{ activity.tenant.slug }}
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground">
                                    -
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <template v-if="activity.user">
                                    <div class="font-medium">
                                        {{ activity.user.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ activity.user.email }}
                                    </div>
                                </template>
                                <span v-else class="text-muted-foreground">
                                    system
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full bg-muted px-2 py-0.5 font-mono text-xs"
                                >
                                    {{ activity.event }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="block max-w-64 truncate font-mono text-xs text-muted-foreground"
                                    :title="subjectLabel(activity)"
                                >
                                    {{ subjectLabel(activity) }}
                                </span>
                            </td>
                            <td
                                class="px-4 py-3 font-mono text-xs text-muted-foreground"
                            >
                                {{ activity.ip_address ?? '-' }}
                            </td>
                            <td class="px-4 py-3">
                                <details
                                    v-if="
                                        Object.keys(activity.properties).length
                                    "
                                    class="max-w-72"
                                >
                                    <summary
                                        class="cursor-pointer text-xs font-medium text-primary"
                                    >
                                        Lihat properti
                                    </summary>
                                    <pre
                                        class="mt-2 max-h-48 overflow-auto rounded-md bg-muted/60 p-2 text-[11px] leading-4"
                                        >{{
                                            prettyProperties(
                                                activity.properties,
                                            )
                                        }}</pre>
                                </details>
                                <span v-else class="text-muted-foreground">
                                    -
                                </span>
                            </td>
                        </tr>
                        <tr v-if="visibleActivities.length === 0">
                            <td
                                colspan="7"
                                class="px-4 py-10 text-center text-sm text-muted-foreground"
                            >
                                <ClipboardList
                                    class="mx-auto mb-2 size-6 text-muted-foreground"
                                />
                                Tidak ada aktivitas yang cocok dengan filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
