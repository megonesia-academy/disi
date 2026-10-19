<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\Tenant;
use App\Services\PlayerManifestResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PlayerManifestController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, Device $device, PlayerManifestResolver $resolver): JsonResponse
    {
        abort_unless($device->hasValidPlayerSecret($request->query('token')), 404);
        abort_unless(
            $device->tenant()->where('status', Tenant::STATUS_ACTIVE)->exists(),
            404,
        );

        return response()->json($resolver->resolve($device));
    }
}
