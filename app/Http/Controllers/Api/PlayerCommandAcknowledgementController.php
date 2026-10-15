<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\DeviceCommand;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerCommandAcknowledgementController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Device $device, DeviceCommand $command): JsonResponse
    {
        abort_unless($device->is_active, 404);
        abort_unless($device->hasValidPlayerSecret($request->query('token')), 404);
        abort_unless($command->device_id === $device->id, 404);

        $command->forceFill([
            'status' => DeviceCommand::STATUS_ACKNOWLEDGED,
            'acknowledged_at' => now(),
        ])->save();

        return response()->json([
            'ok' => true,
            'command' => [
                'id' => $command->id,
                'status' => $command->status,
                'acknowledged_at' => $command->acknowledged_at?->toISOString(),
            ],
        ]);
    }
}
