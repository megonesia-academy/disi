<script setup lang="ts">
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
import { Head, Link, useForm, usePage } from '@inertiajs/vue3';
import {
    Eye,
    MapPin,
    Monitor,
    Pencil,
    Plus,
    Search,
    Smartphone,
    Trash2,
    X,
} from '@lucide/vue';
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
    usage: {
        screens: number;
        screen_limit: number;
        free_screens: number;
        can_add_screen: boolean;
    };
}>();

const formOpen = ref(false);
const editingId = ref<string | null>(null);
const deletingId = ref<string | null>(null);
const search = ref('');
const statusFilter = ref<'all' | 'active' | 'inactive'>('all');
const orientationFilter = ref('all');

const form = useForm({
    name: '',
    location: '',
    orientation: props.orientations[0] ?? 'landscape',
    resolution_width: 1920,
    resolution_height: 1080,
    is_active: true,
});

const editingScreen = computed(() =>
    props.screens.find((screen) => screen.id === editingId.value),
);

const page = usePage();
const billingError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.[
            'billing'
        ] ?? null,
);

const activeCount = computed(
    () => props.screens.filter((screen) => screen.is_active).length,
);

const portraitCount = computed(
    () =>
        props.screens.filter((screen) => screen.orientation === 'portrait')
            .length,
);

const devicesAssigned = computed(() =>
    props.screens.reduce((total, screen) => total + screen.devices_count, 0),
);

const visibleScreens = computed(() => {
    const term = search.value.trim().toLowerCase();

    return [...props.screens]
        .filter((screen) => {
            const matchesTerm =
                term === '' ||
                screen.name.toLowerCase().includes(term) ||
                (screen.location ?? '').toLowerCase().includes(term);
            const matchesStatus =
                statusFilter.value === 'all' ||
                (statusFilter.value === 'active'
                    ? screen.is_active
                    : !screen.is_active);
            const matchesOrientation =
                orientationFilter.value === 'all' ||
                screen.orientation === orientationFilter.value;

            return matchesTerm && matchesStatus && matchesOrientation;
        })
        .sort((a, b) => a.name.localeCompare(b.name));
});

function orientationLabel(orientation: string): string {
    return orientation === 'portrait' ? 'Portrait' : 'Landscape';
}

function resolutionLabel(screen: Screen): string {
    return `${screen.resolution_width} × ${screen.resolution_height}`;
}

function aspectStyle(screen: Screen): Record<string, string> {
    return {
        aspectRatio: `${screen.resolution_width} / ${screen.resolution_height}`,
    };
}

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.orientation = props.orientations[0] ?? 'landscape';
    form.resolution_width = 1920;
    form.resolution_height = 1080;
    form.is_active = true;
}

function openCreate(): void {
    if (!props.usage.can_add_screen) {
        return;
    }

    resetForm();
    formOpen.value = true;
}

function openEdit(screen: Screen): void {
    form.clearErrors();
    editingId.value = screen.id;
    form.name = screen.name;
    form.location = screen.location ?? '';
    form.orientation = screen.orientation;
    form.resolution_width = screen.resolution_width;
    form.resolution_height = screen.resolution_height;
    form.is_active = screen.is_active;
    formOpen.value = true;
}

function handleFormOpenChange(next: boolean): void {
    if (next) {
        formOpen.value = true;

        return;
    }

    formOpen.value = false;
    resetForm();
}

function submit(): void {
    const options = {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
            resetForm();
        },
    };

    if (editingId.value) {
        form.put(`/screens/${editingId.value}`, options);

        return;
    }

    form.post('/screens', options);
}

