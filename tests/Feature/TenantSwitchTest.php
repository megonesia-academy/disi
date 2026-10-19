<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Inertia\Testing\AssertableInertia as Assert;

it('allows a user to switch to their own tenant', function (): void {
    $user = User::factory()->create();
    $tenantA = Tenant::query()->create([
        'name' => 'Tenant A',
        'slug' => 'tenant-a',
        'status' => Tenant::STATUS_ACTIVE,
    ]);
    $tenantB = Tenant::query()->create([
        'name' => 'Tenant B',
        'slug' => 'tenant-b',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenantA->users()->attach($user->id, ['role' => 'owner']);
    $tenantB->users()->attach($user->id, ['role' => 'owner']);

    $this->actingAs($user)
        ->post(route('tenants.switch'), ['tenant_id' => $tenantB->id])
        ->assertRedirect();

    $this->assertSame($tenantB->id, session(CurrentTenant::SESSION_KEY));
});

it('prevents switching to another users tenant', function (): void {
    $user = User::factory()->create();
    $otherTenant = Tenant::query()->create([
        'name' => 'Other Tenant',
        'slug' => 'other-tenant',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)
        ->post(route('tenants.switch'), ['tenant_id' => $otherTenant->id])
        ->assertNotFound();
});

it('lets application admins switch into any tenant', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $homeTenant = Tenant::query()->create([
        'name' => 'Admin Workspace',
        'slug' => 'admin-workspace',
        'status' => Tenant::STATUS_ACTIVE,
    ]);
    $targetTenant = Tenant::query()->create([
        'name' => 'Subscriber Workspace',
        'slug' => 'subscriber-workspace',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $homeTenant->users()->attach($admin->id, ['role' => 'owner']);

    $this->actingAs($admin)
        ->post(route('tenants.switch'), ['tenant_id' => $targetTenant->id])
        ->assertRedirect();

    $this->assertSame($targetTenant->id, session(CurrentTenant::SESSION_KEY));

    $this->actingAs($admin)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('tenancy.current.id', $targetTenant->id)
            ->where('tenancy.current.name', 'Subscriber Workspace')
            ->where('tenancy.current.role', 'owner')
            ->has('tenancy.tenants', 2)
        );
});

it('only shows owned tenants in the switcher for subscribers', function (): void {
    $subscriber = User::factory()->create();
    $ownedTenant = Tenant::query()->create([
        'name' => 'Owned Workspace',
        'slug' => 'owned-workspace',
        'status' => Tenant::STATUS_ACTIVE,
    ]);
    $otherTenant = Tenant::query()->create([
        'name' => 'Other Workspace',
        'slug' => 'other-workspace',
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $ownedTenant->users()->attach($subscriber->id, ['role' => 'owner']);

    $this->actingAs($subscriber)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->has('tenancy.tenants', 1)
            ->where('tenancy.tenants.0.id', $ownedTenant->id)
        );
});
