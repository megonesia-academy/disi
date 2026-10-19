<script setup lang="ts">
import { Head, Link, usePage } from '@inertiajs/vue3';
import {
    ArrowRight,
    Building,
    CalendarClock,
    Check,
    ChevronDown,
    Hospital,
    Images,
    MonitorCheck,
    Plane,
    Plug,
    Sparkles,
    Users,
    Utensils,
    Volume2,
} from '@lucide/vue';
import { computed } from 'vue';
import AppLogoIcon from '@/components/AppLogoIcon.vue';
import ScreenShowcase from '@/components/landing/ScreenShowcase.vue';
import { dashboard, documentation, login, register } from '@/routes';

const page = usePage();
const isAuthenticated = computed(() => Boolean(page.props.auth?.user));
const currentYear = new Date().getFullYear();

const features = [
    {
        icon: CalendarClock,
        title: 'Playlist & jadwal',
        description:
            'Susun urutan konten, lalu tentukan jam tayang untuk setiap hari.',
    },
    {
        icon: Images,
        title: 'Perpustakaan media',
        description:
            'Unggah gambar dan video sekali, lalu pakai ulang di semua layar.',
    },
    {
        icon: Plug,
        title: 'Data dari API',
        description:
            'Hubungkan REST API untuk harga, jadwal, atau kurs yang selalu segar.',
    },
    {
        icon: MonitorCheck,
        title: 'Pemantauan layar',
        description:
            'Pantau status online dan heartbeat perangkat langsung dari dashboard.',
    },
    {
        icon: Building,
        title: 'Banyak lokasi',
        description:
            'Kelompokkan layar per cabang dengan workspace yang terpisah.',
    },
    {
        icon: Users,
        title: 'Akses tim',
        description:
            'Atur peran owner, admin, atau peninjau untuk tiap anggota workspace.',
    },
];

const steps = [
    {
        number: '01',
        title: 'Pasangkan layar',
        description:
            'Buka halaman pairing di browser layar, masukkan kodenya, dan layar langsung terhubung.',
    },
    {
        number: '02',
        title: 'Susun konten',
        description:
            'Tambahkan media, data API, atau template ke playlist yang sesuai dengan lokasi.',
    },
    {
        number: '03',
        title: 'Atur jadwal',
        description:
            'Tentukan waktu tayang. Layar memperbarui dirinya sendiri tanpa perlu dikunjungi.',
    },
];

const templates = [
    {
        icon: Plane,
        label: 'Bandara',
        title: 'Papan keberangkatan',
        description: 'Jadwal, gerbang, dan status dalam sekali lihat.',
        thumb: 'from-sky-500/35 via-blue-600/25 to-slate-900',
    },
    {
        icon: Volume2,
        label: 'Rumah sakit',
        title: 'Papan antrean pasien',
        description: 'Nomor yang dipanggil dan loket tujuan tampil besar.',
        thumb: 'from-teal-400/35 via-cyan-500/25 to-slate-900',
    },
    {
        icon: Hospital,
        label: 'Klinik',
        title: 'Papan layanan',
        description: 'Daftar poliklinik dan nomor antrean per ruang.',
        thumb: 'from-emerald-400/35 via-teal-500/25 to-slate-900',
    },
    {
        icon: Utensils,
        label: 'Restoran',
        title: 'Papan menu',
        description: 'Menu dan harga yang mudah dibaca dari jauh.',
        thumb: 'from-orange-400/40 via-amber-500/25 to-slate-900',
    },
];

const primaryAction = computed(() =>
    isAuthenticated.value
        ? { label: 'Buka dashboard', url: dashboard() }
        : { label: 'Buat akun gratis', url: register() },
);

const secondaryAction = computed(() =>
    isAuthenticated.value
        ? { label: 'Baca dokumentasi', url: documentation() }
        : { label: 'Masuk', url: login() },
);
</script>

