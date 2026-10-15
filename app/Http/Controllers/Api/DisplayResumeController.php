<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DisplayResumeController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'device_key' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:255'],
        ]);

        $device = Device::query()
            ->where('key', $validated['device_key'])
            ->first();

        if (! $device || ! $device->is_active || ! $device->hasValidPlayerSecret($validated['token'])) {
            return response()->json([
                'status' => 'invalid',
                'player_url' => null,
            ], 404);
        }

        return response()->json([
            'status' => 'paired',
            'player_url' => route('player.show', [
                'device' => $device->key,
                'token' => $device->player_secret,
            ]),
        ]);
    }
}
