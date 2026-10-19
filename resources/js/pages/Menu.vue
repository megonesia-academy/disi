<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Building2,
    CalendarClock,
    ChevronRight,
    ClipboardList,
    CreditCard,
    LayoutTemplate,
    Plug,
    Settings,
    ShieldCheck,
    Tags,
    Tv,
    Users,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import ThemeToggle from '@/components/ThemeToggle.vue';
import type { Auth } from '@/types';

const page = usePage();
const isAppAdmin = computed(() =>
    Boolean((page.props.auth as Auth | undefined)?.can_manage_platform),
);

const baseGroups = [
    {
        title: 'Operasional',
        description: 'Kelola perangkat, konten dinamis, dan waktu tayang.',
        items: [
            {
                title: 'Devices',
                description: 'Pairing dan pantau perangkat display.',
                href: '/devices',
                icon: Tv,
                color: 'bg-blue-100 text-blue-700 dark:bg-blue-950 dark:text-blue-300',
            },
            {
                title: 'Templates',
                description: 'Buat tampilan reusable untuk berbagai kebutuhan.',
                href: '/templates',
                icon: LayoutTemplate,
                color: 'bg-orange-100 text-orange-700 dark:bg-orange-950 dark:text-orange-300',
            },
            {
                title: 'Schedules',
                description: 'Atur playlist berdasarkan hari dan jam.',
                href: '/schedules',
                icon: CalendarClock,
                color: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            },
        ],
    },
    {
        title: 'Workspace',
        description: 'Pengaturan tim, akun, dan bantuan penggunaan.',
        items: [
            {
                title: 'Workspace Settings',
                description: 'Atur identitas, logo, dan konfigurasi workspace.',
                href: '/workspace/settings',
                icon: Settings,
                color: 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200',
            },
            {
                title: 'Billing',
                description:
                    'Saldo token, langganan bulanan, dan pembelian fitur.',
                href: '/workspace/billing',
                icon: CreditCard,
                color: 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
            },
            {
                title: 'Documentation',
                description: 'Panduan media, template, player, dan API.',
                href: '/documentation',
                icon: BookOpen,
                color: 'bg-rose-100 text-rose-700 dark:bg-rose-950 dark:text-rose-300',
            },
        ],
    },
];

const groups = computed(() => {
    if (!isAppAdmin.value) {
        return baseGroups;
    }

    return [
        ...baseGroups,
        {
            title: 'Platform admin',
            description: 'Area khusus admin aplikasi.',
            items: [
                {
                    title: 'Application overview',
                    description:
                        'Ringkasan tenant, subscriber, dan perangkat platform.',
                    href: '/admin',
                    icon: ShieldCheck,
                    color: 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300',
                },
                {
                    title: 'Platform tenants',
                    description: 'Pantau dan suspend tenant subscriber.',
                    href: '/admin/tenants',
                    icon: Building2,
                    color: 'bg-fuchsia-100 text-fuchsia-700 dark:bg-fuchsia-950 dark:text-fuchsia-300',
                },
                {
                    title: 'Platform users',
                    description: 'Kelola role admin aplikasi dan subscriber.',
                    href: '/admin/users',
                    icon: Users,
                    color: 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
                },
                {
                    title: 'Activity log',
                    description: 'Audit aktivitas seluruh tenant di platform.',
                    href: '/admin/activity',
                    icon: ClipboardList,
                    color: 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
                },
                {
                    title: 'Feature pricing',
                    description:
                        'Atur biaya token dan batas gratis tiap fitur.',
                    href: '/admin/pricing',
                    icon: Tags,
                    color: 'bg-teal-100 text-teal-700 dark:bg-teal-950 dark:text-teal-300',
                },
                {
                    title: 'Platform templates',
                    description:
                        'Kelola katalog template global (gratis/berbayar).',
                    href: '/admin/templates',
                    icon: LayoutTemplate,
                    color: 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300',
                },
                {
                    title: 'API connections',
                    description:
                        'Kelola koneksi API platform untuk dipakai semua tenant.',
                    href: '/admin/api-connections',
                    icon: Plug,
                    color: 'bg-cyan-100 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300',
                },
                {
                    title: 'Token topups',
                    description:
                        'Setujui atau tolak permintaan topup token tenant.',
                    href: '/admin/topups',
                    icon: Wallet,
                    color: 'bg-lime-100 text-lime-700 dark:bg-lime-950 dark:text-lime-300',
                },
            ],
        },
    ];
});
</script>

<template>
    <Head title="Menu" />

    <main class="mx-auto w-full max-w-5xl px-4 py-6 sm:px-6 sm:py-8">
        <div class="mb-7">
            <p class="text-sm font-medium text-primary">DISI Workspace</p>
            <h1 class="mt-1 text-2xl font-semibold text-foreground">Menu</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-muted-foreground">
                Semua pengaturan dan alat operasional dalam satu tempat.
            </p>
        </div>

        <section class="mb-8 md:hidden">
            <h2 class="mb-3 text-base font-semibold text-foreground">
                Tampilan
            </h2>
            <div
                class="flex min-h-16 items-center gap-3 rounded-lg border border-border bg-card p-3.5 shadow-sm"
            >
                <span class="min-w-0 flex-1">
                    <span
                        class="block text-sm font-semibold text-card-foreground"
                        >Tema aplikasi</span
                    >
                    <span
                        class="mt-0.5 block text-xs leading-5 text-muted-foreground"
                    >
                        Gunakan tampilan terang atau gelap.
                    </span>
                </span>
                <ThemeToggle />
            </div>
        </section>

        <div class="space-y-8">
            <section v-for="group in groups" :key="group.title">
                <div class="mb-3">
                    <h2 class="text-base font-semibold text-foreground">
                        {{ group.title }}
                    </h2>
                    <p class="mt-0.5 text-sm text-muted-foreground">
                        {{ group.description }}
                    </p>
                </div>

                <div class="grid gap-3 sm:grid-cols-2">
                    <Link
                        v-for="item in group.items"
                        :key="item.href"
                        :href="item.href"
                        class="group flex min-h-20 items-center gap-3 rounded-lg border border-border bg-card p-3.5 shadow-sm transition hover:border-primary/40 hover:shadow-md focus-visible:ring-2 focus-visible:ring-ring focus-visible:outline-none"
                    >
                        <span
                            class="flex size-10 shrink-0 items-center justify-center rounded-md"
                            :class="item.color"
                        >
                            <component :is="item.icon" class="size-5" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span
                                class="block text-sm font-semibold text-card-foreground"
                            >
                                {{ item.title }}
                            </span>
                            <span
                                class="mt-0.5 block text-xs leading-5 text-muted-foreground"
                            >
                                {{ item.description }}
                            </span>
                        </span>
                        <ChevronRight
                            class="size-4 shrink-0 text-muted-foreground transition-transform group-hover:translate-x-0.5 group-hover:text-primary"
                        />
                    </Link>
                </div>
            </section>
        </div>
    </main>
</template>
