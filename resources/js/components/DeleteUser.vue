<script setup lang="ts">
import { Form } from '@inertiajs/vue3';
import { Trash2, X } from '@lucide/vue';
import { useTemplateRef } from 'vue';
import ProfileController from '@/actions/App/Http/Controllers/Settings/ProfileController';
import Heading from '@/components/Heading.vue';
import InputError from '@/components/InputError.vue';
import PasswordInput from '@/components/PasswordInput.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
    DialogTrigger,
} from '@/components/ui/dialog';
import { Label } from '@/components/ui/label';

const passwordInput = useTemplateRef('passwordInput');
</script>

<template>
    <section class="overflow-hidden rounded-lg border bg-background shadow-sm">
        <div class="border-b p-5">
            <Heading
                variant="small"
                title="Hapus akun"
                description="Hapus akun Anda beserta seluruh data yang terkait."
            />
        </div>

        <div class="space-y-4 p-5">
            <div
                class="rounded-md border border-red-200 bg-red-50 p-4 text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
            >
                <p class="font-medium">Peringatan</p>
                <p class="mt-0.5 text-sm">
                    Tindakan ini permanen dan tidak dapat dibatalkan.
                </p>
            </div>

            <Dialog>
                <DialogTrigger as-child>
                    <Button
                        variant="destructive"
                        data-test="delete-user-button"
                    >
                        <Trash2 class="size-4" /> Hapus akun
                    </Button>
                </DialogTrigger>
                <DialogContent>
                    <Form
                        v-bind="ProfileController.destroy.form()"
                        reset-on-success
                        @error="() => passwordInput?.focus()"
                        :options="{
                            preserveScroll: true,
                        }"
                        class="space-y-6"
                        v-slot="{ errors, processing, reset, clearErrors }"
                    >
                        <DialogHeader class="space-y-3">
                            <DialogTitle>
                                Yakin ingin menghapus akun Anda?
                            </DialogTitle>
                            <DialogDescription>
                                Seluruh data akun akan dihapus permanen.
                                Masukkan password Anda untuk mengonfirmasi.
                            </DialogDescription>
                        </DialogHeader>

                        <div class="grid gap-2">
                            <Label for="password" class="sr-only">
                                Password
                            </Label>
                            <PasswordInput
                                id="password"
                                name="password"
                                ref="passwordInput"
                                placeholder="Password"
                            />
                            <InputError :message="errors.password" />
                        </div>

                        <DialogFooter class="gap-2">
                            <DialogClose as-child>
                                <Button
                                    variant="secondary"
                                    @click="
                                        () => {
                                            clearErrors();
                                            reset();
                                        }
                                    "
                                >
                                    <X class="size-4" />
                                    Batal
                                </Button>
                            </DialogClose>

                            <SubmitButton
                                variant="destructive"
                                :processing="processing"
                                data-test="confirm-delete-user-button"
                                label="Hapus akun"
                                loading-label="Menghapus..."
                            >
                                <Trash2 class="size-4" />
                            </SubmitButton>
                        </DialogFooter>
                    </Form>
                </DialogContent>
            </Dialog>
        </div>
    </section>
</template>
