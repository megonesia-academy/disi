<script setup lang="ts">
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

const props = defineProps<{
    message?: string | null;
    id?: string;
    class?: HTMLAttributes['class'];
}>();
</script>

<template>
    <p
        v-if="props.message"
        :id="props.id"
        role="alert"
        :class="
            cn(
                'text-sm font-medium text-red-600 dark:text-red-400',
                props.class,
            )
        "
    >
        {{ props.message }}
    </p>
</template>
