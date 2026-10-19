<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    AlertCircle,
    ArrowLeft,
    Building2,
    CalendarClock,
    Coins,
    Image,
    ListVideo,
    Monitor,
    ShieldCheck,
    Users,
} from '@lucide/vue';
import { Head } from '@inertiajs/vue3';

type Stats = {
    tenants: number;
    active_tenants: number;
    suspended_tenants: number;
    subscribers: number;
    admins: number;
    screens: number;
    devices: number;
    playlists: number;
    media_assets: number;
    total_tokens: number;
    past_due_tenants: number;
    active_subscriptions: number;
};

type TenantRow = {
    id: string;
    name: string;
    slug: string;
    status: string;
    token_balance: number;
    subscription_status: string;
    subscription_renews_at: string | null;
    users_count: number;
    devices_count: number;
    playlists_count: number;
    created_at: string | null;
};

defineProps<{
    stats: Stats;
    recentTenants: TenantRow[];
}>();
</script>

<template>
    <Head title="Admin Dashboard" />

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
                    <ShieldCheck class="size-3.5" />
                    Application admin
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Application overview
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Pantau seluruh tenant dan subscriber platform. Halaman ini
                    hanya bisa diakses oleh admin aplikasi.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Link
                    href="/dashboard"
                    class="inline-flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    <ArrowLeft class="size-4" /> Workspace
                </Link>
                <Link
                    href="/admin/tenants"
                    class="inline-flex items-center gap-2 rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground shadow-xs transition-colors hover:bg-primary/90"
                >
                    <Building2 class="size-4" /> Manage tenants
                </Link>
            </div>
        </section>

        <section
            class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6"
        >
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Building2 class="size-4" /> Tenants
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ stats.tenants }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stats.active_tenants }} active ·
                    {{ stats.suspended_tenants }} suspended
                </p>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Users class="size-4" /> Subscribers
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ stats.subscribers }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stats.admins }} application admin
                </p>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Monitor class="size-4" /> Devices
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ stats.devices }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stats.screens }} screen terdaftar
                </p>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <ListVideo class="size-4" /> Playlists
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ stats.playlists }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stats.media_assets }} media asset
                </p>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Coins class="size-4 text-amber-500" /> Token tenant
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ stats.total_tokens }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stats.active_subscriptions }} langganan aktif
                </p>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <AlertCircle class="size-4 text-red-500" /> Past due
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ stats.past_due_tenants }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Perlu topup langganan
                </p>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div class="flex items-center justify-between border-b px-4 py-3">
                <div>
                    <h2 class="font-semibold tracking-tight">Tenant terbaru</h2>
                    <p class="text-xs text-muted-foreground">
                        Ringkasan tenant yang baru bergabung.
                    </p>
                </div>
                <Link
                    href="/admin/tenants"
                    class="text-sm font-medium text-primary hover:underline"
                >
                    Lihat semua
                </Link>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[1040px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Tenant</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Token</th>
                            <th class="px-4 py-3 font-medium">Langganan</th>
                            <th class="px-4 py-3 font-medium">Users</th>
                            <th class="px-4 py-3 font-medium">Devices</th>
                            <th class="px-4 py-3 font-medium">Playlists</th>
                            <th class="px-4 py-3 font-medium">Dibuat</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="tenant in recentTenants"
                            :key="tenant.id"
                            class="border-t"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ tenant.name }}</div>
                                <div
                                    class="font-mono text-xs text-muted-foreground"
                                >
                                    {{ tenant.slug }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        tenant.status === 'active'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                    "
                                >
                                    {{ tenant.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3 font-semibold">
                                {{ tenant.token_balance }}
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        tenant.subscription_status === 'active'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                                    "
                                >
                                    {{ tenant.subscription_status }}
                                </span>
                                <div
                                    v-if="tenant.subscription_renews_at"
                                    class="mt-1 text-[11px] text-muted-foreground"
                                >
                                    s.d. {{ tenant.subscription_renews_at }}
                                </div>
                            </td>
                            <td class="px-4 py-3">{{ tenant.users_count }}</td>
                            <td class="px-4 py-3">
                                {{ tenant.devices_count }}
                            </td>
                            <td class="px-4 py-3">
                                {{ tenant.playlists_count }}
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ tenant.created_at ?? '-' }}
                            </td>
                        </tr>
                        <tr v-if="recentTenants.length === 0">
                            <td
                                colspan="8"
                                class="px-4 py-10 text-center text-sm text-muted-foreground"
                            >
                                Belum ada tenant terdaftar.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <section
            class="flex flex-wrap items-center gap-4 rounded-lg border bg-background p-4 text-xs text-muted-foreground shadow-sm"
        >
            <span class="inline-flex items-center gap-1.5">
                <Image class="size-3.5" /> {{ stats.media_assets }} media
            </span>
            <span class="inline-flex items-center gap-1.5">
                <CalendarClock class="size-3.5" /> Data platform real-time
            </span>
            <span class="ml-auto">
                Subscriber hanya bisa mengakses workspace miliknya sendiri.
            </span>
        </section>
    </div>
</template>
