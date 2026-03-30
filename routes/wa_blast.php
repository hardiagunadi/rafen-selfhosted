<?php

use App\Http\Controllers\WaBlastController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:wa', SuperAdminMiddleware::class])
    ->prefix('super-admin/wa-blast')
    ->name('super-admin.wa-blast.')
    ->group(function (): void {
        Route::get('/', [WaBlastController::class, 'index'])->name('index');
        Route::get('/preview', [WaBlastController::class, 'preview'])->name('preview');
        Route::get('/customer-options', [WaBlastController::class, 'customerOptions'])->name('customer-options');
        Route::post('/send', [WaBlastController::class, 'send'])->name('send');
    });
