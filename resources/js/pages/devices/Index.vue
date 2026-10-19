<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Check,
    Clock3,
    ExternalLink,
    Link2,
    MapPin,
    Monitor,
    Pencil,
    Plus,
    Search,
    Trash2,
    X,
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
    runtime: {
        layout_engine: string;
        manifest_source: string;
        manifest_cached: boolean | null;
        network_online: boolean | null;
        media_readiness: {
            total: number;
            ready: number;
            failed: number;
            pending: number;
            status: string;
        };
    };
};

type DeviceUsage = {
    devices: number;
    device_limit: number;
    free_devices: number;
    can_add_device: boolean;
    balance: number;
};

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
    };
    devices: Device[];
    screens: ScreenOption[];
    statuses: string[];
    usage: DeviceUsage;
}>();

const formOpen = ref(false);
const pairingOpen = ref(false);
const editingId = ref<string | null>(null);
const deletingId = ref<string | null>(null);
const copiedId = ref<string | null>(null);
const pairingCode = ref('');
const search = ref('');
const statusFilter = ref<'all' | 'online' | 'offline'>('all');
const assignmentFilter = ref<'all' | 'assigned' | 'unassigned'>('all');

const form = useForm({
    screen_id: '',
    name: '',
    key: '',
    status: 'offline',
    app_version: '',
    is_active: true,
});

const editingDevice = computed(() =>
    props.devices.find((device) => device.id === editingId.value),
);

const onlineCount = computed(
    () => props.devices.filter((device) => device.status === 'online').length,
);

const readyCount = computed(
    () =>
        props.devices.filter((device) => device.health.status === 'ready')
            .length,
);

const attentionCount = computed(
    () =>
        props.devices.filter((device) => device.health.status !== 'ready')
            .length,
);

const unassignedCount = computed(
    () => props.devices.filter((device) => !device.screen_id).length,
);

const visibleDevices = computed(() => {
    const term = search.value.trim().toLowerCase();

    return [...props.devices]
        .filter((device) => {
            const matchesTerm =
                term === '' ||
                device.name.toLowerCase().includes(term) ||
                device.key.toLowerCase().includes(term) ||
                (device.screen?.name ?? '').toLowerCase().includes(term);
            const matchesStatus =
                statusFilter.value === 'all' ||
                device.status === statusFilter.value;
            const matchesAssignment =
                assignmentFilter.value === 'all' ||
                (assignmentFilter.value === 'assigned'
                    ? Boolean(device.screen_id)
                    : !device.screen_id);

            return matchesTerm && matchesStatus && matchesAssignment;
        })
        .sort((a, b) => a.name.localeCompare(b.name));
});

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.screen_id = '';
    form.key = '';
    form.status = 'offline';
    form.app_version = '';
    form.is_active = true;
}

function openCreate(): void {
    resetForm();
    formOpen.value = true;
}

function openEdit(device: Device): void {
    form.clearErrors();
    editingId.value = device.id;
    form.screen_id = device.screen_id ?? '';
    form.name = device.name;
    form.key = device.key;
    form.status = device.status;
    form.app_version = device.app_version ?? '';
    form.is_active = device.is_active;
    formOpen.value = true;
}

function handleFormOpenChange(next: boolean): void {
    if (next) {
        formOpen.value = true;

        return;
    }

    formOpen.value = false;
    resetForm();
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
            resetForm();
        },
    };

    if (editingDevice.value) {
        form.put(
            `/devices/${encodeURIComponent(editingDevice.value.key)}`,
            options,
        );

        return;
    }

    form.post('/devices', options);
}

