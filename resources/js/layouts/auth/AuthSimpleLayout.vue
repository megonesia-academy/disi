<script setup lang="ts">
import { Activity, CloudSun, MonitorPlay, Wifi } from '@lucide/vue';
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import { home } from '@/routes';

defineProps<{
    title?: string;
    description?: string;
}>();

const appName = computed(() => {
    const name = String(usePage().props.name ?? '').trim();

    return name && name.toLowerCase() !== 'laravel' ? name : 'DISI';
});
</script>

<template>
    <main
        class="grid min-h-svh bg-background lg:grid-cols-[minmax(0,1.1fr)_minmax(430px,0.9fr)]"
    >
        <section
            class="relative hidden min-h-svh overflow-hidden bg-[#070f1c] p-10 text-white lg:flex lg:flex-col xl:p-14"
        >
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(60%_50%_at_10%_0%,rgba(249,115,22,0.20),transparent_65%)]"
            />
            <div
                aria-hidden="true"
                class="pointer-events-none absolute inset-0 bg-[radial-gradient(55%_45%_at_90%_10%,rgba(59,130,246,0.22),transparent_60%)]"
            />

            <Link
                :href="home()"
                class="relative z-10 inline-flex w-fit items-center gap-3 rounded-lg focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:ring-offset-4 focus-visible:ring-offset-[#070f1c] focus-visible:outline-none"
            >
                <AppLogoIcon
                    class="size-10 shrink-0 object-contain"
                    width="40"
                    height="40"
                />
                <span class="text-lg font-black tracking-tight">
                    {{ appName }}
                </span>
            </Link>

            <div class="relative z-10 mx-auto my-auto w-full max-w-3xl py-10">
                <div class="mb-8 max-w-xl">
                    <p
                        class="text-xs font-black tracking-[0.22em] text-orange-300 uppercase"
                    >
                        Platform digital signage
                    </p>
                    <p
                        class="mt-4 text-4xl leading-tight font-black tracking-tight text-balance xl:text-5xl"
                    >
                        Satu workspace untuk setiap layar.
                    </p>
                    <p class="mt-4 max-w-lg text-base leading-7 text-slate-300">
                        Susun playlist, hubungkan data, dan pantau layar dari
                        satu tempat.
                    </p>
                </div>

                <div
                    class="rounded-xl border border-white/10 bg-white/[0.04] p-3 shadow-2xl shadow-black/50"
                >
                    <div
                        class="overflow-hidden rounded-lg bg-white text-zinc-950"
                    >
                        <div
                            class="flex items-center justify-between border-b border-zinc-100 bg-white px-5 py-3"
                        >
                            <div class="flex items-center gap-2">
                                <span
                                    class="size-2 rounded-full bg-emerald-500"
                                    aria-hidden="true"
                                />
                                <span
                                    class="text-xs font-black tracking-wide text-zinc-900"
                                >
                                    LAYAR LOBI 01
                                </span>
                            </div>
                            <div
                                class="flex items-center gap-3 text-[11px] font-semibold text-zinc-500"
                            >
                                <span class="inline-flex items-center gap-1">
                                    <Wifi class="size-3" aria-hidden="true" />
                                    Online
                                </span>
                                <span class="tabular-nums">08:42</span>
                            </div>
                        </div>

                        <div class="grid min-h-80 grid-cols-[1.3fr_0.7fr]">
                            <div
                                class="flex flex-col justify-between bg-gradient-to-br from-orange-500 via-orange-400 to-amber-300 p-7 text-[#0b1220]"
                            >
                                <div>
                                    <p
                                        class="text-xs font-black tracking-[0.18em] uppercase opacity-70"
                                    >
                                        Informasi lobi
                                    </p>
                                    <p
                                        class="mt-3 max-w-sm text-3xl leading-tight font-black tracking-tight text-balance"
                                    >
                                        Informasi hari ini, langsung terlihat.
                                    </p>
                                </div>
                                <div
                                    class="flex items-center gap-3 text-sm font-bold"
                                >
                                    <Activity
                                        class="size-5"
                                        aria-hidden="true"
                                    />
                                    Playlist aktif · Morning Lobby
                                </div>
                            </div>

                            <div class="flex flex-col bg-white p-6">
                                <div class="flex items-center justify-between">
                                    <span
                                        class="text-xs font-black tracking-wide text-zinc-500"
                                    >
                                        JAKARTA
                                    </span>
                                    <CloudSun
                                        class="size-6 text-amber-500"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div
                                    class="mt-3 text-4xl font-black tabular-nums"
                                >
                                    30°
                                </div>
                                <p class="mt-1 text-xs text-zinc-500">
                                    Cerah berawan
                                </p>
                                <div
                                    class="mt-auto space-y-3 border-t pt-4 text-xs"
                                >
                                    <div class="flex justify-between gap-3">
                                        <span class="text-zinc-500"
                                            >Antrean</span
                                        >
                                        <strong>A-024</strong>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <span class="text-zinc-500">Loket</span>
                                        <strong>03</strong>
                                    </div>
                                    <div class="flex justify-between gap-3">
                                        <span class="text-zinc-500">
                                            Pembaruan berikutnya
                                        </span>
                                        <strong>2 menit</strong>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div
                            class="flex items-center gap-3 bg-[#0b1220] px-5 py-2.5 text-xs font-semibold text-orange-200"
                        >
                            <MonitorPlay class="size-4" aria-hidden="true" />
                            Selamat datang · Layanan operasional mulai pukul
                            08.00
                        </div>
                    </div>
                </div>
            </div>

            <div
                class="relative z-10 flex items-center justify-between text-xs font-medium text-slate-400"
            >
                <span>Konten · Layar · Data langsung</span>
                <span>Selalu siap</span>
            </div>
        </section>

        <section
            class="flex min-h-svh flex-col justify-center px-5 py-10 sm:px-8 lg:px-12 xl:px-16"
        >
            <div class="mx-auto w-full max-w-md">
                <Link
                    :href="home()"
                    class="mb-10 inline-flex items-center gap-3 rounded-lg font-black tracking-tight lg:hidden"
                >
                    <AppLogoIcon
                        class="size-9 shrink-0 object-contain"
                        width="36"
                        height="36"
                    />
                    <span>{{ appName }}</span>
                </Link>

                <div class="mb-8">
                    <p
                        class="inline-flex items-center gap-2 text-xs font-black tracking-[0.18em] text-orange-700 uppercase dark:text-orange-300"
                    >
                        <span
                            class="size-1.5 rounded-full bg-orange-500"
                            aria-hidden="true"
                        />
                        Akses workspace
                    </p>
                    <h1
                        class="mt-4 text-3xl font-black tracking-tight text-balance text-foreground"
                    >
                        {{ title }}
                    </h1>
                    <p class="mt-3 text-sm leading-6 text-muted-foreground">
                        {{ description }}
                    </p>
                </div>

                <slot />

                <p
                    class="mt-10 border-t border-border pt-5 text-xs leading-5 text-muted-foreground"
                >
                    Butuh bantuan masuk? Hubungi admin workspace Anda.
                </p>
            </div>
        </section>
    </main>
</template>
