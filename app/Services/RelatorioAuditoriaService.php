<?php

namespace App\Services;

use App\Models\AuditLog;
use App\Models\Certificate;
use App\Models\FolgaDia;
use App\Models\Training;
use App\Models\TrainingVacationExemption;
use App\Models\User;
use App\Models\UserProgress;
use App\Models\UserVacation;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Núcleo de cálculo do relatório de auditoria gerencial.
 *
 * Base de cálculo: exclui SOMENTE super_admin e usuários marcados como teste.
 * Ausências justificadas (férias, folgas, atestados, licenças, isenções de
 * conteúdo e prazos estendidos) são detalhadas em painel próprio e excluídas
 * do denominador de conformidade por padrão (configurável por filtro).
 */
class RelatorioAuditoriaService
{
    private const LIMITE_EXPORTACAO = 50000;

    /** @var array<int, array<string, mixed>> */
    private array $statsCache = [];

    private ?array $ausenciasCache = null;

    private ?array $trilhaCache = null;

    private ?Collection $linhasDetalhadoCache = null;

    public function __construct(
        private Request $request,
        private User $viewer
    ) {}

    // =========================================================================
    // Filtros
    // =========================================================================

    /**
     * @return array{0:?Carbon,1:?Carbon}
     */
    public function periodo(): array
    {
        try {
            $inicio = $this->request->filled('periodo_inicio')
                ? Carbon::parse($this->request->input('periodo_inicio'))->startOfDay()
                : null;
            $fim = $this->request->filled('periodo_fim')
                ? Carbon::parse($this->request->input('periodo_fim'))->endOfDay()
                : null;
        } catch (\Throwable) {
            return [null, null];
        }

        return [$inicio, $fim];
    }

    public function incluirAfastadosNoDenominador(): bool
    {
        return $this->request->boolean('incluir_afastados');
    }

    private function aplicarFiltrosUsuario(Builder $query): void
    {
        $request = $this->request;

        if ($request->filled('usuario_id')) {
            $query->where('id', $request->integer('usuario_id'));
        } elseif ($request->filled('busca')) {
            $busca = trim((string) $request->input('busca'));
            $cpf = preg_replace('/\D/', '', $busca);

            $query->where(function ($q) use ($busca, $cpf) {
                $q->where('nome', 'like', '%'.$busca.'%');

                if ($cpf !== '') {
                    $q->orWhere('cpf', 'like', '%'.$cpf.'%');
                }
            });
        }

        if ($request->filled('tipo_usuario')) {
            $query->where('tipo_usuario', $request->input('tipo_usuario'));
        }

        if ($request->filled('status')) {
            $query->where('status', $request->input('status'));
        }

        if ($request->filled('setor')) {
            $query->where('setor', 'like', '%'.trim((string) $request->input('setor')).'%');
        }

        if ($request->filled('cargo')) {
            $query->where('cargo', 'like', '%'.trim((string) $request->input('cargo')).'%');
        }

        if ($request->filled('empresa')) {
            $query->where('empresa', 'like', '%'.trim((string) $request->input('empresa')).'%');
        }
    }

    public function usuariosQuery(): Builder
    {
        $query = User::query()->auditEligible()->visibleToViewer($this->viewer);
        $this->aplicarFiltrosUsuario($query);

        return $query;
    }

    public function treinamentosQuery(): Builder
    {
        $request = $this->request;

        return Training::query()
            ->when($request->filled('training_id'), fn ($q) => $q->where('id', $request->integer('training_id')))
            ->when($request->filled('training_tipo'), fn ($q) => $q->where('tipo', $request->input('training_tipo')))
            ->when($request->filled('tipo_treinamento'), fn ($q) => $q->where('tipo_treinamento', $request->input('tipo_treinamento')))
            ->when($request->boolean('somente_obrigatorios'), fn ($q) => $q->where('obrigatorio', true))
            ->orderByReleaseDate('desc');
    }

    /**
     * @return array{super_admin:int,teste:int,total:int}
     */
    public function metricasExclusao(): array
    {
        $superAdmins = User::query()->whereHas('role', fn ($q) => $q->where('nome', 'super_admin'))->count();
        $testes = User::query()->where('usuario_teste', true)->count();
        $total = User::query()
            ->where(function ($q) {
                $q->where('usuario_teste', true)
                    ->orWhereHas('role', fn ($role) => $role->where('nome', 'super_admin'));
            })
            ->count();

        return ['super_admin' => $superAdmins, 'teste' => $testes, 'total' => $total];
    }

    /**
     * @return array<string, string>
     */
    public function filtrosAtivos(): array
    {
        $request = $this->request;
        $labels = [];

        if ($request->filled('periodo_inicio') || $request->filled('periodo_fim')) {
            $labels['Período'] = ($request->filled('periodo_inicio') ? Carbon::parse($request->input('periodo_inicio'))->format('d/m/Y') : '...')
                .' a '.($request->filled('periodo_fim') ? Carbon::parse($request->input('periodo_fim'))->format('d/m/Y') : '...');
        }

        if ($request->filled('tipo_usuario')) {
            $labels['Tipo de usuário'] = User::tipoUsuarioLabel($request->input('tipo_usuario'));
        }

        if ($request->filled('status')) {
            $labels['Status'] = ucfirst($request->input('status'));
        }

        if ($request->filled('busca')) {
            $labels['Busca'] = $request->input('busca');
        }

        if ($request->filled('setor')) {
            $labels['Setor'] = $request->input('setor');
        }

        if ($request->filled('cargo')) {
            $labels['Cargo'] = $request->input('cargo');
        }

        if ($request->filled('empresa')) {
            $labels['Empresa'] = $request->input('empresa');
        }

        if ($request->filled('training_id')) {
            $labels['Treinamento'] = optional(Training::find($request->integer('training_id')))->titulo ?? ('#'.$request->input('training_id'));
        }

        if ($request->filled('training_tipo')) {
            $labels['Tipo de conteúdo'] = strtoupper($request->input('training_tipo'));
        }

        if ($request->filled('tipo_treinamento')) {
            $labels['Tipo de treinamento'] = ucfirst($request->input('tipo_treinamento'));
        }

        if ($request->boolean('somente_obrigatorios')) {
            $labels['Somente obrigatórios'] = 'Sim';
        }

        $labels['Afastados no denominador'] = $this->incluirAfastadosNoDenominador() ? 'Incluídos' : 'Excluídos (padrão)';

        return $labels;
    }

