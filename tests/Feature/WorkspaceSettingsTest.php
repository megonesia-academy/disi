<?php

use App\Models\ActivityLog;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createWorkspaceSettingsTenant(string $role = CurrentTenant::ROLE_OWNER): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Workspace Test',
        'slug' => 'workspace-test-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
        'settings' => [
            'brand_color' => '#0891b2',
            'player_accent_label' => 'Workspace Test',
            'logo_width' => 120,
            'logo_position' => 'top-right',
            'logo_offset_x' => 0,
            'logo_offset_y' => 0,
            'player_behavior' => [
                'default_media_fit' => 'cover',
                'manifest_refresh_seconds' => 60,
                'heartbeat_interval_seconds' => 30,
                'show_player_overlay' => true,
            ],
            'billing_metadata' => [
                'company_name' => '',
                'tax_id' => '',
                'billing_address' => '',
                'phone' => '',
            ],
        ],
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => $role,
        'accepted_at' => now(),
    ]);

    return [$tenant, $user];
}

it('renders workspace settings for managers', function (): void {
    [$tenant, $user] = createWorkspaceSettingsTenant();

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->get(route('workspace.settings.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('workspace/Settings')
            ->where('tenant.id', $tenant->id)
            ->where('tenant.settings.brand_color', '#0891b2')
            ->where('tenant.settings.player_behavior.default_media_fit', 'cover'));
});

it('updates workspace identity and branding settings', function (): void {
    [$tenant, $user] = createWorkspaceSettingsTenant();

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->put(route('workspace.settings.update'), [
            'name' => 'Updated Workspace',
            'slug' => 'updated-workspace-'.Str::random(6),
            'billing_email' => 'billing@example.com',
            'brand_color' => '#16a34a',
            'player_accent_label' => 'Updated Player',
            'default_media_fit' => 'contain',
            'manifest_refresh_seconds' => 120,
            'heartbeat_interval_seconds' => 45,
            'show_player_overlay' => true,
            'logo_width' => 180,
            'logo_position' => 'top-left',
            'logo_offset_x' => 24,
            'logo_offset_y' => -12,
            'billing_company_name' => 'Updated Co',
            'billing_tax_id' => 'TAX-123',
            'billing_address' => 'Jl. Testing 123',
            'billing_phone' => '+620000',
        ])
        ->assertRedirect(route('workspace.settings.edit'));

    $tenant->refresh();

    expect($tenant->name)->toBe('Updated Workspace')
        ->and($tenant->billing_email)->toBe('billing@example.com')
        ->and($tenant->settings['brand_color'])->toBe('#16a34a')
        ->and($tenant->settings['player_accent_label'])->toBe('Updated Player')
        ->and($tenant->settings['player_behavior']['default_media_fit'])->toBe('contain')
        ->and($tenant->settings['player_behavior']['manifest_refresh_seconds'])->toBe(120)
        ->and($tenant->settings['logo_width'])->toBe(180)
        ->and($tenant->settings['logo_position'])->toBe('top-left')
        ->and($tenant->settings['logo_offset_x'])->toBe(24)
        ->and($tenant->settings['logo_offset_y'])->toBe(-12)
        ->and($tenant->settings['billing_metadata']['company_name'])->toBe('Updated Co')
        ->and(ActivityLog::query()
            ->where('tenant_id', $tenant->id)
            ->where('event', 'workspace.updated')
            ->exists())->toBeTrue();
});

it('uploads a workspace logo into tenant branding storage', function (): void {
    Storage::fake('public');

    [$tenant, $user] = createWorkspaceSettingsTenant();

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('workspace.settings.upload'), [
            'name' => 'Logo Workspace',
            'slug' => 'logo-workspace-'.Str::random(6),
            'billing_email' => null,
            'brand_color' => '#16a34a',
            'player_accent_label' => 'Logo Player',
            'logo' => UploadedFile::fake()->image('logo.png', 256, 256),
            'default_media_fit' => 'cover',
            'manifest_refresh_seconds' => 60,
            'heartbeat_interval_seconds' => 30,
            'show_player_overlay' => true,
            'logo_width' => 120,
            'logo_position' => 'top-right',
            'logo_offset_x' => 0,
            'logo_offset_y' => 0,
            'billing_company_name' => null,
            'billing_tax_id' => null,
            'billing_address' => null,
            'billing_phone' => null,
        ])
        ->assertRedirect(route('workspace.settings.edit'));

    $tenant->refresh();

    expect($tenant->settings['logo_path'])->toStartWith("tenants/{$tenant->id}/branding/");

    Storage::disk('public')->assertExists($tenant->settings['logo_path']);
});

it('prevents viewers from updating workspace settings', function (): void {
    [$tenant, $user] = createWorkspaceSettingsTenant(CurrentTenant::ROLE_VIEWER);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->put(route('workspace.settings.update'), [
            'name' => 'Blocked Workspace',
            'slug' => $tenant->slug,
            'billing_email' => null,
            'brand_color' => '#16a34a',
            'player_accent_label' => 'Blocked',
            'default_media_fit' => 'cover',
            'manifest_refresh_seconds' => 60,
            'heartbeat_interval_seconds' => 30,
            'show_player_overlay' => true,
            'logo_width' => 120,
            'logo_position' => 'top-right',
            'logo_offset_x' => 0,
            'logo_offset_y' => 0,
            'billing_company_name' => null,
            'billing_tax_id' => null,
            'billing_address' => null,
            'billing_phone' => null,
        ])
        ->assertForbidden();
});

it('requires workspace slug to be unique', function (): void {
    [$tenant, $user] = createWorkspaceSettingsTenant();
    $otherTenant = Tenant::query()->create([
        'name' => 'Other Workspace',
        'slug' => 'other-workspace-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->put(route('workspace.settings.update'), [
            'name' => 'Updated Workspace',
            'slug' => $otherTenant->slug,
            'billing_email' => null,
            'brand_color' => '#16a34a',
            'player_accent_label' => 'Updated',
            'default_media_fit' => 'cover',
            'manifest_refresh_seconds' => 60,
            'heartbeat_interval_seconds' => 30,
            'show_player_overlay' => true,
            'logo_width' => 120,
            'logo_position' => 'top-right',
            'logo_offset_x' => 0,
            'logo_offset_y' => 0,
            'billing_company_name' => null,
            'billing_tax_id' => null,
            'billing_address' => null,
            'billing_phone' => null,
        ])
        ->assertSessionHasErrors('slug');
});
