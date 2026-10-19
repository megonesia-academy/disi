<script setup lang="ts">
import { usePreferredReducedMotion } from '@vueuse/core';
import {
    Clock3,
    CloudSun,
    Hospital,
    MonitorPlay,
    Pause,
    Plane,
    Play,
    Signal,
    Utensils,
    Volume2,
} from '@lucide/vue';
import { computed, onMounted, onUnmounted, ref, watch } from 'vue';

const AUTOPLAY_INTERVAL = 7000;

type ScreenId =
    | 'welcome'
    | 'weather'
    | 'flight'
    | 'airport'
    | 'hospital'
    | 'restaurant';

type Tone = 'sunset' | 'ocean' | 'aurora';

interface InfoCard {
    label: string;
    value: string;
    detail: string;
}

interface GenericScreen {
    id: 'welcome' | 'weather' | 'flight';
    kicker: string;
    title: string;
    accent: string;
    subtitle: string;
    tone: Tone;
    cards: [InfoCard, InfoCard];
}

interface FlightRow {
    time: string;
    code: string;
    airline: string;
    destination: string;
    gate: string;
    status: string;
    statusClass: string;
}

interface QueueRow {
    number: string;
    service: string;
    counter: string;
    remaining: number;
    active: boolean;
}

interface MenuItem {
    name: string;
    description: string;
    price: string;
}

const tabs: { id: ScreenId; label: string }[] = [
    { id: 'welcome', label: 'Sambutan' },
    { id: 'weather', label: 'Cuaca' },
    { id: 'flight', label: 'Penerbangan' },
    { id: 'airport', label: 'Bandara' },
    { id: 'hospital', label: 'Rumah sakit' },
    { id: 'restaurant', label: 'Restoran' },
];

const genericScreens: GenericScreen[] = [
    {
        id: 'welcome',
        kicker: 'Informasi lobi',
        title: 'Selamat datang',
        accent: 'di Jakarta.',
        subtitle:
            'Sampaikan informasi penting kepada pengunjung dengan tampilan yang jelas dan hangat.',
        tone: 'sunset',
        cards: [
            {
                label: 'Status layar',
                value: 'Online',
                detail: 'Terhubung dan siap menampilkan konten',
            },
            {
                label: 'Playlist aktif',
                value: 'Morning Lobby',
                detail: '3 konten sedang dijadwalkan',
            },
        ],
    },
    {
        id: 'weather',
        kicker: 'Data cuaca langsung',
        title: 'Cuaca hari ini',
        accent: '30° di Jakarta.',
        subtitle:
            'Cerah berawan. Informasi diperbarui otomatis agar selalu relevan bagi pengunjung.',
        tone: 'ocean',
        cards: [
            {
                label: 'Suhu sekarang',
                value: '30°',
                detail: 'Terasa seperti 32°',
            },
            {
                label: 'Kondisi',
                value: 'Cerah berawan',
                detail: 'Kelembapan 62% · Angin 2 km/jam',
            },
        ],
    },
    {
        id: 'flight',
        kicker: 'Jadwal keberangkatan',
        title: 'GA 204',
        accent: 'siap boarding.',
        subtitle:
            'Jakarta menuju Denpasar. Status dan waktu tampil dalam sekali lihat.',
        tone: 'aurora',
        cards: [
            {
                label: 'Status',
                value: 'Boarding',
                detail: 'Gate 12 · Tepat waktu',
            },
            {
                label: 'Keberangkatan',
                value: '16:55',
                detail: 'Jakarta → Denpasar',
            },
        ],
    },
];

const flights: FlightRow[] = [
    {
        time: '16:40',
        code: 'GA 204',
        airline: 'Garuda Indonesia',
        destination: 'Denpasar',
        gate: '12',
        status: 'Boarding',
        statusClass: 'airport-status--boarding',
    },
    {
        time: '16:55',
        code: 'QZ 752',
        airline: 'AirAsia',
        destination: 'Singapore',
        gate: '08',
        status: 'On time',
        statusClass: 'airport-status--ontime',
    },
    {
        time: '17:10',
        code: 'ID 7281',
        airline: 'Batik Air',
        destination: 'Surabaya',
        gate: '15',
        status: 'Gate open',
        statusClass: 'airport-status--gate',
    },
    {
        time: '17:25',
        code: 'JT 610',
        airline: 'Lion Air',
        destination: 'Makassar',
        gate: '21',
        status: 'On time',
        statusClass: 'airport-status--ontime',
    },
    {
        time: '17:40',
        code: 'SQ 965',
        airline: 'Singapore Airlines',
        destination: 'Singapore',
        gate: '06',
        status: 'Check-in',
        statusClass: 'airport-status--checkin',
    },
    {
        time: '17:55',
        code: 'MH 720',
        airline: 'Malaysia Airlines',
        destination: 'Kuala Lumpur',
        gate: '11',
        status: 'Final call',
        statusClass: 'airport-status--boarding',
    },
    {
        time: '18:05',
        code: 'GA 822',
        airline: 'Garuda Indonesia',
        destination: 'Bangkok',
        gate: '14',
        status: 'On time',
        statusClass: 'airport-status--ontime',
    },
    {
        time: '18:20',
        code: 'QG 710',
        airline: 'Citilink',
        destination: 'Yogyakarta',
        gate: '09',
        status: 'Gate open',
        statusClass: 'airport-status--gate',
    },
    {
        time: '18:35',
        code: 'EK 357',
        airline: 'Emirates',
        destination: 'Dubai',
        gate: '03',
        status: 'Delayed',
        statusClass: 'airport-status--delayed',
    },
];

