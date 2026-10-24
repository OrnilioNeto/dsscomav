<?php

use App\Modules\CronogramaConteudo\Http\Controllers\CronogramaConteudoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin/cronograma-conteudo')
    ->name('admin.cronograma.')
    ->group(function () {
        Route::middleware('permission:cronograma_conteudo,view')->group(function () {
            Route::get('/', [CronogramaConteudoController::class, 'index'])->name('index');
            Route::get('/pendentes', [CronogramaConteudoController::class, 'pendentes'])->name('pendentes');
        });

        Route::middleware('permission:cronograma_conteudo,edit')->group(function () {
            Route::post('/', [CronogramaConteudoController::class, 'store'])->name('store');
            Route::put('/{cronograma}', [CronogramaConteudoController::class, 'update'])->name('update');
            Route::delete('/{cronograma}', [CronogramaConteudoController::class, 'destroy'])->name('destroy');
        });
    });