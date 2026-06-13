<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\RankingRepository;
use Illuminate\Http\Request;
use App\Services\RankingRuleResolverService;
use App\Models\Certificate;
use App\Models\UserProgress;
use App\Models\User;
use App\Models\Training;
use App\Models\RankingMonthlyScore;
use App\Models\RankingScore;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

class RankingController extends Controller
{
    protected $repo;

    public function __construct(RankingRepository $repo)
    {
        $this->repo = $repo;
    }

    /**
     * Retorna a decomposição das pontuações para um usuário no período.
     * Resposta JSON: { trainings: [ { training_id, title, criteria: [{slug,label,points,value}], raw_score, max_possible, normalized } ] }
     */
    public function breakdown(Request $request, $userId, RankingRuleResolverService $resolver)
    {
        $month = $request->has('month') ? (int)$request->input('month') : (int)now()->month;
        $year = (int) $request->input('year', now()->year);

        $isGeneral = ($month === 0);
        
        $query = Certificate::with(['training', 'user'])
            ->where('user_id', $userId)
            ->where('valido', true);
            
        if ($month !== 0) {
            $query->whereYear('data_emissao', $year)
                  ->whereMonth('data_emissao', $month);
        }

        // Garantimos a deduplicação por ID de treinamento para evitar itens repetidos no detalhamento
        $certificates = $query->get()->unique('training_id')->values();

        $result = $certificates->map(function ($certificate) use ($resolver) {
            $training = $certificate->training;

            // calcular mesmos valores que o recalculo usa
            $releaseDate = $training->data_liberacao ?? $training->created_at; // Use created_at as fallback
            if ($releaseDate && $certificate->data_inicio_assistencia) {
                try { 
                    $diffInMinutes = $releaseDate->diffInMinutes($certificate->data_inicio_assistencia, false);
                    $startHours = round($diffInMinutes / 60, 1); // Decimal precision
                } catch (\Throwable $e) { $startHours = null; }
            }

            $completionDays = null;
            if ($certificate->data_inicio_assistencia && $certificate->data_finalizacao_assistencia) {
                try { $completionDays = $certificate->data_inicio_assistencia->diffInDays($certificate->data_finalizacao_assistencia); } catch (\Throwable $e) { $completionDays = null; }
            }

            $attempts = 1;
            try {
                $progress = $certificate->user?->progress()->where('training_id', $training->id)->orderByDesc('updated_at')->first();
                if ($progress && isset($progress->avaliacao_tentativas)) {
                    $t = (int) $progress->avaliacao_tentativas;
                    $attempts = $t > 0 ? ($t + 1) : 1;
                }
            } catch (\Throwable $e) {
                // ignore
            }

            $criteria = [
                'start_time' => $startHours ?? 9999,
                'completion_time' => $completionDays ?? 9999,
                'quiz_result' => $attempts,
            ];

            $perCriterion = [];
            $rawTotal = 0;
            $maxTotal = 0;

            // Cache estático para evitar consultas repetitivas de pontuação máxima dentro do loop
            static $maxScoreCache = [];

            foreach ($criteria as $slug => $value) {
                $rule = $resolver->resolveRule($slug, $value);
                $points = $rule ? (int) $rule->points : 0;
                $label = $rule ? $rule->label : '—';

                if ($rule && !isset($maxScoreCache[$rule->criterion_id])) {
                    $maxScoreCache[$rule->criterion_id] = (int) \App\Models\RankingRule::where('criterion_id', $rule->criterion_id)->max('points');
                }
                $maxForCriterion = $rule ? $maxScoreCache[$rule->criterion_id] : 0;

                $perCriterion[] = [
                    'slug' => $slug,
                    'label' => $label,
                    'points' => $points,
                    'value' => $value,
                    'min' => $rule?->min_value,
                    'max' => $rule?->max_value,
                ];

                $rawTotal += $points;
                $maxTotal += $maxForCriterion;
            }

            $normalized = $maxTotal > 0 ? round(($rawTotal / $maxTotal) * 100, 2) : 0;

            return [
                'training_id' => $training->id ?? null,
                'training_title' => $training->titulo ?? null,
                'criteria' => $perCriterion,
                'raw_score' => $rawTotal,
                'max_possible' => $maxTotal,
                'normalized' => $normalized,
            ];
        })->values();

        return response()->json(['trainings' => $result]);
    }

