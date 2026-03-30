<?php

use App\Http\Controllers\MetaWhatsAppWebhookController;
use App\Http\Controllers\WaWebhookController;
use Illuminate\Support\Facades\Route;

Route::get('/webhook/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'verify'])->name('meta.whatsapp.webhook.verify');
Route::post('/webhook/meta/whatsapp', [MetaWhatsAppWebhookController::class, 'receive'])->name('meta.whatsapp.webhook.receive');
Route::match(['GET', 'POST'], '/webhook/wa', [WaWebhookController::class, 'ingest'])->name('wa.webhook.ingest');
Route::match(['GET', 'POST'], '/webhook/wa/session', [WaWebhookController::class, 'session'])->name('wa.webhook.session');
Route::match(['GET', 'POST'], '/webhook/wa/message', [WaWebhookController::class, 'message'])->name('wa.webhook.message');
Route::match(['GET', 'POST'], '/webhook/wa/auto-reply', [WaWebhookController::class, 'autoReply'])->name('wa.webhook.auto-reply');
Route::match(['GET', 'POST'], '/webhook/wa/status', [WaWebhookController::class, 'status'])->name('wa.webhook.status');
Route::match(['GET', 'POST'], '/webhook', [WaWebhookController::class, 'ingest'])->name('wa.webhook.ingest.compat');
Route::match(['GET', 'POST'], '/webhook/session', [WaWebhookController::class, 'session'])->name('wa.webhook.session.compat');
Route::match(['GET', 'POST'], '/webhook/message', [WaWebhookController::class, 'message'])->name('wa.webhook.message.compat');
Route::match(['GET', 'POST'], '/webhook/auto-reply', [WaWebhookController::class, 'autoReply'])->name('wa.webhook.auto-reply.compat');
Route::match(['GET', 'POST'], '/webhook/status', [WaWebhookController::class, 'status'])->name('wa.webhook.status.compat');
