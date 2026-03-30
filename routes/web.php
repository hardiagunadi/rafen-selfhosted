<?php

use App\Http\Controllers\Auth\LoginController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    if (! auth()->check()) {
        return redirect()->route('login');
    }

    return auth()->user()?->isSuperAdmin()
        ? redirect()->route('super-admin.dashboard')
        : redirect()->route('shifts.my');
});

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [LoginController::class, 'show'])->name('login');
    Route::post('/login', [LoginController::class, 'login'])->name('login.attempt');
});

Route::middleware('auth')->group(function (): void {
    Route::post('/logout', [LoginController::class, 'logout'])->name('logout');
});

require __DIR__.'/portal.php';
require __DIR__.'/branding.php';
require __DIR__.'/super_admin_dashboard.php';
require __DIR__.'/self_hosted_license.php';
require __DIR__.'/users.php';
require __DIR__.'/system_settings.php';
require __DIR__.'/shifts.php';
require __DIR__.'/help.php';
require __DIR__.'/public_tickets.php';
require __DIR__.'/mikrotik.php';
require __DIR__.'/bandwidth_profiles.php';
require __DIR__.'/profile_groups.php';
require __DIR__.'/ppp_profiles.php';
require __DIR__.'/ppp_users.php';
require __DIR__.'/odps.php';
require __DIR__.'/customer_map.php';
require __DIR__.'/hotspot_profiles.php';
require __DIR__.'/hotspot_users.php';
require __DIR__.'/vouchers.php';
require __DIR__.'/invoices.php';
require __DIR__.'/payments.php';
require __DIR__.'/teknisi_setoran.php';
require __DIR__.'/finance_reports.php';
require __DIR__.'/outages.php';
require __DIR__.'/logs.php';
require __DIR__.'/system_tools.php';
require __DIR__.'/terminal.php';
require __DIR__.'/radius_accounts.php';
require __DIR__.'/active_sessions.php';
require __DIR__.'/freeradius.php';
require __DIR__.'/genieacs.php';
require __DIR__.'/cpe.php';
require __DIR__.'/olt.php';
require __DIR__.'/wireguard.php';
require __DIR__.'/wa_gateway.php';
require __DIR__.'/wa_blast.php';
require __DIR__.'/wa_chat.php';
require __DIR__.'/wa_keyword_rules.php';
require __DIR__.'/wa_tickets.php';
require __DIR__.'/wa_webhooks.php';
