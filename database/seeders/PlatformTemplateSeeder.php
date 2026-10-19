<?php

namespace Database\Seeders;

use App\Models\PlatformTemplate;
use App\Services\PlatformTemplateLibrary;
use Illuminate\Database\Seeder;

class PlatformTemplateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $definitions = (new ContentTemplateSeeder)->templates();

        $paidTemplates = [
            'campus-information-board' => 30,
            'campus-event-board' => 25,
            'image-background-board' => 40,
            'airport-departures-board' => 50,
            'product-showcase-board' => 35,
        ];

        foreach ($definitions as $index => $definition) {
            PlatformTemplate::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'html' => $definition['html'],
                    'css' => $definition['css'],
                    'schema' => $definition['schema'],
                    'token_cost' => $paidTemplates[$definition['slug']] ?? 0,
                    'is_active' => true,
                    'sort_order' => $index * 10,
                ],
            );
        }

        app(PlatformTemplateLibrary::class)->syncAll();
    }
}
