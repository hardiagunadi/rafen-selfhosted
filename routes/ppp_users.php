<?php

use App\Http\Controllers\PppUserController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/ppp-users')
    ->name('super-admin.settings.ppp-users.')
    ->group(function (): void {
        Route::get('/', [PppUserController::class, 'index'])->name('index');
        Route::get('/create', [PppUserController::class, 'create'])->name('create');
        Route::get('/customer-id', [PppUserController::class, 'generateCustomerId'])->name('customer-id');
        Route::get('/datatable', [PppUserController::class, 'datatable'])->name('datatable');
        Route::get('/autocomplete', [PppUserController::class, 'autocomplete'])->name('autocomplete');
        Route::delete('/bulk-destroy', [PppUserController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/', [PppUserController::class, 'store'])->name('store');
        Route::post('/{pppUser}/add-invoice', [PppUserController::class, 'addInvoice'])->name('add-invoice');
        Route::post('/{pppUser}/toggle-status', [PppUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{pppUser}', [PppUserController::class, 'show'])->name('show');
        Route::get('/{pppUser}/edit', [PppUserController::class, 'edit'])->name('edit');
        Route::put('/{pppUser}', [PppUserController::class, 'update'])->name('update');
        Route::delete('/{pppUser}', [PppUserController::class, 'destroy'])->name('destroy');
    });
