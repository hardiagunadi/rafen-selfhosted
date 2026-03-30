<?php

use App\Http\Controllers\BandwidthProfileController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/bandwidth-profiles')
    ->name('super-admin.settings.bandwidth-profiles.')
    ->group(function (): void {
        Route::get('/', [BandwidthProfileController::class, 'index'])->name('index');
        Route::post('/', [BandwidthProfileController::class, 'store'])->name('store');
        Route::put('/{bandwidthProfile}', [BandwidthProfileController::class, 'update'])->name('update');
        Route::delete('/{bandwidthProfile}', [BandwidthProfileController::class, 'destroy'])->name('destroy');
    });
