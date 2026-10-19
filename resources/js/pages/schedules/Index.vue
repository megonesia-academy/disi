<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import EmptyState from '@/components/EmptyState.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Pencil, Plus, Save, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';

type ScreenOption = {
    id: string;
    name: string;
    location: string | null;
};

type PlaylistOption = {
    id: string;
    name: string;
};

type Schedule = {
    id: string;
    screen_id: string;
    playlist_id: string;
    starts_at: string | null;
    ends_at: string | null;
    days_of_week: number[] | null;
    start_time: string | null;
    end_time: string | null;
    priority: number;
    is_active: boolean;
    screen: ScreenOption;
    playlist: PlaylistOption;
};

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
    };
    schedules: Schedule[];
    screens: ScreenOption[];
    playlists: PlaylistOption[];
}>();

const dayOptions = [
    { value: 1, label: 'Mon' },
    { value: 2, label: 'Tue' },
    { value: 3, label: 'Wed' },
    { value: 4, label: 'Thu' },
    { value: 5, label: 'Fri' },
    { value: 6, label: 'Sat' },
    { value: 7, label: 'Sun' },
];

const editingId = ref<string | null>(null);
const deletingId = ref<string | null>(null);

const form = useForm({
    screen_id: '',
    playlist_id: '',
    starts_at: '',
    ends_at: '',
    days_of_week: [1, 2, 3, 4, 5, 6, 7] as number[],
    start_time: '00:00',
    end_time: '23:59',
    priority: 10,
    is_active: true,
});

const editingSchedule = computed(() => {
    return props.schedules.find((schedule) => schedule.id === editingId.value);
});

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.screen_id = '';
    form.playlist_id = '';
    form.starts_at = '';
    form.ends_at = '';
    form.days_of_week = [1, 2, 3, 4, 5, 6, 7];
    form.start_time = '00:00';
    form.end_time = '23:59';
    form.priority = 10;
    form.is_active = true;
}

function editSchedule(schedule: Schedule): void {
    editingId.value = schedule.id;
    form.clearErrors();
    form.screen_id = schedule.screen_id;
    form.playlist_id = schedule.playlist_id;
    form.starts_at = schedule.starts_at ?? '';
    form.ends_at = schedule.ends_at ?? '';
    form.days_of_week = schedule.days_of_week ?? [1, 2, 3, 4, 5, 6, 7];
    form.start_time = schedule.start_time?.slice(0, 5) ?? '00:00';
    form.end_time = schedule.end_time?.slice(0, 5) ?? '23:59';
    form.priority = schedule.priority;
    form.is_active = schedule.is_active;
}

function toggleDay(day: number): void {
    if (form.days_of_week.includes(day)) {
        form.days_of_week = form.days_of_week.filter((value) => value !== day);
        return;
    }

    form.days_of_week = [...form.days_of_week, day].sort();
}

function submit(): void {
    if (editingId.value) {
        form.put(`/schedules/${editingId.value}`, {
            preserveScroll: true,
            onSuccess: resetForm,
        });

        return;
    }

    form.post('/schedules', {
        preserveScroll: true,
        onSuccess: resetForm,
    });
}

