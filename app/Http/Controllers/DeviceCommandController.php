<?php

namespace App\Http\Controllers;

use App\Models\Device;
use App\Models\DeviceCommand;
use App\Support\ActivityLogger;
use App\Support\CurrentTenant;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class DeviceCommandController extends Controller
{
    public function __construct(
        private readonly CurrentTenant $currentTenant,
        private readonly ActivityLogger $activityLogger,
    ) {}

    public function store(Request $request, Device $device): RedirectResponse
    {
        $tenant = $this->currentTenant->resolve($request);
        $this->currentTenant->authorizeModel($tenant, $device);
        $this->currentTenant->authorizeCanManage($request, $tenant);

        $validated = $request->validate([
            'type' => ['required', Rule::in([
                DeviceCommand::TYPE_RELOAD,
                DeviceCommand::TYPE_REFRESH_MANIFEST,
                DeviceCommand::TYPE_IDENTIFY,
                DeviceCommand::TYPE_CLEAR_CACHE,
            ])],
        ]);

        DeviceCommand::query()->create([
            'tenant_id' => $tenant->id,
            'device_id' => $device->id,
            'type' => $validated['type'],
            'payload' => $this->payloadFor($validated['type']),
            'status' => DeviceCommand::STATUS_QUEUED,
        ]);

        $this->activityLogger->log($request, $tenant, 'device.command_queued', $device, [
            'device_name' => $device->name,
            'device_key' => $device->key,
            'command_type' => $validated['type'],
        ]);

        return to_route('devices.show', $device);
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadFor(string $type): array
    {
        return match ($type) {
            DeviceCommand::TYPE_IDENTIFY => ['duration_seconds' => 10],
            default => [],
        };
    }
}
