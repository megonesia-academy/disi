<?php

use App\Http\Controllers\Admin\AdminActivityController;
use App\Http\Controllers\Admin\AdminApiConnectionController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminPricingController;
use App\Http\Controllers\Admin\AdminTemplateController;
use App\Http\Controllers\Admin\AdminTenantController;
use App\Http\Controllers\Admin\AdminTopupController;
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
use App\Http\Controllers\WorkspaceBillingController;
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

Route::middleware(['auth', 'verified', 'tenant.active'])->group(function () {
    Route::get('display/pair/{code}', [DisplayPairingController::class, 'show'])->name('display.pair.show');
    Route::post('display/pair/{code}', [DisplayPairingController::class, 'store'])->name('display.pair.store');
    Route::post('tenants/switch', TenantSwitchController::class)->name('tenants.switch');
    Route::get('workspace/settings', [WorkspaceSettingsController::class, 'edit'])->name('workspace.settings.edit');
    Route::put('workspace/settings', [WorkspaceSettingsController::class, 'update'])->name('workspace.settings.update');
    Route::post('workspace/settings', [WorkspaceSettingsController::class, 'update'])->name('workspace.settings.upload');
    Route::get('workspace/members', [WorkspaceMemberController::class, 'index'])->name('workspace.members.index');
    Route::put('workspace/members/{user}', [WorkspaceMemberController::class, 'update'])->name('workspace.members.update');
    Route::delete('workspace/members/{user}', [WorkspaceMemberController::class, 'destroy'])->name('workspace.members.destroy');
    Route::get('workspace/billing', [WorkspaceBillingController::class, 'index'])->name('workspace.billing.index');
    Route::post('workspace/billing/purchase', [WorkspaceBillingController::class, 'purchase'])->name('workspace.billing.purchase');
    Route::post('workspace/billing/topup', [WorkspaceBillingController::class, 'topup'])->name('workspace.billing.topup');
    Route::get('dashboard', DashboardController::class)->name('dashboard');
    Route::inertia('menu', 'Menu')->name('menu');
    Route::inertia('documentation', 'Documentation')->name('documentation');
    Route::get('screens/{screen}/preview', ScreenPreviewController::class)->name('screens.preview');
    Route::resource('screens', ScreenController::class)->except(['create', 'show', 'edit']);
    Route::resource('devices', DeviceController::class)->except(['create', 'edit']);
    Route::post('devices/{device}/commands', [DeviceCommandController::class, 'store'])->name('devices.commands.store');
    Route::resource('media-assets', MediaAssetController::class)->except(['create', 'show', 'edit']);
    Route::get('integrations/api', [ApiConnectionController::class, 'index'])->name('integrations.api.index');
    Route::post('integrations/api/{apiConnection}/test', [ApiConnectionController::class, 'test'])->name('integrations.api.test');
    Route::resource('templates', ContentTemplateController::class)->only(['index']);
    Route::post('templates/{template}/rent', [ContentTemplateController::class, 'rent'])->name('templates.rent');
    Route::resource('playlists', PlaylistController::class)->except(['show']);
    Route::post('playlists/api-test', [PlaylistController::class, 'testDataSource'])->name('playlists.api-test');
    Route::post('playlists/{playlist}/duplicate', [PlaylistController::class, 'duplicate'])->name('playlists.duplicate');
    Route::resource('schedules', ScheduleController::class)->except(['create', 'show', 'edit']);

    Route::middleware('app.admin')->prefix('admin')->name('admin.')->group(function (): void {
        Route::get('/', AdminDashboardController::class)->name('dashboard');
        Route::get('tenants', [AdminTenantController::class, 'index'])->name('tenants.index');
        Route::patch('tenants/{tenant}/status', [AdminTenantController::class, 'updateStatus'])->name('tenants.status');
        Route::post('tenants/{tenant}/tokens', [AdminTenantController::class, 'adjustTokens'])->name('tenants.tokens');
        Route::patch('tenants/{tenant}/users/{user}/role', [AdminTenantController::class, 'updateUserRole'])->name('tenants.users.role');
        Route::get('activity', AdminActivityController::class)->name('activity.index');
        Route::get('pricing', [AdminPricingController::class, 'index'])->name('pricing.index');
        Route::post('pricing', [AdminPricingController::class, 'store'])->name('pricing.store');
        Route::put('pricing/{featurePrice}', [AdminPricingController::class, 'update'])->name('pricing.update');
        Route::get('topups', [AdminTopupController::class, 'index'])->name('topups.index');
        Route::patch('topups/{topupRequest}/approve', [AdminTopupController::class, 'approve'])->name('topups.approve');
        Route::patch('topups/{topupRequest}/reject', [AdminTopupController::class, 'reject'])->name('topups.reject');
        Route::get('templates', [AdminTemplateController::class, 'index'])->name('templates.index');
        Route::post('templates', [AdminTemplateController::class, 'store'])->name('templates.store');
        Route::put('templates/{platformTemplate}', [AdminTemplateController::class, 'update'])->name('templates.update');
        Route::delete('templates/{platformTemplate}', [AdminTemplateController::class, 'destroy'])->name('templates.destroy');
        Route::post('templates/{platformTemplate}/duplicate', [AdminTemplateController::class, 'duplicate'])->name('templates.duplicate');
        Route::get('api-connections', [AdminApiConnectionController::class, 'index'])->name('api-connections.index');
        Route::post('api-connections', [AdminApiConnectionController::class, 'store'])->name('api-connections.store');
        Route::put('api-connections/{apiConnection}', [AdminApiConnectionController::class, 'update'])->name('api-connections.update');
        Route::delete('api-connections/{apiConnection}', [AdminApiConnectionController::class, 'destroy'])->name('api-connections.destroy');
        Route::post('api-connections/{apiConnection}/test', [AdminApiConnectionController::class, 'test'])->name('api-connections.test');
    });
});

require __DIR__.'/settings.php';
