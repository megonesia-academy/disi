<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';

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
}>();

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

        <section class="max-w-xl rounded-lg border p-4">
            <form class="space-y-4" @submit.prevent="submit">
                <div>
                    <label class="mb-1 block text-sm font-medium">
                        Screen
                    </label>
                    <select
                        v-model="form.screen_id"
                        class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
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
                    <Button
                        type="submit"
                        :disabled="form.processing || screens.length === 0"
                    >
                        Connect display
                    </Button>
                    <Button type="button" variant="outline" as-child>
                        <Link href="/screens">Manage screens</Link>
                    </Button>
                </div>
            </form>
        </section>
    </div>
</template>
