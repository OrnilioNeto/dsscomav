<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RankingMonthlyScore;
use App\Models\Certificate;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\RankingRuleResolverService;
use App\Services\RankingMonthlyConsolidationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;

class RankingController extends Controller
{
    public function me(Request $request, RankingRuleResolverService $resolver)
    {
        $user = $request->user();
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        $controller = app(\App\Http\Controllers\Admin\RankingController::class);
        $internalRequest = new Request(['month' => $month, 'year' => $year]);
        $breakdownData = $controller->breakdown($internalRequest, $user->id, $resolver)->getData();
        $trainings = $breakdownData->trainings ?? [];

        $totalPoints = collect($trainings)->sum('raw_score');

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

        // Ranking geral (posição acumulada) para o usuário
        $generalRank = 0;
        $generalScore = 0;
        if (!$user->usuario_teste) {
            $generalScore = (int) RankingMonthlyScore::where('user_id', $user->id)->sum('average_score');
            if ($generalScore > 0) {
                $generalRank = RankingMonthlyScore::join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                    ->where('users.usuario_teste', false)
                    ->where('users.status', 'ativo')
                    ->groupBy('ranking_monthly_scores.user_id')
                    ->havingRaw('SUM(ranking_monthly_scores.average_score) > ?', [$generalScore])
                    ->selectRaw('SUM(ranking_monthly_scores.average_score) as total')
                    ->get()
                    ->count() + 1;
            }
        }

        $level = $this->calculateLevel($userRank);

        return response()->json([
            'status' => 'success',
            'data' => [
                'month' => $month,
                'year' => $year,
                'total_points' => $totalPoints,
                'rank' => $userRank,
                'general_rank' => $generalRank,
                'general_score' => $generalScore,
                'level' => $level,
                'trainings' => $trainings,
            ],
        ]);
    }

