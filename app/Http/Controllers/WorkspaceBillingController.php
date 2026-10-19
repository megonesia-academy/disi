<?php

namespace App\Http\Controllers;

use App\Exceptions\InsufficientTokensException;
use App\Models\FeaturePrice;
use App\Models\TokenTopupRequest;
use App\Services\TenantBilling;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceBillingController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly TenantBilling $billing,
    ) {}

    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        $features = FeaturePrice::query()
            ->where('is_active', true)
            ->where('kind', FeaturePrice::KIND_ADDON)
            ->orderBy('sort_order')
            ->orderBy('label')
            ->get();

        $subscription = FeaturePrice::query()
            ->where('key', TenantBilling::FEATURE_MONTHLY_SUBSCRIPTION)
            ->first();

        $entitlements = $tenant->entitlements()->get()->keyBy('feature_key');

        return Inertia::render('workspace/Billing', [
            'tenant' => ['id' => $tenant->id, 'name' => $tenant->name],
            'balance' => $tenant->token_balance,
            'subscription' => [
                'status' => $tenant->subscription_status,
                'renews_at' => $tenant->subscription_renews_at?->format('d M Y'),
                'active' => $tenant->hasActiveSubscription(),
                'monthly_cost' => (int) ($subscription?->token_cost ?? 0),
            ],
            'usage' => [
                'screens' => $tenant->screens()->count(),
                'screen_limit' => $this->billing->screenLimit($tenant),
                'free_screens' => $this->billing->freeLimit(TenantBilling::FEATURE_EXTRA_SCREEN),
                'devices' => $tenant->devices()->count(),
                'device_limit' => $this->billing->deviceLimit($tenant),
                'free_devices' => $this->billing->freeLimit(TenantBilling::FEATURE_EXTRA_DEVICE),
                'playlists' => $tenant->playlists()->count(),
                'playlist_limit' => $this->billing->playlistLimit($tenant),
                'free_playlists' => $this->billing->freeLimit(TenantBilling::FEATURE_EXTRA_PLAYLIST),
                'free_items' => $this->billing->playlistItemFreeLimit(),
                'item_cost' => $this->billing->playlistItemCost(),
            ],
            'features' => $features->map(fn (FeaturePrice $feature): array => [
                'key' => $feature->key,
                'label' => $feature->label,
                'description' => $feature->description,
                'token_cost' => $feature->token_cost,
                'free_limit' => $feature->free_limit,
                'unit_label' => $feature->unit_label,
                'owned' => (int) ($entitlements[$feature->key]?->quantity ?? 0),
            ])->values(),
            'transactions' => $tenant->tokenTransactions()
                ->latest()
                ->limit(20)
                ->get()
                ->map(fn ($transaction): array => [
                    'id' => $transaction->id,
                    'type' => $transaction->type,
                    'tokens' => $transaction->tokens,
                    'balance_after' => $transaction->balance_after,
                    'description' => $transaction->description,
                    'created_at' => $transaction->created_at?->diffForHumans(),
                ])->values(),
            'topupRequests' => $tenant->topupRequests()
                ->latest()
                ->limit(10)
                ->get()
                ->map(fn (TokenTopupRequest $topup): array => [
                    'id' => $topup->id,
                    'tokens' => $topup->tokens,
                    'status' => $topup->status,
                    'note' => $topup->note,
                    'created_at' => $topup->created_at?->diffForHumans(),
                ])->values(),
        ]);
    }

    public function purchase(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $validated = $request->validate([
            'feature_key' => ['required', 'string'],
        ]);

        $feature = FeaturePrice::query()
            ->where('key', $validated['feature_key'])
            ->where('kind', FeaturePrice::KIND_ADDON)
            ->where('is_active', true)
            ->first();

        if (! $feature) {
            return back()->withErrors(['billing' => 'Fitur tidak ditemukan atau tidak aktif.']);
        }

        try {
            $this->billing->purchaseFeature($tenant, $feature, $request->user());
        } catch (InsufficientTokensException $exception) {
            return back()->withErrors(['billing' => $exception->getMessage()]);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => "{$feature->label} berhasil dibeli.",
        ]);

        return back();
    }

    public function topup(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $validated = $request->validate([
            'tokens' => ['required', 'integer', 'min:10', 'max:100000'],
            'note' => ['nullable', 'string', 'max:255'],
        ]);

        TokenTopupRequest::query()->create([
            'tenant_id' => $tenant->id,
            'tokens' => $validated['tokens'],
            'status' => TokenTopupRequest::STATUS_PENDING,
            'note' => $validated['note'] ?? null,
            'requested_by' => $request->user()?->id,
        ]);

        Inertia::flash('toast', [
            'type' => 'info',
            'message' => 'Permintaan topup dikirim. Menunggu approval admin aplikasi.',
        ]);

        return back();
    }
}
