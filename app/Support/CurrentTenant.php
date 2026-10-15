<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CurrentTenant
{
    public const SESSION_KEY = 'current_tenant_id';

    public const ROLE_OWNER = 'owner';

    public const ROLE_ADMIN = 'admin';

    public const ROLE_VIEWER = 'viewer';

    public function resolve(Request $request): Tenant
    {
        $tenant = $this->fromSession($request);

        $tenant ??= $request->user()?->tenants()->first();

        if (! $tenant && $request->user()) {
            $tenant = Tenant::query()->create([
                'name' => "{$request->user()->name}'s Workspace",
                'slug' => 'workspace-'.$request->user()->id.'-'.Str::lower(Str::random(6)),
                'status' => Tenant::STATUS_ACTIVE,
            ]);

            $tenant->users()->attach($request->user()->id, [
                'role' => 'owner',
                'accepted_at' => now(),
            ]);
        }

        $tenant ??= Tenant::query()->where('slug', 'demo')->first();

        abort_unless($tenant, 404);

        if ($request->hasSession()) {
            $request->session()->put(self::SESSION_KEY, $tenant->id);
        }

        return $tenant;
    }

    public function switch(Request $request, Tenant $tenant): void
    {
        abort_unless($request->user()?->tenants()->whereKey($tenant->id)->exists(), 404);

        $request->session()->put(self::SESSION_KEY, $tenant->id);
    }

    public function authorizeModel(Tenant $tenant, Model $model): void
    {
        abort_unless($model->getAttribute('tenant_id') === $tenant->id, 404);
    }

    public function role(Request $request, Tenant $tenant): ?string
    {
        $membership = $request->user()
            ?->tenants()
            ->whereKey($tenant->id)
            ->first()
            ?->pivot;

        return is_string($membership?->role) ? $membership->role : null;
    }

    public function canManage(Request $request, Tenant $tenant): bool
    {
        return in_array($this->role($request, $tenant), [
            self::ROLE_OWNER,
            self::ROLE_ADMIN,
        ], true);
    }

    public function authorizeCanManage(Request $request, Tenant $tenant): void
    {
        abort_unless($this->canManage($request, $tenant), 403);
    }

    private function fromSession(Request $request): ?Tenant
    {
        if (! $request->hasSession() || ! $request->user()) {
            return null;
        }

        $tenantId = $request->session()->get(self::SESSION_KEY);

        if (! is_string($tenantId)) {
            return null;
        }

        return $request->user()
            ->tenants()
            ->whereKey($tenantId)
            ->first();
    }
}
