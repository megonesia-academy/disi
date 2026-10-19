<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createPermissionTenant(string $name, string $slug): Tenant
{
    return Tenant::query()->create([
        'name' => $name,
        'slug' => $slug.'-'.Str::random(4),
        'status' => Tenant::STATUS_ACTIVE,
    ]);
}

it('defaults factory users to the subscriber role', function (): void {
    $subscriber = User::factory()->create();
    $admin = User::factory()->appAdmin()->create();

    expect($subscriber->app_role)->toBe(User::APP_ROLE_SUBSCRIBER)
        ->and($subscriber->isSubscriber())->toBeTrue()
        ->and($subscriber->isAppAdmin())->toBeFalse()
        ->and($admin->app_role)->toBe(User::APP_ROLE_ADMIN)
        ->and($admin->isAppAdmin())->toBeTrue()
        ->and($admin->can('manage-platform'))->toBeTrue()
        ->and($subscriber->can('manage-platform'))->toBeFalse();
});

it('allows application admins to open the admin area', function (): void {
    $admin = User::factory()->appAdmin()->create();
    createPermissionTenant('Tenant A', 'tenant-a');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->has('stats')
            ->has('stats.total_tokens')
            ->where('stats.past_due_tenants', 0)
            ->has('recentTenants')
            ->where('recentTenants.0.token_balance', 100)
            ->where('recentTenants.0.subscription_status', Tenant::SUBSCRIPTION_ACTIVE)
        );
});

it('forbids subscribers from the admin area', function (): void {
    $subscriber = User::factory()->create();

    $this->actingAs($subscriber)
        ->get(route('admin.dashboard'))
        ->assertForbidden();

    $this->actingAs($subscriber)
        ->get(route('admin.tenants.index'))
        ->assertForbidden();
});

it('redirects guests away from the admin area', function (): void {
    $this->get(route('admin.dashboard'))->assertRedirect(route('login'));
});

it('shares the platform permission flag with the frontend', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $subscriber = User::factory()->create();
    createPermissionTenant('Tenant A', 'tenant-a');

    $this->actingAs($admin)
        ->get(route('admin.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can_manage_platform', true)
        );

    $this->actingAs($subscriber)
        ->get(route('dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('auth.can_manage_platform', false)
        );
});

it('lets admins see every tenant on the platform', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $tenantA = createPermissionTenant('Tenant A', 'tenant-a');
    $tenantA->users()->attach($admin->id, [
        'role' => 'owner',
        'accepted_at' => now(),
    ]);
    createPermissionTenant('Tenant B', 'tenant-b');

    $this->actingAs($admin)
        ->get(route('admin.tenants.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Tenants')
            ->has('tenants', 2)
            ->where('tenants.0.token_balance', 100)
            ->where('tenants.0.subscription_active', true)
        );
});

it('lets admins suspend and reactivate a tenant', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $tenant = createPermissionTenant('Tenant A', 'tenant-a');

    $this->actingAs($admin)
        ->patch(route('admin.tenants.status', $tenant), [
            'status' => Tenant::STATUS_SUSPENDED,
        ])
        ->assertRedirect(route('admin.tenants.index'));

    expect($tenant->refresh()->status)->toBe(Tenant::STATUS_SUSPENDED);

    $this->actingAs($admin)
        ->patch(route('admin.tenants.status', $tenant), [
            'status' => Tenant::STATUS_ACTIVE,
        ])
        ->assertRedirect(route('admin.tenants.index'));

    expect($tenant->refresh()->status)->toBe(Tenant::STATUS_ACTIVE);
});

it('prevents subscribers from changing tenant status', function (): void {
    $subscriber = User::factory()->create();
    $tenant = createPermissionTenant('Tenant A', 'tenant-a');

    $this->actingAs($subscriber)
        ->patch(route('admin.tenants.status', $tenant), [
            'status' => Tenant::STATUS_SUSPENDED,
        ])
        ->assertForbidden();

    expect($tenant->refresh()->status)->toBe(Tenant::STATUS_ACTIVE);
});
