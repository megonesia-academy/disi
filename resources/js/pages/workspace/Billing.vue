<script setup lang="ts">
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    CheckCircle2,
    Clock3,
    Coins,
    ListVideo,
    Monitor,
    Plus,
    Wallet,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type Feature = {
    key: string;
    label: string;
    description: string | null;
    token_cost: number;
    free_limit: number;
    unit_label: string;
    owned: number;
};

type Transaction = {
    id: string;
    type: string;
    tokens: number;
    balance_after: number;
    description: string | null;
    created_at: string | null;
};

type TopupRequest = {
    id: string;
    tokens: number;
    status: string;
    note: string | null;
    created_at: string | null;
};

const props = defineProps<{
    tenant: { id: string; name: string };
    balance: number;
    subscription: {
        status: string;
        renews_at: string | null;
        active: boolean;
        monthly_cost: number;
    };
    usage: {
        screens: number;
        screen_limit: number;
        free_screens: number;
        devices: number;
        device_limit: number;
        free_devices: number;
        playlists: number;
        playlist_limit: number;
        free_playlists: number;
        free_items: number;
        item_cost: number;
    };
    features: Feature[];
    transactions: Transaction[];
    topupRequests: TopupRequest[];
}>();

const page = usePage();
const purchasingKey = ref<string | null>(null);

const billingError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.[
            'billing'
        ] ?? null,
);

const needsTopup = computed(
    () =>
        props.subscription.monthly_cost > 0 &&
        props.balance < props.subscription.monthly_cost,
);

const topupForm = useForm({
    tokens: 100,
    note: '',
});

const subscriptionTone = computed(() =>
    props.subscription.active
        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
        : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
);

function purchase(feature: Feature): void {
    if (props.balance < feature.token_cost) {
        return;
    }

    purchasingKey.value = feature.key;
    router.post(
        '/workspace/billing/purchase',
        { feature_key: feature.key },
        {
            preserveScroll: true,
            onFinish: () => {
                purchasingKey.value = null;
            },
        },
    );
}

function requestTopup(): void {
    topupForm.post('/workspace/billing/topup', {
        preserveScroll: true,
        onSuccess: () => topupForm.reset('note'),
    });
}

function transactionTone(tokens: number): string {
    return tokens >= 0
        ? 'text-emerald-600 dark:text-emerald-400'
        : 'text-red-600 dark:text-red-400';
}

