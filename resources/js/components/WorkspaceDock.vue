<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';
import { Link, usePage } from '@inertiajs/vue3';
import {
    Building2,
    Coins,
    GripVertical,
    Moon,
    ShieldCheck,
    Sun,
} from '@lucide/vue';
import { computed, nextTick, onMounted, onUnmounted, ref } from 'vue';
import type { Auth, Tenancy } from '@/types';

type DockCorner = 'top-left' | 'top-right' | 'bottom-left' | 'bottom-right';

const STORAGE_KEY = 'disi:dock-corner';
const EDGE_MARGIN = 16;

const page = usePage();
const tenancy = computed(() => page.props.tenancy as Tenancy | undefined);
const currentTenant = computed(() => tenancy.value?.current ?? null);
const canManagePlatform = computed(() =>
    Boolean((page.props.auth as Auth | undefined)?.can_manage_platform),
);
const isAdminArea = computed(() => page.url.split('?')[0].startsWith('/admin'));

const { resolvedAppearance, updateAppearance } = useAppearance();
const isDark = computed(() => resolvedAppearance.value === 'dark');

const isPastDue = computed(
    () =>
        currentTenant.value !== null &&
        currentTenant.value.subscription_status !== 'active',
);
const isLowBalance = computed(() => {
    const cost = currentTenant.value?.monthly_subscription_cost ?? 0;

    return (
        !isPastDue.value &&
        cost > 0 &&
        (currentTenant.value?.token_balance ?? 0) < cost
    );
});

const tokenTone = computed(() =>
    isPastDue.value
        ? 'border-red-300 bg-red-50 text-red-700 dark:border-red-500/40 dark:bg-red-500/10 dark:text-red-200'
        : isLowBalance.value
          ? 'border-amber-300 bg-amber-50 text-amber-700 dark:border-amber-500/40 dark:bg-amber-500/10 dark:text-amber-200'
          : 'border bg-background',
);
const tokenTitle = computed(() =>
    isPastDue.value
        ? 'Langganan tidak aktif (past due) — buka Billing untuk topup'
        : isLowBalance.value
          ? 'Saldo di bawah biaya langganan bulanan — topup di Billing'
          : 'Saldo token tenant — buka Billing',
);

const dockRef = ref<HTMLElement | null>(null);
const mounted = ref(false);
const ready = ref(false);
const dragging = ref(false);
const corner = ref<DockCorner>('bottom-right');
const position = ref({ x: 0, y: 0 });

const positionStyle = computed(() => ({
    left: `${position.value.x}px`,
    top: `${position.value.y}px`,
}));

function dockBottomGap(): number {
    // Keep clear of the mobile bottom navigation.
    return window.innerWidth < 768 ? 84 : EDGE_MARGIN;
}

function applyCorner(nextCorner: DockCorner): void {
    const element = dockRef.value;

    if (!element) {
        return;
    }

    const rect = element.getBoundingClientRect();
    const isLeft = nextCorner.endsWith('left');
    const isTop = nextCorner.startsWith('top');

    corner.value = nextCorner;
    position.value = {
        x: isLeft ? EDGE_MARGIN : window.innerWidth - rect.width - EDGE_MARGIN,
        y: isTop
            ? EDGE_MARGIN
            : window.innerHeight - rect.height - dockBottomGap(),
    };
}

function nearestCorner(): DockCorner {
    const rect = dockRef.value?.getBoundingClientRect();

    if (!rect) {
        return corner.value;
    }

    const centerX = rect.left + rect.width / 2;
    const centerY = rect.top + rect.height / 2;
    const vertical = centerY < window.innerHeight / 2 ? 'top' : 'bottom';
    const horizontal = centerX < window.innerWidth / 2 ? 'left' : 'right';

    return `${vertical}-${horizontal}` as DockCorner;
}

let dragOffsetX = 0;
let dragOffsetY = 0;

function startDrag(event: PointerEvent): void {
    const target = event.currentTarget as HTMLElement;
    const rect = dockRef.value?.getBoundingClientRect();

    if (!rect) {
        return;
    }

    target.setPointerCapture(event.pointerId);
    dragOffsetX = event.clientX - rect.left;
    dragOffsetY = event.clientY - rect.top;
    dragging.value = true;
}

