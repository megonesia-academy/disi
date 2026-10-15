<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerHeartbeatController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Device $device): JsonResponse
    {
        abort_unless($device->is_active, 404);
        abort_unless($device->hasValidPlayerSecret($request->query('token')), 404);

        $validated = $request->validate([
            'app_version' => ['nullable', 'string', 'max:255'],
            'capabilities' => ['nullable', 'array'],
            'capabilities.*' => ['nullable'],
        ]);

        $device->forceFill([
            'app_version' => $validated['app_version'] ?? $device->app_version,
            'capabilities' => $validated['capabilities'] ?? $device->capabilities,
            'last_seen_at' => now(),
            'status' => Device::STATUS_ONLINE,
        ])->save();

        $commands = $device->commands()
            ->where('status', DeviceCommand::STATUS_QUEUED)
            ->oldest()
            ->limit(5)
            ->get();

        $commands->each->forceFill([
            'status' => DeviceCommand::STATUS_SENT,
            'sent_at' => now(),
        ])->each->save();

        return response()->json([
            'ok' => true,
            'device' => [
                'id' => $device->id,
                'key' => $device->key,
                'status' => $device->status,
                'last_seen_at' => $device->last_seen_at?->toISOString(),
            ],
            'commands' => $commands->map(fn (DeviceCommand $command): array => [
                'id' => $command->id,
                'type' => $command->type,
                'payload' => $command->payload ?? [],
                'created_at' => $command->created_at?->toISOString(),
                'ack_url' => route('api.player.commands.ack', [
                    'device' => $device->key,
                    'command' => $command->id,
                    'token' => $device->player_secret,
                ]),
            ])->values(),
        ]);
    }
}