function destroyDevice(device: Device): void {
    if (!window.confirm(`Delete device "${device.name}"?`)) {
        return;
    }

    deletingId.value = device.id;
    form.delete(`/devices/${encodeURIComponent(device.key)}`, {
        preserveScroll: true,
        onSuccess: resetForm,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

function openPairingDialog(): void {
    pairingCode.value = '';
    pairingOpen.value = true;
}

function openPairingCode(): void {
    const code = pairingCode.value.trim().toUpperCase();

    if (!code) {
        return;
    }

    window.location.href = `/display/pair/${encodeURIComponent(code)}`;
}

function copyKey(device: Device): void {
    if (!navigator.clipboard) {
        return;
    }

    navigator.clipboard
        .writeText(device.key)
        .then(() => {
            copiedId.value = device.id;
            window.setTimeout(() => {
                if (copiedId.value === device.id) {
                    copiedId.value = null;
                }
            }, 1500);
        })
        .catch(() => {
            copiedId.value = null;
        });
}

function healthClass(status: string): string {
    return (
        {
            ready: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            offline:
                'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            no_schedule:
                'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
            unassigned:
                'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
            inactive:
                'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300',
        }[status] ??
        'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
    );
}

function hasRuntimeInfo(device: Device): boolean {
    return (
        device.runtime.layout_engine !== 'unknown' ||
        device.runtime.manifest_source !== 'unknown' ||
        device.runtime.network_online !== null ||
        device.runtime.media_readiness.total > 0
    );
}
</script>

<template>
    <Head title="Devices" />

    <div
        class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex rounded-full bg-emerald-50 px-2.5 py-1 text-xs font-medium text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                >
                    Player fleet
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">Devices</h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Register dan assign player untuk {{ tenant.name }}. Pantau
                    status, kesehatan, dan runtime player di satu tempat.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/screens">
                        <Monitor class="size-4" />
                        Screens
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <a href="/display" target="_blank">
                        <ExternalLink class="size-4" />
                        Open display page
                    </a>
                </Button>
                <Button
                    type="button"
                    variant="outline"
                    :disabled="!usage.can_add_device"
                    :title="
                        usage.can_add_device
                            ? 'Pair display baru'
                            : 'Batas device tercapai. Beli slot device tambahan di halaman Billing.'
                    "
                    @click="usage.can_add_device && openPairingDialog()"
                >
                    <Link2 class="size-4" /> Pair display
                </Button>
                <Button
                    type="button"
                    :disabled="!usage.can_add_device"
                    :title="
                        usage.can_add_device
                            ? 'Tambah device baru'
                            : 'Batas device tercapai. Beli slot device tambahan di halaman Billing.'
                    "
                    @click="usage.can_add_device && openCreate()"
                >
                    <Plus class="size-4" /> New device
                </Button>
            </div>
        </section>

        <div
            v-if="!usage.can_add_device"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
        >
            <span class="min-w-0 flex-1">
                Batas device paket Anda tercapai ({{ usage.devices }}/{{
                    usage.device_limit
                }}). Beli slot device tambahan untuk mendaftarkan player baru.
            </span>
            <Link
                href="/workspace/billing"
                class="shrink-0 font-semibold underline underline-offset-4"
            >
                Buka billing
            </Link>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    Devices
                    <span class="text-xs"
                        >({{ usage.devices }}/{{ usage.device_limit }})</span
                    >
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ devices.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Online</div>
                <div class="mt-2 text-2xl font-semibold">{{ onlineCount }}</div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Ready</div>
                <div class="mt-2 text-2xl font-semibold">{{ readyCount }}</div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Need attention</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ attentionCount }}
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
                    placeholder="Cari nama, key, atau screen..."
                />
            </div>
            <select
                v-model="statusFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua status</option>
                <option value="online">Online</option>
                <option value="offline">Offline</option>
            </select>
            <select
                v-model="assignmentFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua assignment</option>
                <option value="assigned">Sudah di-assign</option>
                <option value="unassigned">Belum di-assign</option>
            </select>
            <span class="ml-auto text-xs text-muted-foreground">
                {{ visibleDevices.length }} dari {{ devices.length }} device ·
                {{ unassignedCount }} belum di-assign
            </span>
        </section>

        <section v-if="visibleDevices.length" class="grid gap-4 lg:grid-cols-2">
            <article
                v-for="device in visibleDevices"
                :key="device.id"
                class="flex flex-col overflow-hidden rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="flex items-start gap-3 p-4">
                    <div
                        class="grid size-11 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"
                    >
                        <Monitor class="size-5" />
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex flex-wrap items-center gap-1.5">
                            <h2 class="truncate font-semibold">
                                {{ device.name }}
                            </h2>
                            <span
                                class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="
                                    device.status === 'online'
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400'
                                "
                            >
                                <span
                                    class="size-1.5 rounded-full bg-current"
                                />
                                {{ device.status }}
                            </span>
                            <span
                                class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="healthClass(device.health.status)"
                            >
                                {{ device.health.label }}
                            </span>
                            <span
                                v-if="!device.is_active"
                                class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-semibold text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400"
                            >
                                Inactive
                            </span>
                        </div>
                        <div class="mt-1 flex items-center gap-1.5">
                            <code
                                class="truncate rounded bg-muted px-1.5 py-0.5 font-mono text-[11px]"
                                :title="device.key"
                            >
                                {{ device.key }}
                            </code>
                            <button
                                type="button"
                                class="rounded p-0.5 text-muted-foreground transition-colors hover:text-foreground"
                                :title="
                                    copiedId === device.id
                                        ? 'Key disalin'
                                        : 'Copy device key'
                                "
                                :aria-label="`Copy key ${device.key}`"
                                @click="copyKey(device)"
                            >
                                <Check
                                    v-if="copiedId === device.id"
                                    class="size-3.5 text-emerald-500"
                                />
                                <Link2 v-else class="size-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div class="grid gap-2 px-4 pb-3 text-xs sm:grid-cols-2">
                    <div class="flex min-w-0 items-start gap-1.5">
                        <MapPin
                            class="mt-0.5 size-3.5 shrink-0 text-muted-foreground"
                        />
                        <div class="min-w-0">
                            <p class="text-muted-foreground">Screen</p>
                            <p class="truncate font-medium">
                                {{ device.screen?.name ?? 'Belum di-assign' }}
                            </p>
                            <p
                                v-if="device.screen?.location"
                                class="truncate text-muted-foreground"
                            >
                                {{ device.screen.location }}
                            </p>
                        </div>
                    </div>
                    <div class="flex min-w-0 items-start gap-1.5">
                        <Clock3
                            class="mt-0.5 size-3.5 shrink-0 text-muted-foreground"
                        />
                        <div class="min-w-0">
                            <p class="text-muted-foreground">Last seen</p>
                            <p class="font-medium">
                                {{ device.last_seen_at ?? 'Belum pernah' }}
                            </p>
                            <p
                                v-if="device.app_version"
                                class="truncate text-muted-foreground"
                            >
                                v{{ device.app_version }}
                            </p>
                        </div>
                    </div>
                </div>

                <p class="px-4 pb-3 text-xs leading-5 text-muted-foreground">
                    {{ device.health.message }}
                </p>

                <div
                    v-if="hasRuntimeInfo(device)"
                    class="mx-4 mb-3 flex flex-wrap gap-1.5 rounded-md border bg-muted/30 p-2 text-[11px] text-muted-foreground"
                >
                    <span
                        v-if="device.runtime.layout_engine !== 'unknown'"
                        class="rounded-full bg-background px-2 py-0.5"
                    >
                        engine: {{ device.runtime.layout_engine }}
                    </span>
                    <span
                        v-if="device.runtime.manifest_source !== 'unknown'"
                        class="rounded-full bg-background px-2 py-0.5"
                    >
                        manifest: {{ device.runtime.manifest_source }}
                    </span>
                    <span
                        v-if="device.runtime.network_online !== null"
                        class="rounded-full bg-background px-2 py-0.5"
                        :class="
                            device.runtime.network_online
                                ? 'text-emerald-600'
                                : 'text-amber-600'
                        "
                    >
                        {{
                            device.runtime.network_online
                                ? 'network online'
                                : 'network offline'
                        }}
                    </span>
                    <span
                        v-if="device.runtime.media_readiness.total > 0"
                        class="rounded-full bg-background px-2 py-0.5"
                    >
                        media {{ device.runtime.media_readiness.ready }}/{{
                            device.runtime.media_readiness.total
                        }}
                    </span>
                </div>

                <div
                    class="mt-auto flex flex-wrap items-center gap-1 border-t p-3"
                >
                    <Button size="sm" variant="outline" as-child>
                        <a :href="device.monitor_url">
                            <Monitor class="size-4" /> Monitor
                        </a>
                    </Button>
                    <Button size="sm" variant="outline" as-child>
                        <a :href="device.player_url" target="_blank">
                            <ExternalLink class="size-4" /> Player
                        </a>
                    </Button>
                    <div class="ml-auto flex gap-1">
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            title="Edit device"
                            aria-label="Edit device"
                            @click="openEdit(device)"
                        >
                            <Pencil class="size-4" />
                        </Button>
                        <SubmitButton
                            type="button"
                            size="icon"
                            variant="ghost"
                            class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                            title="Delete device"
                            aria-label="Delete device"
                            loading-label="Menghapus..."
                            :processing="deletingId === device.id"
                            @click="destroyDevice(device)"
                        >
                            <Trash2 class="size-4" />
                        </SubmitButton>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-else-if="devices.length"
            class="rounded-lg border border-dashed bg-background p-10 text-center"
        >
            <div
                class="mx-auto grid size-12 place-items-center rounded-full bg-muted"
            >
                <Search class="size-6 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">
                Tidak ada device yang cocok
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Ubah kata kunci, status, atau filter assignment.
            </p>
            <div class="mt-5 flex justify-center">
                <Button
                    type="button"
                    variant="outline"
                    @click="
                        search = '';
                        statusFilter = 'all';
                        assignmentFilter = 'all';
                    "
                >
                    <X class="size-4" /> Reset filter
                </Button>
            </div>
        </section>

        <section
            v-else
            class="rounded-lg border border-dashed bg-background p-10 text-center"
        >
            <div
                class="mx-auto grid size-12 place-items-center rounded-full bg-muted"
            >
                <Monitor class="size-6 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">
                Belum ada device
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Buka <span class="font-medium">/display</span> di TV, lalu
                masukkan kode pairing untuk menghubungkan player, atau tambahkan
                device manual.
            </p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <Button type="button" @click="openCreate">
                    <Plus class="size-4" /> New device
                </Button>
                <Button
                    variant="outline"
                    type="button"
                    @click="openPairingDialog"
                >
                    <Link2 class="size-4" /> Pair display
                </Button>
                <Button variant="outline" as-child>
                    <Link href="/screens">Create screen</Link>
                </Button>
            </div>
        </section>

        <Dialog :open="formOpen" @update:open="handleFormOpenChange">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <form class="space-y-4" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>
                            {{ editingDevice ? 'Edit device' : 'New device' }}
                        </DialogTitle>
                        <DialogDescription>
                            Assign device ke screen dan atur status player.
                            Kosongkan key untuk generate otomatis.
                        </DialogDescription>
                    </DialogHeader>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Name
                        </label>
                        <Input v-model="form.name" placeholder="TV Lobby" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Device key
                        </label>
                        <Input
                            v-model="form.key"
                            placeholder="Kosongkan untuk generate otomatis"
                            class="font-mono text-sm"
                        />
                        <p class="mt-1 text-[11px] text-muted-foreground">
                            Key dipakai pada URL player dan harus unik.
                        </p>
                        <InputError :message="form.errors.key" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Screen
                        </label>
                        <select
                            v-model="form.screen_id"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option value="">Unassigned</option>
                            <option
                                v-for="screen in screens"
                                :key="screen.id"
                                :value="screen.id"
                            >
                                {{ screen.name }}
                                {{
                                    screen.location
                                        ? `· ${screen.location}`
                                        : ''
                                }}
                            </option>
                        </select>
                        <InputError :message="form.errors.screen_id" />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Status
                            </label>
                            <select
                                v-model="form.status"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
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
                            <Input
                                v-model="form.app_version"
                                placeholder="1.0.0"
                            />
                            <InputError :message="form.errors.app_version" />
                        </div>
                    </div>

                    <label
                        class="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm"
                    >
                        <span>
                            <span class="font-medium">Active</span>
                            <span class="block text-xs text-muted-foreground">
                                Device aktif akan menerima konten dan command.
                            </span>
                        </span>
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        />
                    </label>

                    <DialogFooter class="gap-2 pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                <X class="size-4" /> Cancel
                            </Button>
                        </DialogClose>
                        <SubmitButton
                            :processing="form.processing"
                            :label="editingDevice ? 'Update' : 'Create'"
                            loading-label="Menyimpan..."
                        >
                            <Pencil v-if="editingDevice" class="size-4" />
                            <Plus v-else class="size-4" />
                        </SubmitButton>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>

        <Dialog v-model:open="pairingOpen">
            <DialogContent class="sm:max-w-md">
                <DialogHeader>
                    <DialogTitle>Pair new display</DialogTitle>
                    <DialogDescription>
                        Buka <span class="font-medium">/display</span> di TV,
                        lalu masukkan kode pairing yang tampil di layar.
                    </DialogDescription>
                </DialogHeader>

                <form class="space-y-3" @submit.prevent="openPairingCode">
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Pairing code
                        </label>
                        <Input
                            v-model="pairingCode"
                            class="text-center font-mono text-lg tracking-widest uppercase"
                            placeholder="ABC-123"
                            autocomplete="off"
                        />
                    </div>
                    <p class="text-[11px] text-muted-foreground">
                        Kode biasanya ditampilkan bersama QR di halaman display.
                    </p>

                    <DialogFooter class="gap-2 pt-1">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                <X class="size-4" /> Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" :disabled="!pairingCode.trim()">
                            <Link2 class="size-4" /> Continue
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
