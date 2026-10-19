<?php

namespace App\Http\Controllers;

use App\Models\ApiConnection;
use App\Models\MediaAsset;
use App\Models\Tenant;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use App\Support\OverlayCatalog;
use App\Support\OverlayRules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceSettingsController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function edit(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        return Inertia::render('workspace/Settings', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'billing_email' => $tenant->billing_email,
                'status' => $tenant->status,
                'settings' => [
                    'brand_color' => $tenant->settings['brand_color'] ?? '#0891b2',
                    'player_accent_label' => $tenant->settings['player_accent_label'] ?? $tenant->name,
                    'logo_path' => $tenant->settings['logo_path'] ?? null,
                    'logo_url' => $this->logoUrl($tenant->settings['logo_path'] ?? null),
                    'logo_width' => $tenant->settings['logo_width'] ?? 120,
                    'logo_position' => $tenant->settings['logo_position'] ?? 'top-right',
                    'logo_offset_x' => $tenant->settings['logo_offset_x'] ?? 0,
                    'logo_offset_y' => $tenant->settings['logo_offset_y'] ?? 0,
                    'player_behavior' => [
                        'default_media_fit' => $tenant->settings['player_behavior']['default_media_fit'] ?? 'cover',
                        'manifest_refresh_seconds' => $tenant->settings['player_behavior']['manifest_refresh_seconds'] ?? 60,
                        'heartbeat_interval_seconds' => $tenant->settings['player_behavior']['heartbeat_interval_seconds'] ?? 30,
                        'show_player_overlay' => $tenant->settings['player_behavior']['show_player_overlay'] ?? true,
                    ],
                    'billing_metadata' => [
                        'company_name' => $tenant->settings['billing_metadata']['company_name'] ?? '',
                        'tax_id' => $tenant->settings['billing_metadata']['tax_id'] ?? '',
                        'billing_address' => $tenant->settings['billing_metadata']['billing_address'] ?? '',
                        'phone' => $tenant->settings['billing_metadata']['phone'] ?? '',
                    ],
                    'overlay_defaults' => OverlayCatalog::normalizeList($tenant->settings['overlay_defaults'] ?? []),
                ],
            ],
            'mediaAssets' => MediaAsset::query()
                ->where('tenant_id', $tenant->id)
                ->whereIn('type', [MediaAsset::TYPE_IMAGE, MediaAsset::TYPE_IMAGE_URL])
                ->orderBy('name')
                ->get()
                ->map(fn (MediaAsset $asset): array => [
                    'id' => $asset->id,
                    'name' => $asset->name,
                    'type' => $asset->type,
                    'url' => $this->mediaAssetUrl($asset),
                ])->values(),
            'apiConnections' => ApiConnection::query()
                ->where(fn ($query) => $query
                    ->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id))
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
                ->map(fn (ApiConnection $connection): array => [
                    'id' => $connection->id,
                    'name' => $connection->name,
                    'provider' => $connection->provider,
                ])->values(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'alpha_dash:ascii',
                Rule::unique(Tenant::class, 'slug')->ignore($tenant),
            ],
            'billing_email' => ['nullable', 'email', 'max:255'],
            'brand_color' => ['required', 'string', 'regex:/^#[0-9A-Fa-f]{6}$/'],
            'player_accent_label' => ['nullable', 'string', 'max:255'],
            'logo' => ['nullable', 'image', 'max:2048'],
            'logo_width' => ['required', 'integer', 'min:32', 'max:600'],
            'logo_position' => ['required', Rule::in([
                'top-left', 'top-center', 'top-right',
                'center-left', 'center', 'center-right',
                'bottom-left', 'bottom-center', 'bottom-right',
            ])],
            'logo_offset_x' => ['required', 'integer', 'min:-500', 'max:500'],
            'logo_offset_y' => ['required', 'integer', 'min:-500', 'max:500'],
            'default_media_fit' => ['sometimes', Rule::in(['cover', 'contain', 'fill'])],
            'manifest_refresh_seconds' => ['required', 'integer', 'min:10', 'max:3600'],
            'heartbeat_interval_seconds' => ['required', 'integer', 'min:5', 'max:300'],
            'show_player_overlay' => ['required', 'boolean'],
            'billing_company_name' => ['nullable', 'string', 'max:255'],
            'billing_tax_id' => ['nullable', 'string', 'max:255'],
            'billing_address' => ['nullable', 'string', 'max:2000'],
            'billing_phone' => ['nullable', 'string', 'max:255'],
            ...OverlayRules::rules($tenant, 'overlay_defaults'),
        ]);

        $settings = $tenant->settings ?? [];
        $logoPath = $settings['logo_path'] ?? null;

        if ($request->hasFile('logo')) {
            if (is_string($logoPath)) {
                Storage::disk('public')->delete($logoPath);
            }

            $logoPath = $request->file('logo')?->store("tenants/{$tenant->id}/branding", 'public');
        }

        $tenant->update([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'billing_email' => $validated['billing_email'] ?? null,
            'settings' => [
                ...$settings,
                'brand_color' => $validated['brand_color'],
                'player_accent_label' => $validated['player_accent_label'] ?: $validated['name'],
                'logo_path' => $logoPath,
                'logo_width' => (int) $validated['logo_width'],
                'logo_position' => $validated['logo_position'],
                'logo_offset_x' => (int) $validated['logo_offset_x'],
                'logo_offset_y' => (int) $validated['logo_offset_y'],
                'player_behavior' => [
                    'default_media_fit' => $validated['default_media_fit']
                        ?? ($settings['player_behavior']['default_media_fit'] ?? 'cover'),
                    'manifest_refresh_seconds' => (int) $validated['manifest_refresh_seconds'],
                    'heartbeat_interval_seconds' => (int) $validated['heartbeat_interval_seconds'],
                    'show_player_overlay' => (bool) $validated['show_player_overlay'],
                ],
                'billing_metadata' => [
                    'company_name' => $validated['billing_company_name'] ?? null,
                    'tax_id' => $validated['billing_tax_id'] ?? null,
                    'billing_address' => $validated['billing_address'] ?? null,
                    'phone' => $validated['billing_phone'] ?? null,
                ],
                'overlay_defaults' => OverlayCatalog::normalizeList($validated['overlay_defaults'] ?? []),
            ],
        ]);

        $this->activityLogger->log($request, $tenant, 'workspace.updated', $tenant, [
            'workspace_name' => $tenant->name,
            'workspace_slug' => $tenant->slug,
            'brand_color' => $tenant->settings['brand_color'] ?? null,
            'logo_updated' => $request->hasFile('logo'),
        ]);

        return to_route('workspace.settings.edit');
    }

    private function logoUrl(mixed $logoPath): ?string
    {
        if (! is_string($logoPath) || $logoPath === '') {
            return null;
        }

        return asset('storage/'.ltrim($logoPath, '/'));
    }

    private function mediaAssetUrl(MediaAsset $mediaAsset): string
    {
        if ($mediaAsset->usesExternalUrl()) {
            return $mediaAsset->path;
        }

        return asset('storage/'.ltrim($mediaAsset->path, '/'));
    }
}
