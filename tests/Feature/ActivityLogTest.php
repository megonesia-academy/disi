<?php

use App\Models\ActivityLog;
use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createActivityTenant(string $slug): array
{
    $tenant = Tenant::query()->create([
        'name' => Str::title($slug),
        'slug' => $slug.'-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => CurrentTenant::ROLE_OWNER,
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

it('records an activity when a device command is queued', function (): void {
    [$tenant, $user] = createActivityTenant('activity');

    $device = Device::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby Player',
        'key' => 'lobby-player-'.Str::random(6),
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('devices.commands.store', $device), [
            'type' => DeviceCommand::TYPE_IDENTIFY,
        ])
        ->assertRedirect(route('devices.show', $device));

    expect(ActivityLog::query()
        ->where('tenant_id', $tenant->id)
        ->where('user_id', $user->id)
        ->where('event', 'device.command_queued')
        ->where('subject_id', $device->id)
        ->exists())->toBeTrue();
});

it('records activity when a screen is created updated and deleted', function (): void {
    [$tenant, $user] = createActivityTenant('screen-activity');

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('screens.store'), [
            'name' => 'Audit Screen',
            'location' => 'Lobby',
            'orientation' => Screen::ORIENTATION_LANDSCAPE,
            'resolution_width' => 1920,
            'resolution_height' => 1080,
            'is_active' => true,
        ])
        ->assertRedirect(route('screens.index'));

    $screen = Screen::query()
        ->where('tenant_id', $tenant->id)
        ->where('name', 'Audit Screen')
        ->firstOrFail();

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->put(route('screens.update', $screen), [
            'name' => 'Audit Screen Updated',
            'location' => 'Front Desk',
            'orientation' => Screen::ORIENTATION_LANDSCAPE,
            'resolution_width' => 1920,
            'resolution_height' => 1080,
            'is_active' => true,
        ])
        ->assertRedirect(route('screens.index'));

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->delete(route('screens.destroy', $screen))
        ->assertRedirect(route('screens.index'));

    expect(ActivityLog::query()
        ->where('tenant_id', $tenant->id)
        ->where('event', 'screen.created')
        ->exists())->toBeTrue()
        ->and(ActivityLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('event', 'screen.updated')
            ->exists())->toBeTrue()
        ->and(ActivityLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('event', 'screen.deleted')
            ->exists())->toBeTrue();
});

it('lets platform admins see activity from every tenant', function (): void {
    [$tenantA, $userA] = createActivityTenant('tenant-a-activity');
    [$tenantB] = createActivityTenant('tenant-b-activity');

    ActivityLog::query()->create([
        'tenant_id' => $tenantA->id,
        'user_id' => $userA->id,
        'event' => 'device.command_queued',
        'properties' => ['device_name' => 'Tenant A Player'],
    ]);

    ActivityLog::query()->create([
        'tenant_id' => $tenantB->id,
        'event' => 'display.paired',
        'properties' => ['device_name' => 'Tenant B Player'],
    ]);

    $admin = User::factory()->appAdmin()->create();

    $this->actingAs($admin)
        ->get(route('admin.activity.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Activity')
            ->has('activities', 2)
            ->where('tenants', fn ($tenants): bool => $tenants
                ->pluck('name')
                ->contains($tenantA->name)
                && $tenants->pluck('name')->contains($tenantB->name))
            ->has('events', 2)
        );
});

it('forbids subscribers from the platform activity log', function (): void {
    [, $user] = createActivityTenant('activity-subscriber');

    $this->actingAs($user)
        ->get(route('admin.activity.index'))
        ->assertForbidden();
});
