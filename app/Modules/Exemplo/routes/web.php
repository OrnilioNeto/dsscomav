<?php

use App\Modules\Exemplo\Http\Controllers\ExemploController;
use Illuminate\Support\Facades\Route;

/*
| Rotas do módulo de exemplo (referência).
| Os arquivos de rota do módulo ficam fora do routes/web.php global;
| o ModuleServiceProvider aplica o grupo `web` automaticamente.
*/

Route::middleware(['auth', 'role:super_admin'])
    ->prefix('admin/exemplo')
    ->name('exemplo.')
    ->group(function () {
        Route::get('/', [ExemploController::class, 'index'])->name('index');
    });
