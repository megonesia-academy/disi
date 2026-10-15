<script setup lang="ts">
import { Head, Link } from '@inertiajs/vue3';
import { ArrowRight, BookOpen, CheckCircle2, Code2, ExternalLink, Image, LayoutTemplate, MonitorPlay, Plug, Volume2 } from '@lucide/vue';

const sections = [
    { id: 'quickstart', label: 'Mulai cepat', icon: CheckCircle2 },
    { id: 'display', label: 'Display & device', icon: MonitorPlay },
    { id: 'media', label: 'Media', icon: Image },
    { id: 'playlist', label: 'Playlist', icon: LayoutTemplate },
    { id: 'templates', label: 'Templates', icon: LayoutTemplate },
    { id: 'api', label: 'Integrasi API', icon: Plug },
    { id: 'audio', label: 'Audio & Split Screen', icon: Volume2 },
];

const weatherExample = '/v1/forecast?latitude=-6.221441&longitude=106.78094&current=temperature_2m,relative_humidity_2m,weather_code,wind_speed_10m&timezone=Asia%2FJakarta';
const restaurantExample = {
    items: [
        { name: 'Nasi Goreng Spesial', description: 'Nasi goreng, ayam, telur', price: '35000', image: 'https://example.com/nasi-goreng.jpg', category: 'Main course' },
        { name: 'Es Teh Manis', description: 'Teh manis dingin', price: '10000', image: 'https://example.com/es-teh.jpg', category: 'Drinks' },
    ],
};
const flightExample = {
    rows: [
        { flight: 'GA 204', destination: 'Jakarta', time: '10:35', gate: 'A12', status: 'Boarding' },
        { flight: 'QZ 721', destination: 'Bali', time: '11:20', gate: 'B04', status: 'On time' },
    ],
};
const dataTableExample = {
    columns: ['Name', 'Department', 'Status'],
    rows: [
        ['Siti Rahma', 'Sales', 'Present'],
        ['Budi Santoso', 'Support', 'Break'],
    ],
};
const customImageExample = '<img src="{{ image }}" alt="{{ title }}" class="hero-image">';
const customVideoExample = '<video src="{{ video_url }}" autoplay muted loop playsinline class="hero-video"></video>';
const customYoutubeExample = '<iframe src="{{ youtube_url }}" allow="autoplay; fullscreen" class="hero-video"></iframe>';
const customRowsExample = '<div class="flight-row">{{this.flight}} · {{this.destination}} · {{this.time}} · {{this.status}}</div>';
const customLoopStart = '{{#each rows}}';
const customLoopEnd = '{{/each}}';
const customApiExample = {
    title: 'Promo Oktober',
    subtitle: 'Diskon 20% untuk semua menu',
    image: 'https://cdn.example.com/promo-oktober.jpg',
    video_url: 'https://cdn.example.com/promo-oktober.mp4',
};
</script>

