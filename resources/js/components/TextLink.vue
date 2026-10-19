<script setup lang="ts">
import type { LinkComponentBaseProps, Method } from '@inertiajs/core';
import { Link } from '@inertiajs/vue3';
import type { HTMLAttributes } from 'vue';
import { cn } from '@/lib/utils';

type Props = {
    href: LinkComponentBaseProps['href'];
    tabindex?: number;
    method?: Method;
    as?: string;
    class?: HTMLAttributes['class'];
};

const props = defineProps<Props>();
</script>

<template>
    <Link
        :href="props.href"
        :tabindex="props.tabindex"
        :method="props.method"
        :as="props.as"
        :class="
            cn(
                'rounded-sm font-medium text-foreground underline decoration-orange-400/70 decoration-1 underline-offset-4 transition-colors hover:text-orange-700 hover:decoration-orange-500 focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:ring-offset-2 focus-visible:outline-none dark:decoration-orange-300/50 dark:hover:text-orange-300',
                props.class,
            )
        "
    >
        <slot />
    </Link>
</template>
