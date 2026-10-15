<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class TenantSwitchController extends Controller
{
    /**
     * Handle the incoming request.
     */
    public function __invoke(Request $request, CurrentTenant $currentTenant): RedirectResponse
    {
        $validated = $request->validate([
            'tenant_id' => ['required', 'string'],
        ]);

        $tenant = Tenant::query()->findOrFail($validated['tenant_id']);

        $currentTenant->switch($request, $tenant);

        return back();
    }
}
