<?php

use App\Http\Controllers\InvoiceController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/invoices')
    ->name('super-admin.invoices.')
    ->group(function (): void {
        Route::get('/', [InvoiceController::class, 'index'])->name('index');
        Route::get('/unpaid', [InvoiceController::class, 'unpaidIndex'])->name('unpaid');
        Route::post('/', [InvoiceController::class, 'store'])->name('store');
        Route::get('/{invoice}', [InvoiceController::class, 'show'])->name('show');
        Route::post('/{invoice}/pay', [InvoiceController::class, 'pay'])->name('pay');
        Route::post('/{invoice}/renew', [InvoiceController::class, 'renew'])->name('renew');
        Route::delete('/{invoice}', [InvoiceController::class, 'destroy'])->name('destroy');
    });
