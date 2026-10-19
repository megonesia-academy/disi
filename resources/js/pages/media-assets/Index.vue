<script setup lang="ts">
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Clock3,
    Coins,
    ExternalLink,
    Globe2,
    HardDrive,
    ImageIcon,
    Music2,
    Pencil,
    Plus,
    Save,
    Trash2,
    Video,
    X,
} from '@lucide/vue';
import { computed, ref, watch } from 'vue';
import InputError from '@/components/InputError.vue';
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
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { isImageMedia, isUrlMedia, isVideoMedia } from '@/lib/media';

type MediaAsset = {
    id: string;
    name: string;
    type: string;
    disk: string;
    path: string;
    url: string;
    mime_type: string | null;
    size: number | null;
    duration_seconds: number | null;
    width: number | null;
    height: number | null;
    created_at: string | null;
    availability: {
        status: string;
        label: string;
        message: string;
    };
};

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
    };
    mediaAssets: MediaAsset[];
    types: string[];
    uploadCost: number;
    balance: number;
}>();

const page = usePage();
const billingError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.[
            'billing'
        ] ?? null,
);

const editingId = ref<string | null>(null);
const deletingId = ref<string | null>(null);
const formOpen = ref(false);
const previewFailedIds = ref<Set<string>>(new Set());
const previewUrl = ref('');
const urlPreviewFailed = ref(false);

const form = useForm<{
    name: string;
    type: string;
    file: File | null;
    url: string;
    duration_seconds: number | null;
}>({
    name: '',
    type: 'image',
    file: null,
    url: '',
    duration_seconds: 10,
});

const editingAsset = computed(() => {
    return props.mediaAssets.find((asset) => asset.id === editingId.value);
});

watch(formOpen, (isOpen) => {
    if (!isOpen) {
        resetForm();
    }
});

watch(
    () => [form.url, form.type],
    () => {
        previewUrl.value = '';
        urlPreviewFailed.value = false;
    },
);

watch(
    () => props.mediaAssets,
    () => {
        previewFailedIds.value = new Set();
    },
    { deep: true },
);

function commitUrlPreview(): void {
    previewUrl.value = form.url.trim();
    urlPreviewFailed.value = false;
}

function markPreviewFailed(assetId: string): void {
    previewFailedIds.value.add(assetId);
}

function onUrlPreviewError(): void {
    urlPreviewFailed.value = true;
}

function resetForm(): void {
    editingId.value = null;
    previewUrl.value = '';
    urlPreviewFailed.value = false;
    form.reset();
    form.clearErrors();
    form.type = 'image';
    form.file = null;
    form.duration_seconds = 10;
}

function openCreateDialog(): void {
    resetForm();
    formOpen.value = true;
}

function openEditDialog(asset: MediaAsset): void {
    editingId.value = asset.id;
    form.clearErrors();
    form.name = asset.name;
    form.type = asset.type;
    form.url = isUrlMedia(asset.type) ? asset.path : '';
    form.file = null;
    form.duration_seconds = asset.duration_seconds;
    formOpen.value = true;
}

function onFileChange(event: Event): void {
    const input = event.target as HTMLInputElement;
    form.file = input.files?.[0] ?? null;
}

