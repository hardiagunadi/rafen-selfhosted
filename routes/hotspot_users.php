<?php

use App\Http\Controllers\HotspotUserController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/hotspot-users')
    ->name('super-admin.settings.hotspot-users.')
    ->group(function (): void {
        Route::get('/', [HotspotUserController::class, 'index'])->name('index');
        Route::get('/customer-id', [HotspotUserController::class, 'generateCustomerId'])->name('customer-id');
        Route::post('/', [HotspotUserController::class, 'store'])->name('store');
        Route::put('/{hotspotUser}', [HotspotUserController::class, 'update'])->name('update');
        Route::delete('/{hotspotUser}', [HotspotUserController::class, 'destroy'])->name('destroy');
    });
