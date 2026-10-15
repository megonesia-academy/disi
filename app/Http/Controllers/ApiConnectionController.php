<?php

namespace App\Http\Controllers;

use App\Models\ApiConnection;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApiConnectionController extends Controller
{
    public function __construct(private readonly CurrentTenant $currentTenant, private readonly ActivityLogger $activityLogger) {}

    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        return Inertia::render('integrations/ApiConnections', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'providers' => config('signage.api_providers', []),
            'connections' => ApiConnection::query()->where('tenant_id', $tenant->id)->orderBy('name')->get()->map(fn (ApiConnection $connection): array => [
                'id' => $connection->id,
                'name' => $connection->name,
                'provider' => $connection->provider,
                'is_active' => $connection->is_active,
                'is_approved' => $connection->is_approved,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $validated = $this->validated($request, $tenant);
        $connection = ApiConnection::query()->create($this->data($validated, $tenant));
        $this->activityLogger->log($request, $tenant, 'api_connection.created', $connection, ['connection_name' => $connection->name]);

        return to_route('integrations.api.index');
    }

    public function update(Request $request, ApiConnection $apiConnection): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $apiConnection);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $apiConnection->update($this->data($this->validated($request, $tenant, $apiConnection), $tenant, $apiConnection));

        return to_route('integrations.api.index');
    }

    public function destroy(Request $request, ApiConnection $apiConnection): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $apiConnection);
        $this->currentTenant->authorizeCanManage($request, $tenant);
        $apiConnection->delete();

        return to_route('integrations.api.index');
    }

    public function approve(Request $request, ApiConnection $apiConnection): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $apiConnection);
        abort_unless($this->currentTenant->role($request, $tenant) === CurrentTenant::ROLE_OWNER, 403);
        $apiConnection->forceFill(['is_approved' => true])->save();

        return to_route('integrations.api.index');
    }

    public function test(Request $request, ApiConnection $apiConnection): JsonResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $apiConnection);
        abort_unless($apiConnection->is_approved && $apiConnection->is_active, 422, 'Connection belum disetujui atau tidak aktif.');
        $provider = config('signage.api_providers.'.$apiConnection->provider);
        abort_unless(is_array($provider), 422, 'Provider tidak tersedia.');
        $credential = $apiConnection->credential ? Crypt::decryptString($apiConnection->credential) : null;

        try {
            $response = Http::acceptJson()->withHeaders($this->headers($apiConnection->auth_type, $credential))->timeout(8)->get($provider['healthcheck_url'] ?? $provider['base_url']);
        } catch (\Throwable $exception) {
            return response()->json(['message' => $exception->getMessage(), 'success' => false], 422);
        }

        $payload = $response->json();

        return response()->json([
            'message' => $response->successful() ? 'Connection successful.' : 'Provider returned HTTP '.$response->status().'.',
            'status' => $response->status(),
            'success' => $response->successful(),
            'data' => is_array($payload) ? $payload : [],
        ], $response->successful() ? 200 : 422);
    }

    private function validated(Request $request, $tenant, ?ApiConnection $connection = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('api_connections', 'name')->where('tenant_id', $tenant->id)->ignore($connection?->id)],
            'provider' => ['required', Rule::in(array_keys(config('signage.api_providers', [])))],
            'auth_type' => ['required', Rule::in(['none', 'bearer', 'api_key'])],
            'credential' => ['nullable', 'string', 'max:2000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }

    private function data(array $validated, $tenant, ?ApiConnection $connection = null): array
    {
        $provider = config('signage.api_providers.'.$validated['provider']);

        return [
            'tenant_id' => $tenant->id,
            'name' => $validated['name'],
            'provider' => $validated['provider'],
            'base_url' => $provider['base_url'],
            'auth_type' => $validated['auth_type'],
            'credential' => filled($validated['credential'] ?? null) ? Crypt::encryptString($validated['credential']) : $connection?->credential,
            'is_active' => $validated['is_active'],
            'is_approved' => $connection?->is_approved ?? false,
        ];
    }

    private function headers(string $type, ?string $credential): array
    {
        if (! $credential) {
            return [];
        }

        return match ($type) {
            'bearer' => ['Authorization' => 'Bearer '.$credential],
            'api_key' => ['X-API-Key' => $credential],
            default => [],
        };
    }
}
