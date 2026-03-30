<?php

use App\Http\Controllers\WaTicketController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:wa', SuperAdminMiddleware::class])
    ->prefix('super-admin/wa-tickets')
    ->name('super-admin.wa-tickets.')
    ->group(function (): void {
        Route::get('/', [WaTicketController::class, 'index'])->name('index');
        Route::post('/', [WaTicketController::class, 'store'])->name('store');
        Route::get('/{waTicket}', [WaTicketController::class, 'show'])->name('show');
        Route::put('/{waTicket}', [WaTicketController::class, 'update'])->name('update');
        Route::post('/{waTicket}/assign', [WaTicketController::class, 'assign'])->name('assign');
        Route::post('/{waTicket}/notes', [WaTicketController::class, 'addNote'])->name('notes.store');
    });
