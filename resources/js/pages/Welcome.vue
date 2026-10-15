<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { computed, onMounted, onUnmounted, ref } from 'vue';
import { ArrowRight, Check, ChevronRight, CloudSun, Clock3, ExternalLink, Hospital, Menu, MonitorPlay, Plane, Radio, Signal, Utensils, Volume2, Wifi, X } from '@lucide/vue';
import { dashboard, login, register } from '@/routes';

const screens = [
    {
        id: 'welcome',
        label: 'Sambutan',
        kicker: 'Informasi lobby',
        title: 'Selamat datang',
        accent: 'di Jakarta.',
        subtitle: 'Sampaikan informasi penting kepada pengunjung dengan tampilan yang jelas dan hangat.',
        tone: 'screen-tone-welcome',
        primaryLabel: 'Status layar',
        primaryValue: 'Online',
        primaryDetail: 'Terhubung dan siap menampilkan konten',
        secondaryLabel: 'Playlist aktif',
        secondaryValue: 'Morning Lobby',
        secondaryDetail: '3 konten sedang dijadwalkan',
    },
    {
        id: 'weather',
        label: 'Cuaca',
        kicker: 'Data cuaca langsung',
        title: 'Cuaca hari ini',
        accent: '30° di Jakarta.',
        subtitle: 'Cerah berawan. Informasi diperbarui otomatis agar selalu relevan bagi pengunjung.',
        tone: 'screen-tone-weather',
        primaryLabel: 'Suhu sekarang',
        primaryValue: '30°',
        primaryDetail: 'Terasa seperti 32°',
        secondaryLabel: 'Kondisi',
        secondaryValue: 'Cerah berawan',
        secondaryDetail: 'Kelembapan 62% · Angin 2 km/jam',
    },
    {
        id: 'flight',
        label: 'Penerbangan',
        kicker: 'Jadwal keberangkatan',
        title: 'GA 204',
        accent: 'siap boarding.',
        subtitle: 'Jakarta menuju Denpasar. Informasi status dan waktu tampil dalam sekali lihat.',
        tone: 'screen-tone-flight',
        primaryLabel: 'Status',
        primaryValue: 'Boarding',
        primaryDetail: 'Gate 12 · Tepat waktu',
        secondaryLabel: 'Keberangkatan',
        secondaryValue: '16:55',
        secondaryDetail: 'Jakarta → Denpasar',
    },
    {
        id: 'airport',
        label: 'Airport',
        kicker: 'Informasi keberangkatan',
        title: 'Departures',
        accent: 'Soekarno-Hatta.',
        subtitle: 'Jadwal penerbangan dan promosi destinasi dalam satu layar besar.',
        tone: 'screen-tone-airport',
    },
    {
        id: 'hospital',
        label: 'Rumah Sakit',
        kicker: 'Panggilan antrean',
        title: 'A024',
        accent: 'Loket 01.',
        subtitle: 'Informasi antrean pelayanan untuk ruang tunggu rumah sakit.',
        tone: 'screen-tone-hospital',
    },
];

const airportFlights = [
    { time: '16:40', flight: 'GA 204', airline: 'Garuda Indonesia', destination: 'Denpasar', gate: '12', status: 'Boarding', statusClass: 'airport-status-boarding' },
    { time: '16:55', flight: 'QZ 752', airline: 'AirAsia', destination: 'Singapore', gate: '08', status: 'On time', statusClass: 'airport-status-ontime' },
    { time: '17:10', flight: 'ID 7281', airline: 'Batik Air', destination: 'Surabaya', gate: '15', status: 'Gate open', statusClass: 'airport-status-gate' },
    { time: '17:25', flight: 'JT 610', airline: 'Lion Air', destination: 'Makassar', gate: '21', status: 'On time', statusClass: 'airport-status-ontime' },
    { time: '17:40', flight: 'SQ 965', airline: 'Singapore Airlines', destination: 'Singapore', gate: '06', status: 'Check-in', statusClass: 'airport-status-checkin' },
    { time: '17:55', flight: 'MH 720', airline: 'Malaysia Airlines', destination: 'Kuala Lumpur', gate: '11', status: 'Final call', statusClass: 'airport-status-boarding' },
    { time: '18:05', flight: 'GA 822', airline: 'Garuda Indonesia', destination: 'Bangkok', gate: '14', status: 'On time', statusClass: 'airport-status-ontime' },
    { time: '18:20', flight: 'QG 710', airline: 'Citilink', destination: 'Yogyakarta', gate: '09', status: 'Gate open', statusClass: 'airport-status-gate' },
    { time: '18:35', flight: 'EK 357', airline: 'Emirates', destination: 'Dubai', gate: '03', status: 'Delayed', statusClass: 'airport-status-delayed' },
    { time: '18:50', flight: 'ID 6580', airline: 'Batik Air', destination: 'Labuan Bajo', gate: '17', status: 'On time', statusClass: 'airport-status-ontime' },
];

