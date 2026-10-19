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
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    Coins,
    Image,
    ListVideo,
    Monitor,
    Search,
    ShieldCheck,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type TenantMember = {
    id: number;
    name: string;
    email: string;
    app_role: string;
    role: string;
    is_self: boolean;
};

type TenantRow = {
    id: string;
    name: string;
    slug: string;
    billing_email: string | null;
    status: string;
    token_balance: number;
    subscription_status: string;
    subscription_active: boolean;
    subscription_renews_at: string | null;
    owner: { name: string; email: string } | null;
    users_count: number;
    users: TenantMember[];
    screens_count: number;
    devices_count: number;
    playlists_count: number;
    media_assets_count: number;
    created_at: string | null;
};

const props = defineProps<{
    tenants: TenantRow[];
    statuses: string[];
}>();

const search = ref('');
const statusFilter = ref('all');
const updatingId = ref<string | null>(null);
const roleUpdatingId = ref<number | null>(null);
const adjustOpen = ref(false);
const adjustingTenant = ref<TenantRow | null>(null);

const adjustForm = useForm({
    tokens: 50,
    description: '',
});

function openAdjust(tenant: TenantRow): void {
    adjustingTenant.value = tenant;
    adjustForm.reset();
    adjustForm.clearErrors();
    adjustOpen.value = true;
}

function submitAdjust(): void {
    if (!adjustingTenant.value) {
        return;
    }

    adjustForm.post(`/admin/tenants/${adjustingTenant.value.id}/tokens`, {
        preserveScroll: true,
        onSuccess: () => {
            adjustOpen.value = false;
            adjustingTenant.value = null;
            adjustForm.reset();
        },
    });
}

const activeCount = computed(
    () => props.tenants.filter((tenant) => tenant.status === 'active').length,
);

const visibleTenants = computed(() => {
    const term = search.value.trim().toLowerCase();

    return [...props.tenants].filter((tenant) => {
        const matchesTerm =
            term === '' ||
            tenant.name.toLowerCase().includes(term) ||
            tenant.slug.toLowerCase().includes(term) ||
            (tenant.owner?.email ?? '').toLowerCase().includes(term);
        const matchesStatus =
            statusFilter.value === 'all' ||
            tenant.status === statusFilter.value;

        return matchesTerm && matchesStatus;
    });
});

function setStatus(tenant: TenantRow, status: string): void {
    if (
        status === 'suspended' &&
        !window.confirm(
            `Suspend tenant "${tenant.name}"? Subscriber tidak bisa memakai workspace sampai diaktifkan kembali.`,
        )
    ) {
        return;
    }

    updatingId.value = tenant.id;
    router.patch(
        `/admin/tenants/${tenant.id}/status`,
        { status },
        {
            preserveScroll: true,
            onFinish: () => {
                updatingId.value = null;
            },
        },
    );
}

