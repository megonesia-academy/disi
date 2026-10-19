<script setup lang="ts">
import { Link } from '@inertiajs/vue3';
import { Settings2 } from '@lucide/vue';
import { useCurrentUrl } from '@/composables/useCurrentUrl';
import { edit as editAppearance } from '@/routes/appearance';
import { edit as editProfile } from '@/routes/profile';
import { edit as editSecurity } from '@/routes/security';
import { toUrl } from '@/lib/utils';
import type { NavItem } from '@/types';

const sidebarNavItems: NavItem[] = [
    {
        title: 'Profile',
        href: editProfile(),
    },
    {
        title: 'Security',
        href: editSecurity(),
    },
    {
        title: 'Appearance',
        href: editAppearance(),
    },
];

const { isCurrentOrParentUrl } = useCurrentUrl();
</script>

<template>
    <div class="min-h-full bg-workspace p-4 md:p-6">
        <div class="mx-auto w-full max-w-3xl space-y-5">
            <section
                class="rounded-lg border bg-background p-5 shadow-sm md:p-6"
            >
                <div
                    class="mb-3 inline-flex items-center gap-1.5 rounded-full bg-orange-50 px-2.5 py-1 text-xs font-medium text-orange-700 dark:bg-orange-950 dark:text-orange-300"
                >
                    <Settings2 class="size-3.5" /> Account settings
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">Settings</h1>
                <p class="mt-1 text-sm text-muted-foreground">
                    Kelola profil, keamanan, dan tampilan akun Anda.
                </p>

                <nav
                    class="mt-4 flex w-fit flex-wrap gap-1 rounded-lg border bg-muted/40 p-1"
                    aria-label="Settings"
                >
                    <Link
                        v-for="item in sidebarNavItems"
                        :key="toUrl(item.href)"
                        :href="item.href"
                        class="rounded-md px-3 py-1.5 text-sm font-medium transition-colors"
                        :class="
                            isCurrentOrParentUrl(item.href)
                                ? 'bg-background text-foreground shadow-sm'
                                : 'text-muted-foreground hover:text-foreground'
                        "
                    >
                        {{ item.title }}
                    </Link>
                </nav>
            </section>

            <slot />
        </div>
    </div>
</template>
