<?php

use App\Http\Controllers\HotspotUserController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/hotspot-users')
    ->name('super-admin.settings.hotspot-users.')
    ->group(function (): void {
        Route::get('/', [HotspotUserController::class, 'index'])->name('index');
        Route::get('/create', [HotspotUserController::class, 'create'])->name('create');
        Route::get('/customer-id', [HotspotUserController::class, 'generateCustomerId'])->name('customer-id');
        Route::get('/datatable', [HotspotUserController::class, 'datatable'])->name('datatable');
        Route::get('/autocomplete', [HotspotUserController::class, 'autocomplete'])->name('autocomplete');
        Route::delete('/bulk-destroy', [HotspotUserController::class, 'bulkDestroy'])->name('bulk-destroy');
        Route::post('/', [HotspotUserController::class, 'store'])->name('store');
        Route::post('/{hotspotUser}/renew', [HotspotUserController::class, 'renew'])->name('renew');
        Route::post('/{hotspotUser}/toggle-status', [HotspotUserController::class, 'toggleStatus'])->name('toggle-status');
        Route::get('/{hotspotUser}', [HotspotUserController::class, 'show'])->name('show');
        Route::get('/{hotspotUser}/edit', [HotspotUserController::class, 'edit'])->name('edit');
        Route::put('/{hotspotUser}', [HotspotUserController::class, 'update'])->name('update');
        Route::delete('/{hotspotUser}', [HotspotUserController::class, 'destroy'])->name('destroy');
    });