function submit(): void {
    if (editingId.value) {
        form.transform((data) => ({
            name: data.name,
            duration_seconds: data.duration_seconds,
            ...(editingAsset.value && isUrlMedia(editingAsset.value.type)
                ? { url: data.url }
                : {}),
        })).put(`/media-assets/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: () => {
                formOpen.value = false;
            },
        });

        return;
    }

    form.post('/media-assets', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
        },
    });
}

function destroyAsset(asset: MediaAsset): void {
    if (!window.confirm(`Delete media "${asset.name}"?`)) {
        return;
    }

    deletingId.value = asset.id;
    form.delete(`/media-assets/${asset.id}`, {
        preserveScroll: true,
        onSuccess: resetForm,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

function typeLabel(type: string): string {
    if (type === 'image_url') {
        return 'Image URL';
    }

    if (type === 'video_url') {
        return 'Video URL';
    }

    return type.charAt(0).toUpperCase() + type.slice(1);
}

function readableSize(size: number | null): string {
    if (!size) {
        return '-';
    }

    if (size < 1024 * 1024) {
        return `${Math.round(size / 1024)} KB`;
    }

    return `${(size / 1024 / 1024).toFixed(1)} MB`;
}
</script>

<template>
    <Head title="Media Assets" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Media Assets
                </h1>
                <p class="text-sm text-muted-foreground">
                    Upload konten signage untuk {{ tenant.name }}.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button @click="openCreateDialog">
                    <Plus class="size-4" />
                    New media
                </Button>
                <Link
                    href="/devices"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                >
                    Devices
                </Link>
                <Link
                    href="/dashboard"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                >
                    Dashboard
                </Link>
            </div>
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

        <div
            v-if="uploadCost > 0"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-amber-300/70 bg-amber-50 p-3 text-xs text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
        >
            <Coins class="size-4 shrink-0" />
            <span class="min-w-0 flex-1">
                Upload file image/video/audio membutuhkan
                <b>{{ uploadCost }} token</b> per file. Saldo saat ini
                <b>{{ balance }} token</b>. Tipe <b>Image URL</b> /
                <b>Video URL</b> / <b>Web</b> tetap gratis.
            </span>
            <Link
                href="/workspace/billing"
                class="shrink-0 font-semibold underline underline-offset-4"
            >
                Billing
            </Link>
        </div>

        <section class="space-y-3">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <h2 class="font-medium">Media list</h2>
                    <p class="text-xs text-muted-foreground">
                        Preview dan kelola konten signage.
                    </p>
                </div>
                <span class="text-xs text-muted-foreground">
                    {{ mediaAssets.length }} media
                </span>
            </div>

            <div
                v-if="mediaAssets.length"
                class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
            >
                <article
                    v-for="asset in mediaAssets"
                    :key="asset.id"
                    class="group overflow-hidden rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        class="relative aspect-video overflow-hidden bg-zinc-950"
                    >
                        <img
                            v-if="isImageMedia(asset.type)"
                            :src="asset.url"
                            :alt="asset.name"
                            class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                            loading="lazy"
                            @error="markPreviewFailed(asset.id)"
                        />
                        <video
                            v-else-if="isVideoMedia(asset.type)"
                            :src="asset.url"
                            class="h-full w-full object-cover"
                            muted
                            playsinline
                            preload="metadata"
                        />
                        <div
                            v-else
                            class="grid h-full place-items-center bg-gradient-to-br from-zinc-900 via-zinc-800 to-zinc-950 text-white"
                        >
                            <div class="text-center">
                                <Music2
                                    v-if="asset.type === 'audio'"
                                    class="mx-auto size-10 text-cyan-300"
                                />
                                <Globe2
                                    v-else
                                    class="mx-auto size-10 text-violet-300"
                                />
                                <p class="mt-3 text-sm font-medium">
                                    {{
                                        asset.type === 'audio'
                                            ? 'Audio content'
                                            : 'Web content'
                                    }}
                                </p>
                            </div>
                        </div>

                        <div
                            v-if="
                                isImageMedia(asset.type) &&
                                previewFailedIds.has(asset.id)
                            "
                            class="absolute inset-0 grid place-items-center bg-zinc-950/95 p-4 text-center"
                        >
                            <div>
                                <ImageIcon
                                    class="mx-auto size-8 text-zinc-500"
                                />
                                <p
                                    class="mt-2 text-xs font-medium text-zinc-300"
                                >
                                    Preview tidak tersedia
                                </p>
                                <p
                                    class="mt-1 line-clamp-2 text-[11px] break-all text-zinc-500"
                                >
                                    {{ asset.path }}
                                </p>
                            </div>
                        </div>

                        <div
                            class="absolute top-3 left-3 flex items-center gap-2"
                        >
                            <span
                                class="inline-flex items-center gap-1.5 rounded-md bg-black/70 px-2 py-1 text-xs font-medium text-white backdrop-blur-sm"
                            >
                                <ExternalLink
                                    v-if="asset.type === 'image_url'"
                                    class="size-3.5"
                                />
                                <ImageIcon
                                    v-else-if="asset.type === 'image'"
                                    class="size-3.5"
                                />
                                <Video
                                    v-else-if="isVideoMedia(asset.type)"
                                    class="size-3.5"
                                />
                                <Music2
                                    v-else-if="asset.type === 'audio'"
                                    class="size-3.5"
                                />
                                <Globe2 v-else class="size-3.5" />
                                {{ typeLabel(asset.type) }}
                            </span>
                        </div>

                        <span
                            class="absolute top-3 right-3 inline-flex rounded-md px-2 py-1 text-xs font-medium shadow-sm"
                            :class="
                                asset.availability.status === 'ok'
                                    ? 'bg-emerald-500 text-white'
                                    : asset.availability.status === 'missing'
                                      ? 'bg-red-500 text-white'
                                      : 'bg-amber-400 text-amber-950'
                            "
                        >
                            {{ asset.availability.label }}
                        </span>
                    </div>

                    <div class="p-4">
                        <div class="min-w-0">
                            <h3
                                class="truncate font-semibold"
                                :title="asset.name"
                            >
                                {{ asset.name }}
                            </h3>
                            <p
                                class="mt-0.5 truncate text-xs text-muted-foreground"
                                :title="asset.path"
                            >
                                {{ asset.mime_type ?? asset.path }}
                            </p>
                        </div>

                        <div
                            class="mt-4 grid grid-cols-3 gap-2 rounded-md bg-workspace p-3 text-xs"
                        >
                            <div class="min-w-0">
                                <div
                                    class="flex items-center gap-1 text-muted-foreground"
                                >
                                    <HardDrive class="size-3.5" />
                                    Size
                                </div>
                                <p class="mt-1 truncate font-medium">
                                    {{ readableSize(asset.size) }}
                                </p>
                            </div>
                            <div class="min-w-0 border-l pl-2">
                                <div
                                    class="flex items-center gap-1 text-muted-foreground"
                                >
                                    <Clock3 class="size-3.5" />
                                    Duration
                                </div>
                                <p class="mt-1 truncate font-medium">
                                    {{
                                        asset.duration_seconds
                                            ? `${asset.duration_seconds}s`
                                            : '-'
                                    }}
                                </p>
                            </div>
                            <div class="min-w-0 border-l pl-2">
                                <div class="text-muted-foreground">
                                    Resolution
                                </div>
                                <p class="mt-1 truncate font-medium">
                                    {{
                                        asset.width && asset.height
                                            ? `${asset.width} x ${asset.height}`
                                            : '-'
                                    }}
                                </p>
                            </div>
                        </div>

                        <p
                            class="mt-3 min-h-10 text-xs leading-5 text-muted-foreground"
                        >
                            {{ asset.availability.message }}
                        </p>

                        <div class="mt-4 flex items-center gap-2 border-t pt-4">
                            <a
                                :href="asset.url"
                                target="_blank"
                                class="inline-flex h-9 items-center gap-2 rounded-md border bg-background px-3 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                            >
                                <ExternalLink class="size-4" />
                                Preview
                            </a>
                            <div class="ml-auto flex gap-1">
                                <Button
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    title="Edit media"
                                    @click="openEditDialog(asset)"
                                >
                                    <Pencil class="size-4" />
                                </Button>
                                <SubmitButton
                                    type="button"
                                    size="icon"
                                    variant="ghost"
                                    class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                    title="Delete media"
                                    aria-label="Delete media"
                                    loading-label="Menghapus..."
                                    :processing="deletingId === asset.id"
                                    @click="destroyAsset(asset)"
                                >
                                    <Trash2 class="size-4" />
                                </SubmitButton>
                            </div>
                        </div>
                    </div>
                </article>
            </div>

            <div
                v-else
                class="rounded-lg border border-sidebar-border/70 bg-background p-8 text-center dark:border-sidebar-border"
            >
                <h3 class="text-lg font-semibold tracking-tight">
                    Belum ada media
                </h3>
                <p
                    class="mx-auto mt-2 max-w-md text-sm leading-6 text-muted-foreground"
                >
                    Tambahkan gambar, image URL, video, video URL, audio, atau
                    URL web. Setelah media tersedia, kamu bisa memasukkannya ke
                    playlist.
                </p>
                <div class="mt-5 flex flex-wrap justify-center gap-2">
                    <Button @click="openCreateDialog">
                        <Plus class="size-4" />
                        Tambah media
                    </Button>
                    <Button variant="outline" as-child>
                        <Link href="/playlists">Create playlist</Link>
                    </Button>
                </div>
            </div>
        </section>

        <Dialog v-model:open="formOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto">
                <form class="space-y-4" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>
                            {{ editingAsset ? 'Edit media' : 'New media' }}
                        </DialogTitle>
                        <DialogDescription>
                            Upload image, image URL, video, video URL, audio,
                            atau URL web.
                        </DialogDescription>
                    </DialogHeader>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Name
                        </label>
                        <Input v-model="form.name" placeholder="Nama media" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div v-if="!editingAsset">
                        <label class="mb-1 block text-sm font-medium">
                            Type
                        </label>
                        <select
                            v-model="form.type"
                            class="option:bg-background option:text-foreground h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option
                                v-for="type in types"
                                :key="type"
                                :value="type"
                            >
                                {{ typeLabel(type) }}
                            </option>
                        </select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <div v-if="!editingAsset && !isUrlMedia(form.type)">
                        <label class="mb-1 block text-sm font-medium">
                            File
                        </label>
                        <input
                            type="file"
                            class="block w-full text-sm"
                            :accept="
                                form.type === 'image'
                                    ? 'image/*'
                                    : form.type === 'audio'
                                      ? 'audio/*'
                                      : 'video/*'
                            "
                            @change="onFileChange"
                        />
                        <p class="mt-1 text-[11px] text-muted-foreground">
                            Upload file memotong
                            {{ uploadCost }} token per file (saldo:
                            {{ balance }}). Gunakan Image URL / Video URL untuk
                            media gratis.
                        </p>
                        <InputError :message="form.errors.file" />
                    </div>

                    <div v-if="isUrlMedia(form.type)">
                        <label class="mb-1 block text-sm font-medium">
                            {{
                                isImageMedia(form.type)
                                    ? 'Image URL'
                                    : isVideoMedia(form.type)
                                      ? 'Video URL'
                                      : 'URL'
                            }}
                        </label>
                        <Input
                            v-model="form.url"
                            :placeholder="
                                isImageMedia(form.type)
                                    ? 'https://images.example.com/poster.jpg'
                                    : isVideoMedia(form.type)
                                      ? 'https://cdn.example.com/video.mp4'
                                      : 'https://example.com'
                            "
                            @blur="commitUrlPreview"
                        />
                        <InputError :message="form.errors.url" />
                        <p class="mt-1 text-xs text-muted-foreground">
                            Pakai direct link ke file
                            {{
                                isImageMedia(form.type)
                                    ? 'gambar (.jpg, .png, .webp, .gif)'
                                    : isVideoMedia(form.type)
                                      ? 'video (.mp4, .webm, .m3u8)'
                                      : 'web'
                            }}, bukan URL halaman website.
                        </p>

                        <div
                            v-if="isImageMedia(form.type) && previewUrl"
                            class="mt-2 overflow-hidden rounded-md border bg-workspace"
                        >
                            <img
                                v-if="!urlPreviewFailed"
                                :src="previewUrl"
                                alt="Preview image URL"
                                class="max-h-40 w-full object-contain"
                                @error="onUrlPreviewError"
                            />
                            <p v-else class="p-3 text-xs text-destructive">
                                URL ini tidak bisa dimuat sebagai gambar.
                                Pastikan link langsung ke file gambar, misalnya
                                https://images.unsplash.com/photo-...jpg.
                            </p>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Duration seconds
                        </label>
                        <Input
                            v-model="form.duration_seconds"
                            type="number"
                            min="1"
                        />
                        <InputError :message="form.errors.duration_seconds" />
                    </div>

                    <DialogFooter class="gap-2 pt-2">
                        <DialogClose as-child>
                            <Button
                                type="button"
                                variant="outline"
                                :disabled="form.processing"
                            >
                                <X class="size-4" />
                                Cancel
                            </Button>
                        </DialogClose>
                        <SubmitButton
                            :processing="form.processing"
                            :label="editingAsset ? 'Update' : 'Create'"
                            loading-label="Menyimpan..."
                        >
                            <Save v-if="editingAsset" class="size-4" />
                            <Plus v-else class="size-4" />
                        </SubmitButton>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
