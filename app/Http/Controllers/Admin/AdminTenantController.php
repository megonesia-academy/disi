<?php

namespace App\Http\Controllers\Admin;

use App\Exceptions\InsufficientTokensException;
use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TokenTransaction;
use App\Services\TenantBilling;
use App\Support\ActivityLogger;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminTenantController extends Controller
{
    public function __construct(
        private readonly ActivityLogger $activityLogger,
        private readonly TenantBilling $billing,
    ) {}

    public function index(): Response
    {
        $tenants = Tenant::query()
            ->withCount(['users', 'screens', 'devices', 'playlists', 'mediaAssets'])
            ->with(['users' => fn ($query) => $query->wherePivot('role', 'owner')])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/Tenants', [
            'tenants' => $tenants->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'billing_email' => $tenant->billing_email,
                'status' => $tenant->status,
                'token_balance' => $tenant->token_balance,
                'subscription_status' => $tenant->subscription_status,
                'subscription_active' => $tenant->hasActiveSubscription(),
                'subscription_renews_at' => $tenant->subscription_renews_at?->format('d M Y'),
                'owner' => ($owner = $tenant->users->first()) ? [
                    'name' => $owner->name,
                    'email' => $owner->email,
                ] : null,
                'users_count' => $tenant->users_count,
                'screens_count' => $tenant->screens_count,
                'devices_count' => $tenant->devices_count,
                'playlists_count' => $tenant->playlists_count,
                'media_assets_count' => $tenant->media_assets_count,
                'created_at' => $tenant->created_at?->diffForHumans(),
            ]),
            'statuses' => [
                Tenant::STATUS_ACTIVE,
                Tenant::STATUS_SUSPENDED,
            ],
        ]);
    }

    public function updateStatus(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([Tenant::STATUS_ACTIVE, Tenant::STATUS_SUSPENDED])],
        ]);

        $tenant->update(['status' => $validated['status']]);

        $this->activityLogger->log($request, $tenant, 'platform.tenant.status', $tenant, [
            'tenant_name' => $tenant->name,
            'status' => $tenant->status,
        ]);

        return to_route('admin.tenants.index');
    }

    public function adjustTokens(Request $request, Tenant $tenant): RedirectResponse
    {
        $validated = $request->validate([
            'tokens' => ['required', 'integer', 'not_in:0', 'min:-100000', 'max:100000'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        $tokens = (int) $validated['tokens'];
        $description = $validated['description'] ?? null;

        try {
            $transaction = $tokens > 0
                ? $this->billing->credit(
                    $tenant,
                    $tokens,
                    TokenTransaction::TYPE_ADJUSTMENT,
                    $description ?? 'Penyesuaian saldo oleh admin',
                    null,
                    [],
                    $request->user(),
                )
                : $this->billing->debit(
                    $tenant,
                    abs($tokens),
                    TokenTransaction::TYPE_ADJUSTMENT,
                    $description ?? 'Penyesuaian saldo oleh admin',
                    null,
                    [],
                    $request->user(),
                );
        } catch (InsufficientTokensException $exception) {
            return back()->withErrors(['tokens' => $exception->getMessage()]);
        }

        $this->activityLogger->log($request, $tenant, 'platform.tenant.tokens', $tenant, [
            'tenant_name' => $tenant->name,
            'tokens' => $tokens,
            'balance_after' => $transaction->balance_after,
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "Saldo {$tenant->name} disesuaikan ({$tokens} token).",
        ]);

        return to_route('admin.tenants.index');
    }
}
