<?php

use App\Http\Controllers\ShiftController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'system.license'])
    ->prefix('shifts')
    ->name('shifts.')
    ->group(function (): void {
        Route::get('/', [ShiftController::class, 'index'])->name('index');
        Route::get('/my', [ShiftController::class, 'mySchedule'])->name('my');
        Route::post('/definitions', [ShiftController::class, 'storeDefinition'])->name('definitions.store');
        Route::put('/definitions/{shiftDefinition}', [ShiftController::class, 'updateDefinition'])->name('definitions.update');
        Route::delete('/definitions/{shiftDefinition}', [ShiftController::class, 'destroyDefinition'])->name('definitions.destroy');
        Route::post('/schedule', [ShiftController::class, 'storeSchedule'])->name('schedule.store');
        Route::delete('/schedule/{shiftSchedule}', [ShiftController::class, 'destroySchedule'])->name('schedule.destroy');
        Route::post('/swap-requests', [ShiftController::class, 'requestSwap'])->name('swap-requests.store');
        Route::post('/swap-requests/{shiftSwapRequest}/review', [ShiftController::class, 'reviewSwap'])->name('swap-requests.review');
    });
