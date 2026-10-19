<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\Tenant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class SignageDemoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(TenantSeeder::class);

        $tenant = Tenant::query()->where('slug', 'demo')->firstOrFail();

        $screen = Screen::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Lobby Utama',
            ],
            [
                'location' => 'Gedung A - Lantai 1',
                'orientation' => Screen::ORIENTATION_LANDSCAPE,
                'resolution_width' => 1920,
                'resolution_height' => 1080,
                'is_active' => true,
            ],
        );

        $device = Device::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'key' => 'demo-lobby-player',
            ],
            [
                'screen_id' => $screen->id,
                'name' => 'Player Lobby Utama',
                'last_seen_at' => Carbon::now(),
                'app_version' => '0.1.0',
                'status' => Device::STATUS_ONLINE,
                'capabilities' => [
                    'video' => true,
                    'web' => true,
                    'wasm' => true,
                    'resolution' => '1920x1080',
                ],
                'is_active' => true,
            ],
        );

        if (! $device->player_secret) {
            $device->forceFill([
                'player_secret' => Str::random(48),
            ])->save();
        }

        $welcomeImage = MediaAsset::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'path' => 'media/demo/welcome.jpg',
            ],
            [
                'name' => 'Welcome Banner',
                'type' => MediaAsset::TYPE_IMAGE,
                'disk' => 'public',
                'mime_type' => 'image/jpeg',
                'size' => null,
                'duration_seconds' => 10,
                'width' => 1920,
                'height' => 1080,
                'metadata' => [
                    'label' => 'Demo image placeholder',
                ],
            ],
        );

        $promoVideo = MediaAsset::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'path' => 'media/demo/promo.mp4',
            ],
            [
                'name' => 'Promo Video',
                'type' => MediaAsset::TYPE_VIDEO,
                'disk' => 'public',
                'mime_type' => 'video/mp4',
                'size' => null,
                'duration_seconds' => 20,
                'width' => 1920,
                'height' => 1080,
                'metadata' => [
                    'label' => 'Demo video placeholder',
                ],
            ],
        );

        $playlist = Playlist::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Lobby Daily Loop',
            ],
            [
                'description' => 'Default playlist untuk layar lobby.',
                'is_active' => true,
            ],
        );

        PlaylistItem::query()
            ->where('tenant_id', $tenant->id)
            ->where('playlist_id', $playlist->id)
            ->delete();

        PlaylistItem::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'playlist_id' => $playlist->id,
                'media_asset_id' => $welcomeImage->id,
            ],
            [
                'sort_order' => 1,
                'duration_seconds' => 10,
                'transition' => 'fade',
                'config' => [
                    'fit' => 'cover',
                ],
            ],
        );

        PlaylistItem::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'playlist_id' => $playlist->id,
                'media_asset_id' => $promoVideo->id,
            ],
            [
                'sort_order' => 2,
                'duration_seconds' => 20,
                'transition' => 'cut',
                'config' => [
                    'fit' => 'cover',
                    'muted' => true,
                ],
            ],
        );

        Schedule::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'screen_id' => $screen->id,
                'playlist_id' => $playlist->id,
            ],
            [
                'starts_at' => null,
                'ends_at' => null,
                'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'priority' => 10,
                'is_active' => true,
            ],
        );
    }
}
