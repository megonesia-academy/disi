<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ApiConnection;
use App\Services\ApiConnectionTester;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminApiConnectionController extends Controller
{
    public function __construct(private readonly ApiConnectionTester $tester) {}

    public function index(): Response
    {
        return Inertia::render('admin/ApiConnections', [
            'providers' => config('signage.api_providers', []),
            'connections' => ApiConnection::query()
                ->whereNull('tenant_id')
                ->orderBy('name')
                ->get()
                ->map(fn (ApiConnection $connection): array => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'provider' => $connection->provider,
                    'auth_type' => $connection->auth_type,
                    'has_credential' => filled($connection->credential),
                    'is_active' => $connection->is_active,
                    'created_at' => $connection->created_at?->diffForHumans(),
                ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validated($request);

        $connection = ApiConnection::query()->create([
            ...$this->data($validated),
            'tenant_id' => null,
        ]);

        $connection->forceFill(['is_approved' => true])->save();

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'API connection platform dibuat.',
        ]);

        return to_route('admin.api-connections.index');
    }

    public function update(Request $request, ApiConnection $apiConnection): RedirectResponse
    {
        $this->ensurePlatform($apiConnection);

        $apiConnection->update($this->data(
            $this->validated($request, $apiConnection),
            $apiConnection,
        ));

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => 'API connection platform diperbarui.',
        ]);

        return to_route('admin.api-connections.index');
    }

    public function destroy(ApiConnection $apiConnection): RedirectResponse
    {
        $this->ensurePlatform($apiConnection);

        $apiConnection->delete();

        Inertia::flash('toast', [
            'type' => 'warning',
            'message' => 'API connection platform dihapus.',
        ]);

        return to_route('admin.api-connections.index');
    }

    public function test(ApiConnection $apiConnection): JsonResponse
    {
        $this->ensurePlatform($apiConnection);

        abort_unless($apiConnection->is_active, 422, 'Connection tidak aktif.');

        $result = $this->tester->test($apiConnection);

        return response()->json([
            'message' => $result['message'],
            'status' => $result['status'],
            'success' => $result['success'],
            'data' => $result['data'],
        ], $result['success'] ? 200 : 422);
    }

    /**
     * @return array<string, mixed>
     */
    private function validated(Request $request, ?ApiConnection $connection = null): array
    {
        return $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('api_connections', 'name')
                    ->whereNull('tenant_id')
                    ->ignore($connection?->id),
            ],
            'provider' => ['required', Rule::in(array_keys(config('signage.api_providers', [])))],
            'auth_type' => ['required', Rule::in(['none', 'bearer', 'api_key'])],
            'credential' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function data(array $validated, ?ApiConnection $connection = null): array
    {
        $provider = config('signage.api_providers.'.$validated['provider']);

        return [
            'name' => $validated['name'],
            'provider' => $validated['provider'],
            'base_url' => $provider['base_url'],
            'auth_type' => $validated['auth_type'],
            'credential' => filled($validated['credential'] ?? null)
                ? Crypt::encryptString($validated['credential'])
                : $connection?->credential,
            'is_active' => $validated['is_active'],
        ];
    }

    private function ensurePlatform(ApiConnection $apiConnection): void
    {
        abort_unless($apiConnection->isPlatform(), 404);
    }
}