function toggleUserRole(tenant: TenantRow, member: TenantMember): void {
    roleUpdatingId.value = member.id;
    router.patch(
        `/admin/tenants/${tenant.id}/users/${member.id}/role`,
        { app_role: member.app_role === 'admin' ? 'subscriber' : 'admin' },
        {
            preserveScroll: true,
            onFinish: () => {
                roleUpdatingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Platform Tenants" />

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
                    Platform tenants
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    {{ tenants.length }} tenant terdaftar ({{ activeCount }}
                    active). Suspend tenant untuk menghentikan akses subscriber.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin">
                        <ArrowLeft class="size-4" /> Admin dashboard
                    </Link>
                </Button>
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
                    placeholder="Cari nama, slug, atau email owner..."
                />
            </div>
            <select
                v-model="statusFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua status</option>
                <option
                    v-for="status in statuses"
                    :key="status"
                    :value="status"
                >
                    {{ status }}
                </option>
            </select>
            <span class="ml-auto text-xs text-muted-foreground">
                {{ visibleTenants.length }} dari {{ tenants.length }} tenant
            </span>
        </section>

        <section class="rounded-lg border bg-background shadow-sm">
            <div class="grid gap-4 p-4 sm:grid-cols-2 2xl:grid-cols-3">
                <article
                    v-for="tenant in visibleTenants"
                    :key="tenant.id"
                    class="flex flex-col rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b px-4 py-3"
                    >
                        <div class="min-w-0">
                            <h2 class="truncate font-semibold">
                                {{ tenant.name }}
                            </h2>
                            <p
                                class="truncate font-mono text-xs text-muted-foreground"
                            >
                                {{ tenant.slug }}
                            </p>
                            <p
                                v-if="tenant.billing_email"
                                class="truncate text-xs text-muted-foreground"
                            >
                                {{ tenant.billing_email }}
                            </p>
                        </div>
                        <div
                            class="flex shrink-0 flex-wrap items-center justify-end gap-1.5"
                        >
                            <span
                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    tenant.status === 'active'
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                "
                            >
                                {{ tenant.status }}
                            </span>
                            <span
                                class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium"
                                :class="
                                    tenant.subscription_active
                                        ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                        : 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                                "
                            >
                                {{ tenant.subscription_status }}
                            </span>
                        </div>
                    </div>

                    <dl
                        class="grid grid-cols-2 gap-x-4 gap-y-2 px-4 py-3 text-xs"
                    >
                        <div class="col-span-2">
                            <dt class="text-muted-foreground">Owner</dt>
                            <dd v-if="tenant.owner" class="mt-0.5 font-medium">
                                {{ tenant.owner.name }}
                                <span
                                    class="block text-xs font-normal text-muted-foreground"
                                >
                                    {{ tenant.owner.email }}
                                </span>
                            </dd>
                            <dd v-else class="mt-0.5 text-muted-foreground">
                                Tanpa owner
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Token</dt>
                            <dd class="mt-0.5 font-medium">
                                <span class="inline-flex items-center gap-1">
                                    <Coins class="size-3.5 text-amber-500" />
                                    <span class="font-semibold">{{
                                        tenant.token_balance
                                    }}</span>
                                </span>
                            </dd>
                        </div>
                        <div v-if="tenant.subscription_renews_at">
                            <dt class="text-muted-foreground">
                                Langganan s.d.
                            </dt>
                            <dd class="mt-0.5 font-medium">
                                {{ tenant.subscription_renews_at }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Screens</dt>
                            <dd class="mt-0.5 font-medium">
                                <span class="inline-flex items-center gap-1">
                                    <Monitor
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    {{ tenant.screens_count }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Devices</dt>
                            <dd class="mt-0.5 font-medium">
                                {{ tenant.devices_count }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Playlists</dt>
                            <dd class="mt-0.5 font-medium">
                                <span class="inline-flex items-center gap-1">
                                    <ListVideo
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    {{ tenant.playlists_count }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Media</dt>
                            <dd class="mt-0.5 font-medium">
                                <span class="inline-flex items-center gap-1">
                                    <Image
                                        class="size-3.5 text-muted-foreground"
                                    />
                                    {{ tenant.media_assets_count }}
                                </span>
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Dibuat</dt>
                            <dd
                                class="mt-0.5 font-medium text-muted-foreground"
                            >
                                {{ tenant.created_at ?? '-' }}
                            </dd>
                        </div>
                    </dl>

                    <div class="border-t px-4 py-3">
                        <h3
                            class="flex items-center gap-1.5 text-xs font-semibold tracking-tight text-muted-foreground"
                        >
                            <Users class="size-3.5" />
                            Users ({{ tenant.users_count }})
                        </h3>
                        <ul v-if="tenant.users.length" class="mt-2 space-y-2">
                            <li
                                v-for="member in tenant.users"
                                :key="member.id"
                                class="flex items-start justify-between gap-2"
                            >
                                <div class="min-w-0">
                                    <p
                                        class="flex flex-wrap items-center gap-1.5 text-sm font-medium"
                                    >
                                        <span class="truncate">{{
                                            member.name
                                        }}</span>
                                        <span
                                            v-if="member.is_self"
                                            class="rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                                        >
                                            You
                                        </span>
                                    </p>
                                    <p
                                        class="truncate text-xs text-muted-foreground"
                                    >
                                        {{ member.email }}
                                    </p>
                                    <div
                                        class="mt-1 flex flex-wrap items-center gap-1"
                                    >
                                        <span
                                            class="rounded-full bg-muted px-1.5 py-0.5 text-[10px] font-medium text-muted-foreground"
                                        >
                                            {{ member.role }}
                                        </span>
                                        <span
                                            class="rounded-full px-1.5 py-0.5 text-[10px] font-semibold"
                                            :class="
                                                member.app_role === 'admin'
                                                    ? 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300'
                                                    : 'bg-muted text-muted-foreground'
                                            "
                                        >
                                            {{
                                                member.app_role === 'admin'
                                                    ? 'Admin aplikasi'
                                                    : 'Subscriber'
                                            }}
                                        </span>
                                    </div>
                                </div>
                                <SubmitButton
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    :title="
                                        member.app_role === 'admin'
                                            ? 'Jadikan subscriber'
                                            : 'Jadikan admin aplikasi'
                                    "
                                    loading-label="Menyimpan..."
                                    :processing="roleUpdatingId === member.id"
                                    :disabled="member.is_self"
                                    @click="toggleUserRole(tenant, member)"
                                >
                                    {{
                                        member.app_role === 'admin'
                                            ? 'Demote'
                                            : 'Promote'
                                    }}
                                </SubmitButton>
                            </li>
                        </ul>
                        <p v-else class="mt-2 text-xs text-muted-foreground">
                            Belum ada user di tenant ini.
                        </p>
                    </div>

                    <div
                        class="mt-auto flex flex-wrap gap-2 border-t bg-muted/20 px-4 py-3"
                    >
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="openAdjust(tenant)"
                        >
                            <Coins class="size-3.5" /> Token
                        </Button>
                        <SubmitButton
                            v-if="tenant.status === 'active'"
                            type="button"
                            size="sm"
                            variant="destructive"
                            title="Suspend tenant"
                            loading-label="Menonaktifkan..."
                            :processing="updatingId === tenant.id"
                            @click="setStatus(tenant, 'suspended')"
                        >
                            Suspend
                        </SubmitButton>
                        <SubmitButton
                            v-else
                            type="button"
                            size="sm"
                            variant="outline"
                            title="Aktifkan tenant"
                            loading-label="Mengaktifkan..."
                            :processing="updatingId === tenant.id"
                            @click="setStatus(tenant, 'active')"
                        >
                            Activate
                        </SubmitButton>
                    </div>
                </article>
            </div>
            <div
                v-if="visibleTenants.length === 0"
                class="border-t px-4 py-10 text-center text-sm text-muted-foreground"
            >
                <template v-if="tenants.length">
                    Tidak ada tenant yang cocok dengan filter.
                </template>
                <template v-else> Belum ada tenant terdaftar. </template>
            </div>
        </section>

        <div
            v-if="tenants.length && !visibleTenants.length"
            class="flex justify-center"
        >
            <Button
                type="button"
                variant="outline"
                @click="
                    search = '';
                    statusFilter = 'all';
                "
            >
                <X class="size-4" /> Reset filter
            </Button>
        </div>

        <section
            v-if="!tenants.length"
            class="rounded-lg border border-dashed bg-background p-10 text-center"
        >
            <div
                class="mx-auto grid size-12 place-items-center rounded-full bg-muted"
            >
                <Building2 class="size-6 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">
                Belum ada tenant
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Tenant akan muncul otomatis saat subscriber mendaftar atau
                membuat workspace.
            </p>
        </section>

        <Dialog :open="adjustOpen" @update:open="adjustOpen = $event">
            <DialogContent class="sm:max-w-md">
                <form class="space-y-4" @submit.prevent="submitAdjust">
                    <DialogHeader>
                        <DialogTitle>
                            Adjust token
                            {{ adjustingTenant?.name ?? 'tenant' }}
                        </DialogTitle>
                        <DialogDescription>
                            Saldo saat ini
                            <b>{{ adjustingTenant?.token_balance ?? 0 }}</b>
                            token. Nilai positif menambah, negatif mengurangi.
                            Perubahan tercatat di riwayat transaksi tenant.
                        </DialogDescription>
                    </DialogHeader>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Jumlah token
                        </label>
                        <Input
                            v-model="adjustForm.tokens"
                            type="number"
                            placeholder="50 atau -25"
                        />
                        <InputError :message="adjustForm.errors.tokens" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Catatan (opsional)
                        </label>
                        <Input
                            v-model="adjustForm.description"
                            placeholder="Bonus / koreksi saldo"
                        />
                        <InputError :message="adjustForm.errors.description" />
                    </div>

                    <DialogFooter class="gap-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                <X class="size-4" /> Batal
                            </Button>
                        </DialogClose>
                        <SubmitButton
                            :processing="adjustForm.processing"
                            loading-label="Menyimpan..."
                            label="Simpan penyesuaian"
                        />
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
