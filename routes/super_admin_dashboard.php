<?php

use App\Http\Controllers\SuperAdminDashboardController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', SuperAdminMiddleware::class])
    ->prefix('super-admin')
    ->name('super-admin.')
    ->group(function (): void {
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');
    });
