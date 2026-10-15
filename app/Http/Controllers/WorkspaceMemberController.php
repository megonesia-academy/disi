<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class WorkspaceMemberController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        return Inertia::render('workspace/Members', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'members' => $tenant->users()
                ->orderBy('name')
                ->get()
                ->map(fn (User $user): array => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->pivot->role,
                    'accepted_at' => $user->pivot->accepted_at,
                    'is_current_user' => $request->user()?->is($user) ?? false,
                ]),
            'roles' => [
                CurrentTenant::ROLE_OWNER,
                CurrentTenant::ROLE_ADMIN,
                CurrentTenant::ROLE_VIEWER,
            ],
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        abort_unless($tenant->users()->whereKey($user->id)->exists(), 404);

        $validated = $request->validate([
            'role' => ['required', Rule::in([
                CurrentTenant::ROLE_OWNER,
                CurrentTenant::ROLE_ADMIN,
                CurrentTenant::ROLE_VIEWER,
            ])],
        ]);

        $previousRole = $tenant->users()->whereKey($user->id)->firstOrFail()->pivot->role;

        $tenant->users()->updateExistingPivot($user->id, [
            'role' => $validated['role'],
        ]);

        $this->activityLogger->log($request, $tenant, 'member.role_updated', $tenant, [
            'member_id' => $user->id,
            'member_name' => $user->name,
            'member_email' => $user->email,
            'previous_role' => $previousRole,
            'role' => $validated['role'],
        ]);

        return to_route('workspace.members.index');
    }

    public function destroy(Request $request, User $user): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        abort_unless($tenant->users()->whereKey($user->id)->exists(), 404);
        abort_if($request->user()?->is($user), 422);

        $membership = $tenant->users()->whereKey($user->id)->firstOrFail()->pivot;

        $this->activityLogger->log($request, $tenant, 'member.removed', $tenant, [
            'member_id' => $user->id,
            'member_name' => $user->name,
            'member_email' => $user->email,
            'role' => $membership->role,
        ]);

        $tenant->users()->detach($user->id);

        return to_route('workspace.members.index');
    }
}