    // =========================================================================
    // Estatísticas por conteúdo
    // =========================================================================

    /**
     * @return array<string, mixed>
     */
    public function statsConteudo(Training $training): array
    {
        if (isset($this->statsCache[$training->id])) {
            return $this->statsCache[$training->id];
        }

        $elegiveisQuery = fn () => $this->usuariosQuery()->eligibleForContent($training);
        $elegiveis = $elegiveisQuery()->count();

        $progressos = UserProgress::query()
            ->where('training_id', $training->id)
            ->whereIn('user_id', $elegiveisQuery()->select('users.id'))
            ->get(['user_id', 'concluido', 'data_conclusao', 'prazo_estendido_ate']);

        $prazoBase = $training->prazoBaseConclusao();
        $agora = Carbon::now(config('app.timezone'));

        $emDia = $atraso = $vencidos = $andamento = 0;

        foreach ($progressos as $progresso) {
            $prazo = $progresso->prazo_estendido_ate ?? $prazoBase;

            if ($progresso->concluido) {
                if ($prazo && $progresso->data_conclusao && $progresso->data_conclusao->gt($prazo)) {
                    $atraso++;
                } else {
                    $emDia++;
                }
            } elseif ($prazo && $agora->gt($prazo)) {
                $vencidos++;
            } else {
                $andamento++;
            }
        }

        $isentos = TrainingVacationExemption::query()
            ->where('training_id', $training->id)
            ->whereHas('user', function ($q) {
                $q->auditEligible()->visibleToViewer($this->viewer);
                $this->aplicarFiltrosUsuario($q);
            })
            ->count();

        $afastadosLiberacao = $this->contarAfastadosNaLiberacao($training);

        $stats = [
            'elegiveis' => $elegiveis,
            'concluidos' => $emDia + $atraso,
            'em_dia' => $emDia,
            'atraso' => $atraso,
            'andamento' => $andamento,
            'nao_iniciados' => max(0, $elegiveis - $progressos->count()),
            'vencidos' => $vencidos,
            'isentos' => $isentos,
            'afastados_liberacao' => $afastadosLiberacao,
            'conformidade' => $elegiveis > 0 ? ($emDia / $elegiveis) * 100 : 0.0,
        ];

        return $this->statsCache[$training->id] = $stats;
    }

    /**
     * Usuários que seriam elegíveis ao conteúdo mas estavam de férias na
     * data de liberação (mesmas regras de eligibleForContent, sem o corte
     * por férias, para evidenciar a justificativa).
     */
    private function contarAfastadosNaLiberacao(Training $training): int
    {
        $dataLiberacao = $training->releaseDate();

        if (! $dataLiberacao) {
            return 0;
        }

        $base = $this->usuariosQuery()->eligibleForTrainingKpi($training);

        if ($training->tipo === 'treinamento') {
            $base->whereHas('assignedTrainings', fn ($q) => $q->where('trainings.id', $training->id));
        } else {
            $permitidos = $training->tipo_usuario_permitido;

            if (is_string($permitidos)) {
                $permitidos = json_decode($permitidos, true);
            }

            if (is_array($permitidos) && ! in_array('todos', $permitidos, true)) {
                $base->whereIn('tipo_usuario', User::expandirTiposUsuario($permitidos));
            }
        }

        $base->whereDoesntHave('trainingExemptions', fn ($q) => $q->where('training_id', $training->id));

        $data = $dataLiberacao->toDateString();

        return (clone $base)
            ->whereHas('vacations', function ($q) use ($data) {
                $q->whereDate('data_inicio', '<=', $data)
                    ->whereDate('data_fim', '>=', $data);
            })
            ->count();
    }

    // =========================================================================
    // Resumo geral
    // =========================================================================

