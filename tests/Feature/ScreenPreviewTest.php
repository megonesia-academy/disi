<?php

use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createPreviewTenantWithUser(): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Preview Tenant',
        'slug' => 'preview-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => CurrentTenant::ROLE_OWNER,
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

it('renders a screen preview with the active playlist manifest', function (): void {
    [$tenant, $user] = createPreviewTenantWithUser();

    $screen = Screen::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby Screen',
        'location' => 'Lobby',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    $mediaAsset = MediaAsset::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Welcome Web',
        'type' => MediaAsset::TYPE_WEB,
        'disk' => 'public',
        'path' => 'https://example.com',
        'duration_seconds' => 10,
    ]);

    $playlist = Playlist::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby Playlist',
        'is_active' => true,
    ]);

    PlaylistItem::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'media_asset_id' => $mediaAsset->id,
        'sort_order' => 1,
        'duration_seconds' => 10,
    ]);

    Schedule::query()->create([
        'tenant_id' => $tenant->id,
        'screen_id' => $screen->id,
        'playlist_id' => $playlist->id,
        'days_of_week' => [now()->isoWeekday()],
        'start_time' => '00:00',
        'end_time' => '23:59',
        'priority' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->get(route('screens.preview', $screen))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('screens/Preview')
            ->where('screen.name', 'Lobby Screen')
            ->where('manifest.playlist.name', 'Lobby Playlist')
            ->where('manifest.playlist.items.0.media.name', 'Welcome Web')
            ->where('error', null));
});

it('renders a useful preview state when no active schedule exists', function (): void {
    [$tenant, $user] = createPreviewTenantWithUser();

    $screen = Screen::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Waiting Screen',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->get(route('screens.preview', $screen))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('screens/Preview')
            ->where('screen.name', 'Waiting Screen')
            ->where('manifest', null)
            ->where('error', 'Screen belum punya schedule aktif, playlist aktif, atau screen sedang inactive.'));
});
