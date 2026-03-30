<?php

use App\Http\Controllers\ActiveSessionController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/sessions')
    ->name('super-admin.sessions.')
    ->group(function (): void {
        Route::get('/pppoe', [ActiveSessionController::class, 'pppoe'])->name('pppoe');
        Route::get('/pppoe/datatable', [ActiveSessionController::class, 'pppoeDatatable'])->name('pppoe.datatable');
        Route::get('/pppoe-inactive', [ActiveSessionController::class, 'pppoeInactive'])->name('pppoe-inactive');
        Route::get('/pppoe-inactive/datatable', [ActiveSessionController::class, 'pppoeInactiveDatatable'])->name('pppoe-inactive.datatable');
        Route::get('/hotspot', [ActiveSessionController::class, 'hotspot'])->name('hotspot');
        Route::get('/hotspot/datatable', [ActiveSessionController::class, 'hotspotDatatable'])->name('hotspot.datatable');
        Route::get('/hotspot-inactive', [ActiveSessionController::class, 'hotspotInactive'])->name('hotspot-inactive');
        Route::get('/hotspot-inactive/datatable', [ActiveSessionController::class, 'hotspotInactiveDatatable'])->name('hotspot-inactive.datatable');
    });