    public function index(Request $request, RankingRuleResolverService $resolver)
    {
        // Receber filtros. 0 no mês indica "Ranking Geral"
        $month = $request->has('month') ? (int)$request->input('month') : (int)now()->month;
        $year = $request->has('year') ? (int)$request->input('year') : (int)now()->year;
        $top = (int) $request->input('top', 20);
        $type = $request->input('type', 'all');

        $isGeneral = ($month === 0);
        $hasRealRanking = false;

        $driver = DB::getDriverName();
        $isSqlite = $driver === 'sqlite';

        // 1. Subconsulta para critério de desempate: Média do tempo de início no período selecionado
        $avgStartRaw = $isSqlite ? 'AVG(strftime("%s", data_inicio))' : 'AVG(UNIX_TIMESTAMP(data_inicio))';
        $avgStartSub = UserProgress::select('user_id', DB::raw("$avgStartRaw as avg_start_time"))
            ->groupBy('user_id');
        if (!$isGeneral) {
            $avgStartSub->whereYear('data_inicio', $year)->whereMonth('data_inicio', $month);
        }

        // --- CARREGAMENTO DO RANKING CONSOLIDADO ---
        try {
            $query = RankingMonthlyScore::query()
                ->join('users', 'ranking_monthly_scores.user_id', '=', 'users.id')
                ->leftJoinSub($avgStartSub, 'st', 'users.id', '=', 'st.user_id')
                ->where('users.usuario_teste', false)
                ->where('users.status', 'ativo');

            if ($type !== 'all') {
                $query->where('users.tipo_usuario', $type);
            }

            if (!$isGeneral) {
                // Ranking Mensal: Busca direta do mês selecionado
                $query->where('ranking_monthly_scores.month_reference', $month)
                      ->where('ranking_monthly_scores.year_reference', $year)
                      ->select(
                          'users.id as user_id', 'users.nome', 'users.tipo_usuario',
                          'st.avg_start_time',
                          'ranking_monthly_scores.average_score'
                      );
            } else {
                // Ranking Geral (Acumulado): Soma de todos os meses consolidados na tabela
                $query->select(
                    'users.id as user_id', 'users.nome', 'users.tipo_usuario',
                    'st.avg_start_time',
                    DB::raw('SUM(ranking_monthly_scores.average_score) as average_score')
                )
                ->groupBy('users.id', 'users.nome', 'users.tipo_usuario', 'st.avg_start_time');
            }

            $rows = $query->orderByDesc('average_score')
                ->orderBy(DB::raw('COALESCE(st.avg_start_time, 9999999999)'), 'asc')
                ->orderBy('users.nome', 'asc')
                ->limit($top > 0 ? $top : 100)
                ->get();

            // Obter contagem de certificados para a tabela (Participações)
            $userIds = $rows->pluck('user_id')->toArray();
            $certsQuery = Certificate::whereIn('user_id', $userIds)->where('valido', true);
            if (!$isGeneral) {
                $certsQuery->whereMonth('data_emissao', $month)->whereYear('data_emissao', $year);
            }
            $certsMap = $certsQuery->select('user_id', DB::raw('COUNT(DISTINCT training_id) as total'))
                ->groupBy('user_id')->pluck('total', 'user_id');

            $rows->each(function($row) use ($certsMap) {
                $user = new User();
                $user->id = $row->user_id; $user->nome = $row->nome; $user->tipo_usuario = $row->tipo_usuario;
                $row->setRelation('user', $user);
                $row->real_content_count = $certsMap->get($row->user_id) ?? 0;
            });
            $hasRealRanking = $rows->isNotEmpty();
        } catch (\Throwable $e) {
            \Log::error("Erro ao carregar ranking consolidado: " . $e->getMessage());
            $rows = collect();
            $hasRealRanking = false;
        }
        // --- MÉTRICAS DE ADESÃO PARA O PAINEL DE BI ---
        // 1. Definimos a base de usuários elegíveis (exclui testes, admins não participantes e férias atuais)
        $totalUsuariosElegiveis = User::kpiEligible()->count();

        // 2. Numerador: Apenas usuários ELEGÍVEIS que possuem certificados no período
        $usuariosComCertificadoQuery = User::kpiEligible()->whereHas('certificates', function ($q) use ($month, $year, $isGeneral) {
            $q->where('valido', true);
            if (!$isGeneral) {
                $q->whereMonth('data_emissao', $month)->whereYear('data_emissao', $year);
            }
        });

        $usuariosComCertificadoCount = $usuariosComCertificadoQuery->count();
        $taxaAdesao = $totalUsuariosElegiveis > 0 ? ($usuariosComCertificadoCount / $totalUsuariosElegiveis) * 100 : 0;

        // 3. Total de certificados brutos (apenas de usuários reais)
        $totalCertificadosPeriodoQuery = Certificate::where('valido', true)
            ->whereHas('user', function($q) { $q->where('usuario_teste', false); });
            
        if (!$isGeneral) {
            $totalCertificadosPeriodoQuery->whereYear('data_emissao', $year)->whereMonth('data_emissao', $month);
        }

        $totalCertificadosPeriodo = $totalCertificadosPeriodoQuery->count();

        // --- NOVAS MÉTRICAS DE ENGAJAMENTO DE ELITE ---
        
        // 1. Pioneiros (Velocidade): Menor tempo médio entre liberação e conclusão
        $pioneirosRaw = $isSqlite 
            ? 'AVG(strftime("%s", user_progress.data_inicio) - strftime("%s", COALESCE(trainings.data_liberacao, trainings.created_at)))'
            : 'AVG(UNIX_TIMESTAMP(user_progress.data_inicio) - UNIX_TIMESTAMP(COALESCE(trainings.data_liberacao, trainings.created_at)))';

        $pioneirosQuery = UserProgress::where('concluido', true)
            ->join('trainings', 'user_progress.training_id', '=', 'trainings.id')
            ->join('users', 'user_progress.user_id', '=', 'users.id')
            ->where('users.usuario_teste', false)
            ->where('users.status', 'ativo');
        
        if (!$isGeneral) {
            $pioneirosQuery->whereYear('user_progress.data_conclusao', $year)
                           ->whereMonth('user_progress.data_conclusao', $month);
        }

        $pioneiros = $pioneirosQuery->select(
                'user_id',
                DB::raw($pioneirosRaw . ' as tempo_reacao')
            )
            ->groupBy('user_id')
            ->with('user:id,nome,cargo')
            ->orderBy('tempo_reacao', 'asc')
            ->take(5)
            ->get();

        // 2. Focados (Fluidez): Assistiu o conteúdo sem interrupções significativas
        $focadosRaw = $isSqlite
            ? 'AVG(strftime("%s", data_conclusao) - strftime("%s", data_inicio))'
            : 'AVG(UNIX_TIMESTAMP(data_conclusao) - UNIX_TIMESTAMP(data_inicio))';

        $focadosQuery = UserProgress::where('concluido', true)
            ->join('users', 'user_progress.user_id', '=', 'users.id')
            ->where('users.usuario_teste', false)
            ->where('users.status', 'ativo');

        if (!$isGeneral) {
            $focadosQuery->whereYear('data_conclusao', $year)
                         ->whereMonth('data_conclusao', $month);
        }

        $focados = $focadosQuery->select('user_id', DB::raw($focadosRaw . ' as fluidez'))
            ->groupBy('user_id')
            ->with('user:id,nome,empresa')
            ->orderBy('fluidez', 'asc')
            ->take(5)
            ->get();

        // Insight para a Diretoria: Velocidade Média de Resposta (Empresa toda)
        // IMPORTANTE: Adicionado join com users para excluir usuários de teste da média global
        $mediaEmpresaQuery = UserProgress::where('concluido', true)
            ->join('trainings', 'user_progress.training_id', '=', 'trainings.id')
            ->join('users', 'user_progress.user_id', '=', 'users.id')
            ->where('users.usuario_teste', false);

        if (!$isGeneral) {
            $mediaEmpresaQuery->whereYear('user_progress.data_conclusao', $year)
                              ->whereMonth('user_progress.data_conclusao', $month);
        }

        $mediaEmpresaSegundos = $mediaEmpresaQuery->select(DB::raw($pioneirosRaw . ' as media'))
            ->first()->media ?? 0;
        $mediaEmpresa = $mediaEmpresaSegundos / 3600;

        return view('admin.ranking.index', compact(
            'rows', 'month', 'year', 'top', 'type', 'hasRealRanking', 
            'pioneiros', 'focados', 'mediaEmpresa', 'isGeneral', 
            'taxaAdesao', 'totalCertificadosPeriodo'
        ));
    }

