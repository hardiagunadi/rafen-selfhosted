<?php

use App\Http\Controllers\WgSettingsController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:vpn', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/wireguard')
    ->name('super-admin.settings.wireguard.')
    ->group(function (): void {
        Route::get('/', [WgSettingsController::class, 'index'])->name('index');
        Route::post('/peers', [WgSettingsController::class, 'store'])->name('peers.store');
        Route::post('/sync', [WgSettingsController::class, 'sync'])->name('sync');
        Route::post('/peers/{wgPeer}/keygen', [WgSettingsController::class, 'keygen'])->name('peers.keygen');
        Route::delete('/peers/{wgPeer}', [WgSettingsController::class, 'destroy'])->name('peers.destroy');
    });
