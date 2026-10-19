<?php

namespace Database\Seeders;

use App\Models\FeaturePrice;
use Illuminate\Database\Seeder;

class FeaturePriceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $features = [
            [
                'key' => 'monthly_subscription',
                'label' => 'Langganan bulanan',
                'description' => 'Biaya langganan platform yang didebit otomatis setiap bulan.',
                'kind' => FeaturePrice::KIND_SUBSCRIPTION,
                'token_cost' => 100,
                'free_limit' => 0,
                'unit_label' => 'bulan',
                'sort_order' => 0,
            ],
            [
                'key' => 'extra_screen',
                'label' => 'Slot screen tambahan',
                'description' => 'Paket gratis mencakup 1 screen. Beli slot untuk menambah screen berikutnya.',
                'kind' => FeaturePrice::KIND_ADDON,
                'token_cost' => 25,
                'free_limit' => 1,
                'unit_label' => 'screen',
                'sort_order' => 10,
            ],
            [
                'key' => 'extra_device',
                'label' => 'Slot device tambahan',
                'description' => 'Kuota device player di atas batas paket gratis.',
                'kind' => FeaturePrice::KIND_ADDON,
                'token_cost' => 15,
                'free_limit' => 1,
                'unit_label' => 'device',
                'sort_order' => 20,
            ],
            [
                'key' => 'extra_playlist',
                'label' => 'Slot playlist tambahan',
                'description' => 'Paket gratis mencakup 1 playlist. Beli slot untuk membuat playlist berikutnya.',
                'kind' => FeaturePrice::KIND_ADDON,
                'token_cost' => 20,
                'free_limit' => 1,
                'unit_label' => 'playlist',
                'sort_order' => 30,
            ],
            [
                'key' => 'media_upload',
                'label' => 'Upload media file',
                'description' => 'Biaya token per upload file image, video, atau audio. URL gambar/video tetap gratis.',
                'kind' => FeaturePrice::KIND_USAGE,
                'token_cost' => 5,
                'free_limit' => 0,
                'unit_label' => 'file',
                'sort_order' => 40,
            ],
            [
                'key' => 'playlist_item',
                'label' => 'Item playlist tambahan',
                'description' => '3 item pertama per playlist gratis. Item ke-4 dan seterusnya didebit per item saat playlist disimpan.',
                'kind' => FeaturePrice::KIND_USAGE,
                'token_cost' => 5,
                'free_limit' => 3,
                'unit_label' => 'item',
                'sort_order' => 50,
            ],
        ];

        foreach ($features as $feature) {
            FeaturePrice::query()->updateOrCreate(
                ['key' => $feature['key']],
                [...$feature, 'is_active' => true],
            );
        }
    }
}
