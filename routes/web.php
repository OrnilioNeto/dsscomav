<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CertificateManagementController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\TrainingController;
use App\Http\Controllers\TrainingPlayerController;
use App\Http\Controllers\TrainingMaterialController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\ProfilePhotoController;
use App\Http\Middleware\CheckRole;
use App\Http\Controllers\Admin\SplashContentController;
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

// Ficha pública do colaborador (via QR Code)
Route::get('/ficha/{token}', [App\Http\Controllers\EmployeeFichaController::class, 'showPublic'])->name('ficha.publica');

// Rotas protegidas por autenticação
Route::middleware('auth')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Perfil do Usuário
    Route::get('/perfil/editar', [ProfilePhotoController::class, 'edit'])->name('profile.edit');
    Route::get('/perfil/estatisticas', [DashboardController::class, 'profileStats'])->name('profile.stats');
    Route::post('/perfil/foto/upload', [ProfilePhotoController::class, 'upload'])->name('profile.photo.upload');
    Route::delete('/perfil/foto/delete', [ProfilePhotoController::class, 'delete'])->name('profile.photo.delete');

    // Página de Certificação / Conformidade (acessível a todos os usuários autenticados)
    Route::get('/certificacao-conformidade', function () {
        return view('legal.certificacao');
    })->name('certificacao.conformidade');

    // Visualizar e completar treinamentos
    Route::get('/treinamentos/{id}/player', [TrainingPlayerController::class, 'show'])->name('treinamentos.player');
    Route::post('/treinamentos/{id}/atualizar-progresso', [TrainingPlayerController::class, 'updateProgress'])->name('treinamentos.atualizar-progresso');
    Route::post('/treinamentos/{id}/avaliacao', [TrainingPlayerController::class, 'submitAssessment'])->name('treinamentos.avaliacao');
    Route::post('/treinamentos/{id}/completar', [TrainingPlayerController::class, 'complete'])->name('treinamentos.completar');

    // Certificados do usuário
    Route::get('/meus-certificados', [CertificateController::class, 'myCertificates'])->name('certificados.meus');
    Route::get('/treinamentos/{id}/certificado', [CertificateController::class, 'downloadForTraining'])->name('certificados.por-training');
    Route::get('/certificados/{id}/download', [CertificateController::class, 'downloadCertificate'])->name('certificados.download');

    // Download de Materiais de Apoio
    Route::get('/materiais/{materialId}/download', [TrainingMaterialController::class, 'download'])->name('materiais.download');

    // Rotas do Admin e Super Admin
    Route::middleware([CheckRole::class . ':admin,super_admin'])->group(function () {
        // Usuários
        Route::get('/usuarios/excluidos-kpi', [UserController::class, 'relatorioExcluidosKPI'])->name('usuarios.exclus-kpi');
        Route::resource('usuarios', UserController::class);

        // Ficha do Funcionário (EPIs e Treinamentos Externos)
        Route::get('/usuarios/{id}/ficha', [App\Http\Controllers\EmployeeFichaController::class, 'manage'])->name('usuarios.ficha.manage');
        Route::post('/usuarios/{id}/ficha/treinamento', [App\Http\Controllers\EmployeeFichaController::class, 'storeTraining'])->name('usuarios.ficha.storeTraining');
        Route::delete('/usuarios/ficha/treinamento/{id}', [App\Http\Controllers\EmployeeFichaController::class, 'destroyTraining'])->name('usuarios.ficha.destroyTraining');
        Route::post('/usuarios/{id}/ficha/epi', [App\Http\Controllers\EmployeeFichaController::class, 'storeEpi'])->name('usuarios.ficha.storeEpi');
        Route::delete('/usuarios/ficha/epi/{id}', [App\Http\Controllers\EmployeeFichaController::class, 'destroyEpi'])->name('usuarios.ficha.destroyEpi');
        Route::post('/usuarios/{id}/ficha/regenerar-token', [App\Http\Controllers\EmployeeFichaController::class, 'regenerateToken'])->name('usuarios.ficha.regenerateToken');

        // Treinamentos
        Route::resource('treinamentos', TrainingController::class);

        // Materiais de Apoio
        Route::post('/treinamentos/{trainingId}/materiais/upload', [TrainingMaterialController::class, 'upload'])->name('materiais.upload');
        Route::delete('/materiais/{materialId}', [TrainingMaterialController::class, 'delete'])->name('materiais.delete');
        Route::post('/treinamentos/{trainingId}/materiais/reorder', [TrainingMaterialController::class, 'updateOrder'])->name('materiais.reorder');

        // Certificados e Relatórios Gerenciais
        Route::get('/certificados-gerencial', [CertificateManagementController::class, 'index'])->name('certificados.gerencial');
        Route::get('/relatorios/treinamentos', [CertificateManagementController::class, 'relatorioTreinamentos'])->name('relatorios.treinamentos');
        Route::get('/relatorios/treinamentos/pdf', [CertificateManagementController::class, 'relatorioTreinamentosPdf'])->name('relatorios.treinamentos.pdf');
        // IA / Análises gerenciais (Super Admin)
        Route::get('/relatorios/ia', [CertificateManagementController::class, 'relatoriosIa'])->name('relatorios.ia');
        Route::post('/relatorios/ia/analyze-local', [CertificateManagementController::class, 'analyzeLocal'])->name('relatorios.ia.analyze_local');
        Route::post('/relatorios/ia/analyze-ai', [CertificateManagementController::class, 'analyzeAi'])->name('relatorios.ia.analyze_ai');
        Route::get('/relatorios/usuarios', [CertificateManagementController::class, 'relatorioUsuarios'])->name('relatorios.usuarios');
        Route::get('/relatorios/auditoria', [CertificateManagementController::class, 'relatorioAuditoria'])->name('relatorios.auditoria');
        Route::get('/certificados/exportar', [CertificateManagementController::class, 'exportarCertificados'])->name('certificados.exportar');
    });

    // Roteamento exclusivo para Super Admin: Ranking
    Route::middleware([CheckRole::class . ':super_admin'])->group(function () {
        Route::get('/admin/ranking', [\App\Http\Controllers\Admin\RankingController::class, 'index'])->name('admin.ranking.index');
        Route::post('/admin/ranking/recalculate', [\App\Http\Controllers\Admin\RankingController::class, 'recalculate'])->name('admin.ranking.recalculate');
        Route::get('/admin/ranking/historico', [\App\Http\Controllers\Admin\RankingController::class, 'history'])->name('admin.ranking.history');
        Route::get('/admin/ranking/breakdown/{user}', [\App\Http\Controllers\Admin\RankingController::class, 'breakdown'])->name('admin.ranking.breakdown');
        Route::get('/admin/ranking/configuracoes', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'index'])->name('admin.ranking.settings');
        Route::post('/admin/ranking/configuracoes', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'update'])->name('admin.ranking.settings.update');
        Route::post('/admin/ranking/criterios/{criterion}/regras', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'storeRule'])->name('admin.ranking.rules.store'); // Store new rule
        Route::put('/admin/ranking/regras/{rule}', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'updateRule'])->name('admin.ranking.rules.update'); // Update existing rule
        Route::delete('/admin/ranking/regras/{rule}', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'destroyRule'])->name('admin.ranking.rules.destroy'); // Delete rule

        // Gerenciador de Conteúdos Splash
        Route::get('/admin/splash', [SplashContentController::class, 'index'])->name('admin.splash.index');
        Route::post('/admin/splash', [SplashContentController::class, 'store'])->name('admin.splash.store');
        Route::put('/admin/splash/{id}', [SplashContentController::class, 'update'])->name('admin.splash.update');
        Route::delete('/admin/splash/{id}', [SplashContentController::class, 'destroy'])->name('admin.splash.destroy');
        Route::patch('/admin/splash/{id}/toggle', [SplashContentController::class, 'toggleStatus'])->name('admin.splash.toggle');
        Route::post('/admin/splash/reorder', [SplashContentController::class, 'reorder'])->name('admin.splash.reorder');
    });
});
