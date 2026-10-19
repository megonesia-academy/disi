<script setup lang="ts">
import { Form, Head, Link, usePage } from '@inertiajs/vue3';
import { BadgeCheck, Mail, UserRound } from '@lucide/vue';
import { computed } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import DeleteUser from '@/components/DeleteUser.vue';
import InputError from '@/components/InputError.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { edit } from '@/routes/profile';
import { send } from '@/routes/verification';

defineOptions({
    layout: {
        breadcrumbs: [
            {
                title: 'Profile settings',
                href: edit(),
            },
        ],
    },
});

const page = usePage();
const user = computed(() => page.props.auth.user);

const initials = computed(() => {
    const parts = (user.value.name ?? '')
        .trim()
        .split(/\s+/)
        .filter(Boolean)
        .slice(0, 2);

    return parts.map((part) => part[0]?.toUpperCase()).join('') || 'U';
});

const isVerified = computed(() => Boolean(user.value.email_verified_at));

const memberSince = computed(() => {
    const createdAt = user.value.created_at;

    if (!createdAt) {
        return null;
    }

    return new Intl.DateTimeFormat('id-ID', {
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(new Date(createdAt));
});
</script>

<template>
    <Head title="Profile settings" />

    <div class="space-y-5">
        <section class="rounded-lg border bg-background p-5 shadow-sm">
            <div class="flex flex-wrap items-center gap-4">
                <span
                    class="grid size-14 shrink-0 place-items-center rounded-full bg-primary text-lg font-semibold text-primary-foreground"
                    aria-hidden="true"
                >
                    {{ initials }}
                </span>
                <div class="min-w-0">
                    <h2 class="truncate text-lg font-semibold">
                        {{ user.name }}
                    </h2>
                    <p
                        class="flex items-center gap-1.5 truncate text-sm text-muted-foreground"
                    >
                        <Mail class="size-3.5 shrink-0" />
                        {{ user.email }}
                    </p>
                    <div class="mt-2 flex flex-wrap items-center gap-2">
                        <span
                            class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                            :class="
                                isVerified
                                    ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                    : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                            "
                        >
                            <BadgeCheck class="size-3.5" />
                            {{
                                isVerified
                                    ? 'Email terverifikasi'
                                    : 'Belum terverifikasi'
                            }}
                        </span>
                        <span
                            v-if="memberSince"
                            class="text-[11px] text-muted-foreground"
                        >
                            Member sejak {{ memberSince }}
                        </span>
                    </div>
                </div>
            </div>
        </section>

        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div class="border-b p-5">
                <h2 class="flex items-center gap-2 font-semibold">
                    <UserRound class="size-4 text-muted-foreground" />
                    Informasi profil
                </h2>
                <p class="mt-1 text-sm text-muted-foreground">
                    Perbarui nama dan alamat email yang dipakai untuk masuk.
                </p>
            </div>

            <Form
                v-bind="ProfileController.update.form()"
                class="space-y-5 p-5"
                v-slot="{ errors, processing }"
            >
                <div class="grid gap-4 sm:grid-cols-2">
                    <div class="grid gap-2">
                        <Label for="name">Nama</Label>
                        <Input
                            id="name"
                            name="name"
                            :default-value="user.name"
                            required
                            autocomplete="name"
                            placeholder="Nama lengkap"
                        />
                        <InputError :message="errors.name" />
                    </div>

                    <div class="grid gap-2">
                        <Label for="email">Alamat email</Label>
                        <Input
                            id="email"
                            type="email"
                            name="email"
                            :default-value="user.email"
                            required
                            autocomplete="username"
                            placeholder="nama@perusahaan.com"
                        />
                        <InputError :message="errors.email" />
                    </div>
                </div>

                <div
                    v-if="page.props.mustVerifyEmail && !user.email_verified_at"
                    class="rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
                >
                    <p>
                        Email Anda belum diverifikasi.
                        <Link
                            :href="send()"
                            as="button"
                            class="font-semibold underline underline-offset-4 hover:decoration-current"
                        >
                            Kirim ulang email verifikasi
                        </Link>
                    </p>

                    <p
                        v-if="page.props.status === 'verification-link-sent'"
                        class="mt-2 font-medium text-emerald-700 dark:text-emerald-300"
                    >
                        Tautan verifikasi baru sudah dikirim ke email Anda.
                    </p>
                </div>

                <div class="flex justify-end border-t pt-4">
                    <SubmitButton
                        :processing="processing"
                        data-test="update-profile-button"
                        label="Simpan perubahan"
                        loading-label="Menyimpan..."
                    />
                </div>
            </Form>
        </section>

        <DeleteUser />
    </div>
</template>
