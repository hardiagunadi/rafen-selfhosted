<?php

use App\Http\Controllers\WaKeywordRuleController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:wa', SuperAdminMiddleware::class])
    ->prefix('super-admin/wa-keyword-rules')
    ->name('super-admin.wa-keyword-rules.')
    ->group(function (): void {
        Route::get('/', [WaKeywordRuleController::class, 'index'])->name('index');
        Route::post('/', [WaKeywordRuleController::class, 'store'])->name('store');
        Route::put('/{waKeywordRule}', [WaKeywordRuleController::class, 'update'])->name('update');
        Route::delete('/{waKeywordRule}', [WaKeywordRuleController::class, 'destroy'])->name('destroy');
    });
