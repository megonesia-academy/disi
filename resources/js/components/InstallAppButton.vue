<script setup lang="ts">
import { Download } from '@lucide/vue';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useInstallPrompt } from '@/composables/useInstallPrompt';

const { canInstall, install } = useInstallPrompt();

const handleInstall = (): void => {
    void install();
};
</script>

<template>
    <SidebarMenu v-if="canInstall">
        <SidebarMenuItem>
            <SidebarMenuButton
                class="border border-dashed border-sidebar-border text-sidebar-foreground/80 hover:bg-sidebar-accent hover:text-sidebar-accent-foreground"
                tooltip="Install aplikasi"
                data-test="install-app-button"
                @click="handleInstall"
            >
                <Download />
                <span>Install aplikasi</span>
            </SidebarMenuButton>
        </SidebarMenuItem>
    </SidebarMenu>
</template>
