<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/EmptyState.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

type Screen = {
    id: string;
    name: string;
    location: string | null;
    orientation: string;
    resolution_width: number;
    resolution_height: number;
    is_active: boolean;
    devices_count: number;
};

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
    };
    screens: Screen[];
    orientations: string[];
}>();

const editingId = ref<string | null>(null);

const form = useForm({
    name: '',
    location: '',
    orientation: 'landscape',
    resolution_width: 1920,
    resolution_height: 1080,
    is_active: true,
});

const editingScreen = computed(() => {
    return props.screens.find((screen) => screen.id === editingId.value);
});

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.orientation = 'landscape';
    form.resolution_width = 1920;
    form.resolution_height = 1080;
    form.is_active = true;
}

function editScreen(screen: Screen): void {
    editingId.value = screen.id;
    form.clearErrors();
    form.name = screen.name;
    form.location = screen.location ?? '';
    form.orientation = screen.orientation;
    form.resolution_width = screen.resolution_width;
    form.resolution_height = screen.resolution_height;
    form.is_active = screen.is_active;
}

function submit(): void {
    if (editingId.value) {
        form.put(`/screens/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: resetForm,
        });

        return;
    }

    form.post('/screens', {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}

function destroyScreen(screen: Screen): void {
    if (!window.confirm(`Delete screen "${screen.name}"?`)) {
        return;
    }

    form.delete(`/screens/${screen.id}`, {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}
</script>

<template>
    <Head title="Screens" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Screens</h1>
                <p class="text-sm text-muted-foreground">
                    Kelola layar untuk {{ tenant.name }}.
                </p>
            </div>
            <Link
                href="/dashboard"
                class="text-sm font-medium text-primary underline-offset-4 hover:underline"
            >
                Back to dashboard
            </Link>
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
                        {{ editingScreen ? 'Edit screen' : 'New screen' }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Data dasar lokasi dan resolusi display.
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

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Location
                        </label>
                        <Input v-model="form.location" />
                        <InputError :message="form.errors.location" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Orientation
                        </label>
                        <select
                            v-model="form.orientation"
                            class="border-input h-9 w-full rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option
                                v-for="orientation in orientations"
                                :key="orientation"
                                :value="orientation"
                            >
                                {{ orientation }}
                            </option>
                        </select>
                        <InputError :message="form.errors.orientation" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Width
                            </label>
                            <Input
                                v-model="form.resolution_width"
                                type="number"
                                min="1"
                            />
                            <InputError
                                :message="form.errors.resolution_width"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Height
                            </label>
                            <Input
                                v-model="form.resolution_height"
                                type="number"
                                min="1"
                            />
                            <InputError
                                :message="form.errors.resolution_height"
                            />
                        </div>
                    </div>

                    <label class="flex items-center gap-2 text-sm">
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        />
                        Active
                    </label>

                    <div class="flex gap-2">
                        <Button type="submit" :disabled="form.processing">
                            {{ editingScreen ? 'Update' : 'Create' }}
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
                    <h2 class="font-medium">Screen list</h2>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full min-w-[760px] text-sm">
                        <thead
                            class="bg-muted/50 text-left text-muted-foreground"
                        >
                            <tr>
                                <th class="px-4 py-3 font-medium">Screen</th>
                                <th class="px-4 py-3 font-medium">
                                    Resolution
                                </th>
                                <th class="px-4 py-3 font-medium">Devices</th>
                                <th class="px-4 py-3 font-medium">Status</th>
                                <th class="px-4 py-3 text-right font-medium">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr
                                v-for="screen in screens"
                                :key="screen.id"
                                class="border-t"
                            >
                                <td class="px-4 py-3">
                                    <div class="font-medium">
                                        {{ screen.name }}
                                    </div>
                                    <div class="text-xs text-muted-foreground">
                                        {{ screen.location ?? '-' }}
                                    </div>
                                </td>
                                <td class="px-4 py-3">
                                    {{ screen.resolution_width }} x
                                    {{ screen.resolution_height }}
                                    <span class="text-muted-foreground">
                                        · {{ screen.orientation }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">
                                    {{ screen.devices_count }}
                                </td>
                                <td class="px-4 py-3">
                                    <span
                                        class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                        :class="
                                            screen.is_active
                                                ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                                : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                        "
                                    >
                                        {{
                                            screen.is_active
                                                ? 'active'
                                                : 'inactive'
                                        }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <div class="flex justify-end gap-2">
                                        <Link
                                            :href="`/screens/${screen.id}/preview`"
                                            class="inline-flex h-8 items-center justify-center rounded-md border border-input bg-background px-3 text-sm font-medium shadow-xs transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                        >
                                            Preview
                                        </Link>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            @click="editScreen(screen)"
                                        >
                                            Edit
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            @click="destroyScreen(screen)"
                                        >
                                            Delete
                                        </Button>
                                    </div>
                                </td>
                            </tr>
                            <tr v-if="screens.length === 0">
                                <td
                                    colspan="5"
                                    class="px-4 py-6"
                                >
                                    <EmptyState
                                        title="Buat screen pertama"
                                        description="Screen mewakili lokasi display seperti lobby, kasir, atau ruang tunggu. Setelah screen dibuat, kamu bisa schedule playlist dan pair TV ke screen ini."
                                        action-label="Isi form screen"
                                        action-href="/screens"
                                        secondary-label="Upload media"
                                        secondary-href="/media-assets"
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
