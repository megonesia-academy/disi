<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ActivityLog;
use App\Models\Tenant;
use Inertia\Inertia;
use Inertia\Response;

class AdminActivityController extends Controller
{
    public function __invoke(): Response
    {
        return Inertia::render('admin/Activity', [
            'activities' => ActivityLog::query()
                ->with(['user:id,name,email', 'tenant:id,name,slug'])
                ->latest()
                ->limit(200)
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
                    'tenant' => $activity->tenant ? [
                        'id' => $activity->tenant->id,
                        'name' => $activity->tenant->name,
                        'slug' => $activity->tenant->slug,
                    ] : null,
                    'user' => $activity->user ? [
                        'id' => $activity->user->id,
                        'name' => $activity->user->name,
                        'email' => $activity->user->email,
                    ] : null,
                ]),
            'tenants' => Tenant::query()
                ->orderBy('name')
                ->get(['id', 'name']),
            'events' => ActivityLog::query()
                ->distinct()
                ->orderBy('event')
                ->pluck('event'),
        ]);
    }
}