function destroySchedule(schedule: Schedule): void {
    if (!window.confirm(`Delete schedule for "${schedule.screen.name}"?`)) {
        return;
    }

    deletingId.value = schedule.id;
    form.delete(`/schedules/${schedule.id}`, {
        preserveScroll: true,
        onSuccess: resetForm,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Schedules" />

    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto p-4">
        <section class="flex flex-wrap items-end justify-between gap-3">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Schedules</h1>
                <p class="text-sm text-muted-foreground">
                    Assign playlist ke screen untuk {{ tenant.name }}.
                </p>
            </div>
            <div class="flex gap-3 text-sm">
                <Link
                    href="/playlists"
                    class="font-medium text-primary underline-offset-4 hover:underline"
                >
                    Playlists
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
            class="grid gap-4 lg:grid-cols-[minmax(340px,460px)_minmax(0,1fr)]"
        >
            <form
                class="rounded-lg border border-sidebar-border/70 p-4 dark:border-sidebar-border"
                @submit.prevent="submit"
            >
                <div class="mb-4">
                    <h2 class="font-medium">
                        {{ editingSchedule ? 'Edit schedule' : 'New schedule' }}
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Priority terbesar dipilih lebih dulu oleh player.
                    </p>
                </div>

                <div class="space-y-4">
                    <div
                        v-if="screens.length === 0 || playlists.length === 0"
                        class="rounded-md border bg-muted/35 p-3 text-sm text-muted-foreground"
                    >
                        <div class="font-medium text-foreground">
                            Schedule butuh screen dan playlist.
                        </div>
                        <div class="mt-1">
                            Buat minimal satu screen dan satu playlist berisi
                            media sebelum mengaktifkan schedule.
                        </div>
                        <div class="mt-3 flex gap-2">
                            <Link
                                href="/screens"
                                class="rounded-md border bg-background px-3 py-2 text-xs font-medium"
                            >
                                Screens
                            </Link>
                            <Link
                                href="/playlists"
                                class="rounded-md border bg-background px-3 py-2 text-xs font-medium"
                            >
                                Playlists
                            </Link>
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Screen
                        </label>
                        <select
                            v-model="form.screen_id"
                            class="option:bg-background option:text-foreground h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">Select screen</option>
                            <option
                                v-for="screen in screens"
                                :key="screen.id"
                                :value="screen.id"
                            >
                                {{ screen.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.screen_id" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Playlist
                        </label>
                        <select
                            v-model="form.playlist_id"
                            class="option:bg-background option:text-foreground h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option value="">Select playlist</option>
                            <option
                                v-for="playlist in playlists"
                                :key="playlist.id"
                                :value="playlist.id"
                            >
                                {{ playlist.name }}
                            </option>
                        </select>
                        <InputError :message="form.errors.playlist_id" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Starts at
                            </label>
                            <Input
                                v-model="form.starts_at"
                                type="datetime-local"
                            />
                            <InputError :message="form.errors.starts_at" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Ends at
                            </label>
                            <Input
                                v-model="form.ends_at"
                                type="datetime-local"
                            />
                            <InputError :message="form.errors.ends_at" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Daily start
                            </label>
                            <Input v-model="form.start_time" type="time" />
                            <InputError :message="form.errors.start_time" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Daily end
                            </label>
                            <Input v-model="form.end_time" type="time" />
                            <InputError :message="form.errors.end_time" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-2 block text-sm font-medium">
                            Days
                        </label>
                        <div class="flex flex-wrap gap-2">
                            <button
                                v-for="day in dayOptions"
                                :key="day.value"
                                type="button"
                                class="rounded-md border px-3 py-1.5 text-sm"
                                :class="
                                    form.days_of_week.includes(day.value)
                                        ? 'bg-primary text-primary-foreground'
                                        : 'bg-background'
                                "
                                @click="toggleDay(day.value)"
                            >
                                {{ day.label }}
                            </button>
                        </div>
                        <InputError :message="form.errors.days_of_week" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Priority
                        </label>
                        <Input v-model="form.priority" type="number" min="0" />
                        <InputError :message="form.errors.priority" />
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
                        <SubmitButton
                            :processing="form.processing"
                            :label="editingSchedule ? 'Update' : 'Create'"
                            loading-label="Menyimpan..."
                        >
                            <Save v-if="editingSchedule" class="size-4" />
                            <Plus v-else class="size-4" />
                        </SubmitButton>
                        <Button
                            type="button"
                            variant="outline"
                            :disabled="form.processing"
                            @click="resetForm"
                        >
                            <X class="size-4" />
                            Cancel
                        </Button>
                    </div>
                </div>
            </form>

            <section class="rounded-lg border bg-background shadow-sm">
                <div class="border-b px-4 py-3">
                    <h2 class="font-medium">Schedule list</h2>
                </div>

                <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                    <article
                        v-for="schedule in schedules"
                        :key="schedule.id"
                        class="flex flex-col rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                    >
                        <div
                            class="flex items-start justify-between gap-3 border-b px-4 py-3"
                        >
                            <div class="min-w-0">
                                <h2 class="truncate font-semibold">
                                    {{ schedule.screen.name }}
                                </h2>
                                <p
                                    class="truncate text-xs text-muted-foreground"
                                >
                                    {{ schedule.playlist.name }}
                                </p>
                            </div>
                            <div
                                class="flex shrink-0 flex-wrap items-center justify-end gap-1.5"
                            >
                                <span
                                    class="inline-flex rounded-full px-2 py-1 text-xs font-medium"
                                    :class="
                                        schedule.is_active
                                            ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300'
                                            : 'bg-zinc-100 text-zinc-700 dark:bg-zinc-900 dark:text-zinc-300'
                                    "
                                >
                                    {{
                                        schedule.is_active
                                            ? 'active'
                                            : 'inactive'
                                    }}
                                </span>
                            </div>
                        </div>

                        <dl
                            class="grid grid-cols-2 gap-x-4 gap-y-2 px-4 py-3 text-xs"
                        >
                            <div class="col-span-2">
                                <dt class="text-muted-foreground">Window</dt>
                                <dd class="mt-0.5 font-medium">
                                    <span class="block">
                                        {{ schedule.starts_at ?? 'Always' }} -
                                        {{ schedule.ends_at ?? 'Open' }}
                                    </span>
                                    <span
                                        class="block font-normal text-muted-foreground"
                                    >
                                        {{ schedule.start_time ?? '00:00' }} -
                                        {{ schedule.end_time ?? '23:59' }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="text-muted-foreground">Priority</dt>
                                <dd class="mt-0.5 font-medium">
                                    {{ schedule.priority }}
                                </dd>
                            </div>
                        </dl>

                        <div
                            class="mt-auto flex flex-wrap gap-2 border-t bg-muted/20 px-4 py-3"
                        >
                            <Button
                                size="sm"
                                variant="outline"
                                @click="editSchedule(schedule)"
                            >
                                <Pencil class="size-4" />
                                Edit
                            </Button>
                            <SubmitButton
                                type="button"
                                size="sm"
                                variant="destructive"
                                title="Delete schedule"
                                aria-label="Delete schedule"
                                loading-label="Menghapus..."
                                :processing="deletingId === schedule.id"
                                @click="destroySchedule(schedule)"
                            >
                                <Trash2 class="size-4" />
                                Delete
                            </SubmitButton>
                        </div>
                    </article>
                </div>

                <div
                    v-if="schedules.length === 0"
                    class="border-t px-4 py-10 text-center text-sm text-muted-foreground"
                >
                    <EmptyState
                        title="Belum ada schedule aktif"
                        description="Schedule menghubungkan playlist ke screen. Tanpa schedule, player belum tahu konten mana yang harus tampil."
                        action-label="Pilih screen dan playlist"
                        action-href="/schedules"
                        secondary-label="Manage playlists"
                        secondary-href="/playlists"
                    />
                </div>
            </section>
        </section>
    </div>
</template>