const queues: QueueRow[] = [
    {
        number: 'A024',
        service: 'Poli Umum',
        counter: '01',
        remaining: 12,
        active: true,
    },
    {
        number: 'B056',
        service: 'Poli Anak',
        counter: '03',
        remaining: 8,
        active: false,
    },
    {
        number: 'C008',
        service: 'Kasir',
        counter: '02',
        remaining: 5,
        active: false,
    },
    {
        number: 'D055',
        service: 'Farmasi',
        counter: '03',
        remaining: 16,
        active: false,
    },
    {
        number: 'E012',
        service: 'Laboratorium',
        counter: '04',
        remaining: 7,
        active: false,
    },
    {
        number: 'F031',
        service: 'Radiologi',
        counter: '05',
        remaining: 9,
        active: false,
    },
];

const menuItems: MenuItem[] = [
    {
        name: 'Nasi Goreng Kampung',
        description: 'Telur mata sapi, sambal terasi',
        price: '35K',
    },
    {
        name: 'Mie Ayam Spesial',
        description: 'Ayam kecap, pangsit goreng',
        price: '30K',
    },
    {
        name: 'Es Kopi Susu',
        description: 'Gula aren, susu segar',
        price: '22K',
    },
    {
        name: 'Roti Bakar Srikaya',
        description: 'Mentega, srikaya homemade',
        price: '18K',
    },
    {
        name: 'Teh Melati',
        description: 'Seduh segar, tanpa gula',
        price: '12K',
    },
];

const activeIndex = ref(0);
const isPaused = ref(false);
const clockLabel = ref('');
const dateLabel = ref('');
const tabElements = ref<HTMLButtonElement[]>([]);
const reducedMotion = usePreferredReducedMotion();

let autoplayTimer: ReturnType<typeof setInterval> | undefined;
let clockTimer: ReturnType<typeof setInterval> | undefined;

const activeId = computed<ScreenId>(() => tabs[activeIndex.value].id);
const activeTab = computed(() => tabs[activeIndex.value]);
const genericScreen = computed<GenericScreen>(() => {
    const screen = genericScreens.find(
        (candidate) => candidate.id === activeId.value,
    );

    return screen ?? genericScreens[0];
});

function updateClock(): void {
    const now = new Date();

    clockLabel.value = new Intl.DateTimeFormat('id-ID', {
        hour: '2-digit',
        minute: '2-digit',
        hour12: false,
    }).format(now);

    dateLabel.value = new Intl.DateTimeFormat('id-ID', {
        weekday: 'long',
        day: 'numeric',
        month: 'long',
        year: 'numeric',
    }).format(now);
}

function stopAutoplay(): void {
    if (autoplayTimer !== undefined) {
        window.clearInterval(autoplayTimer);
        autoplayTimer = undefined;
    }
}

function startAutoplay(): void {
    stopAutoplay();

    if (isPaused.value) {
        return;
    }

    autoplayTimer = window.setInterval(() => {
        activeIndex.value = (activeIndex.value + 1) % tabs.length;
    }, AUTOPLAY_INTERVAL);
}

function selectScreen(index: number): void {
    activeIndex.value = index;
    startAutoplay();
}

function togglePause(): void {
    isPaused.value = !isPaused.value;
}

function setTabElement(element: unknown, index: number): void {
    if (element instanceof HTMLButtonElement) {
        tabElements.value[index] = element;
    }
}

function handleTabKeydown(event: KeyboardEvent, index: number): void {
    let nextIndex: number | undefined;

    if (event.key === 'ArrowRight') {
        nextIndex = (index + 1) % tabs.length;
    } else if (event.key === 'ArrowLeft') {
        nextIndex = (index - 1 + tabs.length) % tabs.length;
    } else if (event.key === 'Home') {
        nextIndex = 0;
    } else if (event.key === 'End') {
        nextIndex = tabs.length - 1;
    }

    if (nextIndex === undefined) {
        return;
    }

    event.preventDefault();
    selectScreen(nextIndex);
    tabElements.value[nextIndex]?.focus();
}

function handleVisibilityChange(): void {
    if (document.hidden) {
        stopAutoplay();

        return;
    }

    startAutoplay();
}

watch(reducedMotion, (motion) => {
    if (motion === 'reduce') {
        isPaused.value = true;
    }
});

watch(isPaused, (paused) => {
    if (paused) {
        stopAutoplay();

        return;
    }

    startAutoplay();
});

onMounted(() => {
    updateClock();
    clockTimer = window.setInterval(updateClock, 30000);

    if (reducedMotion.value === 'reduce') {
        isPaused.value = true;
    }

    startAutoplay();
    document.addEventListener('visibilitychange', handleVisibilityChange);
});

