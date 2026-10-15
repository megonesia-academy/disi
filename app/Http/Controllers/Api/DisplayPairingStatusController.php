<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\DisplayPairingSession;
use Illuminate\Http\JsonResponse;

class DisplayPairingStatusController extends Controller
{
    public function __invoke(string $token): JsonResponse
    {
        $pairingSession = DisplayPairingSession::query()
            ->with('device')
            ->where('token', $token)
            ->firstOrFail();

        if ($pairingSession->isExpired() && ! $pairingSession->isPaired()) {
            return response()->json([
                'status' => 'expired',
                'player_url' => null,
            ]);
        }

        if (! $pairingSession->isPaired() || ! $pairingSession->device) {
            return response()->json([
                'status' => 'waiting',
                'player_url' => null,
            ]);
        }

        return response()->json([
            'status' => 'paired',
            'player_url' => route('player.show', [
                'device' => $pairingSession->device->key,
                'token' => $pairingSession->device->player_secret,
            ]),
        ]);
    }
}
