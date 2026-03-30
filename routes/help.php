<?php

use App\Http\Controllers\HelpController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', SuperAdminMiddleware::class])
    ->prefix('super-admin/help')
    ->name('super-admin.help.')
    ->group(function (): void {
        Route::get('/', [HelpController::class, 'index'])->name('index');
        Route::get('/{slug}', [HelpController::class, 'topic'])->name('topic');
    });