    /**
     * @return array<string, mixed>
     */
    public function resumo(): array
    {
        $baseUsuarios = $this->usuariosQuery();
        $totalUsuarios = (clone $baseUsuarios)->count();
        $totalAtivos = (clone $baseUsuarios)->where('status', 'ativo')->count();

        $treinamentos = $this->treinamentosQuery()->get();

        $paresElegiveis = $concluidosEmDia = $concluidosAtraso = $andamento = 0;
        $naoIniciados = $vencidos = $isentos = $afastadosLiberacao = 0;

        foreach ($treinamentos as $training) {
            $stats = $this->statsConteudo($training);
            $paresElegiveis += $stats['elegiveis'];
            $concluidosEmDia += $stats['em_dia'];
            $concluidosAtraso += $stats['atraso'];
            $andamento += $stats['andamento'];
            $naoIniciados += $stats['nao_iniciados'];
            $vencidos += $stats['vencidos'];
            $isentos += $stats['isentos'];
            $afastadosLiberacao += $stats['afastados_liberacao'];
        }

        $denominador = $paresElegiveis;
        if ($this->incluirAfastadosNoDenominador()) {
            $denominador += $isentos + $afastadosLiberacao;
        }

        [$inicio, $fim] = $this->periodo();

        $progressoQuery = fn () => UserProgress::query()->whereHas('user', function ($q) {
            $q->auditEligible()->visibleToViewer($this->viewer);
            $this->aplicarFiltrosUsuario($q);
        })->when($inicio && $fim, fn ($q) => $q->whereBetween('data_inicio', [$inicio, $fim]))
            ->when($inicio && ! $fim, fn ($q) => $q->where('data_inicio', '>=', $inicio))
            ->when(! $inicio && $fim, fn ($q) => $q->where('data_inicio', '<=', $fim));

        $participacoes = $progressoQuery()->count();
        $tempoTotal = (int) $progressoQuery()->sum('tempo_assistido');

        $certificados = $this->resumoCertificados($inicio, $fim);
        $ausencias = $this->ausencias();

        return [
            'total_usuarios' => $totalUsuarios,
            'total_ativos' => $totalAtivos,
            'total_inativos' => max(0, $totalUsuarios - $totalAtivos),
            'total_treinamentos' => $treinamentos->count(),
            'pares_elegiveis' => $paresElegiveis,
            'concluidos_em_dia' => $concluidosEmDia,
            'concluidos_atraso' => $concluidosAtraso,
            'andamento' => $andamento,
            'nao_iniciados' => $naoIniciados,
            'vencidos' => $vencidos,
            'isentos' => $isentos,
            'afastados_liberacao' => $afastadosLiberacao,
            'indice_conformidade' => $denominador > 0 ? ($concluidosEmDia / $denominador) * 100 : 0.0,
            'participacoes' => $participacoes,
            'tempo_total_segundos' => $tempoTotal,
            'tempo_medio_segundos' => $participacoes > 0 ? (int) round($tempoTotal / $participacoes) : 0,
            'certificados' => $certificados,
            'ausencias' => $ausencias['totais'],
        ];
    }

    /**
     * @return array{total:int,validos:int,vencendo:int,vencidos:int,sem_validade:int}
     */
    private function resumoCertificados(?Carbon $inicio, ?Carbon $fim): array
    {
        $query = Certificate::query()
            ->where('valido', true)
            ->whereHas('user', function ($q) {
                $q->auditEligible()->visibleToViewer($this->viewer);
                $this->aplicarFiltrosUsuario($q);
            })
            ->when($this->request->filled('training_id'), fn ($q) => $q->where('training_id', $this->request->integer('training_id')))
            ->when($inicio && $fim, fn ($q) => $q->whereBetween('data_emissao', [$inicio, $fim]))
            ->when($inicio && ! $fim, fn ($q) => $q->where('data_emissao', '>=', $inicio))
            ->when(! $inicio && $fim, fn ($q) => $q->where('data_emissao', '<=', $fim))
            ->with('training:id,dias_validade');

        $resumo = ['total' => 0, 'validos' => 0, 'vencendo' => 0, 'vencidos' => 0, 'sem_validade' => 0];

        $query->chunk(2000, function ($certificados) use (&$resumo) {
            foreach ($certificados as $certificado) {
                $resumo['total']++;
                $resumo[match ($certificado->status_validade) {
                    'valido' => 'validos',
                    'vencendo' => 'vencendo',
                    'vencido' => 'vencidos',
                    default => 'sem_validade',
                }]++;
            }
        });

        return $resumo;
    }

    // =========================================================================
    // Tabelas paginadas
    // =========================================================================

    public function conteudosPaginados(): LengthAwarePaginator
    {
        $paginator = $this->treinamentosQuery()->paginate(20, ['*'], 'page_conteudos')->withQueryString();

        $paginator->getCollection()->transform(function (Training $training) {
            return ['training' => $training, 'stats' => $this->statsConteudo($training)];
        });

        return $paginator;
    }