onUnmounted(() => {
    stopAutoplay();

    if (clockTimer !== undefined) {
        window.clearInterval(clockTimer);
    }

    document.removeEventListener('visibilitychange', handleVisibilityChange);
});
</script>

<template>
    <div
        class="overflow-hidden rounded-2xl border border-white/10 bg-[#0a1424] shadow-2xl shadow-black/40"
    >
        <div
            class="flex items-center justify-between gap-3 border-b border-white/10 bg-white/[0.03] px-3 py-2.5 sm:px-4"
        >
            <div class="flex items-center gap-1.5" aria-hidden="true">
                <span class="size-2 rounded-full bg-white/20" />
                <span class="size-2 rounded-full bg-white/20" />
                <span class="size-2 rounded-full bg-white/20" />
            </div>
            <p
                class="flex items-center gap-2 text-[10px] font-black tracking-[0.16em] text-slate-400 uppercase"
            >
                <span class="relative flex size-2" aria-hidden="true">
                    <span
                        class="absolute inline-flex size-full animate-ping rounded-full bg-emerald-400 opacity-60 motion-reduce:hidden"
                    />
                    <span
                        class="relative inline-flex size-2 rounded-full bg-emerald-400"
                    />
                </span>
                TV/Screen
            </p>
            <p
                class="text-[11px] font-bold text-slate-300 tabular-nums"
                aria-hidden="true"
            >
                {{ clockLabel }}
            </p>
        </div>

        <div class="p-2 sm:p-2.5">
            <div
                id="pratinjau-layar"
                class="screen-frame"
                role="tabpanel"
                :aria-labelledby="`tab-${activeTab.id}`"
            >
                <Transition name="preview-fade" mode="out-in">
                    <div
                        v-if="activeId === 'airport'"
                        key="airport"
                        class="airport-panel"
                    >
                        <div class="airport-board">
                            <div class="airport-bar">
                                <span class="airport-bar-title">
                                    <Plane
                                        class="airport-bar-icon"
                                        aria-hidden="true"
                                    />
                                    Keberangkatan
                                </span>
                                <span class="airport-bar-clock">
                                    Soekarno-Hatta · {{ clockLabel }}
                                </span>
                            </div>
                            <div class="airport-columns" aria-hidden="true">
                                <span>Waktu</span>
                                <span>Penerbangan</span>
                                <span class="airport-destination">Tujuan</span>
                                <span>Gate</span>
                                <span>Status</span>
                            </div>
                            <div class="airport-rows">
                                <div
                                    v-for="(flight, index) in flights"
                                    :key="flight.code"
                                    class="airport-row"
                                    :class="{ 'airport-row--extra': index > 5 }"
                                >
                                    <strong>{{ flight.time }}</strong>
                                    <span class="airport-flight">
                                        <strong>{{ flight.code }}</strong>
                                        <small>{{ flight.airline }}</small>
                                    </span>
                                    <span class="airport-destination">
                                        {{ flight.destination }}
                                    </span>
                                    <strong>{{ flight.gate }}</strong>
                                    <span
                                        class="airport-status"
                                        :class="flight.statusClass"
                                    >
                                        {{ flight.status }}
                                    </span>
                                </div>
                            </div>
                        </div>
                        <div class="airport-promo">
                            <Plane
                                class="airport-promo-art"
                                aria-hidden="true"
                            />
                            <p class="airport-promo-eyebrow">
                                Wonderful Indonesia
                            </p>
                            <p class="airport-promo-title">
                                Terbang lebih jauh.
                            </p>
                            <p class="airport-promo-text">
                                Temukan lebih banyak destinasi dari Jakarta.
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="activeId === 'hospital'"
                        key="hospital"
                        class="hospital-panel"
                    >
                        <div class="hospital-board">
                            <div class="hospital-head">
                                <div class="hospital-brand">
                                    <span class="hospital-mark">
                                        <Hospital
                                            class="hospital-mark-icon"
                                            aria-hidden="true"
                                        />
                                    </span>
                                    <div class="hospital-brand-text">
                                        <strong>RS Sentosa Jakarta</strong>
                                        <small>
                                            Pelayanan cepat, aman, dan ramah
                                        </small>
                                    </div>
                                </div>
                                <div class="hospital-time">
                                    <span>{{ dateLabel }}</span>
                                    <strong>{{ clockLabel }}</strong>
                                </div>
                            </div>
                            <div class="hospital-columns" aria-hidden="true">
                                <span>Nomor</span>
                                <span>Pelayanan</span>
                                <span>Loket</span>
                                <span>Sisa</span>
                            </div>
                            <div class="hospital-rows">
                                <div
                                    v-for="(queue, index) in queues"
                                    :key="queue.number"
                                    class="hospital-row"
                                    :class="{
                                        'hospital-row--active': queue.active,
                                        'hospital-row--extra': index > 4,
                                    }"
                                >
                                    <strong>{{ queue.number }}</strong>
                                    <span>{{ queue.service }}</span>
                                    <b>{{ queue.counter }}</b>
                                    <small>{{ queue.remaining }}</small>
                                </div>
                            </div>
                        </div>
                        <div class="hospital-call">
                            <p class="hospital-call-label">
                                <Volume2
                                    class="hospital-call-icon"
                                    aria-hidden="true"
                                />
                                Sedang dipanggil
                            </p>
                            <p class="hospital-call-number">A024</p>
                            <p class="hospital-call-counter">Loket 01</p>
                            <p class="hospital-call-service">Poli Umum</p>
                            <p class="hospital-call-note">
                                Siapkan dokumen pendaftaran dan kartu identitas
                                Anda.
                            </p>
                        </div>
                    </div>

                    <div
                        v-else-if="activeId === 'restaurant'"
                        key="restaurant"
                        class="restaurant-panel"
                    >
                        <div class="restaurant-head">
                            <div class="restaurant-brand">
                                <span class="restaurant-mark">
                                    <Utensils
                                        class="restaurant-mark-icon"
                                        aria-hidden="true"
                                    />
                                </span>
                                <div class="restaurant-brand-text">
                                    <strong>Kopi Senja</strong>
                                    <small>
                                        Menu hari ini · Dine-in &amp; take away
                                    </small>
                                </div>
                            </div>
                            <div class="restaurant-time">
                                <span>{{ dateLabel }}</span>
                                <strong>{{ clockLabel }}</strong>
                            </div>
                        </div>
                        <div class="restaurant-list">
                            <div
                                v-for="item in menuItems"
                                :key="item.name"
                                class="restaurant-item"
                            >
                                <div class="restaurant-item-text">
                                    <p class="restaurant-item-name">
                                        {{ item.name }}
                                    </p>
                                    <p class="restaurant-item-desc">
                                        {{ item.description }}
                                    </p>
                                </div>
                                <span
                                    class="restaurant-item-dots"
                                    aria-hidden="true"
                                />
                                <span class="restaurant-item-price">
                                    {{ item.price }}
                                </span>
                            </div>
                        </div>
                        <div class="restaurant-foot">
                            <span>Harga sudah termasuk pajak</span>
                            <span class="restaurant-foot-note">
                                Pesan di kasir
                            </span>
                        </div>
                    </div>

                    <div
                        v-else
                        :key="activeId"
                        class="generic-panel"
                        :class="`tone-${genericScreen.tone}`"
                    >
                        <div class="generic-copy">
                            <p class="generic-kicker">
                                <Signal
                                    class="generic-icon"
                                    aria-hidden="true"
                                />
                                {{ genericScreen.kicker }}
                            </p>
                            <p class="generic-title">
                                {{ genericScreen.title }}
                                <br />
                                <span>{{ genericScreen.accent }}</span>
                            </p>
                            <p class="generic-subtitle">
                                {{ genericScreen.subtitle }}
                            </p>
                            <p class="generic-inline">
                                {{ genericScreen.cards[0].label }} ·
                                {{ genericScreen.cards[0].value }}
                            </p>
                        </div>
                        <div class="generic-cards">
                            <div class="generic-card generic-card--light">
                                <div class="generic-card-top">
                                    <CloudSun
                                        v-if="activeId === 'weather'"
                                        class="generic-card-icon"
                                        aria-hidden="true"
                                    />
                                    <Plane
                                        v-else-if="activeId === 'flight'"
                                        class="generic-card-icon"
                                        aria-hidden="true"
                                    />
                                    <MonitorPlay
                                        v-else
                                        class="generic-card-icon"
                                        aria-hidden="true"
                                    />
                                    <span class="generic-card-badge">LIVE</span>
                                </div>
                                <div>
                                    <p class="generic-card-label">
                                        {{ genericScreen.cards[0].label }}
                                    </p>
                                    <p class="generic-card-value">
                                        {{ genericScreen.cards[0].value }}
                                    </p>
                                    <p class="generic-card-detail">
                                        {{ genericScreen.cards[0].detail }}
                                    </p>
                                </div>
                            </div>
                            <div class="generic-card generic-card--dark">
                                <div class="generic-card-top">
                                    <Clock3
                                        class="generic-card-icon"
                                        aria-hidden="true"
                                    />
                                    <span class="generic-card-badge">INFO</span>
                                </div>
                                <div>
                                    <p class="generic-card-label">
                                        {{ genericScreen.cards[1].label }}
                                    </p>
                                    <p class="generic-card-value">
                                        {{ genericScreen.cards[1].value }}
                                    </p>
                                    <p class="generic-card-detail">
                                        {{ genericScreen.cards[1].detail }}
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </Transition>
            </div>
        </div>

        <div
            class="flex items-center gap-2 border-t border-white/10 bg-white/[0.02] px-2 py-2.5 sm:px-3"
        >
            <div
                class="flex min-w-0 flex-1 flex-wrap items-center gap-1.5"
                role="tablist"
                aria-label="Contoh tampilan layar"
            >
                <button
                    v-for="(tab, index) in tabs"
                    :id="`tab-${tab.id}`"
                    :key="tab.id"
                    :ref="(element) => setTabElement(element, index)"
                    type="button"
                    role="tab"
                    :aria-selected="activeIndex === index"
                    aria-controls="pratinjau-layar"
                    :tabindex="activeIndex === index ? 0 : -1"
                    class="shrink-0 rounded-full border px-3 py-1.5 text-[11px] font-bold transition-colors focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0a1424] focus-visible:outline-none sm:text-xs"
                    :class="
                        activeIndex === index
                            ? 'border-transparent bg-white text-slate-900'
                            : 'border-white/10 text-slate-300 hover:border-white/25 hover:bg-white/10 hover:text-white'
                    "
                    @click="selectScreen(index)"
                    @keydown="handleTabKeydown($event, index)"
                >
                    {{ tab.label }}
                </button>
            </div>
            <button
                type="button"
                class="flex shrink-0 items-center gap-1.5 rounded-full border border-white/10 px-2.5 py-1.5 text-[11px] font-bold text-slate-300 transition-colors hover:border-white/25 hover:bg-white/10 hover:text-white focus-visible:ring-2 focus-visible:ring-orange-300 focus-visible:ring-offset-2 focus-visible:ring-offset-[#0a1424] focus-visible:outline-none sm:text-xs"
                @click="togglePause"
            >
                <Play v-if="isPaused" class="size-3.5" aria-hidden="true" />
                <Pause v-else class="size-3.5" aria-hidden="true" />
                {{ isPaused ? 'Lanjutkan' : 'Jeda' }}
            </button>
        </div>
    </div>
