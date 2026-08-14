<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CertificateController;
use App\Http\Controllers\Api\DashboardController;
use App\Http\Controllers\Api\EpiController;
use App\Http\Controllers\Api\MiscController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\RankingController;
use App\Http\Controllers\Api\SocialController;
use App\Http\Controllers\Api\TrainingController;
use App\Http\Controllers\Api\TrainingMaterialController;
use App\Http\Controllers\Api\Admin\UserController as AdminUserController;
use App\Http\Controllers\Api\Admin\TrainingController as AdminTrainingController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 — Plataforma DSS (app Android/iOS)
|--------------------------------------------------------------------------
*/

Route::prefix('v1')->group(function () {

    // ---- Públicas ----
    Route::get('/certificates/validate/{codigo}', [CertificateController::class, 'validateCertificate']);
    Route::get('/ficha/{token}', [MiscController::class, 'fichaPublica']);

    // Proxy de vídeo (autenticado por header OU token na query — players nativos
    // podem não enviar headers personalizados)
    Route::get('/trainings/{id}/stream-proxy', [TrainingController::class, 'streamProxy']);

    // ---- Autenticação ----
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:10,1');

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::post('/auth/logout-all', [AuthController::class, 'logoutAllDevices']);

        // ---- Dashboard / Perfil ----
        Route::get('/dashboard', [DashboardController::class, 'index']);
        Route::get('/profile/stats', [DashboardController::class, 'profileStats']);
        Route::put('/profile', [ProfileController::class, 'updateProfile']);
        Route::post('/profile/photo', [ProfileController::class, 'uploadPhoto']);
        Route::delete('/profile/photo', [ProfileController::class, 'deletePhoto']);
        Route::get('/profile/ficha-qr', [ProfileController::class, 'fichaQr']);

        // ---- Diagnóstico remoto ----
        Route::post('/debug/video-log', [MiscController::class, 'videoLog']);

        // ---- Treinamentos ----
        Route::get('/trainings', [TrainingController::class, 'index']);
        Route::get('/trainings/{id}', [TrainingController::class, 'show']);
        Route::get('/trainings/{id}/stream', [TrainingController::class, 'stream']);
        Route::post('/trainings/{id}/progress', [TrainingController::class, 'updateProgress']);
        Route::post('/trainings/{id}/assessment', [TrainingController::class, 'submitAssessment']);
        Route::post('/trainings/{id}/complete', [TrainingController::class, 'complete']);
        Route::get('/trainings/{id}/certificate', [CertificateController::class, 'downloadForTraining']);

        // ---- Materiais ----
        Route::get('/materials/{materialId}/download', [TrainingMaterialController::class, 'download']);

        // ---- Certificados ----
        Route::get('/certificates/mine', [CertificateController::class, 'mine']);
        Route::get('/certificates/{id}/download', [CertificateController::class, 'download']);

        // ---- EPI (colaborador) ----
        Route::get('/epi/pending-signatures', [EpiController::class, 'pendingSignatures']);
        Route::post('/epi/signatures/{id}/sign', [EpiController::class, 'sign']);
        Route::post('/epi/signatures/{id}/deny', [EpiController::class, 'deny']);
        Route::get('/epi/ficha/me', [EpiController::class, 'fichaMe']);

        // ---- Rede Social ----
        Route::get('/social/feed', [SocialController::class, 'feed']);
        Route::post('/social/posts', [SocialController::class, 'storePost']);
        Route::delete('/social/posts/{id}', [SocialController::class, 'destroyPost']);
        Route::post('/social/posts/{id}/like', [SocialController::class, 'toggleLike']);
        Route::post('/social/posts/{id}/comment', [SocialController::class, 'storeComment']);
        Route::post('/social/users/{id}/follow', [SocialController::class, 'toggleFollow']);
        Route::get('/social/users/{id}', [SocialController::class, 'showProfile']);

        // ---- Ranking (usuário) ----
        Route::get('/ranking/me', [RankingController::class, 'me']);

        // ---- Splash ----
        Route::get('/splash/active', [MiscController::class, 'splashActive']);

        // ---- Admin ----
        Route::middleware('api.admin')->group(function () {
            // Usuários
            Route::get('/admin/users', [AdminUserController::class, 'index'])->middleware('api.permission:users,view');
            Route::get('/admin/users/roles', [AdminUserController::class, 'roles']);
            Route::post('/admin/users', [AdminUserController::class, 'store'])->middleware('api.permission:users,edit');
            Route::get('/admin/users/{id}', [AdminUserController::class, 'show'])->middleware('api.permission:users,view');
            Route::put('/admin/users/{id}', [AdminUserController::class, 'update'])->middleware('api.permission:users,edit');
            Route::delete('/admin/users/{id}', [AdminUserController::class, 'destroy'])->middleware('api.permission:users,edit');
            Route::post('/admin/users/{id}/ficha/treinamento', [AdminUserController::class, 'storeExternalTraining'])->middleware('api.permission:users,edit');
            Route::delete('/admin/users/ficha/treinamento/{id}', [AdminUserController::class, 'destroyExternalTraining'])->middleware('api.permission:users,edit');
            Route::post('/admin/users/{id}/ficha/regenerar-token', [AdminUserController::class, 'regenerateToken'])->middleware('api.permission:users,edit');

            // Treinamentos (CRUD)
            Route::get('/admin/trainings', [AdminTrainingController::class, 'index'])->middleware('api.permission:trainings,view');
            Route::post('/admin/trainings', [AdminTrainingController::class, 'store'])->middleware('api.permission:trainings,edit');
            Route::get('/admin/trainings/{id}', [AdminTrainingController::class, 'show'])->middleware('api.permission:trainings,view');
            Route::put('/admin/trainings/{id}', [AdminTrainingController::class, 'update'])->middleware('api.permission:trainings,edit');
            Route::delete('/admin/trainings/{id}', [AdminTrainingController::class, 'destroy'])->middleware('api.permission:trainings,edit');
            Route::post('/admin/trainings/{id}/toggle', [AdminTrainingController::class, 'toggleStatus'])->middleware('api.permission:trainings,edit');
            Route::post('/admin/trainings/{id}/materials', [AdminTrainingController::class, 'uploadMaterial'])->middleware('api.permission:trainings,edit');
            Route::delete('/admin/materials/{id}', [AdminTrainingController::class, 'destroyMaterial'])->middleware('api.permission:trainings,edit');

            // Ranking (admin)
            Route::get('/admin/ranking', [RankingController::class, 'index'])->middleware('api.permission:rankings,view');
            Route::post('/admin/ranking/recalculate', [RankingController::class, 'recalculate'])->middleware('api.permission:rankings,edit');

            // Splash (admin)
            Route::get('/admin/splash', [App\Http\Controllers\Api\Admin\SplashContentController::class, 'index'])->middleware('api.permission:splash,view');
            Route::post('/admin/splash', [App\Http\Controllers\Api\Admin\SplashContentController::class, 'store'])->middleware('api.permission:splash,edit');
            Route::put('/admin/splash/{id}', [App\Http\Controllers\Api\Admin\SplashContentController::class, 'update'])->middleware('api.permission:splash,edit');
            Route::delete('/admin/splash/{id}', [App\Http\Controllers\Api\Admin\SplashContentController::class, 'destroy'])->middleware('api.permission:splash,edit');
            Route::patch('/admin/splash/{id}/toggle', [App\Http\Controllers\Api\Admin\SplashContentController::class, 'toggleStatus'])->middleware('api.permission:splash,edit');
        });
    });
});
