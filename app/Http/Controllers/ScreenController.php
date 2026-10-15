<?php

namespace App\Http\Controllers;

use App\Models\Screen;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ScreenController extends Controller
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

        return Inertia::render('screens/Index', [
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'screens' => Screen::query()
                ->withCount('devices')
                ->where('tenant_id', $tenant->id)
                ->orderBy('name')
                ->get()
                ->map(fn (Screen $screen): array => [
                    'id' => $screen->id,
                    'name' => $screen->name,
                    'location' => $screen->location,
                    'orientation' => $screen->orientation,
                    'resolution_width' => $screen->resolution_width,
                    'resolution_height' => $screen->resolution_height,
                    'is_active' => $screen->is_active,
                    'devices_count' => $screen->devices_count,
                ]),
            'orientations' => [
                Screen::ORIENTATION_LANDSCAPE,
                Screen::ORIENTATION_PORTRAIT,
            ],
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $screen = Screen::query()->create([
            ...$this->validated($request),
            'tenant_id' => $tenant->id,
        ]);

        $this->activityLogger->log($request, $tenant, 'screen.created', $screen, [
            'screen_name' => $screen->name,
            'location' => $screen->location,
            'orientation' => $screen->orientation,
        ]);

        return to_route('screens.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Screen $screen): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $screen);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $screen->update($this->validated($request));

        $this->activityLogger->log($request, $tenant, 'screen.updated', $screen, [
            'screen_name' => $screen->name,
            'location' => $screen->location,
            'orientation' => $screen->orientation,
        ]);

        return to_route('screens.index');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, Screen $screen): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $screen);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $properties = [
            'screen_name' => $screen->name,
            'location' => $screen->location,
            'orientation' => $screen->orientation,
        ];

        $this->activityLogger->log($request, $tenant, 'screen.deleted', $screen, $properties);

        $screen->delete();

        return to_route('screens.index');
    }

    /**
     * @return array{name: string, location: string|null, orientation: string, resolution_width: int, resolution_height: int, is_active: bool}
     */
    private function validated(Request $request): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'location' => ['nullable', 'string', 'max:255'],
            'orientation' => ['required', Rule::in([Screen::ORIENTATION_LANDSCAPE, Screen::ORIENTATION_PORTRAIT])],
            'resolution_width' => ['required', 'integer', 'min:1', 'max:10000'],
            'resolution_height' => ['required', 'integer', 'min:1', 'max:10000'],
            'is_active' => ['required', 'boolean'],
        ]);
    }
}
