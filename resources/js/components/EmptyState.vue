<script setup lang="ts">
import { Link } from '@inertiajs/vue3';

defineProps<{
    title: string;
    description: string;
    actionLabel?: string;
    actionHref?: string;
    secondaryLabel?: string;
    secondaryHref?: string;
}>();
</script>

<template>
    <div
        class="mx-auto flex max-w-lg flex-col items-center justify-center px-6 py-12 text-center"
    >
        <div
            class="mb-4 grid size-12 place-items-center rounded-full bg-muted text-xl font-semibold text-muted-foreground"
        >
            +
        </div>
        <h3 class="text-lg font-semibold tracking-tight">{{ title }}</h3>
        <p class="mt-2 text-sm leading-6 text-muted-foreground">
            {{ description }}
        </p>
        <div
            v-if="actionLabel || secondaryLabel"
            class="mt-6 flex flex-wrap justify-center gap-2"
        >
            <Link
                v-if="actionLabel && actionHref"
                :href="actionHref"
                class="rounded-md bg-primary px-3 py-2 text-sm font-medium text-primary-foreground shadow-xs"
            >
                {{ actionLabel }}
            </Link>
            <Link
                v-if="secondaryLabel && secondaryHref"
                :href="secondaryHref"
                class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
            >
                {{ secondaryLabel }}
            </Link>
        </div>
    </div>
</template>
