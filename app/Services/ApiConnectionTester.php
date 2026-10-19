<?php

namespace App\Services;

use App\Models\ApiConnection;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;

class ApiConnectionTester
{
    /**
     * @return array{success: bool, status: int|null, message: string, data: array<mixed>}
     */
    public function test(ApiConnection $connection): array
    {
        $provider = config('signage.api_providers.'.$connection->provider);

        if (! is_array($provider)) {
            return [
                'success' => false,
                'status' => null,
                'message' => 'Provider tidak tersedia.',
                'data' => [],
            ];
        }

        $credential = $connection->credential
            ? Crypt::decryptString($connection->credential)
            : null;

        try {
            $response = Http::acceptJson()
                ->withHeaders($this->headers($connection->auth_type, $credential))
                ->timeout(8)
                ->get($provider['healthcheck_url'] ?? $provider['base_url']);
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'status' => null,
                'message' => $exception->getMessage(),
                'data' => [],
            ];
        }

        $payload = $response->json();

        return [
            'success' => $response->successful(),
            'status' => $response->status(),
            'message' => $response->successful()
                ? 'Connection successful.'
                : 'Provider returned HTTP '.$response->status().'.',
            'data' => is_array($payload) ? $payload : [],
        ];
    }

    /**
     * @return array<string, string>
     */
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
