<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import {
    BookOpen,
    Building2,
    CalendarClock,
    ClipboardList,
    CreditCard,
    Image,
    LayoutGrid,
    LayoutTemplate,
    Plug,
    ListVideo,
    Monitor,
    Settings,
    ShieldCheck,
    Tags,
    Tv,
    Wallet,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogo from '@/components/AppLogo.vue';
import InstallAppButton from '@/components/InstallAppButton.vue';
import NavFooter from '@/components/NavFooter.vue';
import NavMain from '@/components/NavMain.vue';
import NavUser from '@/components/NavUser.vue';
import TenantSwitcher from '@/components/TenantSwitcher.vue';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarTrigger,
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { Auth, NavItem } from '@/types';

const page = usePage();

const isAppAdmin = computed(() =>
    Boolean((page.props.auth as Auth | undefined)?.can_manage_platform),
);

const isAdminArea = computed(() => page.url.split('?')[0].startsWith('/admin'));

const logoHref = computed(() => (isAdminArea.value ? '/admin' : dashboard()));

type NavGroup = {
    label: string;
    items: NavItem[];
};

const tenantNavGroups: NavGroup[] = [
    {
        label: 'Operasional',
        items: [
            {
                title: 'Dashboard',
                href: dashboard(),
                icon: LayoutGrid,
            },
            {
                title: 'Screens',
                href: '/screens',
                icon: Monitor,
            },
            {
                title: 'Devices',
                href: '/devices',
                icon: Tv,
            },
            {
                title: 'Playlists',
                href: '/playlists',
                icon: ListVideo,
            },
            {
                title: 'Schedules',
                href: '/schedules',
                icon: CalendarClock,
            },
        ],
    },
    {
        label: 'Konten',
        items: [
            {
                title: 'Media',
                href: '/media-assets',
                icon: Image,
            },
            {
                title: 'Templates',
                href: '/templates',
                icon: LayoutTemplate,
            },
        ],
    },
    {
        label: 'Workspace',
        items: [
            {
                title: 'Settings',
                href: '/workspace/settings',
                icon: Settings,
            },
            {
                title: 'Billing',
                href: '/workspace/billing',
                icon: CreditCard,
            },
        ],
    },
];

const platformNavGroups: NavGroup[] = [
    {
        label: 'Platform',
        items: [
            {
                title: 'Admin dashboard',
                href: '/admin',
                icon: ShieldCheck,
            },
            {
                title: 'Platform tenants',
                href: '/admin/tenants',
                icon: Building2,
            },
            {
                title: 'Activity log',
                href: '/admin/activity',
                icon: ClipboardList,
            },
        ],
    },
    {
        label: 'Katalog & billing',
        items: [
            {
                title: 'Feature pricing',
                href: '/admin/pricing',
                icon: Tags,
            },
            {
                title: 'Platform templates',
                href: '/admin/templates',
                icon: LayoutTemplate,
            },
            {
                title: 'API connections',
                href: '/admin/api-connections',
                icon: Plug,
            },
            {
                title: 'Token topups',
                href: '/admin/topups',
                icon: Wallet,
            },
        ],
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Documentation',
        href: '/documentation',
        icon: BookOpen,
    },
];

const navigationGroups = computed<NavGroup[]>(() =>
    isAdminArea.value && isAppAdmin.value ? platformNavGroups : tenantNavGroups,
);
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader class="relative">
            <SidebarTrigger
                class="absolute top-1/2 right-0 z-30 size-7 translate-x-1/2 -translate-y-1/2 rounded-full border bg-background text-foreground shadow-sm transition-colors hover:bg-accent"
            />
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="logoHref">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
                <SidebarMenuItem
                    v-if="isAppAdmin && !isAdminArea"
                    class="md:hidden"
                >
                    <SidebarMenuButton as-child>
                        <Link href="/admin">
                            <ShieldCheck />
                            <span>Platform admin</span>
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <div
                v-if="!isAdminArea"
                class="group-data-[collapsible=icon]:hidden"
            >
                <TenantSwitcher />
            </div>

            <NavMain
                v-for="group in navigationGroups"
                :key="group.label"
                :items="group.items"
                :label="group.label"
            />
        </SidebarContent>

        <SidebarFooter class="border-t border-sidebar-border/80 bg-black/10">
            <InstallAppButton />
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
