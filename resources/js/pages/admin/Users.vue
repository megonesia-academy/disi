<script setup lang="ts">
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Building2,
    CheckCircle2,
    Search,
    ShieldCheck,
    Users,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type TenantRef = {
    id: string;
    name: string;
    slug: string;
    role: string;
};

type PlatformUser = {
    id: number;
    name: string;
    email: string;
    app_role: 'admin' | 'subscriber';
    is_verified: boolean;
    is_self: boolean;
    tenants_count: number;
    tenants: TenantRef[];
    created_at: string | null;
};

const props = defineProps<{
    users: PlatformUser[];
    admins_count: number;
    subscribers_count: number;
}>();

const page = usePage();
const search = ref('');
const roleFilter = ref('all');
const updatingId = ref<number | null>(null);

const errorMessage = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.[
            'app_role'
        ] ?? null,
);

const visibleUsers = computed(() => {
    const term = search.value.trim().toLowerCase();

    return props.users.filter((user) => {
        const matchesTerm =
            term === '' ||
            user.name.toLowerCase().includes(term) ||
            user.email.toLowerCase().includes(term);
        const matchesRole =
            roleFilter.value === 'all' || user.app_role === roleFilter.value;

        return matchesTerm && matchesRole;
    });
});

function isLastAdmin(user: PlatformUser): boolean {
    return user.app_role === 'admin' && props.admins_count <= 1;
}

function actionTitle(user: PlatformUser): string {
    if (user.is_self) {
        return 'Tidak bisa mengubah role platform akun sendiri';
    }

    if (isLastAdmin(user)) {
        return 'Minimal harus ada satu admin aplikasi';
    }

    return user.app_role === 'admin'
        ? 'Turunkan menjadi subscriber'
        : 'Jadikan admin aplikasi';
}

function toggleRole(user: PlatformUser): void {
    const nextRole = user.app_role === 'admin' ? 'subscriber' : 'admin';

    if (
        nextRole === 'subscriber' &&
        !window.confirm(
            `Turunkan "${user.name}" menjadi subscriber? Akses /admin akan dicabut.`,
        )
    ) {
        return;
    }

    updatingId.value = user.id;
    router.patch(
        `/admin/users/${user.id}/role`,
        { app_role: nextRole },
        {
            preserveScroll: true,
            onFinish: () => {
                updatingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Platform Users" />

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
                    Platform users
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Atur siapa saja yang menjadi admin aplikasi. Admin dapat
                    mengakses area platform, subscriber hanya workspace
                    miliknya.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin">
                        <ArrowLeft class="size-4" /> Admin dashboard
                    </Link>
                </Button>
                <Button variant="outline" as-child>
                    <Link href="/admin/tenants">
                        <Building2 class="size-4" /> Tenants
                    </Link>
                </Button>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Users class="size-4" /> Total users
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ users.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <ShieldCheck class="size-4" /> Admin aplikasi
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ admins_count }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Users class="size-4" /> Subscriber
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ subscribers_count }}
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
                    placeholder="Cari nama atau email user..."
                />
            </div>
            <select
                v-model="roleFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua role platform</option>
                <option value="admin">Admin aplikasi</option>
                <option value="subscriber">Subscriber</option>
            </select>
            <span class="ml-auto text-xs text-muted-foreground">
                {{ visibleUsers.length }} dari {{ users.length }} user
            </span>
        </section>

        <div
            v-if="errorMessage"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
        >
            {{ errorMessage }}
        </div>

        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div class="overflow-x-auto">
                <table class="w-full min-w-[880px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">User</th>
                            <th class="px-4 py-3 font-medium">Role platform</th>
                            <th class="px-4 py-3 font-medium">Tenant</th>
                            <th class="px-4 py-3 font-medium">Bergabung</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Action
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="user in visibleUsers"
                            :key="user.id"
                            class="border-t"
                        >
                            <td class="px-4 py-3">
                                <div
                                    class="flex flex-wrap items-center gap-1.5"
                                >
                                    <span class="font-medium">
                                        {{ user.name }}
                                    </span>
                                    <span
                                        v-if="user.is_self"
                                        class="rounded-full bg-muted px-2 py-0.5 text-[11px] font-medium text-muted-foreground"
                                    >
                                        You
                                    </span>
                                    <span
                                        v-if="user.is_verified"
                                        class="inline-flex items-center gap-1 text-[11px] text-emerald-600"
                                    >
                                        <CheckCircle2 class="size-3" />
                                        verified
                                    </span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ user.email }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-medium"
                                    :class="
                                        user.app_role === 'admin'
                                            ? 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300'
                                            : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-900 dark:text-zinc-400'
                                    "
                                >
                                    <ShieldCheck
                                        v-if="user.app_role === 'admin'"
                                        class="size-3"
                                    />
                                    {{
                                        user.app_role === 'admin'
                                            ? 'Admin aplikasi'
                                            : 'Subscriber'
                                    }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div
                                    v-if="user.tenants.length"
                                    class="flex max-w-64 flex-wrap gap-1"
                                >
                                    <span
                                        v-for="tenant in user.tenants.slice(
                                            0,
                                            2,
                                        )"
                                        :key="tenant.id"
                                        class="max-w-32 truncate rounded-full bg-muted px-2 py-0.5 text-[11px]"
                                        :title="`${tenant.name} · ${tenant.role}`"
                                    >
                                        {{ tenant.name }} · {{ tenant.role }}
                                    </span>
                                    <span
                                        v-if="user.tenants.length > 2"
                                        class="rounded-full bg-muted px-2 py-0.5 text-[11px]"
                                    >
                                        +{{ user.tenants.length - 2 }}
                                    </span>
                                </div>
                                <span
                                    v-else
                                    class="text-xs text-muted-foreground"
                                >
                                    Tanpa tenant
                                </span>
                            </td>
                            <td class="px-4 py-3 text-muted-foreground">
                                {{ user.created_at ?? '-' }}
                            </td>
                            <td class="px-4 py-3 text-right">
                                <SubmitButton
                                    v-if="user.app_role === 'admin'"
                                    type="button"
                                    size="sm"
                                    variant="destructive"
                                    :title="actionTitle(user)"
                                    :disabled="
                                        user.is_self || isLastAdmin(user)
                                    "
                                    loading-label="Menyimpan..."
                                    :processing="updatingId === user.id"
                                    @click="toggleRole(user)"
                                >
                                    Demote
                                </SubmitButton>
                                <SubmitButton
                                    v-else
                                    type="button"
                                    size="sm"
                                    variant="outline"
                                    :title="actionTitle(user)"
                                    loading-label="Menyimpan..."
                                    :processing="updatingId === user.id"
                                    @click="toggleRole(user)"
                                >
                                    <ShieldCheck class="size-4" /> Promote
                                </SubmitButton>
                            </td>
                        </tr>
                        <tr v-if="visibleUsers.length === 0">
                            <td
                                colspan="5"
                                class="px-4 py-10 text-center text-sm text-muted-foreground"
                            >
                                Tidak ada user yang cocok dengan filter.
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>

        <div
            v-if="users.length && !visibleUsers.length"
            class="flex justify-center"
        >
            <Button
                type="button"
                variant="outline"
                @click="
                    search = '';
                    roleFilter = 'all';
                "
            >
                <X class="size-4" /> Reset filter
            </Button>
        </div>
    </div>
</template>
