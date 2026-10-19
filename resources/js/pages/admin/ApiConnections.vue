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
    ArrowLeft,
    CheckCircle2,
    FlaskConical,
    KeyRound,
    Pencil,
    Plug,
    Plus,
    Search,
    ShieldCheck,
    Trash2,
    X,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type Provider = {
    name: string;
    base_url: string;
    auth_type: string;
};

type PlatformConnection = {
    id: string;
    name: string;
    provider: string;
    auth_type: string;
    has_credential: boolean;
    is_active: boolean;
    created_at: string | null;
};

type TestResult = {
    success: boolean;
    message: string;
    status: number | null;
    latency: number | null;
};

const props = defineProps<{
    providers: Record<string, Provider>;
    connections: PlatformConnection[];
}>();

const formOpen = ref(false);
const editingId = ref<string | null>(null);
const deletingId = ref<string | null>(null);
const testingId = ref<string | null>(null);
const search = ref('');
const stateFilter = ref<'all' | 'active' | 'inactive'>('all');
const testResults = ref<Record<string, TestResult>>({});

const authTypeLabels: Record<string, string> = {
    none: 'Tanpa autentikasi',
    bearer: 'Bearer token',
    api_key: 'API key',
};

const providerKeys = computed(() => Object.keys(props.providers));

const form = useForm({
    name: '',
    provider: providerKeys.value[0] ?? 'open-meteo',
    auth_type: 'none',
    credential: '',
    is_active: true,
});

const editingConnection = computed(() =>
    props.connections.find((connection) => connection.id === editingId.value),
);

const activeCount = computed(
    () => props.connections.filter((connection) => connection.is_active).length,
);

const visibleConnections = computed(() => {
    const term = search.value.trim().toLowerCase();

    return [...props.connections]
        .filter((connection) => {
            const provider = props.providers[connection.provider];
            const matchesTerm =
                term === '' ||
                connection.name.toLowerCase().includes(term) ||
                (provider?.name ?? connection.provider)
                    .toLowerCase()
                    .includes(term) ||
                (provider?.base_url ?? '').toLowerCase().includes(term);
            const matchesState =
                stateFilter.value === 'all' ||
                (stateFilter.value === 'active'
                    ? connection.is_active
                    : !connection.is_active);

            return matchesTerm && matchesState;
        })
        .sort((a, b) => a.name.localeCompare(b.name));
});

function providerName(key: string): string {
    return props.providers[key]?.name ?? key;
}

function providerBaseUrl(key: string): string {
    return props.providers[key]?.base_url ?? '-';
}

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.provider = providerKeys.value[0] ?? 'open-meteo';
    form.auth_type = props.providers[form.provider]?.auth_type ?? 'none';
    form.credential = '';
    form.is_active = true;
}

function openCreate(): void {
    resetForm();
    formOpen.value = true;
}

function openEdit(connection: PlatformConnection): void {
    form.clearErrors();
    editingId.value = connection.id;
    form.name = connection.name;
    form.provider = connection.provider;
    form.auth_type = connection.auth_type;
    form.credential = '';
    form.is_active = connection.is_active;
    formOpen.value = true;
}

function applyProviderDefaults(): void {
    const defaultAuthType = props.providers[form.provider]?.auth_type;

    if (defaultAuthType) {
        form.auth_type = defaultAuthType;
    }
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

    if (editingId.value) {
        form.put(`/admin/api-connections/${editingId.value}`, options);

        return;
    }

    form.post('/admin/api-connections', options);
}

