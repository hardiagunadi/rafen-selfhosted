<?php

use App\Http\Controllers\HotspotProfileController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/hotspot-profiles')
    ->name('super-admin.settings.hotspot-profiles.')
    ->group(function (): void {
        Route::get('/', [HotspotProfileController::class, 'index'])->name('index');
        Route::post('/', [HotspotProfileController::class, 'store'])->name('store');
        Route::put('/{hotspotProfile}', [HotspotProfileController::class, 'update'])->name('update');
        Route::delete('/{hotspotProfile}', [HotspotProfileController::class, 'destroy'])->name('destroy');
    });
