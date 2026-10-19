<?php

use App\Models\MediaAsset;
use App\Models\Tenant;
use App\Models\TokenTransaction;
use App\Models\User;
use Database\Seeders\FeaturePriceSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createMediaAssetTenant(): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Media Workspace',
        'slug' => 'media-workspace-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

it('offers image url as a media type', function (): void {
    [, $user] = createMediaAssetTenant();

    $this->actingAs($user)
        ->get(route('media-assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->where('types', MediaAsset::TYPES));
});

it('stores an image url media asset from a url', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Poster Promo',
            'type' => MediaAsset::TYPE_IMAGE_URL,
            'url' => 'https://cdn.example.com/poster-promo.jpg',
            'duration_seconds' => 15,
        ])
        ->assertRedirect(route('media-assets.index'));

    $mediaAsset = MediaAsset::query()->where('tenant_id', $tenant->id)->sole();

    expect($mediaAsset->type)->toBe(MediaAsset::TYPE_IMAGE_URL)
        ->and($mediaAsset->path)->toBe('https://cdn.example.com/poster-promo.jpg')
        ->and($mediaAsset->disk)->toBe('public')
        ->and($mediaAsset->duration_seconds)->toBe(15)
        ->and($mediaAsset->metadata)->toBe(['source' => 'url'])
        ->and($mediaAsset->isImage())->toBeTrue()
        ->and($mediaAsset->usesExternalUrl())->toBeTrue();
});

it('requires a url for image url media', function (): void {
    [, $user] = createMediaAssetTenant();

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Poster Promo',
            'type' => MediaAsset::TYPE_IMAGE_URL,
        ])
        ->assertSessionHasErrors('url');

    expect(MediaAsset::query()->count())->toBe(0);
});

it('rejects an invalid url for image url media', function (): void {
    [, $user] = createMediaAssetTenant();

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Poster Promo',
            'type' => MediaAsset::TYPE_IMAGE_URL,
            'url' => 'not-a-url',
        ])
        ->assertSessionHasErrors('url');

    expect(MediaAsset::query()->count())->toBe(0);
});

it('stores a video url media asset from a url', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Promo Video',
            'type' => MediaAsset::TYPE_VIDEO_URL,
            'url' => 'https://cdn.example.com/promo-video.mp4',
            'duration_seconds' => 30,
        ])
        ->assertRedirect(route('media-assets.index'));

    $mediaAsset = MediaAsset::query()->where('tenant_id', $tenant->id)->sole();

    expect($mediaAsset->type)->toBe(MediaAsset::TYPE_VIDEO_URL)
        ->and($mediaAsset->path)->toBe('https://cdn.example.com/promo-video.mp4')
        ->and($mediaAsset->disk)->toBe('public')
        ->and($mediaAsset->duration_seconds)->toBe(30)
        ->and($mediaAsset->metadata)->toBe(['source' => 'url'])
        ->and($mediaAsset->isVideo())->toBeTrue()
        ->and($mediaAsset->isImage())->toBeFalse()
        ->and($mediaAsset->usesExternalUrl())->toBeTrue();
});

it('requires a url for video url media', function (): void {
    [, $user] = createMediaAssetTenant();

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Promo Video',
            'type' => MediaAsset::TYPE_VIDEO_URL,
        ])
        ->assertSessionHasErrors('url');

    expect(MediaAsset::query()->count())->toBe(0);
});

it('updates the url of an external media asset', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $mediaAsset = MediaAsset::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Poster Promo',
        'type' => MediaAsset::TYPE_IMAGE_URL,
        'disk' => 'public',
        'path' => 'https://cdn.example.com/old-poster.jpg',
        'duration_seconds' => 10,
    ]);

    $this->actingAs($user)
        ->put(route('media-assets.update', $mediaAsset), [
            'name' => 'Poster Promo Baru',
            'duration_seconds' => 20,
            'url' => 'https://cdn.example.com/new-poster.jpg',
        ])
        ->assertRedirect(route('media-assets.index'));

    $mediaAsset->refresh();

    expect($mediaAsset->name)->toBe('Poster Promo Baru')
        ->and($mediaAsset->path)->toBe('https://cdn.example.com/new-poster.jpg')
        ->and($mediaAsset->duration_seconds)->toBe(20);
});

it('rejects an invalid url when updating an external media asset', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $mediaAsset = MediaAsset::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Poster Promo',
        'type' => MediaAsset::TYPE_IMAGE_URL,
        'disk' => 'public',
        'path' => 'https://cdn.example.com/old-poster.jpg',
    ]);

    $this->actingAs($user)
        ->put(route('media-assets.update', $mediaAsset), [
            'name' => 'Poster Promo',
            'url' => 'bukan-url',
        ])
        ->assertSessionHasErrors('url');

    expect($mediaAsset->refresh()->path)->toBe('https://cdn.example.com/old-poster.jpg');
});