function destroyScreen(screen: Screen): void {
    if (!window.confirm(`Delete screen "${screen.name}"?`)) {
        return;
    }

    deletingId.value = screen.id;
    form.delete(`/screens/${screen.id}`, {
        preserveScroll: true,
        onSuccess: resetForm,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Screens" />

    <div
        class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex rounded-full bg-sky-50 px-2.5 py-1 text-xs font-medium text-sky-700 dark:bg-sky-950 dark:text-sky-300"
                >
                    Display targets
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">Screens</h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Kelola screen display untuk {{ tenant.name }}. Atur
                    orientasi dan resolusi, lalu pair device dan schedule
                    playlist ke screen ini.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/devices">
                        <Monitor class="size-4" />
                        Devices
                    </Link>
                </Button>
                <Button
                    type="button"
                    :disabled="!usage.can_add_screen"
                    :title="
                        usage.can_add_screen
                            ? 'Tambah screen baru'
                            : 'Batas screen tercapai. Beli slot screen tambahan di halaman Billing.'
                    "
                    @click="openCreate"
                >
                    <Plus class="size-4" /> New screen
                </Button>
            </div>
        </section>

        <div
            v-if="!usage.can_add_screen"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-amber-300/70 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
        >
            <span class="min-w-0 flex-1">
                Batas screen paket Anda tercapai ({{ usage.screens }}/{{
                    usage.screen_limit
                }}). Paket gratis mencakup {{ usage.free_screens }} screen. Beli
                slot screen tambahan untuk membuat screen baru.
            </span>
            <Link
                href="/workspace/billing"
                class="shrink-0 font-semibold underline underline-offset-4"
            >
                Buka billing
            </Link>
        </div>

        <div
            v-if="billingError"
            class="flex flex-wrap items-center gap-2 rounded-lg border border-amber-300/70 bg-amber-50 p-4 text-sm text-amber-800 dark:border-amber-500/30 dark:bg-amber-500/10 dark:text-amber-200"
        >
            <span class="min-w-0 flex-1">{{ billingError }}</span>
            <Link
                href="/workspace/billing"
                class="shrink-0 font-semibold underline underline-offset-4"
            >
                Buka billing
            </Link>
        </div>

        <section class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    Screens
                    <span class="text-xs"
                        >({{ usage.screens }}/{{ usage.screen_limit }})</span
                    >
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ screens.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Active</div>
                <div class="mt-2 text-2xl font-semibold">{{ activeCount }}</div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    Devices assigned
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ devicesAssigned }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Portrait</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ portraitCount }}
                </div>
            </div>
        </section>

        <section
            class="flex flex-wrap items-center gap-2 rounded-lg border bg-background p-3 shadow-sm"
        >
            <div class="relative min-w-56 flex-1">
                <Search
                    class="pointer-events-none absolute top-2.5 left-3 size-4 text-muted-foreground"
                />
                <Input
                    v-model="search"
                    class="pl-9"
                    placeholder="Cari nama atau lokasi screen..."
                />
            </div>
            <select
                v-model="statusFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua status</option>
                <option value="active">Active</option>
                <option value="inactive">Inactive</option>
            </select>
            <select
                v-model="orientationFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua orientasi</option>
                <option
                    v-for="orientation in orientations"
                    :key="orientation"
                    :value="orientation"
                >
                    {{ orientationLabel(orientation) }}
                </option>
            </select>
            <span class="ml-auto text-xs text-muted-foreground">
                {{ visibleScreens.length }} dari {{ screens.length }} screen
            </span>
        </section>

        <section
            v-if="visibleScreens.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="screen in visibleScreens"
                :key="screen.id"
                class="flex flex-col overflow-hidden rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
            >
                <div
                    class="relative grid h-40 place-items-center overflow-hidden bg-gradient-to-br from-zinc-950 via-zinc-900 to-zinc-800 p-4"
                >
                    <div
                        class="grid max-h-full max-w-[70%] place-items-center rounded-md border border-white/20 bg-zinc-900/80 text-white shadow-lg"
                        :style="aspectStyle(screen)"
                    >
                        <div class="p-3 text-center">
                            <component
                                :is="
                                    screen.orientation === 'portrait'
                                        ? Smartphone
                                        : Monitor
                                "
                                class="mx-auto size-5 text-sky-300"
                            />
                            <p
                                class="mt-1 text-[10px] font-medium whitespace-nowrap text-zinc-300"
                            >
                                {{ resolutionLabel(screen) }}
                            </p>
                        </div>
                    </div>

                    <span
                        class="absolute top-2 left-2 inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[11px] font-semibold"
                        :class="
                            screen.is_active
                                ? 'bg-emerald-500 text-white'
                                : 'bg-zinc-600 text-white'
                        "
                    >
                        <span class="size-1.5 rounded-full bg-current" />
                        {{ screen.is_active ? 'Active' : 'Inactive' }}
                    </span>
                    <span
                        class="absolute top-2 right-2 rounded-full bg-black/60 px-2 py-0.5 text-[11px] font-medium text-white"
                    >
                        {{ screen.devices_count }} device
                    </span>
                </div>

                <div class="flex flex-1 flex-col p-4">
                    <div class="min-w-0">
                        <h2 class="truncate font-semibold" :title="screen.name">
                            {{ screen.name }}
                        </h2>
                        <p
                            class="mt-0.5 flex items-center gap-1 truncate text-xs text-muted-foreground"
                        >
                            <MapPin class="size-3.5 shrink-0" />
                            {{ screen.location ?? 'Tanpa lokasi' }}
                        </p>
                    </div>

                    <div
                        class="mt-3 flex flex-wrap items-center gap-1.5 text-[11px] text-muted-foreground"
                    >
                        <span class="rounded-full bg-muted px-2 py-0.5">
                            {{ orientationLabel(screen.orientation) }}
                        </span>
                        <span class="rounded-full bg-muted px-2 py-0.5">
                            {{ resolutionLabel(screen) }}
                        </span>
                    </div>

                    <div class="mt-auto flex items-center gap-1 border-t pt-3">
                        <Button size="sm" variant="outline" as-child>
                            <Link :href="`/screens/${screen.id}/preview`">
                                <Eye class="size-4" />
                                Preview
                            </Link>
                        </Button>
                        <div class="ml-auto flex gap-1">
                            <Button
                                type="button"
                                size="icon"
                                variant="ghost"
                                title="Edit screen"
                                aria-label="Edit screen"
                                @click="openEdit(screen)"
                            >
                                <Pencil class="size-4" />
                            </Button>
                            <SubmitButton
                                type="button"
                                size="icon"
                                variant="ghost"
                                class="text-destructive hover:bg-destructive/10 hover:text-destructive"
                                title="Delete screen"
                                aria-label="Delete screen"
                                loading-label="Menghapus..."
                                :processing="deletingId === screen.id"
                                @click="destroyScreen(screen)"
                            >
                                <Trash2 class="size-4" />
                            </SubmitButton>
                        </div>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-else-if="screens.length"
            class="rounded-lg border border-dashed bg-background p-10 text-center"
        >
            <div
                class="mx-auto grid size-12 place-items-center rounded-full bg-muted"
            >
                <Search class="size-6 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">
                Tidak ada screen yang cocok
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Ubah kata kunci, status, atau orientasi.
            </p>
            <div class="mt-5 flex justify-center">
                <Button
                    type="button"
                    variant="outline"
                    @click="
                        search = '';
                        statusFilter = 'all';
                        orientationFilter = 'all';
                    "
                >
                    <X class="size-4" /> Reset filter
                </Button>
            </div>
        </section>

        <section
            v-else
            class="rounded-lg border border-dashed bg-background p-10 text-center"
        >
            <div
                class="mx-auto grid size-12 place-items-center rounded-full bg-muted"
            >
                <Monitor class="size-6 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">
                Belum ada screen
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                Screen mewakili lokasi display seperti lobby, kasir, atau ruang
                tunggu. Setelah screen dibuat, pair device dan schedule playlist
                ke screen ini.
            </p>
            <div class="mt-5 flex flex-wrap justify-center gap-2">
                <Button type="button" @click="openCreate">
                    <Plus class="size-4" /> Buat screen
                </Button>
                <Button variant="outline" as-child>
                    <Link href="/devices">Pair display</Link>
                </Button>
            </div>
        </section>

        <Dialog :open="formOpen" @update:open="handleFormOpenChange">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <form class="space-y-4" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>
                            {{ editingScreen ? 'Edit screen' : 'New screen' }}
                        </DialogTitle>
                        <DialogDescription>
                            Atur identitas dan resolusi target screen. Device
                            akan memakai resolusi ini untuk layout player.
                        </DialogDescription>
                    </DialogHeader>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Name
                        </label>
                        <Input v-model="form.name" placeholder="Lobby Utama" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Location
                        </label>
                        <Input
                            v-model="form.location"
                            placeholder="Gedung A · Lantai 1"
                        />
                        <InputError :message="form.errors.location" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Orientation
                        </label>
                        <select
                            v-model="form.orientation"
                            class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                        >
                            <option
                                v-for="orientation in orientations"
                                :key="orientation"
                                :value="orientation"
                            >
                                {{ orientationLabel(orientation) }}
                            </option>
                        </select>
                        <InputError :message="form.errors.orientation" />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Width (px)
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
                                Height (px)
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

                    <label
                        class="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm"
                    >
                        <span>
                            <span class="font-medium">Active</span>
                            <span class="block text-xs text-muted-foreground">
                                Screen aktif bisa dipakai schedule.
                            </span>
                        </span>
                        <input
                            v-model="form.is_active"
                            type="checkbox"
                            class="size-4 rounded border-input"
                        />
                    </label>

                    <DialogFooter class="gap-2 pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                <X class="size-4" /> Cancel
                            </Button>
                        </DialogClose>
                        <SubmitButton
                            :processing="form.processing"
                            :label="editingScreen ? 'Update' : 'Create'"
                            loading-label="Menyimpan..."
                        >
                            <Pencil v-if="editingScreen" class="size-4" />
                            <Plus v-else class="size-4" />
                        </SubmitButton>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
