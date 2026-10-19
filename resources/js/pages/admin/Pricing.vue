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
import { Head, Link, router, useForm, usePage } from '@inertiajs/vue3';
import { ArrowLeft, Plus, Save, ShieldCheck, Tags, X } from '@lucide/vue';
import { computed, ref, watch } from 'vue';

type FeaturePrice = {
    id: string;
    key: string;
    label: string;
    description: string | null;
    kind: string;
    token_cost: number;
    free_limit: number;
    unit_label: string;
    is_active: boolean;
};

const props = defineProps<{
    prices: FeaturePrice[];
    kinds: string[];
}>();

const page = usePage();
const rows = ref<FeaturePrice[]>(props.prices.map((price) => ({ ...price })));
const savingId = ref<string | null>(null);
const formOpen = ref(false);

watch(
    () => props.prices,
    (prices) => {
        rows.value = prices.map((price) => ({ ...price }));
    },
);

const pageError = computed(
    () =>
        (page.props.errors as Record<string, string> | undefined)?.['key'] ??
        null,
);

const form = useForm({
    key: '',
    label: '',
    description: '',
    kind: 'addon',
    token_cost: 25,
    free_limit: 0,
    unit_label: 'slot',
});

function openCreate(): void {
    form.reset();
    form.clearErrors();
    formOpen.value = true;
}

function submitCreate(): void {
    form.post('/admin/pricing', {
        preserveScroll: true,
        onSuccess: () => {
            formOpen.value = false;
            form.reset();
        },
    });
}

function save(row: FeaturePrice): void {
    savingId.value = row.id;
    router.put(
        `/admin/pricing/${row.id}`,
        {
            label: row.label,
            description: row.description,
            token_cost: row.token_cost,
            free_limit: row.free_limit,
            unit_label: row.unit_label,
            is_active: row.is_active,
        },
        {
            preserveScroll: true,
            onFinish: () => {
                savingId.value = null;
            },
        },
    );
}
</script>

