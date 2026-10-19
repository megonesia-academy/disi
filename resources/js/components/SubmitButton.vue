<script setup lang="ts">
import type { ButtonVariants } from '@/components/ui/button';
import { Button } from '@/components/ui/button';
import { Spinner } from '@/components/ui/spinner';

withDefaults(
    defineProps<{
        processing?: boolean;
        disabled?: boolean;
        label?: string;
        loadingLabel?: string;
        type?: 'button' | 'submit' | 'reset';
        variant?: ButtonVariants['variant'];
        size?: ButtonVariants['size'];
        title?: string;
        ariaLabel?: string;
    }>(),
    {
        processing: false,
        disabled: false,
        loadingLabel: 'Memproses...',
        type: 'submit',
        variant: 'default',
        size: 'default',
    },
);
</script>

<template>
    <Button
        :type="type"
        :variant="variant"
        :size="size"
        :title="title"
        :aria-label="ariaLabel"
        :aria-busy="processing"
        :disabled="processing || disabled"
    >
        <Spinner v-if="processing" />
        <slot v-else />
        <template v-if="label">
            {{ processing ? loadingLabel : label }}
        </template>
        <span v-if="processing && !label" class="sr-only">
            {{ loadingLabel }}
        </span>
    </Button>
</template>
