<?php

use App\Http\Controllers\VoucherController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/vouchers')
    ->name('super-admin.vouchers.')
    ->group(function (): void {
        Route::get('/', [VoucherController::class, 'index'])->name('index');
        Route::post('/', [VoucherController::class, 'store'])->name('store');
        Route::get('/{batch}/print', [VoucherController::class, 'printBatch'])->name('print');
        Route::delete('/bulk-destroy', [VoucherController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::delete('/{voucher}', [VoucherController::class, 'destroy'])->name('destroy');
    });
