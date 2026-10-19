<script setup lang="ts">
import { useMediaQuery } from '@vueuse/core';
import { Form, Head } from '@inertiajs/vue3';
import { ArrowRight } from '@lucide/vue';
import { computed } from 'vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import TextLink from '@/components/TextLink.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';
import { describePasswordRules } from '@/lib/passwordRules';
import { login } from '@/routes';
import { store } from '@/routes/register';

defineOptions({
    layout: {
        title: 'Buat workspace Anda',
        description: 'Mulai kelola konten dan layar digital dalam satu tempat.',
    },
});

const props = defineProps<{
    passwordRules: string;
}>();

const isPointerFine = useMediaQuery('(pointer: fine)');
const passwordHint = computed(() => describePasswordRules(props.passwordRules));

const submitClass =
    'h-11 w-full bg-orange-500 font-bold text-[#0b1220] shadow-lg shadow-orange-500/20 transition-colors hover:bg-orange-600 focus-visible:ring-orange-300/60 dark:bg-orange-500 dark:text-[#0b1220] dark:hover:bg-orange-400';
</script>

<template>
    <Head title="Daftar" />

    <Form
        v-bind="store.form()"
        v-slot="{ errors, processing }"
        :reset-on-success="['password', 'password_confirmation']"
        class="grid gap-6"
    >
        <div class="grid gap-5">
            <div class="grid gap-2">
                <Label for="name" class="font-semibold">Nama lengkap</Label>
                <Input
                    id="name"
                    v-focus="isPointerFine"
                    type="text"
                    name="name"
                    autocomplete="name"
                    required
                    placeholder="Nama Anda"
                    class="h-11"
                    :aria-invalid="Boolean(errors.name)"
                    :aria-describedby="errors.name ? 'name-error' : undefined"
                />
                <InputError id="name-error" :message="errors.name" />
            </div>

            <div class="grid gap-2">
                <Label for="email" class="font-semibold">Email</Label>
                <Input
                    id="email"
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

            <div class="grid gap-2">
                <Label for="password" class="font-semibold">Password</Label>
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="new-password"
                    placeholder="Buat password"
                    required
                    class="h-11"
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
                    Konfirmasi password
                </Label>
                <PasswordInput
                    id="password_confirmation"
                    name="password_confirmation"
                    autocomplete="new-password"
                    placeholder="Ulangi password"
                    required
                    class="h-11"
                    :passwordrules="props.passwordRules"
                    :aria-invalid="Boolean(errors.password_confirmation)"
                    :aria-describedby="
                        errors.password_confirmation
                            ? 'password-confirmation-error'
                            : undefined
                    "
                />
                <InputError
                    id="password-confirmation-error"
                    :message="errors.password_confirmation"
                />
            </div>

            <SubmitButton
                :processing="processing"
                :class="submitClass"
                data-test="register-user-button"
                label="Buat akun"
                loading-label="Membuat akun…"
            >
                <ArrowRight class="size-4" aria-hidden="true" />
            </SubmitButton>
        </div>

        <p class="text-center text-sm text-muted-foreground">
            Sudah punya akun?
            <TextLink :href="login()" class="font-semibold">Masuk</TextLink>
        </p>
    </Form>
</template>
