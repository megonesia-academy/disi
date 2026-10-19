<script setup lang="ts">
import type { UrlMethodPair } from '@inertiajs/core';
import { router } from '@inertiajs/vue3';
import { usePasskeyVerify } from '@laravel/passkeys/vue';
import { KeyRound } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Separator } from '@/components/ui/separator';

type Props = {
    routes?: {
        options: UrlMethodPair;
        submit: UrlMethodPair;
    };
    label?: string;
    loadingLabel?: string;
    separator?: string;
};

const props = defineProps<Props>();

const { verify, isLoading, error, isSupported } = usePasskeyVerify({
    ...(props.routes
        ? {
              routes: {
                  options: props.routes.options.url,
                  submit: props.routes.submit.url,
              },
          }
        : {}),
    onSuccess: (response) => {
        router.visit(response.redirect ?? '/dashboard');
    },
});
</script>

<template>
    <div v-if="isSupported">
        <div class="grid gap-2">
            <SubmitButton
                type="button"
                variant="outline"
                class="h-11 w-full font-semibold"
                :processing="isLoading"
                :label="props.label ?? 'Masuk dengan passkey'"
                :loading-label="props.loadingLabel ?? 'Memverifikasi…'"
                @click="verify"
            >
                <KeyRound class="size-4" aria-hidden="true" />
            </SubmitButton>

            <InputError :message="error" class="text-center" />
        </div>

        <div class="relative my-6">
            <div class="absolute inset-0 flex items-center">
                <Separator class="w-full" />
            </div>
            <div
                class="relative flex justify-center text-xs font-semibold tracking-wide uppercase"
            >
                <span class="bg-background px-3 text-muted-foreground">
                    {{ props.separator ?? 'atau lanjutkan dengan email' }}
                </span>
            </div>
        </div>
    </div>
</template>
