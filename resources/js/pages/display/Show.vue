<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { RefreshCw } from '@lucide/vue';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type PairingStatus = {
    status: 'waiting' | 'paired' | 'expired';
    player_url: string | null;
    connection?: DisplayConnection;
};

type DisplayConnection = {
    device_key: string;
    token: string;
};

const STORAGE_KEY = 'disi.display.connection.v1';

const props = defineProps<{
    code: string;
    token: string;
    statusUrl: string;
    resumeUrl: string;
    pairingUrl: string;
    qrSvg: string;
    expiresAt: string;
}>();

const status = ref<'waiting' | 'paired' | 'expired'>('waiting');
const secondsLeft = ref(0);
const errorMessage = ref<string | null>(null);
const isResuming = ref(true);

let pollTimer: number | undefined;
let countdownTimer: number | undefined;

const qrDataUrl = computed(() => {
    return `data:image/svg+xml;base64,${window.btoa(props.qrSvg)}`;
});

function updateCountdown(): void {
    secondsLeft.value = Math.max(
        Math.ceil((new Date(props.expiresAt).getTime() - Date.now()) / 1000),
        0,
    );

    if (secondsLeft.value === 0 && status.value === 'waiting') {
        status.value = 'expired';
    }
}

async function pollStatus(): Promise<void> {
    try {
        const response = await fetch(props.statusUrl, {
            headers: {
                Accept: 'application/json',
            },
        });

        if (!response.ok) {
            throw new Error(`Status request failed: ${response.status}`);
        }

        const payload = (await response.json()) as PairingStatus;
        status.value = payload.status;
        errorMessage.value = null;

        if (payload.status === 'paired' && payload.player_url) {
            if (payload.connection) {
                window.localStorage.setItem(
                    STORAGE_KEY,
                    JSON.stringify(payload.connection),
                );
            }

            window.location.replace(payload.player_url);
        }
    } catch (error) {
        errorMessage.value =
            error instanceof Error ? error.message : 'Pairing belum tersedia.';
    }
}

function savedConnection(): DisplayConnection | null {
    const value = window.localStorage.getItem(STORAGE_KEY);

    if (!value) {
        return null;
    }

    try {
        const connection = JSON.parse(value) as Partial<DisplayConnection>;

        if (
            typeof connection.device_key === 'string' &&
            typeof connection.token === 'string'
        ) {
            return connection as DisplayConnection;
        }
    } catch {
        // Invalid browser data should return the display to pairing mode.
    }

    window.localStorage.removeItem(STORAGE_KEY);

    return null;
}

async function resumeDisplay(): Promise<boolean> {
    const connection = savedConnection();

    if (!connection) {
        return false;
    }

    try {
        const response = await fetch(props.resumeUrl, {
            method: 'POST',
            headers: {
                Accept: 'application/json',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify(connection),
        });

        if (!response.ok) {
            window.localStorage.removeItem(STORAGE_KEY);

            return false;
        }

        const payload = (await response.json()) as PairingStatus;

        if (payload.status === 'paired' && payload.player_url) {
            window.location.replace(payload.player_url);

            return true;
        }
    } catch {
        errorMessage.value = 'Koneksi tersimpan belum dapat divalidasi.';
    }

    return false;
}

function refreshSession(): void {
    window.location.reload();
}

onMounted(async () => {
    updateCountdown();

    if (await resumeDisplay()) {
        return;
    }

    isResuming.value = false;
    void pollStatus();

    countdownTimer = window.setInterval(updateCountdown, 1_000);
    pollTimer = window.setInterval(() => {
        void pollStatus();
    }, 2_000);
});

onBeforeUnmount(() => {
    if (pollTimer) {
        window.clearInterval(pollTimer);
    }

    if (countdownTimer) {
        window.clearInterval(countdownTimer);
    }
});
</script>

<template>
    <Head title="Connect Display" />

    <main class="fixed inset-0 bg-zinc-950 text-white">
        <section
            class="grid h-screen w-screen place-items-center px-8 py-10 text-center"
        >
            <div v-if="isResuming" class="w-full max-w-2xl">
                <p
                    class="text-sm font-medium tracking-[0.28em] text-cyan-300 uppercase"
                >
                    Connect display
                </p>
                <h1
                    class="mt-6 text-4xl font-semibold tracking-normal sm:text-6xl"
                >
                    Menyambungkan kembali...
                </h1>
                <p class="mt-5 text-lg text-zinc-400">
                    Memeriksa koneksi display yang tersimpan di TV ini.
                </p>
            </div>

            <div v-else class="w-full max-w-5xl">
                <div
                    class="mx-auto grid max-w-4xl items-center gap-10 md:grid-cols-[1fr_320px]"
                >
                    <div class="text-left">
                        <p
                            class="text-sm font-medium tracking-[0.28em] text-cyan-300 uppercase"
                        >
                            Connect display
                        </p>
                        <h1
                            class="mt-6 text-6xl font-semibold tracking-normal sm:text-8xl"
                        >
                            {{ code }}
                        </h1>
                        <p class="mt-6 text-xl text-zinc-300">
                            Scan QR atau masukkan kode ini dari dashboard admin.
                        </p>
                        <div class="mt-8 text-sm text-zinc-400">
                            <span v-if="status === 'waiting'">
                                Menunggu pairing, kode aktif
                                {{ secondsLeft }} detik.
                            </span>
                            <span v-else-if="status === 'paired'">
                                Display tersambung. Membuka player...
                            </span>
                            <span v-else>
                                Kode expired. Refresh untuk membuat kode baru.
                            </span>
                        </div>

                        <button
                            v-if="status === 'expired'"
                            type="button"
                            class="mt-6 rounded-md bg-white px-5 py-3 text-sm font-medium text-zinc-950"
                            @click="refreshSession"
                        >
                            <RefreshCw class="mr-2 inline size-4" />
                            Generate kode baru
                        </button>

                        <p
                            v-if="errorMessage"
                            class="mt-4 text-sm text-red-300"
                        >
                            {{ errorMessage }}
                        </p>
                    </div>

                    <div
                        class="rounded-lg bg-white p-5 shadow-2xl shadow-black/40"
                    >
                        <img
                            :src="qrDataUrl"
                            alt="Display pairing QR code"
                            class="h-[280px] w-[280px]"
                        />
                    </div>
                </div>

                <p class="mt-10 text-sm break-all text-zinc-500">
                    {{ pairingUrl }}
                </p>
            </div>
        </section>
    </main>
</template>
