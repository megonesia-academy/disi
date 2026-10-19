<script setup lang="ts">
import SubmitButton from '@/components/SubmitButton.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save, Trash2 } from '@lucide/vue';
import { ref } from 'vue';

type Member = {
    id: number;
    name: string;
    email: string;
    role: string;
    accepted_at: string | null;
    is_current_user: boolean;
};

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
    };
    members: Member[];
    roles: string[];
}>();

const roleForms = new Map(
    props.members.map((member) => [
        member.id,
        useForm({
            role: member.role,
        }),
    ]),
);

const removeForm = useForm({});
const removingId = ref<number | null>(null);

function roleForm(member: Member) {
    return roleForms.get(member.id)!;
}

function updateRole(member: Member): void {
    roleForm(member).put(`/workspace/members/${member.id}`, {
        preserveScroll: true,
    });
}

function removeMember(member: Member): void {
    if (!window.confirm(`Remove ${member.name} from this workspace?`)) {
        return;
    }

    removingId.value = member.id;
    removeForm.delete(`/workspace/members/${member.id}`, {
        preserveScroll: true,
        onFinish: () => {
            removingId.value = null;
        },
    });
}
</script>

<template>
    <Head title="Workspace Members" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-workspace p-4 md:p-6"
    >
        <section
            class="flex flex-wrap items-end justify-between gap-4 rounded-lg border bg-background p-5 shadow-sm"
        >
            <div>
                <div
                    class="mb-2 inline-flex rounded-full bg-cyan-50 px-2.5 py-1 text-xs font-medium text-cyan-700 dark:bg-cyan-950 dark:text-cyan-300"
                >
                    Team access
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">Members</h1>
                <p class="text-sm text-muted-foreground">
                    Kelola akses user untuk {{ tenant.name }}.
                </p>
            </div>

            <div class="flex gap-2">
                <Link
                    href="/workspace/settings"
                    class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Workspace
                </Link>
            </div>
        </section>

        <section class="grid gap-4 md:grid-cols-3">
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Members</div>
                <div class="mt-2 text-3xl font-semibold">
                    {{ members.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Roles</div>
                <div class="mt-2 text-3xl font-semibold">
                    {{ roles.length }}
                </div>
            </div>
            <div class="rounded-lg border bg-background p-4 shadow-sm">
                <div class="text-sm text-muted-foreground">Workspace</div>
                <div class="mt-2 truncate text-lg font-semibold">
                    {{ tenant.name }}
                </div>
            </div>
        </section>

        <section class="rounded-lg border bg-background shadow-sm">
            <div class="border-b px-4 py-3">
                <h2 class="font-semibold tracking-tight">Member list</h2>
                <p class="text-xs text-muted-foreground">
                    Ubah role atau hapus member dari workspace.
                </p>
            </div>

            <div class="grid gap-4 p-4 sm:grid-cols-2 xl:grid-cols-3">
                <article
                    v-for="member in members"
                    :key="member.id"
                    class="flex flex-col rounded-lg border bg-background shadow-sm transition-shadow hover:shadow-md"
                >
                    <div
                        class="flex items-start justify-between gap-3 border-b px-4 py-3"
                    >
                        <div class="min-w-0">
                            <h2
                                class="flex items-center gap-2 truncate font-semibold"
                            >
                                {{ member.name }}
                                <span
                                    v-if="member.is_current_user"
                                    class="rounded-full bg-muted px-2 py-0.5 text-[11px] text-muted-foreground"
                                >
                                    You
                                </span>
                            </h2>
                            <p class="truncate text-xs text-muted-foreground">
                                {{ member.email }}
                            </p>
                        </div>
                        <div
                            class="flex shrink-0 flex-wrap items-center justify-end gap-1.5"
                        >
                            <span
                                class="rounded-full bg-muted px-2 py-1 text-[11px] font-medium text-muted-foreground"
                            >
                                {{ member.role }}
                            </span>
                        </div>
                    </div>

                    <div class="px-4 py-3">
                        <label
                            class="text-xs text-muted-foreground"
                            :for="`role-${member.id}`"
                        >
                            Change role
                        </label>
                        <select
                            :id="`role-${member.id}`"
                            v-model="roleForm(member).role"
                            class="option:bg-background option:text-foreground h-9 w-44 rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                        >
                            <option
                                v-for="role in roles"
                                :key="role"
                                :value="role"
                            >
                                {{ role }}
                            </option>
                        </select>
                    </div>

                    <div
                        class="mt-auto flex flex-wrap gap-2 border-t bg-muted/20 px-4 py-3"
                    >
                        <SubmitButton
                            type="button"
                            size="sm"
                            variant="outline"
                            title="Update role"
                            loading-label="Menyimpan..."
                            :processing="roleForm(member).processing"
                            :disabled="roleForm(member).role === member.role"
                            @click="updateRole(member)"
                        >
                            <Save class="size-4" />
                            Update
                        </SubmitButton>
                        <SubmitButton
                            type="button"
                            size="sm"
                            variant="destructive"
                            title="Remove member"
                            aria-label="Remove member"
                            loading-label="Menghapus..."
                            :processing="removingId === member.id"
                            :disabled="member.is_current_user"
                            @click="removeMember(member)"
                        >
                            <Trash2 class="size-4" />
                            Remove
                        </SubmitButton>
                    </div>
                </article>
            </div>

            <div
                v-if="members.length === 0"
                class="border-t px-4 py-10 text-center text-sm text-muted-foreground"
            >
                Belum ada member.
            </div>
        </section>
    </div>
</template>
