<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/super-admin/settings/license');

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

require __DIR__.'/self_hosted_license.php';
require __DIR__.'/mikrotik.php';
require __DIR__.'/radius_accounts.php';
require __DIR__.'/freeradius.php';
require __DIR__.'/genieacs.php';
require __DIR__.'/olt.php';
require __DIR__.'/wireguard.php';
require __DIR__.'/wa_gateway.php';
