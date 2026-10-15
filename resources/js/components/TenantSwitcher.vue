<script setup lang="ts">
import { useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import type { Tenancy } from '@/types';

const page = usePage();

const tenancy = computed(() => page.props.tenancy as Tenancy);

const form = useForm({
    tenant_id: tenancy.value.current?.id ?? '',
});

function switchTenant(): void {
    if (!form.tenant_id || form.tenant_id === tenancy.value.current?.id) {
        return;
    }

    form.post('/tenants/switch', {
        preserveScroll: true,
    });
}
</script>

<template>
    <div
        v-if="tenancy.current"
        class="mx-2 mb-2 rounded-md border border-sidebar-border/70 px-2 py-2"
    >
        <label class="mb-1 block text-xs font-medium text-muted-foreground">
            Tenant
        </label>
        <select
            v-model="form.tenant_id"
            class="h-8 w-full rounded-md border border-sidebar-border bg-background px-2 text-sm text-foreground outline-none option:bg-background option:text-foreground"
            :disabled="form.processing || tenancy.tenants.length <= 1"
            @change="switchTenant"
        >
            <option
                v-for="tenant in tenancy.tenants"
                :key="tenant.id"
                :value="tenant.id"
            >
                {{ tenant.name }}
            </option>
        </select>
    </div>
</template>