</template>

<style scoped>
.screen-frame {
    position: relative;
    aspect-ratio: 16 / 10;
    overflow: hidden;
    container-type: inline-size;
    border-radius: 0.75rem;
    background: #050b18;
    color: #f8fafc;
}

.preview-fade-enter-active,
.preview-fade-leave-active {
    transition:
        opacity 180ms ease,
        transform 180ms ease;
}

.preview-fade-enter-from {
    opacity: 0;
    transform: translateY(6px);
}

.preview-fade-leave-to {
    opacity: 0;
    transform: translateY(-6px);
}

/* Generic screen: headline on a colour gradient, live data cards beside it */
.generic-panel {
    display: grid;
    height: 100%;
    grid-template-columns: minmax(0, 1fr);
    background: #050b18;
}

.generic-panel.tone-sunset {
    background-image: linear-gradient(
        135deg,
        #fdba74 0%,
        #fbbf24 55%,
        #fde68a 100%
    );
}

.generic-panel.tone-ocean {
    background-image: linear-gradient(
        135deg,
        #7dd3fc 0%,
        #22d3ee 55%,
        #5eead4 100%
    );
}

.generic-panel.tone-aurora {
    background-image: linear-gradient(
        135deg,
        #a5b4fc 0%,
        #818cf8 55%,
        #c4b5fd 100%
    );
}

