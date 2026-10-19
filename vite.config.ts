import inertia from '@inertiajs/vite';
import { wayfinder } from '@laravel/vite-plugin-wayfinder';
import tailwindcss from '@tailwindcss/vite';
import vue from '@vitejs/plugin-vue';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import { defineConfig, lazyPlugins } from 'vite-plus';
import { VitePWA } from 'vite-plugin-pwa';

export default defineConfig({
    plugins: lazyPlugins(() => [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.ts'],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
            ],
        }),
        inertia(),
        tailwindcss(),
        vue({
            template: {
                transformAssetUrls: {
                    base: null,
                    includeAbsolute: false,
                },
            },
        }),
        wayfinder({
            formVariants: true,
        }),
        VitePWA({
            // The service worker is written to the web root instead of
            // `public/build` so it can control the whole application. A worker
            // served from `/build/sw.js` would only be allowed to control
            // `/build/`, unless the web server sends a `Service-Worker-Allowed`
            // header, which Laravel does not do by default.
            outDir: 'public',
            base: '/',
            buildBase: '/',
            scope: '/',
            filename: 'sw.js',
            // Registration happens in `resources/js/app.ts` through
            // `virtual:pwa-register` so it stays in our own bundle.
            injectRegister: false,
            registerType: 'autoUpdate',
            // The web manifest is a hand written file in `public` so it is
            // available before the first build and does not shift with the
            // build directory.
            manifest: false,
            includeManifestIcons: false,
            workbox: {
                globPatterns: ['build/**/*.{js,css,html,woff2}'],
                // Laravel renders the shell server side, there is no
                // `index.html` to fall back to for navigations.
                navigateFallback: undefined,
                dontCacheBustURLsMatching: /^build\/assets\//,
                cleanupOutdatedCaches: true,
                skipWaiting: true,
                clientsClaim: true,
                maximumFileSizeToCacheInBytes: 5 * 1024 * 1024,
            },
        }),
    ]),
    server: {
        watch: {
            ignored: [
                '**/.agents/**',
                '**/.claude/**',
                '**/.cursor/**',
                '**/.junie/**',
                '**/vendor/**',
            ],
        },
    },
    lint: {
        ignorePatterns: [
            'vendor/**',
            'node_modules/**',
            'public/**',
            'bootstrap/ssr/**',
            'tailwind.config.js',
            'resources/js/actions/**',
            'resources/js/components/ui/*',
            'resources/js/routes/**',
            'resources/js/wayfinder/**',
        ],
        options: {
            denyWarnings: true,
            typeAware: true,
        },
    },
    fmt: {
        printWidth: 80,
        tabWidth: 4,
        singleQuote: true,
        semi: true,
        singleAttributePerLine: false,
        htmlWhitespaceSensitivity: 'css',
        ignorePatterns: [
            '.github/**',
            'composer.json',
            'resources/js/components/ui/*',
            'resources/views/mail/*',
        ],
        sortTailwindcss: {
            functions: ['clsx', 'cn', 'cva'],
            stylesheet: 'resources/css/app.css',
        },
    },
});