<template>
    <Head title="Feature Pricing" />

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
                    Feature pricing
                </h1>
                <p class="mt-1 max-w-2xl text-sm text-muted-foreground">
                    Atur biaya token dan batas gratis tiap fitur. Perubahan
                    berlaku untuk pembelian dan tagihan berikutnya.
                </p>
            </div>
            <div class="flex flex-wrap items-center gap-2">
                <Button variant="outline" as-child>
                    <Link href="/admin">
                        <ArrowLeft class="size-4" /> Admin dashboard
                    </Link>
                </Button>
                <Button type="button" @click="openCreate">
                    <Plus class="size-4" /> New feature
                </Button>
            </div>
        </section>

        <div
            v-if="pageError"
            class="rounded-lg border border-red-200 bg-red-50 p-3 text-sm text-red-700 dark:border-red-500/30 dark:bg-red-500/10 dark:text-red-200"
        >
            {{ pageError }}
        </div>

        <section class="grid gap-3 sm:grid-cols-3">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div
                    class="flex items-center gap-2 text-sm text-muted-foreground"
                >
                    <Tags class="size-4" /> Fitur terdaftar
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{ prices.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">
                    Langganan bulanan
                </div>
                <div class="mt-2 text-2xl font-semibold">
                    {{
                        prices.find(
                            (price) => price.key === 'monthly_subscription',
                        )?.token_cost ?? 0
                    }}
                    token
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Gratis screen</div>
                <div class="mt-2 text-2xl font-semibold">
                    {{
                        prices.find((price) => price.key === 'extra_screen')
                            ?.free_limit ?? 0
                    }}
                </div>
            </div>
        </section>

        <section class="rounded-lg border bg-background shadow-sm">
            <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="row in rows"
                    :key="row.id"
                    class="flex flex-col rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b px-4 py-3"
                    >
                        <div class="min-w-0 flex-1">
                            <Input
                                v-model="row.label"
                                class="h-8 text-sm font-semibold"
                            />
                            <code
                                class="mt-1 block truncate text-[11px] text-muted-foreground"
                            >
                                {{ row.key }}
                            </code>
                        </div>
                        <div
                            class="flex shrink-0 flex-wrap items-center justify-end gap-1.5"
                        >
                            <span
                                class="rounded-full px-2 py-0.5 text-[11px] font-semibold"
                                :class="
                                    row.kind === 'subscription'
                                        ? 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300'
                                        : 'bg-muted text-muted-foreground'
                                "
                            >
                                {{ row.kind }}
                            </span>
                        </div>
                    </div>
                    <dl
                        class="grid grid-cols-2 gap-x-4 gap-y-2 px-4 py-3 text-xs"
                    >
                        <div>
                            <dt class="text-muted-foreground">Token cost</dt>
                            <dd class="mt-0.5 font-medium">
                                <Input
                                    v-model="row.token_cost"
                                    type="number"
                                    min="0"
                                    class="h-8 w-full text-sm"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Free limit</dt>
                            <dd class="mt-0.5 font-medium">
                                <Input
                                    v-model="row.free_limit"
                                    type="number"
                                    min="0"
                                    class="h-8 w-full text-sm"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Unit</dt>
                            <dd class="mt-0.5 font-medium">
                                <Input
                                    v-model="row.unit_label"
                                    class="h-8 w-full text-sm"
                                />
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Active</dt>
                            <dd class="mt-0.5 font-medium">
                                <input
                                    v-model="row.is_active"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                            </dd>
                        </div>
                    </dl>
                    <div
                        class="mt-auto flex flex-wrap justify-end gap-2 border-t bg-muted/20 px-4 py-3"
                    >
                        <SubmitButton
                            type="button"
                            size="sm"
                            variant="outline"
                            title="Simpan perubahan"
                            loading-label="Menyimpan..."
                            :processing="savingId === row.id"
                            @click="save(row)"
                        >
                            <Save class="size-4" /> Save
                        </SubmitButton>
                    </div>
                </article>
            </div>
            <div
                v-if="rows.length === 0"
                class="border-t px-4 py-10 text-center text-sm text-muted-foreground"
            >
                Belum ada harga fitur.
            </div>
        </section>

        <Dialog v-model:open="formOpen">
            <DialogContent class="max-h-[90vh] overflow-y-auto sm:max-w-lg">
                <form class="space-y-4" @submit.prevent="submitCreate">
                    <DialogHeader>
                        <DialogTitle>New feature price</DialogTitle>
                        <DialogDescription>
                            Fitur baru bisa langsung dibeli tenant (kind addon)
                            atau dipakai untuk tagihan langganan.
                        </DialogDescription>
                    </DialogHeader>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Key
                            </label>
                            <Input
                                v-model="form.key"
                                placeholder="extra_device"
                                class="font-mono text-sm"
                            />
                            <InputError :message="form.errors.key" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Label
                            </label>
                            <Input
                                v-model="form.label"
                                placeholder="Slot device"
                            />
                            <InputError :message="form.errors.label" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Description
                        </label>
                        <Input
                            v-model="form.description"
                            placeholder="Penjelasan singkat fitur"
                        />
                        <InputError :message="form.errors.description" />
                    </div>

                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Kind
                            </label>
                            <select
                                v-model="form.kind"
                                class="h-9 w-full rounded-md border border-input bg-background px-3 text-sm"
                            >
                                <option
                                    v-for="kind in kinds"
                                    :key="kind"
                                    :value="kind"
                                >
                                    {{ kind }}
                                </option>
                            </select>
                            <InputError :message="form.errors.kind" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Unit label
                            </label>
                            <Input
                                v-model="form.unit_label"
                                placeholder="slot"
                            />
                            <InputError :message="form.errors.unit_label" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Token cost
                            </label>
                            <Input
                                v-model="form.token_cost"
                                type="number"
                                min="0"
                            />
                            <InputError :message="form.errors.token_cost" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Free limit
                            </label>
                            <Input
                                v-model="form.free_limit"
                                type="number"
                                min="0"
                            />
                            <InputError :message="form.errors.free_limit" />
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
                            label="Tambah fitur"
                            loading-label="Menyimpan..."
                        >
                            <Plus class="size-4" />
                        </SubmitButton>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    </div>
</template>
