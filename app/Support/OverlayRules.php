<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Exists;

/**
 * Shared validation rules for playlist overlays and tenant overlay defaults.
 */
class OverlayRules
{
    /**
     * @return array<string, mixed>
     */
    public static function rules(Tenant $tenant, string $field = 'overlays'): array
    {
        return [
            $field => ['array', 'max:'.OverlayCatalog::MAX_OVERLAYS],
            $field.'.*.type' => ['required', Rule::in(OverlayCatalog::types())],
            $field.'.*.is_active' => ['nullable', 'boolean'],
            $field.'.*.config' => ['nullable', 'array'],
            $field.'.*.config.text' => ['nullable', 'string', 'max:1000'],
            $field.'.*.config.source' => ['nullable', Rule::in(['static', 'array'])],
            $field.'.*.config.items' => ['nullable', 'array', 'max:100'],
            $field.'.*.config.items.*' => ['string', 'max:1000'],
            $field.'.*.config.mode' => ['nullable', Rule::in(['loop', 'rotate'])],
            $field.'.*.config.item_duration_seconds' => ['nullable', 'integer', 'min:2', 'max:3600'],
            $field.'.*.config.format' => ['nullable', Rule::in(['24h', '12h', 'long', 'short', 'iso'])],
            $field.'.*.config.locale' => ['nullable', Rule::in(['id', 'en'])],
            $field.'.*.config.position' => ['nullable', Rule::in(OverlayCatalog::positions())],
            $field.'.*.config.offset_x' => ['nullable', 'integer', 'min:-500', 'max:500'],
            $field.'.*.config.offset_y' => ['nullable', 'integer', 'min:-500', 'max:500'],
            $field.'.*.config.speed' => ['nullable', 'integer', 'min:10', 'max:200'],
            $field.'.*.config.text_color' => ['nullable', 'string', 'max:20'],
            $field.'.*.config.background_color' => ['nullable', 'string', 'max:20'],
            $field.'.*.config.theme' => ['nullable', Rule::in(OverlayCatalog::THEMES)],
            $field.'.*.config.schedule' => ['nullable', 'array'],
            $field.'.*.config.schedule.days' => ['nullable', 'array', 'max:7'],
            $field.'.*.config.schedule.days.*' => ['integer', 'between:1,7'],
            $field.'.*.config.schedule.start' => ['nullable', 'date_format:H:i'],
            $field.'.*.config.schedule.end' => ['nullable', 'date_format:H:i'],
            $field.'.*.config.label' => ['nullable', 'string', 'max:120'],
            $field.'.*.config.separator' => ['nullable', 'string', 'max:10'],
            $field.'.*.config.template' => ['nullable', 'string', 'max:255'],
            $field.'.*.config.name' => ['nullable', Rule::in(['tenant', 'screen'])],
            $field.'.*.config.next_mode' => ['nullable', Rule::in(['next', 'now'])],
            $field.'.*.config.size' => ['nullable', 'integer', 'min:16', 'max:600'],
            $field.'.*.config.target_at' => ['nullable', 'string', 'max:40'],
            $field.'.*.config.prefix' => ['nullable', 'string', 'max:120'],
            $field.'.*.config.suffix' => ['nullable', 'string', 'max:120'],
            $field.'.*.config.finished_text' => ['nullable', 'string', 'max:120'],
            $field.'.*.config.title' => ['nullable', 'string', 'max:120'],
            $field.'.*.config.subtitle' => ['nullable', 'string', 'max:200'],
            $field.'.*.config.open_label' => ['nullable', 'string', 'max:60'],
            $field.'.*.config.closed_label' => ['nullable', 'string', 'max:60'],
            $field.'.*.config.show_times' => ['nullable', 'boolean'],
            $field.'.*.config.align' => ['nullable', Rule::in(['left', 'center', 'right'])],
            $field.'.*.config.color' => ['nullable', 'string', 'max:20'],
            $field.'.*.config.track_color' => ['nullable', 'string', 'max:20'],
            $field.'.*.config.height' => ['nullable', 'integer', 'min:2', 'max:40'],
            $field.'.*.config.layout' => ['nullable', Rule::in(['list', 'ticker'])],
            $field.'.*.config.item_limit' => ['nullable', 'integer', 'min:1', 'max:20'],
            $field.'.*.config.fields' => ['nullable', 'array', 'max:8'],
            $field.'.*.config.fields.*.label' => ['nullable', 'string', 'max:60'],
            $field.'.*.config.fields.*.path' => ['nullable', 'string', 'max:255'],
            $field.'.*.config.fields.*.prefix' => ['nullable', 'string', 'max:20'],
            $field.'.*.config.fields.*.suffix' => ['nullable', 'string', 'max:20'],
            $field.'.*.config.mapping' => ['nullable', 'array', 'max:20'],
            $field.'.*.config.mapping.*' => ['string', 'max:255'],
            $field.'.*.config.media_asset_id' => [
                'nullable',
                'required_if:'.$field.'.*.type,badge',
                Rule::exists('media_assets', 'id')->where('tenant_id', $tenant->id),
            ],
            $field.'.*.config.api_connection_id' => [
                'nullable',
                'required_if:'.$field.'.*.type,weather,api,rss',
                self::connectionRule($tenant),
            ],
            $field.'.*.config.endpoint' => [
                'nullable',
                'string',
                'max:2048',
                'required_if:'.$field.'.*.type,weather,api,rss',
            ],
            $field.'.*.config.refresh_seconds' => ['nullable', 'integer', 'min:10', 'max:86400'],
        ];
    }

    private static function connectionRule(Tenant $tenant): Exists
    {
        return Rule::exists('api_connections', 'id')
            ->where(fn ($query) => $query
                ->whereNull('tenant_id')
                ->orWhere('tenant_id', $tenant->id))
            ->where('is_active', true)
            ->where('is_approved', true);
    }
}
