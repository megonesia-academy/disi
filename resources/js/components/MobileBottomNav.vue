<script setup lang="ts">
import { Link, usePage } from '@inertiajs/vue3';
import { Image, LayoutGrid, ListVideo, Menu, Monitor } from '@lucide/vue';
import { computed } from 'vue';

const page = usePage();

const currentPath = computed(
    () => page.url.split('?')[0].replace(/\/$/, '') || '/',
);

// The playlist editor renders its own action bar (back + save) at the bottom of
// the screen, so the tab bar steps aside to keep the save button reachable.
const focusedEditor = computed(
    () =>
        currentPath.value === '/playlists/create' ||
        /^\/playlists\/[^/]+\/edit$/.test(currentPath.value),
);

const items = [
    { title: 'Dashboard', href: '/dashboard', icon: LayoutGrid },
    { title: 'Screens', href: '/screens', icon: Monitor },
    { title: 'Media', href: '/media-assets', icon: Image },
    { title: 'Playlists', href: '/playlists', icon: ListVideo },
    { title: 'Menu', href: '/menu', icon: Menu },
];

const isActive = (href: string) =>
    currentPath.value === href || currentPath.value.startsWith(`${href}/`);
</script>

<template>
    <nav
        v-if="!focusedEditor"
        class="fixed inset-x-0 bottom-0 z-50 border-t border-border/80 bg-background/95 pb-[env(safe-area-inset-bottom)] shadow-[0_-8px_24px_rgba(15,23,42,0.08)] backdrop-blur-xl md:hidden"
        aria-label="Navigasi utama"
    >
        <div class="grid h-16 grid-cols-5 overflow-visible">
            <Link
                v-for="item in items"
                :key="item.href"
                :href="item.href"
                class="relative flex min-w-0 flex-col items-center justify-center gap-1 px-1 text-muted-foreground transition-colors active:bg-muted/60"
                :class="
                    isActive(item.href)
                        ? 'font-semibold text-primary'
                        : 'hover:text-foreground'
                "
                :aria-current="isActive(item.href) ? 'page' : undefined"
            >
                <span
                    class="flex size-10 shrink-0 items-center justify-center rounded-full transition-all duration-200 ease-out"
                    :class="
                        isActive(item.href)
                            ? '-translate-y-2.5 bg-primary text-primary-foreground shadow-[0_8px_20px_rgba(0,119,182,0.3)] ring-4 ring-background'
                            : 'text-muted-foreground'
                    "
                >
                    <component :is="item.icon" class="size-5" />
                </span>
                <span
                    class="-mt-1 max-w-full truncate text-[10px] leading-none font-medium transition-transform duration-200"
                    :class="isActive(item.href) ? '-translate-y-1' : ''"
                >
                    {{ item.title }}
                </span>
            </Link>
        </div>
    </nav>
</template>