<template>
    <Head title="Documentation" />

    <div class="min-h-full bg-muted/20 p-4 md:p-6">
        <div class="mx-auto max-w-7xl">
            <header class="rounded-lg border bg-background p-6 shadow-sm md:p-8">
                <div class="flex flex-wrap items-start justify-between gap-6">
                    <div class="max-w-3xl">
                        <div class="flex items-center gap-3 text-sm font-medium text-cyan-700 dark:text-cyan-300"><BookOpen class="size-4" /> DISI Documentation</div>
                        <h1 class="mt-3 text-3xl font-semibold tracking-tight md:text-4xl">Panduan mengelola digital signage</h1>
                        <p class="mt-3 text-base leading-7 text-muted-foreground">Kelola workspace, media, playlist, display, template, audio, dan data API dari satu tempat.</p>
                    </div>
                    <Link href="/dashboard" class="inline-flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm font-medium shadow-xs hover:bg-accent"><ArrowRight class="size-4" /> Dashboard</Link>
                </div>
                <nav class="mt-8 flex flex-wrap gap-2 border-t pt-5">
                    <a v-for="section in sections" :key="section.id" :href="`#${section.id}`" class="inline-flex items-center gap-2 rounded-md border bg-background px-3 py-2 text-sm font-medium hover:bg-accent"><component :is="section.icon" class="size-4" />{{ section.label }}</a>
                </nav>
            </header>

            <main class="mt-6 grid gap-6 lg:grid-cols-[minmax(0,1fr)_280px]">
                <div class="space-y-6">
                    <section id="quickstart" class="scroll-mt-6 rounded-lg border bg-background p-6 shadow-sm md:p-8">
                        <div class="flex items-center gap-3"><CheckCircle2 class="size-5 text-emerald-600" /><h2 class="text-xl font-semibold">Mulai cepat</h2></div>
                        <ol class="mt-5 grid gap-4 md:grid-cols-2">
                            <li class="rounded-md border p-4"><strong>1. Buat workspace</strong><p class="mt-1 text-sm text-muted-foreground">Lengkapi nama, logo, warna brand, dan perilaku player di Workspace.</p></li>
                            <li class="rounded-md border p-4"><strong>2. Upload media</strong><p class="mt-1 text-sm text-muted-foreground">Tambahkan image, video, audio, atau URL web melalui Media.</p></li>
                            <li class="rounded-md border p-4"><strong>3. Buat playlist</strong><p class="mt-1 text-sm text-muted-foreground">Susun item, durasi, transition, audio, overlay, dan template.</p></li>
                            <li class="rounded-md border p-4"><strong>4. Schedule ke screen</strong><p class="mt-1 text-sm text-muted-foreground">Hubungkan playlist ke screen melalui Schedules.</p></li>
                        </ol>
                    </section>

                    <section id="display" class="scroll-mt-6 rounded-lg border bg-background p-6 shadow-sm md:p-8">
                        <div class="flex items-center gap-3"><MonitorPlay class="size-5 text-cyan-600" /><h2 class="text-xl font-semibold">Display & device</h2></div>
                        <p class="mt-3 leading-7 text-muted-foreground">Buka <code>/display</code> pada Android TV. TV akan menampilkan QR atau kode pairing. Scan dari aplikasi DISI di handphone, pilih screen, lalu konfirmasi pairing.</p>
                        <div class="mt-5 grid gap-3 md:grid-cols-3"><div class="rounded-md bg-muted/40 p-4"><strong>Screen</strong><p class="mt-1 text-sm text-muted-foreground">Lokasi logis dan konfigurasi tampilan.</p></div><div class="rounded-md bg-muted/40 p-4"><strong>Device</strong><p class="mt-1 text-sm text-muted-foreground">Perangkat Android TV yang terhubung.</p></div><div class="rounded-md bg-muted/40 p-4"><strong>Schedule</strong><p class="mt-1 text-sm text-muted-foreground">Aturan playlist yang sedang aktif.</p></div></div>
                    </section>

                    <section id="media" class="scroll-mt-6 rounded-lg border bg-background p-6 shadow-sm md:p-8">
                        <div class="flex items-center gap-3"><Image class="size-5 text-emerald-600" /><h2 class="text-xl font-semibold">Media</h2></div>
                        <p class="mt-3 leading-7 text-muted-foreground">Media adalah aset yang dapat digunakan berulang kali oleh banyak playlist dalam satu workspace. Buka menu Media, klik tambah media, lalu pilih sumber dan tipe aset.</p>
                        <div class="mt-5 grid gap-3 sm:grid-cols-2">
                            <div class="rounded-md border p-4"><strong>Image</strong><p class="mt-1 text-sm text-muted-foreground">Upload JPG, PNG, WebP, atau format gambar lain yang didukung browser. Ukuran dan dimensi dicatat otomatis.</p></div>
                            <div class="rounded-md border p-4"><strong>Video</strong><p class="mt-1 text-sm text-muted-foreground">Upload video ke storage workspace. Isi durasi bila player membutuhkan durasi item tertentu.</p></div>
                            <div class="rounded-md border p-4"><strong>Audio</strong><p class="mt-1 text-sm text-muted-foreground">Upload musik atau suara pengumuman. Audio dapat dipilih pada item playlist apa pun dan dapat di-loop.</p></div>
                            <div class="rounded-md border p-4"><strong>Web / URL</strong><p class="mt-1 text-sm text-muted-foreground">Simpan URL halaman web, video langsung, atau sumber eksternal. URL harus lengkap menggunakan <code>https://</code>.</p></div>
                        </div>
                        <div class="mt-6 rounded-md border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-950 dark:border-emerald-900 dark:bg-emerald-950/40 dark:text-emerald-100">
                            <strong>Alur penggunaan:</strong> buat media → cek preview → buka playlist → pilih media pada item → atur durasi, fit, transition, audio, atau overlay → simpan.
                        </div>
                        <div class="mt-6 grid gap-4 md:grid-cols-2">
                            <div class="rounded-md border bg-muted/30 p-4"><strong class="text-sm">Rekomendasi image</strong><ul class="mt-2 space-y-1 text-sm text-muted-foreground"><li>Gunakan rasio yang sama dengan orientasi screen.</li><li>Optimalkan file agar player TV tidak lambat.</li><li>Gunakan nama aset yang mudah dicari.</li></ul></div>
                            <div class="rounded-md border bg-muted/30 p-4"><strong class="text-sm">Rekomendasi video/audio</strong><ul class="mt-2 space-y-1 text-sm text-muted-foreground"><li>Gunakan format yang didukung browser Android TV.</li><li>Pastikan URL web dapat diakses dari jaringan TV.</li><li>Gunakan audio loop hanya jika memang diperlukan.</li></ul></div>
                        </div>
                    </section>

                    <section id="playlist" class="scroll-mt-6 rounded-lg border bg-background p-6 shadow-sm md:p-8">
                        <div class="flex items-center gap-3"><LayoutTemplate class="size-5 text-violet-600" /><h2 class="text-xl font-semibold">Playlist & template</h2></div>
                        <p class="mt-3 text-muted-foreground">Playlist item dapat diurutkan dengan drag and drop. Template yang tersedia:</p>
                        <div class="mt-4 grid gap-3 sm:grid-cols-2"><div v-for="item in ['Standard media', 'Announcement', 'Weather', 'Flight Board', 'Data Table', 'Restaurant menu', 'Split Screen', 'Custom template']" :key="item" class="rounded-md border px-4 py-3 text-sm font-medium">{{ item }}</div></div>
                        <div class="mt-5 rounded-md border border-cyan-200 bg-cyan-50 p-4 text-sm text-cyan-950 dark:border-cyan-900 dark:bg-cyan-950/40 dark:text-cyan-100"><strong>Tips:</strong> Data Table memakai <code>columns</code> dan <code>rows</code>. Flight Board memakai object rows. Restaurant menu memakai array <code>items</code>.</div>
                    </section>

                    <section id="templates" class="scroll-mt-6 rounded-lg border bg-background p-6 shadow-sm md:p-8">
                        <div class="flex items-center gap-3"><LayoutTemplate class="size-5 text-violet-600" /><h2 class="text-xl font-semibold">Templates</h2></div>
                        <p class="mt-3 leading-7 text-muted-foreground">Template menentukan struktur tampilan sebuah playlist item. Gunakan template standar untuk kebutuhan umum, atau buat custom template jika layout dan field-nya perlu dikendalikan sendiri.</p>
                        <div class="mt-5 space-y-3">
                            <div class="rounded-md border p-4"><strong>Standard media</strong><p class="mt-1 text-sm text-muted-foreground">Menampilkan satu image, video, atau web media asset. Pilih aset dari dropdown, lalu atur fit, durasi, transition, overlay, dan audio.</p></div>
                            <div class="rounded-md border p-4"><strong>Weather, Flight Board, Data Table, Restaurant Menu</strong><p class="mt-1 text-sm text-muted-foreground">Template data-driven. Isi content JSON secara manual atau ambil dari API lalu gunakan mapping untuk memasukkan data ke field template.</p></div>
                            <div class="rounded-md border p-4"><strong>Split Screen</strong><p class="mt-1 text-sm text-muted-foreground">Kolom kiri memakai image asset. Kolom kanan dapat memakai video asset, URL video/web, atau YouTube. Atur persentase lebar kiri dan kanan.</p></div>
                            <div class="rounded-md border p-4"><strong>Custom template</strong><p class="mt-1 text-sm text-muted-foreground">Buat HTML, CSS, dan schema field sendiri di menu Templates. Gunakan placeholder seperti <code v-pre>{{ title }}</code>; field akan diisi saat custom template dipilih pada playlist item.</p></div>
                        </div>

                        <div class="mt-8 border-t pt-6">
                            <h3 class="text-lg font-semibold">Memasukkan image dan video</h3>
                            <div class="mt-4 grid gap-4 md:grid-cols-2">
                                <div class="rounded-md border p-4"><strong>Upload image / video</strong><p class="mt-1 text-sm leading-6 text-muted-foreground">Upload terlebih dahulu melalui Media. Pada template Standard media atau Split Screen, pilih aset tersebut dari pilihan media. Ini adalah pilihan paling stabil untuk player TV.</p></div>
                                <div class="rounded-md border p-4"><strong>Link image / video</strong><p class="mt-1 text-sm leading-6 text-muted-foreground">Untuk halaman atau file eksternal, buat media bertipe Web dan isi URL lengkap dengan <code>https://</code>. Untuk custom template, masukkan URL melalui field schema lalu gunakan placeholder pada HTML.</p></div>
                            </div>
                            <div class="mt-4 rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100">
                                <div class="mb-3 text-zinc-400">Contoh HTML custom template</div>
                                <pre class="overflow-x-auto text-emerald-300">{{ customImageExample }}
{{ customVideoExample }}
{{ customYoutubeExample }}</pre>
                            </div>
                            <div class="mt-4 grid gap-4 md:grid-cols-3">
                                <div class="rounded-md bg-muted/30 p-4 text-sm"><strong>Image URL</strong><p class="mt-1 text-muted-foreground">Gunakan <code v-pre>&lt;img src="{{ image }}"&gt;</code> dan isi field <code>image</code> dengan URL gambar.</p></div>
                                <div class="rounded-md bg-muted/30 p-4 text-sm"><strong>Video URL</strong><p class="mt-1 text-muted-foreground">Gunakan file MP4/WebM/HLS yang bisa diakses TV, lalu set <code>autoplay muted loop</code>.</p></div>
                                <div class="rounded-md bg-muted/30 p-4 text-sm"><strong>YouTube</strong><p class="mt-1 text-muted-foreground">Untuk YouTube gunakan URL embed, misalnya <code>https://www.youtube.com/embed/VIDEO_ID</code>. Split Screen otomatis mengubah URL watch menjadi embed.</p></div>
                            </div>
                            <div class="mt-4 rounded-md border border-amber-200 bg-amber-50 p-4 text-sm text-amber-950 dark:border-amber-900 dark:bg-amber-950/40 dark:text-amber-100"><strong>Catatan keamanan:</strong> script dan event handler HTML custom akan dihapus oleh player. Gunakan HTML/CSS dan placeholder data saja; jangan mengandalkan JavaScript di dalam template.</div>
                        </div>
                        <div class="mt-8 border-t pt-6">
                            <h3 class="text-lg font-semibold">Custom template dengan API</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">Custom template dapat mengambil data API seperti template Weather atau Data Table. Data diambil oleh server, dipetakan ke content, lalu player hanya menerima hasil akhirnya. Credential API tidak dikirim ke TV.</p>
                            <ol class="mt-4 space-y-3 text-sm leading-6 text-muted-foreground">
                                <li><strong class="text-foreground">1. Siapkan API Connection.</strong> Buat koneksi, lakukan test, lalu minta Owner melakukan approval.</li>
                                <li><strong class="text-foreground">2. Buat field schema.</strong> Buat field seperti <code>title</code>, <code>subtitle</code>, <code>image</code>, atau <code>video_url</code> pada custom template.</li>
                                <li><strong class="text-foreground">3. Aktifkan API pada playlist item.</strong> Pilih custom template, ubah Data source menjadi API, pilih koneksi, lalu isi endpoint relatif.</li>
                                <li><strong class="text-foreground">4. Buat mapping.</strong> Sisi kiri harus sama dengan nama placeholder, sisi kanan adalah path dari response JSON.</li>
                            </ol>
                            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                <div class="rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Contoh response API</div><pre class="overflow-x-auto text-emerald-300">{{ JSON.stringify(customApiExample, null, 2) }}</pre></div>
                                <div class="rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Contoh mapping</div><pre class="overflow-x-auto text-emerald-300">{{ JSON.stringify({ title: 'title', subtitle: 'subtitle', image: 'image', video_url: 'video_url' }, null, 2) }}</pre></div>
                            </div>
                            <div class="mt-4 rounded-md border bg-muted/30 p-4 text-sm leading-6"><strong>Catatan:</strong> mapping custom template memakai dot-path, misalnya <code>restaurant.name</code>. Untuk data berulang seperti banyak penerbangan atau banyak menu, gunakan template bawaan atau loop custom template yang dijelaskan di bawah.</div>
                            <div class="mt-4 rounded-md border border-violet-200 bg-violet-50 p-4 text-sm text-violet-950 dark:border-violet-900 dark:bg-violet-950/40 dark:text-violet-100"><strong>Custom table / flight:</strong> untuk data array, map field API ke <code>rows</code>, lalu gunakan loop <code v-pre>{{#each rows}}...{{/each}}</code>. Di dalam loop, akses kolom dengan <code v-pre>{{this.flight}}</code>, <code v-pre>{{this.destination}}</code>, atau field lain dari object row.</div>
                            <div class="mt-4 rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Contoh loop custom template</div><pre class="overflow-x-auto text-emerald-300">{{ customLoopStart }}
{{ customRowsExample }}
{{ customLoopEnd }}</pre></div>
                        </div>
                    </section>

                    <section id="api" class="scroll-mt-6 rounded-lg border bg-background p-6 shadow-sm md:p-8">
                        <div class="flex items-center gap-3"><Plug class="size-5 text-amber-600" /><h2 class="text-xl font-semibold">Panduan integrasi API</h2></div>
                        <ol class="mt-5 space-y-4 text-sm leading-6 text-muted-foreground"><li><strong class="text-foreground">1. Buat API Connection.</strong> Buka API Connections, pilih provider, masukkan credential bila perlu, lalu simpan.</li><li><strong class="text-foreground">2. Approve koneksi.</strong> Owner workspace harus menyetujui koneksi sebelum dapat digunakan playlist.</li><li><strong class="text-foreground">3. Pilih API pada item.</strong> Aktifkan API endpoint, pilih koneksi, lalu isi endpoint relatif.</li><li><strong class="text-foreground">4. Atur mapping.</strong> Mapping menggunakan dot path dan hasilnya masuk ke content template.</li></ol>
                        <div class="mt-6 rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 flex items-center gap-2 text-zinc-400"><Code2 class="size-4" />Contoh Weather endpoint</div><code class="break-all text-emerald-300">{{ weatherExample }}</code></div>
                        <div class="mt-4 rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Mapping Weather</div><pre class="overflow-x-auto text-emerald-300">{{ JSON.stringify({ title: 'current.temperature_2m', subtitle: 'timezone', temperature: 'current.temperature_2m', humidity: 'current.relative_humidity_2m', weather_code: 'current.weather_code', wind_speed: 'current.wind_speed_10m' }, null, 2) }}</pre></div>
                        <div class="mt-8 border-t pt-6">
                            <h3 class="text-lg font-semibold text-foreground">Restaurant Menu</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">Pilih template <strong class="text-foreground">Restaurant menu</strong>. Respons API harus memiliki array <code>items</code>. Setiap item dapat berisi nama, deskripsi, harga, kategori, dan URL gambar.</p>
                            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                <div class="rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Contoh response</div><pre class="overflow-x-auto text-emerald-300">{{ JSON.stringify(restaurantExample, null, 2) }}</pre></div>
                                <div class="rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Contoh mapping</div><pre class="overflow-x-auto text-emerald-300">{{ JSON.stringify({ items: 'items', name: 'items.*.name', description: 'items.*.description', price: 'items.*.price', image: 'items.*.image', category: 'items.*.category' }, null, 2) }}</pre></div>
                            </div>
                        </div>
                        <div class="mt-8 border-t pt-6">
                            <h3 class="text-lg font-semibold text-foreground">Flight Board</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">Pilih template <strong class="text-foreground">Flight Board</strong>. Gunakan array <code>rows</code> dari database penerbangan atau endpoint jadwal bandara. Nama field standar adalah <code>flight</code>, <code>destination</code>, <code>time</code>, <code>gate</code>, dan <code>status</code>.</p>
                            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                <div class="rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Contoh response</div><pre class="overflow-x-auto text-emerald-300">{{ JSON.stringify(flightExample, null, 2) }}</pre></div>
                                <div class="rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Contoh mapping</div><pre class="overflow-x-auto text-emerald-300">{{ JSON.stringify({ rows: 'rows', flight: 'rows.*.flight', destination: 'rows.*.destination', time: 'rows.*.time', gate: 'rows.*.gate', status: 'rows.*.status' }, null, 2) }}</pre></div>
                            </div>
                        </div>
                        <div class="mt-8 border-t pt-6">
                            <h3 class="text-lg font-semibold text-foreground">Data Table</h3>
                            <p class="mt-2 text-sm leading-6 text-muted-foreground">Pilih template <strong class="text-foreground">Data Table</strong>. Respons API berisi <code>columns</code> sebagai judul kolom dan <code>rows</code> sebagai data. Rows boleh berupa array nilai atau object dengan key yang sama seperti columns.</p>
                            <div class="mt-4 grid gap-4 xl:grid-cols-2">
                                <div class="rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Contoh response</div><pre class="overflow-x-auto text-emerald-300">{{ JSON.stringify(dataTableExample, null, 2) }}</pre></div>
                                <div class="rounded-md border bg-zinc-950 p-4 text-sm text-zinc-100"><div class="mb-2 text-zinc-400">Contoh mapping</div><pre class="overflow-x-auto text-emerald-300">{{ JSON.stringify({ columns: 'columns', rows: 'rows' }, null, 2) }}</pre></div>
                            </div>
                        </div>
                        <p class="mt-4 text-sm text-muted-foreground">Credential disimpan terenkripsi di server dan tidak dikirim ke player TV. Endpoint playlist sebaiknya mengembalikan JSON.</p>
                    </section>

                    <section id="audio" class="scroll-mt-6 rounded-lg border bg-background p-6 shadow-sm md:p-8">
                        <div class="flex items-center gap-3"><Volume2 class="size-5 text-rose-600" /><h2 class="text-xl font-semibold">Audio & Split Screen</h2></div>
                        <p class="mt-3 leading-7 text-muted-foreground">Upload audio melalui Media, lalu pilih Audio pada item playlist mana pun. Aktifkan Loop jika suara harus diputar berulang.</p>
                        <p class="mt-3 leading-7 text-muted-foreground">Split Screen membagi layar menjadi image kiri dan video, YouTube, URL video, atau web di kanan. Ukuran default adalah 33% dan 67%.</p>
                        <div class="mt-5 flex flex-wrap gap-3"><Link href="/media-assets" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium hover:bg-accent">Kelola Media <ArrowRight class="size-4" /></Link><Link href="/integrations/api" class="inline-flex items-center gap-2 rounded-md border px-3 py-2 text-sm font-medium hover:bg-accent">API Connections <ExternalLink class="size-4" /></Link></div>
                    </section>
                </div>

                <aside class="hidden lg:block"><div class="sticky top-6 rounded-lg border bg-background p-4 shadow-sm"><div class="text-xs font-semibold uppercase tracking-wider text-muted-foreground">Di halaman ini</div><nav class="mt-3 space-y-1"><a v-for="section in sections" :key="section.id" :href="`#${section.id}`" class="block rounded-md px-3 py-2 text-sm hover:bg-accent">{{ section.label }}</a></nav></div></aside>
            </main>
        </div>
    </div>
</template>
