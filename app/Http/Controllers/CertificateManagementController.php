<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CertificateManagementController extends Controller
{
    /**
     * Página de gerenciamento de certificados (ADM/Super ADM)
     */
    public function index(Request $request)
    {
        $query = Certificate::query();
        $this->aplicarEscopoUsuariosComuns($query, $request->user(), 'user');
        
        // Filtros avançados
        // Filtrar por usuário (por ID) ou por nome livre (compatibilidade)
        if ($request->filled('usuario_id')) {
            $query->where('user_id', request('usuario_id'));
        } elseif ($request->filled('usuario_nome')) {
            $query->whereHas('user', function($q) {
                $q->where('nome', 'like', '%' . request('usuario_nome') . '%');
            });
        }

        if ($request->filled('cpf')) {
            $cpf = preg_replace('/\D/', '', request('cpf'));
            $query->whereHas('user', function($q) use ($cpf) {
                $q->where('cpf', 'like', '%' . $cpf . '%');
            });
        }

        if ($request->filled('training_id')) {
            $query->where('training_id', request('training_id'));
        }

        if ($request->filled('training_tipo')) {
            $query->whereHas('training', function ($q) {
                $q->where('tipo', request('training_tipo'));
            });
        }

        // Filtrar por tipo de treinamento (ex: 'dss' ou 'treinamento')
        if ($request->filled('training_tipo')) {
            $query->whereHas('training', function ($q) {
                $q->where('tipo', request('training_tipo'));
            });
        }

        if ($request->filled('valido')) {
            $query->where('valido', request('valido') === '1');
        }

        if ($request->filled('data_emissao_inicio')) {
            $query->whereDate('data_emissao', '>=', request('data_emissao_inicio'));
        }

        if ($request->filled('data_emissao_fim')) {
            $query->whereDate('data_emissao', '<=', request('data_emissao_fim'));
        }

        if ($request->filled('data_conclusao_inicio')) {
            $query->whereDate('data_finalizacao_assistencia', '>=', request('data_conclusao_inicio'));
        }

        if ($request->filled('data_conclusao_fim')) {
            $query->whereDate('data_finalizacao_assistencia', '<=', request('data_conclusao_fim'));
        }

        if ($request->filled('ordenar')) {
            switch(request('ordenar')) {
                case 'recente':
                    $query->orderBy('data_emissao', 'desc');
                    break;
                case 'antigo':
                    $query->orderBy('data_emissao', 'asc');
                    break;
                case 'nome_asc':
                    $query->whereHas('user', function($q) {
                        $q->orderBy('nome', 'asc');
                    });
                    break;
                default:
                    $query->orderBy('data_emissao', 'desc');
            }
        } else {
            $query->orderBy('data_emissao', 'desc');
        }

        $certificados = $query->paginate(15);
        $treinamentos = Training::orderBy('titulo')->get();
        // Tipos de usuário existentes para o filtro
        $userTypes = User::select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');

        // Lista de usuários, possivelmente filtrada por tipo
        if ($request->filled('tipo_usuario')) {
            $users = User::where('tipo_usuario', request('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::orderBy('nome')->get();
        }

        $totalCertificados = Certificate::count();
        $certificadosValidos = Certificate::where('valido', true)->count();

        return view('certificados.gerencial', [
            'certificados' => $certificados,
            'treinamentos' => $treinamentos,
            'userTypes' => $userTypes,
            'users' => $users,
            'totalCertificados' => $totalCertificados,
            'certificadosValidos' => $certificadosValidos,
            'filtrosAtivos' => $this->verificarFiltrosAtivos($request),
        ]);
    }

    /**
     * Relatório gerencial de treinamentos
     */
    public function relatorioTreinamentos(Request $request)
    {
        $statusProgresso = $request->input('status_progresso');
        $progressos = null;
        $treinamentoNaoIniciado = null;

        if ($statusProgresso === 'nao_iniciado') {
            $usersQuery = User::query();
            $this->aplicarEscopoUsuariosComuns($usersQuery, $request->user());

            if ($request->filled('tipo_usuario')) {
                $usersQuery->where('tipo_usuario', $request->input('tipo_usuario'));
            }

            if ($request->filled('usuario_id')) {
                $usersQuery->where('id', $request->integer('usuario_id'));
            }

            if ($request->filled('usuario_nome')) {
                $usersQuery->where('nome', 'like', '%' . $request->input('usuario_nome') . '%');
            }

            if ($request->filled('training_id')) {
                $treinamentoNaoIniciado = Training::findOrFail($request->integer('training_id'));
                $usersQuery->whereDoesntHave('progress', function ($q) use ($treinamentoNaoIniciado) {
                    $q->where('training_id', $treinamentoNaoIniciado->id);
                });
            } else {
                $usersQuery->whereDoesntHave('progress');
            }

            $usersNaoIniciados = $usersQuery->orderBy('nome')->get();
            $rows = $usersNaoIniciados->map(function ($user) use ($treinamentoNaoIniciado) {
                $row = new \stdClass();
                $row->user = $user;
                $row->training = $treinamentoNaoIniciado;
                $row->tempo_assistido = 0;
                $row->porcentagem_assistida = 0;
                $row->concluido = false;
                $row->data_inicio = null;
                $row->data_conclusao = null;

                return $row;
            });

            $perPage = 15;
            $currentPage = LengthAwarePaginator::resolveCurrentPage();
            $progressos = new LengthAwarePaginator(
                $rows->forPage($currentPage, $perPage)->values(),
                $rows->count(),
                $perPage,
                $currentPage,
                [
                    'path' => $request->url(),
                    'query' => $request->query(),
                ]
            );
        } else {
            $query = UserProgress::query();
            $this->aplicarEscopoUsuariosComuns($query, $request->user(), 'user');

            if ($request->filled('usuario_id')) {
                $query->where('user_id', $request->integer('usuario_id'));
            }

            if ($request->filled('tipo_usuario')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('tipo_usuario', $request->input('tipo_usuario'));
                });
            }

            if ($request->filled('training_id')) {
                $query->where('training_id', $request->integer('training_id'));
            }

            if ($request->filled('usuario_nome')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('nome', 'like', '%' . $request->input('usuario_nome') . '%');
                });
            }

            if ($statusProgresso !== null && $statusProgresso !== '') {
                if (in_array($statusProgresso, ['1', 'true', 'concluido'], true)) {
                    $query->where('concluido', true);
                } elseif (in_array($statusProgresso, ['0', 'false', 'pendente'], true)) {
                    $query->where('concluido', false);
                }
            }

            if ($request->filled('data_inicio')) {
                $query->whereDate('data_inicio', '>=', $request->input('data_inicio'));
            }

            if ($request->filled('data_fim')) {
                $query->whereDate('data_conclusao', '<=', $request->input('data_fim'));
            }

            $progressos = (clone $query)
                ->with(['user', 'training'])
                ->orderByDesc('data_inicio')
                ->paginate(15);
        }

        $treinamentos = Training::orderBy('titulo')->get();

        // Tipos de usuário para filtro e lista dinâmica de usuários
        $userTypes = User::select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');
        if ($request->filled('tipo_usuario')) {
            $users = User::where('tipo_usuario', $request->input('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::orderBy('nome')->get();
        }

        if ($statusProgresso === 'nao_iniciado') {
            $totalAssistencias = $progressos->total();
            $concluidas = 0;
            $taxaGeral = 0;
            $tempoTotalAssistido = 0;
            $tempoMedioAssistido = 0;
            $treinamentosResumo = collect();
            $usuariosEmDestaque = collect();
        } else {
            $query = UserProgress::query();
            $this->aplicarEscopoUsuariosComuns($query, $request->user(), 'user');

            if ($request->filled('usuario_id')) {
                $query->where('user_id', $request->integer('usuario_id'));
            }

            if ($request->filled('tipo_usuario')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('tipo_usuario', $request->input('tipo_usuario'));
                });
            }

            if ($request->filled('training_id')) {
                $query->where('training_id', $request->integer('training_id'));
            }

            if ($request->filled('usuario_nome')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->where('nome', 'like', '%' . $request->input('usuario_nome') . '%');
                });
            }

            if ($statusProgresso !== null && $statusProgresso !== '') {
                if (in_array($statusProgresso, ['1', 'true', 'concluido'], true)) {
                    $query->where('concluido', true);
                } elseif (in_array($statusProgresso, ['0', 'false', 'pendente'], true)) {
                    $query->where('concluido', false);
                }
            }

            if ($request->filled('data_inicio')) {
                $query->whereDate('data_inicio', '>=', $request->input('data_inicio'));
            }

            if ($request->filled('data_fim')) {
                $query->whereDate('data_conclusao', '<=', $request->input('data_fim'));
            }

            $totalAssistencias = (clone $query)->count();
            $concluidas = (clone $query)->where('concluido', true)->count();
            $taxaGeral = $totalAssistencias > 0 ? ($concluidas / $totalAssistencias) * 100 : 0;
            $tempoTotalAssistido = (clone $query)->sum('tempo_assistido');
            $tempoMedioAssistido = (clone $query)->avg('tempo_assistido');

            $treinamentosResumo = (clone $query)
                ->select('training_id')
                ->selectRaw('COUNT(*) as assistencias')
                ->selectRaw('SUM(CASE WHEN concluido = 1 THEN 1 ELSE 0 END) as concluidas')
                ->selectRaw('SUM(COALESCE(tempo_assistido, 0)) as tempo_total_assistido')
                ->groupBy('training_id')
                ->with(['training:id,titulo,tipo,carga_horaria'])
                ->orderByDesc('assistencias')
                ->take(10)
                ->get();

            $usuariosEmDestaque = (clone $query)
                ->select('user_id')
                ->selectRaw('COUNT(*) as assistencias')
                ->selectRaw('SUM(CASE WHEN concluido = 1 THEN 1 ELSE 0 END) as concluidas')
                ->selectRaw('SUM(COALESCE(tempo_assistido, 0)) as tempo_total_assistido')
                ->groupBy('user_id')
                ->with(['user:id,nome,cpf,tipo_usuario,status'])
                ->orderByDesc('tempo_total_assistido')
                ->take(10)
                ->get();
        }

        $tempoMedioFormatado = $this->formatarTempoEmHoras($tempoMedioAssistido);
        $tempoTotalFormatado = $this->formatarTempoEmHoras($tempoTotalAssistido);

        $conteudosPorTipo = Training::selectRaw("COALESCE(tipo, 'sem_tipo') as tipo, COUNT(*) as total")
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->get();

        return view('relatorios.treinamentos', [
            'progressos' => $progressos,
            'treinamentos' => $treinamentos,
            'totalAssistencias' => $totalAssistencias,
            'concluidas' => $concluidas,
            'taxaGeral' => number_format($taxaGeral, 2, ',', '.'),
            'tempoMedioFormatado' => $tempoMedioFormatado,
            'tempoTotalFormatado' => $tempoTotalFormatado,
            'filtrosAtivos' => $this->verificarFiltrosAtivos($request),
            'userTypes' => $userTypes,
            'users' => $users,
            'treinamentosResumo' => $treinamentosResumo,
            'usuariosEmDestaque' => $usuariosEmDestaque,
            'conteudosPorTipo' => $conteudosPorTipo,
            'statusProgresso' => $statusProgresso,
        ]);
    }

    /**
     * Relatório de usuários com histórico de treinamentos
     */
    public function relatorioUsuarios(Request $request)
    {
        $query = User::query();

        // Admin sempre enxerga apenas usuários comuns; Super Admin pode incluir administradores via filtro.
        if ($request->user()->isSuperAdmin() && $request->filled('incluir_adm')) {
            // Sem escopo adicional: visão completa.
        } else {
            $this->aplicarEscopoUsuariosComuns($query, $request->user());
        }

        if ($request->filled('usuario_id')) {
            $query->where('id', request('usuario_id'));
        } elseif ($request->filled('nome')) {
            $query->where('nome', 'like', '%' . request('nome') . '%');
        }

        if ($request->filled('cpf')) {
            $cpf = preg_replace('/\D/', '', request('cpf'));
            $query->where('cpf', 'like', '%' . $cpf . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', request('status'));
        }

        if ($request->filled('tipo_usuario')) {
            $query->where('tipo_usuario', $request->input('tipo_usuario'));
        }

        $usuarios = (clone $query)
            ->with(['progress', 'certificates'])
            ->withCount(['progress', 'certificates'])
            ->withSum('progress as tempo_total_assistido', 'tempo_assistido')
            ->withMax('progress as ultima_atividade_em', 'data_conclusao')
            ->orderBy('nome')
            ->paginate(15);

        // Tipos e lista de usuários para filtros dinâmicos
        $userTypes = User::select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');
        if ($request->filled('tipo_usuario')) {
            $users = User::where('tipo_usuario', $request->input('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::orderBy('nome')->get();
        }

        $totalUsuarios = (clone $query)->count();
        $usuariosAtivos = (clone $query)->where('status', 'ativo')->count();
        $usuariosComTreinamentos = (clone $query)->whereHas('progress')->count();
        $usuariosComCertificados = (clone $query)->whereHas('certificates')->count();
        $tempoTotalAssistido = UserProgress::query()
            ->whereHas('user', function ($q) use ($request) {
                if ($request->user()->isSuperAdmin() && $request->filled('incluir_adm')) {
                    return;
                }

                $q->where(function ($sub) {
                    $sub->whereNull('role_id')
                        ->orWhereHas('role', function ($role) {
                            $role->whereNotIn('nome', ['admin', 'super_admin']);
                        });
                });
            })
            ->sum('tempo_assistido');

        return view('relatorios.usuarios', [
            'usuarios' => $usuarios,
            'totalUsuarios' => $totalUsuarios,
            'usuariosAtivos' => $usuariosAtivos,
            'usuariosComTreinamentos' => $usuariosComTreinamentos,
            'usuariosComCertificados' => $usuariosComCertificados,
            'tempoTotalFormatado' => $this->formatarTempoEmHoras($tempoTotalAssistido),
            'filtrosAtivos' => $this->verificarFiltrosAtivos($request),
            'userTypes' => $userTypes,
            'users' => $users,
        ]);
    }

    /**
     * Relatório de auditoria completo
     */
    public function relatorioAuditoria(Request $request)
    {
        $user = $request->user();
        $periodoInicio = $request->input('periodo_inicio');
        $periodoFim = $request->input('periodo_fim');
        $trainingId = $request->filled('training_id') ? $request->integer('training_id') : null;

        $usuariosBase = User::query();
        $this->aplicarEscopoUsuariosComuns($usuariosBase, $user);
        if ($request->filled('tipo_usuario')) {
            $usuariosBase->where('tipo_usuario', $request->input('tipo_usuario'));
        }
        if ($request->filled('usuario_id')) {
            $usuariosBase->where('id', $request->integer('usuario_id'));
        }

        $certificadosBase = Certificate::query();
        $this->aplicarEscopoUsuariosComuns($certificadosBase, $user, 'user');
        if ($request->filled('usuario_id')) {
            $certificadosBase->where('user_id', $request->integer('usuario_id'));
        }
        if ($request->filled('training_tipo')) {
            $certificadosBase->whereHas('training', function ($q) use ($request) {
                $q->where('tipo', $request->input('training_tipo'));
            });
        }
        if ($trainingId) {
            $certificadosBase->where('training_id', $trainingId);
        }
        if ($periodoInicio) {
            $certificadosBase->whereDate('data_emissao', '>=', $periodoInicio);
        }
        if ($periodoFim) {
            $certificadosBase->whereDate('data_emissao', '<=', $periodoFim);
        }

        $progressBase = UserProgress::query();
        $this->aplicarEscopoUsuariosComuns($progressBase, $user, 'user');
        if ($request->filled('tipo_usuario')) {
            $progressBase->whereHas('user', function ($q) use ($request) {
                $q->where('tipo_usuario', $request->input('tipo_usuario'));
            });
        }
        if ($request->filled('usuario_id')) {
            $progressBase->where('user_id', $request->integer('usuario_id'));
        }
        if ($trainingId) {
            $progressBase->where('training_id', $trainingId);
        }
        if ($periodoInicio) {
            $progressBase->whereDate('data_inicio', '>=', $periodoInicio);
        }
        if ($periodoFim) {
            $progressBase->whereDate('data_inicio', '<=', $periodoFim);
        }

        $totalUsuarios = (clone $usuariosBase)->count();
        $usuariosAtivos = (clone $usuariosBase)->where('status', 'ativo')->count();
        $totalTreinamentos = Training::count();
        $totalCertificados = (clone $certificadosBase)->count();
        $totalAssistencias = (clone $progressBase)->count();
        $concluidas = (clone $progressBase)->where('concluido', true)->count();
        $taxaGeral = $totalAssistencias > 0 ? ($concluidas / $totalAssistencias) * 100 : 0;
        $usuariosComProgresso = (clone $usuariosBase)->whereHas('progress')->count();
        $usuariosComCertificados = (clone $usuariosBase)->whereHas('certificates')->count();
        $taxaEngajamento = $totalUsuarios > 0 ? ($usuariosComProgresso / $totalUsuarios) * 100 : 0;
        $taxaCertificacao = $totalUsuarios > 0 ? ($usuariosComCertificados / $totalUsuarios) * 100 : 0;
        $tempoTotalAssistido = (clone $progressBase)->sum('tempo_assistido');
        $tempoMedioAssistido = (clone $progressBase)->avg('tempo_assistido');

        $usuariosPorTipo = (clone $usuariosBase)
            ->selectRaw("COALESCE(tipo_usuario, 'sem_tipo') as tipo_usuario, COUNT(*) as total")
            ->groupBy('tipo_usuario')
            ->orderByDesc('total')
            ->get();

        $treinamentosMaisAssistidos = Training::withCount(['progress'])
            ->withCount(['progress as concluidos_count' => function ($q) {
                $q->where('concluido', true);
            }])
            ->withSum('progress as tempo_total_assistido', 'tempo_assistido')
            ->orderByDesc('progress_count')
            ->take(10)
            ->get();

        $conteudosPorTipo = Training::selectRaw("COALESCE(tipo, 'sem_tipo') as tipo, COUNT(*) as total")
            ->groupBy('tipo')
            ->orderByDesc('total')
            ->get();

        $taxaConclusao = [];
        $treinamentosComProgressos = Training::withCount(['progress'])
            ->withCount(['progress as concluidos_count' => function ($q) {
                $q->where('concluido', true);
            }])
            ->get();

        foreach ($treinamentosComProgressos as $training) {
            $taxaConclusao[$training->id] = $training->progress_count > 0
                ? ($training->concluidos_count / $training->progress_count) * 100
                : 0;
        }

        $usuariosEmDestaque = (clone $progressBase)
            ->select('user_id')
            ->selectRaw('COUNT(*) as assistencias')
            ->selectRaw('SUM(CASE WHEN concluido = 1 THEN 1 ELSE 0 END) as concluidas')
            ->selectRaw('SUM(COALESCE(tempo_assistido, 0)) as tempo_total_assistido')
            ->groupBy('user_id')
            ->with(['user:id,nome,cpf,tipo_usuario,status'])
            ->orderByDesc('tempo_total_assistido')
            ->take(10)
            ->get();

        $usuariosSemTreinamentoBase = User::whereDoesntHave('progress');
        $this->aplicarEscopoUsuariosComuns($usuariosSemTreinamentoBase, $user);
        if ($request->filled('tipo_usuario')) {
            $usuariosSemTreinamentoBase->where('tipo_usuario', $request->input('tipo_usuario'));
        }
        if ($request->filled('usuario_id')) {
            $usuariosSemTreinamentoBase->where('id', $request->integer('usuario_id'));
        }
        $usuariosSemTreinamento = $usuariosSemTreinamentoBase->count();

        $driver = DB::connection()->getDriverName();
        $monthExpression = $driver === 'mysql'
            ? "DATE_FORMAT(data_emissao, '%Y-%m')"
            : "strftime('%Y-%m', data_emissao)";

        $certificadosPorMes = (clone $certificadosBase)
            ->selectRaw("{$monthExpression} as periodo, COUNT(*) as total")
            ->whereNotNull('data_emissao')
            ->groupBy('periodo')
            ->orderBy('periodo', 'asc')
            ->get();

        $monthExpressionProgress = $driver === 'mysql'
            ? "DATE_FORMAT(data_inicio, '%Y-%m')"
            : "strftime('%Y-%m', data_inicio)";

        $atividadesPorMes = (clone $progressBase)
            ->selectRaw("{$monthExpressionProgress} as periodo, COUNT(*) as total")
            ->whereNotNull('data_inicio')
            ->groupBy('periodo')
            ->orderBy('periodo', 'asc')
            ->get();

        $tempoMedioFormatado = $this->formatarTempoEmHoras($tempoMedioAssistido);
        $tempoTotalFormatado = $this->formatarTempoEmHoras($tempoTotalAssistido);

        // Tipos e usuários para filtros dinâmicos
        $userTypes = User::select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');
        if ($request->filled('tipo_usuario')) {
            $users = User::where('tipo_usuario', $request->input('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::orderBy('nome')->get();
        }

        $treinamentos = Training::orderBy('titulo')->get();

        $usuariosSemTreinamentoLista = (clone $usuariosSemTreinamentoBase)
            ->orderBy('nome')
            ->take(8)
            ->get();

        return view('relatorios.auditoria', [
            'totalUsuarios' => $totalUsuarios,
            'totalTreinamentos' => $totalTreinamentos,
            'totalCertificados' => $totalCertificados,
            'usuariosAtivos' => $usuariosAtivos,
            'totalAssistencias' => $totalAssistencias,
            'concluidas' => $concluidas,
            'taxaGeral' => number_format($taxaGeral, 2, ',', '.'),
            'taxaGeralPercentual' => $taxaGeral,
            'usuariosComProgresso' => $usuariosComProgresso,
            'usuariosComCertificados' => $usuariosComCertificados,
            'taxaEngajamento' => number_format($taxaEngajamento, 2, ',', '.'),
            'taxaEngajamentoPercentual' => $taxaEngajamento,
            'taxaCertificacao' => number_format($taxaCertificacao, 2, ',', '.'),
            'taxaCertificacaoPercentual' => $taxaCertificacao,
            'tempoTotalFormatado' => $tempoTotalFormatado,
            'tempoMedioFormatado' => $tempoMedioFormatado,
            'usuariosPorTipo' => $usuariosPorTipo,
            'treinamentosMaisAssistidos' => $treinamentosMaisAssistidos,
            'conteudosPorTipo' => $conteudosPorTipo,
            'usuariosEmDestaque' => $usuariosEmDestaque,
            'taxaConclusao' => $taxaConclusao,
            'usuariosSemTreinamento' => $usuariosSemTreinamento,
            'usuariosSemTreinamentoLista' => $usuariosSemTreinamentoLista,
            'certificadosPorMes' => $certificadosPorMes,
            'atividadesPorMes' => $atividadesPorMes,
            'userTypes' => $userTypes,
            'users' => $users,
            'treinamentos' => $treinamentos,
        ]);
    }

    /**
     * Exportar certificados para CSV
     */
    public function exportarCertificados(Request $request)
    {
        $query = Certificate::query();
        $this->aplicarEscopoUsuariosComuns($query, $request->user(), 'user');

        // Aplicar mesmos filtros
        // Filtrar por usuário (por ID) ou por nome livre
        if ($request->filled('usuario_id')) {
            $query->where('user_id', request('usuario_id'));
        } elseif ($request->filled('usuario_nome')) {
            $query->whereHas('user', function($q) {
                $q->where('nome', 'like', '%' . request('usuario_nome') . '%');
            });
        }

        if ($request->filled('training_id')) {
            $query->where('training_id', request('training_id'));
        }

        if ($request->filled('training_tipo')) {
            $query->whereHas('training', function ($q) {
                $q->where('tipo', request('training_tipo'));
            });
        }

        if ($request->filled('valido')) {
            $query->where('valido', request('valido') === '1');
        }

        $certificados = $query->with(['user', 'training'])->get();

        $filename = 'certificados_' . now()->format('Y-m-d_H-i-s') . '.csv';

        return response()->streamDownload(function () use ($certificados) {
            $file = fopen('php://output', 'w');
            
            // Cabeçalho
            fputcsv($file, [
                'Código',
                'Usuário',
                'CPF',
                'Treinamento',
                'Data Emissão',
                'Data Conclusão',
                'Válido',
                'Tempo Assistido',
            ], ';');

            foreach ($certificados as $cert) {
                fputcsv($file, [
                    $cert->codigo_certificado,
                    $cert->user->nome,
                    $cert->user->getCpfFormatted(),
                    $cert->training->titulo,
                    $cert->data_emissao->format('d/m/Y H:i'),
                    optional($cert->data_finalizacao_assistencia)->format('d/m/Y H:i'),
                    $cert->valido ? 'Sim' : 'Não',
                    gmdate('H:i:s', $cert->tempo_assistido ?? 0),
                ], ';');
            }

            fclose($file);
        }, $filename);
    }

    /**
     * Verifica se há filtros ativos
     */
    private function verificarFiltrosAtivos(Request $request)
    {
        return $request->filled('usuario_nome') ||
               $request->filled('cpf') ||
               $request->filled('usuario_id') ||
               $request->filled('training_id') ||
               $request->filled('training_tipo') ||
               $request->filled('valido') ||
               $request->filled('data_emissao_inicio') ||
               $request->filled('data_emissao_fim') ||
               $request->filled('data_conclusao_inicio') ||
               $request->filled('data_conclusao_fim') ||
               $request->filled('nome') ||
               $request->filled('status') ||
               $request->filled('tipo_usuario') ||
               $request->filled('concluido') ||
               $request->filled('data_inicio') ||
               $request->filled('data_fim');
    }

    /**
     * Formata segundos em HH:MM:SS com proteção para valores nulos.
     */
    private function formatarTempoEmHoras($segundos): string
    {
        return gmdate('H:i:s', max(0, (int) ($segundos ?? 0)));
    }

    /**
     * Aplica escopo de visibilidade: admin vê usuários comuns; super admin vê tudo.
     */
    private function aplicarEscopoUsuariosComuns($query, User $user, string $relation = null): void
    {
        if ($user->isSuperAdmin()) {
            return;
        }

        $scope = function ($q) {
            $q->where(function ($sub) {
                $sub->whereNull('role_id')
                    ->orWhereHas('role', function ($role) {
                        $role->whereNotIn('nome', ['admin', 'super_admin']);
                    });
            });
        };

        if ($relation) {
            $query->whereHas($relation, $scope);
            return;
        }

        $scope($query);
    }
}
