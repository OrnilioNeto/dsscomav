<?php

use App\Modules\LembretesWhatsapp\Http\Controllers\ReminderController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin/lembretes-whatsapp')
    ->name('admin.lembretes.')
    ->group(function () {
        Route::middleware('permission:lembretes_whatsapp,view')->group(function () {
            Route::get('/', [ReminderController::class, 'index'])->name('index');
            Route::get('/disparo', [ReminderController::class, 'disparo'])->name('disparo');
            Route::get('/sessao', [ReminderController::class, 'sessao'])->name('sessao');
        });

        Route::middleware('permission:lembretes_whatsapp,edit')->group(function () {
            Route::post('/', [ReminderController::class, 'store'])->name('store');
            Route::post('/{reminder}/cancelar', [ReminderController::class, 'cancelar'])->name('cancelar');
        });
    });
