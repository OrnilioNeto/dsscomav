<?php

use App\Modules\Frota\Http\Controllers\FrotaController;
use App\Modules\Frota\Http\Controllers\LiberacaoTreinamentoController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'admin'])
    ->prefix('admin/frota')
    ->name('admin.frota.')
    ->group(function () {
        Route::middleware('permission:frota,view')->group(function () {
            Route::get('/', [FrotaController::class, 'index'])->name('index');
            Route::get('/veiculos', [FrotaController::class, 'veiculos'])->name('veiculos');
            Route::get('/historico', [FrotaController::class, 'historico'])->name('historico');

            Route::get('/liberacoes-treinamento', [LiberacaoTreinamentoController::class, 'index'])
                ->name('liberacoes.index');
            Route::get('/liberacoes-treinamento/exportar', [LiberacaoTreinamentoController::class, 'exportar'])
                ->name('liberacoes.exportar');
        });

        Route::middleware('permission:frota,edit')->group(function () {
            Route::post('/liberacoes-treinamento', [LiberacaoTreinamentoController::class, 'store'])
                ->name('liberacoes.store');
            Route::post('/liberacoes-treinamento/{liberacao}/revogar', [LiberacaoTreinamentoController::class, 'revogar'])
                ->name('liberacoes.revogar');
        });

        Route::middleware('permission:integracao_autotrac,edit')->group(function () {
            Route::get('/configuracao', [FrotaController::class, 'configuracao'])->name('configuracao');
            Route::post('/configuracao', [FrotaController::class, 'salvarConfiguracao'])->name('configuracao.salvar');
            Route::post('/configuracao/testar', [FrotaController::class, 'testar'])->name('configuracao.testar');
            Route::post('/sincronizar', [FrotaController::class, 'sincronizar'])->name('sincronizar');
        });
    });
