<?php

use App\Http\Controllers\ManifestController;
use App\Http\Controllers\Portal\PortalAuthController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('portal')->name('portal.')->group(function (): void {
    Route::get('/manifest.json', [ManifestController::class, 'portal'])->name('manifest');
    Route::get('/icon/{size}', [ManifestController::class, 'portalIcon'])->whereIn('size', ['32', '180', '192', '512'])->name('icon');
    Route::get('/login', [PortalAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [PortalAuthController::class, 'login'])->name('login.post');
    Route::post('/logout', [PortalAuthController::class, 'logout'])->name('logout');

    Route::middleware('portal.auth')->group(function (): void {
        Route::get('/', [PortalDashboardController::class, 'index'])->name('dashboard');
        Route::get('/invoices', [PortalDashboardController::class, 'invoices'])->name('invoices');
        Route::get('/account', [PortalDashboardController::class, 'account'])->name('account');
        Route::post('/change-password', [PortalDashboardController::class, 'changePassword'])->name('change-password');
        Route::get('/traffic', [PortalDashboardController::class, 'getTraffic'])->name('traffic');
        Route::post('/wifi', [PortalDashboardController::class, 'updateWifi'])->name('wifi.update');
        Route::post('/tickets', [PortalDashboardController::class, 'storeTicket'])->name('tickets.store');
        Route::post('/push/subscribe', [PushSubscriptionController::class, 'portalStore'])->name('push.subscribe');
        Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'portalDestroy'])->name('push.unsubscribe');
    });
});
