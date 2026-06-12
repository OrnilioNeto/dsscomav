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

        // Cálculo de pontos e rank em tempo real para o admin (visão pessoal)
        $month = now()->month;
        $year = now()->year;
        $resolver = app(RankingRuleResolverService::class);
        $rankingController = app(\App\Http\Controllers\Admin\RankingController::class);
        $requestObj = new Request(['month' => $month, 'year' => $year]);
        $breakdownData = $rankingController->breakdown($requestObj, $user->id, $resolver)->getData();
        $totalPoints = collect($breakdownData->trainings ?? [])->sum('raw_score');

        $userRank = 0;
        if (!$user->usuario_teste && $totalPoints > 0) {
            $monthlyScore = RankingMonthlyScore::join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                ->where('ranking_monthly_scores.user_id', $user->id)
                ->where('ranking_monthly_scores.month_reference', $month)
                ->where('ranking_monthly_scores.year_reference', $year)
                ->where('users.usuario_teste', false)
                ->where('users.status', 'ativo')
                ->value('ranking_monthly_scores.position');

            if ($monthlyScore) {
                $userRank = $monthlyScore;
            } else {
                $userRank = RankingMonthlyScore::join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                    ->where('users.usuario_teste', false)
                    ->where('users.status', 'ativo')
                    ->where('ranking_monthly_scores.month_reference', $month)
                    ->where('ranking_monthly_scores.year_reference', $year)
                    ->where('ranking_monthly_scores.average_score', '>', $totalPoints)
                    ->count() + 1;
            }
        }

        $rankingLevel = $this->calculateLevel($userRank);

        return view('dashboard.admin', [
            'totalUsuarios' => $totalUsuarios,
            'totalTreinamentos' => $totalTreinamentos,
            'usuariosAtivos' => $usuariosAtivos,
            'certificadosEmitidos' => $certificadosEmitidos,
            'treinamentosRecentes' => $treinamentosRecentes,
            'usuariosRecentes' => $usuariosRecentes,
            'treinamentosDisponíveis' => $treinamentosDisponíveis,
            'rankingLevel' => $rankingLevel,
            'totalPoints' => $totalPoints,
            'userRank' => $userRank
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

        // Rank baseado na última consolidação (usa position já calculado com desempate)
        $userRank = 0;
        if (!$user->usuario_teste && $totalPoints > 0) {
            $monthlyScore = RankingMonthlyScore::join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                ->where('ranking_monthly_scores.user_id', $user->id)
                ->where('ranking_monthly_scores.month_reference', $month)
                ->where('ranking_monthly_scores.year_reference', $year)
                ->where('users.usuario_teste', false)
                ->where('users.status', 'ativo')
                ->value('ranking_monthly_scores.position');

            if ($monthlyScore) {
                $userRank = $monthlyScore;
            } else {
                // Fallback: se o registro ainda não foi consolidado, conta por score
                $userRank = RankingMonthlyScore::join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                    ->where('users.usuario_teste', false)
                    ->where('users.status', 'ativo')
                    ->where('ranking_monthly_scores.month_reference', $month)
                    ->where('ranking_monthly_scores.year_reference', $year)
                    ->where('ranking_monthly_scores.average_score', '>', $totalPoints)
                    ->count() + 1;
            }
        }

        $rankingLevel = $this->calculateLevel($userRank);

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

        $userRank = 0;
        if (!$user->usuario_teste && $score > 0) {
            $monthlyScore = RankingMonthlyScore::join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                ->where('ranking_monthly_scores.user_id', $user->id)
                ->where('ranking_monthly_scores.month_reference', $month)
                ->where('ranking_monthly_scores.year_reference', $year)
                ->where('users.usuario_teste', false)
                ->where('users.status', 'ativo')
                ->value('ranking_monthly_scores.position');

            if ($monthlyScore) {
                $userRank = $monthlyScore;
            } else {
                $userRank = RankingMonthlyScore::join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                    ->where('users.usuario_teste', false)
                    ->where('users.status', 'ativo')
                    ->where('ranking_monthly_scores.month_reference', $month)
                    ->where('ranking_monthly_scores.year_reference', $year)
                    ->where('ranking_monthly_scores.average_score', '>', $score)
                    ->count() + 1;
            }
        }

        $rankingLevel = $this->calculateLevel($userRank);

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

    /**
     * Calcula o nível do usuário baseado na sua posição no ranking do mês.
     * $position = 0 significa sem posição (ainda não consolidado ou sem pontos).
     */
    private function calculateLevel(int $position)
    {
        if ($position === 1) {
            return [
                'name'  => 'Mítico',
                'sub'   => 'Comandante da Segurança',
                'class' => 'tier-mythic',
                'color' => '#7c3aed',
                'icon'  => 'fa-dragon',
                'msg'   => 'Você é o Comandante da Segurança! Parabéns por liderar o ranking — sua dedicação protege vidas e inspira toda a frota.',
            ];
        } elseif ($position <= 3) {
            return [
                'name'  => 'Titã',
                'sub'   => 'Mestre da Prevenção',
                'class' => 'tier-titan',
                'color' => '#ef4444',
                'icon'  => 'fa-crown',
                'msg'   => 'Incrível! Você está entre os 3 primeiros como Mestre da Prevenção. Continue assim e conquiste o topo!',
            ];
        } elseif ($position <= 10) {
            return [
                'name'  => 'Imperial',
                'sub'   => 'Defensor Supremo',
                'class' => 'tier-imperial',
                'color' => '#f97316',
                'icon'  => 'fa-shield-halved',
                'msg'   => 'Excelente! Você é um Defensor Supremo no top 10. Seu comprometimento com a segurança faz a diferença todos os dias.',
            ];
        } elseif ($position <= 20) {
            return [
                'name'  => 'Elite',
                'sub'   => 'Embaixador da Segurança',
                'class' => 'tier-elite',
                'color' => '#0ea5e9',
                'icon'  => 'fa-star',
                'msg'   => 'Parabéns! Você está no top 20 como Embaixador da Segurança. Mais um esforço e você entra para o grupo Imperial!',
            ];
        } elseif ($position <= 35) {
            return [
                'name'  => 'Prata',
                'sub'   => 'Agente Preventivo',
                'class' => 'tier-silver',
                'color' => '#64748b',
                'icon'  => 'fa-certificate',
                'msg'   => 'Bom trabalho, Agente Preventivo! Você está crescendo no ranking. Foque nos treinamentos e suba para o nível Elite!',
            ];
        }

        // Posição 36+ ou sem posição ainda
        return [
            'name'  => 'Bronze',
            'sub'   => 'Observador de Segurança',
            'class' => 'tier-bronze',
            'color' => '#b45309',
            'icon'  => 'fa-shield-alt',
            'msg'   => 'Todo grande campeão começa aqui! Complete os treinamentos, ganhe pontos e suba para o nível Prata.',
        ];
    }
}
