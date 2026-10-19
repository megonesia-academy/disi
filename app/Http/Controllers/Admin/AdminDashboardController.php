<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Device;
use App\Models\MediaAsset;
use App\Models\Playlist;
use App\Models\Screen;
use App\Models\Tenant;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __invoke(): Response
    {
        $tenants = Tenant::query()
            ->withCount(['users', 'screens', 'devices', 'playlists', 'mediaAssets'])
            ->latest()
            ->get();

        return Inertia::render('admin/Dashboard', [
            'stats' => [
                'tenants' => $tenants->count(),
                'active_tenants' => $tenants->where('status', Tenant::STATUS_ACTIVE)->count(),
                'suspended_tenants' => $tenants->where('status', Tenant::STATUS_SUSPENDED)->count(),
                'subscribers' => User::query()->where('app_role', User::APP_ROLE_SUBSCRIBER)->count(),
                'admins' => User::query()->where('app_role', User::APP_ROLE_ADMIN)->count(),
                'screens' => Screen::query()->count(),
                'devices' => Device::query()->count(),
                'playlists' => Playlist::query()->count(),
                'media_assets' => MediaAsset::query()->count(),
                'total_tokens' => (int) Tenant::query()->sum('token_balance'),
                'past_due_tenants' => $tenants->where('subscription_status', Tenant::SUBSCRIPTION_PAST_DUE)->count(),
                'active_subscriptions' => $tenants->where('subscription_status', Tenant::SUBSCRIPTION_ACTIVE)->count(),
            ],
            'recentTenants' => $tenants->take(6)->map(fn (Tenant $tenant): array => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'status' => $tenant->status,
                'token_balance' => $tenant->token_balance,
                'subscription_status' => $tenant->subscription_status,
                'subscription_renews_at' => $tenant->subscription_renews_at?->format('d M Y'),
                'users_count' => $tenant->users_count,
                'devices_count' => $tenant->devices_count,
                'playlists_count' => $tenant->playlists_count,
                'created_at' => $tenant->created_at?->diffForHumans(),
            ])->values(),
        ]);
    }
}
