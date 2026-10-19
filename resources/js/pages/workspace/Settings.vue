<script setup lang="ts">
import InputError from '@/components/InputError.vue';
import SubmitButton from '@/components/SubmitButton.vue';
import { Input } from '@/components/ui/input';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { Save } from '@lucide/vue';

const props = defineProps<{
    tenant: {
        id: string;
        name: string;
        slug: string;
        billing_email: string | null;
        status: string;
        settings: {
            brand_color: string;
            player_accent_label: string;
            logo_path: string | null;
            logo_url: string | null;
            logo_width: number;
            logo_position: string;
            logo_offset_x: number;
            logo_offset_y: number;
            player_behavior: {
                default_media_fit: string;
                manifest_refresh_seconds: number;
                heartbeat_interval_seconds: number;
                show_player_overlay: boolean;
            };
            billing_metadata: {
                company_name: string;
                tax_id: string;
                billing_address: string;
                phone: string;
            };
        };
    };
}>();

const form = useForm({
    name: props.tenant.name,
    slug: props.tenant.slug,
    billing_email: props.tenant.billing_email ?? '',
    brand_color: props.tenant.settings.brand_color,
    player_accent_label: props.tenant.settings.player_accent_label,
    logo: null as File | null,
    logo_width: props.tenant.settings.logo_width,
    logo_position: props.tenant.settings.logo_position,
    logo_offset_x: props.tenant.settings.logo_offset_x,
    logo_offset_y: props.tenant.settings.logo_offset_y,
    manifest_refresh_seconds:
        props.tenant.settings.player_behavior.manifest_refresh_seconds,
    heartbeat_interval_seconds:
        props.tenant.settings.player_behavior.heartbeat_interval_seconds,
    show_player_overlay:
        props.tenant.settings.player_behavior.show_player_overlay,
    billing_company_name: props.tenant.settings.billing_metadata.company_name,
    billing_tax_id: props.tenant.settings.billing_metadata.tax_id,
    billing_address: props.tenant.settings.billing_metadata.billing_address,
    billing_phone: props.tenant.settings.billing_metadata.phone,
});

function submit(): void {
    form.post('/workspace/settings', {
        preserveScroll: true,
        forceFormData: true,
    });
}

function updateLogo(event: Event): void {
    const target = event.target as HTMLInputElement;
    form.logo = target.files?.[0] ?? null;
}
</script>

