<?php

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Models\Tenant;
use App\Models\User;
use App\Support\CurrentTenant;
use Illuminate\Support\Str;

function createCommandDevice(): array
{
    $tenant = Tenant::query()->create([
        'name' => 'Command Tenant',
        'slug' => 'command-'.Str::random(6),
        'status' => Tenant::STATUS_ACTIVE,
    ]);

    $user = User::factory()->create();

    $tenant->users()->attach($user->id, [
        'role' => CurrentTenant::ROLE_OWNER,
        'accepted_at' => now(),
    ]);

    $device = Device::query()->create([
        'tenant_id' => $tenant->id,
        'name' => 'Lobby Player',
        'key' => 'lobby-player-'.Str::random(6),
        'player_secret' => Str::random(48),
        'status' => Device::STATUS_OFFLINE,
        'is_active' => true,
    ]);

    return [$tenant, $user, $device];
}

it('queues a command for a tenant device', function (): void {
    [$tenant, $user, $device] = createCommandDevice();

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('devices.commands.store', $device), [
            'type' => DeviceCommand::TYPE_REFRESH_MANIFEST,
        ])
        ->assertRedirect(route('devices.show', $device));

    expect(DeviceCommand::query()
        ->where('tenant_id', $tenant->id)
        ->where('device_id', $device->id)
        ->where('type', DeviceCommand::TYPE_REFRESH_MANIFEST)
        ->where('status', DeviceCommand::STATUS_QUEUED)
        ->exists())->toBeTrue();
});

it('queues a clear cache command for a tenant device', function (): void {
    [$tenant, $user, $device] = createCommandDevice();

    $this->actingAs($user)
        ->withSession([CurrentTenant::SESSION_KEY => $tenant->id])
        ->post(route('devices.commands.store', $device), [
            'type' => DeviceCommand::TYPE_CLEAR_CACHE,
        ])
        ->assertRedirect(route('devices.show', $device));

    expect(DeviceCommand::query()
        ->where('device_id', $device->id)
        ->where('type', DeviceCommand::TYPE_CLEAR_CACHE)
        ->where('status', DeviceCommand::STATUS_QUEUED)
        ->exists())->toBeTrue();
});

it('returns queued commands on heartbeat and marks them sent', function (): void {
    [, , $device] = createCommandDevice();

    $command = DeviceCommand::query()->create([
        'tenant_id' => $device->tenant_id,
        'device_id' => $device->id,
        'type' => DeviceCommand::TYPE_IDENTIFY,
        'payload' => ['duration_seconds' => 10],
        'status' => DeviceCommand::STATUS_QUEUED,
    ]);

    $this->postJson(route('api.player.heartbeat', [
        'device' => $device->key,
        'token' => $device->player_secret,
    ]))
        ->assertOk()
        ->assertJsonPath('commands.0.id', $command->id)
        ->assertJsonPath('commands.0.type', DeviceCommand::TYPE_IDENTIFY)
        ->assertJsonPath('commands.0.payload.duration_seconds', 10)
        ->assertJsonStructure(['commands' => [['ack_url']]]);

    $command->refresh();

    expect($command->status)->toBe(DeviceCommand::STATUS_SENT)
        ->and($command->sent_at)->not->toBeNull();
});

it('acknowledges a command from the assigned player', function (): void {
    [, , $device] = createCommandDevice();

    $command = DeviceCommand::query()->create([
        'tenant_id' => $device->tenant_id,
        'device_id' => $device->id,
        'type' => DeviceCommand::TYPE_REFRESH_MANIFEST,
        'status' => DeviceCommand::STATUS_SENT,
        'sent_at' => now(),
    ]);

    $this->postJson(route('api.player.commands.ack', [
        'device' => $device->key,
        'command' => $command->id,
        'token' => $device->player_secret,
    ]))
        ->assertOk()
        ->assertJsonPath('command.id', $command->id)
        ->assertJsonPath('command.status', DeviceCommand::STATUS_ACKNOWLEDGED);

    $command->refresh();

    expect($command->status)->toBe(DeviceCommand::STATUS_ACKNOWLEDGED)
        ->and($command->acknowledged_at)->not->toBeNull();
});

it('rejects command acknowledgement with an invalid token', function (): void {
    [, , $device] = createCommandDevice();

    $command = DeviceCommand::query()->create([
        'tenant_id' => $device->tenant_id,
        'device_id' => $device->id,
        'type' => DeviceCommand::TYPE_REFRESH_MANIFEST,
        'status' => DeviceCommand::STATUS_SENT,
        'sent_at' => now(),
    ]);

    $this->postJson(route('api.player.commands.ack', [
        'device' => $device->key,
        'command' => $command->id,
        'token' => 'wrong-token',
    ]))->assertNotFound();
});

it('prevents another device from acknowledging a command', function (): void {
    [, , $device] = createCommandDevice();
    [, , $otherDevice] = createCommandDevice();

    $command = DeviceCommand::query()->create([
        'tenant_id' => $device->tenant_id,
        'device_id' => $device->id,
        'type' => DeviceCommand::TYPE_REFRESH_MANIFEST,
        'status' => DeviceCommand::STATUS_SENT,
        'sent_at' => now(),
    ]);

    $this->postJson(route('api.player.commands.ack', [
        'device' => $otherDevice->key,
        'command' => $command->id,
        'token' => $otherDevice->player_secret,
    ]))->assertNotFound();
});
