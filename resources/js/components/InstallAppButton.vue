<script setup lang="ts">
import { Download } from '@lucide/vue';
import { ref } from 'vue';
import InstallAppDialog from '@/components/InstallAppDialog.vue';
import {
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useInstallPrompt } from '@/composables/useInstallPrompt';

const { canInstall, canPrompt, install } = useInstallPrompt();

const dialogOpen = ref(false);

const handleInstall = (): void => {
    if (canPrompt.value) {
        void install();

        return;
    }

    dialogOpen.value = true;
};
</script>

<template>
    <div v-if="canInstall">
        <SidebarMenu>
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

        <InstallAppDialog v-model:open="dialogOpen" />
    </div>
</template>
