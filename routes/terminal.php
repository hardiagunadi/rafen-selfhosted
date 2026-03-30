<?php

use App\Http\Controllers\SuperAdminTerminalController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', SuperAdminMiddleware::class])
    ->prefix('super-admin/terminal')
    ->name('super-admin.terminal.')
    ->group(function (): void {
        Route::get('/', [SuperAdminTerminalController::class, 'index'])->name('index');
        Route::post('/run', [SuperAdminTerminalController::class, 'run'])->name('run');
    });