it('charges tokens for a media file upload', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $tenant->forceFill(['token_balance' => 20])->save();

    $this->seed(FeaturePriceSeeder::class);
    Storage::fake('public');

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Lobby Video',
            'type' => MediaAsset::TYPE_VIDEO,
            'file' => UploadedFile::fake()->create('lobby.mp4', 512, 'video/mp4'),
            'duration_seconds' => 30,
        ])
        ->assertRedirect(route('media-assets.index'))
        ->assertSessionHasNoErrors();

    expect($tenant->refresh()->token_balance)->toBe(15);

    $transaction = TokenTransaction::query()->sole();

    expect($transaction->tokens)->toBe(-5)
        ->and($transaction->balance_after)->toBe(15)
        ->and($transaction->type)->toBe(TokenTransaction::TYPE_PURCHASE)
        ->and($transaction->feature_key)->toBe('media_upload');

    $mediaAsset = MediaAsset::query()->sole();

    expect($mediaAsset->type)->toBe(MediaAsset::TYPE_VIDEO);

    Storage::disk('public')->assertExists($mediaAsset->path);
});

it('rejects a media file upload when tokens are insufficient', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $tenant->forceFill(['token_balance' => 3])->save();

    $this->seed(FeaturePriceSeeder::class);
    Storage::fake('public');

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Lobby Video',
            'type' => MediaAsset::TYPE_VIDEO,
            'file' => UploadedFile::fake()->create('lobby.mp4', 512, 'video/mp4'),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('billing');

    expect($tenant->refresh()->token_balance)->toBe(3)
        ->and(MediaAsset::query()->count())->toBe(0)
        ->and(TokenTransaction::query()->count())->toBe(0);

    expect(Storage::disk('public')->allFiles())->toBe([]);
});

it('does not charge tokens for image url media', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $tenant->forceFill(['token_balance' => 10])->save();

    $this->seed(FeaturePriceSeeder::class);

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Poster Promo',
            'type' => MediaAsset::TYPE_IMAGE_URL,
            'url' => 'https://cdn.example.com/poster-promo.jpg',
        ])
        ->assertRedirect(route('media-assets.index'));

    expect($tenant->refresh()->token_balance)->toBe(10)
        ->and(TokenTransaction::query()->count())->toBe(0)
        ->and(MediaAsset::query()->count())->toBe(1);
});

it('does not charge tokens for web media', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $tenant->forceFill(['token_balance' => 10])->save();

    $this->seed(FeaturePriceSeeder::class);

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Menu Board',
            'type' => MediaAsset::TYPE_WEB,
            'url' => 'https://example.com/menu',
        ])
        ->assertRedirect(route('media-assets.index'));

    expect($tenant->refresh()->token_balance)->toBe(10)
        ->and(TokenTransaction::query()->count())->toBe(0)
        ->and(MediaAsset::query()->sole()->type)->toBe(MediaAsset::TYPE_WEB);
});

it('shares the upload cost and balance with the media index', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $tenant->forceFill(['token_balance' => 42])->save();

    $this->seed(FeaturePriceSeeder::class);

    $this->actingAs($user)
        ->get(route('media-assets.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('uploadCost', 5)
            ->where('balance', 42)
        );
});

it('blocks media file uploads when the subscription is past due', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $tenant->forceFill([
        'token_balance' => 100,
        'subscription_status' => Tenant::SUBSCRIPTION_PAST_DUE,
    ])->save();

    $this->seed(FeaturePriceSeeder::class);
    Storage::fake('public');

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Lobby Video',
            'type' => MediaAsset::TYPE_VIDEO,
            'file' => UploadedFile::fake()->create('lobby.mp4', 512, 'video/mp4'),
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('billing');

    expect($tenant->refresh()->token_balance)->toBe(100)
        ->and(MediaAsset::query()->count())->toBe(0)
        ->and(TokenTransaction::query()->count())->toBe(0)
        ->and(Storage::disk('public')->allFiles())->toBe([]);
});

it('still allows image url media when the subscription is past due', function (): void {
    [$tenant, $user] = createMediaAssetTenant();

    $tenant->forceFill([
        'token_balance' => 10,
        'subscription_status' => Tenant::SUBSCRIPTION_PAST_DUE,
    ])->save();

    $this->seed(FeaturePriceSeeder::class);

    $this->actingAs($user)
        ->post(route('media-assets.store'), [
            'name' => 'Poster Promo',
            'type' => MediaAsset::TYPE_IMAGE_URL,
            'url' => 'https://cdn.example.com/poster-promo.jpg',
        ])
        ->assertRedirect(route('media-assets.index'));

    expect(MediaAsset::query()->count())->toBe(1)
        ->and($tenant->refresh()->token_balance)->toBe(10);
});