    public function index(Request $request)
    {
        $user = $request->user();

        if (!$user->hasPermission('rankings', 'view')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Você não tem permissão para acessar este recurso.',
            ], 403);
        }

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $top = (int) $request->input('top', 20);
        $type = $request->input('type', 'all');
        $isGeneral = ($month === 0);

        $driver = DB::getDriverName();
        $isSqlite = $driver === 'sqlite';

        $avgStartRaw = $isSqlite ? 'AVG(strftime("%s", data_inicio))' : 'AVG(UNIX_TIMESTAMP(data_inicio))';
        $avgStartSub = UserProgress::select('user_id', DB::raw("$avgStartRaw as avg_start_time"))
            ->groupBy('user_id');
        if (!$isGeneral) {
            $avgStartSub->whereYear('data_inicio', $year)->whereMonth('data_inicio', $month);
        }

        $query = RankingMonthlyScore::query()
            ->join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
            ->leftJoinSub($avgStartSub, 'st', 'users.id', '=', 'st.user_id')
            ->where('users.usuario_teste', false)
            ->where('users.status', 'ativo');

        if ($type !== 'all') {
            $query->where('users.tipo_usuario', $type);
        }

        if (!$isGeneral) {
            $query->where('ranking_monthly_scores.month_reference', $month)
                ->where('ranking_monthly_scores.year_reference', $year)
                ->select(
                    'users.id as user_id',
                    'users.nome',
                    'users.tipo_usuario',
                    'st.avg_start_time',
                    'ranking_monthly_scores.average_score'
                );
        } else {
            $query->select(
                'users.id as user_id',
                'users.nome',
                'users.tipo_usuario',
                'st.avg_start_time',
                DB::raw('SUM(ranking_monthly_scores.average_score) as average_score')
            )->groupBy('users.id', 'users.nome', 'users.tipo_usuario', 'st.avg_start_time');
        }

        $rows = $query->orderByDesc('average_score')
            ->orderBy(DB::raw('COALESCE(st.avg_start_time, 9999999999)'), 'asc')
            ->orderBy('users.nome', 'asc')
            ->limit($top > 0 ? $top : 100)
            ->get();

        $userIds = $rows->pluck('user_id')->toArray();
        $certsQuery = Certificate::whereIn('user_id', $userIds)->where('valido', true);
        if (!$isGeneral) {
            $certsQuery->whereMonth('data_emissao', $month)->whereYear('data_emissao', $year);
        }
        $certsMap = $certsQuery->select('user_id', DB::raw('COUNT(DISTINCT training_id) as total'))
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $ranking = [];
        $position = 0;
        $prevScore = null;
        $prevStart = null;
        foreach ($rows as $row) {
            if ($prevScore === null || $row->average_score != $prevScore || $row->avg_start_time != $prevStart) {
                $position++;
            }
            $ranking[] = [
                'position' => $position,
                'user_id' => $row->user_id,
                'nome' => $row->nome,
                'tipo_usuario' => $row->tipo_usuario,
                'score' => round((float) $row->average_score, 2),
                'certificados' => (int) ($certsMap->get($row->user_id) ?? 0),
            ];
            $prevScore = $row->average_score;
            $prevStart = $row->avg_start_time;
        }

        // Métricas de adesão
        $totalUsuariosElegiveis = User::kpiEligible()->count();

        $usuariosComCertificadoQuery = User::kpiEligible()->whereHas('certificates', function ($q) use ($month, $year, $isGeneral) {
            $q->where('valido', true);
            if (!$isGeneral) {
                $q->whereMonth('data_emissao', $month)->whereYear('data_emissao', $year);
            }
        });
        $usuariosComCertificadoCount = $usuariosComCertificadoQuery->count();
        $taxaAdesao = $totalUsuariosElegiveis > 0 ? round(($usuariosComCertificadoCount / $totalUsuariosElegiveis) * 100, 2) : 0;

        $totalCertificadosPeriodoQuery = Certificate::where('valido', true)
            ->whereHas('user', fn ($q) => $q->where('usuario_teste', false));
        if (!$isGeneral) {
            $totalCertificadosPeriodoQuery->whereYear('data_emissao', $year)->whereMonth('data_emissao', $month);
        }
        $totalCertificadosPeriodo = $totalCertificadosPeriodoQuery->count();

        return response()->json([
            'status' => 'success',
            'data' => [
                'month' => $month,
                'year' => $year,
                'is_general' => $isGeneral,
                'ranking' => $ranking,
                'taxa_adesao' => $taxaAdesao,
                'total_usuarios_elegiveis' => $totalUsuariosElegiveis,
                'usuarios_com_certificado' => $usuariosComCertificadoCount,
                'total_certificados_periodo' => $totalCertificadosPeriodo,
            ],
        ]);
    }

    public function recalculate(Request $request, RankingMonthlyConsolidationService $consolidation)
    {
        $user = $request->user();

        if (!$user->hasPermission('rankings', 'edit')) {
            return response()->json([
                'status' => 'error',
                'message' => 'Você não tem permissão para acessar este recurso.',
            ], 403);
        }

        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);

        if ($month === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'O recálculo manual não está disponível para o Ranking Geral.',
            ], 422);
        }

        try {
            $processedCount = $consolidation->consolidate($month, $year);

            return response()->json([
                'status' => 'success',
                'message' => "Ranking recalculado com sucesso! {$processedCount} usuários atualizados para {$month}/{$year}.",
            ]);
        } catch (\Throwable $e) {
            \Log::error('Erro ao recalcular ranking via API: ' . $e->getMessage());

            return response()->json([
                'status' => 'error',
                'message' => 'Falha ao processar o recálculo: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function calculateLevel(int $position): array
    {
        if ($position === 1) {
            return ['name' => 'Mítico', 'sub' => 'Comandante da Segurança', 'color' => '#7c3aed'];
        } elseif ($position <= 3) {
            return ['name' => 'Titã', 'sub' => 'Mestre da Prevenção', 'color' => '#ef4444'];
        } elseif ($position <= 10) {
            return ['name' => 'Imperial', 'sub' => 'Defensor Supremo', 'color' => '#f97316'];
        } elseif ($position <= 20) {
            return ['name' => 'Elite', 'sub' => 'Embaixador da Segurança', 'color' => '#0ea5e9'];
        } elseif ($position <= 35) {
            return ['name' => 'Prata', 'sub' => 'Agente Preventivo', 'color' => '#64748b'];
        }

        return ['name' => 'Bronze', 'sub' => 'Observador de Segurança', 'color' => '#b45309'];
    }
}
