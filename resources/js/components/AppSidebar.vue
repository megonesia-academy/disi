<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import {
    BookOpen,
    CalendarClock,
    ClipboardList,
    FolderGit2,
    Image,
    LayoutGrid,
    LayoutTemplate,
    Plug,
    ListVideo,
    Monitor,
    Settings,
    Users,
    Tv,
} from '@lucide/vue';
import AppLogo from '@/components/AppLogo.vue';
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
} from '@/components/ui/sidebar';
import { dashboard } from '@/routes';
import type { NavItem } from '@/types';

const mainNavItems: NavItem[] = [
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
        title: 'Media',
        href: '/media-assets',
        icon: Image,
    },
    {
        title: 'Templates',
        href: '/templates',
        icon: LayoutTemplate,
    },
    {
        title: 'API Connections',
        href: '/integrations/api',
        icon: Plug,
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
    {
        title: 'Activity',
        href: '/activity',
        icon: ClipboardList,
    },
    {
        title: 'Workspace',
        href: '/workspace/settings',
        icon: Settings,
    },
    {
        title: 'Members',
        href: '/workspace/members',
        icon: Users,
    },
];

const footerNavItems: NavItem[] = [
    {
        title: 'Repository',
        href: 'https://github.com/laravel/vue-starter-kit',
        icon: FolderGit2,
    },
    {
        title: 'Documentation',
        href: '/documentation',
        icon: BookOpen,
    },
];
</script>

<template>
    <Sidebar collapsible="icon" variant="inset">
        <SidebarHeader>
            <SidebarMenu>
                <SidebarMenuItem>
                    <SidebarMenuButton size="lg" as-child>
                        <Link :href="dashboard()">
                            <AppLogo />
                        </Link>
                    </SidebarMenuButton>
                </SidebarMenuItem>
            </SidebarMenu>
        </SidebarHeader>

        <SidebarContent>
            <TenantSwitcher />
            <NavMain :items="mainNavItems" />
        </SidebarContent>

        <SidebarFooter>
            <NavFooter :items="footerNavItems" />
            <NavUser />
        </SidebarFooter>
    </Sidebar>
    <slot />
</template>
