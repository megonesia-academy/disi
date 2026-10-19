<?php

use App\Models\FeaturePrice;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\TenantEntitlement;
use App\Models\TokenTopupRequest;
use App\Models\TokenTransaction;
use App\Models\User;
use Database\Seeders\FeaturePriceSeeder;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createBillingTenant(int $balance = 100, string $subscriptionStatus = Tenant::SUBSCRIPTION_ACTIVE): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Billing Workspace',
        'slug' => 'billing-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
        'token_balance' => $balance,
        'subscription_status' => $subscriptionStatus,
        'subscription_renews_at' => now()->addMonth(),
    ]);

    $owner = User::factory()->create();

    $tenant->users()->attach($owner->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);

    return [$tenant, $owner];
}

function seedBillingPrices(): void
{
    test()->seed(FeaturePriceSeeder::class);
}

it('lets tenants purchase a feature with tokens', function (): void {
    seedBillingPrices();
    [$tenant, $owner] = createBillingTenant(100);

    $this->actingAs($owner)
        ->post(route('workspace.billing.purchase'), [
            'feature_key' => 'extra_screen',
        ])
        ->assertRedirect();

    expect($tenant->refresh()->token_balance)->toBe(75)
        ->and(TenantEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->where('feature_key', 'extra_screen')
            ->value('quantity'))->toBe(1)
        ->and(TokenTransaction::query()
            ->where('tenant_id', $tenant->id)
            ->where('type', TokenTransaction::TYPE_PURCHASE)
            ->value('tokens'))->toBe(-25);
});

it('rejects a purchase when the token balance is insufficient', function (): void {
    seedBillingPrices();
    [$tenant, $owner] = createBillingTenant(10);

    $this->actingAs($owner)
        ->post(route('workspace.billing.purchase'), [
            'feature_key' => 'extra_screen',
        ])
        ->assertSessionHasErrors('billing');

    expect($tenant->refresh()->token_balance)->toBe(10)
        ->and(TenantEntitlement::query()->where('tenant_id', $tenant->id)->count())->toBe(0);
});

it('limits free tenants to one screen until a slot is purchased', function (): void {
    seedBillingPrices();
    [$tenant, $owner] = createBillingTenant(100);

    Screen::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'First Screen',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    $payload = [
        'name' => 'Second Screen',
        'location' => null,
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ];

    $this->actingAs($owner)
        ->post(route('screens.store'), $payload)
        ->assertSessionHasErrors('billing');

    expect($tenant->screens()->count())->toBe(1);

    $this->actingAs($owner)->post(route('workspace.billing.purchase'), [
        'feature_key' => 'extra_screen',
    ]);

    $this->actingAs($owner)
        ->post(route('screens.store'), $payload)
        ->assertRedirect(route('screens.index'));

    expect($tenant->screens()->count())->toBe(2);
});

it('lets tenants request a topup and admins approve it', function (): void {
    [$tenant, $owner] = createBillingTenant(20);
    $admin = User::factory()->appAdmin()->create();

    $this->actingAs($owner)
        ->post(route('workspace.billing.topup'), [
            'tokens' => 50,
            'note' => 'Transfer bank',
        ])
        ->assertRedirect();

    $topup = TokenTopupRequest::query()->where('tenant_id', $tenant->id)->sole();

    expect($topup->status)->toBe(TokenTopupRequest::STATUS_PENDING);

    $this->actingAs($admin)
        ->patch(route('admin.topups.approve', $topup))
        ->assertRedirect();

    expect($tenant->refresh()->token_balance)->toBe(70)
        ->and($topup->refresh()->status)->toBe(TokenTopupRequest::STATUS_APPROVED)
        ->and($topup->approved_by)->toBe($admin->id);
});

