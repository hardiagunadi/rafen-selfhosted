<?php

use App\Http\Controllers\WaGatewaySettingsController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:wa', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/wa-gateway')
    ->name('super-admin.settings.wa-gateway.')
    ->group(function (): void {
        Route::get('/', [WaGatewaySettingsController::class, 'index'])->name('index');
        Route::put('/', [WaGatewaySettingsController::class, 'update'])->name('update');
        Route::post('/service/{action}', [WaGatewaySettingsController::class, 'serviceControl'])->name('service');
        Route::post('/test-connection', [WaGatewaySettingsController::class, 'testConnection'])->name('test-connection');
        Route::post('/test-message', [WaGatewaySettingsController::class, 'sendTestMessage'])->name('test-message');
        Route::post('/devices', [WaGatewaySettingsController::class, 'storeDevice'])->name('devices.store');
        Route::post('/devices/{device}/default', [WaGatewaySettingsController::class, 'setDefaultDevice'])->name('devices.default');
        Route::post('/devices/{device}/session/{action}', [WaGatewaySettingsController::class, 'sessionControl'])->name('devices.session');
        Route::delete('/devices/{device}', [WaGatewaySettingsController::class, 'destroyDevice'])->name('devices.destroy');
    });
