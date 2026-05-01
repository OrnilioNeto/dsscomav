<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingPlayerController;
use App\Http\Controllers\UserController;
use App\Http\Middleware\CheckRole;
use Illuminate\Support\Facades\Route;

// Rota inicial
Route::get('/', function () {
    return view('welcome');
})->name('home');

// TESTE DE VÍDEO - ACESSÍVEL PARA TODOS
Route::get('/teste-video', function () {
    return view('teste-video');
});

// Rotas de autenticação
Route::get('/login', [AuthController::class, 'showLogin'])->name('login')->middleware('guest');
Route::post('/login', [AuthController::class, 'login'])->middleware('guest');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

// Validação pública de certificado
Route::get('/validar/{codigo}', [CertificateController::class, 'validateCertificate'])->name('validar.certificado');

// Rotas protegidas por autenticação
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Visualizar e completar treinamentos
    Route::get('/treinamentos/{id}/player', [TrainingPlayerController::class, 'show'])->name('treinamentos.player');
    Route::post('/treinamentos/{id}/atualizar-progresso', [TrainingPlayerController::class, 'updateProgress'])->name('treinamentos.atualizar-progresso');
    Route::post('/treinamentos/{id}/avaliacao', [TrainingPlayerController::class, 'submitAssessment'])->name('treinamentos.avaliacao');
    Route::post('/treinamentos/{id}/completar', [TrainingPlayerController::class, 'complete'])->name('treinamentos.completar');

    // Certificados do usuário
    Route::get('/meus-certificados', [CertificateController::class, 'myCertificates'])->name('certificados.meus');
    Route::get('/treinamentos/{id}/certificado', [CertificateController::class, 'downloadForTraining'])->name('certificados.por-training');
    Route::get('/certificados/{id}/download', [CertificateController::class, 'downloadCertificate'])->name('certificados.download');

    // Rotas do Admin e Super Admin
    Route::middleware([CheckRole::class . ':admin,super_admin'])->group(function () {
        // Usuários
        Route::resource('usuarios', UserController::class);

        // Treinamentos
        Route::resource('treinamentos', TrainingController::class);

        // Certificados e Relatórios Gerenciais
        Route::get('/certificados-gerencial', [CertificateManagementController::class, 'index'])->name('certificados.gerencial');
        Route::get('/relatorios/treinamentos', [CertificateManagementController::class, 'relatorioTreinamentos'])->name('relatorios.treinamentos');
        Route::get('/relatorios/usuarios', [CertificateManagementController::class, 'relatorioUsuarios'])->name('relatorios.usuarios');
        Route::get('/relatorios/auditoria', [CertificateManagementController::class, 'relatorioAuditoria'])->name('relatorios.auditoria');
        Route::get('/certificados/exportar', [CertificateManagementController::class, 'exportarCertificados'])->name('certificados.exportar');
    });
});
