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
import { Head, Link, router, useForm } from '@inertiajs/vue3';
import {
    ArrowLeft,
    Code2,
    Copy,
    Pencil,
    Plus,
    Search,
    ShieldCheck,
    Tags,
    Trash2,
    X,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type SchemaField = {
    name: string;
    label?: string;
    type?: string;
    default?: unknown;
};

type PlatformTemplate = {
    id: string;
    name: string;
    slug: string;
    html: string;
    css: string | null;
    schema: SchemaField[];
    token_cost: number;
    is_active: boolean;
    sort_order: number;
    tenant_copies_count: number;
};

const props = defineProps<{
    templates: PlatformTemplate[];
}>();

const DEFAULT_HTML =
    '<section class="signage-card"><h1>{{ title }}</h1><p>{{ subtitle }}</p></section>';
const DEFAULT_CSS =
    '.signage-card { padding: 48px; color: white; font-family: sans-serif; background: #0f172a; min-height: 100vh; } h1 { font-size: 56px; } p { font-size: 28px; }';
const DEFAULT_SCHEMA: SchemaField[] = [
    { name: 'title', label: 'Title', type: 'text', default: 'Welcome' },
    {
        name: 'subtitle',
        label: 'Subtitle',
        type: 'text',
        default: 'Information',
    },
];

const formOpen = ref(false);
const editingId = ref<string | null>(null);
const deletingId = ref<string | null>(null);
const duplicatingId = ref<string | null>(null);
const search = ref('');
const priceFilter = ref<'all' | 'free' | 'paid'>('all');
const slugTouched = ref(false);

const form = useForm({
    name: '',
    slug: '',
    html: DEFAULT_HTML,
    css: DEFAULT_CSS,
    schema_json: JSON.stringify(DEFAULT_SCHEMA, null, 2),
    token_cost: 0,
    is_active: true,
});

const editingTemplate = computed(() =>
    props.templates.find((template) => template.id === editingId.value),
);

const visibleTemplates = computed(() => {
    const term = search.value.trim().toLowerCase();

    return [...props.templates]
        .filter((template) => {
            const matchesTerm =
                term === '' ||
                template.name.toLowerCase().includes(term) ||
                template.slug.toLowerCase().includes(term);
            const matchesPrice =
                priceFilter.value === 'all' ||
                (priceFilter.value === 'free'
                    ? template.token_cost === 0
                    : template.token_cost > 0);

            return matchesTerm && matchesPrice;
        })
        .sort((a, b) => a.name.localeCompare(b.name));
});

const previewSource = computed(() => {
    try {
        const parsed = JSON.parse(form.schema_json);
        const schema = Array.isArray(parsed) ? parsed : [];

        return buildPreview(form.html, form.css, schema);
    } catch {
        return buildPreview(form.html, form.css, []);
    }
});

function slugify(value: string): string {
    return value
        .toLowerCase()
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/^-+|-+$/g, '');
}

