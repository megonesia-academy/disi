<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core';
import { Form, Head } from '@inertiajs/vue3';
import { ArrowRight, CheckCircle2 } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasskeyVerify from '@/components/PasskeyVerify.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextLink from '@/components/TextLink.vue';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { register } from '@/routes';
import { store } from '@/routes/login';
import { request } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Masuk ke workspace',
        description:
            'Kelola playlist, layar, dan konten signage Anda dari satu dashboard.',
    },
});

const props = defineProps<{
    status?: string;
    canResetPassword: boolean;
}>();

const isPointerFine = useMediaQuery('(pointer: fine)');

const statusMessage = computed(() => {
    if (!props.status) {
        return null;
    }

    if (/reset/i.test(props.status)) {
        return 'Password berhasil diperbarui. Silakan masuk dengan password baru.';
    }

    if (/verif/i.test(props.status)) {
        return 'Tautan verifikasi baru sudah dikirim ke email Anda.';
    }

    return props.status;
});

const submitClass =
    'h-11 w-full bg-orange-500 font-bold text-[#0b1220] shadow-lg shadow-orange-500/20 transition-colors hover:bg-orange-600 focus-visible:ring-orange-300/60 dark:bg-orange-500 dark:text-[#0b1220] dark:hover:bg-orange-400';
</script>

<template>
    <Head title="Masuk" />

    <div class="grid gap-6">
        <div
            v-if="statusMessage"
            role="status"
            class="flex items-start gap-3 rounded-xl border border-emerald-300/70 bg-emerald-50 px-4 py-3 text-sm font-medium text-emerald-900 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-100"
        >
            <CheckCircle2 class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <p>{{ statusMessage }}</p>
        </div>

        <PasskeyVerify
            label="Masuk dengan passkey"
            loading-label="Memverifikasi…"
            separator="atau lanjutkan dengan email"
        />

        <Form
            v-bind="store.form()"
            v-slot="{ errors, processing }"
            :reset-on-success="['password']"
            class="grid gap-6"
        >
            <div class="grid gap-5">
                <div class="grid gap-2">
                    <Label for="email" class="font-semibold">Email</Label>
                    <Input
                        id="email"
                        v-focus="isPointerFine"
                        type="email"
                        name="email"
                        inputmode="email"
                        autocomplete="email"
                        spellcheck="false"
                        required
                        placeholder="nama@perusahaan.com"
                        class="h-11"
                        :aria-invalid="Boolean(errors.email)"
                        :aria-describedby="
                            errors.email ? 'email-error' : undefined
                        "
                    />
                    <InputError id="email-error" :message="errors.email" />
                </div>

                <div class="grid gap-2">
                    <div class="flex items-baseline justify-between gap-3">
                        <Label for="password" class="font-semibold">
                            Password
                        </Label>
                        <TextLink
                            v-if="canResetPassword"
                            :href="request()"
                            class="text-xs"
                        >
                            Lupa password?
                        </TextLink>
                    </div>
                    <PasswordInput
                        id="password"
                        name="password"
                        autocomplete="current-password"
                        placeholder="Masukkan password"
                        required
                        class="h-11"
                        :aria-invalid="Boolean(errors.password)"
                        :aria-describedby="
                            errors.password ? 'password-error' : undefined
                        "
                    />
                    <InputError
                        id="password-error"
                        :message="errors.password"
                    />
                </div>

                <label
                    for="remember"
                    class="flex w-fit cursor-pointer items-center gap-2.5 text-sm font-medium text-muted-foreground select-none hover:text-foreground"
                >
                    <Checkbox id="remember" name="remember" />
                    <span>Ingat saya</span>
                </label>

                <SubmitButton
                    :processing="processing"
                    :class="submitClass"
                    data-test="login-button"
                    label="Masuk"
                    loading-label="Memproses…"
                >
                    <ArrowRight class="size-4" aria-hidden="true" />
                </SubmitButton>
            </div>

            <p class="text-center text-sm text-muted-foreground">
                Belum punya akun?
                <TextLink :href="register()" class="font-semibold">
                    Buat akun
                </TextLink>
            </p>
        </Form>
    </div>
</template>
