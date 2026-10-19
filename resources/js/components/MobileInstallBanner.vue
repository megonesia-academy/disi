<script setup lang="ts">
import { Download, X } from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';
import InstallAppDialog from '@/components/InstallAppDialog.vue';
import { Button } from '@/components/ui/button';
import { useInstallPrompt } from '@/composables/useInstallPrompt';

const DISMISS_KEY = 'disi:install-banner-dismissed';
const OFFSET_VARIABLE = '--mobile-install-offset';

const { canInstall, canPrompt, install } = useInstallPrompt();

const dismissed = ref(false);
const dialogOpen = ref(false);

const visible = computed(() => canInstall.value && !dismissed.value);

function syncContentOffset(isVisible: boolean): void {
    if (typeof document === 'undefined') {
        return;
    }

    document.documentElement.style.setProperty(
        OFFSET_VARIABLE,
        isVisible ? '72px' : '0px',
    );

    window.dispatchEvent(new Event('disi:install-offset'));
}

onMounted(() => {
    try {
        dismissed.value = window.localStorage.getItem(DISMISS_KEY) === '1';
    } catch {
        // Storage can be unavailable in private mode.
    }
});

onUnmounted(() => syncContentOffset(false));

watch(visible, syncContentOffset, { immediate: true });

function dismiss(): void {
    dismissed.value = true;

    try {
        window.localStorage.setItem(DISMISS_KEY, '1');
    } catch {
        // Storage can be unavailable in private mode.
    }
}

function handleInstall(): void {
    if (canPrompt.value) {
        void install();

        return;
    }

    dialogOpen.value = true;
}
</script>

<template>
    <div v-if="visible">
        <div
            class="fixed inset-x-0 bottom-[calc(4rem+env(safe-area-inset-bottom))] z-40 px-3 pb-2 md:hidden"
        >
            <div
                class="mx-auto flex max-w-lg items-center gap-3 rounded-xl border bg-background/95 p-3 shadow-[0_-4px_16px_rgba(15,23,42,0.12)] backdrop-blur"
            >
                <span
                    class="grid size-9 shrink-0 place-items-center rounded-xl bg-primary/10 text-primary"
                >
                    <Download class="size-4" />
                </span>
                <div class="min-w-0 flex-1">
                    <p class="truncate text-sm font-semibold">
                        Install aplikasi
                    </p>
                    <p class="truncate text-xs text-muted-foreground">
                        Buka Disi langsung dari layar utama
                    </p>
                </div>
                <Button type="button" size="sm" @click="handleInstall">
                    Install
                </Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    class="size-8 shrink-0"
                    aria-label="Tutup banner install"
                    @click="dismiss"
                >
                    <X class="size-4" />
                </Button>
            </div>
        </div>

        <InstallAppDialog v-model:open="dialogOpen" />
    </div>
</template>
