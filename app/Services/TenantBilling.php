<?php

namespace App\Services;

use App\Exceptions\InsufficientTokensException;
use App\Exceptions\SubscriptionInactiveException;
use App\Models\FeaturePrice;
use App\Models\Tenant;
use App\Models\TenantEntitlement;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TenantBilling
{
    public const FEATURE_EXTRA_SCREEN = 'extra_screen';

    public const FEATURE_EXTRA_DEVICE = 'extra_device';

    public const FEATURE_EXTRA_PLAYLIST = 'extra_playlist';

    public const FEATURE_PLAYLIST_ITEM = 'playlist_item';

    public const FEATURE_MONTHLY_SUBSCRIPTION = 'monthly_subscription';

    /**
     * @param  array<string, mixed>  $meta
     */
    public function credit(
        Tenant $tenant,
        int $tokens,
        string $type = TokenTransaction::TYPE_TOPUP,
        ?string $description = null,
        ?string $featureKey = null,
        array $meta = [],
        ?User $actor = null,
    ): TokenTransaction {
        return DB::transaction(fn (): TokenTransaction => $this->apply(
            $tenant,
            abs($tokens),
            $type,
            $description,
            $featureKey,
            $meta,
            $actor,
        ));
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    public function debit(
        Tenant $tenant,
        int $tokens,
        string $type = TokenTransaction::TYPE_ADJUSTMENT,
        ?string $description = null,
        ?string $featureKey = null,
        array $meta = [],
        ?User $actor = null,
    ): TokenTransaction {
        return DB::transaction(fn (): TokenTransaction => $this->apply(
            $tenant,
            -abs($tokens),
            $type,
            $description,
            $featureKey,
            $meta,
            $actor,
        ));
    }

    public function purchaseFeature(Tenant $tenant, FeaturePrice $feature, ?User $actor = null): TokenTransaction
    {
        if ($feature->kind !== FeaturePrice::KIND_ADDON || ! $feature->is_active) {
            throw new \RuntimeException('Fitur ini tidak tersedia untuk dibeli.');
        }

        return DB::transaction(function () use ($tenant, $feature, $actor): TokenTransaction {
            $transaction = $this->apply(
                $tenant,
                -$feature->token_cost,
                TokenTransaction::TYPE_PURCHASE,
                "Pembelian {$feature->label}",
                $feature->key,
                ['label' => $feature->label, 'token_cost' => $feature->token_cost],
                $actor,
            );

            $entitlement = TenantEntitlement::query()->firstOrCreate(
                ['tenant_id' => $tenant->id, 'feature_key' => $feature->key],
                ['quantity' => 0],
            );
            $entitlement->increment('quantity');

            return $transaction;
        });
    }

    public function entitlementQuantity(Tenant $tenant, string $featureKey): int
    {
        return (int) TenantEntitlement::query()
            ->where('tenant_id', $tenant->id)
            ->where('feature_key', $featureKey)
            ->value('quantity');
    }

    public function freeLimit(string $featureKey): int
    {
        $price = FeaturePrice::query()->where('key', $featureKey)->first();

        if (! $price) {
            return match ($featureKey) {
                self::FEATURE_EXTRA_SCREEN,
                self::FEATURE_EXTRA_DEVICE,
                self::FEATURE_EXTRA_PLAYLIST => 1,
                default => 0,
            };
        }

        return $price->free_limit;
    }

    public function screenLimit(Tenant $tenant): int
    {
        return $this->freeLimit(self::FEATURE_EXTRA_SCREEN)
            + $this->entitlementQuantity($tenant, self::FEATURE_EXTRA_SCREEN);
    }

    public function canAddScreen(Tenant $tenant): bool
    {
        $used = $tenant->screens()->count();

        if ($used < $this->freeLimit(self::FEATURE_EXTRA_SCREEN)) {
            return true;
        }

        return $tenant->hasActiveSubscription() && $used < $this->screenLimit($tenant);
    }

    public function canAddDevice(Tenant $tenant): bool
    {
        $used = $tenant->devices()->count();

        if ($used < $this->freeLimit(self::FEATURE_EXTRA_DEVICE)) {
            return true;
        }

        return $tenant->hasActiveSubscription() && $used < $this->deviceLimit($tenant);
    }

    public function canUsePaidFeatures(Tenant $tenant): bool
    {
        return $tenant->hasActiveSubscription();
    }

    public function assertCanUsePaidFeatures(Tenant $tenant): void
    {
        if (! $this->canUsePaidFeatures($tenant)) {
            throw new SubscriptionInactiveException($tenant->subscription_status ?? 'unknown');
        }
    }

    public function deviceLimit(Tenant $tenant): int
    {
        return $this->freeLimit(self::FEATURE_EXTRA_DEVICE)
            + $this->entitlementQuantity($tenant, self::FEATURE_EXTRA_DEVICE);
    }

    public function playlistLimit(Tenant $tenant): int
    {
        return $this->freeLimit(self::FEATURE_EXTRA_PLAYLIST)
            + $this->entitlementQuantity($tenant, self::FEATURE_EXTRA_PLAYLIST);
    }

    public function canAddPlaylist(Tenant $tenant): bool
    {
        $used = $tenant->playlists()->count();

        if ($used < $this->freeLimit(self::FEATURE_EXTRA_PLAYLIST)) {
            return true;
        }

        return $tenant->hasActiveSubscription() && $used < $this->playlistLimit($tenant);
    }

    public function playlistItemFreeLimit(): int
    {
        return $this->freeLimit(self::FEATURE_PLAYLIST_ITEM);
    }

    public function playlistItemCost(): int
    {
        return (int) FeaturePrice::query()
            ->where('key', self::FEATURE_PLAYLIST_ITEM)
            ->where('is_active', true)
            ->value('token_cost');
    }

    /**
     * Token yang perlu didebit untuk item playlist di atas batas gratis.
     */
    public function playlistItemCharge(int $newItemCount, int $previousItemCount = 0): int
    {
        $free = $this->playlistItemFreeLimit();
        $billableBefore = max($previousItemCount, $free);
        $billableNow = max($newItemCount, $free);
        $extraItems = $billableNow - $billableBefore;

        if ($extraItems <= 0) {
            return 0;
        }

        return $extraItems * $this->playlistItemCost();
    }

    public function monthlySubscriptionCost(): int
    {
        return (int) FeaturePrice::query()
            ->where('key', self::FEATURE_MONTHLY_SUBSCRIPTION)
            ->where('is_active', true)
            ->value('token_cost');
    }

    public function chargeSubscription(Tenant $tenant): bool
    {
        $cost = $this->monthlySubscriptionCost();

        if ($cost > 0 && $tenant->token_balance < $cost) {
            $tenant->forceFill([
                'subscription_status' => Tenant::SUBSCRIPTION_PAST_DUE,
            ])->save();

            return false;
        }

        if ($cost > 0) {
            $this->apply(
                $tenant,
                -$cost,
                TokenTransaction::TYPE_SUBSCRIPTION,
                'Langganan bulanan',
                self::FEATURE_MONTHLY_SUBSCRIPTION,
            );
        }

        $tenant->forceFill([
            'subscription_status' => Tenant::SUBSCRIPTION_ACTIVE,
            'subscription_renews_at' => now()->addMonth(),
        ])->save();

        return true;
    }

    /**
     * @param  array<string, mixed>  $meta
     */
    private function apply(
        Tenant $tenant,
        int $signedTokens,
        string $type,
        ?string $description = null,
        ?string $featureKey = null,
        array $meta = [],
        ?User $actor = null,
    ): TokenTransaction {
        $locked = Tenant::query()->whereKey($tenant->id)->lockForUpdate()->firstOrFail();
        $balance = $locked->token_balance + $signedTokens;

        if ($balance < 0) {
            throw new InsufficientTokensException(abs($signedTokens), $locked->token_balance);
        }

        $locked->forceFill(['token_balance' => $balance])->save();
        $tenant->setAttribute('token_balance', $balance);

        return TokenTransaction::query()->create([
            'tenant_id' => $tenant->id,
            'type' => $type,
            'tokens' => $signedTokens,
            'balance_after' => $balance,
            'feature_key' => $featureKey,
            'description' => $description,
            'meta' => $meta === [] ? null : $meta,
            'created_by' => $actor?->id,
        ]);
    }
}
