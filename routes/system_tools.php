<?php

use App\Http\Controllers\SystemToolController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', SuperAdminMiddleware::class])
    ->prefix('super-admin/tools')
    ->name('super-admin.tools.')
    ->group(function (): void {
        Route::get('/backup', [SystemToolController::class, 'backupIndex'])->name('backup');
        Route::post('/backup/create', [SystemToolController::class, 'backupCreate'])->name('backup.create');
        Route::get('/backup/download', [SystemToolController::class, 'backupDownload'])->name('backup.download');
        Route::post('/backup/restore', [SystemToolController::class, 'backupRestore'])->name('backup.restore');
        Route::delete('/backup/delete', [SystemToolController::class, 'backupDelete'])->name('backup.delete');
        Route::get('/export-transactions', [SystemToolController::class, 'exportTransactionsIndex'])->name('export-transactions');
        Route::get('/export-transactions/download', [SystemToolController::class, 'exportTransactionsDownload'])->name('export-transactions.download');
    });
