<script setup lang="ts">
import { Head } from '@inertiajs/vue3';
import { computed, onBeforeUnmount, onMounted, ref } from 'vue';

type PairingStatus = {
    status: 'waiting' | 'paired' | 'expired';
    player_url: string | null;
};

const props = defineProps<{
    code: string;
    token: string;
    statusUrl: string;
    pairingUrl: string;
    qrSvg: string;
    expiresAt: string;
}>();

const status = ref<'waiting' | 'paired' | 'expired'>('waiting');
const secondsLeft = ref(0);
const errorMessage = ref<string | null>(null);

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
            window.location.href = payload.player_url;
        }
    } catch (error) {
        errorMessage.value =
            error instanceof Error ? error.message : 'Pairing belum tersedia.';
    }
}

function refreshSession(): void {
    window.location.reload();
}

onMounted(() => {
    updateCountdown();
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
            <div class="w-full max-w-5xl">
                <div
                    class="mx-auto grid max-w-4xl items-center gap-10 md:grid-cols-[1fr_320px]"
                >
                    <div class="text-left">
                        <p
                            class="text-sm font-medium uppercase tracking-[0.28em] text-cyan-300"
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
                            Generate kode baru
                        </button>

                        <p v-if="errorMessage" class="mt-4 text-sm text-red-300">
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

                <p class="mt-10 break-all text-sm text-zinc-500">
                    {{ pairingUrl }}
                </p>
            </div>
        </section>
    </main>
</template>
