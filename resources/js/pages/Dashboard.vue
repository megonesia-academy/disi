<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import { dashboard } from '@/routes';

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

const props = defineProps<{
    tenant: Tenant;
    stats: Stats;
    setup: SetupItem[];
    devices: Device[];
    screens: Screen[];
}>();

const completedSetup = computed(() => {
    return props.setup.filter((item) => item.done).length;
});

const setupProgress = computed(() => {
    if (props.setup.length === 0) {
        return 0;
    }

    return Math.round((completedSetup.value / props.setup.length) * 100);
});

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
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-muted/20 p-4 md:p-6"
    >
        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div
                class="flex flex-wrap items-end justify-between gap-5 border-b bg-linear-to-br from-background via-background to-muted/60 p-6"
            >
                <div class="max-w-2xl">
                    <div
                        class="mb-3 inline-flex rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300"
                    >
                        Active workspace
                    </div>
                    <h1 class="text-3xl font-semibold tracking-tight">
                        {{ tenant.name }}
                    </h1>
                    <p class="mt-2 text-sm text-muted-foreground">
                        Kelola konten, playlist, jadwal, dan koneksi display
                        dari satu tempat.
                    </p>
                    <div class="mt-3 text-xs text-muted-foreground">
                        {{ tenant.slug }} · {{ tenant.status }}
                    </div>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a
                        href="/screens"
                        class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                    >
                        Screens
                    </a>
                    <a
                        href="/devices"
                        class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground shadow-xs"
                    >
                        Pair display
                    </a>
                    <a
                        href="/media-assets"
                        class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                    >
                        Upload media
                    </a>
                </div>
            </div>
        </section>

        <section class="grid auto-rows-min gap-4 md:grid-cols-5">
            <div
                class="rounded-lg border bg-background p-4 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="text-sm text-muted-foreground">Screens</div>
                <div class="mt-2 text-3xl font-semibold">
                    {{ stats.screens }}
                </div>
            </div>
            <div
                class="rounded-lg border bg-background p-4 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="text-sm text-muted-foreground">Devices</div>
                <div class="mt-2 text-3xl font-semibold">
                    {{ stats.devices }}
                </div>
            </div>
            <div
                class="rounded-lg border bg-background p-4 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="text-sm text-muted-foreground">Online</div>
                <div class="mt-2 text-3xl font-semibold text-emerald-600">
                    {{ stats.online_devices }}
                </div>
            </div>
            <div
                class="rounded-lg border bg-background p-4 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="text-sm text-muted-foreground">Media</div>
                <div class="mt-2 text-3xl font-semibold">
                    {{ stats.media_assets }}
                </div>
            </div>
            <div
                class="rounded-lg border bg-background p-4 shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="text-sm text-muted-foreground">Playlists</div>
                <div class="mt-2 text-3xl font-semibold">
                    {{ stats.playlists }}
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-4 border-b px-4 py-3"
            >
                <div>
                    <h2 class="font-semibold tracking-tight">
                        Screen readiness
                    </h2>
                    <p class="text-xs text-muted-foreground">
                        Cek screen yang sudah punya schedule sebelum dipair ke
                        TV.
                    </p>
                </div>
                <a
                    href="/screens"
                    class="rounded-md border bg-background px-3 py-2 text-xs font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Manage screens
                </a>
            </div>

            <div class="grid gap-3 p-4 md:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="screen in screens"
                    :key="screen.id"
                    class="rounded-lg border bg-background p-4 transition-colors hover:bg-muted/30"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0">
                            <h3 class="truncate font-semibold tracking-tight">
                                {{ screen.name }}
                            </h3>
                            <p class="mt-1 text-xs text-muted-foreground">
                                {{ screen.location ?? 'No location' }} ·
                                {{ screen.resolution.width }} x
                                {{ screen.resolution.height }}
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2 py-1 text-xs font-medium"
                            :class="
                                screen.health.status === 'ready'
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                    : screen.health.status === 'no_device'
                                      ? 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300'
                                      : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                            "
                        >
                            {{ screen.health.label }}
                        </span>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2 text-xs">
                        <div class="rounded-md bg-muted/45 p-2">
                            <div class="text-muted-foreground">Device</div>
                            <div class="mt-1 font-semibold">
                                {{ screen.devices_count }}
                            </div>
                        </div>
                        <div class="rounded-md bg-muted/45 p-2">
                            <div class="text-muted-foreground">Schedule</div>
                            <div class="mt-1 font-semibold">
                                {{ screen.active_schedules_count }}
                            </div>
                        </div>
                        <div class="rounded-md bg-muted/45 p-2">
                            <div class="text-muted-foreground">Mode</div>
                            <div class="mt-1 truncate font-semibold">
                                {{ screen.orientation }}
                            </div>
                        </div>
                    </div>

                    <p class="mt-3 min-h-9 text-sm text-muted-foreground">
                        {{ screen.health.message }}
                    </p>

                    <div class="mt-4 flex gap-2">
                        <a
                            :href="screen.preview_url"
                            class="rounded-md bg-primary px-3 py-2 text-xs font-medium text-primary-foreground shadow-xs"
                        >
                            Preview
                        </a>
                        <a
                            href="/schedules"
                            class="rounded-md border bg-background px-3 py-2 text-xs font-medium shadow-xs transition-colors hover:bg-accent"
                        >
                            Schedule
                        </a>
                    </div>
                </article>

                <div
                    v-if="screens.length === 0"
                    class="rounded-lg border border-dashed p-8 text-center text-sm text-muted-foreground md:col-span-2 xl:col-span-3"
                >
                    Belum ada screen. Buat screen pertama untuk mulai preview.
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div
                class="flex flex-wrap items-center justify-between gap-4 border-b p-5"
            >
                <div>
                    <h2 class="text-lg font-semibold tracking-tight">
                        Setup checklist
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Ikuti urutan ini supaya display pertama cepat tayang.
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div
                        class="grid size-14 place-items-center rounded-full border bg-muted/35 text-sm font-semibold"
                    >
                        {{ setupProgress }}%
                    </div>
                    <div class="text-sm">
                        <div class="font-medium">
                            {{ completedSetup }} of {{ setup.length }} done
                        </div>
                        <div class="text-muted-foreground">
                            Ready for launch
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-3 p-4 lg:grid-cols-5">
                <a
                    v-for="(item, index) in setup"
                    :key="item.key"
                    :href="item.href"
                    class="group rounded-lg border bg-background p-4 transition-colors hover:bg-muted/35"
                >
                    <div class="flex items-start justify-between gap-3">
                        <div
                            class="grid size-8 shrink-0 place-items-center rounded-full text-sm font-semibold"
                            :class="
                                item.done
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-muted text-muted-foreground'
                            "
                        >
                            {{ item.done ? '✓' : index + 1 }}
                        </div>
                        <span
                            class="rounded-full px-2 py-1 text-xs font-medium"
                            :class="
                                item.done
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                            "
                        >
                            {{ item.done ? 'Done' : 'Next' }}
                        </span>
                    </div>

                    <h3 class="mt-4 font-semibold tracking-tight">
                        {{ item.title }}
                    </h3>
                    <p class="mt-2 min-h-12 text-sm text-muted-foreground">
                        {{ item.description }}
                    </p>
                    <div
                        class="mt-4 text-sm font-medium text-primary underline-offset-4 group-hover:underline"
                    >
                        {{ item.action }}
                    </div>
                </a>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div class="flex items-center justify-between border-b px-4 py-3">
                <div>
                    <h2 class="font-semibold tracking-tight">Device status</h2>
                    <p class="text-xs text-muted-foreground">
                        Pantau display yang sudah tersambung.
                    </p>
                </div>
                <a
                    href="/devices"
                    class="rounded-md border bg-background px-3 py-2 text-xs font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Manage
                </a>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[960px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Device</th>
                            <th class="px-4 py-3 font-medium">Screen</th>
                            <th class="px-4 py-3 font-medium">Status</th>
                            <th class="px-4 py-3 font-medium">Health</th>
                            <th class="px-4 py-3 font-medium">Runtime</th>
                            <th class="px-4 py-3 font-medium">Manifest</th>
                            <th class="px-4 py-3 font-medium">Media</th>
                            <th class="px-4 py-3 font-medium">Last seen</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Player
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="device in devices"
                            :key="device.id"
                            class="border-t transition-colors hover:bg-muted/35"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">{{ device.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ device.key }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <div>{{ device.screen?.name ?? '-' }}</div>
                                <div class="text-xs text-muted-foreground">
                                    {{ device.screen?.location ?? '' }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        device.status === 'online'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                    "
                                >
                                    {{ device.status }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div>
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                        :class="
                                            device.health.status === 'ready'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                : device.health.status ===
                                                    'offline'
                                                  ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                                  : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                        "
                                    >
                                        {{ device.health.label }}
                                    </span>
                                    <div
                                        class="mt-1 max-w-56 text-xs text-muted-foreground"
                                    >
                                        {{ device.health.message }}
                                    </div>
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        device.runtime.rust_wasm
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : device.runtime.wasm_supported
                                              ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                              : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                    "
                                >
                                    {{ device.runtime.layout_engine }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        device.runtime.manifest_source ===
                                        'live'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : device.runtime
                                                    .manifest_source === 'cache'
                                              ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                              : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                    "
                                >
                                    {{ device.runtime.manifest_source }}
                                </span>
                                <div class="mt-1 text-xs text-muted-foreground">
                                    {{
                                        device.runtime.network_online === false
                                            ? 'Network offline'
                                            : ''
                                    }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        device.runtime.media_readiness.status ===
                                        'ready'
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : device.runtime.media_readiness
                                                    .status === 'degraded'
                                              ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                              : device.runtime.media_readiness
                                                      .status === 'warming'
                                                ? 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300'
                                                : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                    "
                                >
                                    {{
                                        device.runtime.media_readiness.ready
                                    }}/{{ device.runtime.media_readiness.total }}
                                </span>
                                <div class="mt-1 text-xs text-muted-foreground">
                                    {{ device.runtime.media_readiness.status }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                {{ device.last_seen_at ?? 'Never' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <a
                                    :href="device.player_url"
                                    target="_blank"
                                    class="font-medium text-primary underline-offset-4 hover:underline"
                                >
                                    Open
                                </a>
                            </td>
                        </tr>
                        <tr v-if="devices.length === 0">
                            <td
                                colspan="9"
                                class="px-4 py-10 text-center text-muted-foreground"
                            >
                                Belum ada device.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
