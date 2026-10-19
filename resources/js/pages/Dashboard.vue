<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    Activity,
    ArrowRight,
    CalendarClock,
    CheckCircle2,
    Coins,
    ExternalLink,
    Image,
    Link2,
    ListVideo,
    Monitor,
    Plus,
    Tv,
    Upload,
} from '@lucide/vue';
import { computed, type Component } from 'vue';
import { dashboard } from '@/routes';
import type { Tenancy } from '@/types';

type Tenant = {
    id: string;
    name: string;
    slug: string;
    status: string;
};

type Stats = {
    screens: number;
    devices: number;
    online_devices: number;
    media_assets: number;
    playlists: number;
    active_schedules: number;
};

type Device = {
    id: string;
    name: string;
    key: string;
    status: string;
    is_active: boolean;
    last_seen_at: string | null;
    last_seen_iso: string | null;
    player_url: string;
    screen: {
        id: string;
        name: string;
        location: string | null;
    } | null;
    health: {
        status: string;
        label: string;
        message: string;
    };
    runtime: {
        wasm_supported: boolean | null;
        rust_wasm: boolean | null;
        layout_engine: string;
        manifest_source: string;
        manifest_cached: boolean | null;
        network_online: boolean | null;
        media_readiness: {
            total: number;
            ready: number;
            failed: number;
            pending: number;
            unsupported: number;
            status: string;
        };
    };
};

type Screen = {
    id: string;
    name: string;
    location: string | null;
    orientation: string;
    resolution: {
        width: number;
        height: number;
    };
    is_active: boolean;
    devices_count: number;
    active_schedules_count: number;
    preview_url: string;
    health: {
        status: string;
        label: string;
        message: string;
    };
};

type SetupItem = {
    key: string;
    title: string;
    description: string;
    done: boolean;
    href: string;
    action: string;
};

type StatCard = {
    label: string;
    value: number;
    hint: string;
    icon: Component;
};

const props = defineProps<{
    tenant: Tenant;
    stats: Stats;
    setup: SetupItem[];
    devices: Device[];
    screens: Screen[];
}>();

const page = usePage();
const tenancy = computed(() => page.props.tenancy as Tenancy | undefined);
const tokenBalance = computed(() => tenancy.value?.current?.token_balance ?? 0);

const completedSetup = computed(
    () => props.setup.filter((item) => item.done).length,
);

const setupProgress = computed(() => {
    if (props.setup.length === 0) {
        return 0;
    }

    return Math.round((completedSetup.value / props.setup.length) * 100);
});

const statCards = computed<StatCard[]>(() => [
    {
        label: 'Screens',
        value: props.stats.screens,
        hint: 'Lokasi display aktif',
        icon: Monitor,
    },
    {
        label: 'Devices',
        value: props.stats.devices,
        hint: `${props.stats.online_devices} online`,
        icon: Tv,
    },
    {
        label: 'Playlists',
        value: props.stats.playlists,
        hint: 'Urutan konten',
        icon: ListVideo,
    },
    {
        label: 'Media',
        value: props.stats.media_assets,
        hint: 'Aset tersimpan',
        icon: Image,
    },
    {
        label: 'Schedules',
        value: props.stats.active_schedules,
        hint: 'Jadwal aktif',
        icon: CalendarClock,
    },
]);

const quickActions = [
    {
        title: 'Kelola playlist',
        description: 'Susun urutan konten tayang.',
        href: '/playlists',
        icon: ListVideo,
    },
    {
        title: 'Atur schedule',
        description: 'Tentukan kapan playlist tampil.',
        href: '/schedules',
        icon: CalendarClock,
    },
    {
        title: 'Media library',
        description: 'Gambar, video, audio, dan URL.',
        href: '/media-assets',
        icon: Image,
    },
    {
        title: 'Billing & token',
        description: 'Topup dan pantau pemakaian.',
        href: '/workspace/billing',
        icon: Coins,
    },
];

function healthTone(status: string): string {
    if (status === 'ready' || status === 'online') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (status === 'no_device') {
        return 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300';
    }

    if (
        status === 'offline' ||
        status === 'no_schedule' ||
        status === 'unassigned' ||
        status === 'degraded'
    ) {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
    }

    return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-800 dark:text-zinc-300';
}