it('lets admins update feature pricing', function (): void {
    seedBillingPrices();
    $admin = User::factory()->appAdmin()->create();
    $feature = FeaturePrice::query()->where('key', 'extra_screen')->firstOrFail();

    $this->actingAs($admin)
        ->put(route('admin.pricing.update', $feature), [
            'label' => 'Extra screen slot',
            'description' => 'Slot tambahan',
            'token_cost' => 40,
            'free_limit' => 2,
            'unit_label' => 'screen',
            'is_active' => true,
        ])
        ->assertRedirect();

    $feature->refresh();

    expect($feature->token_cost)->toBe(40)
        ->and($feature->free_limit)->toBe(2)
        ->and($feature->label)->toBe('Extra screen slot');
});

it('debits the monthly subscription and marks past due when balance is low', function (): void {
    seedBillingPrices();

    [$richTenant] = createBillingTenant(150);
    [$poorTenant] = createBillingTenant(50);

    Tenant::query()->whereIn('id', [$richTenant->id, $poorTenant->id])->update([
        'subscription_renews_at' => now()->subDay(),
    ]);

    $this->artisan('tokens:charge-subscriptions')->assertSuccessful();

    $richTenant->refresh();
    $poorTenant->refresh();

    expect($richTenant->token_balance)->toBe(50)
        ->and($richTenant->subscription_status)->toBe(Tenant::SUBSCRIPTION_ACTIVE)
        ->and($poorTenant->token_balance)->toBe(50)
        ->and($poorTenant->subscription_status)->toBe(Tenant::SUBSCRIPTION_PAST_DUE)
        ->and(TokenTransaction::query()
            ->where('tenant_id', $richTenant->id)
            ->where('type', TokenTransaction::TYPE_SUBSCRIPTION)
            ->exists())->toBeTrue();
});

it('shares the tenant token balance with the app header props', function (): void {
    seedBillingPrices();
    [$tenant, $owner] = createBillingTenant(42);

    $this->actingAs($owner)
        ->get(route('playlists.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('tenancy.current.id', $tenant->id)
            ->where('tenancy.current.token_balance', 42)
            ->where('tenancy.current.subscription_status', Tenant::SUBSCRIPTION_ACTIVE)
            ->where('tenancy.current.monthly_subscription_cost', 100)
            ->where('tenancy.tenants.0.id', $tenant->id)
            ->where('tenancy.tenants.0.token_balance', 42)
        );
});

it('lets admins grant tokens to a tenant', function (): void {
    [$tenant] = createBillingTenant(100);
    $admin = User::factory()->appAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.tenants.tokens', $tenant), [
            'tokens' => 25,
            'description' => 'Bonus onboarding',
        ])
        ->assertRedirect(route('admin.tenants.index'));

    $transaction = TokenTransaction::query()->where('tenant_id', $tenant->id)->sole();

    expect($tenant->refresh()->token_balance)->toBe(125)
        ->and($transaction->type)->toBe(TokenTransaction::TYPE_ADJUSTMENT)
        ->and($transaction->tokens)->toBe(25)
        ->and($transaction->balance_after)->toBe(125)
        ->and($transaction->description)->toBe('Bonus onboarding')
        ->and($transaction->created_by)->toBe($admin->id);
});

it('rejects an admin token debit that exceeds the balance', function (): void {
    [$tenant] = createBillingTenant(10);
    $admin = User::factory()->appAdmin()->create();

    $this->actingAs($admin)
        ->post(route('admin.tenants.tokens', $tenant), [
            'tokens' => -50,
        ])
        ->assertRedirect()
        ->assertSessionHasErrors('tokens');

    expect($tenant->refresh()->token_balance)->toBe(10)
        ->and(TokenTransaction::query()->count())->toBe(0);
});

it('forbids subscribers from adjusting tenant tokens', function (): void {
    [$tenant, $owner] = createBillingTenant(10);

    $this->actingAs($owner)
        ->post(route('admin.tenants.tokens', $tenant), [
            'tokens' => 100,
        ])
        ->assertForbidden();

    expect($tenant->refresh()->token_balance)->toBe(10);
});
