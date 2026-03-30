<?php

use App\Http\Controllers\CustomerMapController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/customer-map')
    ->name('super-admin.customer-map.')
    ->group(function (): void {
        Route::get('/', [CustomerMapController::class, 'index'])->name('index');
    });
