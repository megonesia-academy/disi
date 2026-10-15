<?php

use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;

function createUserForTenantRole(string $role): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Permission Tenant '.$role,
        'slug' => 'permission-tenant-'.$role,
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => $role,
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

it('allows viewers to view tenant screens', function (): void {
    [$tenant, $user] = createUserForTenantRole('viewer');

    Screen::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Viewer Screen',
        'orientation' => Screen::ORIENTATION_LANDSCAPE,
        'resolution_width' => 1920,
        'resolution_height' => 1080,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->get(route('screens.index'))
        ->assertOk()
        ->assertSee('Viewer Screen');
});

it('prevents viewers from creating tenant resources', function (): void {
    [, $user] = createUserForTenantRole('viewer');

    $this->actingAs($user)
        ->post(route('screens.store'), [
            'name' => 'Blocked Screen',
            'location' => null,
            'orientation' => Screen::ORIENTATION_LANDSCAPE,
            'resolution_width' => 1920,
            'resolution_height' => 1080,
            'is_active' => true,
        ])
        ->assertForbidden();
});

it('allows admins to create tenant resources', function (): void {
    [$tenant, $user] = createUserForTenantRole('admin');

    $this->actingAs($user)
        ->post(route('screens.store'), [
            'name' => 'Admin Screen',
            'location' => null,
            'orientation' => Screen::ORIENTATION_LANDSCAPE,
            'resolution_width' => 1920,
            'resolution_height' => 1080,
            'is_active' => true,
        ])
        ->assertRedirect(route('screens.index'));

    expect(Screen::query()
        ->where('tenant_id', $tenant->id)
        ->where('name', 'Admin Screen')
        ->exists())->toBeTrue();
});

it('allows owners to create tenant resources', function (): void {
    [$tenant, $user] = createUserForTenantRole('owner');

    $this->actingAs($user)
        ->post(route('screens.store'), [
            'name' => 'Owner Screen',
            'location' => null,
            'orientation' => Screen::ORIENTATION_LANDSCAPE,
            'resolution_width' => 1920,
            'resolution_height' => 1080,
            'is_active' => true,
        ])
        ->assertRedirect(route('screens.index'));

    expect(Screen::query()
        ->where('tenant_id', $tenant->id)
        ->where('name', 'Owner Screen')
        ->exists())->toBeTrue();
});
