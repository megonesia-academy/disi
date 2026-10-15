<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Activity,
    Clock3,
    ExternalLink,
    MapPin,
    Monitor,
    Pencil,
    Trash2,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type ScreenOption = {
    id: string;
    name: string;
    location: string | null;
};

type Device = {
    id: string;
    screen_id: string | null;
    name: string;
    key: string;
    status: string;
    app_version: string | null;
    last_seen_at: string | null;
    is_active: boolean;
    player_url: string;
    monitor_url: string;
    screen: ScreenOption | null;
    health: {
        status: string;
        label: string;
        message: string;
    };
};

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
    };
    devices: Device[];
    screens: ScreenOption[];
    statuses: string[];
}>();

const editingId = ref<string | null>(null);
const pairingCode = ref('');

const form = useForm({
    screen_id: '',
    name: '',
    key: '',
    status: 'offline',
    app_version: '',
    is_active: true,
});

const editingDevice = computed(() => {
    return props.devices.find((device) => device.id === editingId.value);
});

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.screen_id = '';
    form.status = 'offline';
    form.is_active = true;
}

function editDevice(device: Device): void {
    editingId.value = device.id;
    form.clearErrors();
    form.screen_id = device.screen_id ?? '';
    form.name = device.name;
    form.key = device.key;
    form.status = device.status;
    form.app_version = device.app_version ?? '';
    form.is_active = device.is_active;
}