const hospitalQueues = [
    { number: 'A024', counter: '01', service: 'Poli Umum', remaining: 12, active: true },
    { number: 'B056', counter: '03', service: 'Poli Anak', remaining: 8, active: false },
    { number: 'C008', counter: '02', service: 'Kasir', remaining: 5, active: false },
    { number: 'D055', counter: '03', service: 'Farmasi', remaining: 16, active: false },
    { number: 'E012', counter: '04', service: 'Laboratorium', remaining: 7, active: false },
    { number: 'F031', counter: '05', service: 'Radiologi', remaining: 9, active: false },
    { number: 'G017', counter: '06', service: 'Layanan BPJS', remaining: 14, active: false },
    { number: 'H042', counter: '07', service: 'Medical Check Up', remaining: 6, active: false },
];

const activeScreenIndex = ref(0);
const mobileMenuOpen = ref(false);
const activeScreen = computed(() => screens[activeScreenIndex.value]);
let rotationTimer: number | undefined;

function startRotation() {
    if (rotationTimer) window.clearInterval(rotationTimer);
    rotationTimer = window.setInterval(() => {
        activeScreenIndex.value = (activeScreenIndex.value + 1) % screens.length;
    }, 6000);
}

function selectScreen(index: number) {
    activeScreenIndex.value = index;
    startRotation();
}

function scrollToSection(id: string) {
    mobileMenuOpen.value = false;
    document.getElementById(id)?.scrollIntoView({ behavior: 'smooth', block: 'start' });
}

onMounted(startRotation);
onUnmounted(() => {
    if (rotationTimer) window.clearInterval(rotationTimer);
});
</script>

