<?php

namespace Database\Seeders;

use App\Models\Device;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

class TenantSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->call(FeaturePriceSeeder::class);

        foreach ($this->demoTenants() as $definition) {
            $tenant = Tenant::query()->updateOrCreate(
                ['slug' => $definition['slug']],
                [
                    'name' => $definition['name'],
                    'billing_email' => $definition['billing_email'],
                    'status' => $definition['status'],
                    'token_balance' => $definition['token_balance'] ?? 100,
                    'subscription_status' => Tenant::SUBSCRIPTION_ACTIVE,
                    'subscription_renews_at' => Carbon::now()->addMonth(),
                    'settings' => [
                        'timezone' => 'Asia/Jakarta',
                        'default_orientation' => 'landscape',
                    ],
                ],
            );

            $owner = User::query()->firstOrCreate(
                ['email' => $definition['owner_email']],
                [
                    'name' => $definition['owner_name'],
                    'password' => 'password',
                    'app_role' => $definition['owner_role'],
                    'email_verified_at' => Carbon::now(),
                ],
            );

            $owner->forceFill([
                'app_role' => $definition['owner_role'],
                'email_verified_at' => $owner->email_verified_at ?? Carbon::now(),
            ])->save();

            $tenant->users()->syncWithoutDetaching([
                $owner->id => [
                    'role' => 'owner',
                    'accepted_at' => Carbon::now(),
                ],
            ]);

            $this->seedWorkspace($tenant);
        }

        $this->call(PlatformTemplateSeeder::class);
        $this->call(ApiConnectionSeeder::class);
    }

    /**
     * @return array<int, array{slug: string, name: string, billing_email: string, status: string, owner_name: string, owner_email: string, owner_role: string}>
     */
    private function demoTenants(): array
    {
        return [
            [
                'slug' => 'demo',
                'name' => 'Demo Client',
                'billing_email' => 'billing@example.com',
                'status' => Tenant::STATUS_ACTIVE,
                'owner_name' => 'Test Admin',
                'owner_email' => 'test@example.com',
                'owner_role' => User::APP_ROLE_ADMIN,
            ],
            [
                'slug' => 'demo-subscriber',
                'name' => 'Demo Subscriber',
                'billing_email' => 'subscriber@example.com',
                'status' => Tenant::STATUS_ACTIVE,
                'token_balance' => 60,
                'owner_name' => 'Test Subscriber',
                'owner_email' => 'subscriber@example.com',
                'owner_role' => User::APP_ROLE_SUBSCRIBER,
            ],
            [
                'slug' => 'megonesia-mall',
                'name' => 'Megonesia Mall',
                'billing_email' => 'mall@example.com',
                'status' => Tenant::STATUS_ACTIVE,
                'owner_name' => 'Mall Operations',
                'owner_email' => 'mall@example.com',
                'owner_role' => User::APP_ROLE_SUBSCRIBER,
            ],
            [
                'slug' => 'hotel-senja',
                'name' => 'Hotel Senja Nusantara',
                'billing_email' => 'hotel@example.com',
                'status' => Tenant::STATUS_ACTIVE,
                'token_balance' => 40,
                'owner_name' => 'Hotel Senja',
                'owner_email' => 'hotel@example.com',
                'owner_role' => User::APP_ROLE_SUBSCRIBER,
            ],
            [
                'slug' => 'kopi-senja',
                'name' => 'Kopi Senja Cafe',
                'billing_email' => 'cafe@example.com',
                'status' => Tenant::STATUS_SUSPENDED,
                'token_balance' => 0,
                'owner_name' => 'Kopi Senja',
                'owner_email' => 'cafe@example.com',
                'owner_role' => User::APP_ROLE_SUBSCRIBER,
            ],
            [
                'slug' => 'ub-cendekia',
                'name' => 'Universitas Bina Cendekia',
                'billing_email' => 'kampus@example.com',
                'status' => Tenant::STATUS_ACTIVE,
                'owner_name' => 'Kampus Admin',
                'owner_email' => 'kampus@example.com',
                'owner_role' => User::APP_ROLE_SUBSCRIBER,
            ],
            [
                'slug' => 'rs-megonesia',
                'name' => 'RS Megonesia',
                'billing_email' => 'hospital@example.com',
                'status' => Tenant::STATUS_ACTIVE,
                'owner_name' => 'RS Megonesia',
                'owner_email' => 'hospital@example.com',
                'owner_role' => User::APP_ROLE_SUBSCRIBER,
            ],
        ];
    }

    private function seedWorkspace(Tenant $tenant): void
    {
        $screen = Screen::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'name' => 'Lobby Utama',
            ],
            [
                'location' => 'Lantai 1',
                'orientation' => Screen::ORIENTATION_LANDSCAPE,
                'resolution_width' => 1920,
                'resolution_height' => 1080,
                'is_active' => true,
            ],
        );

        $device = Device::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'key' => Str::slug($tenant->slug).'-lobby-player',
            ],
            [
                'screen_id' => $screen->id,
                'name' => 'Player Lobby Utama',
                'last_seen_at' => Carbon::now(),
                'app_version' => '0.1.0',
                'status' => Device::STATUS_ONLINE,
                'is_active' => true,
            ],
        );

        if (! $device->player_secret) {
            $device->forceFill(['player_secret' => Str::random(48)])->save();
        }

        $welcomeImage = MediaAsset::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'path' => "tenants/{$tenant->id}/media/demo-welcome.jpg",
            ],
            [
                'name' => 'Welcome Banner',
                'type' => MediaAsset::TYPE_IMAGE,
                'disk' => 'public',
                'mime_type' => 'image/jpeg',
                'duration_seconds' => 10,
                'width' => 1920,
                'height' => 1080,
                'metadata' => ['source' => 'demo'],
            ],
        );

        $promoVideo = MediaAsset::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'path' => "tenants/{$tenant->id}/media/demo-promo.mp4",
            ],
            [
                'name' => 'Promo Video',
                'type' => MediaAsset::TYPE_VIDEO,
                'disk' => 'public',
                'mime_type' => 'video/mp4',
                'duration_seconds' => 20,
                'width' => 1920,
                'height' => 1080,
                'metadata' => ['source' => 'demo'],
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
                'status' => Playlist::STATUS_PUBLISHED,
            ],
        );

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
                'config' => ['fit' => 'cover'],
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
                'config' => ['fit' => 'cover', 'muted' => true],
            ],
        );

        Schedule::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'screen_id' => $screen->id,
                'playlist_id' => $playlist->id,
            ],
            [
                'days_of_week' => [1, 2, 3, 4, 5, 6, 7],
                'start_time' => '00:00:00',
                'end_time' => '23:59:59',
                'priority' => 10,
                'is_active' => true,
            ],
        );
    }
}
