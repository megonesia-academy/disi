<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core';
import { Form, Head } from '@inertiajs/vue3';
import { ArrowLeft, ArrowRight, MailCheck } from '@lucide/vue';
import InputError from '@/components/InputError.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextLink from '@/components/TextLink.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { login } from '@/routes';
import { email } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Lupa password?',
        description:
            'Masukkan email akun Anda. Kami akan mengirim tautan untuk membuat password baru.',
    },
});

defineProps<{
    status?: string;
}>();

const isPointerFine = useMediaQuery('(pointer: fine)');

const submitClass =
    'h-11 w-full bg-orange-500 font-bold text-[#0b1220] shadow-lg shadow-orange-500/20 transition-colors hover:bg-orange-600 focus-visible:ring-orange-300/60 dark:bg-orange-500 dark:text-[#0b1220] dark:hover:bg-orange-400';
</script>

<template>
    <Head title="Lupa password" />

    <div class="grid gap-6">
        <div
            v-if="status"
            role="status"
            class="flex items-start gap-3 rounded-xl border border-emerald-300/70 bg-emerald-50 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-100"
        >
            <MailCheck class="mt-0.5 size-4 shrink-0" aria-hidden="true" />
            <div class="space-y-1">
                <p class="font-semibold">Periksa kotak masuk Anda</p>
                <p class="leading-6">
                    Jika email tersebut terdaftar, kami sudah mengirim tautan
                    untuk membuat password baru. Tautan berlaku selama 60 menit.
                </p>
            </div>
        </div>

        <Form
            v-bind="email.form()"
            v-slot="{ errors, processing }"
            class="grid gap-5"
        >
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
                    :aria-describedby="errors.email ? 'email-error' : undefined"
                />
                <InputError id="email-error" :message="errors.email" />
            </div>

            <SubmitButton
                :processing="processing"
                :class="submitClass"
                data-test="email-password-reset-link-button"
                label="Kirim tautan reset"
                loading-label="Mengirim tautan…"
            >
                <ArrowRight class="size-4" aria-hidden="true" />
            </SubmitButton>
        </Form>

        <p class="text-center text-sm">
            <TextLink :href="login()" class="inline-flex items-center gap-1.5">
                <ArrowLeft class="size-3.5" aria-hidden="true" />
                Kembali ke halaman masuk
            </TextLink>
        </p>
    </div>
</template>
