<script setup lang="ts">
import { computed } from 'vue';
import {
    overlayApiTickerText,
    overlayBarPositionClass,
    overlayClockText,
    overlayCountdown,
    overlayDateText,
    overlayGreetingText,
    overlayOffsetStyle,
    overlayPositionClass,
    overlayProgressPercent,
    overlayRssText,
    overlayScheduleMatches,
    overlayThemeClasses,
    overlayTickerAnimationStyle,
    overlayTickerStyle,
    overlayTickerText,
    overlayWeatherCondition,
    overlayTypeLabel,
    scheduleIsActive,
    type OverlayConfig,
    type OverlayEntry,
} from '@/lib/overlays';

const props = defineProps<{
    overlays: OverlayEntry[];
    now: Date;
    brandLabel: string;
    screen: { name: string; location?: string | null };
    item: {
        title: string | null;
        nextTitle: string | null;
        elapsedSeconds: number;
        durationSeconds: number;
    } | null;
    schedule: {
        start_time?: string | null;
        end_time?: string | null;
        days_of_week?: number[] | null;
    } | null;
}>();

const visibleOverlays = computed(() =>
    props.overlays.filter((overlay) =>
        overlayScheduleMatches(overlay.config?.schedule, props.now),
    ),
);

function theme(config: OverlayConfig) {
    return overlayThemeClasses(config.theme);
}

function overlayKey(overlay: OverlayEntry, index: number): string {
    return overlay.id ?? `${overlay.type}-${index}`;
}

function clockText(config: OverlayConfig): string {
    return overlayClockText(props.now, config.format);
}

function dateText(config: OverlayConfig): string {
    return overlayDateText(props.now, config);
}

function greetingText(config: OverlayConfig): string {
    return overlayGreetingText(config, props.now, {
        tenant: props.brandLabel,
        screen: props.screen.name,
    });
}

function countdownState(config: OverlayConfig) {
    return overlayCountdown(config, props.now);
}

function locationText(config: OverlayConfig): string {
    const location = props.screen.location ?? props.screen.name;
    const label = (config.label ?? '').trim();
    const separator = config.separator ?? '·';

    return [label, location].filter(Boolean).join(` ${separator} `);
}

function nextText(config: OverlayConfig): string {
    if (config.next_mode === 'now') {
        return props.item?.title ?? '';
    }

    return props.item?.nextTitle ?? '';
}

function nextLabel(config: OverlayConfig): string {
    if (config.next_mode === 'now') {
        return config.label || 'Sekarang';
    }

    return config.label || 'Berikutnya';
}

function openState(config: OverlayConfig) {
    return scheduleIsActive(props.schedule, props.now);
}

function progressPercent(config: OverlayConfig): number {
    return overlayProgressPercent(
        props.item?.elapsedSeconds ?? 0,
        props.item?.durationSeconds ?? 0,
    );
}

function weatherValue(value: unknown, suffix = ''): string {
    if (value === null || value === undefined || value === '') {
        return '-';
    }

    return `${String(value)}${suffix}`;
}

function weatherLocation(config: OverlayConfig): string {
    return String(config.weather?.location ?? 'Weather');
}

function weatherCondition(config: OverlayConfig): string {
    return overlayWeatherCondition(config.weather?.weather_code);
}

function apiTickerText(config: OverlayConfig): string {
    return overlayApiTickerText(config);
}

function rssTickerText(config: OverlayConfig): string {
    return overlayRssText(config, props.now);
}

function rssEntries(config: OverlayConfig) {
    return (config.entries ?? []).slice(0, Math.min(4, config.item_limit ?? 4));
}

function overlayLabel(type: string): string {
    return overlayTypeLabel(type);
}

const PANEL_TYPES = [
    'clock',
    'date',
    'greeting',
    'location',
    'next',
    'countdown',
    'hours',
    'weather',
    'api',
    'rss',
];

function isPanelOverlay(type: string): boolean {
    return PANEL_TYPES.includes(type);
}
</script>

