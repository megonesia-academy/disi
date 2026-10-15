<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import { Code2, Eye, Pencil, Plus, Trash2, X } from '@lucide/vue';

type ContentTemplate = {
    id: string;
    name: string;
    slug: string;
    html: string;
    css: string | null;
    schema: Array<Record<string, unknown>>;
    is_active: boolean;
    playlist_items_count: number;
};

const props = defineProps<{ tenant: { id: string; name: string }; templates: ContentTemplate[] }>();
const editingId = ref<string | null>(null);
const showPreview = ref(false);

const form = useForm({
    name: '',
    slug: '',
    html: '<section class="signage-card"><h1>{{ title }}</h1><p>{{ subtitle }}</p></section>',
    css: '.signage-card { padding: 48px; color: white; font-family: sans-serif; background: #0f172a; min-height: 100vh; } h1 { font-size: 56px; } p { font-size: 28px; }',
    schema_json: '[{"name":"title","label":"Title","type":"text","default":"Welcome"},{"name":"subtitle","label":"Subtitle","type":"text","default":"Information"}]',
    is_active: true,
});

const editingTemplate = computed(() => props.templates.find((template) => template.id === editingId.value));
const previewDocument = computed(() => `<!doctype html><html><head><style>${form.css}</style></head><body>${form.html.replaceAll('{{ title }}', 'Preview title').replaceAll('{{ subtitle }}', 'Preview subtitle')}</body></html>`);

function resetForm(): void {
    editingId.value = null;
    form.reset();
    form.clearErrors();
    form.html = '<section class="signage-card"><h1>{{ title }}</h1><p>{{ subtitle }}</p></section>';
    form.css = '.signage-card { padding: 48px; color: white; font-family: sans-serif; background: #0f172a; min-height: 100vh; } h1 { font-size: 56px; } p { font-size: 28px; }';
    form.schema_json = '[{"name":"title","label":"Title","type":"text","default":"Welcome"},{"name":"subtitle","label":"Subtitle","type":"text","default":"Information"}]';
    form.is_active = true;
}

function editTemplate(template: ContentTemplate): void {
    editingId.value = template.id;
    form.clearErrors();
    form.name = template.name;
    form.slug = template.slug;
    form.html = template.html;
    form.css = template.css ?? '';
    form.schema_json = JSON.stringify(template.schema ?? [], null, 2);
    form.is_active = template.is_active;
}

function submit(): void {
    const options = { preserveScroll: true, onSuccess: resetForm };
    if (editingId.value) {
        form.put(`/templates/${editingId.value}`, options);
    } else {
        form.post('/templates', options);
    }
}

function destroyTemplate(template: ContentTemplate): void {
    if (template.playlist_items_count > 0 || !window.confirm(`Delete template "${template.name}"?`)) return;
    form.delete(`/templates/${template.id}`, { preserveScroll: true, onSuccess: resetForm });
}
</script>

