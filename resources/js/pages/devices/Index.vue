<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
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
    if (editingId.value) {
        form.put(`/devices/${editingId.value}`, {
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

    form.delete(`/devices/${device.id}`, {
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

            <div
                class="overflow-hidden rounded-lg border bg-background shadow-sm"
            >
                <div class="flex items-center justify-between border-b px-4 py-3">
                    <h2 class="font-semibold tracking-tight">Device list</h2>
                    <span class="text-xs text-muted-foreground">
                        {{ devices.length }} device
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[860px] text-sm">
                        <thead
                            class="bg-muted/50 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">Device</th>
                                <th class="px-4 py-3 font-medium">Screen</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">Health</th>
                                <th class="px-4 py-3 font-medium">
                                    Last seen
                                </th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="device in devices"
                                :key="device.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ device.name }}
                                    </div>
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
                                    {{ device.last_seen_at ?? 'Never' }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <a
                                            :href="device.monitor_url"
                                            class="rounded-md border px-3 py-1.5 text-xs font-medium"
                                        >
                                            Monitor
                                        </a>
                                        <a
                                            :href="device.player_url"
                                            target="_blank"
                                            class="rounded-md border px-3 py-1.5 text-xs font-medium"
                                        >
                                            Player
                                        </a>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="editDevice(device)"
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="destroyDevice(device)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="devices.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-6"
                                >
                                    <EmptyState
                                        title="Belum ada display tersambung"
                                        description="Buka /display di TV Android, lalu scan QR atau masukkan kode pairing di panel Pair New Display."
                                        action-label="Open display page"
                                        action-href="/display"
                                        secondary-label="Create screen"
                                        secondary-href="/screens"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</template>
