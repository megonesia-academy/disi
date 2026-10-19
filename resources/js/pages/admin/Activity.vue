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

        <section class="rounded-lg border bg-background shadow-sm">
            <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="activity in visibleActivities"
                    :key="activity.id"
                    class="flex flex-col rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b px-4 py-3"
                    >
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold">
                                {{ activity.tenant?.name ?? '-' }}
                            </h2>
                            <p
                                v-if="activity.tenant"
                                class="truncate font-mono text-xs text-muted-foreground"
                            >
                                {{ activity.tenant.slug }}
                            </p>
                        </div>
                        <div
                            class="flex shrink-0 flex-wrap items-center justify-end gap-1.5"
                        >
                            <span
                                class="inline-flex rounded-full bg-muted px-2 py-0.5 font-mono text-xs"
                            >
                                {{ activity.event }}
                            </span>
                        </div>
                    </div>
                    <dl
                        class="grid grid-cols-2 gap-x-4 gap-y-2 px-4 py-3 text-xs"
                    >
                        <div class="min-w-0">
                            <dt class="text-muted-foreground">Waktu</dt>
                            <dd
                                class="mt-0.5 font-medium"
                                :title="activity.created_at_iso ?? ''"
                            >
                                {{ activity.created_at ?? '-' }}
                            </dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-muted-foreground">User</dt>
                            <dd class="mt-0.5 font-medium">
                                <template v-if="activity.user">
                                    <span class="block truncate">
                                        {{ activity.user.name }}
                                    </span>
                                    <span
                                        class="block truncate text-muted-foreground"
                                    >
                                        {{ activity.user.email }}
                                    </span>
                                </template>
                                <span v-else class="text-muted-foreground">
                                    system
                                </span>
                            </dd>
                        </div>
                        <div class="col-span-2 min-w-0">
                            <dt class="text-muted-foreground">Subject</dt>
                            <dd
                                class="mt-0.5 truncate font-mono font-medium"
                                :title="subjectLabel(activity)"
                            >
                                {{ subjectLabel(activity) }}
                            </dd>
                        </div>
                        <div class="min-w-0">
                            <dt class="text-muted-foreground">IP</dt>
                            <dd class="mt-0.5 truncate font-mono font-medium">
                                {{ activity.ip_address ?? '-' }}
                            </dd>
                        </div>
                        <div class="col-span-2 min-w-0">
                            <dt class="text-muted-foreground">Detail</dt>
                            <dd class="mt-0.5">
                                <details
                                    v-if="
                                        Object.keys(activity.properties).length
                                    "
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
                                <span
                                    v-else
                                    class="font-medium text-muted-foreground"
                                >
                                    -
                                </span>
                            </dd>
                        </div>
                    </dl>
                </article>
            </div>
            <div
                v-if="visibleActivities.length === 0"
                class="border-t px-4 py-10 text-center text-sm text-muted-foreground"
            >
                <ClipboardList
                    class="mx-auto mb-2 size-6 text-muted-foreground"
                />
                Tidak ada aktivitas yang cocok dengan filter.
            </div>
        </section>
    </div>
</template>
