<script setup lang="ts">
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link } from '@inertiajs/vue3';
import {
    CheckCircle2,
    FlaskConical,
    KeyRound,
    Plug,
    Search,
    XCircle,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type Provider = {
    name: string;
    base_url: string;
    auth_type: string;
};

type Connection = {
    id: string;
    name: string;
    provider: string;
    auth_type: string;
    has_credential: boolean;
    is_active: boolean;
    is_approved: boolean;
    source: 'platform' | 'workspace';
    created_at: string | null;
};

type TestResult = {
    success: boolean;
    message: string;
    status: number | null;
    latency: number | null;
};

const props = defineProps<{
    tenant: { id: string; name: string };
    providers: Record<string, Provider>;
    connections: Connection[];
}>();

const search = ref('');
const testingId = ref<string | null>(null);
const testResults = ref<Record<string, TestResult>>({});

const authTypeLabels: Record<string, string> = {
    none: 'Tanpa autentikasi',
    bearer: 'Bearer token',
    api_key: 'API key',
};

const visibleConnections = computed(() => {
    const term = search.value.trim().toLowerCase();

    return [...props.connections]
        .filter((connection) => {
            const provider = props.providers[connection.provider];

            return (
                term === '' ||
                connection.name.toLowerCase().includes(term) ||
                (provider?.name ?? connection.provider)
                    .toLowerCase()
                    .includes(term) ||
                (provider?.base_url ?? '').toLowerCase().includes(term)
            );
        })
        .sort((a, b) => a.name.localeCompare(b.name));
});

function providerName(key: string): string {
    return props.providers[key]?.name ?? key;
}

function providerBaseUrl(key: string): string {
    return props.providers[key]?.base_url ?? '-';
}

async function test(connection: Connection): Promise<void> {
    testingId.value = connection.id;
    const started = performance.now();

    try {
        const response = await fetch(
            `/integrations/api/${connection.id}/test`,
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
                    class="mb-2 inline-flex rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300"
                >
                    Platform integrations
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    API Connections
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Koneksi API dikelola admin aplikasi di platform admin.
                    Gunakan koneksi yang tersedia untuk weather, antrean, dan
                    konten dinamis di playlist {{ tenant.name }}.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link href="/playlists">Playlists</Link>
            </Button>
        </section>

        <div
            class="flex items-start gap-3 rounded-lg border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-800 dark:border-cyan-500/30 dark:bg-cyan-500/10 dark:text-cyan-200"
        >
            <Plug class="mt-0.5 size-4 shrink-0" />
            <p>
                Hubungi admin aplikasi jika butuh koneksi baru atau perubahan
                kredensial. Tenant hanya dapat memakai dan menguji koneksi.
            </p>
        </div>

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
                    <span
                        class="rounded-full px-2 py-0.5"
                        :class="
                            connection.source === 'platform'
                                ? 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300'
                                : 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300'
                        "
                    >
                        {{
                            connection.source === 'platform'
                                ? 'Platform'
                                : 'Workspace'
                        }}
                    </span>
                </div>

                <p class="mt-3 truncate text-xs text-muted-foreground">
                    <code>{{ providerBaseUrl(connection.provider) }}</code>
                </p>

                <div class="mt-3 flex items-center gap-2 border-t pt-3">
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
                        : 'Belum ada koneksi tersedia'
                }}
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                {{
                    connections.length
                        ? 'Ubah kata kunci pencarian.'
                        : 'Admin aplikasi belum menambahkan koneksi API platform. Coba lagi nanti.'
                }}
            </p>
        </section>
    </div>
</template>
