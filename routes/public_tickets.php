<?php

use App\Http\Controllers\TicketPublicController;
use Illuminate\Support\Facades\Route;

Route::get('/tiket/{token}', [TicketPublicController::class, 'show'])->name('ticket.public-progress');
