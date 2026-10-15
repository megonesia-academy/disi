<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Support\CurrentTenant;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ActivityLogController extends Controller
{
    public function __construct(private readonly CurrentTenant $currentTenant) {}

    public function __invoke(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        return Inertia::render('activity/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'activities' => ActivityLog::query()
                ->with('user:id,name,email')
                ->where('tenant_id', $tenant->id)
                ->latest()
                ->limit(100)
                ->get()
                ->map(fn (ActivityLog $activity): array => [
                    'id' => $activity->id,
                    'event' => $activity->event,
                    'subject_type' => $activity->subject_type,
                    'subject_id' => $activity->subject_id,
                    'properties' => $activity->properties ?? [],
                    'ip_address' => $activity->ip_address,
                    'created_at' => $activity->created_at?->diffForHumans(),
                    'created_at_iso' => $activity->created_at?->toISOString(),
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                        'email' => $activity->user->email,
                    ] : null,
                ]),
        ]);
    }
}