function onDrag(event: PointerEvent): void {
    const rect = dockRef.value?.getBoundingClientRect();

    if (!dragging.value || !rect) {
        return;
    }

    const margin = 8;
    const maxX = window.innerWidth - rect.width - margin;
    const bottomGap = window.innerWidth < 768 ? 76 : margin;
    const maxY = window.innerHeight - rect.height - bottomGap;

    position.value = {
        x: Math.min(Math.max(event.clientX - dragOffsetX, margin), maxX),
        y: Math.min(Math.max(event.clientY - dragOffsetY, margin), maxY),
    };
}

function endDrag(): void {
    if (!dragging.value) {
        return;
    }

    dragging.value = false;
    const nextCorner = nearestCorner();
    applyCorner(nextCorner);

    try {
        localStorage.setItem(STORAGE_KEY, nextCorner);
    } catch {
        // Storage can be unavailable in private browsing; snapping still works.
    }
}

function handleResize(): void {
    applyCorner(corner.value);
}

function toggleTheme(): void {
    updateAppearance(isDark.value ? 'light' : 'dark');
}

onMounted(async () => {
    let saved: DockCorner | null = null;

    try {
        saved = localStorage.getItem(STORAGE_KEY) as DockCorner | null;
    } catch {
        saved = null;
    }

    const initial: DockCorner = saved ?? 'bottom-right';

    corner.value = initial;
    mounted.value = true;
    await nextTick();
    applyCorner(initial);
    ready.value = true;
    window.addEventListener('resize', handleResize);
});

onUnmounted(() => {
    window.removeEventListener('resize', handleResize);
});
</script>

<template>
    <div
        v-if="mounted"
        ref="dockRef"
        class="fixed z-40 select-none"
        :class="
            ready && !dragging
                ? 'transition-[left,top] duration-200 ease-out'
                : ''
        "
        :style="positionStyle"
    >
        <div
            class="flex items-center gap-1 rounded-full border bg-background/95 p-1 shadow-lg backdrop-blur supports-[backdrop-filter]:bg-background/80"
        >
            <button
                type="button"
                class="grid size-7 shrink-0 cursor-grab touch-none place-items-center rounded-full text-muted-foreground transition-colors hover:bg-accent hover:text-foreground active:cursor-grabbing"
                aria-label="Geser panel ke sudut lain"
                title="Geser panel"
                @pointerdown="startDrag"
                @pointermove="onDrag"
                @pointerup="endDrag"
                @pointercancel="endDrag"
            >
                <GripVertical class="size-3.5" />
            </button>

            <Link
                v-if="currentTenant && !isAdminArea"
                href="/workspace/billing"
                class="inline-flex h-8 items-center gap-1.5 rounded-full border px-3 text-xs font-medium transition-colors hover:bg-accent"
                :class="tokenTone"
                :title="tokenTitle"
            >
                <Coins class="size-3.5 shrink-0 text-amber-500" />
                <span class="font-semibold">
                    {{ currentTenant.token_balance ?? 0 }}
                </span>
                <span class="hidden sm:inline">token</span>
            </Link>

            <Link
                v-if="canManagePlatform"
                :href="isAdminArea ? '/dashboard' : '/admin'"
                class="inline-flex h-8 items-center gap-1.5 rounded-full px-3 text-xs font-medium text-muted-foreground transition-colors hover:bg-accent hover:text-foreground"
                :title="
                    isAdminArea
                        ? 'Kembali ke workspace tenant'
                        : 'Buka platform admin'
                "
            >
                <Building2 v-if="isAdminArea" class="size-3.5 shrink-0" />
                <ShieldCheck v-else class="size-3.5 shrink-0" />
                <span class="hidden sm:inline">
                    {{ isAdminArea ? 'Workspace' : 'Platform admin' }}
                </span>
            </Link>

            <Button
                type="button"
                variant="ghost"
                size="icon"
                class="size-8 shrink-0 rounded-full"
                :aria-label="
                    isDark ? 'Gunakan tema terang' : 'Gunakan tema gelap'
                "
                :title="isDark ? 'Mode terang' : 'Mode gelap'"
                @click="toggleTheme"
            >
                <Sun v-if="isDark" class="size-4" />
                <Moon v-else class="size-4" />
            </Button>
        </div>
    </div>
</template>
