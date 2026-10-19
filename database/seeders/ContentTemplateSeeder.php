<?php

namespace Database\Seeders;

use App\Models\ContentTemplate;
use App\Models\Tenant;
use Illuminate\Database\Seeder;

class ContentTemplateSeeder extends Seeder
{
    public function run(): void
    {
        foreach (Tenant::query()->get() as $tenant) {
            foreach ($this->templates() as $template) {
                ContentTemplate::query()->updateOrCreate(
                    ['tenant_id' => $tenant->id, 'slug' => $template['slug']],
                    [
                        'name' => $template['name'],
                        'html' => $template['html'],
                        'css' => $template['css'],
                        'schema' => $template['schema'],
                        'is_active' => true,
                    ],
                );
            }
        }
    }

    /**
     * @return array<int, array{slug: string, name: string, html: string, css: string, schema: array<int, array<string, mixed>>}>
     */
    public function templates(): array
    {
        return [
            [
                'slug' => 'patient-queue-board',
                'name' => 'Patient Queue Board',
                'html' => <<<'HTML'
<main class="queue-board">
    <p class="eyebrow">{{ clinic }}</p>
    <h1>{{ title }}</h1>
    <div class="queue-number">{{ number }}</div>
    <div class="counter">{{ counter }}</div>
    <p class="message">{{ message }}</p>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.queue-board { display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 1.6vh; min-height: 100vh; padding: 8vh 10vw; color: #f8fafc; background: radial-gradient(circle at 50% 0%, #155e75, #0f172a 65%); font-family: Arial, sans-serif; text-align: center; }
.eyebrow { margin: 0; color: #a5f3fc; font-size: 1.6vw; font-weight: 700; letter-spacing: .16em; text-transform: uppercase; }
h1 { margin: 1.6vh 0 0; color: #e0f2fe; font-size: 3.4vw; font-weight: 600; }
.queue-number { margin: 1vh 0; font-size: 17vw; font-weight: 800; line-height: .9; letter-spacing: -.02em; }
.counter { padding: 1.6vh 2.4vw; border: .12vw solid rgba(165, 243, 252, .45); border-radius: 999px; color: #cffafe; font-size: 2.2vw; font-weight: 700; }
.message { margin: 1.4vh 0 0; color: #a5f3fc; font-size: 1.4vw; }
CSS,
                'schema' => [
                    ['name' => 'clinic', 'label' => 'Clinic name', 'type' => 'text', 'default' => 'DISI Medical Center'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Queue number'],
                    ['name' => 'number', 'label' => 'Queue number', 'type' => 'text', 'default' => 'A-102'],
                    ['name' => 'counter', 'label' => 'Counter', 'type' => 'text', 'default' => 'Go to Counter 03'],
                    ['name' => 'message', 'label' => 'Message', 'type' => 'text', 'default' => 'Please wait for your turn'],
                ],
            ],
            [
                'slug' => 'hospital-service-board',
                'name' => 'Hospital Service Board',
                'html' => <<<'HTML'
<main class="hospital-board">
    <header class="hospital-header">
        <div>
            <div class="hospital-name">{{ hospital }}</div>
            <div class="hospital-location">{{ location }}</div>
        </div>
        <div class="hospital-updated">Updated<br><strong>{{ updated_at }}</strong></div>
    </header>
    <section class="hospital-intro">
        <div>
            <div class="hospital-eyebrow">Patient services</div>
            <h1>{{ title }}</h1>
            <p>{{ subtitle }}</p>
        </div>
        <div class="hospital-callout">
            <span>NOW SERVING</span>
            <strong>{{ current_number }}</strong>
            <small>{{ current_room }}</small>
        </div>
    </section>
    <section class="hospital-grid">
        <article><span>{{ department_1 }}</span><strong>{{ number_1 }}</strong><small>{{ room_1 }}</small></article>
        <article><span>{{ department_2 }}</span><strong>{{ number_2 }}</strong><small>{{ room_2 }}</small></article>
        <article><span>{{ department_3 }}</span><strong>{{ number_3 }}</strong><small>{{ room_3 }}</small></article>
        <article><span>{{ department_4 }}</span><strong>{{ number_4 }}</strong><small>{{ room_4 }}</small></article>
    </section>
    <footer>
        <strong>{{ message }}</strong>
        <span>{{ emergency }}</span>
    </footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.hospital-board { min-height: 100vh; padding: 4vh 4vw; color: #0f2e28; background: #edf7f4; font-family: Arial, sans-serif; }
.hospital-header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 3vh; border-bottom: .15vw solid #b8d8cf; }
.hospital-name { color: #075e54; font-size: 2.3vw; font-weight: 800; }
.hospital-location { margin-top: .6vh; color: #52706a; font-size: 1.1vw; }
.hospital-updated { color: #52706a; font-size: .95vw; line-height: 1.5; text-align: right; }
.hospital-updated strong { color: #0f2e28; font-size: 1.15vw; }
.hospital-intro { display: grid; grid-template-columns: 1fr auto; gap: 4vw; align-items: center; padding: 4vh 0; }
.hospital-eyebrow { color: #0e8a78; font-size: 1vw; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
h1 { margin: 1vh 0; max-width: 56vw; color: #0f2e28; font-size: 3.6vw; line-height: 1.05; }
.hospital-intro p { margin: 0; color: #52706a; font-size: 1.4vw; }
.hospital-callout { min-width: 17vw; padding: 2.4vh 1.6vw; border-radius: .8vw; color: #fff; background: #075e54; text-align: center; }
.hospital-callout span { display: block; color: #a7f3d0; font-size: .85vw; font-weight: 800; letter-spacing: .14em; }
.hospital-callout strong { display: block; margin: .6vh 0; font-size: 4.6vw; line-height: 1; }
.hospital-callout small { color: #d1fae5; font-size: 1.1vw; }
.hospital-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.2vw; }
.hospital-grid article { min-height: 20vh; padding: 2.4vh 1.4vw; border: .1vw solid #c9e1db; border-radius: .7vw; background: #fff; box-shadow: 0 1vh 2.4vh rgba(7, 94, 84, .08); }
.hospital-grid span { display: block; min-height: 3.6vh; color: #52706a; font-size: 1.15vw; font-weight: 700; }
.hospital-grid strong { display: block; margin: 1.4vh 0 .6vh; color: #075e54; font-size: 3.6vw; }
.hospital-grid small { color: #0f2e28; font-size: 1vw; font-weight: 700; }
footer { display: flex; justify-content: space-between; gap: 3vw; margin-top: 3vh; padding: 2vh 1.6vw; border-radius: .6vw; color: #0f2e28; background: #c9f2e7; font-size: 1.1vw; }
footer span { color: #b42318; font-weight: 700; }
CSS,
                'schema' => [
                    ['name' => 'hospital', 'label' => 'Hospital name', 'type' => 'text', 'default' => 'Megonesia Medical Center'],
                    ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'default' => 'Main Lobby · Building A'],
                    ['name' => 'updated_at', 'label' => 'Updated at', 'type' => 'text', 'default' => '08:45 WIB'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Pelayanan hari ini'],
                    ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'text', 'default' => 'Pantau nomor antrean dan ruang tujuan Anda.'],
                    ['name' => 'current_number', 'label' => 'Current number', 'type' => 'text', 'default' => 'A-024'],
                    ['name' => 'current_room', 'label' => 'Current room', 'type' => 'text', 'default' => 'Poli Umum · Room 03'],
                    ['name' => 'department_1', 'label' => 'Department 1', 'type' => 'text', 'default' => 'Poli Umum'],
                    ['name' => 'number_1', 'label' => 'Queue 1', 'type' => 'text', 'default' => 'A-024'],
                    ['name' => 'room_1', 'label' => 'Room 1', 'type' => 'text', 'default' => 'Room 03'],
                    ['name' => 'department_2', 'label' => 'Department 2', 'type' => 'text', 'default' => 'Poli Anak'],
                    ['name' => 'number_2', 'label' => 'Queue 2', 'type' => 'text', 'default' => 'B-012'],
                    ['name' => 'room_2', 'label' => 'Room 2', 'type' => 'text', 'default' => 'Room 07'],
                    ['name' => 'department_3', 'label' => 'Department 3', 'type' => 'text', 'default' => 'Poli Gigi'],
                    ['name' => 'number_3', 'label' => 'Queue 3', 'type' => 'text', 'default' => 'C-009'],
                    ['name' => 'room_3', 'label' => 'Room 3', 'type' => 'text', 'default' => 'Room 11'],
                    ['name' => 'department_4', 'label' => 'Department 4', 'type' => 'text', 'default' => 'Laboratorium'],
                    ['name' => 'number_4', 'label' => 'Queue 4', 'type' => 'text', 'default' => 'L-018'],
                    ['name' => 'room_4', 'label' => 'Room 4', 'type' => 'text', 'default' => 'Level 2'],
                    ['name' => 'message', 'label' => 'Footer message', 'type' => 'text', 'default' => 'Siapkan kartu pasien sebelum memasuki ruang pemeriksaan.'],
                    ['name' => 'emergency', 'label' => 'Emergency message', 'type' => 'text', 'default' => 'Emergency · Call 118'],
                ],
            ],
            [
                'slug' => 'restaurant-menu-board',
                'name' => 'Restaurant Menu Board',
                'html' => <<<'HTML'
<main class="restaurant-board">
    <p class="brand">{{ restaurant }}</p>
    <p class="category">{{ category }}</p>
    <h1>{{ title }}</h1>
    <div class="items">{{ items }}</div>
    <p class="footer">{{ footer }}</p>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.restaurant-board { min-height: 100vh; padding: 7vh 10vw; color: #fff7ed; background: radial-gradient(circle at 50% 0%, #7c2d12, #431407 62%); font-family: Georgia, serif; text-align: center; }
.brand { margin: 0; color: #fdba74; font-size: 1.5vw; font-weight: 700; letter-spacing: .2em; text-transform: uppercase; }
.category { margin: 3vh 0 0; color: #fed7aa; font: 700 1.3vw Arial, sans-serif; letter-spacing: .18em; text-transform: uppercase; }
h1 { margin: 1vh 0 4vh; font-size: 4.4vw; line-height: 1.05; }
.items { display: inline-block; min-width: 58vw; padding: 3vh 4vw; border: .12vw dashed rgba(253, 186, 116, .5); border-radius: 1vw; font: 2vw/1.8 Arial, sans-serif; text-align: left; white-space: pre-line; }
.footer { margin-top: 5vh; color: #fdba74; font: 1.2vw Arial, sans-serif; letter-spacing: .08em; }
CSS,
                'schema' => [
                    ['name' => 'restaurant', 'label' => 'Restaurant', 'type' => 'text', 'default' => 'Kopi Senja'],
                    ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'default' => 'Today\'s menu'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Chef\'s selection'],
                    ['name' => 'items', 'label' => 'Menu items', 'type' => 'textarea', 'default' => 'Nasi Goreng   35K\nMie Ayam      30K\nEs Kopi Susu  22K'],
                    ['name' => 'footer', 'label' => 'Footer', 'type' => 'text', 'default' => 'Order at the counter'],
                ],
            ],
            [
                'slug' => 'restaurant-specials-board',
                'name' => 'Restaurant Specials Board',
                'html' => <<<'HTML'
<main class="specials-board">
    <header class="specials-header">
        <div>
            <p class="eyebrow">{{ tagline }}</p>
            <h1>{{ title }}</h1>
        </div>
        <div class="specials-date">{{ day }}<small>{{ hours }}</small></div>
    </header>
    <section class="specials-grid">
        {{#each dishes}}
        <article class="special-card">
            <span class="special-tag">{{ tag }}</span>
            <strong>{{ name }}</strong>
            <span class="special-price">{{ price }}</span>
            <p>{{ description }}</p>
        </article>
        {{/each}}
    </section>
    <footer class="specials-footer"><strong>{{ promo }}</strong><span>{{ note }}</span></footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.specials-board { min-height: 100vh; padding: 5vh 5vw; color: #fffbeb; background: radial-gradient(circle at 80% 0%, #7c2d12, #1c1917 60%); font-family: Arial, sans-serif; }
.specials-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 3vw; padding-bottom: 3vh; border-bottom: .15vw solid rgba(251, 191, 36, .35); }
.eyebrow { margin: 0 0 .8vh; color: #fbbf24; font-size: 1vw; font-weight: 800; letter-spacing: .22em; text-transform: uppercase; }
h1 { margin: 0; font-size: 3.8vw; line-height: 1.05; }
.specials-date { color: #fde68a; font-size: 1.3vw; font-weight: 700; text-align: right; }
.specials-date small { display: block; margin-top: .6vh; color: #a8a29e; font-size: .95vw; font-weight: 500; }
.specials-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 2vh 2.4vw; margin: 4vh 0; }
.special-card { display: grid; grid-template-columns: 1fr auto; align-items: start; gap: .8vh 1.4vw; padding: 2.4vh 1.8vw; border: .12vw solid rgba(251, 191, 36, .25); border-radius: .8vw; background: rgba(41, 37, 36, .8); }
.special-tag { grid-column: 1 / -1; justify-self: start; padding: .5vh .9vw; border-radius: 999px; color: #1c1917; background: #fbbf24; font-size: .8vw; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.special-card strong { color: #fef3c7; font-size: 1.7vw; }
.special-price { color: #fbbf24; font-size: 1.7vw; font-weight: 800; }
.special-card p { grid-column: 1 / -1; margin: 0; color: #a8a29e; font-size: 1.05vw; line-height: 1.5; }
.specials-footer { display: flex; justify-content: space-between; gap: 3vw; padding-top: 2.4vh; border-top: .12vw solid rgba(251, 191, 36, .25); font-size: 1.15vw; }
.specials-footer strong { color: #fbbf24; }
CSS,
                'schema' => [
                    ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'default' => 'Daily specials'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Menu spesial hari ini'],
                    ['name' => 'day', 'label' => 'Day', 'type' => 'text', 'default' => 'Jumat, 20 Oktober'],
                    ['name' => 'hours', 'label' => 'Hours', 'type' => 'text', 'default' => 'Buka 11.00 – 22.00'],
                    [
                        'name' => 'dishes',
                        'label' => 'Dishes',
                        'type' => 'json',
                        'default' => [
                            ['tag' => 'Favorit', 'name' => 'Nasi Goreng Kampung', 'price' => '38K', 'description' => 'Telur mata sapi, ayam suwir, kerupuk.'],
                            ['tag' => 'Baru', 'name' => 'Iga Bakar Madu', 'price' => '65K', 'description' => 'Iga sapi panggang dengan sambal kecap.'],
                            ['tag' => 'Hemat', 'name' => 'Mie Ayam Bakso', 'price' => '32K', 'description' => 'Mie ayam rumahan dengan bakso sapi.'],
                            ['tag' => 'Dessert', 'name' => 'Es Campur Senja', 'price' => '25K', 'description' => 'Buah segar, cincau, dan sirop gula aren.'],
                        ],
                    ],
                    ['name' => 'promo', 'label' => 'Promo', 'type' => 'text', 'default' => 'Diskon 10% untuk pemesanan sebelum 12.00'],
                    ['name' => 'note', 'label' => 'Footer note', 'type' => 'text', 'default' => 'Harga sudah termasuk pajak · Dine-in & takeaway'],
                ],
            ],
            [
                'slug' => 'cafe-menu-board',
                'name' => 'Cafe Menu Board',
                'html' => <<<'HTML'
<main class="cafe-board">
    <header class="cafe-header">
        <div>
            <p class="eyebrow">{{ tagline }}</p>
            <h1>{{ cafe }}</h1>
        </div>
        <div class="today">{{ today }}<small>{{ hours }}</small></div>
    </header>
    <section class="cafe-menu">
        {{#each menu}}
        <article class="cafe-item">
            <div class="cafe-item-head"><strong>{{ name }}</strong><span>{{ price }}</span></div>
            <p>{{ description }}</p>
        </article>
        {{/each}}
    </section>
    <footer class="cafe-footer">
        <strong>{{ promo }}</strong>
        <span>{{ footer_note }}</span>
    </footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.cafe-board { min-height: 100vh; padding: 5vh 5vw; color: #f5f5f4; background: radial-gradient(circle at 12% 8%, #292524, #1c1917 58%); font-family: Arial, sans-serif; }
.cafe-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 3vw; padding-bottom: 3vh; border-bottom: .15vw solid rgba(245, 158, 11, .35); }
.eyebrow { margin: 0 0 .8vh; color: #fbbf24; font-size: 1vw; font-weight: 800; letter-spacing: .22em; text-transform: uppercase; }
h1 { margin: 0; font-size: 4vw; line-height: 1; }
.today { color: #d6d3d1; font-size: 1.3vw; font-weight: 700; text-align: right; }
.today small { display: block; margin-top: .6vh; color: #a8a29e; font-size: .95vw; font-weight: 500; }
.cafe-menu { display: grid; grid-template-columns: repeat(2, 1fr); gap: 2vh 2.4vw; margin: 4vh 0; }
.cafe-item { padding: 2.4vh 1.6vw; border: .12vw solid rgba(245, 158, 11, .25); border-radius: .8vw; background: rgba(41, 37, 36, .8); }
.cafe-item-head { display: flex; align-items: baseline; justify-content: space-between; gap: 1.4vw; }
.cafe-item-head strong { font-size: 1.65vw; }
.cafe-item-head span { color: #fbbf24; font-size: 1.65vw; font-weight: 800; }
.cafe-item p { margin: 1vh 0 0; color: #a8a29e; font-size: 1.05vw; line-height: 1.5; }
.cafe-footer { display: flex; justify-content: space-between; gap: 3vw; padding-top: 2.4vh; border-top: .12vw solid rgba(245, 158, 11, .25); font-size: 1.15vw; }
.cafe-footer strong { color: #fbbf24; }
CSS,
                'schema' => [
                    ['name' => 'tagline', 'label' => 'Tagline', 'type' => 'text', 'default' => 'Coffee & Kitchen'],
                    ['name' => 'cafe', 'label' => 'Cafe name', 'type' => 'text', 'default' => 'Kopi Senja'],
                    ['name' => 'today', 'label' => 'Today label', 'type' => 'text', 'default' => 'Menu hari ini'],
                    ['name' => 'hours', 'label' => 'Opening hours', 'type' => 'text', 'default' => 'Buka 08.00 – 22.00'],
                    [
                        'name' => 'menu',
                        'label' => 'Menu items',
                        'type' => 'json',
                        'default' => [
                            ['name' => 'Espresso', 'price' => '20K', 'description' => 'Double shot, house blend'],
                            ['name' => 'Cappuccino', 'price' => '28K', 'description' => 'Espresso, steamed milk, foam'],
                            ['name' => 'Cafe Latte', 'price' => '30K', 'description' => 'Smooth milk coffee'],
                            ['name' => 'Cold Brew', 'price' => '32K', 'description' => 'Slow-steeped 18 hours'],
                            ['name' => 'Croissant', 'price' => '25K', 'description' => 'Butter croissant, freshly baked'],
                            ['name' => 'Banana Cake', 'price' => '22K', 'description' => 'Homemade, served warm'],
                        ],
                    ],
                    ['name' => 'promo', 'label' => 'Promo', 'type' => 'text', 'default' => 'Promo: beli 2 kopi gratis 1 pastry'],
                    ['name' => 'footer_note', 'label' => 'Footer note', 'type' => 'text', 'default' => 'Free WiFi · @kopisenja'],
                ],
            ],
            [
                'slug' => 'campus-information-board',
                'name' => 'Campus Information Board',
                'html' => <<<'HTML'
<main class="campus-board">
    <header class="campus-header">
        <div class="campus-brand">
            <span class="campus-logo">{{ initials }}</span>
            <div>
                <h1>{{ university }}</h1>
                <p>{{ faculty }}</p>
            </div>
        </div>
        <div class="campus-date">{{ today }}<small>{{ semester }}</small></div>
    </header>
    <section class="campus-body">
        <div class="campus-agenda">
            <h2>{{ agenda_title }}</h2>
            {{#each agenda}}
            <div class="agenda-row">
                <span class="agenda-time">{{ time }}</span>
                <div>
                    <strong>{{ title }}</strong>
                    <p>{{ place }}</p>
                </div>
            </div>
            {{/each}}
        </div>
        <aside class="campus-note">
            <p class="eyebrow">Pengumuman</p>
            <h2>{{ announcement_title }}</h2>
            <p>{{ announcement_body }}</p>
        </aside>
    </section>
    <footer class="campus-footer">
        <span>{{ contact }}</span>
        <strong>{{ website }}</strong>
    </footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.campus-board { min-height: 100vh; padding: 4vh 4vw; color: #0f172a; background: linear-gradient(160deg, #eef2ff, #e0e7ff 58%, #dbeafe); font-family: Arial, sans-serif; }
.campus-header { display: flex; align-items: center; justify-content: space-between; gap: 3vw; padding-bottom: 3vh; border-bottom: .2vw solid #1e3a8a; }
.campus-brand { display: flex; align-items: center; gap: 1.4vw; }
.campus-logo { display: grid; place-items: center; width: 5vw; height: 5vw; border-radius: 1vw; color: #fefce8; background: #1e3a8a; font-size: 1.8vw; font-weight: 800; letter-spacing: .06em; }
.campus-brand h1 { margin: 0; color: #1e3a8a; font-size: 2.2vw; }
.campus-brand p { margin: .5vh 0 0; color: #475569; font-size: 1.1vw; }
.campus-date { color: #1e3a8a; font-size: 1.3vw; font-weight: 700; text-align: right; }
.campus-date small { display: block; margin-top: .5vh; color: #64748b; font-size: .95vw; font-weight: 500; }
.campus-body { display: grid; grid-template-columns: 1.6fr 1fr; gap: 3vw; margin: 4vh 0; }
.campus-agenda h2 { margin: 0 0 2vh; color: #1e3a8a; font-size: 1.5vw; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.agenda-row { display: flex; gap: 1.4vw; padding: 1.8vh 1.4vw; border: .1vw solid #c7d2fe; border-radius: .6vw; background: #fff; }
.agenda-row + .agenda-row { margin-top: 1.2vh; }
.agenda-time { min-width: 6vw; color: #4338ca; font-size: 1.5vw; font-weight: 800; }
.agenda-row strong { font-size: 1.3vw; }
.agenda-row p { margin: .4vh 0 0; color: #64748b; font-size: 1vw; }
.campus-note { align-self: start; padding: 3vh 2vw; border-radius: .8vw; color: #fefce8; background: #1e3a8a; }
.campus-note .eyebrow { margin: 0; color: #fbbf24; font-size: .9vw; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
.campus-note h2 { margin: 1vh 0; font-size: 1.8vw; }
.campus-note p { margin: 0; color: #c7d2fe; font-size: 1.1vw; line-height: 1.6; }
.campus-footer { display: flex; justify-content: space-between; gap: 3vw; color: #475569; font-size: 1vw; }
.campus-footer strong { color: #1e3a8a; }
CSS,
                'schema' => [
                    ['name' => 'initials', 'label' => 'Initials', 'type' => 'text', 'default' => 'UB'],
                    ['name' => 'university', 'label' => 'University', 'type' => 'text', 'default' => 'Universitas Bina Cendekia'],
                    ['name' => 'faculty', 'label' => 'Faculty / building', 'type' => 'text', 'default' => 'Fakultas Teknik · Gedung A'],
                    ['name' => 'today', 'label' => 'Today', 'type' => 'text', 'default' => 'Senin, 20 Oktober 2026'],
                    ['name' => 'semester', 'label' => 'Semester', 'type' => 'text', 'default' => 'Semester Ganjil 2026/2027'],
                    ['name' => 'agenda_title', 'label' => 'Agenda title', 'type' => 'text', 'default' => 'Agenda hari ini'],
                    [
                        'name' => 'agenda',
                        'label' => 'Agenda',
                        'type' => 'json',
                        'default' => [
                            ['time' => '08.00', 'title' => 'Kuliah Algoritma', 'place' => 'Ruang 3.2 · Teknik Informatika'],
                            ['time' => '10.30', 'title' => 'Seminar Karier', 'place' => 'Auditorium Lt. 5'],
                            ['time' => '13.00', 'title' => 'Praktikum Basis Data', 'place' => 'Lab Komputer 2'],
                            ['time' => '15.30', 'title' => 'Rapat Himpunan', 'place' => 'Ruang B.1'],
                        ],
                    ],
                    ['name' => 'announcement_title', 'label' => 'Announcement title', 'type' => 'text', 'default' => 'Pengingat KRS'],
                    ['name' => 'announcement_body', 'label' => 'Announcement body', 'type' => 'textarea', 'default' => 'Batas pengisian KRS semester ganjil 25 Oktober 2026. Hubungi layanan akademik bila ada kendala.'],
                    ['name' => 'contact', 'label' => 'Contact', 'type' => 'text', 'default' => 'Layanan akademik · Gedung B Lt. 1'],
                    ['name' => 'website', 'label' => 'Website', 'type' => 'text', 'default' => 'campus.ac.id'],
                ],
            ],
            [
                'slug' => 'campus-event-board',
                'name' => 'Campus Event Board',
                'html' => <<<'HTML'
<main class="event-board">
    <section class="event-hero">
        <div class="event-date"><span>{{ month }}</span><strong>{{ day }}</strong></div>
        <div>
            <p class="eyebrow">{{ category }}</p>
            <h1>{{ title }}</h1>
            <p class="event-subtitle">{{ subtitle }}</p>
        </div>
    </section>
    <section class="event-details">
        <article><span>Waktu</span><strong>{{ time }}</strong></article>
        <article><span>Lokasi</span><strong>{{ location }}</strong></article>
        <article><span>Pembicara</span><strong>{{ speaker }}</strong></article>
        <article><span>Registrasi</span><strong>{{ registration }}</strong></article>
    </section>
    <footer class="event-footer"><strong>{{ organizer }}</strong><span>{{ contact }}</span></footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.event-board { min-height: 100vh; padding: 6vh 6vw; color: #f8fafc; background: radial-gradient(circle at 20% 10%, #4c1d95, #1e1b4b 60%); font-family: Arial, sans-serif; }
.event-hero { display: flex; align-items: center; gap: 3vw; padding-bottom: 4vh; border-bottom: .15vw solid rgba(196, 181, 253, .35); }
.event-date { display: grid; place-items: center; min-width: 13vw; padding: 3vh 2vw; border-radius: 1vw; color: #1e1b4b; background: #fbbf24; text-align: center; }
.event-date span { font-size: 1.2vw; font-weight: 800; letter-spacing: .16em; text-transform: uppercase; }
.event-date strong { font-size: 5vw; line-height: 1; }
.eyebrow { margin: 0 0 1vh; color: #c4b5fd; font-size: 1.1vw; font-weight: 800; letter-spacing: .2em; text-transform: uppercase; }
h1 { margin: 0; max-width: 60vw; font-size: 4.4vw; line-height: 1.05; }
.event-subtitle { margin: 1.6vh 0 0; max-width: 55vw; color: #ddd6fe; font-size: 1.6vw; }
.event-details { display: grid; grid-template-columns: repeat(4, 1fr); gap: 1.6vw; margin: 4vh 0; }
.event-details article { padding: 2.4vh 1.4vw; border: .12vw solid rgba(196, 181, 253, .3); border-radius: .8vw; background: rgba(30, 27, 75, .65); }
.event-details span { display: block; color: #c4b5fd; font-size: .9vw; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.event-details strong { display: block; margin-top: 1.2vh; font-size: 1.4vw; line-height: 1.35; }
.event-footer { display: flex; justify-content: space-between; gap: 3vw; padding-top: 2.4vh; border-top: .12vw solid rgba(196, 181, 253, .35); color: #ddd6fe; font-size: 1.1vw; }
.event-footer strong { color: #fbbf24; }
CSS,
                'schema' => [
                    ['name' => 'month', 'label' => 'Month', 'type' => 'text', 'default' => 'OKT'],
                    ['name' => 'day', 'label' => 'Day', 'type' => 'text', 'default' => '20'],
                    ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'default' => 'Seminar'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'AI untuk Riset Akademik'],
                    ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'Memahami penerapan AI generatif untuk penelitian dan publikasi.'],
                    ['name' => 'time', 'label' => 'Time', 'type' => 'text', 'default' => '09.00 – 12.00 WIB'],
                    ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'default' => 'Auditorium Lt. 5 · Gedung A'],
                    ['name' => 'speaker', 'label' => 'Speaker', 'type' => 'text', 'default' => 'Dr. Amira Putri · Peneliti AI'],
                    ['name' => 'registration', 'label' => 'Registration', 'type' => 'text', 'default' => 'Daftar di campus.ac.id/ai'],
                    ['name' => 'organizer', 'label' => 'Organizer', 'type' => 'text', 'default' => 'Fakultas Teknik · Universitas Bina Cendekia'],
                    ['name' => 'contact', 'label' => 'Contact', 'type' => 'text', 'default' => 'acara@campus.ac.id · ext. 214'],
                ],
            ],
            [
                'slug' => 'image-background-board',
                'name' => 'Image Background Board',
                'html' => <<<'HTML'
<main class="hero-board">
    <div class="hero-background" style="background-image: url('{{ background }}');"></div>
    <div class="hero-shade"></div>
    <header class="hero-header">
        <div class="hero-logo" style="background-image: url('{{ logo }}');"></div>
        <span>{{ brand }}</span>
    </header>
    <section class="hero-content">
        <p class="eyebrow">{{ eyebrow }}</p>
        <h1>{{ title }}</h1>
        <p class="subtitle">{{ subtitle }}</p>
        <div class="cta">{{ cta }}</div>
    </section>
    <footer class="hero-footer">
        <span>{{ footer_left }}</span>
        <span>{{ footer_right }}</span>
    </footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.hero-board { position: relative; min-height: 100vh; overflow: hidden; color: #f8fafc; background: #0f172a; font-family: Arial, sans-serif; }
.hero-background, .hero-shade { position: absolute; inset: 0; }
.hero-background { background-size: cover; background-position: center; }
.hero-shade { background: linear-gradient(90deg, rgba(15, 23, 42, .93), rgba(15, 23, 42, .5) 55%, rgba(15, 23, 42, .25)); }
.hero-header { position: relative; z-index: 1; display: flex; align-items: center; gap: 1.2vw; padding: 4vh 5vw 0; }
.hero-logo { width: 4.4vw; height: 4.4vw; border-radius: 1vw; background-color: #f8fafc; background-size: contain; background-position: center; background-repeat: no-repeat; }
.hero-header span { font-size: 1.4vw; font-weight: 700; letter-spacing: .1em; text-transform: uppercase; }
.hero-content { position: relative; z-index: 1; display: flex; flex-direction: column; justify-content: center; min-height: 70vh; max-width: 62vw; padding: 0 5vw; }
.eyebrow { margin: 0 0 1.4vh; color: #67e8f9; font-size: 1vw; font-weight: 800; letter-spacing: .22em; text-transform: uppercase; }
h1 { margin: 0; font-size: 5vw; line-height: 1.02; text-shadow: 0 .4vh 2vh rgba(0, 0, 0, .35); }
.subtitle { margin: 2vh 0 0; max-width: 46vw; color: #cbd5e1; font-size: 1.7vw; line-height: 1.5; }
.cta { align-self: flex-start; margin-top: 3.4vh; padding: 1.6vh 2vw; border-radius: 999px; color: #0f172a; background: #67e8f9; font-size: 1.3vw; font-weight: 800; }
.hero-footer { position: relative; z-index: 1; display: flex; justify-content: space-between; gap: 3vw; padding: 0 5vw 3vh; color: #cbd5e1; font-size: 1vw; }
CSS,
                'schema' => [
                    ['name' => 'brand', 'label' => 'Brand', 'type' => 'text', 'default' => 'Megonesia'],
                    ['name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => 'Signature event'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Malam Apresiasi Mitra'],
                    ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'Terima kasih atas kolaborasi Anda sepanjang tahun 2026.'],
                    ['name' => 'cta', 'label' => 'Call to action', 'type' => 'text', 'default' => 'Registrasi sekarang'],
                    ['name' => 'background', 'label' => 'Background image', 'type' => 'image', 'default' => 'https://images.unsplash.com/photo-1492684223066-81342ee5ff30?auto=format&fit=crop&w=1920&q=80'],
                    ['name' => 'logo', 'label' => 'Logo image', 'type' => 'image', 'default' => ''],
                    ['name' => 'footer_left', 'label' => 'Footer left', 'type' => 'text', 'default' => 'Megonesia HQ · Jakarta'],
                    ['name' => 'footer_right', 'label' => 'Footer right', 'type' => 'text', 'default' => '20 Oktober 2026 · 19.00 WIB'],
                ],
            ],
            [
                'slug' => 'airport-departures-board',
                'name' => 'Airport Departures Board',
                'html' => <<<'HTML'
<main class="airport-board">
    <header>
        <div><span class="airport-code">{{ airport_code }}</span><strong>{{ airport }}</strong></div>
        <div class="airport-time">{{ local_time }}<small>LOCAL TIME</small></div>
    </header>
    <section class="airport-title">
        <div><span>FLIGHT INFORMATION</span><h1>{{ title }}</h1></div>
        <p>{{ terminal }}</p>
    </section>
    <div class="flight-head"><span>TIME</span><span>FLIGHT</span><span>AIRLINE</span><span>DESTINATION</span><span>GATE</span><span>STATUS</span></div>
    {{#each flights}}
    <div class="flight-row {{ state_class }}"><strong>{{ time }}</strong><span>{{ flight }}</span><span>{{ airline }}</span><b>{{ destination }}</b><span>{{ gate }}</span><em>{{ status }}</em></div>
    {{/each}}
    <footer><strong>{{ notice }}</strong><span>{{ updated_at }}</span></footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.airport-board { min-height: 100vh; padding: 3.5vh 3vw; color: #f8fafc; background: #071018; font-family: Arial, sans-serif; }
header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 2.5vh; border-bottom: .15vw solid #33434f; }
header > div:first-child { display: flex; align-items: center; gap: 1.4vw; }
.airport-code { padding: 1.1vh 1vw; border-radius: .35vw; color: #071018; background: #facc15; font-size: 1.5vw; font-weight: 900; }
header strong { font-size: 1.5vw; }
.airport-time { font-size: 2.1vw; font-weight: 800; text-align: right; }
.airport-time small { display: block; margin-top: .4vh; color: #94a3b8; font-size: .7vw; letter-spacing: .14em; }
.airport-title { display: flex; align-items: end; justify-content: space-between; padding: 2.6vh 0; }
.airport-title span { color: #facc15; font-size: .8vw; font-weight: 800; letter-spacing: .18em; }
h1 { margin: .5vh 0 0; font-size: 3vw; line-height: 1; }
.airport-title p { margin: 0; color: #cbd5e1; font-size: 1.1vw; }
.flight-head, .flight-row { display: grid; grid-template-columns: .7fr .9fr 1.2fr 1.7fr .55fr 1fr; align-items: center; gap: 1.2vw; padding: 0 1.2vw; }
.flight-head { height: 4.6vh; color: #071018; background: #facc15; font-size: .72vw; font-weight: 900; letter-spacing: .08em; }
.flight-row { min-height: 8vh; border-bottom: .1vw solid #263642; font-size: 1.1vw; }
.flight-row:nth-of-type(even) { background: #0d1a23; }
.flight-row strong { color: #facc15; font-size: 1.4vw; }
.flight-row b { font-size: 1.2vw; }
.flight-row em { justify-self: start; padding: .6vh .7vw; border-radius: .3vw; color: #a5f3fc; background: #123746; font-size: .85vw; font-style: normal; font-weight: 800; }
.flight-row.boarding em { color: #052e16; background: #86efac; }
footer { display: flex; justify-content: space-between; gap: 3vw; margin-top: 2.6vh; color: #94a3b8; font-size: 1vw; }
footer strong { color: #f8fafc; }
CSS,
                'schema' => [
                    ['name' => 'airport_code', 'label' => 'Airport code', 'type' => 'text', 'default' => 'CGK'],
                    ['name' => 'airport', 'label' => 'Airport name', 'type' => 'text', 'default' => 'Soekarno-Hatta International Airport'],
                    ['name' => 'local_time', 'label' => 'Local time', 'type' => 'text', 'default' => '08:45'],
                    ['name' => 'title', 'label' => 'Board title', 'type' => 'text', 'default' => 'Departures'],
                    ['name' => 'terminal', 'label' => 'Terminal', 'type' => 'text', 'default' => 'Terminal 3 · International'],
                    [
                        'name' => 'flights',
                        'label' => 'Flights',
                        'type' => 'json',
                        'default' => [
                            ['time' => '09:10', 'flight' => 'GA 204', 'airline' => 'Garuda Indonesia', 'destination' => 'Denpasar · Bali', 'gate' => 'A12', 'status' => 'BOARDING', 'state_class' => 'boarding'],
                            ['time' => '09:25', 'flight' => 'SQ 951', 'airline' => 'Singapore Airlines', 'destination' => 'Singapore', 'gate' => 'B04', 'status' => 'FINAL CALL', 'state_class' => ''],
                            ['time' => '09:40', 'flight' => 'QZ 7520', 'airline' => 'AirAsia', 'destination' => 'Kuala Lumpur', 'gate' => 'C09', 'status' => 'ON TIME', 'state_class' => ''],
                            ['time' => '10:05', 'flight' => 'JT 650', 'airline' => 'Lion Air', 'destination' => 'Lombok', 'gate' => 'D02', 'status' => 'ON TIME', 'state_class' => ''],
                            ['time' => '10:20', 'flight' => 'CX 798', 'airline' => 'Cathay Pacific', 'destination' => 'Hong Kong', 'gate' => 'B11', 'status' => 'DELAYED', 'state_class' => ''],
                            ['time' => '10:45', 'flight' => 'EK 357', 'airline' => 'Emirates', 'destination' => 'Dubai', 'gate' => 'A08', 'status' => 'CHECK-IN', 'state_class' => ''],
                        ],
                    ],
                    ['name' => 'notice', 'label' => 'Notice', 'type' => 'text', 'default' => 'Please be at the gate 30 minutes before departure.'],
                    ['name' => 'updated_at', 'label' => 'Updated at', 'type' => 'text', 'default' => 'Last updated 08:43 WIB'],
                ],
            ],
            [
                'slug' => 'service-counter-board',
                'name' => 'Service Counter Board',
                'html' => <<<'HTML'
<main class="counter-board">
    <header class="counter-header">
        <div>
            <p class="eyebrow">{{ location }}</p>
            <h1>{{ title }}</h1>
        </div>
        <div class="counter-clock">{{ time }}<small>{{ date }}</small></div>
    </header>
    <section class="counter-main">
        <div class="counter-current">
            <span>NOW SERVING</span>
            <strong>{{ current_number }}</strong>
            <p>{{ current_room }}</p>
        </div>
        <div class="counter-grid">
            {{#each counters}}
            <article class="counter-card {{ state_class }}">
                <span>{{ label }}</span>
                <strong>{{ number }}</strong>
                <small>{{ note }}</small>
            </article>
            {{/each}}
        </div>
    </section>
    <footer class="counter-footer"><strong>{{ message }}</strong><span>{{ queue_info }}</span></footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.counter-board { min-height: 100vh; padding: 4vh 4vw; color: #0f172a; background: linear-gradient(150deg, #e0f2fe, #f0f9ff 65%, #e0e7ff); font-family: Arial, sans-serif; }
.counter-header { display: flex; align-items: center; justify-content: space-between; padding-bottom: 3vh; border-bottom: .2vw solid #0ea5e9; }
.eyebrow { margin: 0 0 .8vh; color: #0369a1; font-size: 1vw; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
h1 { margin: 0; color: #0c4a6e; font-size: 2.4vw; }
.counter-clock { color: #0c4a6e; font-size: 2vw; font-weight: 800; text-align: right; }
.counter-clock small { display: block; margin-top: .5vh; color: #475569; font-size: .95vw; font-weight: 500; }
.counter-main { display: grid; grid-template-columns: 1fr 1.4fr; gap: 3vw; margin: 4vh 0; }
.counter-current { display: flex; flex-direction: column; align-items: center; justify-content: center; padding: 4vh 2vw; border-radius: 1vw; color: #f0f9ff; background: #0284c7; box-shadow: 0 2vh 4vh rgba(2, 132, 199, .25); }
.counter-current span { color: #bae6fd; font-size: 1vw; font-weight: 800; letter-spacing: .18em; }
.counter-current strong { margin: 1.4vh 0; font-size: 8vw; line-height: 1; }
.counter-current p { margin: 0; color: #e0f2fe; font-size: 1.3vw; }
.counter-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1.6vw; }
.counter-card { padding: 2.4vh 1.6vw; border: .12vw solid #bae6fd; border-radius: .8vw; background: #fff; }
.counter-card span { display: block; color: #0369a1; font-size: 1vw; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.counter-card strong { display: block; margin: 1vh 0 .6vh; color: #0c4a6e; font-size: 3vw; }
.counter-card small { color: #475569; font-size: 1vw; }
.counter-card.active { border-color: #0284c7; background: #f0f9ff; }
.counter-footer { display: flex; justify-content: space-between; gap: 3vw; padding-top: 2.4vh; border-top: .15vw solid #bae6fd; color: #475569; font-size: 1.05vw; }
.counter-footer strong { color: #0c4a6e; }
CSS,
                'schema' => [
                    ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'default' => 'Kantor Pusat · Lantai 1'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Nomor antrean layanan'],
                    ['name' => 'time', 'label' => 'Time', 'type' => 'text', 'default' => '09:42'],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'text', 'default' => 'Senin, 20 Oktober 2026'],
                    ['name' => 'current_number', 'label' => 'Current number', 'type' => 'text', 'default' => 'B-014'],
                    ['name' => 'current_room', 'label' => 'Current counter', 'type' => 'text', 'default' => 'Loket 02 · Pembayaran'],
                    [
                        'name' => 'counters',
                        'label' => 'Counters',
                        'type' => 'json',
                        'default' => [
                            ['label' => 'Loket 01', 'number' => 'A-021', 'note' => 'Informasi', 'state_class' => ''],
                            ['label' => 'Loket 02', 'number' => 'B-014', 'note' => 'Pembayaran', 'state_class' => 'active'],
                            ['label' => 'Loket 03', 'number' => 'C-007', 'note' => 'Pengaduan', 'state_class' => ''],
                            ['label' => 'Loket 04', 'number' => 'D-003', 'note' => 'Legalitas', 'state_class' => ''],
                        ],
                    ],
                    ['name' => 'message', 'label' => 'Footer message', 'type' => 'text', 'default' => 'Mohon menunggu nomor antrean Anda dipanggil'],
                    ['name' => 'queue_info', 'label' => 'Queue info', 'type' => 'text', 'default' => 'Sisa antrean: 18 orang'],
                ],
            ],
            [
                'slug' => 'cinema-showtimes-board',
                'name' => 'Cinema Showtimes Board',
                'html' => <<<'HTML'
<main class="cinema-board">
    <header class="cinema-header">
        <div>
            <p class="eyebrow">{{ cinema }}</p>
            <h1>{{ title }}</h1>
        </div>
        <div class="cinema-date">{{ date }}<small>{{ location }}</small></div>
    </header>
    <section class="cinema-list">
        {{#each movies}}
        <article class="cinema-row">
            <div class="cinema-info">
                <strong>{{ title }}</strong>
                <span>{{ genre }} · {{ duration }}</span>
            </div>
            <span class="cinema-times">{{ times }}</span>
            <span class="cinema-studio">{{ studio }}</span>
            <span class="cinema-status {{ state_class }}">{{ status }}</span>
        </article>
        {{/each}}
    </section>
    <footer class="cinema-footer"><span>{{ note }}</span><strong>{{ ticket }}</strong></footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.cinema-board { min-height: 100vh; padding: 4vh 4vw; color: #f8fafc; background: radial-gradient(circle at 80% 0%, #1e1b4b, #09090b 60%); font-family: Arial, sans-serif; }
.cinema-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 3vw; padding-bottom: 3vh; border-bottom: .15vw solid rgba(250, 204, 21, .35); }
.eyebrow { margin: 0 0 .8vh; color: #facc15; font-size: 1vw; font-weight: 800; letter-spacing: .22em; text-transform: uppercase; }
h1 { margin: 0; font-size: 3vw; line-height: 1.05; }
.cinema-date { color: #e2e8f0; font-size: 1.3vw; font-weight: 700; text-align: right; }
.cinema-date small { display: block; margin-top: .5vh; color: #94a3b8; font-size: .9vw; font-weight: 500; }
.cinema-list { display: flex; flex-direction: column; gap: 1.4vh; margin: 4vh 0; }
.cinema-row { display: grid; grid-template-columns: 1.6fr 1.5fr .6fr .7fr; align-items: center; gap: 2vw; padding: 2.2vh 1.6vw; border: .1vw solid rgba(148, 163, 184, .25); border-radius: .7vw; background: rgba(30, 27, 75, .45); }
.cinema-info strong { display: block; font-size: 1.6vw; }
.cinema-info span { color: #cbd5e1; font-size: 1vw; }
.cinema-times { color: #facc15; font-size: 1.4vw; font-weight: 800; letter-spacing: .02em; }
.cinema-studio { color: #cbd5e1; font-size: 1vw; font-weight: 700; }
.cinema-status { justify-self: start; padding: .6vh .8vw; border-radius: 999px; color: #a7f3d0; background: #064e3b; font-size: .85vw; font-weight: 800; letter-spacing: .08em; }
.cinema-status.sold-out { color: #fecdd3; background: #7f1d1d; }
.cinema-footer { display: flex; justify-content: space-between; gap: 3vw; padding-top: 2.4vh; border-top: .12vw solid rgba(148, 163, 184, .25); color: #94a3b8; font-size: 1vw; }
.cinema-footer strong { color: #facc15; }
CSS,
                'schema' => [
                    ['name' => 'cinema', 'label' => 'Cinema', 'type' => 'text', 'default' => 'Megonesia Cinema'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Jadwal film hari ini'],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'text', 'default' => 'Senin, 20 Oktober 2026'],
                    ['name' => 'location', 'label' => 'Location', 'type' => 'text', 'default' => 'Studio 1–4 · Lantai 3'],
                    [
                        'name' => 'movies',
                        'label' => 'Movies',
                        'type' => 'json',
                        'default' => [
                            ['title' => 'Laut Bercerita', 'genre' => 'Drama', 'duration' => '2j 10m', 'times' => '12:30 · 15:10 · 18:00', 'studio' => 'Studio 1', 'status' => 'AVAILABLE', 'state_class' => ''],
                            ['title' => 'Petualangan Rimba', 'genre' => 'Animasi', 'duration' => '1j 45m', 'times' => '11:00 · 13:20 · 16:40', 'studio' => 'Studio 2', 'status' => 'AVAILABLE', 'state_class' => ''],
                            ['title' => 'Malam Terakhir', 'genre' => 'Thriller', 'duration' => '1j 58m', 'times' => '14:00 · 17:30 · 20:10', 'studio' => 'Studio 3', 'status' => 'SOLD OUT', 'state_class' => 'sold-out'],
                            ['title' => 'Kisah Kota Tua', 'genre' => 'Sejarah', 'duration' => '2j 05m', 'times' => '12:00 · 15:00 · 19:30', 'studio' => 'Studio 4', 'status' => 'AVAILABLE', 'state_class' => ''],
                        ],
                    ],
                    ['name' => 'note', 'label' => 'Footer note', 'type' => 'text', 'default' => 'Tiket dapat dibeli di loket dan aplikasi'],
                    ['name' => 'ticket', 'label' => 'Ticket info', 'type' => 'text', 'default' => 'Harga tiket mulai Rp 45.000'],
                ],
            ],
            [
                'slug' => 'hotel-lobby-board',
                'name' => 'Hotel Lobby Board',
                'html' => <<<'HTML'
<main class="hotel-board">
    <div class="hotel-background" style="background-image: url('{{ background }}');"></div>
    <div class="hotel-shade"></div>
    <section class="hotel-content">
        <p class="eyebrow">{{ eyebrow }}</p>
        <h1>{{ title }}</h1>
        <p class="subtitle">{{ subtitle }}</p>
        <div class="hotel-grid">
            <article><span>Check-in</span><strong>{{ checkin }}</strong></article>
            <article><span>Check-out</span><strong>{{ checkout }}</strong></article>
            <article><span>WiFi</span><strong>{{ wifi }}</strong></article>
            <article><span>Concierge</span><strong>{{ concierge }}</strong></article>
        </div>
        <p class="hotel-note">{{ note }}</p>
    </section>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.hotel-board { position: relative; min-height: 100vh; overflow: hidden; color: #f8fafc; background: #1c1917; font-family: Georgia, serif; }
.hotel-background, .hotel-shade { position: absolute; inset: 0; }
.hotel-background { background-size: cover; background-position: center; }
.hotel-shade { background: linear-gradient(100deg, rgba(28, 25, 23, .94), rgba(28, 25, 23, .55) 60%, rgba(28, 25, 23, .2)); }
.hotel-content { position: relative; z-index: 1; display: flex; flex-direction: column; justify-content: center; min-height: 100vh; max-width: 66vw; padding: 6vh 6vw; }
.eyebrow { margin: 0 0 1.2vh; color: #fcd34d; font-size: 1vw; font-weight: 800; letter-spacing: .24em; text-transform: uppercase; }
h1 { margin: 0; font-size: 4.6vw; line-height: 1.05; }
.subtitle { margin: 1.8vh 0 0; max-width: 48vw; color: #e7e5e4; font-size: 1.6vw; font-family: Arial, sans-serif; line-height: 1.5; }
.hotel-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 1.4vw; max-width: 46vw; margin: 4vh 0 0; }
.hotel-grid article { padding: 2vh 1.4vw; border: .1vw solid rgba(252, 211, 77, .35); border-radius: .7vw; background: rgba(28, 25, 23, .55); }
.hotel-grid span { display: block; color: #fcd34d; font-size: .85vw; font-family: Arial, sans-serif; font-weight: 800; letter-spacing: .14em; text-transform: uppercase; }
.hotel-grid strong { display: block; margin-top: 1vh; color: #fafaf9; font-size: 1.3vw; font-family: Arial, sans-serif; font-weight: 700; }
.hotel-note { margin: 4vh 0 0; color: #d6d3d1; font-size: 1vw; font-family: Arial, sans-serif; }
CSS,
                'schema' => [
                    ['name' => 'eyebrow', 'label' => 'Eyebrow', 'type' => 'text', 'default' => 'Welcome to'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Hotel Senja Nusantara'],
                    ['name' => 'subtitle', 'label' => 'Subtitle', 'type' => 'textarea', 'default' => 'Terima kasih telah menginap bersama kami. Selamat menikmati masa menginap Anda.'],
                    ['name' => 'background', 'label' => 'Background image', 'type' => 'image', 'default' => ''],
                    ['name' => 'checkin', 'label' => 'Check-in', 'type' => 'text', 'default' => '14.00'],
                    ['name' => 'checkout', 'label' => 'Check-out', 'type' => 'text', 'default' => '12.00'],
                    ['name' => 'wifi', 'label' => 'WiFi', 'type' => 'text', 'default' => 'SenjaNusantara'],
                    ['name' => 'concierge', 'label' => 'Concierge', 'type' => 'text', 'default' => 'Ext. 100'],
                    ['name' => 'note', 'label' => 'Note', 'type' => 'text', 'default' => 'Resepsionis tersedia 24 jam · Sarapan 06.00–10.00'],
                ],
            ],
            [
                'slug' => 'school-schedule-board',
                'name' => 'School Schedule Board',
                'html' => <<<'HTML'
<main class="school-board">
    <header class="school-header">
        <div>
            <p class="eyebrow">{{ school }}</p>
            <h1>{{ title }}</h1>
            <p class="school-class">{{ classroom }}</p>
        </div>
        <div class="school-date">{{ date }}<small>{{ semester }}</small></div>
    </header>
    <section class="school-schedule">
        <div class="schedule-head"><span>Jam</span><span>Mata pelajaran</span><span>Guru</span><span>Ruang</span></div>
        {{#each lessons}}
        <div class="schedule-row"><strong>{{ time }}</strong><span>{{ subject }}</span><span>{{ teacher }}</span><span>{{ room }}</span></div>
        {{/each}}
    </section>
    <footer class="school-footer"><span>{{ note }}</span><strong>{{ contact }}</strong></footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.school-board { min-height: 100vh; padding: 4vh 4vw; color: #1e293b; background: #f1f5f9; font-family: Arial, sans-serif; }
.school-header { display: flex; align-items: center; justify-content: space-between; gap: 3vw; padding-bottom: 3vh; border-bottom: .2vw solid #2563eb; }
.eyebrow { margin: 0 0 .6vh; color: #2563eb; font-size: 1vw; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
h1 { margin: 0; color: #1e3a8a; font-size: 2.4vw; }
.school-class { margin: .6vh 0 0; color: #475569; font-size: 1.2vw; }
.school-date { color: #1e3a8a; font-size: 1.2vw; font-weight: 700; text-align: right; }
.school-date small { display: block; margin-top: .5vh; color: #64748b; font-size: .9vw; font-weight: 500; }
.school-schedule { margin: 3vh 0; overflow: hidden; border: .1vw solid #cbd5e1; border-radius: .7vw; background: #fff; }
.schedule-head, .schedule-row { display: grid; grid-template-columns: .8fr 1.6fr 1.2fr .8fr; align-items: center; gap: 1.4vw; padding: 1.6vh 1.6vw; }
.schedule-head { color: #f8fafc; background: #1e3a8a; font-size: .85vw; font-weight: 800; letter-spacing: .12em; text-transform: uppercase; }
.schedule-row + .schedule-row { border-top: .08vw solid #e2e8f0; }
.schedule-row strong { color: #2563eb; font-size: 1.2vw; }
.schedule-row span { font-size: 1.05vw; }
.school-footer { display: flex; justify-content: space-between; gap: 3vw; color: #64748b; font-size: 1vw; }
.school-footer strong { color: #1e3a8a; }
CSS,
                'schema' => [
                    ['name' => 'school', 'label' => 'School', 'type' => 'text', 'default' => 'SMA Nusantara Cendekia'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Jadwal pelajaran'],
                    ['name' => 'classroom', 'label' => 'Classroom', 'type' => 'text', 'default' => 'Kelas XI IPA 2 · Wali kelas: Bu Ratna'],
                    ['name' => 'date', 'label' => 'Date', 'type' => 'text', 'default' => 'Senin, 20 Oktober 2026'],
                    ['name' => 'semester', 'label' => 'Semester', 'type' => 'text', 'default' => 'Semester Ganjil 2026/2027'],
                    [
                        'name' => 'lessons',
                        'label' => 'Lessons',
                        'type' => 'json',
                        'default' => [
                            ['time' => '07.00', 'subject' => 'Matematika', 'teacher' => 'Pak Andi', 'room' => 'R. 12'],
                            ['time' => '08.30', 'subject' => 'Fisika', 'teacher' => 'Bu Sari', 'room' => 'Lab IPA'],
                            ['time' => '10.00', 'subject' => 'Bahasa Indonesia', 'teacher' => 'Bu Dewi', 'room' => 'R. 12'],
                            ['time' => '11.30', 'subject' => 'Istirahat', 'teacher' => '—', 'room' => 'Kantin'],
                            ['time' => '12.30', 'subject' => 'Sejarah', 'teacher' => 'Pak Budi', 'room' => 'R. 12'],
                            ['time' => '14.00', 'subject' => 'Bahasa Inggris', 'teacher' => 'Ms. Lina', 'room' => 'R. 12'],
                        ],
                    ],
                    ['name' => 'note', 'label' => 'Note', 'type' => 'text', 'default' => 'Bel berbunyi 5 menit sebelum pelajaran dimulai.'],
                    ['name' => 'contact', 'label' => 'Contact', 'type' => 'text', 'default' => 'Tata usaha · Gedung A Lt. 1'],
                ],
            ],
            [
                'slug' => 'product-showcase-board',
                'name' => 'Product Showcase Board',
                'html' => <<<'HTML'
<main class="showcase-board">
    <header class="showcase-header">
        <div>
            <p class="eyebrow">{{ store }}</p>
            <h1>{{ title }}</h1>
        </div>
        <span class="showcase-promo">{{ promo }}</span>
    </header>
    <section class="showcase-grid">
        <article class="product-card">
            <div class="product-image" style="background-image: url('{{ product_1_image }}');"></div>
            <strong>{{ product_1_name }}</strong>
            <div class="product-price"><span>{{ product_1_price }}</span></div>
        </article>
        <article class="product-card">
            <div class="product-image" style="background-image: url('{{ product_2_image }}');"></div>
            <strong>{{ product_2_name }}</strong>
            <div class="product-price"><span>{{ product_2_price }}</span></div>
        </article>
        <article class="product-card">
            <div class="product-image" style="background-image: url('{{ product_3_image }}');"></div>
            <strong>{{ product_3_name }}</strong>
            <div class="product-price"><span>{{ product_3_price }}</span></div>
        </article>
    </section>
    <footer class="showcase-footer"><span>{{ footer_left }}</span><strong>{{ footer_right }}</strong></footer>
</main>
HTML,
                'css' => <<<'CSS'
* { box-sizing: border-box; }
.showcase-board { min-height: 100vh; padding: 4vh 4vw; color: #1f2937; background: #f8fafc; font-family: Arial, sans-serif; }
.showcase-header { display: flex; align-items: flex-end; justify-content: space-between; gap: 3vw; padding-bottom: 3vh; border-bottom: .2vw solid #0f766e; }
.eyebrow { margin: 0 0 .8vh; color: #0f766e; font-size: 1vw; font-weight: 800; letter-spacing: .18em; text-transform: uppercase; }
h1 { margin: 0; color: #134e4a; font-size: 2.8vw; }
.showcase-promo { padding: 1.2vh 1.6vw; border-radius: 999px; color: #fff; background: #ef4444; font-size: 1.2vw; font-weight: 800; }
.showcase-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 2vw; margin: 4vh 0; }
.product-card { overflow: hidden; border: .1vw solid #e2e8f0; border-radius: 1vw; background: #fff; box-shadow: 0 1.2vh 2.6vh rgba(15, 118, 110, .08); }
.product-image { height: 24vh; background-color: #e2e8f0; background-size: cover; background-position: center; }
.product-card strong { display: block; padding: 2vh 1.4vw 0; color: #134e4a; font-size: 1.4vw; }
.product-price { display: flex; align-items: baseline; justify-content: space-between; gap: 1vw; padding: 1vh 1.4vw 2vh; }
.product-price span { color: #0f766e; font-size: 1.6vw; font-weight: 800; }
.showcase-footer { display: flex; justify-content: space-between; gap: 3vw; padding-top: 2.4vh; border-top: .12vw solid #e2e8f0; color: #64748b; font-size: 1vw; }
.showcase-footer strong { color: #134e4a; }
CSS,
                'schema' => [
                    ['name' => 'store', 'label' => 'Store', 'type' => 'text', 'default' => 'Megonesia Store'],
                    ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Produk pilihan minggu ini'],
                    ['name' => 'promo', 'label' => 'Promo', 'type' => 'text', 'default' => 'Diskon 20%'],
                    ['name' => 'product_1_image', 'label' => 'Product 1 image', 'type' => 'image', 'default' => ''],
                    ['name' => 'product_1_name', 'label' => 'Product 1 name', 'type' => 'text', 'default' => 'Tas Kanvas Senja'],
                    ['name' => 'product_1_price', 'label' => 'Product 1 price', 'type' => 'text', 'default' => 'Rp 189.000'],
                    ['name' => 'product_2_image', 'label' => 'Product 2 image', 'type' => 'image', 'default' => ''],
                    ['name' => 'product_2_name', 'label' => 'Product 2 name', 'type' => 'text', 'default' => 'Sepatu Lari Cepat'],
                    ['name' => 'product_2_price', 'label' => 'Product 2 price', 'type' => 'text', 'default' => 'Rp 549.000'],
                    ['name' => 'product_3_image', 'label' => 'Product 3 image', 'type' => 'image', 'default' => ''],
                    ['name' => 'product_3_name', 'label' => 'Product 3 name', 'type' => 'text', 'default' => 'Botol Minum Elegan'],
                    ['name' => 'product_3_price', 'label' => 'Product 3 price', 'type' => 'text', 'default' => 'Rp 129.000'],
                    ['name' => 'footer_left', 'label' => 'Footer left', 'type' => 'text', 'default' => 'Tersedia di seluruh cabang'],
                    ['name' => 'footer_right', 'label' => 'Footer right', 'type' => 'text', 'default' => 'Promo berlaku hingga 31 Oktober'],
                ],
            ],
        ];
    }
}
