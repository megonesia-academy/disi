<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\CurrentTenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureTenantIsActive
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    /**
     * Block workspace access for subscribers whose tenant is suspended.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || $user->isAppAdmin()) {
            return $next($request);
        }

        // Allow leaving a suspended tenant through the tenant switcher.
        if ($request->routeIs('tenants.switch')) {
            return $next($request);
        }

        $tenant = $this->currentTenant->resolve($request);

        abort_if(
            $tenant->status === Tenant::STATUS_SUSPENDED,
            403,
            'Workspace tenant sedang suspended. Hubungi admin aplikasi untuk mengaktifkan kembali.',
        );

        return $next($request);
    }
}
