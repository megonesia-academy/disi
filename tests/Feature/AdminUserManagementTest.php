<?php

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createUserManagementTenant(string $name): Tenant
{
    return Tenant::query()->create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.Str::random(4),
        'status' => Tenant::STATUS_ACTIVE,
    ]);
}

function attachTenantMember(Tenant $tenant, User $user, string $role = 'owner'): void
{
    $tenant->users()->attach($user->id, [
        'role' => $role,
        'accepted_at' => now(),
    ]);
}

it('lists tenant members with their platform roles on the tenants page', function (): void {
    $admin = User::factory()->appAdmin()->create(['name' => 'Alice Admin']);
    $member = User::factory()->create(['name' => 'Bob Member']);
    $tenant = createUserManagementTenant('Tenant A');
    attachTenantMember($tenant, $admin);
    attachTenantMember($tenant, $member, 'member');

    $this->actingAs($admin)
        ->get(route('admin.tenants.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Tenants')
            ->has('tenants', 1)
            ->has('tenants.0.users', 2)
            ->where('tenants.0.users_count', 2)
            ->where('tenants.0.owner.email', $admin->email)
            ->where('tenants.0.users.0.email', $admin->email)
            ->where('tenants.0.users.0.app_role', User::APP_ROLE_ADMIN)
            ->where('tenants.0.users.0.role', 'owner')
            ->where('tenants.0.users.0.is_self', true)
            ->where('tenants.0.users.1.email', $member->email)
            ->where('tenants.0.users.1.app_role', User::APP_ROLE_SUBSCRIBER)
            ->where('tenants.0.users.1.role', 'member')
            ->where('tenants.0.users.1.is_self', false)
        );
});

it('promotes a tenant member to application admin', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $subscriber = User::factory()->create();
    $tenant = createUserManagementTenant('Tenant A');
    attachTenantMember($tenant, $admin);
    attachTenantMember($tenant, $subscriber, 'member');

    $this->actingAs($admin)
        ->patch(route('admin.tenants.users.role', [$tenant, $subscriber]), [
            'app_role' => User::APP_ROLE_ADMIN,
        ])
        ->assertRedirect(route('admin.tenants.index'));

    expect($subscriber->refresh()->app_role)->toBe(User::APP_ROLE_ADMIN)
        ->and($subscriber->isAppAdmin())->toBeTrue();
});

it('demotes another application admin to subscriber', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $otherAdmin = User::factory()->appAdmin()->create();
    $tenant = createUserManagementTenant('Tenant A');
    attachTenantMember($tenant, $admin);
    attachTenantMember($tenant, $otherAdmin, 'member');

    $this->actingAs($admin)
        ->patch(route('admin.tenants.users.role', [$tenant, $otherAdmin]), [
            'app_role' => User::APP_ROLE_SUBSCRIBER,
        ])
        ->assertRedirect(route('admin.tenants.index'));

    expect($otherAdmin->refresh()->app_role)->toBe(User::APP_ROLE_SUBSCRIBER);
});

it('prevents admins from changing their own platform role', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $tenant = createUserManagementTenant('Tenant A');
    attachTenantMember($tenant, $admin);

    $this->actingAs($admin)
        ->patch(route('admin.tenants.users.role', [$tenant, $admin]), [
            'app_role' => User::APP_ROLE_SUBSCRIBER,
        ])
        ->assertSessionHasErrors('app_role');

    expect($admin->refresh()->app_role)->toBe(User::APP_ROLE_ADMIN);
});

it('rejects invalid platform roles', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $subscriber = User::factory()->create();
    $tenant = createUserManagementTenant('Tenant A');
    attachTenantMember($tenant, $admin);
    attachTenantMember($tenant, $subscriber, 'member');

    $this->actingAs($admin)
        ->patch(route('admin.tenants.users.role', [$tenant, $subscriber]), [
            'app_role' => 'owner',
        ])
        ->assertSessionHasErrors('app_role');

    expect($subscriber->refresh()->app_role)->toBe(User::APP_ROLE_SUBSCRIBER);
});

it('rejects role changes for users outside the tenant', function (): void {
    $admin = User::factory()->appAdmin()->create();
    $outsider = User::factory()->create();
    $tenant = createUserManagementTenant('Tenant A');
    attachTenantMember($tenant, $admin);

    $this->actingAs($admin)
        ->patch(route('admin.tenants.users.role', [$tenant, $outsider]), [
            'app_role' => User::APP_ROLE_ADMIN,
        ])
        ->assertNotFound();

    expect($outsider->refresh()->app_role)->toBe(User::APP_ROLE_SUBSCRIBER);
});

it('forbids subscribers from the admin area and from managing roles', function (): void {
    $subscriber = User::factory()->create();
    $other = User::factory()->create();
    $tenant = createUserManagementTenant('Tenant A');
    attachTenantMember($tenant, $subscriber);
    attachTenantMember($tenant, $other, 'member');

    $this->actingAs($subscriber)
        ->get(route('admin.tenants.index'))
        ->assertForbidden();

    $this->actingAs($subscriber)
        ->patch(route('admin.tenants.users.role', [$tenant, $other]), [
            'app_role' => User::APP_ROLE_ADMIN,
        ])
        ->assertForbidden();

    expect($other->refresh()->app_role)->toBe(User::APP_ROLE_SUBSCRIBER);
});

it('no longer serves the standalone platform users page', function (): void {
    $admin = User::factory()->appAdmin()->create();

    $this->actingAs($admin)
        ->get('/admin/users')
        ->assertNotFound();
});
