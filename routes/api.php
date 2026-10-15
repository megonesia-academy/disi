<?php

use App\Http\Controllers\Api\DisplayPairingStatusController;
use App\Http\Controllers\Api\DisplayResumeController;
use App\Http\Controllers\Api\PlayerCommandAcknowledgementController;
use App\Http\Controllers\Api\PlayerHeartbeatController;
use App\Http\Controllers\Api\PlayerManifestController;
use Illuminate\Support\Facades\Route;

Route::get('display/{token}/status', DisplayPairingStatusController::class)
    ->name('api.display.status');

Route::post('display/resume', DisplayResumeController::class)
    ->name('api.display.resume');

Route::get('player/{device:key}/manifest', PlayerManifestController::class)
    ->name('api.player.manifest');

Route::post('player/{device:key}/heartbeat', PlayerHeartbeatController::class)
    ->name('api.player.heartbeat');

Route::post('player/{device:key}/commands/{command}/ack', PlayerCommandAcknowledgementController::class)
    ->name('api.player.commands.ack');