function buildPreview(
    html: string,
    css: string,
    schema: SchemaField[],
): string {
    const values: Record<string, unknown> = {};

    for (const field of schema) {
        if (field.name) {
            values[field.name] = field.default ?? field.label ?? field.name;
        }
    }

    const replaceFields = (
        markup: string,
        source: Record<string, unknown>,
    ): string =>
        markup.replace(
            /{{\s*(?:this\.)?([\w.-]+)\s*}}/g,
            (_match, key: string) => String(source[key] ?? ''),
        );

    const repeated = html.replace(
        /{{#each\s+([\w.-]+)}}([\s\S]*?){{\/each}}/g,
        (_match, key: string, block: string) => {
            const rows = values[key];

            return Array.isArray(rows)
                ? rows
                      .filter(
                          (row): row is Record<string, unknown> =>
                              Boolean(row) && typeof row === 'object',
                      )
                      .map((row) => replaceFields(block, row))
                      .join('')
                : '';
        },
    );

    const body = repeated.replace(
        /{{\s*([\w.-]+)\s*}}/g,
        (_match, key: string) => String(values[key] ?? ''),
    );

    return `<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><style>html,body{margin:0;min-height:100%;}${css}</style></head><body>${body}</body></html>`;
}

function templatePreview(template: PlatformTemplate): string {
    return buildPreview(template.html, template.css ?? '', template.schema);
}

function resetForm(): void {
    editingId.value = null;
    slugTouched.value = false;
    form.reset();
    form.clearErrors();
    form.html = DEFAULT_HTML;
    form.css = DEFAULT_CSS;
    form.schema_json = JSON.stringify(DEFAULT_SCHEMA, null, 2);
    form.token_cost = 0;
    form.is_active = true;
}

function openCreate(): void {
    resetForm();
    formOpen.value = true;
}

function openEdit(template: PlatformTemplate): void {
    form.clearErrors();
    editingId.value = template.id;
    slugTouched.value = true;
    form.name = template.name;
    form.slug = template.slug;
    form.html = template.html;
    form.css = template.css ?? '';
    form.schema_json = JSON.stringify(template.schema ?? [], null, 2);
    form.token_cost = template.token_cost;
    form.is_active = template.is_active;
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
        form.put(`/admin/templates/${editingId.value}`, options);

        return;
    }

    form.post('/admin/templates', options);
}

function destroyTemplate(template: PlatformTemplate): void {
    if (
        !window.confirm(
            `Hapus template "${template.name}" dari katalog platform?`,
        )
    ) {
        return;
    }

    deletingId.value = template.id;
    form.delete(`/admin/templates/${template.id}`, {
        preserveScroll: true,
        onFinish: () => {
            deletingId.value = null;
        },
    });
}

function duplicateTemplate(template: PlatformTemplate): void {
    duplicatingId.value = template.id;
    router.post(
        `/admin/templates/${template.id}/duplicate`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                duplicatingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Platform Templates" />

    <div
        class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex items-center gap-1.5 rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                >
                    <ShieldCheck class="size-3.5" />
                    Application admin
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Platform templates
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Kelola katalog template global. Template otomatis
                    disinkronkan ke semua tenant; tandai gratis atau berbayar
                    dengan token.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin">
                        <ArrowLeft class="size-4" /> Admin dashboard
                    </Link>
                </Button>
                <Button type="button" @click="openCreate">
                    <Plus class="size-4" /> New template
                </Button>
            </div>
        </section>

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Tags class="size-4" /> Template
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ templates.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Template gratis</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{
                        templates.filter(
                            (template) => template.token_cost === 0,
                        ).length
                    }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    Template berbayar
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{
                        templates.filter((template) => template.token_cost > 0)
                            .length
                    }}
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
                    placeholder="Cari nama atau slug template..."
                />
            </div>
            <select
                v-model="priceFilter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua harga</option>
                <option value="free">Gratis</option>
                <option value="paid">Berbayar</option>
            </select>
            <span class="ml-auto text-xs text-muted-foreground">
                {{ visibleTemplates.length }} dari
                {{ templates.length }} template
            </span>
        </section>

        <section
            v-if="visibleTemplates.length"
            class="grid gap-4 sm:grid-cols-2 xl:grid-cols-3"
        >
            <article
                v-for="template in visibleTemplates"
                :key="template.id"
                class="flex flex-col overflow-hidden rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
            >
                <div class="relative aspect-video overflow-hidden bg-zinc-950">
                    <iframe
                        :srcdoc="templatePreview(template)"
                        class="pointer-events-none absolute top-0 left-0 h-[400%] w-[400%] origin-top-left scale-[0.25] border-0"
                        sandbox=""
                        loading="lazy"
                        tabindex="-1"
                        aria-hidden="true"
                    />
                    <span class="absolute top-2 left-2 flex gap-1">
                        <span
                            class="rounded-md px-2 py-0.5 text-[11px] font-semibold"
                            :class="
                                template.token_cost > 0
                                    ? 'bg-amber-400 text-amber-950'
                                    : 'bg-emerald-500 text-white'
                            "
                        >
                            {{
                                template.token_cost > 0
                                    ? `${template.token_cost} token`
                                    : 'Free'
                            }}
                        </span>
                        <span
                            v-if="!template.is_active"
                            class="rounded-md bg-zinc-600 px-2 py-0.5 text-[11px] font-semibold text-white"
                        >
                            Inactive
                        </span>
                    </span>
                </div>

                <div class="flex flex-1 flex-col p-4">
                    <h2 class="truncate font-semibold" :title="template.name">
                        {{ template.name }}
                    </h2>
                    <p class="mt-0.5 truncate text-xs text-muted-foreground">
                        <code>{{ template.slug }}</code> ·
                        {{ template.tenant_copies_count }} tenant copy
                    </p>
                    <p class="mt-2 text-[11px] text-muted-foreground">
                        {{ template.schema.length }} field schema
                    </p>

                    <div class="mt-auto flex items-center gap-1 border-t pt-3">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="openEdit(template)"
                        >
                            <Pencil class="size-4" /> Edit
                        </Button>
                        <SubmitButton
                            type="button"
                            size="sm"
                            variant="ghost"
                            title="Duplicate template"
                            loading-label="Menduplikasi..."
                            :processing="duplicatingId === template.id"
                            @click="duplicateTemplate(template)"
                        >
                            <Copy class="size-4" />
                        </SubmitButton>
                        <SubmitButton
                            type="button"
                            size="sm"
                            variant="ghost"
                            class="ml-auto text-destructive hover:bg-destructive/10 hover:text-destructive"
                            title="Hapus template"
                            aria-label="Hapus template"
                            loading-label="Menghapus..."
                            :processing="deletingId === template.id"
                            @click="destroyTemplate(template)"
                        >
                            <Trash2 class="size-4" />
                        </SubmitButton>
                    </div>
                </div>
            </article>
        </section>

        <section
            v-else
            class="rounded-lg border border-dashed bg-background p-10 text-center"
        >
            <div
                class="mx-auto grid size-12 place-items-center rounded-full bg-muted"
            >
                <Code2 class="size-6 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">
                {{
                    templates.length
                        ? 'Tidak ada template yang cocok'
                        : 'Belum ada template platform'
                }}
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                {{
                    templates.length
                        ? 'Ubah kata kunci atau filter harga.'
                        : 'Buat template pertama; otomatis tersedia untuk semua tenant.'
                }}
            </p>
            <div class="mt-5 flex justify-center">
                <Button type="button" @click="openCreate">
                    <Plus class="size-4" /> New template
                </Button>
            </div>
        </section>

        <Dialog :open="formOpen" @update:open="handleFormOpenChange">
            <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-5xl">
                <form class="space-y-4" @submit.prevent="submit">
                    <DialogHeader>
                        <DialogTitle>
                            {{
                                editingTemplate
                                    ? 'Edit template'
                                    : 'New template'
                            }}
                        </DialogTitle>
                        <DialogDescription>
                            Template global untuk semua tenant. Perubahan
                            disinkronkan setelah disimpan.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-4 lg:grid-cols-2">
                        <div class="space-y-4">
                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium"
                                        >Name</label
                                    >
                                    <Input
                                        v-model="form.name"
                                        @input="
                                            !slugTouched &&
                                            (form.slug = slugify(form.name))
                                        "
                                    />
                                    <InputError :message="form.errors.name" />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium"
                                        >Slug</label
                                    >
                                    <Input
                                        v-model="form.slug"
                                        class="font-mono text-sm"
                                        @input="slugTouched = true"
                                    />
                                    <InputError :message="form.errors.slug" />
                                </div>
                            </div>

                            <div class="grid gap-3 sm:grid-cols-2">
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium"
                                    >
                                        Harga token (0 = gratis)
                                    </label>
                                    <Input
                                        v-model="form.token_cost"
                                        type="number"
                                        min="0"
                                    />
                                    <InputError
                                        :message="form.errors.token_cost"
                                    />
                                </div>
                                <label
                                    class="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm"
                                >
                                    <span class="font-medium">Active</span>
                                    <input
                                        v-model="form.is_active"
                                        type="checkbox"
                                        class="size-4 rounded border-input"
                                    />
                                </label>
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >HTML</label
                                >
                                <textarea
                                    v-model="form.html"
                                    class="min-h-40 w-full scrollbar-thin rounded-md border bg-background px-3 py-2 font-mono text-xs"
                                    spellcheck="false"
                                />
                                <InputError :message="form.errors.html" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium"
                                    >CSS</label
                                >
                                <textarea
                                    v-model="form.css"
                                    class="min-h-32 w-full scrollbar-thin rounded-md border bg-background px-3 py-2 font-mono text-xs"
                                    spellcheck="false"
                                />
                                <InputError :message="form.errors.css" />
                            </div>

                            <div>
                                <label class="mb-1 block text-sm font-medium">
                                    Data schema JSON
                                </label>
                                <textarea
                                    v-model="form.schema_json"
                                    class="min-h-32 w-full scrollbar-thin rounded-md border bg-background px-3 py-2 font-mono text-xs"
                                    spellcheck="false"
                                />
                                <InputError
                                    :message="form.errors.schema_json"
                                />
                            </div>
                        </div>

                        <div class="space-y-2">
                            <p class="text-sm font-medium">Live preview</p>
                            <div
                                class="relative overflow-hidden rounded-lg border bg-zinc-950"
                                style="aspect-ratio: 16 / 9"
                            >
                                <iframe
                                    :srcdoc="previewSource"
                                    class="pointer-events-none absolute top-0 left-0 h-[400%] w-[400%] origin-top-left scale-[0.25] border-0"
                                    sandbox=""
                                    title="Template preview"
                                />
                            </div>
                            <p class="text-[11px] text-muted-foreground">
                                Preview memakai data contoh dari schema.
                            </p>
                        </div>
                    </div>

                    <DialogFooter class="gap-2 pt-2">
                        <DialogClose as-child>
                            <Button type="button" variant="outline">
                                <X class="size-4" /> Cancel
                            </Button>
                        </DialogClose>
                        <SubmitButton
                            :processing="form.processing"
                            :label="
                                editingTemplate
                                    ? 'Save template'
                                    : 'Create template'
                            "
                            loading-label="Menyimpan..."
                        >
                            <Code2 class="size-4" />
                        </SubmitButton>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