function topupTone(status: string): string {
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
</script>

<template>
    <Head title="Billing & Tokens" />

    <div
        class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex rounded-full bg-amber-50 px-2.5 py-1 text-xs font-medium text-amber-700 dark:bg-amber-950 dark:text-amber-300"
                >
                    Token & langganan
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Billing &amp; tokens
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Kelola saldo token {{ tenant.name }}: topup, beli slot
                    fitur, dan pantau tagihan langganan bulanan.
                </p>
            </div>
            <Button variant="outline" as-child>
                <Link href="/dashboard">
                    <ArrowLeft class="size-4" /> Dashboard
                </Link>
            </Button>
        </section>

        <div
            v-if="billingError"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
        >
            {{ billingError }}
        </div>

        <div
            v-if="!subscription.active"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
        >
            Langganan berstatus <b>{{ subscription.status }}</b
            >. Fitur berbayar diblokir sampai topup langganan disetujui admin.
            Saldo Anda {{ balance }} token; biaya bulanan
            {{ subscription.monthly_cost }} token.
        </div>
        <div
            v-else-if="needsTopup"
            class="rounded-lg border border-amber-200 bg-amber-50 p-3 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
        >
            Saldo {{ balance }} token belum cukup untuk biaya langganan bulanan
            {{ subscription.monthly_cost }} token. Ajukan topup agar langganan
            tidak jatuh ke status past due.
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border bg-background p-5 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Coins class="size-4" /> Saldo token
                </div>
                <div class="mt-2 text-4xl font-semibold tracking-tight">
                    {{ balance }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Token dipakai untuk langganan dan pembelian fitur.
                </p>
            </div>

            <div class="rounded-lg border bg-background p-5 shadow-sm">
                <div class="flex items-center justify-between gap-2">
                    <div
                        class="flex items-center gap-2 text-sm text-muted-foreground"
                    >
                        <Clock3 class="size-4" /> Langganan
                    </div>
                    <span
                        class="inline-flex rounded-full px-2 py-0.5 text-[11px] font-semibold"
                        :class="subscriptionTone"
                    >
                        {{
                            subscription.active ? 'Active' : subscription.status
                        }}
                    </span>
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ subscription.monthly_cost }} token/bulan
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Perpanjangan berikutnya:
                    {{ subscription.renews_at ?? 'belum dijadwalkan' }}
                </p>
            </div>

            <div class="rounded-lg border bg-background p-5 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Monitor class="size-4" /> Screen &amp; device
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ usage.screens }} / {{ usage.screen_limit }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Screen: paket gratis {{ usage.free_screens }} + slot.
                    Device: {{ usage.devices }}/{{ usage.device_limit }} (gratis
                    {{ usage.free_devices }}).
                </p>
            </div>

            <div class="rounded-lg border bg-background p-5 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <ListVideo class="size-4" /> Playlist &amp; item
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ usage.playlists }} / {{ usage.playlist_limit }}
                </div>
                <p class="mt-1 text-xs text-muted-foreground">
                    Paket gratis {{ usage.free_playlists }} playlist dan
                    {{ usage.free_items }} item per playlist. Item berikutnya
                    {{ usage.item_cost }} token/item.
                </p>
            </div>
        </section>

        <section
            class="grid gap-4 lg:grid-cols-[minmax(0,1.4fr)_minmax(0,1fr)]"
        >
            <div class="rounded-lg border bg-background p-5 shadow-sm">
                <div class="mb-4">
                    <h2 class="font-semibold tracking-tight">Fitur tambahan</h2>
                    <p class="text-xs text-muted-foreground">
                        Harga ditentukan platform admin. Token langsung dipotong
                        saat pembelian.
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <article
                        v-for="feature in features"
                        :key="feature.key"
                        class="flex flex-col rounded-lg border p-4"
                    >
                        <h3 class="font-medium">{{ feature.label }}</h3>
                        <p class="mt-1 text-xs leading-5 text-muted-foreground">
                            {{ feature.description ?? 'Fitur tambahan.' }}
                        </p>
                        <div
                            class="mt-2 flex flex-wrap items-center gap-1.5 text-[11px] text-muted-foreground"
                        >
                            <span class="rounded-full bg-muted px-2 py-0.5">
                                {{ feature.token_cost }} token /
                                {{ feature.unit_label }}
                            </span>
                            <span class="rounded-full bg-muted px-2 py-0.5">
                                Gratis {{ feature.free_limit }}
                            </span>
                            <span
                                v-if="feature.owned"
                                class="rounded-full bg-emerald-100 px-2 py-0.5 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300"
                            >
                                Dimiliki {{ feature.owned }}
                            </span>
                        </div>
                        <SubmitButton
                            type="button"
                            size="sm"
                            variant="outline"
                            class="mt-3 self-start"
                            :disabled="balance < feature.token_cost"
                            :title="
                                balance < feature.token_cost
                                    ? 'Saldo token tidak cukup'
                                    : `Beli ${feature.label}`
                            "
                            loading-label="Membeli..."
                            :processing="purchasingKey === feature.key"
                            @click="purchase(feature)"
                        >
                            <Plus class="size-4" />
                            Beli ({{ feature.token_cost }} token)
                        </SubmitButton>
                    </article>
                </div>

                <div
                    v-if="features.length === 0"
                    class="rounded-md border border-dashed p-6 text-center text-sm text-muted-foreground"
                >
                    Belum ada fitur tambahan yang aktif.
                </div>
            </div>

            <div class="space-y-4">
                <form
                    class="rounded-lg border bg-background p-5 shadow-sm"
                    @submit.prevent="requestTopup"
                >
                    <div class="mb-3">
                        <h2
                            class="flex items-center gap-2 font-semibold tracking-tight"
                        >
                            <Wallet class="size-4" /> Ajukan topup
                        </h2>
                        <p class="mt-1 text-xs text-muted-foreground">
                            Permintaan akan ditinjau admin aplikasi.
                        </p>
                    </div>
                    <div class="space-y-2">
                        <div>
                            <label class="mb-1 block text-xs font-medium">
                                Jumlah token
                            </label>
                            <Input
                                v-model="topupForm.tokens"
                                type="number"
                                min="10"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-xs font-medium">
                                Catatan (opsional)
                            </label>
                            <Input
                                v-model="topupForm.note"
                                placeholder="Contoh: transfer BCA 20 Okt"
                            />
                        </div>
                        <SubmitButton
                            type="submit"
                            class="w-full"
                            :processing="topupForm.processing"
                            label="Kirim permintaan topup"
                            loading-label="Mengirim..."
                        >
                            <Plus class="size-4" />
                        </SubmitButton>
                    </div>

                    <div
                        v-if="topupRequests.length"
                        class="mt-4 space-y-1.5 border-t pt-3 text-xs"
                    >
                        <p class="font-medium text-muted-foreground">
                            Riwayat permintaan
                        </p>
                        <div
                            v-for="topup in topupRequests"
                            :key="topup.id"
                            class="flex items-center justify-between gap-2"
                        >
                            <span class="text-muted-foreground">
                                {{ topup.tokens }} token ·
                                {{ topup.created_at }}
                            </span>
                            <span
                                class="rounded-full px-2 py-0.5 font-semibold"
                                :class="topupTone(topup.status)"
                            >
                                {{ topup.status }}
                            </span>
                        </div>
                    </div>
                </form>

                <div
                    class="rounded-lg border border-amber-200/70 bg-amber-50 p-4 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
                >
                    <p class="font-semibold">Cara kerja langganan</p>
                    <p class="mt-1 leading-5">
                        Setiap bulan, biaya langganan ({{
                            subscription.monthly_cost
                        }}
                        token) dipotong otomatis dari saldo. Jika saldo kurang,
                        status menjadi <b>past_due</b> dan fitur berbayar
                        diblokir sampai topup disetujui.
                    </p>
                </div>
            </div>
        </section>

        <section class="rounded-lg border bg-background shadow-sm">
            <div class="border-b px-4 py-3">
                <h2 class="font-semibold tracking-tight">Riwayat transaksi</h2>
                <p class="text-xs text-muted-foreground">
                    20 transaksi terakhir.
                </p>
            </div>
            <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="transaction in transactions"
                    :key="transaction.id"
                    class="flex flex-col rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b px-4 py-3"
                    >
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold">
                                {{
                                    transaction.description ?? transaction.type
                                }}
                            </h2>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ transaction.created_at ?? '-' }}
                            </p>
                        </div>
                        <div
                            class="flex shrink-0 flex-wrap items-center justify-end gap-1.5"
                        >
                            <span
                                v-if="transaction.description"
                                class="inline-flex rounded-full bg-muted px-2 py-0.5 text-[11px] font-semibold text-muted-foreground"
                            >
                                {{ transaction.type }}
                            </span>
                        </div>
                    </div>
                    <dl
                        class="grid grid-cols-2 gap-x-4 gap-y-2 px-4 py-3 text-xs"
                    >
                        <div>
                            <dt class="text-muted-foreground">Token</dt>
                            <dd
                                class="mt-0.5 font-semibold"
                                :class="transactionTone(transaction.tokens)"
                            >
                                {{ transaction.tokens > 0 ? '+' : ''
                                }}{{ transaction.tokens }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Saldo</dt>
                            <dd class="mt-0.5 font-medium">
                                {{ transaction.balance_after }}
                            </dd>
                        </div>
                    </dl>
                </article>
            </div>
            <div
                v-if="transactions.length === 0"
                class="border-t px-4 py-10 text-center text-sm text-muted-foreground"
            >
                Belum ada transaksi.
            </div>
        </section>

        <div
            class="flex flex-wrap items-center gap-2 rounded-lg border bg-background p-4 text-xs text-muted-foreground shadow-sm"
        >
            <CheckCircle2 class="size-3.5" />
            Butuh lebih banyak screen atau playlist? Beli slot tambahan, lalu
            buat screen/playlist baru. Item playlist ke-{{
                usage.free_items + 1
            }}
            dan seterusnya didebit otomatis.
            <Link
                href="/playlists"
                class="ml-auto font-medium text-primary hover:underline"
            >
                Buka playlists
            </Link>
        </div>
    </div>
</template>
