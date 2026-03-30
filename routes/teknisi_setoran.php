<?php

use App\Http\Controllers\TeknisiSetoranController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license'])
    ->prefix('teknisi-setoran')
    ->name('teknisi-setoran.')
    ->group(function (): void {
        Route::get('/', [TeknisiSetoranController::class, 'index'])->name('index');
        Route::post('/', [TeknisiSetoranController::class, 'store'])->name('store');
        Route::get('/{teknisiSetoran}', [TeknisiSetoranController::class, 'show'])->name('show');
        Route::post('/{teknisiSetoran}/submit', [TeknisiSetoranController::class, 'submit'])->name('submit');
        Route::post('/{teknisiSetoran}/verify', [TeknisiSetoranController::class, 'verify'])->name('verify');
    });
