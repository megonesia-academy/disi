<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class AdminUserController extends Controller
{
    public function index(Request $request): Response
    {
        $users = User::query()
            ->with(['tenants' => fn ($query) => $query->select('tenants.id', 'tenants.name', 'tenants.slug')])
            ->withCount('tenants')
            ->orderByRaw('case when app_role = ? then 0 else 1 end', [User::APP_ROLE_ADMIN])
            ->orderBy('name')
            ->get();

        return Inertia::render('admin/Users', [
            'users' => $users->map(fn (User $user): array => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'app_role' => $user->app_role,
                'is_verified' => $user->hasVerifiedEmail(),
                'is_self' => $request->user()?->is($user) ?? false,
                'tenants_count' => $user->tenants_count,
                'tenants' => $user->tenants->map(fn ($tenant): array => [
                    'id' => $tenant->id,
                    'name' => $tenant->name,
                    'slug' => $tenant->slug,
                    'role' => $tenant->pivot->role,
                ])->values(),
                'created_at' => $user->created_at?->diffForHumans(),
            ]),
            'admins_count' => User::query()->where('app_role', User::APP_ROLE_ADMIN)->count(),
            'subscribers_count' => User::query()->where('app_role', User::APP_ROLE_SUBSCRIBER)->count(),
        ]);
    }

    public function updateRole(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate([
            'app_role' => ['required', Rule::in([User::APP_ROLE_ADMIN, User::APP_ROLE_SUBSCRIBER])],
        ]);

        if ($request->user()?->is($user)) {
            throw ValidationException::withMessages([
                'app_role' => 'Anda tidak bisa mengubah role platform akun sendiri.',
            ]);
        }

        if ($user->isAppAdmin() && $validated['app_role'] === User::APP_ROLE_SUBSCRIBER) {
            $adminCount = User::query()->where('app_role', User::APP_ROLE_ADMIN)->count();

            if ($adminCount <= 1) {
                throw ValidationException::withMessages([
                    'app_role' => 'Minimal harus ada satu admin aplikasi yang aktif.',
                ]);
            }
        }

        $user->forceFill(['app_role' => $validated['app_role']])->save();

        return to_route('admin.users.index');
    }
}
