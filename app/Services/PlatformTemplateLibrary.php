<?php

namespace App\Services;

use App\Models\ContentTemplate;
use App\Models\PlatformTemplate;
use App\Models\Tenant;
use App\Models\TokenTransaction;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class PlatformTemplateLibrary
{
    public function __construct(private readonly TenantBilling $billing) {}

    public function sync(PlatformTemplate $template): void
    {
        Tenant::query()->each(function (Tenant $tenant) use ($template): void {
            ContentTemplate::query()->updateOrCreate(
                [
                    'tenant_id' => $tenant->id,
                    'slug' => $template->slug,
                ],
                [
                    'platform_template_id' => $template->id,
                    'name' => $template->name,
                    'html' => $template->html,
                    'css' => $template->css,
                    'schema' => $template->schema ?? [],
                    'is_active' => $template->is_active,
                ],
            );
        });
    }

    public function syncAll(): void
    {
        PlatformTemplate::query()->each(
            fn (PlatformTemplate $template) => $this->sync($template),
        );
    }

    public function rent(Tenant $tenant, ContentTemplate $copy, ?User $actor = null): TokenTransaction
    {
        $platform = $copy->platformTemplate;

        if (! $platform || ! $platform->is_active || $platform->token_cost <= 0 || $copy->is_rented) {
            throw ValidationException::withMessages([
                'billing' => 'Template ini tidak dapat disewa.',
            ]);
        }

        $this->billing->assertCanUsePaidFeatures($tenant);

        $transaction = $this->billing->debit(
            $tenant,
            $platform->token_cost,
            TokenTransaction::TYPE_PURCHASE,
            "Sewa template {$copy->name}",
            'template',
            [
                'template_id' => $copy->id,
                'platform_template_id' => $platform->id,
            ],
            $actor,
        );

        $copy->forceFill(['is_rented' => true])->save();

        return $transaction;
    }
}
