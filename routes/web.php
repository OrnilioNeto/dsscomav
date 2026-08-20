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
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\ProjetoPedagogicoController;
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

    // Módulo de Saúde e Segurança (Gestão de EPIs) - Acesso restrito a quem tem permissão
    Route::prefix('epi')->middleware('permission:epi')->group(function () {
        Route::get('/', [\App\Http\Controllers\EpiController::class, 'index'])->name('epi.index');
        Route::get('/estoque-disponivel', [\App\Http\Controllers\EpiController::class, 'getEstoqueDisponivel'])->name('epi.estoque-disponivel');
        Route::get('/variacoes/{epiId}', [\App\Http\Controllers\EpiController::class, 'getVariacoes'])->name('epi.variacoes');
        Route::post('/catalogo', [\App\Http\Controllers\EpiController::class, 'catalogoStore'])->name('epi.catalogo.store');
        Route::post('/catalogo/{id}/toggle', [\App\Http\Controllers\EpiController::class, 'catalogoToggleStatus'])->name('epi.catalogo.toggle');
        Route::get('/modelo-csv', [\App\Http\Controllers\EpiController::class, 'modeloCsv'])->name('epi.modelo-csv');
        Route::get('/export-csv', [\App\Http\Controllers\EpiController::class, 'exportCsv'])->name('epi.export-csv');
        Route::post('/import-csv', [\App\Http\Controllers\EpiController::class, 'importCsv'])->name('epi.import-csv');
        Route::post('/estoque', [\App\Http\Controllers\EpiController::class, 'estoqueStore'])->name('epi.estoque.store');
        Route::post('/kits', [\App\Http\Controllers\EpiController::class, 'kitStore'])->name('epi.kits.store');
        Route::delete('/kits/{id}', [\App\Http\Controllers\EpiController::class, 'kitDestroy'])->name('epi.kits.destroy');
        Route::post('/entrega', [\App\Http\Controllers\EpiController::class, 'entregaStore'])->name('epi.entrega.store');
        Route::post('/entrega/{id}/cancelar', [\App\Http\Controllers\EpiController::class, 'entregaCancelar'])->name('epi.entrega.cancelar');
        Route::post('/entrega/{id}/vencimento', [\App\Http\Controllers\EpiController::class, 'editarVencimentoEntrega'])->name('epi.entrega.vencimento');
        Route::get('/colaborador/{id}/entregas', [\App\Http\Controllers\EpiController::class, 'getEntregasColaborador'])->name('epi.colaborador.entregas');
        Route::get('/{epiId}/entregas', [\App\Http\Controllers\EpiController::class, 'getEntregasPorEpi'])->name('epi.entregas-por-epi');
        Route::post('/devolucao', [\App\Http\Controllers\EpiController::class, 'devolucaoStore'])->name('epi.devolucao.store');
        Route::post('/devolucao/{id}/decidir', [\App\Http\Controllers\EpiController::class, 'inspecaoDecidir'])->name('epi.devolucao.decidir');
        Route::get('/ficha/{colaborador_id}', [\App\Http\Controllers\EpiController::class, 'fichaColaborador'])->name('epi.ficha');
        Route::post('/colaborador', [\App\Http\Controllers\EpiController::class, 'colaboradorStore'])->name('epi.colaborador.store');
        Route::post('/filiais', [\App\Http\Controllers\EpiController::class, 'filialStore'])->name('epi.filiais.store');
        Route::post('/filiais/{id}/toggle', [\App\Http\Controllers\EpiController::class, 'filialToggleStatus'])->name('epi.filiais.toggle');
        Route::delete('/filiais/{id}', [\App\Http\Controllers\EpiController::class, 'filialDestroy'])->name('epi.filiais.destroy');

        // Gestão de Assinaturas (apenas gestores)
        Route::get('/gestao-assinaturas', [\App\Http\Controllers\EpiController::class, 'gestaoAssinaturas'])->name('epi.gestao-assinaturas');
        Route::post('/gestao-assinaturas/{id}/alterar', [\App\Http\Controllers\EpiController::class, 'alterarEntrega'])->name('epi.gestao-assinaturas.alterar');
    });

    // Assinatura Digital do Colaborador (acessível a qualquer usuário autenticado)
    Route::prefix('epi')->group(function () {
        Route::get('/assinaturas', [\App\Http\Controllers\EpiController::class, 'pendentesAssinatura'])->name('epi.assinaturas');
        Route::post('/assinaturas/{id}/assinar', [\App\Http\Controllers\EpiController::class, 'assinarEntrega'])->name('epi.assinaturas.assinar');
        Route::post('/assinaturas/{id}/negar', [\App\Http\Controllers\EpiController::class, 'negarAssinatura'])->name('epi.assinaturas.negar');
    });

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
    Route::post('/treinamentos/{id}/avaliacao/iniciar', [TrainingPlayerController::class, 'iniciarAvaliacao'])->name('treinamentos.avaliacao.iniciar');
    Route::post('/treinamentos/{id}/avaliacao', [TrainingPlayerController::class, 'submitAssessment'])->name('treinamentos.avaliacao');
    Route::post('/treinamentos/{id}/completar', [TrainingPlayerController::class, 'complete'])->name('treinamentos.completar');

    // Certificados do usuário
    Route::get('/meus-certificados', [CertificateController::class, 'myCertificates'])->name('certificados.meus');
    Route::get('/treinamentos/{id}/certificado', [CertificateController::class, 'downloadForTraining'])->name('certificados.por-training');
    Route::get('/certificados/{id}/download', [CertificateController::class, 'downloadCertificate'])->name('certificados.download');

    // Download de Materiais de Apoio
    Route::get('/materiais/{materialId}/download', [TrainingMaterialController::class, 'download'])->name('materiais.download');

    // Rotas do Admin e Super Admin (filtradas dinamicamente via permissões e middleware admin)
    Route::middleware('admin')->group(function () {
        // Usuários
        Route::get('/usuarios/excluidos-kpi', [UserController::class, 'relatorioExcluidosKPI'])->name('usuarios.exclus-kpi');
        Route::resource('usuarios', UserController::class);

        // Ficha do Funcionário (EPIs e Treinamentos Externos)
        Route::get('/usuarios/{id}/ficha', [App\Http\Controllers\EmployeeFichaController::class, 'manage'])->name('usuarios.ficha.manage');
        Route::post('/usuarios/{id}/ficha/treinamento', [App\Http\Controllers\EmployeeFichaController::class, 'storeTraining'])->name('usuarios.ficha.storeTraining');
        Route::delete('/usuarios/ficha/treinamento/{id}', [App\Http\Controllers\EmployeeFichaController::class, 'destroyTraining'])->name('usuarios.ficha.destroyTraining');
        Route::post('/usuarios/{id}/ficha/regenerar-token', [App\Http\Controllers\EmployeeFichaController::class, 'regenerateToken'])->name('usuarios.ficha.regenerateToken');

        // Treinamentos
        Route::resource('treinamentos', TrainingController::class);

        // Projetos Pedagógicos (NR-01 Anexo II 3.1/4.1.1) — liberação por perfil
        Route::get('/projetos-pedagogicos', [ProjetoPedagogicoController::class, 'index'])->name('projetos-pedagogicos.index');
        Route::get('/projetos-pedagogicos/novo', [ProjetoPedagogicoController::class, 'create'])->name('projetos-pedagogicos.create');
        Route::post('/projetos-pedagogicos', [ProjetoPedagogicoController::class, 'store'])->name('projetos-pedagogicos.store');
        Route::get('/projetos-pedagogicos/{pp}/editar', [ProjetoPedagogicoController::class, 'edit'])->name('projetos-pedagogicos.edit');
        Route::put('/projetos-pedagogicos/{pp}', [ProjetoPedagogicoController::class, 'update'])->name('projetos-pedagogicos.update');
        Route::delete('/projetos-pedagogicos/{pp}', [ProjetoPedagogicoController::class, 'destroy'])->name('projetos-pedagogicos.destroy');
        Route::get('/projetos-pedagogicos/{pp}/pdf', [ProjetoPedagogicoController::class, 'download'])->name('projetos-pedagogicos.download');
        Route::get('/projetos-pedagogicos/{pp}/arquivo', [ProjetoPedagogicoController::class, 'downloadArquivo'])->name('projetos-pedagogicos.download-arquivo');

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

        // Ranking (acesso controlado por permissão:rankings)
        Route::get('/admin/ranking', [\App\Http\Controllers\Admin\RankingController::class, 'index'])->name('admin.ranking.index');
        Route::post('/admin/ranking/recalculate', [\App\Http\Controllers\Admin\RankingController::class, 'recalculate'])->name('admin.ranking.recalculate');
        Route::get('/admin/ranking/historico', [\App\Http\Controllers\Admin\RankingController::class, 'history'])->name('admin.ranking.history');
        Route::get('/admin/ranking/breakdown/{user}', [\App\Http\Controllers\Admin\RankingController::class, 'breakdown'])->name('admin.ranking.breakdown');
        Route::get('/admin/ranking/configuracoes', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'index'])->name('admin.ranking.settings');
        Route::post('/admin/ranking/configuracoes', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'update'])->name('admin.ranking.settings.update');
        Route::post('/admin/ranking/criterios/{criterion}/regras', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'storeRule'])->name('admin.ranking.rules.store');
        Route::put('/admin/ranking/regras/{rule}', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'updateRule'])->name('admin.ranking.rules.update');
        Route::delete('/admin/ranking/regras/{rule}', [\App\Http\Controllers\Admin\RankingSettingsController::class, 'destroyRule'])->name('admin.ranking.rules.destroy');

        // Gerenciador de Conteúdos Splash (acesso controlado por permissão:splash)
        Route::get('/admin/splash', [SplashContentController::class, 'index'])->name('admin.splash.index');
        Route::post('/admin/splash', [SplashContentController::class, 'store'])->name('admin.splash.store');
        Route::put('/admin/splash/{id}', [SplashContentController::class, 'update'])->name('admin.splash.update');
        Route::delete('/admin/splash/{id}', [SplashContentController::class, 'destroy'])->name('admin.splash.destroy');
        Route::patch('/admin/splash/{id}/toggle', [SplashContentController::class, 'toggleStatus'])->name('admin.splash.toggle');
        Route::post('/admin/splash/reorder', [SplashContentController::class, 'reorder'])->name('admin.splash.reorder');
    });

    // Roteamento exclusivo para Super Admin
    Route::middleware([CheckRole::class . ':super_admin'])->group(function () {
        // Gestão de Perfis e Permissões
        Route::get('/admin/permissoes', [PermissionController::class, 'index'])->name('admin.permissoes.index');
        Route::post('/admin/permissoes/perfis', [PermissionController::class, 'storeRole'])->name('admin.permissoes.storeRole');
        Route::delete('/admin/permissoes/perfis/{id}', [PermissionController::class, 'destroyRole'])->name('admin.permissoes.destroyRole');
        Route::post('/admin/permissoes/salvar', [PermissionController::class, 'updatePermissions'])->name('admin.permissoes.update');
    });

    // REDE SOCIAL (Acesso controlado por permissão:social)
    Route::middleware('permission:social')->group(function () {
        Route::get('/social/feed', [\App\Http\Controllers\SocialController::class, 'index'])->name('social.feed');
        Route::post('/social/posts', [\App\Http\Controllers\SocialController::class, 'storePost'])->name('social.posts.store');
        Route::delete('/social/posts/{id}', [\App\Http\Controllers\SocialController::class, 'destroyPost'])->name('social.posts.destroy');
        Route::post('/social/posts/{id}/like', [\App\Http\Controllers\SocialController::class, 'toggleLike'])->name('social.posts.like');
        Route::post('/social/posts/{id}/comment', [\App\Http\Controllers\SocialController::class, 'storeComment'])->name('social.posts.comment');
        Route::post('/social/user/{id}/follow', [\App\Http\Controllers\SocialController::class, 'toggleFollow'])->name('social.user.follow');
        Route::get('/social/user/{id}', [\App\Http\Controllers\SocialController::class, 'showProfile'])->name('social.user.profile');
    });
});

