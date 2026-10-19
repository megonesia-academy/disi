import { createInertiaApp } from '@inertiajs/vue3';
import { registerSW } from 'virtual:pwa-register';
import { initializeTheme } from '@/composables/useAppearance';
import { initializeInstallPrompt } from '@/composables/useInstallPrompt';
import AppLayout from '@/layouts/AppLayout.vue';
import AuthLayout from '@/layouts/AuthLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { initializeFlashToast } from '@/lib/flashToast';

const appName = import.meta.env.VITE_APP_NAME || 'Laravel';

void createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    layout: (name) => {
        switch (true) {
            case name === 'Welcome':
                return null;
            case name === 'display/Show':
                return null;
            case name.startsWith('player/'):
                return null;
            case name.startsWith('auth/'):
                return AuthLayout;
            case name.startsWith('settings/'):
                return [AppLayout, SettingsLayout];
            default:
                return AppLayout;
        }
    },
    withApp: (app) => {
        app.directive('focus', {
            mounted: (el: HTMLElement, shouldFocus) => {
                if (shouldFocus.value !== false) {
                    el.focus();
                }
            },
        });
    },
    progress: {
        color: '#4B5563',
    },
});

// This will set light / dark mode on page load...
initializeTheme();

// This will listen for flash toast data from the server...
initializeFlashToast();

// This will capture the browser install prompt so the dashboard can be
// installed as an app from our own UI...
initializeInstallPrompt();

// This will register the PWA service worker (no-op on the dev server) so the
// dashboard is installable and its assets are precached for production. Player
// screens run their own worker (`/player-sw.js`) and are left alone so the two
// never compete for control of the page...
if (
    typeof window !== 'undefined' &&
    !window.location.pathname.startsWith('/player/')
) {
    registerSW({ immediate: true });
}
