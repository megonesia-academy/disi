<?php

namespace App\Http\Controllers;

use App\Models\ApiConnection;
use App\Services\ApiConnectionTester;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ApiConnectionController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ApiConnectionTester $tester,
    ) {}

    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        return Inertia::render('integrations/ApiConnections', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'providers' => config('signage.api_providers', []),
            'connections' => ApiConnection::query()
                ->where(fn ($query) => $query
                    ->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (ApiConnection $connection): array => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'provider' => $connection->provider,
                    'auth_type' => $connection->auth_type,
                    'has_credential' => filled($connection->credential),
                    'is_active' => $connection->is_active,
                    'is_approved' => $connection->is_approved,
                    'source' => $connection->isPlatform() ? 'platform' : 'workspace',
                    'created_at' => $connection->created_at?->diffForHumans(),
                ]),
        ]);
    }

    public function test(Request $request, ApiConnection $apiConnection): JsonResponse
    {
        $tenant = $this->currentTenant->resolve($request);

        abort_unless(
            $apiConnection->isPlatform() || $apiConnection->tenant_id === $tenant->id,
            404,
        );

        $this->currentTenant->authorizeCanManage($request, $tenant);

        abort_unless($apiConnection->is_active, 422, 'Connection tidak aktif.');
        abort_unless(
            $apiConnection->isPlatform() || $apiConnection->is_approved,
            422,
            'Connection belum disetujui atau tidak aktif.',
        );

        $result = $this->tester->test($apiConnection);

        return response()->json([
            'message' => $result['message'],
            'status' => $result['status'],
            'success' => $result['success'],
            'data' => $result['data'],
        ], $result['success'] ? 200 : 422);
    }
}
