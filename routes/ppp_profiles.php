<?php

use App\Http\Controllers\PppProfileController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/ppp-profiles')
    ->name('super-admin.settings.ppp-profiles.')
    ->group(function (): void {
        Route::get('/', [PppProfileController::class, 'index'])->name('index');
        Route::post('/', [PppProfileController::class, 'store'])->name('store');
        Route::put('/{pppProfile}', [PppProfileController::class, 'update'])->name('update');
        Route::delete('/{pppProfile}', [PppProfileController::class, 'destroy'])->name('destroy');
    });