<template>
    <Head title="Template Library" />
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-muted/20 p-4 md:p-6">
        <section class="flex flex-wrap items-end justify-between gap-4">
            <div>
                <p class="text-sm text-muted-foreground">{{ tenant.name }}</p>
                <h1 class="mt-1 text-2xl font-semibold tracking-tight">Template Library</h1>
                <p class="mt-1 text-sm text-muted-foreground">Buat layout HTML yang bisa dipakai ulang dengan data berbeda di setiap playlist item.</p>
            </div>
            <div class="flex items-center gap-2">
                <Link href="/playlists" class="text-sm font-medium text-primary hover:underline">Playlists</Link>
                <Button type="button" @click="resetForm"><Plus class="size-4" /> New template</Button>
            </div>
        </section>

        <section class="grid gap-6 xl:grid-cols-[minmax(320px,420px)_minmax(0,1fr)]">
            <form class="space-y-4 rounded-lg border bg-background p-5 shadow-sm" @submit.prevent="submit">
                <div class="flex items-start justify-between gap-3">
                    <div><h2 class="font-semibold">{{ editingTemplate ? 'Edit template' : 'New template' }}</h2><p class="mt-1 text-xs text-muted-foreground">Gunakan placeholder seperti <code v-pre>{{ title }}</code>.</p></div>
                    <Button v-if="editingTemplate" type="button" size="icon" variant="ghost" title="Cancel edit" @click="resetForm"><X class="size-4" /></Button>
                </div>
                <div><label class="mb-1 block text-sm font-medium">Name</label><Input v-model="form.name" placeholder="Patient queue board" /><InputError :message="form.errors.name" /></div>
                <div><label class="mb-1 block text-sm font-medium">Slug</label><Input v-model="form.slug" placeholder="patient-queue-board" /><InputError :message="form.errors.slug" /></div>
                <div><label class="mb-1 block text-sm font-medium">HTML</label><textarea v-model="form.html" class="min-h-48 w-full rounded-md border bg-background px-3 py-2 font-mono text-xs" spellcheck="false" /><InputError :message="form.errors.html" /></div>
                <div><label class="mb-1 block text-sm font-medium">CSS</label><textarea v-model="form.css" class="min-h-40 w-full rounded-md border bg-background px-3 py-2 font-mono text-xs" spellcheck="false" /><InputError :message="form.errors.css" /></div>
                <div><label class="mb-1 block text-sm font-medium">Data schema JSON</label><textarea v-model="form.schema_json" class="min-h-32 w-full rounded-md border bg-background px-3 py-2 font-mono text-xs" spellcheck="false" placeholder='[{"name":"title","label":"Title","type":"text"}]' /><p class="mt-1 text-xs text-muted-foreground">Tipe yang didukung: <code>text</code>, <code>number</code>, <code>textarea</code>, <code>image</code>. Field image akan memilih media dari playlist item.</p><InputError :message="form.errors.schema_json" /></div>
                <label class="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm"><span><span class="font-medium">Active</span><span class="block text-xs text-muted-foreground">Tersedia di playlist editor.</span></span><input v-model="form.is_active" type="checkbox" class="size-4 rounded border-input" /></label>
                <Button type="submit" class="w-full" :disabled="form.processing"><Code2 class="size-4" /> {{ editingTemplate ? 'Save template' : 'Create template' }}</Button>
            </form>

            <div class="space-y-4">
                <section class="rounded-lg border bg-background p-5 shadow-sm">
                    <div class="flex items-center justify-between"><div><h2 class="font-semibold">Live preview</h2><p class="mt-1 text-xs text-muted-foreground">Preview menggunakan data contoh.</p></div><Button type="button" size="icon" variant="outline" title="Toggle preview" @click="showPreview = !showPreview"><Eye class="size-4" /></Button></div>
                    <iframe v-if="showPreview" :srcdoc="previewDocument" class="mt-4 h-72 w-full rounded-md border bg-zinc-950" sandbox="" />
                    <div v-else class="mt-4 grid h-72 place-items-center rounded-md border border-dashed text-sm text-muted-foreground">Klik preview untuk melihat hasil template.</div>
                </section>
                <section class="divide-y rounded-lg border bg-background shadow-sm">
                    <div v-for="template in templates" :key="template.id" class="flex items-center justify-between gap-4 p-4"><div class="min-w-0"><div class="truncate font-medium">{{ template.name }}</div><div class="mt-1 text-xs text-muted-foreground"><code>{{ template.slug }}</code> · {{ template.playlist_items_count }} playlist item</div></div><div class="flex shrink-0 gap-1"><Button type="button" size="icon" variant="ghost" title="Edit template" @click="editTemplate(template)"><Pencil class="size-4" /></Button><Button type="button" size="icon" variant="ghost" title="Delete template" :disabled="template.playlist_items_count > 0" @click="destroyTemplate(template)"><Trash2 class="size-4" /></Button></div></div>
                    <div v-if="templates.length === 0" class="p-8 text-center text-sm text-muted-foreground">Belum ada custom template.</div>
                </section>
            </div>
        </section>
    </div>
</template>
