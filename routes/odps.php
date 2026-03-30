<?php

use App\Http\Controllers\OdpController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/odps')
    ->name('super-admin.odps.')
    ->group(function (): void {
        Route::get('/', [OdpController::class, 'index'])->name('index');
        Route::get('/create', [OdpController::class, 'create'])->name('create');
        Route::get('/datatable', [OdpController::class, 'datatable'])->name('datatable');
        Route::get('/autocomplete', [OdpController::class, 'autocomplete'])->name('autocomplete');
        Route::get('/generate-code', [OdpController::class, 'generateCode'])->name('generate-code');
        Route::post('/', [OdpController::class, 'store'])->name('store');
        Route::get('/{odp}', [OdpController::class, 'show'])->name('show');
        Route::get('/{odp}/edit', [OdpController::class, 'edit'])->name('edit');
        Route::put('/{odp}', [OdpController::class, 'update'])->name('update');
        Route::delete('/{odp}', [OdpController::class, 'destroy'])->name('destroy');
    });
