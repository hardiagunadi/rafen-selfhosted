<?php

use App\Http\Controllers\PppUserController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/ppp-users')
    ->name('super-admin.settings.ppp-users.')
    ->group(function (): void {
        Route::get('/', [PppUserController::class, 'index'])->name('index');
        Route::get('/customer-id', [PppUserController::class, 'generateCustomerId'])->name('customer-id');
        Route::post('/', [PppUserController::class, 'store'])->name('store');
        Route::put('/{pppUser}', [PppUserController::class, 'update'])->name('update');
        Route::delete('/{pppUser}', [PppUserController::class, 'destroy'])->name('destroy');
    });
