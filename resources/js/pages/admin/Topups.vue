<script setup lang="ts">
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    Clock3,
    Coins,
    ShieldCheck,
    Wallet,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type TopupRequest = {
    id: string;
    tenant: { id: string; name: string; slug: string } | null;
    tokens: number;
    status: string;
    note: string | null;
    requested_by: string | null;
    created_at: string | null;
    approved_at: string | null;
};

const props = defineProps<{
    requests: TopupRequest[];
    pending_count: number;
}>();

const search = ref('');
const statusFilter = ref('all');
const processingId = ref<string | null>(null);

const visibleRequests = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.requests.filter((request) => {
        const matchesTerm =
            term === '' ||
            (request.tenant?.name ?? '').toLowerCase().includes(term) ||
            (request.requested_by ?? '').toLowerCase().includes(term) ||
            (request.note ?? '').toLowerCase().includes(term);
        const matchesStatus =
            statusFilter.value === 'all' ||
            request.status === statusFilter.value;

        return matchesTerm && matchesStatus;
    });
});

function statusTone(status: string): string {
    return (
        {
            pending:
                'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            approved:
                'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
            rejected:
                'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
        }[status] ?? 'bg-muted text-muted-foreground'
    );
}

function approve(request: TopupRequest): void {
    processingId.value = request.id;
    router.patch(
        `/admin/topups/${request.id}/approve`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingId.value = null;
            },
        },
    );
}

function reject(request: TopupRequest): void {
    if (
        !window.confirm(
            `Tolak topup ${request.tokens} token untuk ${request.tenant?.name ?? 'tenant'}?`,
        )
    ) {
        return;
    }

    processingId.value = request.id;
    router.patch(
        `/admin/topups/${request.id}/reject`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                processingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Token Topups" />

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
                    Token topups
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Setujui permintaan topup token dari tenant. Token langsung
                    ditambahkan ke saldo setelah disetujui.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin/pricing">
                        <Coins class="size-4" /> Pricing
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link href="/admin">
                        <ArrowLeft class="size-4" /> Admin dashboard
                    </Link>
                </Button>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Clock3 class="size-4" /> Menunggu approval
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ pending_count }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Wallet class="size-4" /> Total request
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ requests.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <CheckCircle2 class="size-4" /> Disetujui
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{
                        requests.filter(
                            (request) => request.status === 'approved',
                        ).length
                    }}
                </div>
            </div>
        </section>

        <section
            class="flex flex-wrap items-center gap-2 rounded-lg border bg-background p-3 shadow-sm"
        >
            <div class="relative min-w-56 flex-1">
                <Input
                    v-model="search"
                    placeholder="Cari tenant, user, atau catatan..."
                />
            </div>
            <select
                v-model="statusFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua status</option>
                <option value="pending">Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
            <span class="ml-auto text-xs text-muted-foreground">
                {{ visibleRequests.length }} dari {{ requests.length }} request
            </span>
        </section>

        <section class="rounded-lg border bg-background shadow-sm">
            <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="request in visibleRequests"
                    :key="request.id"
                    class="flex flex-col rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b px-4 py-3"
                    >
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold">
                                {{ request.tenant?.name ?? 'Tenant dihapus' }}
                            </h2>
                            <p
                                class="truncate font-mono text-xs text-muted-foreground"
                            >
                                {{ request.tenant?.slug }}
                            </p>
                        </div>
                        <div
                            class="flex shrink-0 flex-wrap items-center justify-end gap-1.5"
                        >
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="statusTone(request.status)"
                            >
                                {{ request.status }}
                            </span>
                        </div>
                    </div>

                    <dl
                        class="grid grid-cols-2 gap-x-4 gap-y-2 px-4 py-3 text-xs"
                    >
                        <div>
                            <dt class="text-muted-foreground">Token</dt>
                            <dd class="mt-0.5 font-semibold">
                                {{ request.tokens }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Diminta oleh</dt>
                            <dd class="mt-0.5 font-medium">
                                {{ request.requested_by ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Waktu</dt>
                            <dd class="mt-0.5 font-medium">
                                {{ request.created_at ?? '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Disetujui</dt>
                            <dd class="mt-0.5 font-medium">
                                {{ request.approved_at ?? '-' }}
                            </dd>
                        </div>
                        <div class="col-span-2">
                            <dt class="text-muted-foreground">Catatan</dt>
                            <dd class="mt-0.5 font-medium">
                                {{ request.note ?? '-' }}
                            </dd>
                        </div>
                    </dl>

                    <div
                        class="mt-auto flex flex-wrap gap-2 border-t bg-muted/20 px-4 py-3"
                    >
                        <div
                            v-if="request.status === 'pending'"
                            class="flex flex-wrap gap-2"
                        >
                            <SubmitButton
                                type="button"
                                size="sm"
                                variant="outline"
                                title="Setujui topup"
                                loading-label="Memproses..."
                                :processing="processingId === request.id"
                                @click="approve(request)"
                            >
                                <CheckCircle2 class="size-4" />
                                Approve
                            </SubmitButton>
                            <SubmitButton
                                type="button"
                                size="sm"
                                variant="destructive"
                                title="Tolak topup"
                                loading-label="Memproses..."
                                :processing="processingId === request.id"
                                @click="reject(request)"
                            >
                                <X class="size-4" />
                                Reject
                            </SubmitButton>
                        </div>
                        <span v-else class="text-xs text-muted-foreground">
                            {{ request.approved_at ?? '-' }}
                        </span>
                    </div>
                </article>
            </div>

            <div
                v-if="visibleRequests.length === 0"
                class="border-t px-4 py-10 text-center text-sm text-muted-foreground"
            >
                <template v-if="requests.length">
                    Tidak ada request yang cocok.
                </template>
                <template v-else>Belum ada permintaan topup.</template>
            </div>
        </section>
    </div>
</template>
