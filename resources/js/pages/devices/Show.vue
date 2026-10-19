<script setup lang="ts">
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Send } from '@lucide/vue';
import { computed, ref } from 'vue';
import { Spinner } from '@/components/ui/spinner';

type Device = {
    id: string;
    screen_id: string | null;
    name: string;
    key: string;
    status: string;
    stored_status: string;
    app_version: string | null;
    last_seen_at: string | null;
    last_seen_iso: string | null;
    is_active: boolean;
    capabilities: Record<string, unknown>;
    runtime: {
        wasm_supported: boolean | null;
        rust_wasm: boolean | null;
        layout_engine: string;
        manifest_source: string;
        manifest_cached: boolean | null;
        manifest_cached_at: string | null;
        network_online: boolean | null;
        service_worker_cache: boolean | null;
        media_readiness: {
            total: number;
            ready: number;
            failed: number;
            pending: number;
            unsupported: number;
            status: string;
            updated_at: string | null;
        };
        viewport: Record<string, unknown> | null;
        user_agent: string | null;
    };
    player_url: string;
    manifest_url: string;
    heartbeat_url: string;
    health: {
        status: string;
        label: string;
        message: string;
    };
    commands: Array<{
        id: string;
        type: string;
        status: string;
        payload: Record<string, unknown>;
        created_at: string | null;
        sent_at: string | null;
        acknowledged_at: string | null;
    }>;
    screen: {
        id: string;
        name: string;
        location: string | null;
        orientation: string;
        resolution: {
            width: number;
            height: number;
        };
        active_schedules_count: number;
        preview_url: string;
    } | null;
};

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
    };
    device: Device;
    commandTypes: Array<{
        type: string;
        label: string;
        description: string;
    }>;
}>();

const commandForm = useForm({
    type: '',
});
const sendingCommand = ref<string | null>(null);

const capabilityEntries = computed(() => {
    return Object.entries(props.device.capabilities ?? {});
});

const runtimeTone = computed(() => {
    if (props.device.runtime.rust_wasm) {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (props.device.runtime.wasm_supported) {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
    }

    return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300';
});

const viewportLabel = computed(() => {
    const viewport = props.device.runtime.viewport;

    if (!viewport) {
        return 'Unknown';
    }

    const width = viewport.width;
    const height = viewport.height;
    const ratio = viewport.device_pixel_ratio;

    if (typeof width !== 'number' || typeof height !== 'number') {
        return 'Unknown';
    }

    return `${width} x ${height}${typeof ratio === 'number' ? ` @${ratio}x` : ''}`;
});

const manifestTone = computed(() => {
    if (props.device.runtime.manifest_source === 'live') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (props.device.runtime.manifest_source === 'cache') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
    }

    return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300';
});

const mediaReadinessTone = computed(() => {
    if (props.device.runtime.media_readiness.status === 'ready') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (props.device.runtime.media_readiness.status === 'warming') {
        return 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300';
    }

    if (props.device.runtime.media_readiness.status === 'degraded') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
    }

    return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300';
});

const mediaReadinessProgress = computed(() => {
    const readiness = props.device.runtime.media_readiness;

    if (readiness.total === 0) {
        return 0;
    }

    return Math.round((readiness.ready / readiness.total) * 100);
});

const healthTone = computed(() => {
    if (props.device.health.status === 'ready') {
        return 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300';
    }

    if (props.device.health.status === 'offline') {
        return 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300';
    }

    return 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300';
});

function sendCommand(type: string): void {
    sendingCommand.value = type;
    commandForm.type = type;
    commandForm.post(`/devices/${props.device.key}/commands`, {
        preserveScroll: true,
        onSuccess: () => commandForm.reset(),
        onFinish: () => {
            sendingCommand.value = null;
        },
    });
}
</script>

