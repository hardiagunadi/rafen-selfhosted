<?php

use App\Http\Controllers\MikrotikConnectionController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/mikrotik')
    ->name('super-admin.settings.mikrotik.')
    ->group(function (): void {
        Route::get('/', [MikrotikConnectionController::class, 'index'])->name('index');
        Route::post('/', [MikrotikConnectionController::class, 'store'])->name('store');
        Route::put('/{mikrotikConnection}', [MikrotikConnectionController::class, 'update'])->name('update');
        Route::delete('/{mikrotikConnection}', [MikrotikConnectionController::class, 'destroy'])->name('destroy');
        Route::post('/test', [MikrotikConnectionController::class, 'test'])->name('test');
        Route::post('/{mikrotikConnection}/ping', [MikrotikConnectionController::class, 'pingNow'])->name('ping');
    });
