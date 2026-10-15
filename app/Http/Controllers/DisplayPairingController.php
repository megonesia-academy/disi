<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DisplayPairingSession;
use App\Models\Screen;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class DisplayPairingController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function show(Request $request, string $code): Response
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $pairingSession = $this->activePairingSession($code);

        return Inertia::render('display/Pair', [
            'pairing' => [
                'code' => $pairingSession->code,
                'expires_at' => $pairingSession->expires_at->toISOString(),
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
            ],
            'screens' => Screen::query()
                ->where('tenant_id', $tenant->id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get(['id', 'name', 'location']),
        ]);
    }

    public function store(Request $request, string $code): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $pairingSession = $this->activePairingSession($code);

        $validated = $request->validate([
            'screen_id' => [
                'required',
                Rule::exists('screens', 'id')->where('tenant_id', $tenant->id),
            ],
            'name' => ['nullable', 'string', 'max:255'],
        ]);

        $screen = Screen::query()
            ->where('tenant_id', $tenant->id)
            ->findOrFail($validated['screen_id']);

        $device = Device::query()->create([
            'tenant_id' => $tenant->id,
            'screen_id' => $screen->id,
            'name' => ($validated['name'] ?? null) ?: "Display {$screen->name}",
            'key' => Str::slug($screen->name).'-'.Str::lower(Str::random(6)),
            'player_secret' => Str::random(48),
            'status' => Device::STATUS_OFFLINE,
            'is_active' => true,
        ]);

        $pairingSession->forceFill([
            'device_id' => $device->id,
            'paired_at' => now(),
        ])->save();

        $this->activityLogger->log($request, $tenant, 'display.paired', $device, [
            'device_name' => $device->name,
            'device_key' => $device->key,
            'screen_id' => $screen->id,
            'screen_name' => $screen->name,
            'pairing_code' => $pairingSession->code,
        ]);

        return to_route('devices.index');
    }

    private function activePairingSession(string $code): DisplayPairingSession
    {
        $pairingSession = DisplayPairingSession::query()
            ->where('code', Str::upper($code))
            ->firstOrFail();

        abort_if($pairingSession->isExpired() || $pairingSession->isPaired(), 404);

        return $pairingSession;
    }
}
