<?php

use App\Http\Controllers\FreeRadiusSettingsController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/freeradius')
    ->name('super-admin.settings.freeradius.')
    ->group(function (): void {
        Route::get('/', [FreeRadiusSettingsController::class, 'index'])->name('index');
        Route::post('/sync', [FreeRadiusSettingsController::class, 'sync'])->name('sync');
        Route::post('/sync-replies', [FreeRadiusSettingsController::class, 'syncReplies'])->name('sync-replies');
        Route::post('/service/{action}', [FreeRadiusSettingsController::class, 'service'])->name('service');
        Route::post('/nas', [FreeRadiusSettingsController::class, 'store'])->name('nas.store');
        Route::put('/nas/{radiusNas}', [FreeRadiusSettingsController::class, 'update'])->name('nas.update');
        Route::delete('/nas/{radiusNas}', [FreeRadiusSettingsController::class, 'destroy'])->name('nas.destroy');
    });
