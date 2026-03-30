<?php

use App\Http\Controllers\IncomeReportController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', SuperAdminMiddleware::class])
    ->prefix('super-admin/reports')
    ->name('super-admin.reports.')
    ->group(function (): void {
        Route::get('/income', IncomeReportController::class)->name('income');
        Route::post('/expenses', [IncomeReportController::class, 'storeExpense'])->name('expenses.store');
    });
