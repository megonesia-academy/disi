<?php

use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;

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
