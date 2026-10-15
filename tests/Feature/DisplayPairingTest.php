<?php

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\DisplayPairingSession;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function createPairingTenant(string $role = CurrentTenant::ROLE_OWNER): array
{
    $user = User::factory()->create();
    $tenant = Tenant::query()->create([
        'name' => 'Acme',
        'slug' => 'acme-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $tenant->users()->attach($user->id, [
        'role' => $role,
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

    return [$user, $tenant, $screen];
}

it('shows a display pairing code and qr payload', function (): void {
    $this->get(route('display.show'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('display/Show')
            ->has('code')
            ->has('token')
            ->has('statusUrl')
            ->has('resumeUrl')
            ->has('pairingUrl')
            ->has('qrSvg')
            ->has('expiresAt'));

    expect(DisplayPairingSession::query()->count())->toBe(1);
});

it('pairs a display session to a new device', function (): void {
    [$user, $tenant, $screen] = createPairingTenant();

    $pairingSession = DisplayPairingSession::query()->create([
        'code' => 'ABC-123',
        'token' => Str::random(64),
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('display.pair.store', ['code' => $pairingSession->code]), [
            'screen_id' => $screen->id,
            'name' => 'Lobby TV',
        ])
        ->assertRedirect(route('devices.index'));

    $pairingSession->refresh();

    expect($pairingSession->isPaired())->toBeTrue()
        ->and(Device::query()->where('name', 'Lobby TV')->where('screen_id', $screen->id)->exists())->toBeTrue();
});

it('returns player url after the display is paired', function (): void {
    [$user, $tenant, $screen] = createPairingTenant();

    $pairingSession = DisplayPairingSession::query()->create([
        'code' => 'DEF-456',
        'token' => Str::random(64),
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('display.pair.store', ['code' => $pairingSession->code]), [
            'screen_id' => $screen->id,
        ])
        ->assertRedirect(route('devices.index'));

    $this->getJson(route('api.display.status', ['token' => $pairingSession->token]))
        ->assertOk()
        ->assertJsonPath('status', 'paired')
        ->assertJsonStructure([
            'player_url',
            'connection' => ['device_key', 'token'],
        ]);
});

it('resumes a paired display with its saved browser connection', function (): void {
    [$user, $tenant, $screen] = createPairingTenant();

    $device = Device::query()->create([
        'tenant_id' => $tenant->id,
        'screen_id' => $screen->id,
        'name' => 'Lobby TV',
        'key' => 'lobby-tv',
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);

    $this->postJson(route('api.display.resume'), [
        'device_key' => $device->key,
        'token' => $device->player_secret,
    ])
        ->assertOk()
        ->assertJsonPath('status', 'paired')
        ->assertJsonPath('player_url', route('player.show', [
            'device' => $device->key,
            'token' => $device->player_secret,
        ]));
});

it('rejects an invalid saved display connection', function (): void {
    $this->postJson(route('api.display.resume'), [
        'device_key' => 'unknown-display',
        'token' => Str::random(48),
    ])
        ->assertNotFound()
        ->assertJsonPath('status', 'invalid');
});

it('deletes a device by its route key with related pairing data', function (): void {
    [$user, $tenant, $screen] = createPairingTenant();

    $device = Device::query()->create([
        'tenant_id' => $tenant->id,
        'screen_id' => $screen->id,
        'name' => 'Lobby TV',
        'key' => 'lobby-tv-delete',
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);

    $pairingSession = DisplayPairingSession::query()->create([
        'code' => 'DEL-123',
        'token' => Str::random(64),
        'device_id' => $device->id,
        'expires_at' => now()->addMinutes(10),
        'paired_at' => now(),
    ]);

    DeviceCommand::query()->create([
        'tenant_id' => $tenant->id,
        'device_id' => $device->id,
        'type' => DeviceCommand::TYPE_RELOAD,
        'status' => DeviceCommand::STATUS_QUEUED,
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->delete(route('devices.destroy', $device))
        ->assertRedirect(route('devices.index'));

    expect(Device::query()->whereKey($device->id)->exists())->toBeFalse()
        ->and(DeviceCommand::query()->where('device_id', $device->id)->exists())->toBeFalse()
        ->and($pairingSession->fresh()?->device_id)->toBeNull();
});

it('prevents viewers from pairing displays', function (): void {
    [$user, $tenant, $screen] = createPairingTenant(CurrentTenant::ROLE_VIEWER);

    $pairingSession = DisplayPairingSession::query()->create([
        'code' => 'GHI-789',
        'token' => Str::random(64),
        'expires_at' => now()->addMinutes(10),
    ]);

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('display.pair.store', ['code' => $pairingSession->code]), [
            'screen_id' => $screen->id,
        ])
        ->assertForbidden();
});
