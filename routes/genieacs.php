<?php

use App\Http\Controllers\GenieAcsSettingsController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:genieacs', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/genieacs')
    ->name('super-admin.settings.genieacs.')
    ->group(function (): void {
        Route::get('/', [GenieAcsSettingsController::class, 'index'])->name('index');
        Route::post('/test-connection', [GenieAcsSettingsController::class, 'testConnection'])->name('test-connection');
        Route::post('/service/{action}', [GenieAcsSettingsController::class, 'service'])->name('service');
        Route::post('/devices/connection-request', [GenieAcsSettingsController::class, 'connectionRequest'])->name('devices.connection-request');
        Route::delete('/devices/tasks', [GenieAcsSettingsController::class, 'clearTasks'])->name('devices.clear-tasks');
    });
