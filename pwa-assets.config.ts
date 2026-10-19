import {
    defineConfig,
    minimal2023Preset,
} from '@vite-pwa/assets-generator/config';

const brandBackground = '#13295e';

export default defineConfig({
    headLinkOptions: {
        preset: '2023',
    },
    preset: {
        ...minimal2023Preset,
        maskable: {
            ...minimal2023Preset.maskable,
            resizeOptions: { background: brandBackground },
        },
        apple: {
            ...minimal2023Preset.apple,
            resizeOptions: { background: brandBackground },
        },
    },
    images: ['public/disi-logo.svg'],
});
