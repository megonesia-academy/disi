<script setup lang="ts">
import { computed } from 'vue';
import { Moon, Sun } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { useAppearance } from '@/composables/useAppearance';

const { resolvedAppearance, updateAppearance } = useAppearance();

const isDark = computed(() => resolvedAppearance.value === 'dark');
const label = computed(() =>
    isDark.value ? 'Gunakan tema terang' : 'Gunakan tema gelap',
);

function toggleTheme(): void {
    updateAppearance(isDark.value ? 'light' : 'dark');
}
</script>

<template>
    <Button
        type="button"
        variant="ghost"
        size="icon"
        class="relative size-9 shrink-0"
        :aria-label="label"
        :title="label"
        @click="toggleTheme"
    >
        <Sun v-if="isDark" class="size-4" aria-hidden="true" />
        <Moon v-else class="size-4" aria-hidden="true" />
    </Button>
</template>
