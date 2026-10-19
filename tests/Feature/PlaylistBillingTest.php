<?php

use App\Models\FeaturePrice;
use App\Models\Playlist;
use App\Models\Tenant;
use App\Models\TokenTransaction;
use App\Models\User;
use App\Services\TenantBilling;
use Database\Seeders\FeaturePriceSeeder;
use Illuminate\Support\Str;

function createPlaylistBillingTenant(int $balance = 100): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Playlist Billing',
        'slug' => 'playlist-billing-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
        'token_balance' => $balance,
        'subscription_status' => Tenant::SUBSCRIPTION_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

/**
 * @return array<string, mixed>
 */
function playlistBillingItem(string $title = 'Announcement'): array
{
    return [
        'media_asset_id' => null,
        'template' => 'announcement',
        'content' => ['title' => $title],
        'duration_seconds' => 10,
    ];
}

/**
 * @return array<string, mixed>
 */
function playlistBillingPayload(string $name, int $itemCount): array
{
    $items = [];

    for ($index = 1; $index <= $itemCount; $index++) {
        $items[] = playlistBillingItem("Item {$index}");
    }

    return [
        'name' => $name,
        'description' => null,
        'is_active' => true,
        'status' => Playlist::STATUS_DRAFT,
        'items' => $items,
        'overlays' => [],
    ];
}

it('limits free playlists to one per tenant', function (): void {
    [$tenant, $user] = createPlaylistBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    Playlist::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Existing Loop',
        'is_active' => true,
        'status' => Playlist::STATUS_DRAFT,
    ]);

    $this->actingAs($user)
        ->post(route('playlists.store'), playlistBillingPayload('Second Loop', 0))
        ->assertRedirect()
        ->assertSessionHasErrors('billing');

    expect(Playlist::query()->where('tenant_id', $tenant->id)->count())->toBe(1)
        ->and($tenant->refresh()->token_balance)->toBe(100);
});

it('allows a new playlist after buying an extra playlist slot', function (): void {
    [$tenant, $user] = createPlaylistBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    Playlist::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Existing Loop',
        'is_active' => true,
        'status' => Playlist::STATUS_DRAFT,
    ]);

    $feature = FeaturePrice::query()->where('key', TenantBilling::FEATURE_EXTRA_PLAYLIST)->sole();

    app(TenantBilling::class)->purchaseFeature($tenant, $feature, $user);

    expect($tenant->refresh()->token_balance)->toBe(80);

    $this->actingAs($user)
        ->post(route('playlists.store'), playlistBillingPayload('Second Loop', 0))
        ->assertRedirect(route('playlists.index'))
        ->assertSessionHasNoErrors();

    expect(Playlist::query()->where('tenant_id', $tenant->id)->count())->toBe(2);
});

it('charges tokens for playlist items beyond the free limit', function (): void {
    [$tenant, $user] = createPlaylistBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    $this->actingAs($user)
        ->post(route('playlists.store'), playlistBillingPayload('Lobby Loop', 4))
        ->assertRedirect(route('playlists.index'))
        ->assertSessionHasNoErrors();

    expect($tenant->refresh()->token_balance)->toBe(95);

    $transaction = TokenTransaction::query()->sole();

    expect($transaction->tokens)->toBe(-5)
        ->and($transaction->balance_after)->toBe(95)
        ->and($transaction->feature_key)->toBe(TenantBilling::FEATURE_PLAYLIST_ITEM);

    $playlist = Playlist::query()->where('tenant_id', $tenant->id)->sole();

    expect($playlist->items()->count())->toBe(4);
});

it('rejects a playlist when tokens are insufficient for the extra items', function (): void {
    [$tenant, $user] = createPlaylistBillingTenant(2);

    $this->seed(FeaturePriceSeeder::class);

    $this->actingAs($user)
        ->post(route('playlists.store'), playlistBillingPayload('Lobby Loop', 4))
        ->assertRedirect()
        ->assertSessionHasErrors('billing');

    expect(Playlist::query()->where('tenant_id', $tenant->id)->count())->toBe(0)
        ->and($tenant->refresh()->token_balance)->toBe(2)
        ->and(TokenTransaction::query()->count())->toBe(0);
});

