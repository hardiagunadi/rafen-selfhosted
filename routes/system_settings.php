<?php

use App\Http\Controllers\IsolirPageController;
use App\Http\Controllers\SystemSettingsController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/system')
    ->name('super-admin.settings.system.')
    ->group(function (): void {
        Route::get('/', [SystemSettingsController::class, 'index'])->name('index');
        Route::put('/business', [SystemSettingsController::class, 'updateBusiness'])->name('update-business');
        Route::put('/isolir', [SystemSettingsController::class, 'updateIsolir'])->name('update-isolir');
        Route::put('/update-notice', [SystemSettingsController::class, 'updateNotice'])->name('update-notice');
        Route::post('/logo', [SystemSettingsController::class, 'uploadLogo'])->name('upload-logo');
        Route::get('/isolir-preview', [IsolirPageController::class, 'preview'])->name('isolir-preview');
    });
