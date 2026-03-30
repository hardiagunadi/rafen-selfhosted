<?php

use App\Http\Controllers\WaChatController;
use App\Http\Middleware\SuperAdminMiddleware;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license', 'system.feature:wa', SuperAdminMiddleware::class])
    ->prefix('super-admin/wa-chat')
    ->name('super-admin.wa-chat.')
    ->group(function (): void {
        Route::get('/', [WaChatController::class, 'index'])->name('index');
        Route::get('/conversations', [WaChatController::class, 'conversations'])->name('conversations');
        Route::get('/conversations/{waConversation}', [WaChatController::class, 'show'])->name('show');
        Route::post('/conversations/{waConversation}/reply', [WaChatController::class, 'reply'])->name('reply');
        Route::post('/conversations/{waConversation}/resolve', [WaChatController::class, 'markResolved'])->name('resolve');
        Route::post('/conversations/{waConversation}/open', [WaChatController::class, 'markOpen'])->name('open');
        Route::post('/conversations/{waConversation}/assign', [WaChatController::class, 'assign'])->name('assign');
        Route::get('/assignable-users', [WaChatController::class, 'assignableUsers'])->name('assignable-users');
        Route::delete('/conversations/{waConversation}', [WaChatController::class, 'destroy'])->name('destroy');
    });