.generic-copy {
    display: flex;
    min-width: 0;
    flex-direction: column;
    justify-content: center;
    gap: clamp(6px, 1.5cqw, 14px);
    padding: clamp(14px, 4cqw, 38px);
    color: #0b1220;
}

.generic-kicker {
    display: flex;
    margin: 0;
    align-items: center;
    gap: 0.4em;
    color: rgb(11 18 32 / 0.72);
    font-size: clamp(9px, 1.7cqw, 12px);
    font-weight: 900;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.generic-icon {
    width: 1.3em;
    height: 1.3em;
    flex: none;
}

.generic-title {
    margin: 0;
    font-size: clamp(18px, 6cqw, 44px);
    font-weight: 900;
    letter-spacing: -0.02em;
    line-height: 1.03;
}

.generic-subtitle {
    display: -webkit-box;
    max-width: 32ch;
    overflow: hidden;
    margin: 0;
    color: rgb(11 18 32 / 0.78);
    font-size: clamp(10px, 1.9cqw, 15px);
    font-weight: 600;
    line-height: 1.5;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 3;
}

.generic-inline {
    margin: 0;
    color: rgb(11 18 32 / 0.7);
    font-size: clamp(8px, 1.5cqw, 11px);
    font-weight: 800;
}

.generic-cards {
    display: none;
    flex-direction: column;
    gap: clamp(4px, 1cqw, 10px);
    padding: clamp(6px, 1.2cqw, 12px);
    background: #050b18;
}

.generic-card {
    display: flex;
    flex: 1;
    flex-direction: column;
    justify-content: space-between;
    gap: clamp(4px, 1cqw, 10px);
    border-radius: clamp(8px, 1.4cqw, 16px);
    padding: clamp(7px, 1.5cqw, 16px);
}

.generic-card--light {
    background: #ffffff;
    color: #0b1220;
}

.generic-card--dark {
    background: #0f1c31;
    color: #f8fafc;
}

.generic-card-top {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 6px;
}

.generic-card-icon {
    width: clamp(12px, 2.4cqw, 26px);
    height: clamp(12px, 2.4cqw, 26px);
    flex: none;
}

.generic-card-badge {
    border-radius: 999px;
    background: #0b1220;
    padding: 0.15em 0.7em;
    color: #ffffff;
    font-size: clamp(8px, 1.2cqw, 9px);
    font-weight: 900;
    letter-spacing: 0.06em;
}

.generic-card--dark .generic-card-badge {
    background: rgb(255 255 255 / 0.14);
}

.generic-card-label {
    margin: 0;
    font-size: clamp(8px, 1.4cqw, 10px);
    font-weight: 800;
    letter-spacing: 0.1em;
    opacity: 0.66;
    text-transform: uppercase;
}

.generic-card-value {
    margin: 0;
    font-size: clamp(14px, 3cqw, 26px);
    font-weight: 900;
    letter-spacing: -0.01em;
    line-height: 1.1;
}

.generic-card-detail {
    margin: 0;
    font-size: clamp(8px, 1.4cqw, 10px);
    font-weight: 600;
    line-height: 1.4;
    opacity: 0.66;
}

/* Airport board */
.airport-panel {
    display: grid;
    height: 100%;
    grid-template-columns: minmax(0, 1fr);
    background: #050b18;
}

.airport-board {
    display: flex;
    min-width: 0;
    min-height: 0;
    flex-direction: column;
}

.airport-bar {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    padding: clamp(6px, 1.3cqw, 13px) clamp(8px, 1.8cqw, 18px);
    background: #f97316;
    color: #0b1220;
}

.airport-bar-title {
    display: flex;
    align-items: center;
    gap: 0.4em;
    font-size: clamp(10px, 2.1cqw, 16px);
    font-weight: 900;
    letter-spacing: 0.06em;
    text-transform: uppercase;
}

.airport-bar-icon {
    width: 1.25em;
    height: 1.25em;
    flex: none;
}

.airport-bar-clock {
    font-size: clamp(8px, 1.5cqw, 12px);
    font-variant-numeric: tabular-nums;
    font-weight: 800;
    white-space: nowrap;
}

.airport-columns,
.airport-row {
    display: grid;
    grid-template-columns: 0.62fr 1.45fr 0.45fr 0.95fr;
    align-items: center;
    gap: clamp(3px, 0.8cqw, 10px);
}

.airport-columns {
    padding: clamp(3px, 0.8cqw, 8px) clamp(8px, 1.8cqw, 18px);
    background: #111c2e;
    color: #94a3b8;
    font-size: clamp(8px, 1.5cqw, 11px);
    font-weight: 800;
    letter-spacing: 0.1em;
    text-transform: uppercase;
}

.airport-rows {
    display: flex;
    min-height: 0;
    flex: 1;
    flex-direction: column;
}

.airport-row {
    flex: 1;
    border-top: 1px solid rgb(148 163 184 / 0.12);
    padding: 0 clamp(8px, 1.8cqw, 18px);
    color: #e2e8f0;
    font-size: clamp(9px, 2.15cqw, 14px);
}

.airport-row--extra {
    display: none;
}

.airport-flight {
    display: flex;
    min-width: 0;
    flex-direction: column;
    line-height: 1.1;
}

.airport-flight strong {
    color: #fdba74;
    font-weight: 900;
    white-space: nowrap;
}

.airport-flight small {
    display: none;
    overflow: hidden;
    margin-top: 1px;
    color: #94a3b8;
    font-size: clamp(8px, 1.5cqw, 10px);
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.airport-row > strong {
    font-variant-numeric: tabular-nums;
    font-weight: 800;
    white-space: nowrap;
}

.airport-destination {
    display: none;
    overflow: hidden;
    color: #e2e8f0;
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.airport-status {
    justify-self: start;
    border-radius: 999px;
    padding: 0.35em 0.7em;
    font-size: clamp(8px, 1.4cqw, 10px);
    font-weight: 900;
    white-space: nowrap;
}

.airport-status--boarding {
    background: #fbbf24;
    color: #0b1220;
}

.airport-status--ontime {
    background: #047857;
    color: #ffffff;
}

.airport-status--gate {
    background: #0369a1;
    color: #ffffff;
}

.airport-status--checkin {
    background: #7c3aed;
    color: #ffffff;
}

.airport-status--delayed {
    background: #dc2626;
    color: #ffffff;
}

.airport-promo {
    position: relative;
    display: none;
    min-height: 0;
    flex-direction: column;
    justify-content: flex-end;
    overflow: hidden;
    padding: clamp(12px, 2.6cqw, 26px);
    background-image: linear-gradient(
        150deg,
        #1e40af 0%,
        #1d4ed8 45%,
        #0369a1 100%
    );
    color: #ffffff;
}

.airport-promo-art {
    position: absolute;
    top: 12%;
    right: -8%;
    width: 62%;
    height: auto;
    opacity: 0.16;
    transform: rotate(-12deg);
}

.airport-promo-eyebrow {
    margin: 0;
    color: #fde68a;
    font-size: clamp(8px, 1.4cqw, 11px);
    font-weight: 900;
    letter-spacing: 0.16em;
    text-transform: uppercase;
}

.airport-promo-title {
    margin: 0.15em 0 0;
    font-size: clamp(16px, 3.6cqw, 34px);
    font-weight: 900;
    line-height: 1.05;
}

.airport-promo-text {
    margin: 0.4em 0 0;
    color: #dbeafe;
    font-size: clamp(8px, 1.5cqw, 12px);
    font-weight: 600;
    line-height: 1.45;
}

/* Hospital queue board */
.hospital-panel {
    display: grid;
    height: 100%;
    grid-template-rows: minmax(0, 1fr) auto;
    background: #ecfeff;
    color: #0b1220;
}

.hospital-board {
    display: flex;
    min-width: 0;
    min-height: 0;
    flex-direction: column;
    background: #f8fafc;
}

.hospital-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    border-bottom: 1px solid #bae6fd;
    padding: clamp(5px, 1.2cqw, 12px) clamp(8px, 1.8cqw, 18px);
    background: #ffffff;
}

.hospital-brand {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 0.5em;
}

.hospital-mark {
    display: grid;
    flex: none;
    place-items: center;
    border-radius: 0.35em;
    padding: 0.35em;
    background: #0f766e;
    color: #ffffff;
}

.hospital-mark-icon {
    width: clamp(9px, 1.8cqw, 18px);
    height: clamp(9px, 1.8cqw, 18px);
}

.hospital-brand-text {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.hospital-brand-text strong {
    font-size: clamp(9px, 1.9cqw, 16px);
    font-weight: 900;
    white-space: nowrap;
}

.hospital-brand-text small {
    display: none;
    overflow: hidden;
    color: #475569;
    font-size: clamp(8px, 1.3cqw, 10px);
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hospital-time {
    display: flex;
    flex: none;
    flex-direction: column;
    align-items: flex-end;
}

.hospital-time span {
    display: none;
    color: #475569;
    font-size: clamp(8px, 1.25cqw, 10px);
    font-weight: 700;
    white-space: nowrap;
}

.hospital-time strong {
    font-size: clamp(12px, 2.4cqw, 22px);
    font-variant-numeric: tabular-nums;
    font-weight: 900;
    line-height: 1;
}

.hospital-columns,
.hospital-row {
    display: grid;
    grid-template-columns: 0.8fr 1.4fr 0.55fr 0.45fr;
    align-items: center;
    gap: clamp(2px, 0.6cqw, 8px);
}

.hospital-columns {
    padding: clamp(2px, 0.6cqw, 6px) clamp(8px, 1.8cqw, 18px);
    background: #e2e8f0;
    color: #475569;
    font-size: clamp(8px, 1.3cqw, 10px);
    font-weight: 900;
    letter-spacing: 0.08em;
    text-transform: uppercase;
}

.hospital-rows {
    display: flex;
    min-height: 0;
    flex: 1;
    flex-direction: column;
}

.hospital-row {
    flex: 1;
    border-top: 1px solid #e2e8f0;
    padding: 0 clamp(8px, 1.8cqw, 18px);
    color: #334155;
    font-size: clamp(9px, 2.1cqw, 14px);
}

.hospital-row--extra {
    display: none;
}

.hospital-row > strong {
    color: #0f766e;
    font-size: clamp(12px, 2.6cqw, 22px);
    font-variant-numeric: tabular-nums;
    font-weight: 900;
}

.hospital-row > span {
    overflow: hidden;
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.hospital-row > b {
    justify-self: start;
    border-radius: 0.35em;
    padding: 0.2em 0.55em;
    background: #ccfbf1;
    color: #115e59;
    font-weight: 800;
}

.hospital-row > small {
    justify-self: center;
    font-size: inherit;
    color: #475569;
    font-variant-numeric: tabular-nums;
    font-weight: 800;
}

.hospital-row--active {
    background: #dcfce7;
}

.hospital-row--active > strong {
    color: #166534;
}

.hospital-call {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    justify-content: center;
    gap: clamp(6px, 1.6cqw, 14px);
    padding: clamp(8px, 1.8cqw, 16px);
    background-image: linear-gradient(
        140deg,
        #115e59 0%,
        #0f766e 50%,
        #0e7490 100%
    );
    color: #ffffff;
}

.hospital-call-label {
    display: flex;
    margin: 0;
    align-items: center;
    gap: 0.4em;
    font-size: clamp(8px, 1.3cqw, 10px);
    font-weight: 900;
    letter-spacing: 0.1em;
    text-transform: uppercase;
    white-space: nowrap;
}

.hospital-call-icon {
    width: 1.2em;
    height: 1.2em;
    flex: none;
}

.hospital-call-number {
    margin: 0;
    font-size: clamp(18px, 6cqw, 48px);
    font-variant-numeric: tabular-nums;
    font-weight: 900;
    line-height: 1;
}

.hospital-call-counter {
    margin: 0;
    border-radius: 0.4em;
    padding: 0.15em 0.6em;
    background: #ffffff;
    color: #0e7490;
    font-size: clamp(9px, 1.9cqw, 18px);
    font-weight: 900;
    white-space: nowrap;
}

.hospital-call-service {
    margin: 0;
    font-size: clamp(8px, 1.5cqw, 12px);
    font-weight: 800;
    white-space: nowrap;
}

.hospital-call-note {
    display: none;
    max-width: 26ch;
    margin: 0;
    color: #cffafe;
    font-size: clamp(8px, 1.4cqw, 11px);
    font-weight: 700;
}

/* Restaurant menu board */
.restaurant-panel {
    display: flex;
    height: 100%;
    flex-direction: column;
    background: #140d09;
    color: #f8fafc;
}

.restaurant-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    border-bottom: 1px solid rgb(255 255 255 / 0.1);
    padding: clamp(5px, 1.2cqw, 12px) clamp(8px, 1.8cqw, 18px);
}

.restaurant-brand {
    display: flex;
    min-width: 0;
    align-items: center;
    gap: 0.55em;
}

.restaurant-mark {
    display: grid;
    flex: none;
    place-items: center;
    border-radius: 0.35em;
    padding: 0.35em;
    background: #f97316;
    color: #140d09;
}

.restaurant-mark-icon {
    width: clamp(9px, 1.8cqw, 18px);
    height: clamp(9px, 1.8cqw, 18px);
}

.restaurant-brand-text {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.restaurant-brand-text strong {
    font-size: clamp(9px, 1.9cqw, 16px);
    font-weight: 900;
    letter-spacing: -0.01em;
    white-space: nowrap;
}

.restaurant-brand-text small {
    display: none;
    overflow: hidden;
    color: #a8a29e;
    font-size: clamp(8px, 1.3cqw, 10px);
    font-weight: 700;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.restaurant-time {
    display: flex;
    flex: none;
    flex-direction: column;
    align-items: flex-end;
}

.restaurant-time span {
    display: none;
    color: #a8a29e;
    font-size: clamp(8px, 1.25cqw, 10px);
    font-weight: 700;
    white-space: nowrap;
}

.restaurant-time strong {
    color: #fdba74;
    font-size: clamp(10px, 2cqw, 20px);
    font-variant-numeric: tabular-nums;
    font-weight: 900;
    line-height: 1;
}

.restaurant-list {
    display: flex;
    min-height: 0;
    flex: 1;
    flex-direction: column;
    padding: clamp(2px, 0.8cqw, 8px) clamp(8px, 1.8cqw, 18px);
}

.restaurant-item {
    display: flex;
    flex: 1;
    align-items: baseline;
    gap: clamp(4px, 1cqw, 12px);
    border-bottom: 1px dashed rgb(255 255 255 / 0.12);
    padding: clamp(1px, 0.5cqw, 5px) 0;
}

.restaurant-item:last-child {
    border-bottom: 0;
}

.restaurant-item-text {
    display: flex;
    min-width: 0;
    flex-direction: column;
}

.restaurant-item-name {
    overflow: hidden;
    margin: 0;
    font-size: clamp(9px, 2.1cqw, 15px);
    font-weight: 800;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.restaurant-item-desc {
    display: none;
    overflow: hidden;
    margin: 0;
    color: #a8a29e;
    font-size: clamp(8px, 1.4cqw, 10px);
    font-weight: 600;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.restaurant-item-dots {
    flex: 1;
    border-bottom: 1px dotted rgb(255 255 255 / 0.22);
    transform: translateY(-2px);
}

.restaurant-item-price {
    color: #fdba74;
    font-size: clamp(10px, 2.2cqw, 16px);
    font-variant-numeric: tabular-nums;
    font-weight: 900;
    white-space: nowrap;
}

.restaurant-foot {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 8px;
    border-top: 1px solid rgb(255 255 255 / 0.1);
    background: rgb(249 115 22 / 0.12);
    padding: clamp(4px, 1cqw, 9px) clamp(8px, 1.8cqw, 18px);
    color: #fed7aa;
    font-size: clamp(8px, 1.3cqw, 10px);
    font-weight: 800;
}

.restaurant-foot-note {
    color: #ffffff;
    white-space: nowrap;
}

@media (min-width: 640px) {
    .generic-panel {
        grid-template-columns: minmax(0, 1.06fr) minmax(0, 0.64fr);
    }

    .generic-inline {
        display: none;
    }

    .generic-cards {
        display: flex;
    }

    .airport-panel {
        grid-template-columns: minmax(0, 1.55fr) minmax(0, 1fr);
    }

    .airport-columns,
    .airport-row {
        grid-template-columns: 0.55fr 1.3fr 1.1fr 0.42fr 0.95fr;
    }

    .airport-row--extra {
        display: grid;
    }

    .airport-destination {
        display: block;
    }

    .airport-flight small,
    .hospital-brand-text small,
    .hospital-time span,
    .restaurant-brand-text small,
    .restaurant-time span,
    .restaurant-item-desc {
        display: block;
    }

    .airport-promo {
        display: flex;
    }

    .hospital-panel {
        grid-template-columns: minmax(0, 1.4fr) minmax(0, 1fr);
        grid-template-rows: minmax(0, 1fr);
    }

    .hospital-row--extra {
        display: grid;
    }

    .hospital-call {
        flex-direction: column;
        gap: clamp(4px, 1cqw, 10px);
        text-align: center;
    }

    .hospital-call-note {
        display: block;
    }
}

@media (prefers-reduced-motion: reduce) {
    .preview-fade-enter-active,
    .preview-fade-leave-active {
        transition-duration: 1ms;
    }
}
</style>