<template>
    <Head title="Digital signage yang terasa hidup" />

    <div class="page-shell min-h-screen overflow-hidden bg-slate-950 text-slate-950">
        <header class="page-header relative z-50 mx-auto flex w-full max-w-7xl items-center justify-between px-5 py-5 sm:px-8 lg:px-10">
            <Link href="/" class="flex items-center gap-3 text-white"><span class="grid size-10 place-items-center rounded-xl bg-gradient-to-br from-cyan-300 to-emerald-300 text-slate-950 shadow-lg shadow-cyan-500/20"><MonitorPlay class="size-5" /></span><span class="text-lg font-black tracking-tight">DISI</span></Link>
            <nav class="desktop-nav items-center gap-1 rounded-2xl border border-white/10 bg-white/[0.06] p-1 text-sm font-semibold text-slate-300"><a href="#screens" class="rounded-xl px-3 py-2 transition hover:bg-white/10 hover:text-white" @click.prevent="scrollToSection('screens')">Screens</a><a href="#templates" class="rounded-xl px-3 py-2 transition hover:bg-white/10 hover:text-white" @click.prevent="scrollToSection('templates')">Templates</a><a href="#workflow" class="rounded-xl px-3 py-2 transition hover:bg-white/10 hover:text-white" @click.prevent="scrollToSection('workflow')">Cara kerja</a></nav>
            <div class="flex items-center gap-2"><Link :href="login()" class="desktop-auth-link px-3 py-2 text-sm font-semibold text-slate-300 transition hover:text-white">Masuk</Link><Link :href="register()" class="desktop-cta header-cta items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-bold transition">Mulai gratis <ArrowRight class="size-4" /></Link><button class="mobile-menu-button size-10 place-items-center rounded-xl border border-white/15 text-white transition hover:bg-white/10" aria-label="Buka menu" :aria-expanded="mobileMenuOpen" @click="mobileMenuOpen = !mobileMenuOpen"><X v-if="mobileMenuOpen" class="size-5" /><Menu v-else class="size-5" /></button></div>
            <nav v-if="mobileMenuOpen" class="mobile-nav absolute left-5 right-5 top-full mt-2 grid gap-1 rounded-2xl border border-white/10 bg-slate-900/95 p-2 text-sm font-semibold text-slate-300 shadow-2xl backdrop-blur-xl sm:left-8 sm:right-8"><a href="#screens" class="rounded-xl px-4 py-3 hover:bg-white/10 hover:text-white" @click.prevent="scrollToSection('screens')">Screens</a><a href="#templates" class="rounded-xl px-4 py-3 hover:bg-white/10 hover:text-white" @click.prevent="scrollToSection('templates')">Templates</a><a href="#workflow" class="rounded-xl px-4 py-3 hover:bg-white/10 hover:text-white" @click.prevent="scrollToSection('workflow')">Cara kerja</a><Link :href="login()" class="rounded-xl px-4 py-3 hover:bg-white/10 hover:text-white">Masuk</Link><Link :href="register()" class="header-cta mt-1 inline-flex items-center justify-center gap-2 rounded-xl px-4 py-3 font-bold">Mulai gratis <ArrowRight class="size-4" /></Link></nav>
        </header>

        <main class="snap-page w-full bg-gradient-to-b from-slate-950 via-slate-950 to-slate-900">
            <section id="screens" class="snap-section snap-screen relative flex min-h-0 w-full flex-col overflow-hidden border-y border-white/15 bg-slate-950 shadow-2xl shadow-cyan-950/40 transition-colors duration-700" :class="activeScreen.tone">
                <svg class="pointer-events-none absolute inset-0 h-full w-full opacity-30" viewBox="0 0 1200 720" fill="none" aria-hidden="true"><defs><linearGradient id="signalGradient" x1="0" y1="0" x2="1" y2="1"><stop stop-color="#67e8f9" /><stop offset="1" stop-color="#fef08a" /></linearGradient></defs><path d="M-40 520C180 390 260 650 470 500S750 280 1240 430" stroke="url(#signalGradient)" stroke-width="2" /><path d="M-40 570C170 440 270 690 490 540S770 330 1240 480" stroke="url(#signalGradient)" stroke-width="1" /><circle cx="925" cy="146" r="4" fill="#fef08a" /><circle cx="1000" cy="116" r="2" fill="#67e8f9" /></svg>
                <div class="player-statusbar relative flex flex-wrap items-center justify-between gap-4 border-b border-white/15 px-5 py-4 text-white backdrop-blur sm:px-7"><div class="flex items-center gap-4"><span class="flex items-center gap-2 text-xs font-black text-cyan-200"><Radio class="size-4" /> DISI PLAYER</span><span class="hidden text-sm font-semibold text-white/80 sm:inline">Layar Lobby 01</span></div><div class="flex items-center gap-3 text-xs font-bold text-white"><div class="flex items-center gap-1.5"><Wifi class="size-4 text-emerald-300" /> Terhubung</div><span class="hidden text-white/70 sm:inline">16:42</span></div></div>
                <div class="player-tabsbar screen-tabs relative flex flex-nowrap items-center gap-2 overflow-x-auto border-b border-white/15 px-5 py-3 sm:px-7"><span class="mr-2 hidden shrink-0 text-xs font-bold text-white/70 sm:inline">Contoh tampilan</span><button v-for="(screen, index) in screens" :key="screen.id" type="button" class="min-h-10 shrink-0 rounded-full px-4 py-2 text-xs font-bold transition focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-cyan-300" :aria-pressed="activeScreenIndex === index" :class="activeScreenIndex === index ? 'screen-tab-active' : 'screen-tab-inactive'" @click="selectScreen(index)">{{ screen.label }}</button></div>

                <div class="player-layout relative grid min-h-0 flex-1">
                    <template v-if="activeScreen.id !== 'airport' && activeScreen.id !== 'hospital'">
                        <Transition name="screen-fade" mode="out-in"><div :key="activeScreen.id" class="screen-copy relative flex min-h-0 flex-col justify-between overflow-hidden bg-white/10 p-6 sm:p-10 lg:p-12" :class="activeScreen.tone"><div class="relative z-10 max-w-2xl"><div class="screen-copy-muted mb-5 flex items-center gap-2 text-xs font-black"><Signal class="size-4" /> {{ activeScreen.kicker }}</div><h1 class="max-w-xl text-4xl font-black leading-tight sm:text-6xl lg:text-7xl">{{ activeScreen.title }}<br /><span>{{ activeScreen.accent }}</span></h1><p class="screen-copy-muted mt-5 max-w-lg text-sm font-semibold leading-6 sm:text-lg sm:leading-7">{{ activeScreen.subtitle }}</p></div><div class="screen-copy-muted relative z-10 flex items-center gap-3 pt-6 text-xs font-bold"><span class="size-2 animate-pulse rounded-full bg-slate-950" /><span>Tampilan {{ activeScreenIndex + 1 }} dari {{ screens.length }}</span></div><svg class="absolute -bottom-8 -right-8 h-56 w-[75%] opacity-30" viewBox="0 0 600 220" fill="none" aria-hidden="true"><path d="M0 180C120 40 190 240 310 120S470 50 620 20" stroke="#0f172a" stroke-width="3" /><path d="M0 205C120 65 190 265 310 145S470 75 620 45" stroke="white" stroke-opacity=".45" stroke-width="2" /></svg></div></Transition>
                        <aside class="player-aside grid grid-cols-2 gap-3 bg-slate-950/90 p-4 sm:p-6"><div class="info-card-primary flex min-h-0 flex-col justify-between rounded-2xl p-4 shadow-lg shadow-slate-950/20 sm:min-h-52 sm:p-5"><div class="flex items-center justify-between"><CloudSun v-if="activeScreen.id === 'weather'" class="size-6 sm:size-7" /><Plane v-else-if="activeScreen.id === 'flight'" class="size-6 sm:size-7" /><MonitorPlay v-else class="size-6 sm:size-7" /><span class="info-card-badge rounded-full px-2.5 py-1 text-[10px] font-black">LIVE</span></div><div><p class="info-card-muted text-xs font-bold sm:text-sm">{{ activeScreen.primaryLabel }}</p><p class="mt-1 text-2xl font-black sm:text-4xl">{{ activeScreen.primaryValue }}</p><p class="info-card-muted mt-2 text-[11px] font-semibold sm:text-xs">{{ activeScreen.primaryDetail }}</p></div></div><div class="info-card-secondary flex min-h-0 flex-col justify-between rounded-2xl p-4 shadow-lg shadow-slate-950/20 sm:min-h-52 sm:p-5"><div class="flex items-center justify-between"><Clock3 class="size-6 sm:size-7" /><span class="info-card-muted text-[10px] font-black sm:text-xs">INFO</span></div><div><p class="info-card-muted text-xs font-bold sm:text-sm">{{ activeScreen.secondaryLabel }}</p><p class="mt-1 text-xl font-black sm:text-3xl">{{ activeScreen.secondaryValue }}</p><p class="info-card-muted mt-2 text-[11px] font-semibold sm:text-xs">{{ activeScreen.secondaryDetail }}</p></div></div></aside>
                    </template>

                    <Transition v-else-if="activeScreen.id === 'airport'" name="screen-fade" mode="out-in">
                        <div :key="activeScreen.id" class="airport-screen">
                            <div class="airport-board">
                                <div class="airport-titlebar">
                                    <span class="airport-title"><Plane class="size-5" /> Keberangkatan</span>
                                    <span class="airport-clock">Soekarno-Hatta · 16:42</span>
                                </div>
                                <div class="airport-columns" aria-hidden="true"><span>Waktu</span><span>Maskapai / Penerbangan</span><span>Tujuan</span><span>Gate</span><span>Status</span></div>
                                <div class="airport-rows">
                                    <div v-for="flight in airportFlights" :key="flight.flight" class="airport-row">
                                        <strong>{{ flight.time }}</strong>
                                        <span class="airport-flight-cell"><strong class="airport-flight">{{ flight.flight }}</strong><small>{{ flight.airline }}</small></span>
                                        <span class="airport-destination">{{ flight.destination }}</span>
                                        <strong>{{ flight.gate }}</strong>
                                        <span class="airport-status" :class="flight.statusClass">{{ flight.status }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="airport-promo">
                                <div class="airport-promo-copy"><span>Wonderful Indonesia</span><h2>Terbang lebih jauh.</h2><p>Temukan lebih banyak destinasi dari Jakarta.</p></div>
                                <svg class="airport-plane-art" viewBox="0 0 760 390" fill="none" aria-hidden="true">
                                    <circle cx="585" cy="78" r="54" fill="#fde68a" />
                                    <path d="M-20 315C120 250 205 338 342 286C485 232 584 132 790 146V410H-20V315Z" fill="#0e7490" opacity=".34" />
                                    <path d="M-20 340C155 280 250 365 398 308C540 254 625 190 790 202V410H-20V340Z" fill="#164e63" opacity=".46" />
                                    <path d="M140 218L610 145C637 141 653 151 641 161L467 208L585 273L532 286L385 226L236 257L186 302L142 307L173 255L89 244L67 220L140 218Z" fill="#f8fafc" />
                                    <path d="M385 226L467 208L585 273L532 286L385 226Z" fill="#cbd5e1" />
                                </svg>
                                <span class="airport-promo-tag">Jakarta · Bali · Singapore</span>
                            </div>
                        </div>
                    </Transition>

                    <Transition v-else name="screen-fade" mode="out-in">
                        <div :key="activeScreen.id" class="hospital-screen">
                            <header class="hospital-header">
                                <div class="hospital-brand"><span class="hospital-mark"><Hospital class="size-6" /></span><div><strong>RS Sentosa Jakarta</strong><small>Pelayanan cepat, aman, dan ramah</small></div></div>
                                <div class="hospital-time"><span>Rabu · 15 Oktober 2026</span><strong>16:42</strong></div>
                            </header>
                            <div class="hospital-content">
                                <section class="hospital-queue-board">
                                    <div class="hospital-board-title"><div><span>Antrean pelayanan</span><strong>Silakan perhatikan nomor Anda</strong></div><span class="hospital-live">LIVE</span></div>
                                    <div class="hospital-columns" aria-hidden="true"><span>Nomor</span><span>Pelayanan</span><span>Loket</span><span>Sisa</span></div>
                                    <div class="hospital-rows">
                                        <div v-for="queue in hospitalQueues" :key="queue.number" class="hospital-row" :class="{ 'hospital-row-active': queue.active }">
                                            <strong>{{ queue.number }}</strong><span>{{ queue.service }}</span><b>{{ queue.counter }}</b><small>{{ queue.remaining }}</small>
                                        </div>
                                    </div>
                                </section>
                                <aside class="hospital-now">
                                    <div class="hospital-call-label"><Volume2 class="size-5" /> Sedang dipanggil</div>
                                    <strong class="hospital-current-number">A024</strong>
                                    <div class="hospital-counter-label">Silakan menuju</div>
                                    <div class="hospital-current-counter">Loket 01</div>
                                    <div class="hospital-current-service">Poli Umum</div>
                                    <p>Siapkan dokumen pendaftaran dan kartu identitas Anda.</p>
                                </aside>
                            </div>
                        </div>
                    </Transition>
                </div>
                <div class="relative flex items-center gap-3 border-t border-white/15 bg-slate-950 px-5 py-4 text-sm font-semibold text-white sm:px-7"><span class="rounded-full bg-cyan-300 px-2.5 py-1 text-[10px] font-black text-slate-950">INFO</span><span class="truncate">Semua informasi penting tampil jelas dan diperbarui otomatis.</span><ChevronRight class="ml-auto size-4 shrink-0 text-cyan-200" /></div>
            </section>

            <section id="workflow" class="snap-section mx-auto grid max-w-7xl gap-8 px-5 py-20 sm:px-8 lg:grid-cols-[0.8fr_1.2fr] lg:items-center lg:px-10"><div><p class="text-sm font-black text-cyan-300">3 langkah sederhana</p><h2 class="mt-4 max-w-xl text-4xl font-black leading-tight text-white sm:text-5xl">Dari konten ke layar dalam beberapa menit.</h2><p class="mt-5 max-w-lg text-base leading-7 text-slate-300">Pilih layar, tambahkan informasi, lalu tentukan kapan konten ditampilkan.</p></div><div class="grid gap-3 sm:grid-cols-3"><div class="rounded-2xl border border-white/15 bg-white/[0.08] p-5 text-white"><MonitorPlay class="size-6 text-cyan-300" /><p class="mt-8 text-lg font-black">1. Pilih layar</p><p class="mt-2 text-sm leading-6 text-slate-300">Kelola layar di setiap lokasi dari satu workspace.</p></div><div class="rounded-2xl border border-white/15 bg-white/[0.08] p-5 text-white"><CloudSun class="size-6 text-emerald-300" /><p class="mt-8 text-lg font-black">2. Isi konten</p><p class="mt-2 text-sm leading-6 text-slate-300">Gunakan media, data API, cuaca, atau ticker.</p></div><div class="rounded-2xl border border-white/15 bg-white/[0.08] p-5 text-white"><Clock3 class="size-6 text-yellow-300" /><p class="mt-8 text-lg font-black">3. Jadwalkan</p><p class="mt-2 text-sm leading-6 text-slate-300">Tampilkan konten yang tepat pada waktu yang tepat.</p></div></div></section>

            <section id="templates" class="snap-section mx-auto flex max-w-7xl flex-col justify-center rounded-[2rem] bg-gradient-to-br from-cyan-200 via-emerald-200 to-yellow-100 p-6 sm:p-10"><div class="flex flex-col justify-between gap-5 sm:flex-row sm:items-end"><div><p class="text-sm font-black text-slate-700">Template siap pakai</p><h2 class="mt-3 text-3xl font-black leading-tight text-slate-950 sm:text-4xl">Pilih tampilan.<br />Hubungkan datanya.</h2></div><Link :href="register()" class="inline-flex min-h-11 items-center gap-2 self-start rounded-xl bg-slate-950 px-4 py-3 text-sm font-bold text-white transition hover:bg-slate-800 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-slate-950 focus-visible:ring-offset-2 sm:self-auto">Lihat template <ArrowRight class="size-4" /></Link></div><div class="mt-8 grid gap-3 sm:grid-cols-2 lg:grid-cols-4"><div class="rounded-2xl bg-gradient-to-br from-slate-950 to-sky-900 p-5 text-white"><Plane class="size-6 text-cyan-300" /><p class="mt-12 font-black">Papan penerbangan</p><p class="mt-1 text-sm text-white/80">Status dan jadwal langsung.</p></div><div class="rounded-2xl bg-gradient-to-br from-rose-400 to-orange-300 p-5 text-slate-950"><Utensils class="size-6" /><p class="mt-12 font-black">Menu restoran</p><p class="mt-1 text-sm text-slate-800">Menu yang mudah dibaca.</p></div><div class="rounded-2xl bg-gradient-to-br from-cyan-200 to-teal-300 p-5 text-slate-950"><CloudSun class="size-6" /><p class="mt-12 font-black">Cuaca langsung</p><p class="mt-1 text-sm text-slate-800">Selalu diperbarui otomatis.</p></div><div class="rounded-2xl bg-gradient-to-br from-yellow-200 to-amber-400 p-5 text-slate-950"><ExternalLink class="size-6" /><p class="mt-12 font-black">Template kustom</p><p class="mt-1 text-sm text-slate-800">Sesuaikan dengan kebutuhan.</p></div></div></section>

            <footer class="mx-auto flex max-w-7xl flex-col gap-5 border-t border-white/10 px-5 py-8 text-sm text-slate-300 sm:flex-row sm:items-center sm:justify-between sm:px-8 lg:px-10"><p>© {{ new Date().getFullYear() }} DISI. Informasi yang jelas, di setiap layar.</p><div class="flex items-center gap-5"><Link v-if="$page.props.auth?.user" :href="dashboard()" class="font-semibold text-white hover:text-cyan-300">Dashboard</Link><span class="flex items-center gap-2"><Check class="size-4 text-emerald-300" /> Siap untuk tim</span></div></footer>
        </main>
    </div>
</template>

<style>
.screen-fade-enter-active,
.screen-fade-leave-active { transition: opacity 0.5s ease, transform 0.5s ease; }
.screen-fade-enter-from { opacity: 0; transform: translateY(18px) scale(0.985); }
.screen-fade-leave-to { opacity: 0; transform: translateY(-14px) scale(0.985); }
.screen-tone-welcome { background-image: linear-gradient(135deg, #fb7185 0%, #fb923c 52%, #fcd34d 100%); }
.screen-tone-weather { background-image: linear-gradient(135deg, #67e8f9 0%, #5eead4 52%, #86efac 100%); }
.screen-tone-flight { background-image: linear-gradient(135deg, #fef08a 0%, #fdba74 52%, #fda4af 100%); }
.screen-tone-airport { background: #020617; }
.screen-tone-hospital { background: #ecfeff; }
.screen-copy { color: #0f172a; }
.screen-copy-muted { color: #334155; }
.info-card-primary { background: #a5f3fc; color: #0f172a; }
.info-card-secondary { background: #fde68a; color: #0f172a; }
.info-card-muted { color: #334155; }
.info-card-badge { background: #0f172a; color: #ffffff; }
.header-cta,
.screen-tab-active { background: #ffffff; color: #0f172a; }
.header-cta:hover { background: #a5f3fc; }
.screen-tab-inactive { color: #ffffff; }
.screen-tab-inactive:hover { background: rgb(255 255 255 / 0.15); }
.player-statusbar { background: rgb(2 6 23 / 0.94); }
.player-tabsbar { background: rgb(15 23 42 / 0.96); }
.screen-tabs { scrollbar-width: none; }
.screen-tabs::-webkit-scrollbar { display: none; }
.airport-screen { grid-column: 1 / -1; display: grid; min-height: 0; grid-template-rows: minmax(0, 1.35fr) minmax(150px, 0.65fr); overflow: hidden; background: #020617; color: #f8fafc; }
.airport-board { display: flex; min-height: 0; flex-direction: column; background: #050b18; }
.airport-titlebar { display: flex; min-height: 56px; align-items: center; justify-content: space-between; gap: 16px; background: #fbbf24; padding: 12px 20px; color: #0f172a; }
.airport-title { display: flex; align-items: center; gap: 10px; font-size: 16px; font-weight: 900; text-transform: uppercase; }
.airport-clock { font-size: 12px; font-weight: 800; }
.airport-columns,
.airport-row { display: grid; grid-template-columns: 0.7fr 1.35fr 1.2fr 0.5fr 1fr; align-items: center; gap: 10px; }
.airport-columns { padding: 10px 18px; background: #111827; color: #94a3b8; font-size: 10px; font-weight: 800; }
.airport-rows { display: grid; min-height: 0; flex: 1; grid-template-rows: repeat(10, minmax(0, 1fr)); }
.airport-row { border-top: 1px solid #1e293b; padding: 4px 14px; font-size: 11px; }
.airport-flight { color: #fbbf24; font-weight: 900; }
.airport-flight-cell { display: flex; min-width: 0; flex-direction: column; line-height: 1.1; }
.airport-flight-cell small { overflow: hidden; margin-top: 2px; color: #94a3b8; font-size: 8px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
.airport-destination { overflow: hidden; color: #e2e8f0; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
.airport-status { justify-self: start; border-radius: 999px; padding: 5px 8px; font-size: 9px; font-weight: 900; white-space: nowrap; }
.airport-status-boarding { background: #fbbf24; color: #0f172a; }
.airport-status-ontime { background: #059669; color: #ffffff; }
.airport-status-gate { background: #0284c7; color: #ffffff; }
.airport-status-checkin { background: #7c3aed; color: #ffffff; }
.airport-status-delayed { background: #dc2626; color: #ffffff; }
.airport-promo { position: relative; min-height: 0; overflow: hidden; background: linear-gradient(145deg, #075985 0%, #0284c7 48%, #38bdf8 100%); }
.airport-promo-copy { position: relative; z-index: 2; max-width: 440px; padding: 28px; }
.airport-promo-copy span { color: #fef08a; font-size: 11px; font-weight: 900; text-transform: uppercase; }
.airport-promo-copy h2 { margin-top: 8px; color: #ffffff; font-size: clamp(28px, 4vw, 58px); font-weight: 900; line-height: 1; }
.airport-promo-copy p { margin-top: 12px; max-width: 320px; color: #e0f2fe; font-size: 14px; font-weight: 700; }
.airport-plane-art { position: absolute; right: -5%; bottom: -4%; width: 86%; }
.airport-promo-tag { position: absolute; right: 20px; bottom: 18px; z-index: 2; border-radius: 999px; background: rgb(2 6 23 / 0.76); padding: 8px 12px; color: #ffffff; font-size: 10px; font-weight: 800; }
.hospital-screen { grid-column: 1 / -1; display: grid; min-height: 0; grid-template-rows: auto minmax(0, 1fr); overflow: hidden; background: #ecfeff; color: #0f172a; }
.hospital-header { display: flex; min-height: 68px; align-items: center; justify-content: space-between; gap: 16px; border-bottom: 1px solid #bae6fd; background: #ffffff; padding: 10px 18px; }
.hospital-brand { display: flex; min-width: 0; align-items: center; gap: 10px; }
.hospital-mark { display: grid; width: 42px; height: 42px; flex: none; place-items: center; border-radius: 10px; background: #0f766e; color: #ffffff; }
.hospital-brand div { display: flex; min-width: 0; flex-direction: column; }
.hospital-brand strong { color: #0f172a; font-size: 14px; }
.hospital-brand small { overflow: hidden; color: #475569; font-size: 9px; font-weight: 700; text-overflow: ellipsis; white-space: nowrap; }
.hospital-time { display: flex; flex: none; flex-direction: column; align-items: flex-end; }
.hospital-time span { color: #475569; font-size: 8px; font-weight: 700; }
.hospital-time strong { color: #0f172a; font-size: 22px; line-height: 1; }
.hospital-content { display: grid; min-height: 0; grid-template-rows: minmax(0, 1.15fr) minmax(180px, 0.85fr); }
.hospital-queue-board { display: flex; min-height: 0; flex-direction: column; background: #f8fafc; }
.hospital-board-title { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 16px; }
.hospital-board-title div { display: flex; flex-direction: column; }
.hospital-board-title span { color: #0f766e; font-size: 10px; font-weight: 900; text-transform: uppercase; }
.hospital-board-title strong { color: #0f172a; font-size: 12px; }
.hospital-live { border-radius: 999px; background: #dcfce7; padding: 5px 9px; color: #166534 !important; font-size: 8px !important; }
.hospital-columns,
.hospital-row { display: grid; grid-template-columns: 0.8fr 1.5fr 0.55fr 0.45fr; align-items: center; gap: 8px; }
.hospital-columns { background: #e2e8f0; padding: 7px 16px; color: #475569; font-size: 8px; font-weight: 900; }
.hospital-rows { display: grid; min-height: 0; flex: 1; grid-template-rows: repeat(8, minmax(0, 1fr)); }
.hospital-row { border-top: 1px solid #e2e8f0; padding: 3px 16px; color: #334155; font-size: 10px; }
.hospital-row > strong { color: #0f766e; font-size: 14px; }
.hospital-row > span { overflow: hidden; font-weight: 800; text-overflow: ellipsis; white-space: nowrap; }
.hospital-row > b { justify-self: start; border-radius: 7px; background: #ccfbf1; padding: 4px 8px; color: #115e59; }
.hospital-row > small { justify-self: center; color: #475569; font-weight: 900; }
.hospital-row-active { background: #dcfce7; }
.hospital-row-active > strong { color: #166534; }
.hospital-now { display: flex; min-height: 0; flex-direction: column; align-items: center; justify-content: center; background: linear-gradient(145deg, #0e7490 0%, #0891b2 52%, #22d3ee 100%); padding: 18px; text-align: center; color: #ffffff; }
.hospital-call-label { display: flex; align-items: center; gap: 8px; font-size: 10px; font-weight: 900; text-transform: uppercase; }
.hospital-current-number { margin-top: 6px; font-size: clamp(42px, 8vw, 96px); line-height: 1; }
.hospital-counter-label { margin-top: 8px; color: #cffafe; font-size: 10px; font-weight: 700; }
.hospital-current-counter { margin-top: 4px; border-radius: 12px; background: #ffffff; padding: 7px 16px; color: #0e7490; font-size: 20px; font-weight: 900; }
.hospital-current-service { margin-top: 7px; color: #ffffff; font-size: 13px; font-weight: 900; }
.hospital-now p { margin-top: 8px; max-width: 320px; color: #cffafe; font-size: 9px; font-weight: 700; }
.page-shell { display: flex; height: 100dvh; flex-direction: column; }
.page-header { flex: none; }
.desktop-nav,
.desktop-auth-link,
.desktop-cta { display: none; }
.mobile-menu-button { display: grid; }
.snap-page { min-height: 0; flex: 1; overflow-y: auto; scrollbar-width: none; scroll-snap-type: y mandatory; scroll-behavior: smooth; }
.snap-page::-webkit-scrollbar { display: none; }
.snap-section { min-height: 100%; scroll-snap-align: start; scroll-snap-stop: always; }
.snap-screen { height: 100%; }
@media (min-width: 640px) {
    .desktop-auth-link,
    .desktop-cta { display: inline-flex; }
}
@media (min-width: 768px) {
    .desktop-nav { display: flex; }
    .mobile-menu-button,
    .mobile-nav { display: none; }
}
@media (min-width: 1024px) {
    .player-layout { grid-template-columns: minmax(0, 1.35fr) minmax(320px, 0.65fr); }
    .player-aside { grid-template-columns: minmax(0, 1fr); }
    .airport-screen { grid-template-columns: minmax(500px, 0.9fr) minmax(0, 1.1fr); grid-template-rows: minmax(0, 1fr); }
    .airport-titlebar { min-height: 68px; padding: 14px 24px; }
    .airport-title { font-size: 20px; }
    .airport-row { padding: 7px 24px; font-size: 13px; }
    .airport-flight-cell small { font-size: 10px; }
    .airport-columns { padding: 11px 24px; }
    .airport-promo-copy { padding: 42px; }
    .hospital-header { min-height: 84px; padding: 14px 28px; }
    .hospital-mark { width: 52px; height: 52px; }
    .hospital-brand strong { font-size: 18px; }
    .hospital-brand small { font-size: 11px; }
    .hospital-time span { font-size: 11px; }
    .hospital-time strong { font-size: 30px; }
    .hospital-content { grid-template-columns: minmax(0, 1.25fr) minmax(360px, 0.75fr); grid-template-rows: minmax(0, 1fr); }
    .hospital-board-title { padding: 18px 28px; }
    .hospital-board-title span { font-size: 12px; }
    .hospital-board-title strong { font-size: 16px; }
    .hospital-columns { padding: 10px 28px; font-size: 10px; }
    .hospital-row { padding: 5px 28px; font-size: 13px; }
    .hospital-row > strong { font-size: 22px; }
    .hospital-current-counter { font-size: 28px; }
    .hospital-current-service { font-size: 17px; }
    .hospital-now p { font-size: 11px; }
}
@media (prefers-reduced-motion: reduce) { .screen-fade-enter-active, .screen-fade-leave-active { transition-duration: 0.01ms; } }
</style>
