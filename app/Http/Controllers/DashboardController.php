<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\Request;
use App\Models\RankingMonthlyScore;
use App\Services\RankingRuleResolverService;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        try {
            if ($user->isSuperAdmin()) {
                return $this->dashboardSuperAdmin();
            }

            if ($user->isAdmin()) {
                return $this->dashboardAdmin();
            }

            return $this->dashboardUser();
        } catch (\Throwable $e) {
            report($e);

            return redirect()->route('login')
                ->with('error', 'Não foi possível carregar o painel após o login. Verifique o log do servidor.');
        }
    }

    private function dashboardSuperAdmin()
    {
        $totalUsuarios = User::kpiEligible()->count();
        $totalTreinamentos = Training::count();
        $usuariosAtivos = User::kpiEligible()->where('status', 'ativo')->count();
        $certificadosEmitidos = Certificate::whereHas('user', function ($query) {
            $query->kpiEligible();
        })->count();

        $usuariosPorTipo = User::kpiEligible()->groupBy('tipo_usuario')
            ->selectRaw('tipo_usuario, count(*) as total')
            ->get();

        $taxaConclusao = [];
        $treinamentos = Training::where('status', 'ativo')->take(5)->get();
        foreach ($treinamentos as $training) {
            $taxaConclusao[$training->id] = $training->getTaxaConclusao();
        }

        return view('dashboard.super_admin', [
            'totalUsuarios' => $totalUsuarios,
            'totalTreinamentos' => $totalTreinamentos,
            'usuariosAtivos' => $usuariosAtivos,
            'certificadosEmitidos' => $certificadosEmitidos,
            'usuariosPorTipo' => $usuariosPorTipo,
            'taxaConclusao' => $taxaConclusao,
            'treinamentos' => $treinamentos,
        ]);
    }

    private function dashboardAdmin()
    {
        $user = auth()->user();
        
        $totalUsuarios = User::kpiEligible()->where('role_id', '<>', 1)->count();
        $totalTreinamentos = Training::count();
        $usuariosAtivos = User::kpiEligible()->where('status', 'ativo')->count();
        $certificadosEmitidos = Certificate::whereHas('user', function ($query) {
            $query->kpiEligible();
        })->count();

        $treinamentosRecentes = Training::orderBy('created_at', 'desc')->take(5)->get();
        $usuariosRecentes = User::kpiEligible()->where('role_id', '<>', 1)->orderBy('created_at', 'desc')->take(5)->get();

        // Se o admin participa de treinamentos, carregar dados de treinamentos disponíveis
        $treinamentosDisponíveis = [];
        if ($user->participa_treinamentos) {
            $treinamentosDisponíveis = Training::where('status', 'ativo')
                ->get()
                ->filter(fn($t) => $user->canAccessTraining($t));
        }

        return view('dashboard.admin', [
            'totalUsuarios' => $totalUsuarios,
            'totalTreinamentos' => $totalTreinamentos,
            'usuariosAtivos' => $usuariosAtivos,
            'certificadosEmitidos' => $certificadosEmitidos,
            'treinamentosRecentes' => $treinamentosRecentes,
            'usuariosRecentes' => $usuariosRecentes,
            'treinamentosDisponíveis' => $treinamentosDisponíveis,
        ]);
    }

    private function dashboardUser()
    {
        $user = auth()->user();

        $treinamentosElegiveis = Training::where('status', 'ativo')
            ->get()
            ->filter(fn($t) => $user->canAccessTraining($t));

        $treinamentosDisponíveis = $treinamentosElegiveis
            ->filter(fn($t) => $t->isReleased())
            ->values();

        $treinamentosBloqueados = $treinamentosElegiveis
            ->filter(fn($t) => !$t->isReleased())
            ->values();

        $progresso = UserProgress::where('user_id', $user->id)->get();
        $certificados = Certificate::where('user_id', $user->id)->where('valido', true)->count();

        $tempoTotal = $progresso->sum('tempo_assistido');
        $tempoFormatado = gmdate('H:i:s', $tempoTotal);

        $treinamentosCompletos = $progresso->where('concluido', true)->count();

        // Separar em categorias
        $treinamentosConcluidos = [];
        $treinamentosPendentes = [];
        $treinamentosNaoIniciados = [];

        foreach ($treinamentosDisponíveis as $training) {
            $userProgress = $progresso->where('training_id', $training->id)->first();
            
            if (!$userProgress) {
                $treinamentosNaoIniciados[] = $training;
            } elseif ($userProgress->concluido) {
                $treinamentosConcluidos[] = $training;
            } else {
                $treinamentosPendentes[] = $training;
            }
        }

        // Cálculo de pontos e rank em tempo real para o dashboard
        $month = now()->month;
        $year = now()->year;
        $resolver = app(RankingRuleResolverService::class);
        
        // Reutilizamos a lógica do RankingController para pegar a pontuação real atualizada
        $rankingController = app(\App\Http\Controllers\Admin\RankingController::class);
        $request = new Request(['month' => $month, 'year' => $year]);
        $breakdownData = $rankingController->breakdown($request, $user->id, $resolver)->getData();
        
        $totalPoints = collect($breakdownData->trainings ?? [])->sum('raw_score');
        $rankingLevel = $this->calculateLevel($totalPoints);

        // Rank baseado na última consolidação
        $userRank = 0;
        if (!$user->usuario_teste && $totalPoints > 0) {
            $userRank = RankingMonthlyScore::join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                ->where('users.usuario_teste', false)
                ->where('users.status', 'ativo')
                ->where('ranking_monthly_scores.month_reference', $month)
                ->where('ranking_monthly_scores.year_reference', $year)
                ->where('ranking_monthly_scores.average_score', '>', $totalPoints)
                ->count() + 1;
        }

        return view('dashboard.usuario', [
            'treinamentosDisponíveis' => $treinamentosDisponíveis,
            'treinamentosBloqueados' => $treinamentosBloqueados,
            'treinamentosConcluidos' => $treinamentosConcluidos,
            'treinamentosPendentes' => $treinamentosPendentes,
            'treinamentosNaoIniciados' => $treinamentosNaoIniciados,
            'progresso' => $progresso,
            'certificados' => $certificados,
            'tempoTotal' => $tempoFormatado,
            'treinamentosCompletos' => $treinamentosCompletos,
            'rankingLevel' => $rankingLevel,
            'totalPoints' => $totalPoints,
            'userRank' => $userRank
        ]);
    }

    public function profileStats(RankingRuleResolverService $resolver)
    {
        $user = auth()->user();
        $month = now()->month;
        $year = now()->year;

        // Reutilizamos a lógica de breakdown do RankingController, mas para o próprio usuário
        $controller = app(\App\Http\Controllers\Admin\RankingController::class);
        $request = new Request(['month' => $month, 'year' => $year]);
        $breakdownData = $controller->breakdown($request, $user->id, $resolver)->getData();
        $trainings = $breakdownData->trainings ?? [];

        $score = collect($trainings)->sum('raw_score');
        $rankingLevel = $this->calculateLevel($score);

        $userRank = 0;
        if (!$user->usuario_teste && $score > 0) {
            $userRank = RankingMonthlyScore::join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                ->where('users.usuario_teste', false)
                ->where('users.status', 'ativo')
                ->where('ranking_monthly_scores.month_reference', $month)
                ->where('ranking_monthly_scores.year_reference', $year)
                ->where('ranking_monthly_scores.average_score', '>', $score)
                ->count() + 1;
        }

        return view('dashboard.profile-stats', [
            'user' => $user,
            'rankingLevel' => $rankingLevel,
            'totalPoints' => $score,
            'userRank' => $userRank,
            'trainings' => $trainings,
            'month' => $month,
            'year' => $year
        ]);
    }

    private function calculateLevel($score)
    {
        if ($score >= 1000) {
            return ['name' => 'Mítico', 'class' => 'tier-mythic', 'color' => '#7c3aed', 'icon' => 'fa-dragon', 'msg' => 'Você é uma lenda da segurança! Sua dedicação salva vidas e inspira a todos.'];
        } elseif ($score >= 750) {
            return ['name' => 'Mestre', 'class' => 'tier-master', 'color' => '#ef4444', 'icon' => 'fa-crown', 'msg' => 'Excelente desempenho! Seu compromisso com o aprendizado é exemplar.'];
        } elseif ($score >= 500) {
            return ['name' => 'Diamante', 'class' => 'tier-diamond', 'color' => '#0ea5e9', 'icon' => 'fa-gem', 'msg' => 'Brilhante! Você está entre os profissionais mais qualificados da frota.'];
        } elseif ($score >= 350) {
            return ['name' => 'Platina', 'class' => 'tier-platinum', 'color' => '#94a3b8', 'icon' => 'fa-award', 'msg' => 'Parabéns pela evolução constante! Continue acelerando seu conhecimento.'];
        } elseif ($score >= 200) {
            return ['name' => 'Ouro', 'class' => 'tier-gold', 'color' => '#eab308', 'icon' => 'fa-medal', 'msg' => 'Ótimo trabalho! Você está no caminho certo para a elite.'];
        } elseif ($score >= 100) {
            return ['name' => 'Prata', 'class' => 'tier-silver', 'color' => '#94a3b8', 'icon' => 'fa-certificate', 'msg' => 'Bom começo! Continue assistindo aos conteúdos para subir de nível.'];
        }
        
        return ['name' => 'Bronze', 'class' => 'tier-bronze', 'color' => '#b45309', 'icon' => 'fa-shield-alt', 'msg' => 'Inicie seus treinamentos para conquistar sua primeira medalha!'];
    }
}