<template>
    <template
        v-for="(overlay, index) in visibleOverlays"
        :key="overlayKey(overlay, index)"
    >
        <!-- Running text bar -->
        <div
            v-if="overlay.type === 'ticker'"
            class="pointer-events-none absolute right-0 left-0 z-20 overflow-hidden px-4 py-3 text-lg font-medium"
            :class="overlayBarPositionClass(overlay.config.position)"
            :style="{
                ...overlayTickerStyle(overlay.config),
                ...overlayOffsetStyle(overlay.config),
            }"
        >
            <div
                class="ticker-track w-max whitespace-nowrap"
                :style="overlayTickerAnimationStyle(overlay.config)"
            >
                {{ overlayTickerText(overlay.config) }}
            </div>
        </div>

        <!-- Item progress bar -->
        <div
            v-else-if="overlay.type === 'progress'"
            class="pointer-events-none absolute right-0 left-0 z-20"
            :class="overlayBarPositionClass(overlay.config.position)"
            :style="overlayOffsetStyle(overlay.config)"
        >
            <div
                class="w-full overflow-hidden"
                :style="{
                    height: `${overlay.config.height ?? 6}px`,
                    backgroundColor: overlay.config.track_color ?? '#111827',
                }"
            >
                <div
                    class="h-full transition-[width] duration-700 ease-linear"
                    :style="{
                        width: `${progressPercent(overlay.config)}%`,
                        backgroundColor: overlay.config.color ?? '#22d3ee',
                    }"
                />
            </div>
        </div>

        <!-- Image badge -->
        <img
            v-else-if="overlay.type === 'badge' && overlay.config.image_url"
            :src="overlay.config.image_url"
            :alt="overlay.config.image_name ?? ''"
            class="pointer-events-none absolute z-20 h-auto object-contain drop-shadow-lg"
            :class="overlayPositionClass(overlay.config.position)"
            :style="{
                width: `${overlay.config.size ?? 96}px`,
                ...overlayOffsetStyle(overlay.config),
            }"
        />

        <!-- Lower third title card -->
        <div
            v-else-if="overlay.type === 'title'"
            class="pointer-events-none absolute z-20 max-w-xl min-w-64 rounded-lg border px-5 py-4 shadow-xl backdrop-blur-sm"
            :class="[
                theme(overlay.config).panel,
                overlayPositionClass(overlay.config.position),
                overlay.config.align === 'center'
                    ? 'text-center'
                    : overlay.config.align === 'right'
                      ? 'text-right'
                      : 'text-left',
            ]"
            :style="overlayOffsetStyle(overlay.config)"
        >
            <div class="text-xl leading-tight font-semibold">
                {{ overlay.config.title || overlayLabel(overlay.type) }}
            </div>
            <div
                v-if="overlay.config.subtitle"
                class="mt-1 text-sm"
                :class="theme(overlay.config).muted"
            >
                {{ overlay.config.subtitle }}
            </div>
        </div>

        <!-- Panel overlays -->
        <div
            v-else-if="isPanelOverlay(overlay.type)"
            class="pointer-events-none absolute z-20 rounded-lg border px-4 py-3 shadow-xl backdrop-blur-sm"
            :class="[
                theme(overlay.config).panel,
                overlayPositionClass(overlay.config.position),
                overlay.type === 'weather' ? 'z-30 min-w-56' : '',
            ]"
            :style="overlayOffsetStyle(overlay.config)"
        >
            <template v-if="overlay.type === 'clock'">
                <div class="text-xl font-semibold tabular-nums">
                    {{ clockText(overlay.config) }}
                </div>
            </template>

            <template v-else-if="overlay.type === 'date'">
                <div class="text-lg font-semibold">
                    {{ dateText(overlay.config) }}
                </div>
            </template>

            <template v-else-if="overlay.type === 'greeting'">
                <div class="text-lg font-semibold">
                    {{ greetingText(overlay.config) }}
                </div>
            </template>

            <template v-else-if="overlay.type === 'location'">
                <div class="text-lg font-semibold">
                    {{ locationText(overlay.config) }}
                </div>
            </template>

            <template v-else-if="overlay.type === 'next'">
                <div
                    class="text-[10px] font-semibold tracking-[0.2em] uppercase"
                    :class="theme(overlay.config).accent"
                >
                    {{ nextLabel(overlay.config) }}
                </div>
                <div class="mt-1 max-w-72 truncate text-lg font-semibold">
                    {{ nextText(overlay.config) || '-' }}
                </div>
            </template>

            <template v-else-if="overlay.type === 'countdown'">
                <div
                    v-if="!countdownState(overlay.config).finished"
                    class="text-center"
                >
                    <div
                        v-if="overlay.config.prefix"
                        class="text-[11px] font-medium"
                        :class="theme(overlay.config).muted"
                    >
                        {{ overlay.config.prefix }}
                    </div>
                    <div class="mt-1 text-2xl font-semibold tabular-nums">
                        {{ countdownState(overlay.config).text }}
                    </div>
                    <div
                        v-if="overlay.config.suffix"
                        class="text-[11px]"
                        :class="theme(overlay.config).muted"
                    >
                        {{ overlay.config.suffix }}
                    </div>
                </div>
                <div
                    v-else-if="overlay.config.finished_text"
                    class="text-center text-lg font-semibold"
                >
                    {{ overlay.config.finished_text }}
                </div>
            </template>

            <template v-else-if="overlay.type === 'hours'">
                <div
                    v-if="overlay.config.title"
                    class="text-[10px] font-semibold tracking-[0.2em] uppercase"
                    :class="theme(overlay.config).muted"
                >
                    {{ overlay.config.title }}
                </div>
                <div class="mt-1 flex items-center gap-2">
                    <span
                        class="size-2 rounded-full"
                        :class="
                            openState(overlay.config)
                                ? 'bg-emerald-400'
                                : 'bg-rose-400'
                        "
                    />
                    <span class="text-lg font-semibold">
                        {{
                            openState(overlay.config)
                                ? (overlay.config.open_label ?? 'BUKA')
                                : (overlay.config.closed_label ?? 'TUTUP')
                        }}
                    </span>
                </div>
                <div
                    v-if="
                        overlay.config.show_times &&
                        schedule?.start_time &&
                        schedule?.end_time
                    "
                    class="mt-0.5 text-xs tabular-nums"
                    :class="theme(overlay.config).muted"
                >
                    {{ schedule.start_time }} – {{ schedule.end_time }}
                </div>
            </template>

            <template v-else-if="overlay.type === 'weather'">
                <div class="flex items-center justify-between gap-5">
                    <div>
                        <div
                            class="text-[10px] font-semibold tracking-[0.2em] uppercase"
                            :class="theme(overlay.config).accent"
                        >
                            Weather
                        </div>
                        <div class="mt-1 text-sm font-medium">
                            {{ weatherLocation(overlay.config) }}
                        </div>
                    </div>
                    <div class="text-3xl leading-none font-semibold">
                        {{
                            weatherValue(
                                overlay.config.weather?.temperature,
                                ' °C',
                            )
                        }}
                    </div>
                </div>
                <div
                    class="mt-3 flex items-center gap-3 border-t pt-2 text-xs"
                    :class="[
                        theme(overlay.config).muted,
                        overlay.config.theme === 'light'
                            ? 'border-slate-900/10'
                            : 'border-white/10',
                    ]"
                >
                    <span>{{ weatherCondition(overlay.config) }}</span>
                    <span
                        >Humidity
                        {{
                            weatherValue(overlay.config.weather?.humidity, '%')
                        }}</span
                    >
                    <span v-if="overlay.config.weather?.wind_speed">
                        Wind
                        {{
                            weatherValue(
                                overlay.config.weather?.wind_speed,
                                ' km/h',
                            )
                        }}</span
                    >
                </div>
            </template>

            <template v-else-if="overlay.type === 'api'">
                <div
                    v-if="overlay.config.title"
                    class="mb-1 text-[10px] font-semibold tracking-[0.2em] uppercase"
                    :class="theme(overlay.config).accent"
                >
                    {{ overlay.config.title }}
                </div>
                <div
                    v-if="overlay.config.layout === 'ticker'"
                    class="w-64 overflow-hidden"
                >
                    <div
                        class="ticker-track w-max text-sm font-medium whitespace-nowrap"
                        :style="{
                            animationDuration: '18s',
                            color: overlay.config.text_color ?? '#ffffff',
                        }"
                    >
                        {{ apiTickerText(overlay.config) }}
                    </div>
                </div>
                <div v-else class="mt-1 grid min-w-40 gap-1.5">
                    <div
                        v-for="(value, valueIndex) in overlay.config.values ??
                        []"
                        :key="`api-${valueIndex}`"
                        class="flex items-baseline justify-between gap-6 text-sm"
                    >
                        <span :class="theme(overlay.config).muted">{{
                            value.label
                        }}</span>
                        <span class="font-semibold tabular-nums"
                            >{{ value.prefix }}{{ value.value ?? '-'
                            }}{{ value.suffix }}</span
                        >
                    </div>
                    <div
                        v-if="(overlay.config.values ?? []).length === 0"
                        class="text-sm"
                        :class="theme(overlay.config).muted"
                    >
                        Menunggu data…
                    </div>
                </div>
            </template>

            <template v-else-if="overlay.type === 'rss'">
                <div
                    v-if="overlay.config.title"
                    class="mb-1 text-[10px] font-semibold tracking-[0.2em] uppercase"
                    :class="theme(overlay.config).accent"
                >
                    {{ overlay.config.title }}
                </div>
                <div
                    v-if="overlay.config.layout === 'ticker'"
                    class="w-64 overflow-hidden"
                >
                    <div
                        class="ticker-track w-max text-sm font-medium whitespace-nowrap"
                        :style="{
                            animationDuration: '18s',
                        }"
                    >
                        {{
                            rssTickerText(overlay.config) || 'Menunggu berita…'
                        }}
                    </div>
                </div>
                <div v-else class="mt-1 grid min-w-56 gap-1.5">
                    <div
                        v-for="(entry, entryIndex) in rssEntries(
                            overlay.config,
                        )"
                        :key="`rss-${entryIndex}`"
                        class="max-w-72 truncate text-sm"
                    >
                        {{ entry.title }}
                    </div>
                    <div
                        v-if="rssEntries(overlay.config).length === 0"
                        class="text-sm"
                        :class="theme(overlay.config).muted"
                    >
                        Menunggu berita…
                    </div>
                </div>
            </template>
        </div>
    </template>
</template>

<style scoped>
.ticker-track {
    animation: ticker-scroll linear infinite;
}

@keyframes ticker-scroll {
    from {
        transform: translateX(100vw);
    }

    to {
        transform: translateX(-100%);
    }
}
</style>
