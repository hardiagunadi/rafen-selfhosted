<?php

use App\Http\Controllers\CpeController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:genieacs', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/cpe')
    ->name('super-admin.settings.cpe.')
    ->group(function (): void {
        Route::get('/', [CpeController::class, 'index'])->name('index');
        Route::post('/sync', [CpeController::class, 'sync'])->name('sync');
        Route::post('/link', [CpeController::class, 'link'])->name('link');
        Route::post('/{cpeDevice}/reboot', [CpeController::class, 'reboot'])->name('reboot');
        Route::delete('/{cpeDevice}', [CpeController::class, 'destroy'])->name('destroy');
    });
