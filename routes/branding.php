<?php

use App\Http\Controllers\IsolirPageController;
use App\Http\Controllers\ManifestController;
use App\Http\Controllers\PushSubscriptionController;
use Illuminate\Support\Facades\Route;

Route::get('/manifest.json', [ManifestController::class, 'admin'])->name('manifest.admin');
Route::get('/pwa-icon/{size}', [ManifestController::class, 'icon'])->whereIn('size', ['32', '180', '192', '512'])->name('manifest.admin.icon');
Route::get('/push/vapid-public-key', [PushSubscriptionController::class, 'vapidKey'])->name('push.vapid-key');
Route::get('/isolir', [IsolirPageController::class, 'show'])->name('isolir.show');
Route::get('/isolir/{pppUser}', [IsolirPageController::class, 'show'])->name('isolir.customer')->whereNumber('pppUser');

Route::middleware('auth')->group(function (): void {
    Route::post('/push/subscribe', [PushSubscriptionController::class, 'store'])->name('push.subscribe');
    Route::delete('/push/unsubscribe', [PushSubscriptionController::class, 'destroy'])->name('push.unsubscribe');
});
