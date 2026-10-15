<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { CheckCircle2, Pencil, Plus, ShieldCheck, Trash2, X } from '@lucide/vue';
import { computed, ref } from 'vue';

type Provider = { name: string; base_url: string; auth_type: string };
type Connection = { id: string; name: string; provider: string; is_active: boolean; is_approved: boolean };
const props = defineProps<{ tenant: { id: string; name: string }; providers: Record<string, Provider>; connections: Connection[] }>();
const editingId = ref<string | null>(null);
const editing = computed(() => props.connections.find((connection) => connection.id === editingId.value));
const form = useForm({ name: '', provider: Object.keys(props.providers)[0] ?? '', auth_type: 'none', credential: '', is_active: true });

function reset(): void { editingId.value = null; form.reset(); form.provider = Object.keys(props.providers)[0] ?? ''; form.auth_type = 'none'; form.is_active = true; }
function edit(connection: Connection): void { editingId.value = connection.id; form.name = connection.name; form.provider = connection.provider; form.auth_type = 'none'; form.credential = ''; form.is_active = connection.is_active; }
function submit(): void { const options = { preserveScroll: true, onSuccess: reset }; editingId.value ? form.put(`/integrations/api/${editingId.value}`, options) : form.post('/integrations/api', options); }
function remove(connection: Connection): void { if (window.confirm(`Delete connection "${connection.name}"?`)) form.delete(`/integrations/api/${connection.id}`, { preserveScroll: true }); }
function approve(connection: Connection): void { form.post(`/integrations/api/${connection.id}/approve`, { preserveScroll: true }); }
async function test(connection: Connection): Promise<void> { const response = await fetch(`/integrations/api/${connection.id}/test`, { method: 'POST', headers: { Accept: 'application/json', 'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '' } }); const payload = await response.json(); window.alert(payload.message); }
</script>

<template>
    <Head title="API Connections" />
    <div class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-muted/20 p-4 md:p-6">
        <section class="flex flex-wrap items-end justify-between gap-4"><div><p class="text-sm text-muted-foreground">{{ tenant.name }}</p><h1 class="mt-1 text-2xl font-semibold tracking-tight">API Connections</h1><p class="mt-1 text-sm text-muted-foreground">Kelola koneksi provider resmi tanpa mengubah environment server.</p></div><div class="flex gap-2"><Link href="/templates" class="text-sm font-medium text-primary hover:underline">Templates</Link><Button type="button" @click="reset"><Plus class="size-4" /> New connection</Button></div></section>
        <section class="grid gap-6 xl:grid-cols-[minmax(320px,400px)_minmax(0,1fr)]">
            <form class="space-y-4 rounded-lg border bg-background p-5 shadow-sm" @submit.prevent="submit"><div class="flex items-start justify-between"><div><h2 class="font-semibold">{{ editing ? 'Edit connection' : 'New connection' }}</h2><p class="mt-1 text-xs text-muted-foreground">Connection baru perlu approval owner.</p></div><Button v-if="editing" type="button" size="icon" variant="ghost" title="Cancel" @click="reset"><X class="size-4" /></Button></div><div><label class="mb-1 block text-sm font-medium">Name</label><Input v-model="form.name" placeholder="Weather API" /></div><div><label class="mb-1 block text-sm font-medium">Provider</label><select v-model="form.provider" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option v-for="(provider, key) in providers" :key="key" :value="key">{{ provider.name }} · {{ provider.base_url }}</option></select></div><div><label class="mb-1 block text-sm font-medium">Authentication</label><select v-model="form.auth_type" class="h-9 w-full rounded-md border bg-background px-3 text-sm"><option value="none">No authentication</option><option value="bearer">Bearer token</option><option value="api_key">API key</option></select></div><div v-if="form.auth_type !== 'none'"><label class="mb-1 block text-sm font-medium">Credential</label><Input v-model="form.credential" type="password" placeholder="Credential terenkripsi" /></div><label class="flex items-center justify-between rounded-md border bg-muted/30 px-3 py-2 text-sm"><span class="font-medium">Active</span><input v-model="form.is_active" type="checkbox" class="size-4 rounded border-input" /></label><Button type="submit" class="w-full" :disabled="form.processing"><ShieldCheck class="size-4" /> {{ editing ? 'Save connection' : 'Create connection' }}</Button></form>
            <section class="divide-y rounded-lg border bg-background shadow-sm"><div v-for="connection in connections" :key="connection.id" class="flex flex-wrap items-center justify-between gap-4 p-4"><div class="min-w-0"><div class="flex items-center gap-2 font-medium"><span class="truncate">{{ connection.name }}</span><span v-if="connection.is_approved" class="inline-flex items-center gap-1 text-xs text-emerald-600"><CheckCircle2 class="size-3" /> Approved</span><span v-else class="text-xs text-amber-600">Pending approval</span></div><div class="mt-1 text-xs text-muted-foreground">{{ providers[connection.provider]?.name ?? connection.provider }} · {{ connection.is_active ? 'Active' : 'Inactive' }}</div></div><div class="flex gap-1"><Button v-if="!connection.is_approved" type="button" size="sm" variant="outline" @click="approve(connection)">Approve</Button><Button v-if="connection.is_approved" type="button" size="sm" variant="outline" @click="test(connection)">Test</Button><Button type="button" size="icon" variant="ghost" title="Edit" @click="edit(connection)"><Pencil class="size-4" /></Button><Button type="button" size="icon" variant="ghost" title="Delete" @click="remove(connection)"><Trash2 class="size-4" /></Button></div></div><div v-if="connections.length === 0" class="p-8 text-center text-sm text-muted-foreground">Belum ada API connection.</div></section>
        </section>
    </div>
</template>
