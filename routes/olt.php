<?php

use App\Http\Controllers\OltSettingsController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:olt', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/olt')
    ->name('super-admin.settings.olt.')
    ->group(function (): void {
        Route::get('/', [OltSettingsController::class, 'index'])->name('index');
        Route::post('/connections', [OltSettingsController::class, 'store'])->name('store');
        Route::put('/connections/{oltConnection}', [OltSettingsController::class, 'update'])->name('update');
        Route::delete('/connections/{oltConnection}', [OltSettingsController::class, 'destroy'])->name('destroy');
        Route::post('/connections/{oltConnection}/detect-model', [OltSettingsController::class, 'autoDetectModel'])->name('detect-model');
        Route::post('/connections/{oltConnection}/detect-oid', [OltSettingsController::class, 'autoDetectOid'])->name('detect-oid');
        Route::post('/connections/{oltConnection}/poll', [OltSettingsController::class, 'poll'])->name('poll');
        Route::post('/connections/{oltConnection}/onu/reboot', [OltSettingsController::class, 'rebootOnu'])->name('onu-reboot');
    });
