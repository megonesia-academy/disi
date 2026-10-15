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
            ContentTemplate::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'patient-queue-board'],
                [
                    'name' => 'Patient Queue Board',
                    'html' => <<<'HTML'
<main class="queue-board">
    <div class="eyebrow">{{ clinic }}</div>
    <h1>{{ title }}</h1>
    <div class="queue-number">{{ number }}</div>
    <div class="counter">{{ counter }}</div>
    <p class="message">{{ message }}</p>
</main>
HTML,
                    'css' => <<<'CSS'
.queue-board { min-height: 100vh; box-sizing: border-box; padding: 8vh 10vw; color: #f8fafc; background: linear-gradient(135deg, #0f172a, #155e75); font-family: Arial, sans-serif; text-align: center; }
.eyebrow { color: #a5f3fc; font-size: 2vw; letter-spacing: .12em; text-transform: uppercase; }
h1 { margin: 2vh 0; font-size: 5vw; }
.queue-number { font-size: 18vw; font-weight: 800; line-height: .9; }
.counter { margin-top: 3vh; font-size: 4vw; font-weight: 700; }
.message { margin-top: 5vh; font-size: 2vw; color: #cffafe; }
CSS,
                    'schema' => [
                        ['name' => 'clinic', 'label' => 'Clinic name', 'type' => 'text', 'default' => 'DISI Medical Center'],
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Queue number'],
                        ['name' => 'number', 'label' => 'Queue number', 'type' => 'text', 'default' => 'A-102'],
                        ['name' => 'counter', 'label' => 'Counter', 'type' => 'text', 'default' => 'Go to Counter 03'],
                        ['name' => 'message', 'label' => 'Message', 'type' => 'text', 'default' => 'Please wait for your turn'],
                    ],
                    'is_active' => true,
                ],
            );

            ContentTemplate::query()->updateOrCreate(
                ['tenant_id' => $tenant->id, 'slug' => 'restaurant-menu-board'],
                [
                    'name' => 'Restaurant Menu Board',
                    'html' => <<<'HTML'
<main class="menu-board">
    <div class="brand">{{ restaurant }}</div>
    <p class="category">{{ category }}</p>
    <h1>{{ title }}</h1>
    <div class="items">{{ items }}</div>
    <p class="footer">{{ footer }}</p>
</main>
HTML,
                    'css' => <<<'CSS'
.menu-board { min-height: 100vh; box-sizing: border-box; padding: 8vh 10vw; color: #fff7ed; background: #431407; font-family: Georgia, serif; text-align: center; }
.brand { color: #fdba74; font-size: 2.4vw; letter-spacing: .16em; text-transform: uppercase; }
.category { margin-top: 4vh; color: #fed7aa; font: 700 2vw Arial, sans-serif; text-transform: uppercase; }
h1 { margin: 1vh 0 5vh; font-size: 6vw; }
.items { white-space: pre-line; font: 2.6vw/1.7 Arial, sans-serif; }
.footer { margin-top: 7vh; color: #fdba74; font: 1.8vw Arial, sans-serif; }
CSS,
                    'schema' => [
                        ['name' => 'restaurant', 'label' => 'Restaurant', 'type' => 'text', 'default' => 'Kopi Senja'],
                        ['name' => 'category', 'label' => 'Category', 'type' => 'text', 'default' => 'Today\'s menu'],
                        ['name' => 'title', 'label' => 'Title', 'type' => 'text', 'default' => 'Chef\'s selection'],
                        ['name' => 'items', 'label' => 'Menu items', 'type' => 'textarea', 'default' => 'Nasi Goreng   35K\nMie Ayam      30K\nEs Kopi Susu  22K'],
                        ['name' => 'footer', 'label' => 'Footer', 'type' => 'text', 'default' => 'Order at the counter'],
                    ],
                    'is_active' => true,
                ],
            );
        }
    }
}
