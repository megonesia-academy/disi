<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\ApiConnectionController;
use App\Http\Controllers\ContentTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DeviceCommandController;
use App\Http\Controllers\DeviceController;
use App\Http\Controllers\DisplayController;
use App\Http\Controllers\DisplayPairingController;
use App\Http\Controllers\MediaAssetController;
use App\Http\Controllers\PlaylistController;
use App\Http\Controllers\ScheduleController;
use App\Http\Controllers\ScreenController;
use App\Http\Controllers\ScreenPreviewController;
use App\Http\Controllers\TenantSwitchController;
use App\Http\Controllers\WorkspaceMemberController;
use App\Http\Controllers\WorkspaceSettingsController;
use App\Models\Device;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::inertia('/', 'Welcome')->name('home');

Route::get('display', [DisplayController::class, 'show'])->name('display.show');

Route::get('player/{device:key}', function (Request $request, Device $device) {
    abort_unless($device->hasValidPlayerSecret($request->query('token')), 404);

    return Inertia::render('player/Show', [
        'deviceKey' => $device->key,
        'manifestUrl' => route('api.player.manifest', [
            'device' => $device->key,
            'token' => $device->player_secret,
        ]),
        'heartbeatUrl' => route('api.player.heartbeat', [
            'device' => $device->key,
            'token' => $device->player_secret,
        ]),
    ]);
})->name('player.show');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('display/pair/{code}', [DisplayPairingController::class, 'show'])->name('display.pair.show');
    Route::post('display/pair/{code}', [DisplayPairingController::class, 'store'])->name('display.pair.store');
    Route::post('tenants/switch', TenantSwitchController::class)->name('tenants.switch');
    Route::get('activity', ActivityLogController::class)->name('activity.index');
    Route::get('workspace/settings', [WorkspaceSettingsController::class, 'edit'])->name('workspace.settings.edit');
    Route::put('workspace/settings', [WorkspaceSettingsController::class, 'update'])->name('workspace.settings.update');
    Route::post('workspace/settings', [WorkspaceSettingsController::class, 'update'])->name('workspace.settings.upload');
    Route::get('workspace/members', [WorkspaceMemberController::class, 'index'])->name('workspace.members.index');
    Route::put('workspace/members/{user}', [WorkspaceMemberController::class, 'update'])->name('workspace.members.update');
    Route::delete('workspace/members/{user}', [WorkspaceMemberController::class, 'destroy'])->name('workspace.members.destroy');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::inertia('documentation', 'Documentation')->name('documentation');
    Route::get('screens/{screen}/preview', ScreenPreviewController::class)->name('screens.preview');
    Route::resource('screens', ScreenController::class)->except(['create', 'show', 'edit']);
    Route::resource('devices', DeviceController::class)->except(['create', 'edit']);
    Route::post('devices/{device}/commands', [DeviceCommandController::class, 'store'])->name('devices.commands.store');
    Route::resource('media-assets', MediaAssetController::class)->except(['create', 'show', 'edit']);
    Route::get('integrations/api', [ApiConnectionController::class, 'index'])->name('integrations.api.index');
    Route::post('integrations/api', [ApiConnectionController::class, 'store'])->name('integrations.api.store');
    Route::put('integrations/api/{apiConnection}', [ApiConnectionController::class, 'update'])->name('integrations.api.update');
    Route::delete('integrations/api/{apiConnection}', [ApiConnectionController::class, 'destroy'])->name('integrations.api.destroy');
    Route::post('integrations/api/{apiConnection}/approve', [ApiConnectionController::class, 'approve'])->name('integrations.api.approve');
    Route::post('integrations/api/{apiConnection}/test', [ApiConnectionController::class, 'test'])->name('integrations.api.test');
    Route::resource('templates', ContentTemplateController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('playlists', PlaylistController::class)->except(['show']);
    Route::post('playlists/api-test', [PlaylistController::class, 'testDataSource'])->name('playlists.api-test');
    Route::post('playlists/{playlist}/duplicate', [PlaylistController::class, 'duplicate'])->name('playlists.duplicate');
    Route::resource('schedules', ScheduleController::class)->except(['create', 'show', 'edit']);
});

require __DIR__.'/settings.php';
