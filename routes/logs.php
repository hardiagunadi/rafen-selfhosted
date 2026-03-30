<?php

use App\Http\Controllers\LogController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', SuperAdminMiddleware::class])
    ->prefix('super-admin/logs')
    ->name('super-admin.logs.')
    ->group(function (): void {
        Route::get('/activity', [LogController::class, 'activityIndex'])->name('activity');
    });