function submit(): void {
    if (editingDevice.value) {
        form.put(`/devices/${encodeURIComponent(editingDevice.value.key)}`, {
            preserveScroll: true,
            onSuccess: resetForm,
        });

        return;
    }

    form.post('/devices', {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}

function destroyDevice(device: Device): void {
    if (!window.confirm(`Delete device "${device.name}"?`)) {
        return;
    }

    form.delete(`/devices/${encodeURIComponent(device.key)}`, {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}

function openPairingCode(): void {
    const code = pairingCode.value.trim().toUpperCase();

    if (!code) {
        return;
    }

    window.location.href = `/display/pair/${encodeURIComponent(code)}`;
}
</script>

<template>
    <Head title="Devices" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-muted/20 p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Devices</h1>
                <p class="text-sm text-muted-foreground">
                    Register dan assign player untuk {{ tenant.name }}.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-3 text-sm">
                <a
                    href="/display"
                    target="_blank"
                    class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Open display page
                </a>
                <Link
                    href="/screens"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                >
                    Screens
                </Link>
                <Link
                    href="/dashboard"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                >
                    Dashboard
                </Link>
            </div>
        </section>

        <section
            class="flex flex-wrap items-end gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div class="min-w-64 flex-1">
                <div
                    class="mb-2 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                >
                    Quick setup
                </div>
                <h2 class="text-lg font-semibold tracking-tight">
                    Pair New Display
                </h2>
                <p class="text-sm text-muted-foreground">
                    Buka <span class="font-medium">/display</span> di TV, lalu
                    scan QR atau masukkan kode pairing di sini.
                </p>
            </div>

            <form
                class="flex w-full gap-2 sm:w-auto"
                @submit.prevent="openPairingCode"
            >
                <Input
                    v-model="pairingCode"
                    class="h-10 text-center font-mono uppercase tracking-widest sm:w-44"
                    placeholder="ABC-123"
                    autocomplete="off"
                />
                <Button
                    type="submit"
                    class="h-10"
                    :disabled="!pairingCode.trim()"
                >
                    Pair
                </Button>
            </form>
        </section>

        <section
            class="grid gap-4 lg:grid-cols-[minmax(320px,420px)_minmax(0,1fr)]"
        >
            <form
                class="rounded-lg border bg-background p-5 shadow-sm"
                @submit.prevent="submit"
            >
                <div class="mb-4">
                    <h2 class="text-lg font-semibold tracking-tight">
                        {{ editingDevice ? 'Edit device' : 'New device' }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Kosongkan key untuk generate otomatis.
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Name
                        </label>
                        <Input v-model="form.name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Device key
                        </label>
                        <Input v-model="form.key" />
                        <InputError :message="form.errors.key" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Screen
                        </label>
                        <select
                            v-model="form.screen_id"
                            class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">Unassigned</option>
                            <option
                                v-for="screen in screens"
                                :key="screen.id"
                                :value="screen.id"
                            >
                                {{ screen.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.screen_id" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Status
                        </label>
                        <select
                            v-model="form.status"
                            class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option
                                v-for="status in statuses"
                                :key="status"
                                :value="status"
                            >
                                {{ status }}
                            </option>
                        </select>
                        <InputError :message="form.errors.status" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            App version
                        </label>
                        <Input v-model="form.app_version" />
                        <InputError :message="form.errors.app_version" />
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        />
                        Active
                    </label>

                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing">
                            {{ editingDevice ? 'Update' : 'Create' }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="resetForm"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            </form>

            <div class="min-w-0">
                <div class="mb-3 flex items-center justify-between">
                    <h2 class="font-semibold tracking-tight">Device list</h2>
                    <span class="text-xs text-muted-foreground">
                        {{ devices.length }} device
                    </span>
                </div>

                <div v-if="devices.length" class="grid gap-4 2xl:grid-cols-2">
                    <article
                        v-for="device in devices"
                        :key="device.id"
                        class="overflow-hidden rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="flex items-start justify-between gap-4 p-5">
                            <div class="flex min-w-0 gap-3">
                                <div class="grid size-10 shrink-0 place-items-center rounded-md bg-primary/10 text-primary">
                                    <Monitor class="size-5" />
                                </div>
                                <div class="min-w-0">
                                    <h3 class="truncate font-semibold">
                                        {{ device.name }}
                                    </h3>
                                    <p class="truncate font-mono text-xs text-muted-foreground">
                                        {{ device.key }}
                                    </p>
                                </div>
                            </div>
                            <span
                                class="inline-flex shrink-0 items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium"
                                :class="
                                    device.status === 'online'
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                "
                            >
                                <span
                                    class="size-1.5 rounded-full bg-current"
                                />
                                {{ device.status }}
                            </span>
                        </div>

                        <div class="grid gap-3 border-y bg-muted/20 p-5 sm:grid-cols-2">
                            <div class="flex min-w-0 gap-2.5">
                                <MapPin class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <div class="min-w-0">
                                    <p class="text-xs text-muted-foreground">Screen</p>
                                    <p class="truncate text-sm font-medium">
                                        {{ device.screen?.name ?? 'Unassigned' }}
                                    </p>
                                    <p v-if="device.screen?.location" class="truncate text-xs text-muted-foreground">
                                        {{ device.screen.location }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex min-w-0 gap-2.5">
                                <Clock3 class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <p class="text-xs text-muted-foreground">Last seen</p>
                                    <p class="text-sm font-medium">
                                        {{ device.last_seen_at ?? 'Never' }}
                                    </p>
                                </div>
                            </div>
                        </div>

                        <div class="p-5">
                            <div class="flex items-start gap-2.5">
                                <Activity class="mt-0.5 size-4 shrink-0 text-muted-foreground" />
                                <div>
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="text-xs font-medium text-muted-foreground">Health</span>
                                        <span
                                            class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                            :class="
                                                device.health.status === 'ready'
                                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                    : device.health.status === 'offline'
                                                      ? 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                                      : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                            "
                                        >
                                            {{ device.health.label }}
                                        </span>
                                    </div>
                                    <p class="mt-1 text-xs leading-5 text-muted-foreground">
                                        {{ device.health.message }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-2 border-t pt-4">
                                <a
                                    :href="device.monitor_url"
                                    class="inline-flex h-9 items-center gap-2 rounded-md border bg-background px-3 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                                >
                                    <Monitor class="size-4" />
                                    Monitor
                                </a>
                                <a
                                    :href="device.player_url"
                                    target="_blank"
                                    class="inline-flex h-9 items-center gap-2 rounded-md border bg-background px-3 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                                >
                                    <ExternalLink class="size-4" />
                                    Player
                                </a>
                                <div class="ml-auto flex gap-1">
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        title="Edit device"
                                        @click="editDevice(device)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        title="Delete device"
                                        @click="destroyDevice(device)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div v-else class="rounded-lg border bg-background p-4 shadow-sm">
                    <EmptyState
                        title="Belum ada display tersambung"
                        description="Buka /display di TV Android, lalu scan QR atau masukkan kode pairing di panel Pair New Display."
                        action-label="Open display page"
                        action-href="/display"
                        secondary-label="Create screen"
                        secondary-href="/screens"
                    />
                </div>
            </div>
        </section>
    </div>
</template>
