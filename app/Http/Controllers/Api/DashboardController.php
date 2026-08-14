<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\RankingMonthlyScore;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\RankingRuleResolverService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    private function getRankingStats(Request $request): array
    {
        $user = $request->user();
        $month = (int) now()->month;
        $year = (int) now()->year;

        $controller = app(\App\Http\Controllers\Admin\RankingController::class);
        $internalRequest = new Request(['month' => $month, 'year' => $year]);
        $resolver = app(RankingRuleResolverService::class);
        $breakdownData = $controller->breakdown($internalRequest, $user->id, $resolver)->getData();
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
                $userRank = (int) $monthlyScore;
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

        return [
            'month' => $month,
            'year' => $year,
            'total_points' => $totalPoints,
            'rank' => $userRank,
            'level' => $this->calculateLevel($userRank),
        ];
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if ($user->isSuperAdmin()) {
            return response()->json([
                'status' => 'success',
                'data' => $this->dashboardSuperAdmin(),
            ]);
        }

        if ($user->isAdmin()) {
            return response()->json([
                'status' => 'success',
                'data' => $this->dashboardAdmin($request),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'data' => $this->dashboardUser($request),
        ]);
    }

    private function dashboardSuperAdmin(): array
    {
        $totalUsuarios = User::kpiEligible()->count();
        $totalTreinamentos = Training::count();
        $certificadosEmitidos = Certificate::whereHas('user', fn ($q) => $q->kpiEligible())->count();

        $usuariosPorTipo = User::kpiEligible()
            ->selectRaw('tipo_usuario, count(*) as total')
            ->groupBy('tipo_usuario')
            ->pluck('total', 'tipo_usuario');

        $taxaConclusao = [];
        $treinamentos = Training::where('status', 'ativo')->take(5)->get();
        foreach ($treinamentos as $training) {
            $taxaConclusao[] = [
                'id' => $training->id,
                'titulo' => $training->titulo,
                'taxa_conclusao' => $training->getTaxaConclusao(),
            ];
        }

        return [
            'perfil' => 'super_admin',
            'total_usuarios' => $totalUsuarios,
            'usuarios_ativos' => $totalUsuarios,
            'total_treinamentos' => $totalTreinamentos,
            'certificados_emitidos' => $certificadosEmitidos,
            'usuarios_por_tipo' => $usuariosPorTipo,
            'taxa_conclusao' => $taxaConclusao,
        ];
    }

    private function dashboardAdmin(Request $request): array
    {
        $user = $request->user();

        $totalUsuarios = User::kpiEligible()->where('role_id', '<>', 1)->count();
        $certificadosEmitidos = Certificate::whereHas('user', fn ($q) => $q->kpiEligible())->count();

        $treinamentosRecentes = Training::orderBy('created_at', 'desc')->take(5)->get(['id', 'titulo', 'status', 'created_at']);
        $usuariosRecentes = User::kpiEligible()->where('role_id', '<>', 1)
            ->orderBy('created_at', 'desc')
            ->take(5)
            ->get(['id', 'nome', 'tipo_usuario', 'foto_perfil']);

        $stats = $this->getRankingStats($request);

        return [
            'perfil' => 'admin',
            'total_usuarios' => $totalUsuarios,
            'total_treinamentos' => Training::count(),
            'certificados_emitidos' => $certificadosEmitidos,
            'treinamentos_recentes' => $treinamentosRecentes,
            'usuarios_recentes' => $usuariosRecentes,
            'ranking' => $stats,
        ];
    }

    private function dashboardUser(Request $request): array
    {
        $user = $request->user();

        $progresso = UserProgress::where('user_id', $user->id)->get();
        $certificados = Certificate::where('user_id', $user->id)->where('valido', true)->count();
        $tempoTotal = $progresso->sum('tempo_assistido');
        $treinamentosCompletos = $progresso->where('concluido', true)->count();

        $stats = $this->getRankingStats($request);

        return [
            'perfil' => 'usuario',
            'certificados' => $certificados,
            'tempo_total_assistido' => $tempoTotal,
            'tempo_total_formatado' => gmdate('H:i:s', $tempoTotal),
            'treinamentos_completos' => $treinamentosCompletos,
            'ranking' => $stats,
        ];
    }

    public function profileStats(Request $request, RankingRuleResolverService $resolver)
    {
        $user = $request->user();
        $month = (int) now()->month;
        $year = (int) now()->year;

        $controller = app(\App\Http\Controllers\Admin\RankingController::class);
        $internalRequest = new Request(['month' => $month, 'year' => $year]);
        $breakdownData = $controller->breakdown($internalRequest, $user->id, $resolver)->getData();

        $stats = $this->getRankingStats($request);

        return response()->json([
            'status' => 'success',
            'data' => [
                'month' => $month,
                'year' => $year,
                'level' => $stats['level'],
                'total_points' => $stats['total_points'],
                'rank' => $stats['rank'],
                'trainings' => $breakdownData->trainings ?? [],
            ],
        ]);
    }

    /**
     * Calcula o nível do usuário baseado na posição no ranking do mês.
     */
    private function calculateLevel(int $position): array
    {
        if ($position === 1) {
            return [
                'name' => 'Mítico',
                'sub' => 'Comandante da Segurança',
                'color' => '#7c3aed',
                'msg' => 'Você é o Comandante da Segurança! Parabéns por liderar o ranking — sua dedicação protege vidas e inspira toda a frota.',
            ];
        } elseif ($position <= 3) {
            return [
                'name' => 'Titã',
                'sub' => 'Mestre da Prevenção',
                'color' => '#ef4444',
                'msg' => 'Incrível! Você está entre os 3 primeiros como Mestre da Prevenção. Continue assim e conquiste o topo!',
            ];
        } elseif ($position <= 10) {
            return [
                'name' => 'Imperial',
                'sub' => 'Defensor Supremo',
                'color' => '#f97316',
                'msg' => 'Excelente! Você é um Defensor Supremo no top 10. Seu comprometimento com a segurança faz a diferença todos os dias.',
            ];
        } elseif ($position <= 20) {
            return [
                'name' => 'Elite',
                'sub' => 'Embaixador da Segurança',
                'color' => '#0ea5e9',
                'msg' => 'Parabéns! Você está no top 20 como Embaixador da Segurança. Mais um esforço e você entra para o grupo Imperial!',
            ];
        } elseif ($position <= 35) {
            return [
                'name' => 'Prata',
                'sub' => 'Agente Preventivo',
                'color' => '#64748b',
                'msg' => 'Bom trabalho, Agente Preventivo! Você está crescendo no ranking. Foque nos treinamentos e suba para o nível Elite!',
            ];
        }

        return [
            'name' => 'Bronze',
            'sub' => 'Observador de Segurança',
            'color' => '#b45309',
            'msg' => 'Todo grande campeão começa aqui! Complete os treinamentos, ganhe pontos e suba para o nível Prata.',
        ];
    }
}
