<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core';
import { Form, Head } from '@inertiajs/vue3';
import { LockKeyhole } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextLink from '@/components/TextLink.vue';
import { Label } from '@/components/ui/label';
import { describePasswordRules } from '@/lib/passwordRules';
import { request, update } from '@/routes/password';

defineOptions({
    layout: {
        title: 'Atur password baru',
        description:
            'Buat password baru untuk akun Anda, lalu masuk kembali ke workspace.',
    },
});

const props = defineProps<{
    token: string;
    email: string;
    passwordRules: string;
}>();

const isPointerFine = useMediaQuery('(pointer: fine)');
const passwordHint = computed(() => describePasswordRules(props.passwordRules));

const submitClass =
    'h-11 w-full bg-orange-500 font-bold text-[#0b1220] shadow-lg shadow-orange-500/20 transition-colors hover:bg-orange-600 focus-visible:ring-orange-300/60 dark:bg-orange-500 dark:text-[#0b1220] dark:hover:bg-orange-400';
</script>

<template>
    <Head title="Atur password baru" />

    <Form
        v-bind="update.form()"
        v-slot="{ errors, processing }"
        :transform="(data) => ({ ...data, token, email })"
        :reset-on-success="['password', 'password_confirmation']"
    >
        <div class="grid gap-5">
            <div class="grid gap-2">
                <p class="text-sm font-semibold">Email</p>
                <p
                    class="flex items-center gap-2.5 rounded-md border border-input bg-muted px-3 py-3 text-sm text-muted-foreground select-all"
                >
                    <LockKeyhole class="size-4 shrink-0" aria-hidden="true" />
                    <span class="truncate">{{ props.email }}</span>
                </p>
                <p class="text-xs leading-5 text-muted-foreground">
                    Tautan reset hanya berlaku untuk email ini.
                </p>
            </div>

            <div class="grid gap-2">
                <Label for="password" class="font-semibold">
                    Password baru
                </Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    placeholder="Buat password baru"
                    required
                    :autofocus="isPointerFine"
                    :passwordrules="props.passwordRules"
                    :aria-invalid="Boolean(errors.password)"
                    :aria-describedby="
                        [
                            passwordHint ? 'password-hint' : null,
                            errors.password ? 'password-error' : null,
                        ]
                            .filter(Boolean)
                            .join(' ') || undefined
                    "
                    class="h-11"
                />
                <p
                    v-if="passwordHint"
                    id="password-hint"
                    class="text-xs leading-5 text-muted-foreground"
                >
                    {{ passwordHint }}
                </p>
                <InputError id="password-error" :message="errors.password" />
            </div>

            <div class="grid gap-2">
                <Label for="password_confirmation" class="font-semibold">
                    Konfirmasi password baru
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Ulangi password baru"
                    required
                    :passwordrules="props.passwordRules"
                    :aria-invalid="Boolean(errors.password_confirmation)"
                    :aria-describedby="
                        errors.password_confirmation
                            ? 'password-confirmation-error'
                            : undefined
                    "
                    class="h-11"
                />
                <InputError
                    id="password-confirmation-error"
                    :message="errors.password_confirmation"
                />
            </div>

            <SubmitButton
                :processing="processing"
                :class="submitClass"
                data-test="reset-password-button"
                label="Simpan password baru"
                loading-label="Menyimpan password…"
            />

            <p class="text-center text-sm text-muted-foreground">
                Tautan sudah kedaluwarsa?
                <TextLink :href="request()" class="font-semibold">
                    Minta tautan baru
                </TextLink>
            </p>
        </div>
    </Form>
</template>
