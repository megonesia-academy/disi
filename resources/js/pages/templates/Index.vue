<script setup lang="ts">
import SubmitButton from '@/components/SubmitButton.vue';
import { Button } from '@/components/ui/button';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import {
    CheckCircle2,
    Coins,
    Eye,
    LayoutTemplate,
    PlayCircle,
    Search,
    ShoppingBag,
} from '@lucide/vue';
import { computed, ref } from 'vue';

type SchemaField = {
    name: string;
    label?: string;
    type?: string;
    default?: unknown;
};

type TenantTemplate = {
    id: string;
    name: string;
    slug: string;
    html: string;
    css: string | null;
    schema: SchemaField[];
    token_cost: number;
    is_rented: boolean;
};

const props = defineProps<{
    tenant: { id: string; name: string };
    templates: TenantTemplate[];
    balance: number;
}>();

const page = usePage();
const search = ref('');
const filter = ref<'all' | 'free' | 'paid' | 'rented'>('all');
const rentingId = ref<string | null>(null);
const previewOpen = ref(false);
const previewTemplate = ref<TenantTemplate | null>(null);

const billingError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.[
            'billing'
        ] ?? null,
);

const freeCount = computed(
    () =>
        props.templates.filter((template) => template.token_cost === 0).length,
);
const paidCount = computed(
    () => props.templates.filter((template) => template.token_cost > 0).length,
);
const rentedCount = computed(
    () => props.templates.filter((template) => template.is_rented).length,
);

const visibleTemplates = computed(() => {
    const term = search.value.trim().toLowerCase();

    return [...props.templates]
        .filter((template) => {
            const matchesTerm =
                term === '' ||
                template.name.toLowerCase().includes(term) ||
                template.slug.toLowerCase().includes(term);
            const matchesFilter =
                filter.value === 'all' ||
                (filter.value === 'free' && template.token_cost === 0) ||
                (filter.value === 'paid' && template.token_cost > 0) ||
                (filter.value === 'rented' && template.is_rented);

            return matchesTerm && matchesFilter;
        })
        .sort((a, b) => a.name.localeCompare(b.name));
});

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

function templatePreview(template: TenantTemplate): string {
    return buildPreview(template.html, template.css ?? '', template.schema);
}

function rent(template: TenantTemplate): void {
    if (props.balance < template.token_cost) {
        return;
    }

    rentingId.value = template.id;
    router.post(
        `/templates/${template.id}/rent`,
        {},
        {
            preserveScroll: true,
            onFinish: () => {
                rentingId.value = null;
            },
        },
    );
}

function openPreview(template: TenantTemplate): void {
    previewTemplate.value = template;
    previewOpen.value = true;
}
</script>

