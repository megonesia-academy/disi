<?php

namespace App\Http\Controllers;

use App\Models\Playlist;
use App\Models\Schedule;
use App\Models\Screen;
use App\Models\Tenant;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ActivityLogger $activityLogger,
    ) {}

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): Response
    {
        $tenant = $this->currentTenant->resolve($request);

        return Inertia::render('schedules/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'schedules' => Schedule::query()
                ->with(['screen', 'playlist'])
                ->where('tenant_id', $tenant->id)
                ->orderByDesc('priority')
                ->latest()
                ->get()
                ->map(fn (Schedule $schedule): array => [
                    'id' => $schedule->id,
                    'screen_id' => $schedule->screen_id,
                    'playlist_id' => $schedule->playlist_id,
                    'starts_at' => $schedule->starts_at?->format('Y-m-d\TH:i'),
                    'ends_at' => $schedule->ends_at?->format('Y-m-d\TH:i'),
                    'days_of_week' => $schedule->days_of_week,
                    'start_time' => $schedule->start_time,
                    'end_time' => $schedule->end_time,
                    'priority' => $schedule->priority,
                    'is_active' => $schedule->is_active,
                    'screen' => [
                        'id' => $schedule->screen->id,
                        'name' => $schedule->screen->name,
                        'location' => $schedule->screen->location,
                    ],
                    'playlist' => [
                        'id' => $schedule->playlist->id,
                        'name' => $schedule->playlist->name,
                    ],
                ]),
            'screens' => Screen::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->where('status', Playlist::STATUS_PUBLISHED)
                ->orderBy('name')
                ->get(['id', 'name', 'location']),
            'playlists' => Playlist::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name']),
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $schedule = Schedule::query()->create([
            ...$this->validated($request, $tenant),
            'tenant_id' => $tenant->id,
        ]);

        $this->activityLogger->log($request, $tenant, 'schedule.created', $schedule, [
            'screen_id' => $schedule->screen_id,
            'playlist_id' => $schedule->playlist_id,
            'priority' => $schedule->priority,
            'is_active' => $schedule->is_active,
        ]);

        return to_route('schedules.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $schedule);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $schedule->update($this->validated($request, $tenant));

        $this->activityLogger->log($request, $tenant, 'schedule.updated', $schedule, [
            'screen_id' => $schedule->screen_id,
            'playlist_id' => $schedule->playlist_id,
            'priority' => $schedule->priority,
            'is_active' => $schedule->is_active,
        ]);

        return to_route('schedules.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Schedule $schedule): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $schedule);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $properties = [
            'screen_id' => $schedule->screen_id,
            'playlist_id' => $schedule->playlist_id,
            'priority' => $schedule->priority,
            'is_active' => $schedule->is_active,
        ];

        $this->activityLogger->log($request, $tenant, 'schedule.deleted', $schedule, $properties);

        $schedule->delete();

        return to_route('schedules.index');
    }

    /**
     * @return array{screen_id: string, playlist_id: string, starts_at: string|null, ends_at: string|null, days_of_week: array<int, int>|null, start_time: string|null, end_time: string|null, priority: int, is_active: bool}
     */
    private function validated(Request $request, Tenant $tenant): array
    {
        return $request->validate([
            'screen_id' => [
                'required',
                Rule::exists('screens', 'id')->where('tenant_id', $tenant->id),
            ],
            'playlist_id' => [
                'required',
                Rule::exists('playlists', 'id')->where('tenant_id', $tenant->id),
            ],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
            'days_of_week' => ['nullable', 'array'],
            'days_of_week.*' => ['integer', 'between:1,7'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
            'priority' => ['required', 'integer', 'min:0', 'max:65535'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
