<script setup lang="ts">
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';

const open = defineModel<boolean>('open', { required: true });

const isDevServer = import.meta.env.DEV;
const isSecureContext =
    typeof window === 'undefined' ? true : window.isSecureContext;
</script>

<template>
    <Dialog :open="open" @update:open="open = $event">
        <DialogContent class="sm:max-w-md">
            <DialogHeader>
                <DialogTitle>Install aplikasi Disi</DialogTitle>
                <DialogDescription>
                    Browser ini belum menawarkan install otomatis. Pakai langkah
                    berikut, atau buka lewat browser yang mendukung install PWA.
                </DialogDescription>
            </DialogHeader>

            <div class="space-y-3 text-sm">
                <p
                    v-if="!isSecureContext"
                    class="rounded-md border border-amber-200 bg-amber-50 px-3 py-2 text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
                >
                    Halaman ini tidak diakses lewat HTTPS. Service worker
                    (syarat install PWA) hanya jalan di origin aman: HTTPS atau
                    localhost. Contoh lokal:
                    <code class="font-mono">herd secure disi</code>.
                </p>
                <p
                    v-if="isDevServer"
                    class="rounded-md border border-sky-200 bg-sky-50 px-3 py-2 text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200"
                >
                    Mode dev server sedang aktif, jadi service worker dimatikan.
                    Jalankan <code class="font-mono">npm run build</code> dan
                    akses aplikasi tanpa
                    <code class="font-mono">npm run dev</code>.
                </p>

                <ol class="list-decimal space-y-1 pl-5">
                    <li>
                        Chrome/Edge desktop: klik ikon install di address bar,
                        atau menu <span aria-hidden="true">⋮</span> → "Install
                        Disi".
                    </li>
                    <li>
                        Chrome Android: menu <span aria-hidden="true">⋮</span> →
                        "Add to Home screen" / "Install app".
                    </li>
                    <li>
                        iPhone/iPad (Safari): tombol Share → "Add to Home
                        Screen".
                    </li>
                </ol>
            </div>

            <DialogFooter>
                <DialogClose as-child>
                    <Button type="button" variant="outline">Tutup</Button>
                </DialogClose>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
