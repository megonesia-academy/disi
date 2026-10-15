<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
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

            <div
                class="overflow-hidden rounded-lg border border-sidebar-border/70 dark:border-sidebar-border"
            >
                <div class="border-b px-4 py-3">
                    <h2 class="font-medium">Media list</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[900px] text-sm">
                        <thead
                            class="bg-muted/50 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">Media</th>
                                <th class="px-4 py-3 font-medium">Type</th>
                                <th class="px-4 py-3 font-medium">Size</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 font-medium">
                                    Duration
                                </th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="asset in mediaAssets"
                                :key="asset.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ asset.name }}
                                    </div>
                                    <a
                                        :href="asset.url"
                                        target="_blank"
                                        class="max-w-md truncate text-xs text-primary underline-offset-4 hover:underline"
                                    >
                                        {{ asset.path }}
                                    </a>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full bg-zinc-100 px-2 py-1 text-xs font-medium text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300"
                                    >
                                        {{ asset.type }}
                                    </span>
                                    <div class="mt-1 text-xs text-muted-foreground">
                                        {{ asset.mime_type ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <div>{{ readableSize(asset.size) }}</div>
                                    <div class="text-xs text-muted-foreground">
                                        <template
                                            v-if="asset.width && asset.height"
                                        >
                                            {{ asset.width }} x
                                            {{ asset.height }}
                                        </template>
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                        :class="
                                            asset.availability.status === 'ok'
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                : asset.availability.status ===
                                                    'missing'
                                                  ? 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300'
                                                  : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300'
                                        "
                                    >
                                        {{ asset.availability.label }}
                                    </span>
                                    <div
                                        class="mt-1 max-w-60 text-xs text-muted-foreground"
                                    >
                                        {{ asset.availability.message }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    {{
                                        asset.duration_seconds
                                            ? `${asset.duration_seconds}s`
                                            : '-'
                                    }}
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="editAsset(asset)"
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="destroyAsset(asset)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="mediaAssets.length === 0">
                                <td
                                    colspan="6"
                                    class="px-4 py-6"
                                >
                                    <EmptyState
                                        title="Upload media pertama"
                                        description="Tambahkan gambar, video, audio, atau URL web. Setelah media tersedia, kamu bisa memasukkannya ke playlist."
                                        action-label="Isi form media"
                                        action-href="/media-assets"
                                        secondary-label="Create playlist"
                                        secondary-href="/playlists"
                                    />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </section>
    </div>
</template>
