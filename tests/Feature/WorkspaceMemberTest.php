<?php

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createWorkspaceMembersTenant(string $managerRole = CurrentTenant::ROLE_OWNER): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Members Workspace',
        'slug' => 'members-workspace-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $manager = User::factory()->create();
    $member = User::factory()->create();

    $tenant->users()->attach($manager->id, [
        'role' => $managerRole,
        'accepted_at' => now(),
    ]);
    $tenant->users()->attach($member->id, [
        'role' => CurrentTenant::ROLE_VIEWER,
        'accepted_at' => now(),
    ]);

    return [$tenant, $manager, $member];
}

it('renders workspace members for managers', function (): void {
    [$tenant, $manager, $member] = createWorkspaceMembersTenant();

    $this->actingAs($manager)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->get(route('workspace.members.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspace/Members')
            ->where('tenant.id', $tenant->id)
            ->has('members', 2)
            ->where('members', fn ($members): bool => collect($members)
                ->pluck('email')
                ->sort()
                ->values()
                ->all() === collect([$manager->email, $member->email])->sort()->values()->all()));
});

it('updates a member role and records activity', function (): void {
    [$tenant, $manager, $member] = createWorkspaceMembersTenant();

    $this->actingAs($manager)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->put(route('workspace.members.update', $member), [
            'role' => CurrentTenant::ROLE_ADMIN,
        ])
        ->assertRedirect(route('workspace.members.index'));

    expect($tenant->users()->whereKey($member->id)->firstOrFail()->pivot->role)
        ->toBe(CurrentTenant::ROLE_ADMIN)
        ->and(ActivityLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('event', 'member.role_updated')
            ->where('properties->member_email', $member->email)
            ->exists())->toBeTrue();
});

it('removes a member and records activity', function (): void {
    [$tenant, $manager, $member] = createWorkspaceMembersTenant();

    $this->actingAs($manager)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->delete(route('workspace.members.destroy', $member))
        ->assertRedirect(route('workspace.members.index'));

    expect($tenant->users()->whereKey($member->id)->exists())->toBeFalse()
        ->and(ActivityLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('event', 'member.removed')
            ->where('properties->member_email', $member->email)
            ->exists())->toBeTrue();
});

it('prevents a manager from removing themselves', function (): void {
    [$tenant, $manager] = createWorkspaceMembersTenant();

    $this->actingAs($manager)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->delete(route('workspace.members.destroy', $manager))
        ->assertStatus(422);
});

it('prevents viewers from managing members', function (): void {
    [$tenant, , $viewer] = createWorkspaceMembersTenant();

    $this->actingAs($viewer)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->get(route('workspace.members.index'))
        ->assertForbidden();
});
