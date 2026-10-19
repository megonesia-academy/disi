<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Services\TenantBilling;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('tokens:charge-subscriptions {--dry-run : Tampilkan tenant yang akan ditagih tanpa menyimpan}')]
#[Description('Debit token langganan bulanan untuk tenant yang jatuh tempo')]
class ChargeSubscriptions extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(TenantBilling $billing): int
    {
        $due = Tenant::query()
            ->where('subscription_status', '!=', Tenant::SUBSCRIPTION_CANCELED)
            ->where(function ($query): void {
                $query->whereNull('subscription_renews_at')
                    ->orWhere('subscription_renews_at', '<=', now());
            })
            ->orderBy('name')
            ->get();

        if ($this->option('dry-run')) {
            $this->table(
                ['Tenant', 'Saldo', 'Jatuh tempo'],
                $due->map(fn (Tenant $tenant): array => [
                    $tenant->name,
                    $tenant->token_balance,
                    $tenant->subscription_renews_at?->toDateTimeString() ?? '-',
                ])->all(),
            );

            return self::SUCCESS;
        }

        $charged = 0;
        $pastDue = 0;

        foreach ($due as $tenant) {
            if ($billing->chargeSubscription($tenant)) {
                $charged++;

                continue;
            }

            $pastDue++;
            $this->warn("Token tidak cukup untuk {$tenant->name}; status diubah menjadi past_due.");
        }

        $this->info("Langganan ditagih: {$charged}. Past due: {$pastDue}.");

        return self::SUCCESS;
    }
}
