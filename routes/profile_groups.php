<?php

use App\Http\Controllers\ProfileGroupController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:radius', SuperAdminMiddleware::class])
    ->prefix('super-admin/settings/profile-groups')
    ->name('super-admin.settings.profile-groups.')
    ->group(function (): void {
        Route::get('/', [ProfileGroupController::class, 'index'])->name('index');
        Route::post('/', [ProfileGroupController::class, 'store'])->name('store');
        Route::put('/{profileGroup}', [ProfileGroupController::class, 'update'])->name('update');
        Route::delete('/{profileGroup}', [ProfileGroupController::class, 'destroy'])->name('destroy');
    });