<template>
    <Head title="Workspace Settings" />

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
                    SaaS workspace
                </div>
                <h1 class="text-2xl font-semibold tracking-tight">
                    Workspace settings
                </h1>
                <p class="text-sm text-muted-foreground">
                    Kelola identitas tenant, billing contact, dan branding dasar
                    player.
                </p>
            </div>

            <div class="flex gap-2">
                <Link
                    href="/dashboard"
                    class="rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs transition-colors hover:bg-accent"
                >
                    Dashboard
                </Link>
            </div>
        </section>

        <section class="grid gap-4 lg:grid-cols-[minmax(360px,520px)_1fr]">
            <form
                class="rounded-lg border bg-background p-5 shadow-sm"
                @submit.prevent="submit"
            >
                <div>
                    <h2 class="text-lg font-semibold tracking-tight">
                        Workspace identity
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Nama dan slug dipakai untuk identifikasi tenant di
                        seluruh aplikasi.
                    </p>
                </div>

                <div class="mt-5 space-y-5">
                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Workspace name
                        </label>
                        <Input v-model="form.name" />
                        <InputError :message="form.errors.name" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Slug
                        </label>
                        <Input v-model="form.slug" />
                        <InputError :message="form.errors.slug" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Billing email
                        </label>
                        <Input
                            v-model="form.billing_email"
                            type="email"
                            placeholder="billing@example.com"
                        />
                        <InputError :message="form.errors.billing_email" />
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Logo
                        </label>
                        <input
                            type="file"
                            accept="image/*"
                            class="h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none file:mr-3 file:border-0 file:bg-transparent file:text-sm file:font-medium focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            @change="updateLogo"
                        />
                        <InputError :message="form.errors.logo" />
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Logo width (px)
                            </label>
                            <Input
                                v-model="form.logo_width"
                                type="number"
                                min="32"
                                max="600"
                            />
                            <InputError :message="form.errors.logo_width" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Player position
                            </label>
                            <select
                                v-model="form.logo_position"
                                class="option:bg-background option:text-foreground h-9 w-full rounded-md border border-input bg-background px-3 py-1 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                            >
                                <option value="top-left">Top left</option>
                                <option value="top-center">Top center</option>
                                <option value="top-right">Top right</option>
                                <option value="center-left">Center left</option>
                                <option value="center">Center</option>
                                <option value="center-right">
                                    Center right
                                </option>
                                <option value="bottom-left">Bottom left</option>
                                <option value="bottom-center">
                                    Bottom center
                                </option>
                                <option value="bottom-right">
                                    Bottom right
                                </option>
                            </select>
                            <InputError :message="form.errors.logo_position" />
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Horizontal offset (px)
                            </label>
                            <Input
                                v-model="form.logo_offset_x"
                                type="number"
                                min="-500"
                                max="500"
                                step="1"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Positif ke kanan, negatif ke kiri.
                            </p>
                            <InputError :message="form.errors.logo_offset_x" />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Vertical offset (px)
                            </label>
                            <Input
                                v-model="form.logo_offset_y"
                                type="number"
                                min="-500"
                                max="500"
                                step="1"
                            />
                            <p class="mt-1 text-xs text-muted-foreground">
                                Positif ke bawah, negatif ke atas.
                            </p>
                            <InputError :message="form.errors.logo_offset_y" />
                        </div>
                    </div>

                    <div class="grid grid-cols-[80px_1fr] gap-3">
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Color
                            </label>
                            <input
                                v-model="form.brand_color"
                                type="color"
                                class="h-10 w-full rounded-md border border-input bg-background p-1"
                            />
                        </div>
                        <div>
                            <label class="mb-1 block text-sm font-medium">
                                Brand color
                            </label>
                            <Input v-model="form.brand_color" />
                            <InputError :message="form.errors.brand_color" />
                        </div>
                    </div>

                    <div>
                        <label class="mb-1 block text-sm font-medium">
                            Player label
                        </label>
                        <Input v-model="form.player_accent_label" />
                        <InputError
                            :message="form.errors.player_accent_label"
                        />
                    </div>

                    <div class="rounded-lg border p-4">
                        <h3 class="font-semibold tracking-tight">
                            Player behavior
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium"
                                    >
                                        Manifest refresh
                                    </label>
                                    <Input
                                        v-model="form.manifest_refresh_seconds"
                                        type="number"
                                        min="10"
                                        max="3600"
                                    />
                                    <InputError
                                        :message="
                                            form.errors.manifest_refresh_seconds
                                        "
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium"
                                    >
                                        Heartbeat
                                    </label>
                                    <Input
                                        v-model="
                                            form.heartbeat_interval_seconds
                                        "
                                        type="number"
                                        min="5"
                                        max="300"
                                    />
                                    <InputError
                                        :message="
                                            form.errors
                                                .heartbeat_interval_seconds
                                        "
                                    />
                                </div>
                            </div>

                            <label class="flex items-center gap-2 text-sm">
                                <input
                                    v-model="form.show_player_overlay"
                                    type="checkbox"
                                    class="size-4 rounded border-input"
                                />
                                Show player overlay
                            </label>
                        </div>
                    </div>

                    <div class="rounded-lg border p-4">
                        <h3 class="font-semibold tracking-tight">
                            Billing metadata
                        </h3>
                        <div class="mt-4 space-y-4">
                            <div>
                                <label class="mb-1 block text-sm font-medium">
                                    Company name
                                </label>
                                <Input v-model="form.billing_company_name" />
                                <InputError
                                    :message="form.errors.billing_company_name"
                                />
                            </div>
                            <div class="grid grid-cols-2 gap-3">
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium"
                                    >
                                        Tax ID
                                    </label>
                                    <Input v-model="form.billing_tax_id" />
                                    <InputError
                                        :message="form.errors.billing_tax_id"
                                    />
                                </div>
                                <div>
                                    <label
                                        class="mb-1 block text-sm font-medium"
                                    >
                                        Phone
                                    </label>
                                    <Input v-model="form.billing_phone" />
                                    <InputError
                                        :message="form.errors.billing_phone"
                                    />
                                </div>
                            </div>
                            <div>
                                <label class="mb-1 block text-sm font-medium">
                                    Billing address
                                </label>
                                <textarea
                                    v-model="form.billing_address"
                                    rows="3"
                                    class="w-full rounded-md border border-input bg-background px-3 py-2 text-sm text-foreground shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                />
                                <InputError
                                    :message="form.errors.billing_address"
                                />
                            </div>
                        </div>
                    </div>

                    <SubmitButton
                        :processing="form.processing"
                        label="Save workspace"
                        loading-label="Menyimpan..."
                    >
                        <Save class="size-4" />
                    </SubmitButton>
                </div>
            </form>

            <aside class="space-y-4">
                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <h2 class="text-lg font-semibold tracking-tight">
                        Branding preview
                    </h2>
                    <p class="text-sm text-muted-foreground">
                        Preview sederhana untuk warna dan label player.
                    </p>

                    <div
                        class="mt-5 overflow-hidden rounded-lg border bg-zinc-950 text-white"
                    >
                        <div
                            class="h-2"
                            :style="{ backgroundColor: form.brand_color }"
                        />
                        <div class="grid min-h-72 place-items-center p-8">
                            <div class="text-center">
                                <img
                                    v-if="tenant.settings.logo_url"
                                    :src="tenant.settings.logo_url"
                                    alt=""
                                    class="mx-auto mb-4 max-w-full border border-white/20 object-contain"
                                    :style="{
                                        width: `${form.logo_width}px`,
                                        height: 'auto',
                                    }"
                                />
                                <div
                                    v-else
                                    class="mx-auto mb-4 grid size-16 place-items-center rounded-full text-xl font-semibold"
                                    :style="{
                                        backgroundColor: form.brand_color,
                                    }"
                                >
                                    {{ form.name.charAt(0).toUpperCase() }}
                                </div>
                                <div class="text-2xl font-semibold">
                                    {{ form.player_accent_label || form.name }}
                                </div>
                                <div class="mt-2 text-sm text-white/55">
                                    {{ form.slug }} · {{ tenant.status }}
                                </div>
                                <div class="mt-4 text-xs text-white/45">
                                    Item fit diatur per playlist · refresh
                                    {{ form.manifest_refresh_seconds }}s ·
                                    heartbeat
                                    {{ form.heartbeat_interval_seconds }}s
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="rounded-lg border bg-background p-5 shadow-sm">
                    <h2 class="font-semibold tracking-tight">
                        Billing snapshot
                    </h2>
                    <dl class="mt-3 space-y-3 text-sm">
                        <div>
                            <dt class="text-muted-foreground">Company</dt>
                            <dd class="font-medium">
                                {{ form.billing_company_name || '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Tax ID</dt>
                            <dd class="font-medium">
                                {{ form.billing_tax_id || '-' }}
                            </dd>
                        </div>
                        <div>
                            <dt class="text-muted-foreground">Phone</dt>
                            <dd class="font-medium">
                                {{ form.billing_phone || '-' }}
                            </dd>
                        </div>
                    </dl>
                </div>
            </aside>
        </section>
    </div>
</template>
