import { computed, ref, type ComputedRef } from 'vue';

/**
 * The `beforeinstallprompt` event is not part of the DOM lib yet.
 *
 * @see https://developer.mozilla.org/en-US/docs/Web/API/BeforeInstallPromptEvent
 */
interface BeforeInstallPromptEvent extends Event {
    readonly platforms: string[];
    readonly userChoice: Promise<{
        outcome: 'accepted' | 'dismissed';
        platform: string;
    }>;
    prompt: () => Promise<void>;
}

const deferredPrompt = ref<BeforeInstallPromptEvent | null>(null);
const isStandalone = ref(false);

let initialized = false;

function isStandaloneDisplay(): boolean {
    if (typeof window === 'undefined') {
        return false;
    }

    const navigatorWithStandalone = window.navigator as Navigator & {
        standalone?: boolean;
    };

    return (
        window.matchMedia('(display-mode: standalone)').matches ||
        navigatorWithStandalone.standalone === true
    );
}

/**
 * Capture the install prompt as soon as the app boots, so the install action
 * stays available after Inertia navigations (the event is only fired once per
 * page load).
 */
export function initializeInstallPrompt(): void {
    if (initialized || typeof window === 'undefined') {
        return;
    }

    initialized = true;
    isStandalone.value = isStandaloneDisplay();

    window.addEventListener('beforeinstallprompt', (event) => {
        // Keep the event around: calling `preventDefault()` suppresses the
        // browser prompt so the install flow can be started from our own UI.
        event.preventDefault();
        deferredPrompt.value = event as BeforeInstallPromptEvent;
    });

    window.addEventListener('appinstalled', () => {
        deferredPrompt.value = null;
        isStandalone.value = true;
    });
}

export function useInstallPrompt(): {
    canInstall: ComputedRef<boolean>;
    install: () => Promise<boolean>;
} {
    const canInstall = computed(
        () => deferredPrompt.value !== null && !isStandalone.value,
    );

    const install = async (): Promise<boolean> => {
        const event = deferredPrompt.value;

        if (event === null) {
            return false;
        }

        await event.prompt();

        const { outcome } = await event.userChoice;

        deferredPrompt.value = null;

        if (outcome === 'accepted') {
            isStandalone.value = true;
        }

        return outcome === 'accepted';
    };

    return { canInstall, install };
}