function deviceDotTone(status: string): string {
    return status === 'online' ? 'bg-emerald-500' : 'bg-zinc-400';
}

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Dashboard',
                href: dashboard(),
            },
        ],
    },
});
</script>

<template>
    <Head title="Dashboard" />

    <div
        class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="overflow-hidden rounded-xl border bg-background shadow-sm"
        >
            <div
                class="relative bg-linear-to-br from-orange-50/80 via-background to-slate-100/80 p-5 sm:p-6 dark:from-orange-950/25 dark:to-slate-900/40"
            >
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="max-w-2xl min-w-0">
                        <div
                            class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-orange-50 px-2.5 py-1 text-xs font-medium text-orange-700 dark:bg-orange-950 dark:text-orange-300"
                        >
                            <Activity class="size-3.5" />
                            Active workspace
                        </div>
                        <h1
                            class="truncate text-2xl font-semibold tracking-tight sm:text-3xl"
                        >
                            {{ tenant.name }}
                        </h1>
                        <p class="mt-2 text-sm text-muted-foreground">
                            Ringkasan display, konten, dan jadwal workspace Anda
                            dalam satu tampilan.
                        </p>

                        <div class="mt-4 flex flex-wrap items-center gap-2">
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md border bg-background/80 px-2.5 py-1 text-xs font-medium"
                            >
                                <Coins class="size-3.5 text-amber-500" />
                                {{ tokenBalance }} token
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md border bg-background/80 px-2.5 py-1 text-xs font-medium text-muted-foreground"
                            >
                                <Monitor class="size-3.5" />
                                {{ stats.screens }} screen
                            </span>
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md border bg-background/80 px-2.5 py-1 text-xs font-medium text-muted-foreground"
                            >
                                <Tv class="size-3.5" />
                                {{ stats.online_devices }}/{{ stats.devices }}
                                device online
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Button as-child>
                            <Link href="/playlists/create">
                                <Plus class="size-4" /> New playlist
                            </Link>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link href="/media-assets">
                                <Upload class="size-4" /> Upload media
                            </Link>
                        </Button>
                        <Button variant="outline" as-child>
                            <Link href="/devices">
                                <Link2 class="size-4" /> Pair display
                            </Link>
                        </Button>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="grid gap-3 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5"
        >
            <div
                v-for="stat in statCards"
                :key="stat.label"
                class="rounded-lg border bg-background p-4 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-center justify-between gap-2">
                    <span class="text-sm text-muted-foreground">
                        {{ stat.label }}
                    </span>
                    <component
                        :is="stat.icon"
                        class="size-4 text-muted-foreground"
                    />
                </div>
                <div class="mt-2 text-3xl font-semibold tracking-tight">
                    {{ stat.value }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    {{ stat.hint }}
                </p>
            </div>
        </section>

        <div class="grid gap-5 xl:grid-cols-3">
            <div class="space-y-5 xl:col-span-2">
                <section
                    class="overflow-hidden rounded-lg border bg-background shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3"
                    >
                        <div>
                            <h2 class="font-semibold tracking-tight">
                                Device status
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                Pantau display yang sudah tersambung.
                            </p>
                        </div>
                        <Button size="sm" variant="outline" as-child>
                            <Link href="/devices">
                                Manage <ArrowRight class="size-3.5" />
                            </Link>
                        </Button>
                    </div>

                    <div
                        v-if="devices.length"
                        class="grid gap-3 p-4 sm:grid-cols-2"
                    >
                        <article
                            v-for="device in devices"
                            :key="device.id"
                            class="flex flex-col rounded-lg border bg-background p-4 transition-shadow hover:shadow-md"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h3
                                        class="flex items-center gap-2 truncate font-semibold"
                                    >
                                        <span
                                            class="size-2 shrink-0 rounded-full"
                                            :class="
                                                deviceDotTone(device.status)
                                            "
                                        />
                                        {{ device.name }}
                                    </h3>
                                    <p
                                        class="mt-1 truncate text-xs text-muted-foreground"
                                    >
                                        {{
                                            device.screen?.name ??
                                            'Belum assign screen'
                                        }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold capitalize"
                                    :class="healthTone(device.status)"
                                >
                                    {{ device.status }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-2 gap-2 text-xs">
                                <div class="rounded-md bg-muted/60 p-2">
                                    <div class="text-muted-foreground">
                                        Health
                                    </div>
                                    <div class="mt-1">
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                            :class="
                                                healthTone(device.health.status)
                                            "
                                        >
                                            {{ device.health.label }}
                                        </span>
                                    </div>
                                </div>
                                <div class="rounded-md bg-muted/60 p-2">
                                    <div class="text-muted-foreground">
                                        Runtime
                                    </div>
                                    <div
                                        class="mt-1 truncate font-semibold capitalize"
                                    >
                                        {{ device.runtime.layout_engine }}
                                    </div>
                                </div>
                                <div class="rounded-md bg-muted/60 p-2">
                                    <div class="text-muted-foreground">
                                        Manifest
                                    </div>
                                    <div
                                        class="mt-1 truncate font-semibold capitalize"
                                    >
                                        {{ device.runtime.manifest_source }}
                                    </div>
                                </div>
                                <div class="rounded-md bg-muted/60 p-2">
                                    <div class="text-muted-foreground">
                                        Media ready
                                    </div>
                                    <div class="mt-1 font-semibold">
                                        {{
                                            device.runtime.media_readiness
                                                .ready
                                        }}/{{
                                            device.runtime.media_readiness.total
                                        }}
                                    </div>
                                </div>
                            </div>

                            <div
                                class="mt-auto flex items-center justify-between gap-2 border-t pt-3"
                            >
                                <span class="text-[11px] text-muted-foreground">
                                    {{
                                        device.last_seen_at
                                            ? `Seen ${device.last_seen_at}`
                                            : 'Belum pernah online'
                                    }}
                                </span>
                                <Button size="sm" variant="outline" as-child>
                                    <a
                                        :href="device.player_url"
                                        target="_blank"
                                        rel="noopener"
                                    >
                                        <ExternalLink class="size-3.5" />
                                        Player
                                    </a>
                                </Button>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="p-8 text-center text-sm text-muted-foreground"
                    >
                        <p>Belum ada device terdaftar.</p>
                        <Button class="mt-3" size="sm" as-child>
                            <Link href="/devices">
                                <Link2 class="size-4" /> Pair display
                            </Link>
                        </Button>
                    </div>
                </section>

                <section
                    class="overflow-hidden rounded-lg border bg-background shadow-sm"
                >
                    <div
                        class="flex flex-wrap items-center justify-between gap-3 border-b px-4 py-3"
                    >
                        <div>
                            <h2 class="font-semibold tracking-tight">
                                Screen readiness
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                Pastikan screen punya schedule sebelum dipair.
                            </p>
                        </div>
                        <Button size="sm" variant="outline" as-child>
                            <Link href="/screens">
                                Manage <ArrowRight class="size-3.5" />
                            </Link>
                        </Button>
                    </div>

                    <div
                        v-if="screens.length"
                        class="grid gap-3 p-4 sm:grid-cols-2"
                    >
                        <article
                            v-for="screen in screens"
                            :key="screen.id"
                            class="rounded-lg border bg-background p-4 transition-colors hover:bg-muted/25"
                        >
                            <div class="flex items-start justify-between gap-2">
                                <div class="min-w-0">
                                    <h3 class="truncate font-semibold">
                                        {{ screen.name }}
                                    </h3>
                                    <p
                                        class="mt-1 truncate text-xs text-muted-foreground"
                                    >
                                        {{ screen.location ?? 'Tanpa lokasi' }}
                                        · {{ screen.resolution.width }} ×
                                        {{ screen.resolution.height }}
                                    </p>
                                </div>
                                <span
                                    class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                    :class="healthTone(screen.health.status)"
                                >
                                    {{ screen.health.label }}
                                </span>
                            </div>

                            <div class="mt-3 grid grid-cols-3 gap-2 text-xs">
                                <div class="rounded-md bg-muted/60 p-2">
                                    <div class="text-muted-foreground">
                                        Device
                                    </div>
                                    <div class="mt-1 font-semibold">
                                        {{ screen.devices_count }}
                                    </div>
                                </div>
                                <div class="rounded-md bg-muted/60 p-2">
                                    <div class="text-muted-foreground">
                                        Schedule
                                    </div>
                                    <div class="mt-1 font-semibold">
                                        {{ screen.active_schedules_count }}
                                    </div>
                                </div>
                                <div class="rounded-md bg-muted/60 p-2">
                                    <div class="text-muted-foreground">
                                        Mode
                                    </div>
                                    <div
                                        class="mt-1 truncate font-semibold capitalize"
                                    >
                                        {{ screen.orientation }}
                                    </div>
                                </div>
                            </div>

                            <p
                                class="mt-2 line-clamp-2 min-h-8 text-xs text-muted-foreground"
                            >
                                {{ screen.health.message }}
                            </p>

                            <div class="mt-3 flex gap-2">
                                <Button size="sm" as-child>
                                    <a :href="screen.preview_url"> Preview </a>
                                </Button>
                                <Button size="sm" variant="outline" as-child>
                                    <Link href="/schedules">Schedule</Link>
                                </Button>
                            </div>
                        </article>
                    </div>

                    <div
                        v-else
                        class="p-8 text-center text-sm text-muted-foreground"
                    >
                        <p>Belum ada screen. Buat screen pertama Anda.</p>
                        <Button class="mt-3" size="sm" as-child>
                            <Link href="/screens">
                                <Plus class="size-4" /> Create screen
                            </Link>
                        </Button>
                    </div>
                </section>
            </div>

            <aside class="space-y-5">
                <section class="rounded-lg border bg-background p-5 shadow-sm">
                    <div class="flex items-center justify-between gap-3">
                        <div>
                            <h2 class="font-semibold tracking-tight">
                                Setup checklist
                            </h2>
                            <p class="text-xs text-muted-foreground">
                                {{ completedSetup }} dari {{ setup.length }}
                                langkah selesai
                            </p>
                        </div>
                        <div
                            class="grid size-12 shrink-0 place-items-center rounded-full border bg-muted/60 text-sm font-semibold"
                        >
                            {{ setupProgress }}%
                        </div>
                    </div>

                    <div
                        class="mt-4 h-2 w-full overflow-hidden rounded-full bg-muted"
                        role="progressbar"
                        :aria-valuenow="setupProgress"
                        aria-valuemin="0"
                        aria-valuemax="100"
                    >
                        <div
                            class="h-full rounded-full bg-primary transition-all"
                            :style="{ width: `${setupProgress}%` }"
                        />
                    </div>

                    <div class="mt-4 space-y-2">
                        <Link
                            v-for="(item, index) in setup"
                            :key="item.key"
                            :href="item.href"
                            class="group flex items-start gap-3 rounded-md border p-3 transition-colors hover:bg-muted/60"
                        >
                            <span
                                class="grid size-7 shrink-0 place-items-center rounded-full text-xs font-semibold"
                                :class="
                                    item.done
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                <CheckCircle2 v-if="item.done" class="size-4" />
                                <template v-else>{{ index + 1 }}</template>
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium">
                                    {{ item.title }}
                                </span>
                                <span
                                    class="mt-0.5 line-clamp-2 block text-xs text-muted-foreground"
                                >
                                    {{ item.description }}
                                </span>
                            </span>
                            <ArrowRight
                                class="mt-1 size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                            />
                        </Link>
                    </div>
                </section>

                <section class="rounded-lg border bg-background p-5 shadow-sm">
                    <h2 class="font-semibold tracking-tight">Quick actions</h2>
                    <p class="text-xs text-muted-foreground">
                        Pintasan ke alat operasional harian.
                    </p>

                    <div class="mt-3 space-y-1">
                        <Link
                            v-for="action in quickActions"
                            :key="action.href"
                            :href="action.href"
                            class="group flex items-center gap-3 rounded-md p-2.5 transition-colors hover:bg-muted/60"
                        >
                            <span
                                class="grid size-8 shrink-0 place-items-center rounded-md bg-muted text-muted-foreground group-hover:text-foreground"
                            >
                                <component :is="action.icon" class="size-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm font-medium">
                                    {{ action.title }}
                                </span>
                                <span
                                    class="block truncate text-xs text-muted-foreground"
                                >
                                    {{ action.description }}
                                </span>
                            </span>
                            <ArrowRight
                                class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5"
                            />
                        </Link>
                    </div>
                </section>
            </aside>
        </div>
    </div>
</template>
