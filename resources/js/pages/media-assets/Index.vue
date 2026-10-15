<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import {
    Clock3,
    ExternalLink,
    Globe2,
    HardDrive,
    ImageIcon,
    Music2,
    Pencil,
    Trash2,
    Video,
} from '@lucide/vue';
import { computed, ref } from 'vue';

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
}>();

const editingId = ref<string | null>(null);

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

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.type = 'image';
    form.file = null;
    form.duration_seconds = 10;
}

function editAsset(asset: MediaAsset): void {
    editingId.value = asset.id;
    form.clearErrors();
    form.name = asset.name;
    form.type = asset.type;
    form.url = asset.type === 'web' ? asset.path : '';
    form.file = null;
    form.duration_seconds = asset.duration_seconds;
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
        })).put(`/media-assets/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: resetForm,
        });

        return;
    }

    form.post('/media-assets', {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: resetForm,
    });
}

function destroyAsset(asset: MediaAsset): void {
    if (!window.confirm(`Delete media "${asset.name}"?`)) {
        return;
    }

    form.delete(`/media-assets/${asset.id}`, {
        preserveScroll: true,
        onSuccess: resetForm,
    });
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
            <div class="flex gap-3 text-sm">
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

        <section
            class="grid gap-4 lg:grid-cols-[minmax(320px,420px)_minmax(0,1fr)]"
        >
            <form
                class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                @submit.prevent="submit"
            >
                <div class="mb-4">
                    <h2 class="font-medium">
                        {{ editingAsset ? 'Edit media' : 'New media' }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Image, video, audio upload atau URL web.
                    </p>
                </div>

                <div class="space-y-4">
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Name
                        </label>
                        <Input v-model="form.name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div v-if="!editingAsset">
                        <label class="mb-1 block text-sm font-medium">
                            Type
                        </label>
                        <select
                            v-model="form.type"
                            class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option
                                v-for="type in types"
                                :key="type"
                                :value="type"
                            >
                                {{ type }}
                            </option>
                        </select>
                        <InputError :message="form.errors.type" />
                    </div>

                    <div v-if="!editingAsset && form.type !== 'web'">
                        <label class="mb-1 block text-sm font-medium">
                            File
                        </label>
                        <input
                            type="file"
                            class="block w-full text-sm"
                            :accept="
                                form.type === 'image' ? 'image/*' : form.type === 'audio' ? 'audio/*' : 'video/*'
                            "
                            @change="onFileChange"
                        />
                        <InputError :message="form.errors.file" />
                    </div>

                    <div v-if="!editingAsset && form.type === 'web'">
                        <label class="mb-1 block text-sm font-medium">
                            URL
                        </label>
                        <Input v-model="form.url" />
                        <InputError :message="form.errors.url" />
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

                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing">
                            {{ editingAsset ? 'Update' : 'Create' }}
                        </Button>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="resetForm"
                        >
                            Cancel
                        </Button>
                    </div>
                </div>
            </form>

            <div class="min-w-0">
                <div class="mb-3 flex items-center justify-between gap-3">
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

                <div v-if="mediaAssets.length" class="grid gap-4 2xl:grid-cols-2">
                    <article
                        v-for="asset in mediaAssets"
                        :key="asset.id"
                        class="group overflow-hidden rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div class="relative aspect-video overflow-hidden bg-zinc-950">
                            <img
                                v-if="asset.type === 'image'"
                                :src="asset.url"
                                :alt="asset.name"
                                class="h-full w-full object-cover transition-transform duration-300 group-hover:scale-[1.02]"
                                loading="lazy"
                            />
                            <video
                                v-else-if="asset.type === 'video'"
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
                                    <Music2 v-if="asset.type === 'audio'" class="mx-auto size-10 text-cyan-300" />
                                    <Globe2 v-else class="mx-auto size-10 text-violet-300" />
                                    <p class="mt-3 text-sm font-medium">
                                        {{ asset.type === 'audio' ? 'Audio content' : 'Web content' }}
                                    </p>
                                </div>
                            </div>

                            <div class="absolute left-3 top-3 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-md bg-black/70 px-2 py-1 text-xs font-medium text-white backdrop-blur-sm">
                                    <ImageIcon v-if="asset.type === 'image'" class="size-3.5" />
                                    <Video v-else-if="asset.type === 'video'" class="size-3.5" />
                                    <Music2 v-else-if="asset.type === 'audio'" class="size-3.5" />
                                    <Globe2 v-else class="size-3.5" />
                                    {{ asset.type }}
                                </span>
                            </div>

                            <span
                                class="absolute right-3 top-3 inline-flex rounded-md px-2 py-1 text-xs font-medium shadow-sm"
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
                                <h3 class="truncate font-semibold" :title="asset.name">
                                    {{ asset.name }}
                                </h3>
                                <p class="mt-0.5 truncate text-xs text-muted-foreground" :title="asset.path">
                                    {{ asset.mime_type ?? asset.path }}
                                </p>
                            </div>

                            <div class="mt-4 grid grid-cols-3 gap-2 rounded-md bg-muted/40 p-3 text-xs">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-1 text-muted-foreground">
                                        <HardDrive class="size-3.5" />
                                        Size
                                    </div>
                                    <p class="mt-1 truncate font-medium">
                                        {{ readableSize(asset.size) }}
                                    </p>
                                </div>
                                <div class="min-w-0 border-l pl-2">
                                    <div class="flex items-center gap-1 text-muted-foreground">
                                        <Clock3 class="size-3.5" />
                                        Duration
                                    </div>
                                    <p class="mt-1 truncate font-medium">
                                        {{ asset.duration_seconds ? `${asset.duration_seconds}s` : '-' }}
                                    </p>
                                </div>
                                <div class="min-w-0 border-l pl-2">
                                    <div class="text-muted-foreground">Resolution</div>
                                    <p class="mt-1 truncate font-medium">
                                        {{ asset.width && asset.height ? `${asset.width} x ${asset.height}` : '-' }}
                                    </p>
                                </div>
                            </div>

                            <p class="mt-3 min-h-10 text-xs leading-5 text-muted-foreground">
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
                                        @click="editAsset(asset)"
                                    >
                                        <Pencil class="size-4" />
                                    </Button>
                                    <Button
                                        type="button"
                                        size="icon"
                                        variant="ghost"
                                        class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                        title="Delete media"
                                        @click="destroyAsset(asset)"
                                    >
                                        <Trash2 class="size-4" />
                                    </Button>
                                </div>
                            </div>
                        </div>
                    </article>
                </div>

                <div
                    v-else
                    class="rounded-lg border border-sidebar-border/70 bg-background p-4 dark:border-sidebar-border"
                >
                    <EmptyState
                        title="Upload media pertama"
                        description="Tambahkan gambar, video, audio, atau URL web. Setelah media tersedia, kamu bisa memasukkannya ke playlist."
                        action-label="Isi form media"
                        action-href="/media-assets"
                        secondary-label="Create playlist"
                        secondary-href="/playlists"
                    />
                </div>
            </div>
        </section>
    </div>
</template>