it('only charges for newly added items on update', function (): void {
    [$tenant, $user] = createPlaylistBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    $this->actingAs($user)
        ->post(route('playlists.store'), playlistBillingPayload('Lobby Loop', 4))
        ->assertRedirect(route('playlists.index'));

    $playlist = Playlist::query()->where('tenant_id', $tenant->id)->sole();

    expect($tenant->refresh()->token_balance)->toBe(95);

    $this->actingAs($user)
        ->put(route('playlists.update', $playlist), playlistBillingPayload('Lobby Loop', 5))
        ->assertRedirect(route('playlists.edit', $playlist));

    expect($tenant->refresh()->token_balance)->toBe(90)
        ->and(TokenTransaction::query()->count())->toBe(2);

    $this->actingAs($user)
        ->put(route('playlists.update', $playlist), playlistBillingPayload('Lobby Loop', 5))
        ->assertRedirect(route('playlists.edit', $playlist));

    expect($tenant->refresh()->token_balance)->toBe(90)
        ->and(TokenTransaction::query()->count())->toBe(2)
        ->and($playlist->items()->count())->toBe(5);
});

it('blocks duplicating a playlist when the playlist limit is reached', function (): void {
    [$tenant, $user] = createPlaylistBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    $playlist = Playlist::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Existing Loop',
        'is_active' => true,
        'status' => Playlist::STATUS_DRAFT,
    ]);

    $this->actingAs($user)
        ->post(route('playlists.duplicate', $playlist))
        ->assertRedirect()
        ->assertSessionHasErrors('billing');

    expect(Playlist::query()->where('tenant_id', $tenant->id)->count())->toBe(1);
});

it('blocks paid playlist items when the subscription is past due', function (): void {
    [$tenant, $user] = createPlaylistBillingTenant();

    $tenant->forceFill([
        'subscription_status' => Tenant::SUBSCRIPTION_PAST_DUE,
    ])->save();

    $this->seed(FeaturePriceSeeder::class);

    $this->actingAs($user)
        ->post(route('playlists.store'), playlistBillingPayload('Lobby Loop', 4))
        ->assertRedirect()
        ->assertSessionHasErrors('billing');

    expect(Playlist::query()->where('tenant_id', $tenant->id)->count())->toBe(0)
        ->and($tenant->refresh()->token_balance)->toBe(100)
        ->and(TokenTransaction::query()->count())->toBe(0);
});

it('still allows a playlist with free items when the subscription is past due', function (): void {
    [$tenant, $user] = createPlaylistBillingTenant();

    $tenant->forceFill([
        'subscription_status' => Tenant::SUBSCRIPTION_PAST_DUE,
    ])->save();

    $this->seed(FeaturePriceSeeder::class);

    $this->actingAs($user)
        ->post(route('playlists.store'), playlistBillingPayload('Lobby Loop', 3))
        ->assertRedirect(route('playlists.index'))
        ->assertSessionHasNoErrors();

    expect(Playlist::query()->where('tenant_id', $tenant->id)->sole()->items()->count())->toBe(3)
        ->and(TokenTransaction::query()->count())->toBe(0);
});

it('does not charge item tokens when duplicating a playlist', function (): void {
    [$tenant, $user] = createPlaylistBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    $playlist = Playlist::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby Loop',
        'is_active' => true,
        'status' => Playlist::STATUS_DRAFT,
    ]);

    for ($index = 1; $index <= 4; $index++) {
        $playlist->items()->create([
            'tenant_id' => $tenant->id,
            'template' => 'announcement',
            'content' => ['title' => "Item {$index}"],
            'sort_order' => $index,
            'duration_seconds' => 10,
        ]);
    }

    $feature = FeaturePrice::query()->where('key', TenantBilling::FEATURE_EXTRA_PLAYLIST)->sole();
    app(TenantBilling::class)->purchaseFeature($tenant, $feature, $user);

    expect($tenant->refresh()->token_balance)->toBe(80);

    $this->actingAs($user)
        ->post(route('playlists.duplicate', $playlist))
        ->assertRedirect(route('playlists.index'))
        ->assertSessionHasNoErrors();

    $copy = Playlist::query()
        ->where('tenant_id', $tenant->id)
        ->where('name', 'Lobby Loop Copy')
        ->sole();

    expect($copy->items()->count())->toBe(4)
        ->and($tenant->refresh()->token_balance)->toBe(80)
        ->and(TokenTransaction::query()
            ->where('tenant_id', $tenant->id)
            ->where('feature_key', TenantBilling::FEATURE_PLAYLIST_ITEM)
            ->count())->toBe(0);
});