    public function usuariosPaginados(): LengthAwarePaginator
    {
        $paginator = $this->usuariosQuery()->orderBy('nome')->paginate(25, ['*'], 'page_usuarios')->withQueryString();
        $usuarios = $paginator->getCollection();

        if ($usuarios->isEmpty()) {
            return $paginator;
        }

        $usuarios->load([
            'assignedTrainings:id',
            'trainingExemptions:id,user_id,training_id',
            'vacations:id,user_id,data_inicio,data_fim',
        ]);

        $treinos = $this->treinamentosParaConformidade();
        $treinoIds = $treinos->pluck('id');
        $userIds = $usuarios->pluck('id');
        [$inicio, $fim] = $this->periodo();
        $agora = Carbon::now(config('app.timezone'));

        $progressos = UserProgress::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('training_id', $treinoIds)
            ->get(['user_id', 'training_id', 'concluido', 'data_conclusao', 'prazo_estendido_ate'])
            ->groupBy('user_id');

        $certificados = Certificate::query()
            ->whereIn('user_id', $userIds)
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->pluck('total', 'user_id');

        $folgas = FolgaDia::query()
            ->whereIn('user_id', $userIds)
            ->when($inicio && $fim, fn ($q) => $q->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()]))
            ->when($inicio && ! $fim, fn ($q) => $q->whereDate('data', '>=', $inicio->toDateString()))
            ->when(! $inicio && $fim, fn ($q) => $q->whereDate('data', '<=', $fim->toDateString()))
            ->selectRaw('user_id, tipo, COUNT(*) as dias')
            ->groupBy('user_id', 'tipo')
            ->get()
            ->groupBy('user_id');

        foreach ($usuarios as $usuario) {
            $counters = [
                'elegiveis' => 0, 'concluidos' => 0, 'em_dia' => 0, 'atraso' => 0,
                'andamento' => 0, 'nao_iniciados' => 0, 'vencidos' => 0,
                'isentos' => 0, 'fora' => 0,
            ];
            $ultimaAtividade = null;
            $progressoUsuario = $progressos->get($usuario->id) ?? collect();

            foreach ($treinos as $training) {
                $progresso = $progressoUsuario->firstWhere('training_id', $training->id);

                if (! $this->elegivelEmMemoria($usuario, $training)) {
                    if ($usuario->trainingExemptions->contains('training_id', $training->id)) {
                        $counters['isentos']++;
                    } else {
                        $counters['fora']++;
                    }

                    continue;
                }

                $counters['elegiveis']++;

                if (! $progresso) {
                    $counters['nao_iniciados']++;

                    continue;
                }

                $prazo = $progresso->prazo_estendido_ate ?? $training->prazoBaseConclusao();

                if ($progresso->concluido) {
                    $counters['concluidos']++;

                    if ($prazo && $progresso->data_conclusao && $progresso->data_conclusao->gt($prazo)) {
                        $counters['atraso']++;
                    } else {
                        $counters['em_dia']++;
                    }

                    if ($progresso->data_conclusao && (! $ultimaAtividade || $progresso->data_conclusao->gt($ultimaAtividade))) {
                        $ultimaAtividade = $progresso->data_conclusao;
                    }
                } elseif ($prazo && $agora->gt($prazo)) {
                    $counters['vencidos']++;
                } else {
                    $counters['andamento']++;
                }
            }

            $usuario->conformidade = $counters;
            $usuario->ultima_atividade = $ultimaAtividade;
            $usuario->certificados_total = (int) ($certificados[$usuario->id] ?? 0);
            $usuario->afastamento_periodo = $this->rotuloAfastamento(
                $usuario,
                $folgas->get($usuario->id) ?? collect(),
                $inicio,
                $fim
            );
        }

        return $paginator;
    }

    /**
     * Lista, para um conteúdo específico, os usuários elegíveis e o status
     * atual de cada um (concluído em dia/atraso, andamento, vencido, não iniciado).
     */
    public function usuariosDoConteudo(Training $training): LengthAwarePaginator
    {
        $paginator = $this->usuariosQuery()
            ->eligibleForContent($training)
            ->orderBy('nome')
            ->paginate(50, ['*'], 'page_conteudo')
            ->withQueryString();

        $userIds = $paginator->getCollection()->pluck('id');

        if ($userIds->isEmpty()) {
            return $paginator;
        }

        $progressos = UserProgress::query()
            ->where('training_id', $training->id)
            ->whereIn('user_id', $userIds)
            ->get(['user_id', 'concluido', 'data_inicio', 'data_conclusao', 'tempo_assistido', 'prazo_estendido_ate', 'avaliacao_nota', 'avaliacao_aprovada'])
            ->keyBy('user_id');

        $certificados = Certificate::query()
            ->whereIn('user_id', $userIds)
            ->where('training_id', $training->id)
            ->get()
            ->keyBy('user_id');

        $prazoBase = $training->prazoBaseConclusao();
        $agora = Carbon::now(config('app.timezone'));

        foreach ($paginator->getCollection() as $usuario) {
            $progresso = $progressos->get($usuario->id);
            $prazo = $progresso?->prazo_estendido_ate ?? $prazoBase;

            $situacao = 'nao_iniciado';

            if ($progresso) {
                if ($progresso->concluido) {
                    $situacao = ($prazo && $progresso->data_conclusao && $progresso->data_conclusao->gt($prazo))
                        ? 'concluido_atraso'
                        : 'concluido_em_dia';
                } elseif ($prazo && $agora->gt($prazo)) {
                    $situacao = 'vencido';
                } else {
                    $situacao = 'andamento';
                }
            }

            $usuario->progresso_conteudo = $progresso;
            $usuario->situacao_conteudo = $situacao;
            $usuario->prazo_conteudo = $prazo;
            $usuario->certificado_conteudo = $certificados->get($usuario->id);
        }

        return $paginator;
    }

    /**
     * @return Collection<int, Training>
     */
    private function treinamentosParaConformidade(): Collection
    {
        if ($this->request->filled('training_id')) {
            return Training::query()->where('id', $this->request->integer('training_id'))->get();
        }

        $obrigatorios = $this->treinamentosQuery()
            ->where('obrigatorio', true)
            ->get();

        return $obrigatorios->isNotEmpty() ? $obrigatorios : $this->treinamentosQuery()->get();
    }

    /**
     * Elegibilidade em memória (mesma semântica de User::isEligibleForContent)
     * usando relações pré-carregadas, para evitar consultas por par usuário×conteúdo.
     */
    private function elegivelEmMemoria(User $usuario, Training $training): bool
    {
        $dataLiberacao = $training->releaseDate();

        if ($dataLiberacao && $usuario->created_at) {
            $maxCadastro = Carbon::parse($dataLiberacao, config('app.timezone'))
                ->endOfWeek(Carbon::SUNDAY)
                ->endOfDay();

            if ($usuario->created_at->gt($maxCadastro)) {
                return false;
            }
        }

        if ($training->tipo === 'treinamento') {
            if (! $usuario->assignedTrainings->contains('id', $training->id)) {
                return false;
            }
        } else {
            $permitidos = $training->tipo_usuario_permitido;

            if (is_string($permitidos)) {
                $permitidos = json_decode($permitidos, true);
            }

            if (is_array($permitidos) && ! in_array('todos', $permitidos, true)
                && ! in_array($usuario->tipo_usuario, User::expandirTiposUsuario($permitidos), true)) {
                return false;
            }
        }

        if ($usuario->trainingExemptions->contains('training_id', $training->id)) {
            return false;
        }

        if ($dataLiberacao) {
            $data = Carbon::parse($dataLiberacao)->toDateString();

            if ($usuario->vacations->contains(fn ($ferias) => $ferias->data_inicio->toDateString() <= $data && $ferias->data_fim->toDateString() >= $data)) {
                return false;
            }
        }

        return true;
    }

    // =========================================================================
    // Ausências e justificativas
    // =========================================================================

    /**
     * @return array{totais:array<string,int>, ferias:Collection, folgas:Collection, isencoes:Collection, prorrogacoes:Collection}
     */
    public function ausencias(): array
    {
        if ($this->ausenciasCache !== null) {
            return $this->ausenciasCache;
        }

        [$inicio, $fim] = $this->periodo();

        // ----- Férias (histórico user_vacations) -----
        $ferias = UserVacation::query()
            ->whereHas('user', function ($q) {
                $q->auditEligible()->visibleToViewer($this->viewer);
                $this->aplicarFiltrosUsuario($q);
            })
            ->when($inicio && $fim, fn ($q) => $q->whereDate('data_inicio', '<=', $fim->toDateString())->whereDate('data_fim', '>=', $inicio->toDateString()))
            ->when($inicio && ! $fim, fn ($q) => $q->whereDate('data_fim', '>=', $inicio->toDateString()))
            ->when(! $inicio && $fim, fn ($q) => $q->whereDate('data_inicio', '<=', $fim->toDateString()))
            ->with('user:id,nome,cpf,tipo_usuario,setor,cargo,empresa')
            ->orderByDesc('data_inicio')
            ->get()
            ->map(function (UserVacation $ferias) use ($inicio, $fim) {
                $ferias->dias_periodo = $this->diasSobreposicao($ferias->data_inicio, $ferias->data_fim, $inicio, $fim);

                return $ferias;
            });

        // ----- Folgas, atestados e licenças (folga_dias) -----
        $folgas = FolgaDia::query()
            ->whereHas('user', function ($q) {
                $q->auditEligible()->visibleToViewer($this->viewer);
                $this->aplicarFiltrosUsuario($q);
            })
            ->whereIn('tipo', ['folga', 'atestado', 'licenca'])
            ->when($inicio && $fim, fn ($q) => $q->whereBetween('data', [$inicio->toDateString(), $fim->toDateString()]))
            ->when($inicio && ! $fim, fn ($q) => $q->whereDate('data', '>=', $inicio->toDateString()))
            ->when(! $inicio && $fim, fn ($q) => $q->whereDate('data', '<=', $fim->toDateString()))
            ->with('user:id,nome,cpf,tipo_usuario,setor,cargo,empresa')
            ->orderByDesc('data')
            ->get();

        // ----- Isenções de conteúdo por férias -----
        $isencoes = TrainingVacationExemption::query()
            ->whereHas('user', function ($q) {
                $q->auditEligible()->visibleToViewer($this->viewer);
                $this->aplicarFiltrosUsuario($q);
            })
            ->when($inicio && $fim, fn ($q) => $q->whereDate('data_inicio', '<=', $fim->toDateString())->whereDate('data_fim', '>=', $inicio->toDateString()))
            ->when($inicio && ! $fim, fn ($q) => $q->whereDate('data_fim', '>=', $inicio->toDateString()))
            ->when(! $inicio && $fim, fn ($q) => $q->whereDate('data_inicio', '<=', $fim->toDateString()))
            ->with(['user:id,nome,cpf,tipo_usuario', 'training:id,titulo', 'creator:id,nome'])
            ->orderByDesc('data_inicio')
            ->get();

        // ----- Prazos estendidos (prorrogações concedidas) -----
        $prorrogacoes = UserProgress::query()
            ->whereNotNull('prazo_estendido_ate')
            ->whereHas('user', function ($q) {
                $q->auditEligible()->visibleToViewer($this->viewer);
                $this->aplicarFiltrosUsuario($q);
            })
            ->when($inicio && $fim, fn ($q) => $q->whereBetween('prazo_estendido_em', [$inicio, $fim]))
            ->when($inicio && ! $fim, fn ($q) => $q->where('prazo_estendido_em', '>=', $inicio))
            ->when(! $inicio && $fim, fn ($q) => $q->where('prazo_estendido_em', '<=', $fim))
            ->with(['user:id,nome,cpf,tipo_usuario', 'training:id,titulo'])
            ->orderByDesc('prazo_estendido_em')
            ->get();

        $porTipoFolga = $folgas->groupBy('tipo')->map->count();
        $pessoasFerias = $ferias->pluck('user_id')->unique()->count();

        $totais = [
            'ferias_pessoas' => $pessoasFerias,
            'ferias_dias' => (int) $ferias->sum('dias_periodo'),
            'folgas_dias' => (int) ($porTipoFolga['folga'] ?? 0),
            'atestados_dias' => (int) ($porTipoFolga['atestado'] ?? 0),
            'licencas_dias' => (int) ($porTipoFolga['licenca'] ?? 0),
            'folgas_pessoas' => (int) ($folgas->where('tipo', 'folga')->pluck('user_id')->unique()->count()),
            'atestados_pessoas' => (int) ($folgas->where('tipo', 'atestado')->pluck('user_id')->unique()->count()),
            'licencas_pessoas' => (int) ($folgas->where('tipo', 'licenca')->pluck('user_id')->unique()->count()),
            'isencoes_total' => $isencoes->count(),
            'prorrogacoes_total' => $prorrogacoes->count(),
        ];

        return $this->ausenciasCache = [
            'totais' => $totais,
            'ferias' => $ferias,
            'folgas' => $folgas,
            'isencoes' => $isencoes,
            'prorrogacoes' => $prorrogacoes,
        ];
    }

    private function rotuloAfastamento(User $usuario, Collection $folgasDoUsuario, ?Carbon $inicio, ?Carbon $fim): string
    {
        $partes = [];

        $ferias = $usuario->vacations
            ->filter(fn ($v) => $this->periodoIntersecta($v->data_inicio, $v->data_fim, $inicio, $fim))
            ->map(fn ($v) => $v->data_inicio->format('d/m').' a '.$v->data_fim->format('d/m'));

        if ($ferias->isNotEmpty()) {
            $partes[] = 'Férias '.$ferias->implode(', ');
        }

        foreach (['atestado' => 'Atestado', 'licenca' => 'Licença', 'folga' => 'Folga'] as $tipo => $label) {
            $dias = (int) ($folgasDoUsuario->firstWhere('tipo', $tipo)->dias ?? 0);

            if ($dias > 0) {
                $partes[] = $label.' '.$dias.'d';
            }
        }

        return $partes === [] ? '' : implode(' • ', $partes);
    }

    private function periodoIntersecta(Carbon $inicioEvento, Carbon $fimEvento, ?Carbon $inicio, ?Carbon $fim): bool
    {
        if ($inicio && $fimEvento->lt($inicio)) {
            return false;
        }

        if ($fim && $inicioEvento->gt($fim)) {
            return false;
        }

        return true;
    }

    private function diasSobreposicao(Carbon $inicioEvento, Carbon $fimEvento, ?Carbon $inicio, ?Carbon $fim): int
    {
        $de = $inicio && $inicio->gt($inicioEvento) ? $inicio->copy() : $inicioEvento->copy();
        $ate = $fim && $fim->lt($fimEvento) ? $fim->copy() : $fimEvento->copy();

        if ($ate->lt($de)) {
            return 0;
        }

        return (int) $de->diffInDays($ate) + 1;
    }

    // =========================================================================
    // Evolução mensal
    // =========================================================================

    /**
     * @return array{participacoes:Collection,certificados:Collection}
     */
    public function evolucao(): array
    {
        $driver = DB::connection()->getDriverName();
        [$inicio, $fim] = $this->periodo();

        $exprProgresso = $driver === 'mysql'
            ? "DATE_FORMAT(data_inicio, '%Y-%m')"
            : "strftime('%Y-%m', data_inicio)";

        $participacoes = UserProgress::query()
            ->whereHas('user', function ($q) {
                $q->auditEligible()->visibleToViewer($this->viewer);
                $this->aplicarFiltrosUsuario($q);
            })
            ->whereNotNull('data_inicio')
            ->when($inicio && $fim, fn ($q) => $q->whereBetween('data_inicio', [$inicio, $fim]))
            ->when($inicio && ! $fim, fn ($q) => $q->where('data_inicio', '>=', $inicio))
            ->when(! $inicio && $fim, fn ($q) => $q->where('data_inicio', '<=', $fim))
            ->selectRaw("{$exprProgresso} as periodo, COUNT(*) as total")
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        $exprCertificado = $driver === 'mysql'
            ? "DATE_FORMAT(data_emissao, '%Y-%m')"
            : "strftime('%Y-%m', data_emissao)";

        $certificados = Certificate::query()
            ->where('valido', true)
            ->whereHas('user', function ($q) {
                $q->auditEligible()->visibleToViewer($this->viewer);
                $this->aplicarFiltrosUsuario($q);
            })
            ->whereNotNull('data_emissao')
            ->when($inicio && $fim, fn ($q) => $q->whereBetween('data_emissao', [$inicio, $fim]))
            ->when($inicio && ! $fim, fn ($q) => $q->where('data_emissao', '>=', $inicio))
            ->when(! $inicio && $fim, fn ($q) => $q->where('data_emissao', '<=', $fim))
            ->selectRaw("{$exprCertificado} as periodo, COUNT(*) as total")
            ->groupBy('periodo')
            ->orderBy('periodo')
            ->get();

        return ['participacoes' => $participacoes, 'certificados' => $certificados];
    }

    // =========================================================================
    // Trilha de auditoria (audit_logs)
    // =========================================================================

    public function podeVerTrilha(): bool
    {
        return $this->viewer->isSuperAdmin() || $this->viewer->hasPermission('auditoria', 'view');
    }

    /**
     * @return array<string, mixed>|null
     */
    public function trilha(): ?array
    {
        if (! $this->podeVerTrilha()) {
            return null;
        }

        if ($this->trilhaCache !== null) {
            return $this->trilhaCache;
        }

        [$inicio, $fim] = $this->periodo();

        $base = fn () => AuditLog::query()
            ->when($inicio && $fim, fn ($q) => $q->whereBetween('created_at', [$inicio, $fim]))
            ->when($inicio && ! $fim, fn ($q) => $q->where('created_at', '>=', $inicio))
            ->when(! $inicio && $fim, fn ($q) => $q->where('created_at', '<=', $fim));

        $total = $base()->count();

        $porEvento = $base()
            ->selectRaw('event, COUNT(*) as total')
            ->groupBy('event')
            ->orderByDesc('total')
            ->pluck('total', 'event');

        $porModulo = $base()
            ->whereNotNull('module')
            ->selectRaw('module, COUNT(*) as total')
            ->groupBy('module')
            ->orderByDesc('total')
            ->limit(10)
            ->pluck('total', 'module');

        $topUsuarios = $base()
            ->whereNotNull('user_id')
            ->selectRaw('user_id, COUNT(*) as total')
            ->groupBy('user_id')
            ->orderByDesc('total')
            ->limit(10)
            ->get();

        $nomes = User::query()->whereIn('id', $topUsuarios->pluck('user_id'))->pluck('nome', 'id');
        $topUsuarios->each(function ($linha) use ($nomes) {
            $linha->nome = $nomes[$linha->user_id] ?? 'Usuário removido';
        });

        $recentes = $base()
            ->with('user:id,nome')
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->limit(15)
            ->get();

        return $this->trilhaCache = [
            'total' => $total,
            'por_evento' => $porEvento,
            'por_modulo' => $porModulo,
            'top_usuarios' => $topUsuarios,
            'recentes' => $recentes,
        ];
    }

    // =========================================================================
    // Exportação (CSV) e integridade
    // =========================================================================

    /**
     * Uma linha por participação (usuário × conteúdo) para o CSV detalhado.
     * Opcionalmente inclui usuários elegíveis sem nenhum registro quando um
     * conteúdo específico está selecionado.
     *
     * @return Collection<int, \stdClass>
     */
    public function linhasDetalhado(?int $limite = self::LIMITE_EXPORTACAO): Collection
    {
        if ($this->linhasDetalhadoCache !== null) {
            return $this->linhasDetalhadoCache;
        }

        [$inicio, $fim] = $this->periodo();
        $colunaPeriodo = $this->request->input('base_data', 'inicio') === 'conclusao' ? 'data_conclusao' : 'data_inicio';

        $progressos = UserProgress::query()
            ->whereHas('user', function ($q) {
                $q->auditEligible()->visibleToViewer($this->viewer);
                $this->aplicarFiltrosUsuario($q);
            })
            ->when($this->request->filled('training_id'), fn ($q) => $q->where('training_id', $this->request->integer('training_id')))
            ->when($this->request->filled('training_tipo'), fn ($q) => $q->whereHas('training', fn ($t) => $t->where('tipo', $this->request->input('training_tipo'))))
            ->when($this->request->filled('tipo_treinamento'), fn ($q) => $q->whereHas('training', fn ($t) => $t->where('tipo_treinamento', $this->request->input('tipo_treinamento'))))
            ->when($this->request->boolean('somente_obrigatorios'), fn ($q) => $q->whereHas('training', fn ($t) => $t->where('obrigatorio', true)))
            ->when($inicio && $fim, fn ($q) => $q->whereBetween($colunaPeriodo, [$inicio, $fim]))
            ->when($inicio && ! $fim, fn ($q) => $q->where($colunaPeriodo, '>=', $inicio))
            ->when(! $inicio && $fim, fn ($q) => $q->where($colunaPeriodo, '<=', $fim))
            ->with(['user', 'training'])
            ->orderBy('user_id')
            ->orderBy('training_id')
            ->limit($limite ?? self::LIMITE_EXPORTACAO)
            ->get();

        $this->anexarDadosExportacao($progressos);

        if ($this->request->boolean('incluir_nao_iniciados') && $this->request->filled('training_id')) {
            $progressos = $progressos->concat($this->linhasNaoIniciados((int) $this->request->integer('training_id')));
        }

        return $this->linhasDetalhadoCache = $progressos;
    }

    /**
     * @return Collection<int, \stdClass>
     */
    private function linhasNaoIniciados(int $trainingId): Collection
    {
        $training = Training::find($trainingId);

        if (! $training) {
            return collect();
        }

        $comProgresso = UserProgress::query()->where('training_id', $trainingId)->select('user_id');

        $usuarios = $this->usuariosQuery()
            ->eligibleForContent($training)
            ->whereNotIn('id', $comProgresso)
            ->orderBy('nome')
            ->limit(5000)
            ->get();

        if ($usuarios->isEmpty()) {
            return collect();
        }

        $usuarios->load('vacations:id,user_id,data_inicio,data_fim');

        $folgas = FolgaDia::query()
            ->whereIn('user_id', $usuarios->pluck('id'))
            ->whereIn('tipo', ['folga', 'atestado', 'licenca'])
            ->selectRaw('user_id, tipo, COUNT(*) as dias')
            ->groupBy('user_id', 'tipo')
            ->get()
            ->groupBy('user_id');

        return $usuarios->map(function (User $usuario) use ($training, $folgas) {
            $linha = new \stdClass;
            $linha->user_id = $usuario->id;
            $linha->training_id = $training->id;
            $linha->user = $usuario;
            $linha->training = $training;
            $linha->data_inicio = null;
            $linha->data_conclusao = null;
            $linha->concluido = false;
            $linha->porcentagem_assistida = 0;
            $linha->tempo_assistido = 0;
            $linha->avaliacao_nota = null;
            $linha->avaliacao_aprovada = false;
            $linha->avaliacao_tentativas = 0;
            $linha->prazo_estendido_ate = null;
            $linha->prazo_estendido_motivo = null;
            $linha->situacao_detalhada = 'não iniciado';
            $linha->afastamento_periodo = $this->rotuloAfastamento($usuario, $folgas->get($usuario->id) ?? collect(), null, null);
            $linha->isencao = '';

            return $linha;
        });
    }

    private function anexarDadosExportacao(Collection $linhas): void
    {
        if ($linhas->isEmpty()) {
            return;
        }

        $userIds = $linhas->pluck('user_id')->unique()->values();
        $trainingIds = $linhas->pluck('training_id')->unique();

        $certificados = collect();
        foreach ($userIds->chunk(1000) as $chunk) {
            $certificados = $certificados->merge(
                Certificate::query()
                    ->whereIn('user_id', $chunk)
                    ->whereIn('training_id', $trainingIds)
                    ->orderByDesc('data_emissao')
                    ->get()
                    ->keyBy(fn ($cert) => $cert->user_id.'-'.$cert->training_id)
            );
        }

        $isencoes = TrainingVacationExemption::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('training_id', $trainingIds)
            ->get()
            ->keyBy(fn ($ex) => $ex->user_id.'-'.$ex->training_id);

        $folgas = FolgaDia::query()
            ->whereIn('user_id', $userIds)
            ->whereIn('tipo', ['folga', 'atestado', 'licenca'])
            ->selectRaw('user_id, tipo, COUNT(*) as dias')
            ->groupBy('user_id', 'tipo')
            ->get()
            ->groupBy('user_id');

        $linhas->pluck('user')->filter()->unique('id')->each(fn (User $usuario) => $usuario->loadMissing('vacations:id,user_id,data_inicio,data_fim'));

        [$inicio, $fim] = $this->periodo();
        $agora = Carbon::now(config('app.timezone'));

        foreach ($linhas as $linha) {
            $training = $linha->training;
            $prazo = $linha->prazo_estendido_ate ?? $training?->prazoBaseConclusao();

            $linha->prazo_efetivo = $prazo;
            $linha->certificado = $certificados->get($linha->user_id.'-'.$linha->training_id);
            $linha->isencao = isset($isencoes[$linha->user_id.'-'.$linha->training_id]) ? 'Sim' : '';
            $linha->afastamento_periodo = $this->rotuloAfastamento($linha->user, $folgas->get($linha->user_id) ?? collect(), $inicio, $fim);

            if ($linha->concluido) {
                $linha->situacao_detalhada = ($prazo && $linha->data_conclusao && $linha->data_conclusao->gt($prazo))
                    ? 'concluído em atraso'
                    : 'concluído em dia';
            } elseif ($prazo && $agora->gt($prazo)) {
                $linha->situacao_detalhada = 'prazo expirado';
            } elseif ($linha->data_inicio) {
                $linha->situacao_detalhada = 'em andamento';
            } else {
                $linha->situacao_detalhada = 'não iniciado';
            }
        }
    }

    /**
     * Hash determinístico do recorte — o mesmo valor aparece no CSV e no PDF
     * para um mesmo conjunto de filtros (cadeia de custódia).
     */
    public function hashRecorte(): string
    {
        try {
            $linhas = $this->linhasDetalhado(self::LIMITE_EXPORTACAO);

            return hash('sha256', $linhas->map(function ($linha) {
                return implode('|', [
                    $linha->user_id,
                    $linha->training_id,
                    (int) (bool) $linha->concluido,
                    optional($linha->data_inicio)->toIso8601String(),
                    optional($linha->data_conclusao)->toIso8601String(),
                    (int) ($linha->tempo_assistido ?? 0),
                    $linha->avaliacao_nota ?? '',
                ]);
            })->implode("\n"));
        } catch (\Throwable) {
            return 'indisponível';
        }
    }

    /**
     * Lista plana de ausências para o CSV de justificativas.
     *
     * @return Collection<int, array<string, mixed>>
     */
    public function linhasAusencias(): Collection
    {
        $ausencias = $this->ausencias();
        $linhas = collect();

        $autores = User::query()
            ->whereIn('id', $ausencias['prorrogacoes']->pluck('prazo_estendido_por')->filter()->unique())
            ->pluck('nome', 'id');

        foreach ($ausencias['ferias'] as $ferias) {
            $linhas->push([
                'nome' => optional($ferias->user)->nome ?? '—',
                'cpf' => optional($ferias->user)?->getCpfFormatted() ?? '',
                'tipo' => 'Férias',
                'inicio' => $ferias->data_inicio->format('d/m/Y'),
                'fim' => $ferias->data_fim->format('d/m/Y'),
                'dias' => $ferias->dias_periodo,
                'motivo' => '',
                'autorizado_por' => '',
                'observacao' => 'Histórico de férias',
            ]);
        }

        foreach ($ausencias['folgas'] as $folga) {
            $linhas->push([
                'nome' => optional($folga->user)->nome ?? '—',
                'cpf' => optional($folga->user)?->getCpfFormatted() ?? '',
                'tipo' => ['folga' => 'Folga', 'atestado' => 'Atestado', 'licenca' => 'Licença'][$folga->tipo] ?? $folga->tipo,
                'inicio' => $folga->data->format('d/m/Y'),
                'fim' => $folga->data->format('d/m/Y'),
                'dias' => 1,
                'motivo' => $folga->motivo_folga ?? '',
                'autorizado_por' => optional($folga->lancadoPor)->nome ?? '',
                'observacao' => $folga->observacao ?? '',
            ]);
        }

        foreach ($ausencias['isencoes'] as $isencao) {
            $linhas->push([
                'nome' => optional($isencao->user)->nome ?? '—',
                'cpf' => optional($isencao->user)?->getCpfFormatted() ?? '',
                'tipo' => 'Isenção de conteúdo',
                'inicio' => $isencao->data_inicio->format('d/m/Y'),
                'fim' => $isencao->data_fim->format('d/m/Y'),
                'dias' => (int) $isencao->data_inicio->diffInDays($isencao->data_fim) + 1,
                'motivo' => $isencao->motivo ?? '',
                'autorizado_por' => optional($isencao->creator)->nome ?? '',
                'observacao' => 'Conteúdo: '.optional($isencao->training)->titulo,
            ]);
        }

        foreach ($ausencias['prorrogacoes'] as $prorrogacao) {
            $linhas->push([
                'nome' => optional($prorrogacao->user)->nome ?? '—',
                'cpf' => optional($prorrogacao->user)?->getCpfFormatted() ?? '',
                'tipo' => 'Prazo estendido',
                'inicio' => optional($prorrogacao->prazo_estendido_em)->format('d/m/Y H:i') ?? '',
                'fim' => optional($prorrogacao->prazo_estendido_ate)->format('d/m/Y H:i') ?? '',
                'dias' => '',
                'motivo' => $prorrogacao->prazo_estendido_motivo ?? '',
                'autorizado_por' => $autores[$prorrogacao->prazo_estendido_por] ?? '',
                'observacao' => 'Conteúdo: '.optional($prorrogacao->training)->titulo,
            ]);
        }

        return $linhas->sortBy('nome')->values();
    }
}
