<script setup lang="ts">
import { Button } from '@/components/ui/button';
import { Head, Link, useForm } from '@inertiajs/vue3';

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

    removeForm.delete(`/workspace/members/${member.id}`, {
        preserveScroll: true,
    });
}
</script>

<template>
    <Head title="Workspace Members" />

    <div
        class="flex h-full flex-1 flex-col gap-6 overflow-x-auto bg-muted/20 p-4 md:p-6"
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
                <h1 class="text-2xl font-semibold tracking-tight">
                    Members
                </h1>
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
                <Link
                    href="/activity"
                    class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Activity
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

        <section
            class="overflow-hidden rounded-lg border bg-background shadow-sm"
        >
            <div class="border-b px-4 py-3">
                <h2 class="font-semibold tracking-tight">Member list</h2>
                <p class="text-xs text-muted-foreground">
                    Ubah role atau hapus member dari workspace.
                </p>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full min-w-[820px] text-sm">
                    <thead class="bg-muted/50 text-left text-muted-foreground">
                        <tr>
                            <th class="px-4 py-3 font-medium">Member</th>
                            <th class="px-4 py-3 font-medium">Current role</th>
                            <th class="px-4 py-3 font-medium">Change role</th>
                            <th class="px-4 py-3 text-right font-medium">
                                Actions
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr
                            v-for="member in members"
                            :key="member.id"
                            class="border-t"
                        >
                            <td class="px-4 py-3">
                                <div class="font-medium">
                                    {{ member.name }}
                                    <span
                                        v-if="member.is_current_user"
                                        class="ml-2 rounded-full bg-muted px-2 py-0.5 text-xs text-muted-foreground"
                                    >
                                        You
                                    </span>
                                </div>
                                <div class="text-xs text-muted-foreground">
                                    {{ member.email }}
                                </div>
                            </td>
                            <td class="px-4 py-3">
                                <span
                                    class="rounded-full bg-muted px-2 py-1 text-xs font-medium text-muted-foreground"
                                >
                                    {{ member.role }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <select
                                    v-model="roleForm(member).role"
                                    class="border-input h-9 w-44 rounded-md border bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none option:bg-background option:text-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                >
                                    <option
                                        v-for="role in roles"
                                        :key="role"
                                        :value="role"
                                    >
                                        {{ role }}
                                    </option>
                                </select>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex justify-end gap-2">
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        :disabled="
                                            roleForm(member).processing ||
                                            roleForm(member).role ===
                                                member.role
                                        "
                                        @click="updateRole(member)"
                                    >
                                        Update
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        :disabled="
                                            member.is_current_user ||
                                            removeForm.processing
                                        "
                                        @click="removeMember(member)"
                                    >
                                        Remove
                                    </Button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
</template>
