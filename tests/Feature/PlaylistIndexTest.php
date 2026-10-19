<?php

use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\PlaylistItem;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createPlaylistIndexTenant(): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Playlist Workspace',
        'slug' => 'playlist-workspace-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

it('lists playlists with item and schedule summaries', function (): void {
    [$tenant, $user] = createPlaylistIndexTenant();

    $media = MediaAsset::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby Photo',
        'type' => MediaAsset::TYPE_IMAGE,
        'disk' => 'public',
        'path' => "tenants/{$tenant->id}/media/lobby.jpg",
    ]);

    $playlist = Playlist::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby Loop',
        'description' => 'Loop pagi untuk lobby',
        'is_active' => true,
        'status' => Playlist::STATUS_PUBLISHED,
    ]);

    PlaylistItem::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'media_asset_id' => $media->id,
        'template' => 'media',
        'sort_order' => 1,
        'duration_seconds' => 10,
        'config' => ['fit' => 'cover'],
    ]);

    PlaylistItem::query()->create([
        'tenant_id' => $tenant->id,
        'playlist_id' => $playlist->id,
        'media_asset_id' => $media->id,
        'template' => 'media',
        'sort_order' => 2,
        'duration_seconds' => 15,
        'config' => ['fit' => 'contain'],
    ]);

    $screen = Screen::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby Screen',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    Schedule::query()->create([
        'tenant_id' => $tenant->id,
        'screen_id' => $screen->id,
        'playlist_id' => $playlist->id,
        'priority' => 10,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('playlists.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('playlists/Index')
            ->has('playlists', 1)
            ->where('playlists.0.name', 'Lobby Loop')
            ->where('playlists.0.status', 'published')
            ->where('playlists.0.schedules_count', 1)
            ->where('playlists.0.items.0.duration_seconds', 10)
            ->where('playlists.0.items.1.duration_seconds', 15)
            ->where('playlists.0.items.0.media.name', 'Lobby Photo')
            ->has('playlists.0.updated_at')
            ->missing('mediaAssets')
        );
});