<template>
    <Head title="Template Library" />

    <div
        class="flex h-full flex-1 flex-col gap-5 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex rounded-full bg-violet-50 px-2.5 py-1 text-xs font-medium text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                >
                    Template library
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Template library
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Template dikelola admin aplikasi. Gunakan template gratis,
                    atau sewa template berbayar dengan token untuk dipakai di
                    playlist.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <span
                    class="inline-flex items-center gap-1.5 rounded-md border bg-background px-3 py-2 text-sm font-medium"
                >
                    <Coins class="size-4 text-amber-500" />
                    {{ balance }} token
                </span>
                <Button variant="outline" as-child>
                    <Link href="/workspace/billing">
                        <ShoppingBag class="size-4" /> Billing
                    </Link>
                </Button>
                <Button as-child>
                    <Link href="/playlists">
                        <PlayCircle class="size-4" /> Playlists
                    </Link>
                </Button>
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

        <section class="grid gap-4 sm:grid-cols-3">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Template gratis</div>
                <div class="mt-2 text-2xl font-semibold">{{ freeCount }}</div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    Template berbayar
                </div>
                <div class="mt-2 text-2xl font-semibold">{{ paidCount }}</div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Disewa</div>
                <div class="mt-2 text-2xl font-semibold">{{ rentedCount }}</div>
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
                v-model="filter"
                class="h-9 rounded-md border border-input bg-background px-3 text-sm"
            >
                <option value="all">Semua template</option>
                <option value="free">Gratis</option>
                <option value="paid">Berbayar</option>
                <option value="rented">Sudah disewa</option>
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
                class="group flex flex-col overflow-hidden rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
            >
                <button
                    type="button"
                    class="relative aspect-video w-full overflow-hidden bg-zinc-950"
                    :title="`Preview ${template.name}`"
                    @click="openPreview(template)"
                >
                    <iframe
                        :srcdoc="templatePreview(template)"
                        class="pointer-events-none absolute top-0 left-0 h-[400%] w-[400%] origin-top-left scale-[0.25] border-0"
                        sandbox=""
                        loading="lazy"
                        tabindex="-1"
                        aria-hidden="true"
                    />
                    <span
                        class="absolute inset-0 grid place-items-center bg-black/50 text-white opacity-0 transition-opacity group-hover:opacity-100"
                    >
                        <span
                            class="inline-flex items-center gap-2 rounded-md bg-black/60 px-3 py-2 text-sm font-medium"
                        >
                            <Eye class="size-4" /> Preview
                        </span>
                    </span>
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
                            v-if="template.is_rented"
                            class="inline-flex items-center gap-1 rounded-md bg-emerald-500 px-2 py-0.5 text-[11px] font-semibold text-white"
                        >
                            <CheckCircle2 class="size-3" /> Rented
                        </span>
                    </span>
                </button>

                <div class="flex flex-1 flex-col p-4">
                    <h2 class="truncate font-semibold" :title="template.name">
                        {{ template.name }}
                    </h2>
                    <p class="mt-0.5 truncate text-xs text-muted-foreground">
                        <code>{{ template.slug }}</code> ·
                        {{ template.schema.length }} field
                    </p>

                    <div class="mt-auto flex items-center gap-2 border-t pt-3">
                        <Button
                            type="button"
                            size="sm"
                            variant="outline"
                            @click="openPreview(template)"
                        >
                            <Eye class="size-4" /> Preview
                        </Button>

                        <span
                            v-if="template.token_cost === 0"
                            class="ml-auto text-xs font-medium text-emerald-600"
                        >
                            Gratis
                        </span>
                        <span
                            v-else-if="template.is_rented"
                            class="ml-auto inline-flex items-center gap-1 text-xs font-medium text-emerald-600"
                        >
                            <CheckCircle2 class="size-3.5" /> Disewa
                        </span>
                        <SubmitButton
                            v-else
                            type="button"
                            size="sm"
                            class="ml-auto"
                            :disabled="balance < template.token_cost"
                            :title="
                                balance < template.token_cost
                                    ? 'Saldo token tidak cukup'
                                    : `Sewa template (${template.token_cost} token)`
                            "
                            loading-label="Menyewa..."
                            :processing="rentingId === template.id"
                            @click="rent(template)"
                        >
                            <ShoppingBag class="size-4" />
                            Sewa {{ template.token_cost }}
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
                <LayoutTemplate class="size-6 text-muted-foreground" />
            </div>
            <h3 class="mt-4 text-lg font-semibold tracking-tight">
                {{
                    templates.length
                        ? 'Tidak ada template yang cocok'
                        : 'Belum ada template tersedia'
                }}
            </h3>
            <p class="mx-auto mt-2 max-w-md text-sm text-muted-foreground">
                {{
                    templates.length
                        ? 'Ubah kata kunci atau filter.'
                        : 'Admin aplikasi belum menambahkan template. Coba lagi nanti.'
                }}
            </p>
        </section>

        <Dialog v-model:open="previewOpen">
            <DialogContent class="max-h-[92vh] overflow-y-auto sm:max-w-4xl">
                <DialogHeader>
                    <DialogTitle>
                        {{ previewTemplate?.name ?? 'Template preview' }}
                    </DialogTitle>
                    <DialogDescription>
                        {{ previewTemplate?.slug }} ·
                        {{
                            previewTemplate?.token_cost
                                ? `${previewTemplate.token_cost} token`
                                : 'Gratis'
                        }}
                    </DialogDescription>
                </DialogHeader>

                <div
                    v-if="previewTemplate"
                    class="relative overflow-hidden rounded-lg border bg-zinc-950"
                    style="aspect-ratio: 16 / 9"
                >
                    <iframe
                        :srcdoc="templatePreview(previewTemplate)"
                        class="pointer-events-none absolute top-0 left-0 h-[400%] w-[400%] origin-top-left scale-[0.25] border-0"
                        sandbox=""
                        title="Template preview"
                    />
                </div>
            </DialogContent>
        </Dialog>
    </div>
</template>