    public function history(Request $request)
    {
        // Placeholder: implementar pesquisa por usuário/histórico
        return view('admin.ranking.history');
    }

    /**
     * Processa o recálculo de todos os usuários elegíveis e salva na tabela de scores mensais.
     * Inclui o tiebreaker_value (média do timestamp de data_inicio) e a position final com desempate.
     */
    public function recalculate(Request $request, RankingRuleResolverService $resolver)
    {   
        $month = (int) $request->input('month', now()->month);
        $year = (int) $request->input('year', now()->year);
        $isGeneral = ($month === 0);

        if ($isGeneral) {
            return redirect()->route('admin.ranking.index')
                ->with('error', 'O recálculo manual não está disponível para o Ranking Geral.');
        }

        try {
            // 1. Obter todos os usuários elegíveis para KPI
            $users = User::kpiEligible()->get();
            $processedCount = 0;
            $scores = []; // ['user_id' => ['score' => X, 'tiebreaker' => Y]]

            $driver = DB::getDriverName();
            $isSqlite = $driver === 'sqlite';

            // 2. Pré-carregar os tiebreakers de todos os usuários de uma vez (eficiência)
            $avgStartRaw = $isSqlite
                ? 'AVG(strftime("%s", data_inicio))'
                : 'AVG(UNIX_TIMESTAMP(data_inicio))';

            $tiebreakers = UserProgress::select('user_id', DB::raw("$avgStartRaw as avg_start_time"))
                ->whereYear('data_inicio', $year)
                ->whereMonth('data_inicio', $month)
                ->groupBy('user_id')
                ->pluck('avg_start_time', 'user_id');

            foreach ($users as $user) {
                // 3. Buscar certificados válidos no período
                $certificates = Certificate::with('training')
                    ->where('user_id', $user->id)
                    ->where('valido', true)
                    ->whereMonth('data_emissao', $month)
                    ->whereYear('data_emissao', $year)
                    ->get()
                    ->unique('training_id');

                $totalScore = 0;

                // 4. Calcular pontos para cada certificado (lógica idêntica ao breakdown/index)
                foreach ($certificates as $cert) {
                    $training = $cert->training;
                    if (!$training) continue;

                    $startHours = null;
                    $releaseDate = $training->data_liberacao ?? $training->created_at;
                    if ($releaseDate && $cert->data_inicio_assistencia) {
                        $diffInMinutes = $releaseDate->diffInMinutes($cert->data_inicio_assistencia, false);
                        $startHours = round($diffInMinutes / 60, 1);
                    }

                    $completionDays = null;
                    if ($cert->data_inicio_assistencia && $cert->data_finalizacao_assistencia) {
                        $completionDays = $cert->data_inicio_assistencia->diffInDays($cert->data_finalizacao_assistencia);
                    }

                    $attempts = 1;
                    $prog = UserProgress::where('user_id', $user->id)
                        ->where('training_id', $training->id)
                        ->orderByDesc('updated_at')
                        ->first();
                    if ($prog && isset($prog->avaliacao_tentativas)) {
                        $attempts = ($prog->avaliacao_tentativas > 0) ? ($prog->avaliacao_tentativas + 1) : 1;
                    }

                    $totalScore += $resolver->resolvePoints('start_time', $startHours ?? 9999) ?? 0;
                    $totalScore += $resolver->resolvePoints('completion_time', $completionDays ?? 9999) ?? 0;
                    $totalScore += $resolver->resolvePoints('quiz_result', $attempts) ?? 0;
                }

                $scores[$user->id] = [
                    'score'       => $totalScore,
                    'tiebreaker'  => isset($tiebreakers[$user->id]) ? (float) $tiebreakers[$user->id] : null,
                    'nome'        => $user->nome,
                ];
            }

            // 5. Ordenar com a mesma lógica da página admin para calcular positions corretas:
            //    1º maior score, 2º menor tiebreaker (iniciou mais cedo), 3º ordem alfabética
            uasort($scores, function ($a, $b) {
                if ($b['score'] !== $a['score']) {
                    return $b['score'] <=> $a['score'];
                }
                $ta = $a['tiebreaker'] ?? PHP_INT_MAX;
                $tb = $b['tiebreaker'] ?? PHP_INT_MAX;
                if ($ta !== $tb) {
                    return $ta <=> $tb;
                }
                return strcmp($a['nome'], $b['nome']);
            });

            // 6. Atribuir posições respeitando empates verdadeiros
            //    (mesma posição apenas se score E tiebreaker E nome forem iguais — na prática, só score e tiebreaker)
            $position = 0;
            $rankPos = 0;
            $lastScore = null;
            $lastTiebreaker = null;

            foreach ($scores as $userId => $data) {
                $rankPos++;
                if (
                    $lastScore === null
                    || $data['score'] !== $lastScore
                    || $data['tiebreaker'] !== $lastTiebreaker
                ) {
                    $position = $rankPos;
                }

                \Log::info("RankingController@recalculate: user {$userId}, score {$data['score']}, tiebreaker {$data['tiebreaker']}, position {$position}");

                RankingMonthlyScore::updateOrCreate(
                    ['user_id' => $userId, 'month_reference' => $month, 'year_reference' => $year],
                    [
                        'average_score'    => $data['score'],
                        'tiebreaker_value' => $data['tiebreaker'],
                        'position'         => $position,
                    ]
                );

                $lastScore = $data['score'];
                $lastTiebreaker = $data['tiebreaker'];
                $processedCount++;
            }

            return redirect()->route('admin.ranking.index')
                ->with('success', "Ranking recalculado com sucesso! {$processedCount} usuários atualizados para {$month}/{$year}.");
        } catch (\Throwable $e) {
            \Log::error('Erro ao recalcular ranking web: ' . $e->getMessage());
            return redirect()->route('admin.ranking.index')
                ->with('error', 'Falha ao processar o recálculo: ' . $e->getMessage());
        }
    }
}
