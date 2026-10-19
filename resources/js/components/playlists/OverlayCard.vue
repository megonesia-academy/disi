<script setup lang="ts">
import { ChevronDown, ChevronUp, Copy, Trash2 } from '@lucide/vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    API_OVERLAY_PRESETS,
    OVERLAY_POSITIONS,
    OVERLAY_THEMES,
    overlayTypeLabel,
    type ApiConnectionOption,
    type MediaAssetOption,
    type OverlayEntry,
    type OverlayField,
    type OverlaySchedule,
} from '@/lib/overlays';

const props = defineProps<{
    overlay: OverlayEntry;
    apiConnections: ApiConnectionOption[];
    mediaAssets: MediaAssetOption[];
    index: number;
    total: number;
}>();

defineEmits<{
    remove: [];
    duplicate: [];
    move: [direction: 'up' | 'down'];
}>();

const DAYS: Array<{ value: number; label: string }> = [
    { value: 1, label: 'Sen' },
    { value: 2, label: 'Sel' },
    { value: 3, label: 'Rab' },
    { value: 4, label: 'Kam' },
    { value: 5, label: 'Jum' },
    { value: 6, label: 'Sab' },
    { value: 7, label: 'Min' },
];

const imageAssets = (): MediaAssetOption[] =>
    props.mediaAssets.filter(
        (asset) => asset.type === 'image' || asset.type === 'image_url',
    );

function schedule(): OverlaySchedule {
    const existing = props.overlay.config.schedule;

    if (existing) {
        return existing;
    }

    const created: OverlaySchedule = { days: [] };
    props.overlay.config.schedule = created;

    return created;
}

function toggleDay(day: number): void {
    const current = schedule();
    const days = current.days ?? [];

    current.days = days.includes(day)
        ? days.filter((value) => value !== day)
        : [...days, day].sort((a, b) => a - b);
}

function fields(): OverlayField[] {
    if (!props.overlay.config.fields) {
        props.overlay.config.fields = [];
    }

    return props.overlay.config.fields;
}

function addField(): void {
    const list = fields();

    if (list.length >= 8) {
        return;
    }

    list.push({ label: 'Nilai', path: '', prefix: '', suffix: '' });
}

function removeField(index: number): void {
    fields().splice(index, 1);
}

function applyPreset(key: string): void {
    const preset = API_OVERLAY_PRESETS.find((item) => item.key === key);

    if (!preset) {
        return;
    }

    props.overlay.config.endpoint = preset.endpoint;
    props.overlay.config.fields = preset.fields.map((field) => ({ ...field }));

    if (!props.overlay.config.title) {
        props.overlay.config.title = preset.label;
    }
}
</script>

