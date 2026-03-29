<?php

use App\Http\Controllers\RadiusAccountController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/radius-accounts')
    ->name('super-admin.settings.radius-accounts.')
    ->group(function (): void {
        Route::get('/', [RadiusAccountController::class, 'index'])->name('index');
        Route::post('/', [RadiusAccountController::class, 'store'])->name('store');
        Route::put('/{radiusAccount}', [RadiusAccountController::class, 'update'])->name('update');
        Route::delete('/{radiusAccount}', [RadiusAccountController::class, 'destroy'])->name('destroy');
    });