function destroyConnection(connection: PlatformConnection): void {
    if (
        !window.confirm(
            `Hapus koneksi "${connection.name}" dari platform? Playlist yang memakainya akan kehilangan koneksi ini.`,
        )
    ) {
        return;
    }

    deletingId.value = connection.id;
    form.delete(`/admin/api-connections/${connection.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

async function test(connection: PlatformConnection): Promise<void> {
    testingId.value = connection.id;
    const started = performance.now();

    try {
        const response = await fetch(
            `/admin/api-connections/${connection.id}/test`,
            {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'X-CSRF-TOKEN':
                        document
                            .querySelector('meta[name="csrf-token"]')
                            ?.getAttribute('content') ?? '',
                },
            },
        );
        const payload = await response.json().catch(() => ({}));

        testResults.value = {
            ...testResults.value,
            [connection.id]: {
                success: response.ok && payload.success !== false,
                message:
                    payload.message ??
                    (response.ok ? 'Connection successful.' : 'Request gagal.'),
                status: payload.status ?? response.status,
                latency: Math.round(performance.now() - started),
            },
        };
    } catch (error) {
        testResults.value = {
            ...testResults.value,
            [connection.id]: {
                success: false,
                message:
                    error instanceof Error
                        ? error.message
                        : 'Gagal menghubungi server.',
                status: null,
                latency: Math.round(performance.now() - started),
            },
        };
    } finally {
        testingId.value = null;
    }
}
</script>

<template>
    <Head title="API Connections" />

    <div
        class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300"
                >
                    <ShieldCheck class="size-3.5" /> Platform admin
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    API Connections
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Kelola koneksi API tingkat platform. Koneksi aktif dan
                    approved otomatis tersedia untuk dipakai playlist semua
                    tenant.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin">
                        <ArrowLeft class="size-4" /> Dashboard
                    </Link>
                </Button>
                <Button type="button" @click="openCreate">
                    <Plus class="size-4" /> New connection
                </Button>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Connections</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ connections.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Active</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ activeCount }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Providers</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ providerKeys.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    Dengan credential
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{
                        connections.filter(
                            (connection) => connection.has_credential,
                        ).length
                    }}
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
                    placeholder="Cari nama, provider, atau base URL..."
                />
            </div>
            <select
                v-model="stateFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <span class="ml-auto text-xs text-muted-foreground">
                {{ visibleConnections.length }} dari
                {{ connections.length }} koneksi
            </span>
        </section>

        <section
            v-if="visibleConnections.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="connection in visibleConnections"
                :key="connection.id"
                class="flex flex-col rounded-lg border bg-background p-4 shadow-sm"
            >
                <div class="flex items-start justify-between gap-2">
                    <div class="min-w-0">
                        <h2 class="truncate font-semibold">
                            {{ connection.name }}
                        </h2>
                        <p
                            class="mt-0.5 truncate text-xs text-muted-foreground"
                        >
                            {{ providerName(connection.provider) }}
                        </p>
                    </div>
                    <span
                        class="shrink-0 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                        :class="
                            connection.is_active
                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                : 'bg-muted text-muted-foreground'
                        "
                    >
                        {{ connection.is_active ? 'Active' : 'Inactive' }}
                    </span>
                </div>

                <div
                    class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px] text-muted-foreground"
                >
                    <span class="rounded-full bg-muted px-2 py-0.5">
                        {{
                            authTypeLabels[connection.auth_type] ??
                            connection.auth_type
                        }}
                    </span>
                    <span
                        class="inline-flex items-center gap-1 rounded-full bg-muted px-2 py-0.5"
                    >
                        <KeyRound class="size-3" />
                        {{
                            connection.has_credential
                                ? 'Credential set'
                                : 'Tanpa credential'
                        }}
                    </span>
                </div>

                <p class="mt-3 truncate text-xs text-muted-foreground">
                    <code>{{ providerBaseUrl(connection.provider) }}</code>
                </p>

                <div
                    class="mt-3 flex flex-wrap items-center gap-2 border-t pt-3"
                >
                    <SubmitButton
                        type="button"
                        size="sm"
                        variant="outline"
                        :processing="testingId === connection.id"
                        loading-label="Menguji..."
                        :disabled="!connection.is_active"
                        @click="test(connection)"
                    >
                        <FlaskConical class="size-4" /> Test
                    </SubmitButton>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="openEdit(connection)"
                    >
                        <Pencil class="size-4" /> Edit
                    </Button>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        class="text-red-600 hover:text-red-700"
                        :disabled="deletingId === connection.id"
                        @click="destroyConnection(connection)"
                    >
                        <Trash2 class="size-4" /> Hapus
                    </Button>
                    <span
                        v-if="connection.created_at"
                        class="ml-auto text-[11px] text-muted-foreground"
                    >
                        {{ connection.created_at }}
                    </span>
                </div>

                <div
                    v-if="testResults[connection.id]"
                    class="mt-2 flex items-start gap-1.5 rounded-md p-2 text-[11px]"
                    :class="
                        testResults[connection.id].success
                            ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                            : 'bg-red-50 text-red-700 dark:bg-red-500/10 dark:text-red-300'
                    "
                >
                    <CheckCircle2
                        v-if="testResults[connection.id].success"
                        class="mt-0.5 size-3.5 shrink-0"
                    />
                    <XCircle v-else class="mt-0.5 size-3.5 shrink-0" />
                    <span class="min-w-0 flex-1">
                        {{ testResults[connection.id].message }}
                        <span
                            v-if="testResults[connection.id].status"
                            class="opacity-70"
                        >
                            · HTTP {{ testResults[connection.id].status }}
                        </span>
                        <span
                            v-if="testResults[connection.id].latency"
                            class="opacity-70"
                        >
                            · {{ testResults[connection.id].latency }}ms
                        </span>
                    </span>
                </div>
            </article>
        </section>

        <section
            v-else
            class="rounded-lg border border-dashed bg-background p-10 text-center"
        >
            <div
                class="mx-auto grid size-12 place-items-center rounded-full bg-muted"
            >
                <Plug class="size-6 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">
                {{
                    connections.length
                        ? 'Tidak ada koneksi yang cocok'
                        : 'Belum ada koneksi platform'
                }}
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                {{
                    connections.length
                        ? 'Ubah kata kunci atau filter status.'
                        : 'Tambahkan koneksi API agar bisa dipakai playlist tenant.'
                }}
            </p>
        </section>

        <Dialog :open="formOpen" @update:open="handleFormOpenChange">
            <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-lg">
                <form class="space-y-4" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                editingConnection
                                    ? 'Edit connection'
                                    : 'New platform connection'
                            }}
                        </DialogTitle>
                        <DialogDescription>
                            Koneksi disimpan di level platform dan dapat dipakai
                            semua tenant yang aktif.
                        </DialogDescription>
                    </DialogHeader>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Name
                        </label>
                        <Input
                            v-model="form.name"
                            placeholder="Open-Meteo Weather"
                        />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Provider
                            </label>
                            <select
                                v-model="form.provider"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                                @change="applyProviderDefaults"
                            >
                                <option
                                    v-for="key in providerKeys"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ providers[key].name }}
                                </option>
                            </select>
                            <InputError :message="form.errors.provider" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Auth type
                            </label>
                            <select
                                v-model="form.auth_type"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option
                                    v-for="(label, key) in authTypeLabels"
                                    :key="key"
                                    :value="key"
                                >
                                    {{ label }}
                                </option>
                            </select>
                            <InputError :message="form.errors.auth_type" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Credential
                        </label>
                        <Input
                            v-model="form.credential"
                            type="password"
                            autocomplete="off"
                            :placeholder="
                                editingConnection?.has_credential
                                    ? 'Kosongkan untuk mempertahankan credential'
                                    : 'Opsional untuk auth none'
                            "
                        />
                        <InputError :message="form.errors.credential" />
                    </div>

                    <label
                        class="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm"
                    >
                        <span>
                            <span class="font-medium">Active</span>
                            <span class="block text-xs text-muted-foreground">
                                Koneksi aktif dapat dipakai tenant.
                            </span>
                        </span>
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4"
                        />
                    </label>
                    <InputError :message="form.errors.is_active" />

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                <X class="size-4" /> Batal
                            </Button>
                        </DialogClose>
                        <SubmitButton
                            :processing="form.processing"
                            loading-label="Menyimpan..."
                            :label="
                                editingConnection ? 'Save changes' : 'Create'
                            "
                        />
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
