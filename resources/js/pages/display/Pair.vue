<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import { Link2, MonitorCog } from '@lucide/vue';
import { computed } from 'vue';

type ScreenOption = {
    id: string;
    name: string;
    location: string | null;
};

const props = defineProps<{
    pairing: {
        code: string;
        expires_at: string;
    };
    tenant: {
        id: string;
        name: string;
    };
    screens: ScreenOption[];
    usage: {
        devices: number;
        device_limit: number;
        free_devices: number;
        can_add_device: boolean;
        balance: number;
    };
}>();

const page = usePage();
const billingError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.[
            'billing'
        ] ?? null,
);

const form = useForm({
    screen_id: props.screens[0]?.id ?? '',
    name: '',
});

function submit(): void {
    form.post(`/display/pair/${props.pairing.code}`);
}
</script>

<template>
    <Head title="Pair Display" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Pair display
                </h1>
                <p class="text-sm text-muted-foreground">
                    Sambungkan kode {{ pairing.code }} ke workspace
                    {{ tenant.name }}.
                </p>
            </div>

            <Link
                href="/devices"
                class="text-sm font-medium text-primary underline-offset-4 hover:underline"
            >
                Devices
            </Link>
        </section>

        <div
            v-if="billingError"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
        >
            <span class="min-w-0 flex-1">{{ billingError }}</span>
            <Link
                href="/workspace/billing"
                class="shrink-0 font-semibold underline underline-offset-4"
            >
                Topup token
            </Link>
        </div>

        <section class="max-w-xl rounded-lg border p-4">
            <form class="space-y-4" @submit.prevent="submit">
                <p class="text-xs text-muted-foreground">
                    Paket gratis {{ usage.free_devices }} device · terpakai
                    {{ usage.devices }}/{{ usage.device_limit }}.
                </p>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Screen
                    </label>
                    <select
                        v-model="form.screen_id"
                        class="option:bg-background option:text-foreground h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                    >
                        <option value="" disabled>Pilih screen</option>
                        <option
                            v-for="screen in screens"
                            :key="screen.id"
                            :value="screen.id"
                        >
                            {{ screen.name }}
                            {{ screen.location ? `- ${screen.location}` : '' }}
                        </option>
                    </select>
                    <InputError :message="form.errors.screen_id" />
                </div>

                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Device name
                    </label>
                    <Input
                        v-model="form.name"
                        placeholder="Kosongkan untuk nama otomatis"
                    />
                    <InputError :message="form.errors.name" />
                </div>

                <div class="flex gap-2">
                    <SubmitButton
                        :processing="form.processing"
                        :disabled="
                            screens.length === 0 || !usage.can_add_device
                        "
                        label="Connect display"
                        loading-label="Menghubungkan..."
                    >
                        <Link2 class="size-4" />
                    </SubmitButton>
                    <Button type="button" variant="outline" as-child>
                        <Link href="/screens"
                            ><MonitorCog class="size-4" />Manage screens</Link
                        >
                    </Button>
                </div>
            </form>
        </section>
    </div>
</template>
