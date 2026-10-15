<?php

use App\Models\Device;
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
        ->assertJsonStructure(['player_url']);
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
