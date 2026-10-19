<?php

use App\Models\Device;
use App\Models\DisplayPairingSession;
use App\Models\FeaturePrice;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TenantBilling;
use App\Support\CurrentTenant;
use Database\Seeders\FeaturePriceSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createDeviceBillingTenant(): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Device Billing',
        'slug' => 'device-billing-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
        'token_balance' => 100,
        'subscription_status' => Tenant::SUBSCRIPTION_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => CurrentTenant::ROLE_OWNER,
        'accepted_at' => now(),
    ]);

    $screen = Screen::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    return [$tenant, $user, $screen];
}

function createExistingDevice(Tenant $tenant, Screen $screen): Device
{
    return Device::query()->create([
        'tenant_id' => $tenant->id,
        'screen_id' => $screen->id,
        'name' => 'Lobby TV',
        'key' => 'lobby-tv-'.Str::lower(Str::random(6)),
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);
}

it('limits free devices to one per tenant', function (): void {
    [$tenant, $user, $screen] = createDeviceBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    createExistingDevice($tenant, $screen);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('devices.store'), [
            'name' => 'Lobby TV 2',
            'status' => Device::STATUS_OFFLINE,
            'is_active' => true,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('billing');

    expect(Device::query()->where('tenant_id', $tenant->id)->count())->toBe(1)
        ->and($tenant->refresh()->token_balance)->toBe(100);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->get(route('devices.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('usage.devices', 1)
            ->where('usage.device_limit', 1)
            ->where('usage.can_add_device', false)
        );
});

it('allows a new device after buying an extra device slot', function (): void {
    [$tenant, $user, $screen] = createDeviceBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    createExistingDevice($tenant, $screen);

    $feature = FeaturePrice::query()->where('key', TenantBilling::FEATURE_EXTRA_DEVICE)->sole();

    app(TenantBilling::class)->purchaseFeature($tenant, $feature, $user);

    expect($tenant->refresh()->token_balance)->toBe(85);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('devices.store'), [
            'name' => 'Lobby TV 2',
            'status' => Device::STATUS_OFFLINE,
            'is_active' => true,
        ])
        ->assertRedirect(route('devices.index'))
        ->assertSessionHasNoErrors();

    expect(Device::query()->where('tenant_id', $tenant->id)->count())->toBe(2)
        ->and($tenant->refresh()->token_balance)->toBe(85);
});

it('blocks pairing a display when the device limit is reached', function (): void {
    [$tenant, $user, $screen] = createDeviceBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    createExistingDevice($tenant, $screen);

    $pairingSession = DisplayPairingSession::query()->create([
        'code' => 'DEV-123',
        'token' => Str::random(64),
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('display.pair.store', ['code' => $pairingSession->code]), [
            'screen_id' => $screen->id,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('billing');

    expect(Device::query()->where('tenant_id', $tenant->id)->count())->toBe(1)
        ->and($pairingSession->refresh()->isPaired())->toBeFalse();
});

it('shares device usage with the pairing page', function (): void {
    [$tenant, $user, $screen] = createDeviceBillingTenant();

    $this->seed(FeaturePriceSeeder::class);

    createExistingDevice($tenant, $screen);

    $pairingSession = DisplayPairingSession::query()->create([
        'code' => 'DEV-456',
        'token' => Str::random(64),
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->get(route('display.pair.show', ['code' => $pairingSession->code]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('display/Pair')
            ->where('usage.devices', 1)
            ->where('usage.can_add_device', false)
        );
});
