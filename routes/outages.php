<?php

use App\Http\Controllers\OutageController;
use App\Http\Controllers\OutageStatusController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', SuperAdminMiddleware::class])
    ->prefix('super-admin/outages')
    ->name('super-admin.outages.')
    ->group(function (): void {
        Route::get('/', [OutageController::class, 'index'])->name('index');
        Route::post('/', [OutageController::class, 'store'])->name('store');
        Route::get('/{outage}', [OutageController::class, 'show'])->name('show');
        Route::put('/{outage}', [OutageController::class, 'update'])->name('update');
        Route::post('/{outage}/updates', [OutageController::class, 'addUpdate'])->name('updates.store');
        Route::post('/{outage}/resolve', [OutageController::class, 'resolve'])->name('resolve');
        Route::delete('/{outage}', [OutageController::class, 'destroy'])->name('destroy');
    });

Route::get('/status/{token}', [OutageStatusController::class, 'show'])->name('outage.public-status');
