<?php

namespace App\Http\Middleware;

use App\Models\FeaturePrice;
use App\Models\Tenant;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that's loaded on the first page visit.
     *
     * @see https://inertiajs.com/server-side-setup#root-template
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determines the current asset version.
     *
     * @see https://inertiajs.com/asset-versioning
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @see https://inertiajs.com/shared-data
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $tenant = $request->user()
            ? app(CurrentTenant::class)->resolve($request)
            : null;
        $currentTenant = app(CurrentTenant::class);
        $monthlyCost = (int) FeaturePrice::query()
            ->where('key', 'monthly_subscription')
            ->where('is_active', true)
            ->value('token_cost');

        // Application admins may switch into any tenant on the platform.
        $availableTenants = $request->user()?->isAppAdmin()
            ? Tenant::query()
                ->orderBy('name')
                ->get(['id', 'name', 'slug', 'status', 'token_balance'])
            : $request->user()?->tenants()
                ->orderBy('name')
                ->get(['tenants.id', 'tenants.name', 'tenants.slug', 'tenants.status', 'tenants.token_balance']);

        return [
            ...parent::share($request),
            'name' => config('app.name'),
            'auth' => [
                'user' => $request->user(),
                'can_manage_platform' => $request->user()?->can('manage-platform') ?? false,
            ],
            'tenancy' => [
                'current' => $tenant ? [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'status' => $tenant->status,
                    'token_balance' => $tenant->token_balance,
                    'subscription_status' => $tenant->subscription_status,
                    'monthly_subscription_cost' => $monthlyCost,
                    'role' => $currentTenant->role($request, $tenant),
                    'can_manage' => $currentTenant->canManage($request, $tenant),
                ] : null,
                'tenants' => ($availableTenants ?? collect())
                    ->map(fn ($tenant): array => [
                        'id' => $tenant->id,
                        'name' => $tenant->name,
                        'slug' => $tenant->slug,
                        'status' => $tenant->status,
                        'token_balance' => $tenant->token_balance,
                    ])
                    ->values(),
            ],
            'sidebarOpen' => ! $request->hasCookie('sidebar_state') || $request->cookie('sidebar_state') === 'true',
            'initialAppearance' => $request->cookie('appearance') ?? 'system',
        ];
    }
}