<template>
    <Head :title="`Monitor ${device.name}`" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300"
                >
                    Display monitoring
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    {{ device.name }}
                </h1>
                <p class="text-sm text-muted-foreground">
                    {{ tenant.name }} · {{ device.key }}
                </p>
            </div>

            <div class="flex flex-wrap gap-2">
                <Link
                    href="/devices"
                    class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Devices
                </Link>
                <a
                    :href="device.player_url"
                    target="_blank"
                    class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground shadow-xs"
                >
                    Open player
                </a>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div class="space-y-4">
                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <div
                        class="flex flex-wrap items-start justify-between gap-4"
                    >
                        <div>
                            <h2 class="text-lg font-semibold tracking-tight">
                                Health
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                {{ device.health.message }}
                            </p>
                        </div>
                        <span
                            class="rounded-full px-3 py-1 text-sm font-medium"
                            :class="healthTone"
                        >
                            {{ device.health.label }}
                        </span>
                    </div>

                    <div class="mt-5 grid gap-3 sm:grid-cols-3">
                        <div class="rounded-md bg-muted/45 p-3">
                            <div class="text-xs text-muted-foreground">
                                Live status
                            </div>
                            <div class="mt-1 font-semibold">
                                {{ device.status }}
                            </div>
                        </div>
                        <div class="rounded-md bg-muted/45 p-3">
                            <div class="text-xs text-muted-foreground">
                                Last heartbeat
                            </div>
                            <div class="mt-1 font-semibold">
                                {{ device.last_seen_at ?? 'Never' }}
                            </div>
                        </div>
                        <div class="rounded-md bg-muted/45 p-3">
                            <div class="text-xs text-muted-foreground">
                                App version
                            </div>
                            <div class="mt-1 font-semibold">
                                {{ device.app_version ?? '-' }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <div
                        class="flex flex-wrap items-center justify-between gap-3"
                    >
                        <div>
                            <h2 class="text-lg font-semibold tracking-tight">
                                Assigned screen
                            </h2>
                            <p class="text-sm text-muted-foreground">
                                Screen menentukan playlist dan schedule yang
                                akan dibaca player.
                            </p>
                        </div>
                        <a
                            v-if="device.screen"
                            :href="device.screen.preview_url"
                            class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                        >
                            Preview screen
                        </a>
                    </div>

                    <div
                        v-if="device.screen"
                        class="mt-4 grid gap-3 sm:grid-cols-3"
                    >
                        <div class="rounded-md bg-muted/45 p-3">
                            <div class="text-xs text-muted-foreground">
                                Screen
                            </div>
                            <div class="mt-1 font-semibold">
                                {{ device.screen.name }}
                            </div>
                            <div class="text-xs text-muted-foreground">
                                {{ device.screen.location ?? 'No location' }}
                            </div>
                        </div>
                        <div class="rounded-md bg-muted/45 p-3">
                            <div class="text-xs text-muted-foreground">
                                Resolution
                            </div>
                            <div class="mt-1 font-semibold">
                                {{ device.screen.resolution.width }} x
                                {{ device.screen.resolution.height }}
                            </div>
                        </div>
                        <div class="rounded-md bg-muted/45 p-3">
                            <div class="text-xs text-muted-foreground">
                                Active schedules
                            </div>
                            <div class="mt-1 font-semibold">
                                {{ device.screen.active_schedules_count }}
                            </div>
                        </div>
                    </div>

                    <div
                        v-else
                        class="mt-4 rounded-lg border border-dashed p-6 text-sm text-muted-foreground"
                    >
                        Device belum diassign ke screen. Assign dari halaman
                        Devices agar player bisa membaca playlist.
                    </div>
                </div>

                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight">
                            Remote commands
                        </h2>
                        <p class="mt-1 text-sm text-muted-foreground">
                            Command akan dikirim saat player mengirim heartbeat
                            berikutnya.
                        </p>
                    </div>

                    <div class="mt-4 grid gap-3 sm:grid-cols-3">
                        <button
                            v-for="command in commandTypes"
                            :key="command.type"
                            type="button"
                            class="rounded-lg border bg-background p-3 text-left transition-colors hover:bg-muted/35 disabled:cursor-not-allowed disabled:opacity-60"
                            :disabled="sendingCommand !== null"
                            :aria-busy="sendingCommand === command.type"
                            @click="sendCommand(command.type)"
                        >
                            <div
                                class="flex items-center gap-2 text-sm font-semibold"
                            >
                                <Spinner
                                    v-if="sendingCommand === command.type"
                                />
                                <Send v-else class="size-4" />
                                {{ command.label }}
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                {{ command.description }}
                            </div>
                        </button>
                    </div>
                </div>
            </div>

            <aside class="space-y-4">
                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <h2 class="font-semibold tracking-tight">
                        Recent commands
                    </h2>
                    <div
                        v-if="device.commands.length"
                        class="mt-3 divide-y text-sm"
                    >
                        <div
                            v-for="command in device.commands"
                            :key="command.id"
                            class="py-3"
                        >
                            <div
                                class="flex items-center justify-between gap-3"
                            >
                                <div class="font-medium">
                                    {{ command.type.replace('_', ' ') }}
                                </div>
                                <span
                                    class="rounded-full bg-muted px-2 py-1 text-xs font-medium text-muted-foreground"
                                >
                                    {{ command.status }}
                                </span>
                            </div>
                            <div class="mt-1 text-xs text-muted-foreground">
                                Created {{ command.created_at ?? '-' }}
                            </div>
                            <div
                                v-if="command.sent_at"
                                class="text-xs text-muted-foreground"
                            >
                                Sent {{ command.sent_at }}
                            </div>
                        </div>
                    </div>
                    <p v-else class="mt-3 text-sm text-muted-foreground">
                        Belum ada command untuk device ini.
                    </p>
                </div>

                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <h2 class="font-semibold tracking-tight">Player URLs</h2>
                    <div class="mt-4 space-y-3 text-sm">
                        <div>
                            <div class="text-xs text-muted-foreground">
                                Player
                            </div>
                            <a
                                :href="device.player_url"
                                target="_blank"
                                class="font-medium break-all text-primary underline-offset-4 hover:underline"
                            >
                                {{ device.player_url }}
                            </a>
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground">
                                Manifest API
                            </div>
                            <div class="font-mono text-xs break-all">
                                {{ device.manifest_url }}
                            </div>
                        </div>
                        <div>
                            <div class="text-xs text-muted-foreground">
                                Heartbeat API
                            </div>
                            <div class="font-mono text-xs break-all">
                                {{ device.heartbeat_url }}
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="font-semibold tracking-tight">
                                Runtime
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Engine rendering yang dilaporkan player saat
                                heartbeat terakhir.
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="runtimeTone"
                        >
                            {{ device.runtime.layout_engine }}
                        </span>
                    </div>

                    <div class="mt-4 grid gap-3 text-sm">
                        <div
                            class="flex items-center justify-between gap-3 rounded-md bg-muted/45 p-3"
                        >
                            <span class="text-muted-foreground">Manifest</span>
                            <span
                                class="rounded-full px-2 py-1 text-xs font-medium"
                                :class="manifestTone"
                            >
                                {{ device.runtime.manifest_source }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 rounded-md bg-muted/45 p-3"
                        >
                            <span class="text-muted-foreground">Network</span>
                            <span class="font-medium">
                                {{
                                    device.runtime.network_online === null
                                        ? 'Unknown'
                                        : device.runtime.network_online
                                          ? 'Online'
                                          : 'Offline'
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 rounded-md bg-muted/45 p-3"
                        >
                            <span class="text-muted-foreground"
                                >Media cache</span
                            >
                            <span class="font-medium">
                                {{
                                    device.runtime.service_worker_cache === null
                                        ? 'Unknown'
                                        : device.runtime.service_worker_cache
                                          ? 'Active'
                                          : 'Unavailable'
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 rounded-md bg-muted/45 p-3"
                        >
                            <span class="text-muted-foreground"
                                >Rust WASM core</span
                            >
                            <span class="font-medium">
                                {{
                                    device.runtime.rust_wasm === null
                                        ? 'Unknown'
                                        : device.runtime.rust_wasm
                                          ? 'Active'
                                          : 'Fallback JS'
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 rounded-md bg-muted/45 p-3"
                        >
                            <span class="text-muted-foreground"
                                >WebAssembly</span
                            >
                            <span class="font-medium">
                                {{
                                    device.runtime.wasm_supported === null
                                        ? 'Unknown'
                                        : device.runtime.wasm_supported
                                          ? 'Supported'
                                          : 'Not supported'
                                }}
                            </span>
                        </div>
                        <div
                            class="flex items-center justify-between gap-3 rounded-md bg-muted/45 p-3"
                        >
                            <span class="text-muted-foreground">Viewport</span>
                            <span class="font-medium">{{ viewportLabel }}</span>
                        </div>
                    </div>

                    <p
                        v-if="device.runtime.manifest_cached_at"
                        class="mt-3 text-xs text-muted-foreground"
                    >
                        Cached at {{ device.runtime.manifest_cached_at }}
                    </p>
                    <p
                        v-if="device.runtime.user_agent"
                        class="mt-3 text-xs break-words text-muted-foreground"
                    >
                        {{ device.runtime.user_agent }}
                    </p>
                </div>

                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h2 class="font-semibold tracking-tight">
                                Media readiness
                            </h2>
                            <p class="mt-1 text-sm text-muted-foreground">
                                Status preload media image dan video dari
                                playlist aktif.
                            </p>
                        </div>
                        <span
                            class="shrink-0 rounded-full px-2.5 py-1 text-xs font-medium"
                            :class="mediaReadinessTone"
                        >
                            {{ device.runtime.media_readiness.status }}
                        </span>
                    </div>

                    <div class="mt-4">
                        <div class="h-2 overflow-hidden rounded-full bg-muted">
                            <div
                                class="h-full rounded-full bg-primary transition-all"
                                :style="{
                                    width: `${mediaReadinessProgress}%`,
                                }"
                            />
                        </div>
                        <div
                            class="mt-2 flex items-center justify-between text-xs text-muted-foreground"
                        >
                            <span>
                                {{ device.runtime.media_readiness.ready }} /
                                {{ device.runtime.media_readiness.total }} ready
                            </span>
                            <span>{{ mediaReadinessProgress }}%</span>
                        </div>
                    </div>

                    <div class="mt-4 grid grid-cols-3 gap-2 text-xs">
                        <div class="rounded-md bg-muted/45 p-2">
                            <div class="text-muted-foreground">Pending</div>
                            <div class="mt-1 font-semibold">
                                {{ device.runtime.media_readiness.pending }}
                            </div>
                        </div>
                        <div class="rounded-md bg-muted/45 p-2">
                            <div class="text-muted-foreground">Failed</div>
                            <div class="mt-1 font-semibold">
                                {{ device.runtime.media_readiness.failed }}
                            </div>
                        </div>
                        <div class="rounded-md bg-muted/45 p-2">
                            <div class="text-muted-foreground">Web</div>
                            <div class="mt-1 font-semibold">
                                {{ device.runtime.media_readiness.unsupported }}
                            </div>
                        </div>
                    </div>

                    <p
                        v-if="device.runtime.media_readiness.updated_at"
                        class="mt-3 text-xs text-muted-foreground"
                    >
                        Updated
                        {{ device.runtime.media_readiness.updated_at }}
                    </p>
                </div>

                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <h2 class="font-semibold tracking-tight">Capabilities</h2>
                    <dl
                        v-if="capabilityEntries.length"
                        class="mt-3 divide-y text-sm"
                    >
                        <div
                            v-for="[key, value] in capabilityEntries"
                            :key="key"
                            class="flex justify-between gap-3 py-2"
                        >
                            <dt class="text-muted-foreground">{{ key }}</dt>
                            <dd class="text-right font-medium break-all">
                                {{ String(value) }}
                            </dd>
                        </div>
                    </dl>
                    <p v-else class="mt-3 text-sm text-muted-foreground">
                        Belum ada capabilities dari player.
                    </p>
                </div>
            </aside>
        </section>
    </div>
</template>