<template>
    <Head title="Digital signage untuk informasi publik">
        <meta
            name="description"
            content="DISI membantu tim menyusun playlist, mengatur jadwal, dan memantau layar digital signage dari satu dashboard."
        />
    </Head>

    <div
        class="h-dvh snap-y snap-mandatory overflow-y-auto scroll-smooth bg-[#060c18] text-slate-200 [color-scheme:dark] motion-reduce:scroll-auto"
    >
        <a
            href="#konten"
            class="fixed top-4 left-4 z-[60] -translate-y-24 rounded-lg bg-white px-4 py-2 text-sm font-bold text-slate-900 shadow-lg transition-transform focus:translate-y-0 focus-visible:ring-2 focus-visible:ring-orange-400 motion-reduce:transition-none"
        >
            Lewati ke konten utama
        </a>

        <main id="konten">
            <section
                aria-labelledby="judul-hero"
                class="relative flex h-dvh snap-start flex-col overflow-y-auto"
            >
                <div
                    class="hero-glow pointer-events-none absolute inset-0"
                    aria-hidden="true"
                />
                <div
                    class="hero-grid pointer-events-none absolute inset-0"
                    aria-hidden="true"
                />

                <div
                    class="relative mx-auto my-auto grid w-full max-w-7xl grid-cols-1 items-center gap-12 px-5 py-12 sm:px-8 sm:py-14 lg:px-10 lg:py-16 xl:grid-cols-[0.95fr_1.05fr] xl:gap-14"
                >
                    <div class="mx-auto w-full max-w-2xl min-w-0 lg:mx-0">
                        <p
                            class="inline-flex items-center gap-2 rounded-full border border-orange-400/30 bg-orange-500/10 px-3 py-1.5 text-[11px] font-black tracking-[0.16em] text-orange-200 uppercase"
                        >
                            <Sparkles class="size-3.5" aria-hidden="true" />
                            Platform digital signage
                        </p>
                        <h1
                            id="judul-hero"
                            class="mt-5 text-4xl font-black tracking-tight text-balance text-white sm:text-5xl lg:text-6xl"
                        >
                            Satu dashboard untuk
                            <span class="text-orange-300">semua layar</span>
                            Anda.
                        </h1>
                        <p
                            class="mt-5 max-w-xl text-base leading-7 text-slate-300 sm:text-lg"
                        >
                            Susun playlist, atur jadwal, dan tampilkan data
                            langsung ke layar di lobi, bandara, rumah sakit,
                            atau restoran — tanpa perlu mengunjungi lokasinya.
                        </p>

                        <div
                            class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center"
                        >
                            <Link
                                :href="primaryAction.url"
                                class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 text-sm font-bold text-[#0b1220] shadow-lg shadow-orange-500/25 transition-colors hover:bg-orange-600 focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#060c18] focus-visible:outline-none"
                            >
                                {{ primaryAction.label }}
                                <ArrowRight class="size-4" aria-hidden="true" />
                            </Link>
                            <Link
                                :href="secondaryAction.url"
                                class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 text-sm font-bold text-white transition-colors hover:border-white/30 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#060c18] focus-visible:outline-none"
                            >
                                {{ secondaryAction.label }}
                            </Link>
                        </div>

                        <ul
                            class="mt-8 flex flex-wrap items-center gap-x-6 gap-y-2 text-sm font-semibold text-slate-400"
                        >
                            <li class="flex items-center gap-2">
                                <Check
                                    class="size-4 text-emerald-400"
                                    aria-hidden="true"
                                />
                                Mulai gratis
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="size-4 text-emerald-400"
                                    aria-hidden="true"
                                />
                                Tanpa kartu kredit
                            </li>
                            <li class="flex items-center gap-2">
                                <Check
                                    class="size-4 text-emerald-400"
                                    aria-hidden="true"
                                />
                                Pratinjau di browser
                            </li>
                        </ul>
                    </div>

                    <div class="mx-auto w-full max-w-3xl min-w-0 xl:max-w-none">
                        <ScreenShowcase />
                    </div>
                </div>

                <p
                    class="relative mx-auto hidden shrink-0 items-center gap-1.5 pb-6 text-[11px] font-black tracking-[0.18em] text-slate-500 uppercase xl:flex"
                    aria-hidden="true"
                >
                    Gulir
                    <ChevronDown
                        class="size-3.5 animate-bounce motion-reduce:animate-none"
                    />
                </p>
            </section>

            <section
                id="pratinjau"
                aria-label="Pratinjau tampilan layar"
                class="h-dvh snap-start overflow-hidden border-t border-white/5 bg-[#050b18]"
            >
                <ScreenShowcase bare />
            </section>

            <section
                id="fitur"
                aria-labelledby="judul-fitur"
                class="flex h-dvh snap-start flex-col overflow-y-auto border-t border-white/5"
            >
                <div
                    class="mx-auto my-auto w-full max-w-7xl px-[clamp(1.25rem,3vw,2.5rem)] py-[clamp(1rem,4.5dvh,4rem)]"
                >
                    <div class="max-w-2xl">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-orange-300 uppercase"
                        >
                            Fitur
                        </p>
                        <h2
                            id="judul-fitur"
                            class="mt-[clamp(0.35rem,1.2dvh,0.75rem)] text-[clamp(1.75rem,3.4vw,2.25rem)] font-black tracking-tight text-balance text-white"
                        >
                            Semua yang dibutuhkan untuk mengelola layar.
                        </h2>
                        <p
                            class="mt-[clamp(0.5rem,1.4dvh,1rem)] text-base leading-[clamp(1.4rem,3dvh,1.75rem)] text-slate-400"
                        >
                            Dari unggah media sampai pemantauan perangkat —
                            dikerjakan dari satu tempat, oleh tim yang sama.
                        </p>
                    </div>

                    <ul
                        class="mt-[clamp(1rem,3.4dvh,2.5rem)] grid gap-[clamp(0.5rem,1.5dvh,1rem)] sm:grid-cols-3"
                    >
                        <li
                            v-for="feature in features"
                            :key="feature.title"
                            class="rounded-2xl border border-white/10 bg-white/[0.03] p-[clamp(0.8rem,2dvh,1.5rem)] transition-colors hover:border-white/20 hover:bg-white/[0.06]"
                        >
                            <span
                                class="grid size-[clamp(2.25rem,5dvh,2.75rem)] place-items-center rounded-xl bg-orange-500/15 text-orange-300 ring-1 ring-orange-400/20"
                            >
                                <component
                                    :is="feature.icon"
                                    class="size-[clamp(1rem,2.4dvh,1.25rem)]"
                                    aria-hidden="true"
                                />
                            </span>
                            <h3
                                class="mt-[clamp(0.4rem,1.5dvh,1.25rem)] text-base font-bold text-white"
                            >
                                {{ feature.title }}
                            </h3>
                            <p
                                class="mt-[clamp(0.25rem,0.7dvh,0.5rem)] text-sm leading-[clamp(1.15rem,2.6dvh,1.5rem)] text-slate-400"
                            >
                                {{ feature.description }}
                            </p>
                        </li>
                    </ul>
                </div>
            </section>

            <section
                id="cara-kerja"
                aria-labelledby="judul-cara-kerja"
                class="flex h-dvh snap-start flex-col overflow-y-auto border-y border-white/5 bg-white/[0.02]"
            >
                <div
                    class="mx-auto my-auto w-full max-w-7xl px-5 py-12 sm:px-8 sm:py-14 lg:px-10 lg:py-16"
                >
                    <div class="max-w-2xl">
                        <p
                            class="text-xs font-black tracking-[0.18em] text-orange-300 uppercase"
                        >
                            Cara kerja
                        </p>
                        <h2
                            id="judul-cara-kerja"
                            class="mt-3 text-3xl font-black tracking-tight text-balance text-white sm:text-4xl"
                        >
                            Dari konten ke layar dalam tiga langkah.
                        </h2>
                        <p class="mt-4 text-base leading-7 text-slate-400">
                            Tidak perlu instalasi di setiap perangkat. Layar
                            cukup dibuka sekali, sisanya diatur dari dashboard.
                        </p>
                    </div>

                    <ol class="mt-10 grid gap-4 sm:grid-cols-3">
                        <li
                            v-for="step in steps"
                            :key="step.number"
                            class="rounded-2xl border border-white/10 bg-[#0b1526] p-6"
                        >
                            <span
                                class="text-sm font-black text-orange-300 tabular-nums"
                            >
                                {{ step.number }}
                            </span>
                            <h3 class="mt-3 text-lg font-bold text-white">
                                {{ step.title }}
                            </h3>
                            <p class="mt-2 text-sm leading-6 text-slate-400">
                                {{ step.description }}
                            </p>
                        </li>
                    </ol>
                </div>
            </section>

            <section
                id="template"
                aria-labelledby="judul-template"
                class="flex h-dvh snap-start flex-col overflow-y-auto"
            >
                <div
                    class="mx-auto my-auto w-full max-w-7xl px-5 py-12 sm:px-8 sm:py-14 lg:px-10 lg:py-16"
                >
                    <div
                        class="flex flex-col justify-between gap-6 sm:flex-row sm:items-end"
                    >
                        <div class="max-w-2xl">
                            <p
                                class="text-xs font-black tracking-[0.18em] text-orange-300 uppercase"
                            >
                                Template
                            </p>
                            <h2
                                id="judul-template"
                                class="mt-3 text-3xl font-black tracking-tight text-balance text-white sm:text-4xl"
                            >
                                Pilih tampilan, hubungkan datanya.
                            </h2>
                            <p class="mt-4 text-base leading-7 text-slate-400">
                                Template siap pakai untuk papan informasi yang
                                paling sering dibutuhkan di ruang publik.
                            </p>
                        </div>
                        <Link
                            :href="primaryAction.url"
                            class="inline-flex h-11 shrink-0 items-center justify-center gap-2 self-start rounded-xl border border-white/15 bg-white/5 px-5 text-sm font-bold text-white transition-colors hover:border-white/30 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:outline-none sm:self-auto"
                        >
                            {{ primaryAction.label }}
                            <ArrowRight class="size-4" aria-hidden="true" />
                        </Link>
                    </div>

                    <ul class="mt-10 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <li
                            v-for="template in templates"
                            :key="template.title"
                            class="overflow-hidden rounded-2xl border border-white/10 bg-white/[0.03] transition-colors hover:border-white/20"
                        >
                            <div
                                class="relative flex h-28 flex-col justify-between bg-gradient-to-br p-3"
                                :class="template.thumb"
                            >
                                <div
                                    class="flex items-center justify-between gap-2 text-[9px] font-black tracking-[0.14em] text-white/90 uppercase"
                                >
                                    <span>{{ template.label }}</span>
                                    <component
                                        :is="template.icon"
                                        class="size-3.5"
                                        aria-hidden="true"
                                    />
                                </div>
                                <div
                                    class="flex flex-col gap-1.5"
                                    aria-hidden="true"
                                >
                                    <span
                                        class="h-1.5 w-3/4 rounded-full bg-white/40"
                                    />
                                    <span
                                        class="h-1.5 w-1/2 rounded-full bg-white/25"
                                    />
                                    <span
                                        class="h-1.5 w-2/3 rounded-full bg-white/15"
                                    />
                                </div>
                            </div>
                            <div class="p-4">
                                <h3 class="text-sm font-bold text-white">
                                    {{ template.title }}
                                </h3>
                                <p
                                    class="mt-1 text-sm leading-6 text-slate-400"
                                >
                                    {{ template.description }}
                                </p>
                            </div>
                        </li>
                    </ul>
                </div>
            </section>

            <section
                aria-labelledby="judul-cta"
                class="flex h-dvh snap-start flex-col overflow-y-auto"
            >
                <div
                    class="mx-auto my-auto w-full max-w-7xl px-5 py-12 sm:px-8 sm:py-14 lg:px-10 lg:py-16"
                >
                    <div
                        class="relative overflow-hidden rounded-3xl border border-white/10 bg-[#0b1526] px-6 py-12 text-center sm:px-12 sm:py-16"
                    >
                        <div
                            class="cta-glow pointer-events-none absolute inset-0"
                            aria-hidden="true"
                        />
                        <div class="relative">
                            <h2
                                id="judul-cta"
                                class="text-3xl font-black tracking-tight text-balance text-white sm:text-4xl"
                            >
                                Siap menampilkan informasi di layar Anda?
                            </h2>
                            <p
                                class="mx-auto mt-4 max-w-xl text-base leading-7 text-slate-300"
                            >
                                Mulai dengan satu layar hari ini. Tambah lokasi
                                dan anggota tim kapan saja.
                            </p>
                            <div
                                class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row"
                            >
                                <Link
                                    :href="primaryAction.url"
                                    class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-orange-500 px-6 text-sm font-bold text-[#0b1220] shadow-lg shadow-orange-500/25 transition-colors hover:bg-orange-600 focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0b1526] focus-visible:outline-none"
                                >
                                    {{ primaryAction.label }}
                                    <ArrowRight
                                        class="size-4"
                                        aria-hidden="true"
                                    />
                                </Link>
                                <Link
                                    v-if="!isAuthenticated"
                                    :href="login()"
                                    class="inline-flex h-12 items-center justify-center gap-2 rounded-xl border border-white/15 bg-white/5 px-6 text-sm font-bold text-white transition-colors hover:border-white/30 hover:bg-white/10 focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:outline-none"
                                >
                                    Masuk ke akun
                                </Link>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
        </main>

        <footer
            class="flex h-dvh snap-start flex-col overflow-y-auto border-t border-white/10"
        >
            <div
                class="mx-auto my-auto grid w-full max-w-7xl gap-10 px-5 py-12 sm:grid-cols-2 sm:px-8 lg:grid-cols-4 lg:px-10"
            >
                <div class="lg:col-span-2">
                    <div class="flex items-center gap-2.5">
                        <AppLogoIcon
                            class="size-8 shrink-0 object-contain"
                            width="32"
                            height="32"
                            loading="lazy"
                        />
                        <span
                            class="text-base font-black tracking-tight text-white"
                        >
                            DISI
                        </span>
                    </div>
                    <p class="mt-4 max-w-sm text-sm leading-6 text-slate-400">
                        Informasi yang jelas, di setiap layar. Kelola digital
                        signage tanpa kerumitan.
                    </p>
                </div>

                <nav aria-labelledby="judul-footer-produk">
                    <p
                        id="judul-footer-produk"
                        class="text-xs font-black tracking-[0.16em] text-slate-400 uppercase"
                    >
                        Produk
                    </p>
                    <ul class="mt-4 flex flex-col gap-2 text-sm">
                        <li>
                            <a
                                href="#pratinjau"
                                class="font-semibold text-slate-300 transition-colors hover:text-white"
                            >
                                Pratinjau layar
                            </a>
                        </li>
                        <li>
                            <a
                                href="#fitur"
                                class="font-semibold text-slate-300 transition-colors hover:text-white"
                            >
                                Fitur
                            </a>
                        </li>
                        <li>
                            <a
                                href="#cara-kerja"
                                class="font-semibold text-slate-300 transition-colors hover:text-white"
                            >
                                Cara kerja
                            </a>
                        </li>
                        <li>
                            <a
                                href="#template"
                                class="font-semibold text-slate-300 transition-colors hover:text-white"
                            >
                                Template
                            </a>
                        </li>
                    </ul>
                </nav>

                <nav aria-labelledby="judul-footer-akun">
                    <p
                        id="judul-footer-akun"
                        class="text-xs font-black tracking-[0.16em] text-slate-400 uppercase"
                    >
                        Akun
                    </p>
                    <ul class="mt-4 flex flex-col gap-2 text-sm">
                        <li v-if="!isAuthenticated">
                            <Link
                                :href="login()"
                                class="font-semibold text-slate-300 transition-colors hover:text-white"
                            >
                                Masuk
                            </Link>
                        </li>
                        <li>
                            <Link
                                :href="primaryAction.url"
                                class="font-semibold text-slate-300 transition-colors hover:text-white"
                            >
                                {{ primaryAction.label }}
                            </Link>
                        </li>
                        <li v-if="isAuthenticated">
                            <Link
                                :href="secondaryAction.url"
                                class="font-semibold text-slate-300 transition-colors hover:text-white"
                            >
                                {{ secondaryAction.label }}
                            </Link>
                        </li>
                    </ul>
                </nav>
            </div>

            <div class="border-t border-white/10">
                <p
                    class="mx-auto max-w-7xl px-5 py-5 text-xs font-medium text-slate-400 sm:px-8 lg:px-10"
                >
                    © {{ currentYear }} DISI · PT Megonesia Anugerah Nusantara.
                    Semua hak dilindungi.
                </p>
            </div>
        </footer>
    </div>
</template>

<style scoped>
.hero-glow {
    background-image:
        radial-gradient(
            70% 60% at 15% -10%,
            rgb(249 115 22 / 0.22),
            transparent 65%
        ),
        radial-gradient(
            60% 50% at 90% 0%,
            rgb(59 130 246 / 0.24),
            transparent 65%
        );
}

.hero-grid {
    background-image:
        linear-gradient(to right, rgb(148 163 184 / 0.09) 1px, transparent 1px),
        linear-gradient(to bottom, rgb(148 163 184 / 0.09) 1px, transparent 1px);
    background-size: 56px 56px;
    -webkit-mask-image: radial-gradient(
        ellipse 70% 60% at 50% 0%,
        #000 35%,
        transparent 100%
    );
    mask-image: radial-gradient(
        ellipse 70% 60% at 50% 0%,
        #000 35%,
        transparent 100%
    );
}

.cta-glow {
    background-image:
        radial-gradient(
            80% 120% at 15% 0%,
            rgb(249 115 22 / 0.3),
            transparent 60%
        ),
        radial-gradient(
            80% 120% at 85% 100%,
            rgb(59 130 246 / 0.3),
            transparent 60%
        );
}
</style>