<template>
    <div class="mt-4 rounded-md border bg-muted/20 p-3">
        <div class="flex items-center justify-between gap-2">
            <div class="flex min-w-0 items-center gap-2">
                <span class="truncate text-sm font-medium">{{
                    overlayTypeLabel(overlay.type)
                }}</span>
                <label
                    class="flex items-center gap-1 text-[11px] text-muted-foreground"
                >
                    <input
                        v-model="overlay.is_active"
                        type="checkbox"
                        class="size-3.5 rounded border-input"
                    />
                    Aktif
                </label>
            </div>
            <div class="flex items-center gap-1">
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    title="Move up"
                    :disabled="index === 0"
                    @click="$emit('move', 'up')"
                    ><ChevronUp class="size-4"
                /></Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    title="Move down"
                    :disabled="index === total - 1"
                    @click="$emit('move', 'down')"
                    ><ChevronDown class="size-4"
                /></Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    title="Duplicate overlay"
                    @click="$emit('duplicate')"
                    ><Copy class="size-4"
                /></Button>
                <Button
                    type="button"
                    size="icon"
                    variant="ghost"
                    title="Remove overlay"
                    @click="$emit('remove')"
                    ><Trash2 class="size-4"
                /></Button>
            </div>
        </div>

        <!-- Ticker -->
        <div v-if="overlay.type === 'ticker'" class="mt-3 space-y-3">
            <Input
                v-if="overlay.config.source !== 'array'"
                v-model="overlay.config.text"
                placeholder="Teks berjalan"
            />
            <select
                v-model="overlay.config.source"
                class="h-9 w-full rounded-md border bg-background px-3 text-sm"
            >
                <option value="static">Static text</option>
                <option value="array">Text array</option>
            </select>
            <textarea
                v-if="overlay.config.source === 'array'"
                :value="(overlay.config.items ?? []).join('\n')"
                class="min-h-20 w-full rounded-md border bg-background px-3 py-2 text-sm"
                placeholder="Satu pesan per baris"
                @input="
                    overlay.config.items = (
                        $event.target as HTMLTextAreaElement
                    ).value
                        .split('\n')
                        .filter(Boolean)
                "
            />
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium">Speed</label>
                    <Input
                        v-model="overlay.config.speed"
                        type="number"
                        min="10"
                        max="200"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Warna</label>
                    <div class="flex gap-2">
                        <input
                            v-model="overlay.config.text_color"
                            type="color"
                            class="h-9 w-1/2 rounded-md border bg-background"
                        />
                        <input
                            v-model="overlay.config.background_color"
                            type="color"
                            class="h-9 w-1/2 rounded-md border bg-background"
                        />
                    </div>
                </div>
            </div>
        </div>

        <!-- Clock -->
        <div v-else-if="overlay.type === 'clock'" class="mt-3">
            <label class="mb-1 block text-xs font-medium">Clock format</label>
            <select
                v-model="overlay.config.format"
                class="h-9 w-full rounded-md border bg-background px-3 text-sm"
            >
                <option value="24h">24 hour</option>
                <option value="12h">12 hour (AM/PM)</option>
            </select>
        </div>

        <!-- Date -->
        <div
            v-else-if="overlay.type === 'date'"
            class="mt-3 grid gap-3 sm:grid-cols-2"
        >
            <div>
                <label class="mb-1 block text-xs font-medium">Format</label>
                <select
                    v-model="overlay.config.format"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option value="long">Lengkap</option>
                    <option value="short">Singkat</option>
                    <option value="iso">ISO (2026-10-15)</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Bahasa</label>
                <select
                    v-model="overlay.config.locale"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option value="id">Indonesia</option>
                    <option value="en">English</option>
                </select>
            </div>
        </div>

        <!-- Location -->
        <div v-else-if="overlay.type === 'location'" class="mt-3">
            <label class="mb-1 block text-xs font-medium">Label</label>
            <Input
                v-model="overlay.config.label"
                placeholder="Contoh: Lobby Utama"
            />
            <p class="mt-1 text-[11px] text-muted-foreground">
                Lokasi diambil dari data screen.
            </p>
        </div>

        <!-- Greeting -->
        <div v-else-if="overlay.type === 'greeting'" class="mt-3 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-medium">Template</label>
                <Input v-model="overlay.config.template" />
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Placeholder: {greeting}, {tenant}, {screen}, {date}, {time}.
                </p>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >Sumber nama</label
                >
                <select
                    v-model="overlay.config.name"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option value="tenant">Nama workspace</option>
                    <option value="screen">Nama screen</option>
                </select>
            </div>
        </div>

        <!-- Next up -->
        <div
            v-else-if="overlay.type === 'next'"
            class="mt-3 grid gap-3 sm:grid-cols-2"
        >
            <div>
                <label class="mb-1 block text-xs font-medium">Tampilkan</label>
                <select
                    v-model="overlay.config.next_mode"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option value="next">Item berikutnya</option>
                    <option value="now">Item sekarang</option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Label</label>
                <Input
                    v-model="overlay.config.label"
                    placeholder="Berikutnya"
                />
            </div>
        </div>

        <!-- Badge -->
        <div v-else-if="overlay.type === 'badge'" class="mt-3 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-medium">Gambar</label>
                <select
                    v-model="overlay.config.media_asset_id"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option value="">Pilih gambar</option>
                    <option
                        v-for="asset in imageAssets()"
                        :key="asset.id"
                        :value="asset.id"
                    >
                        {{ asset.name }}
                    </option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Lebar (px)</label>
                <Input
                    v-model="overlay.config.size"
                    type="number"
                    min="16"
                    max="600"
                />
            </div>
        </div>

        <!-- Countdown -->
        <div v-else-if="overlay.type === 'countdown'" class="mt-3 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >Target waktu</label
                >
                <Input
                    v-model="overlay.config.target_at"
                    type="datetime-local"
                />
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Memakai waktu lokal display.
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium">Prefix</label>
                    <Input v-model="overlay.config.prefix" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Suffix</label>
                    <Input v-model="overlay.config.suffix" />
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >Teks setelah selesai</label
                >
                <Input v-model="overlay.config.finished_text" />
            </div>
        </div>

        <!-- Open status -->
        <div v-else-if="overlay.type === 'hours'" class="mt-3 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-medium">Judul</label>
                <Input
                    v-model="overlay.config.title"
                    placeholder="Jam operasional"
                />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium"
                        >Label buka</label
                    >
                    <Input v-model="overlay.config.open_label" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium"
                        >Label tutup</label
                    >
                    <Input v-model="overlay.config.closed_label" />
                </div>
            </div>
            <label class="flex items-center gap-2 text-sm">
                <input
                    v-model="overlay.config.show_times"
                    type="checkbox"
                    class="size-4 rounded border-input"
                />
                Tampilkan jam
            </label>
        </div>

        <!-- Title card -->
        <div v-else-if="overlay.type === 'title'" class="mt-3 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-medium">Judul</label>
                <Input v-model="overlay.config.title" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Subjudul</label>
                <Input v-model="overlay.config.subtitle" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Perataan</label>
                <select
                    v-model="overlay.config.align"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option value="left">Kiri</option>
                    <option value="center">Tengah</option>
                    <option value="right">Kanan</option>
                </select>
            </div>
        </div>

        <!-- Progress -->
        <div
            v-else-if="overlay.type === 'progress'"
            class="mt-3 grid gap-3 sm:grid-cols-3"
        >
            <div>
                <label class="mb-1 block text-xs font-medium">Warna</label>
                <input
                    v-model="overlay.config.color"
                    type="color"
                    class="h-9 w-full rounded-md border bg-background"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Track</label>
                <input
                    v-model="overlay.config.track_color"
                    type="color"
                    class="h-9 w-full rounded-md border bg-background"
                />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >Tinggi (px)</label
                >
                <Input
                    v-model="overlay.config.height"
                    type="number"
                    min="2"
                    max="40"
                />
            </div>
        </div>

        <!-- API data -->
        <div v-else-if="overlay.type === 'api'" class="mt-3 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >API connection</label
                >
                <select
                    v-model="overlay.config.api_connection_id"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option value="">Pilih API connection</option>
                    <option
                        v-for="connection in apiConnections"
                        :key="connection.id"
                        :value="connection.id"
                    >
                        {{ connection.name }} · {{ connection.provider }}
                    </option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >Endpoint relatif</label
                >
                <Input
                    v-model="overlay.config.endpoint"
                    placeholder="/v1/air-quality?..."
                />
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium">Judul</label>
                    <Input v-model="overlay.config.title" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Layout</label>
                    <select
                        v-model="overlay.config.layout"
                        class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="list">List</option>
                        <option value="ticker">Ticker</option>
                    </select>
                </div>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium">Preset</label>
                <div class="flex flex-wrap gap-2">
                    <Button
                        v-for="preset in API_OVERLAY_PRESETS"
                        :key="preset.key"
                        type="button"
                        size="sm"
                        variant="outline"
                        @click="applyPreset(preset.key)"
                        >{{ preset.label }}</Button
                    >
                </div>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Pastikan connection memakai provider yang sesuai.
                </p>
            </div>
            <div class="space-y-2">
                <div class="flex items-center justify-between">
                    <label class="text-xs font-medium">Field</label>
                    <Button
                        type="button"
                        size="sm"
                        variant="outline"
                        :disabled="fields().length >= 8"
                        @click="addField"
                        >Tambah field</Button
                    >
                </div>
                <div
                    v-for="(field, fieldIndex) in overlay.config.fields ?? []"
                    :key="`field-${fieldIndex}`"
                    class="space-y-2 rounded-md border bg-background p-2"
                >
                    <div class="flex items-center gap-2">
                        <Input
                            v-model="field.label"
                            placeholder="Label"
                            class="flex-1"
                        />
                        <Button
                            type="button"
                            size="icon"
                            variant="ghost"
                            title="Remove field"
                            @click="removeField(fieldIndex)"
                            ><Trash2 class="size-4"
                        /></Button>
                    </div>
                    <Input
                        v-model="field.path"
                        placeholder="current.temperature_2m"
                    />
                    <div class="grid grid-cols-2 gap-2">
                        <Input v-model="field.prefix" placeholder="Prefix" />
                        <Input v-model="field.suffix" placeholder="Suffix" />
                    </div>
                </div>
            </div>
        </div>

        <!-- RSS -->
        <div v-else-if="overlay.type === 'rss'" class="mt-3 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >API connection</label
                >
                <select
                    v-model="overlay.config.api_connection_id"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option value="">Pilih API connection</option>
                    <option
                        v-for="connection in apiConnections"
                        :key="connection.id"
                        :value="connection.id"
                    >
                        {{ connection.name }} · {{ connection.provider }}
                    </option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >Endpoint feed</label
                >
                <Input
                    v-model="overlay.config.endpoint"
                    placeholder="/rss/berita.xml"
                />
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Feed XML (RSS/Atom) atau JSON Feed.
                </p>
            </div>
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium">Judul</label>
                    <Input v-model="overlay.config.title" />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium">Layout</label>
                    <select
                        v-model="overlay.config.layout"
                        class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option value="ticker">Ticker</option>
                        <option value="list">List</option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium"
                        >Maks item</label
                    >
                    <Input
                        v-model="overlay.config.item_limit"
                        type="number"
                        min="1"
                        max="20"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium"
                        >Durasi per item (detik)</label
                    >
                    <Input
                        v-model="overlay.config.item_duration_seconds"
                        type="number"
                        min="2"
                        max="120"
                    />
                </div>
            </div>
        </div>

        <!-- Weather -->
        <div v-else-if="overlay.type === 'weather'" class="mt-3 space-y-3">
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >API connection</label
                >
                <select
                    v-model="overlay.config.api_connection_id"
                    class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                >
                    <option value="">Pilih API connection</option>
                    <option
                        v-for="connection in apiConnections"
                        :key="connection.id"
                        :value="connection.id"
                    >
                        {{ connection.name }} · {{ connection.provider }}
                    </option>
                </select>
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >Endpoint relatif</label
                >
                <Input v-model="overlay.config.endpoint" />
            </div>
            <div>
                <label class="mb-1 block text-xs font-medium"
                    >Refresh (detik)</label
                >
                <Input
                    v-model="overlay.config.refresh_seconds"
                    type="number"
                    min="10"
                    max="86400"
                />
            </div>
        </div>

        <!-- Common settings -->
        <div class="mt-3 space-y-3 border-t pt-3">
            <div class="grid gap-3 sm:grid-cols-2">
                <div>
                    <label class="mb-1 block text-xs font-medium"
                        >Position</label
                    >
                    <select
                        v-model="overlay.config.position"
                        class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option
                            v-for="position in OVERLAY_POSITIONS"
                            :key="position.value"
                            :value="position.value"
                        >
                            {{ position.label }}
                        </option>
                    </select>
                </div>
                <div v-if="overlay.type !== 'ticker'">
                    <label class="mb-1 block text-xs font-medium">Tema</label>
                    <select
                        v-model="overlay.config.theme"
                        class="h-9 w-full rounded-md border bg-background px-3 text-sm"
                    >
                        <option
                            v-for="themeOption in OVERLAY_THEMES"
                            :key="themeOption.value"
                            :value="themeOption.value"
                        >
                            {{ themeOption.label }}
                        </option>
                    </select>
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium"
                        >Offset X (px)</label
                    >
                    <Input
                        v-model="overlay.config.offset_x"
                        type="number"
                        min="-500"
                        max="500"
                    />
                </div>
                <div>
                    <label class="mb-1 block text-xs font-medium"
                        >Offset Y (px)</label
                    >
                    <Input
                        v-model="overlay.config.offset_y"
                        type="number"
                        min="-500"
                        max="500"
                    />
                </div>
            </div>

            <div>
                <label class="mb-1 block text-xs font-medium"
                    >Jadwal tampil (opsional)</label
                >
                <div class="flex flex-wrap gap-1.5">
                    <button
                        v-for="day in DAYS"
                        :key="day.value"
                        type="button"
                        class="rounded-md border px-2 py-1 text-[11px] font-medium"
                        :class="
                            (overlay.config.schedule?.days ?? []).includes(
                                day.value,
                            )
                                ? 'border-cyan-600 bg-cyan-50 text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300'
                                : 'bg-background text-muted-foreground'
                        "
                        @click="toggleDay(day.value)"
                    >
                        {{ day.label }}
                    </button>
                </div>
                <div class="mt-2 grid gap-3 sm:grid-cols-2">
                    <div>
                        <label class="mb-1 block text-xs font-medium"
                            >Mulai</label
                        >
                        <Input v-model="schedule().start" type="time" />
                    </div>
                    <div>
                        <label class="mb-1 block text-xs font-medium"
                            >Selesai</label
                        >
                        <Input v-model="schedule().end" type="time" />
                    </div>
                </div>
                <p class="mt-1 text-[11px] text-muted-foreground">
                    Kosongkan untuk selalu tampil.
                </p>
            </div>
        </div>
    </div>
</template>
