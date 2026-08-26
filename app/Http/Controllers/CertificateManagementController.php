<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\AiSummarizer;
use App\Services\TrainingAnalyzer;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use TCPDF;

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
            $query->whereHas('user', function ($q) {
                $q->kpiEligible()->where('nome', 'like', '%'.request('usuario_nome').'%');
            });
        }

        if ($request->filled('cpf')) {
            $cpf = preg_replace('/\D/', '', request('cpf'));
            $query->whereHas('user', function ($q) use ($cpf) {
                $q->kpiEligible()->where('cpf', 'like', '%'.$cpf.'%');
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

        // Filtro por status de validade do treinamento (data de emissão + dias de validade)
        if ($request->filled('status_validade')) {
            $statusValidade = $request->input('status_validade');
            $hoje = now()->format('Y-m-d');
            $limite = now()->addDays(30)->format('Y-m-d');
            $driver = DB::connection()->getDriverName();

            $query->whereHas('training', function ($q) use ($statusValidade, $hoje, $limite, $driver) {
                $q->whereNotNull('dias_validade')->where('dias_validade', '>', 0);

                if ($driver === 'sqlite') {
                    $expr = "date(certificates.data_emissao, '+' || trainings.dias_validade || ' days')";
                } else {
                    $expr = 'DATE_ADD(certificates.data_emissao, INTERVAL trainings.dias_validade DAY)';
                }

                if ($statusValidade === 'vencido') {
                    $q->whereRaw("{$expr} < ?", [$hoje]);
                } elseif ($statusValidade === 'vencendo') {
                    $q->whereRaw("{$expr} >= ? AND {$expr} <= ?", [$hoje, $limite]);
                } elseif ($statusValidade === 'valido') {
                    $q->whereRaw("{$expr} > ?", [$limite]);
                }
            });
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
            switch (request('ordenar')) {
                case 'recente':
                    $query->orderBy('data_emissao', 'desc');
                    break;
                case 'antigo':
                    $query->orderBy('data_emissao', 'asc');
                    break;
                case 'nome_asc':
                    $query->whereHas('user', function ($q) {
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
        $userTypes = User::kpiEligible()->select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');

        // Lista de usuários, possivelmente filtrada por tipo
        if ($request->filled('tipo_usuario')) {
            $users = User::kpiEligible()->where('tipo_usuario', request('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::kpiEligible()->orderBy('nome')->get();
        }

        $totalCertificados = Certificate::whereHas('user', function ($q) {
            $q->kpiEligible();
        })->count();
        $certificadosValidos = Certificate::where('valido', true)
            ->whereHas('user', function ($q) {
                $q->kpiEligible();
            })
            ->count();

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
        $somenteFerias = $request->filled('somente_ferias');
        $trainingId = $request->filled('training_id') ? $request->integer('training_id') : null;
        $trainingFilter = $trainingId ? Training::findOrFail($trainingId) : null;
        $progressos = null;
        $treinamentoNaoIniciado = null;

        if ($statusProgresso === 'nao_iniciado') {
            $usersQuery = User::query()->kpiEligible();
            $this->aplicarEscopoUsuariosComuns($usersQuery, $request->user());

            if ($request->filled('tipo_usuario')) {
                $usersQuery->where('tipo_usuario', $request->input('tipo_usuario'));
            }

            if ($request->filled('usuario_id')) {
                $usersQuery->where('id', $request->integer('usuario_id'));
            }

            if ($request->filled('usuario_nome')) {
                $usersQuery->where('nome', 'like', '%'.$request->input('usuario_nome').'%');
            }

            if ($somenteFerias) {
                $usersQuery->vacationInPeriod($request->input('data_inicio'), $request->input('data_fim'));
            }

            if ($trainingId) {
                $treinamentoNaoIniciado = $trainingFilter;
                $usersQuery->eligibleForTrainingKpi($treinamentoNaoIniciado);
                $usersQuery->whereDoesntHave('progress', function ($q) use ($treinamentoNaoIniciado) {
                    $q->where('training_id', $treinamentoNaoIniciado->id);
                });
            } else {
                $usersQuery->whereDoesntHave('progress');
            }

            $usersNaoIniciados = $usersQuery->orderBy('nome')->get();
            $rows = $usersNaoIniciados->map(function ($user) use ($treinamentoNaoIniciado) {
                $row = new \stdClass;
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
            $query->whereHas('user', function ($userQuery) {
                $userQuery->kpiEligible();
            });

            if ($trainingFilter) {
                $query->whereHas('user', function ($q) use ($trainingFilter) {
                    $q->kpiEligible()->eligibleForTrainingKpi($trainingFilter);
                });
            }

            if ($request->filled('usuario_id')) {
                $query->where('user_id', $request->integer('usuario_id'));
            }

            if ($request->filled('tipo_usuario')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->kpiEligible()->where('tipo_usuario', $request->input('tipo_usuario'));
                });
            }

            if ($trainingFilter) {
                $query->where('training_id', $trainingFilter->id);
            }

            if ($request->filled('usuario_nome')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->kpiEligible()->where('nome', 'like', '%'.$request->input('usuario_nome').'%');
                });
            }

            if ($somenteFerias) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->vacationInPeriod($request->input('data_inicio'), $request->input('data_fim'));
                });
            }

            if ($statusProgresso !== null && $statusProgresso !== '') {
                if (in_array($statusProgresso, ['1', 'true', 'concluido'], true)) {
                    $query->where('concluido', true);
                } elseif (in_array($statusProgresso, ['0', 'false', 'pendente', 'nao_finalizados'], true)) {
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
        $usuariosEmFeriasBase = User::query()->vacationInPeriod($request->input('data_inicio'), $request->input('data_fim'));
        $this->aplicarEscopoUsuariosComuns($usuariosEmFeriasBase, $request->user());
        if ($request->filled('tipo_usuario')) {
            $usuariosEmFeriasBase->where('tipo_usuario', $request->input('tipo_usuario'));
        }
        if ($request->filled('usuario_id')) {
            $usuariosEmFeriasBase->where('id', $request->integer('usuario_id'));
        }
        if ($request->filled('usuario_nome')) {
            $usuariosEmFeriasBase->where('nome', 'like', '%'.$request->input('usuario_nome').'%');
        }
        if ($trainingId) {
            $usuariosEmFeriasBase->whereHas('progress', function ($q) use ($trainingId, $request) {
                $q->where('training_id', $trainingId);
                if ($request->filled('data_inicio')) {
                    $q->whereDate('data_inicio', '>=', $request->input('data_inicio'));
                }
                if ($request->filled('data_fim')) {
                    $q->whereDate('data_inicio', '<=', $request->input('data_fim'));
                }
            });
        }
        $usuariosEmFerias = (clone $usuariosEmFeriasBase)->count();
        $usuariosEmFeriasLista = (clone $usuariosEmFeriasBase)
            ->orderBy('nome')
            ->take(8)
            ->get();

        // Tipos de usuário para filtro e lista dinâmica de usuários
        $userTypes = User::kpiEligible()->select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');
        if ($request->filled('tipo_usuario')) {
            $users = User::kpiEligible()->where('tipo_usuario', $request->input('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::kpiEligible()->orderBy('nome')->get();
        }

        if ($statusProgresso === 'nao_iniciado') {
            $totalAssistencias = $progressos->total();
            $concluidas = 0;
            $taxaGeral = 0;
            $tempoTotalAssistido = 0;
            $tempoMedioAssistido = 0;
            $treinamentosResumo = collect();
            $usuariosEmDestaque = collect();
            $usuariosPorTreinamento = [];
        } else {
            $query = UserProgress::query();
            $this->aplicarEscopoUsuariosComuns($query, $request->user(), 'user');
            $query->whereHas('user', function ($userQuery) {
                $userQuery->kpiEligible();
            });

            if ($trainingFilter) {
                $query->whereHas('user', function ($q) use ($trainingFilter) {
                    $q->kpiEligible()->eligibleForTrainingKpi($trainingFilter);
                });
            }

            if ($request->filled('usuario_id')) {
                $query->where('user_id', $request->integer('usuario_id'));
            }

            if ($request->filled('tipo_usuario')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->kpiEligible()->where('tipo_usuario', $request->input('tipo_usuario'));
                });
            }

            if ($trainingFilter) {
                $query->where('training_id', $trainingFilter->id);
            }

            if ($request->filled('usuario_nome')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->kpiEligible()->where('nome', 'like', '%'.$request->input('usuario_nome').'%');
                });
            }

            if ($statusProgresso !== null && $statusProgresso !== '') {
                if (in_array($statusProgresso, ['1', 'true', 'concluido'], true)) {
                    $query->where('concluido', true);
                } elseif (in_array($statusProgresso, ['0', 'false', 'pendente', 'nao_finalizados'], true)) {
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

            $trainingIds = $treinamentosResumo->pluck('training_id')->toArray();
            $usuariosPorTreinamento = [];

            if (!empty($trainingIds)) {
                $progressByTraining = UserProgress::whereIn('training_id', $trainingIds)
                    ->with('user:id,nome,cpf,tipo_usuario')
                    ->whereHas('user', function ($uq) {
                        $uq->kpiEligible();
                    })
                    ->get()
                    ->groupBy('training_id');

                $mostrarNaoIniciados = in_array($statusProgresso, ['nao_iniciado', 'nao_finalizados'], true);
                $mostrarPendentes = in_array($statusProgresso, ['pendente', 'nao_finalizados', ''], true);
                $mostrarConcluidos = in_array($statusProgresso, ['concluido', ''], true);

                foreach ($trainingIds as $tId) {
                    $usuariosPorTreinamento[$tId] = [
                        'concluidos' => collect(),
                        'pendentes' => collect(),
                        'nao_iniciados' => collect(),
                    ];

                    $progressList = $progressByTraining->get($tId, collect());

                    foreach ($progressList as $p) {
                        if ($p->concluido && $mostrarConcluidos) {
                            $usuariosPorTreinamento[$tId]['concluidos']->push($p->user);
                        } elseif (!$p->concluido && $mostrarPendentes) {
                            $usuariosPorTreinamento[$tId]['pendentes']->push($p->user);
                        }
                    }

                    if ($mostrarNaoIniciados) {
                        $userIdsWithProgress = $progressList->pluck('user_id')->toArray();
                        $naoIniciadosQuery = User::kpiEligible()
                            ->whereDoesntHave('progress', function ($pq) use ($tId) {
                                $pq->where('training_id', $tId);
                            });
                        if (!empty($userIdsWithProgress)) {
                            $naoIniciadosQuery->whereNotIn('id', $userIdsWithProgress);
                        }
                        $usuariosPorTreinamento[$tId]['nao_iniciados'] = $naoIniciadosQuery
                            ->orderBy('nome')
                            ->get();
                    }
                }
            }

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

        // =====================================================================
        // LISTA COMPLETA POR USUÁRIO (Foco no Usuário)
        // Quando usuario_id é selecionado, lista TODOS os treinamentos do
        // usuário com seu status (concluído, pendente, não iniciado).
        // =====================================================================
        $focoUsuario = null;
        $focoUsuarioTreinamentos = collect();
        $focoUsuarioResumo = ['concluidos' => 0, 'pendentes' => 0, 'nao_iniciados' => 0, 'total' => 0];

        if ($request->filled('usuario_id')) {
            $focoUsuario = User::with('role')->find($request->integer('usuario_id'));

            if ($focoUsuario) {
                $todosTreinamentos = Training::where('status', 'ativo')->orderBy('titulo')->get();

                $progressMap = UserProgress::where('user_id', $focoUsuario->id)
                    ->with('training:id,titulo,tipo,carga_horaria,dias_validade')
                    ->get()
                    ->keyBy('training_id');

                $focoUsuarioTreinamentosFull = $todosTreinamentos->map(function ($training) use ($focoUsuario, $progressMap) {
                    $podeAcessar = $focoUsuario->canAccessTraining($training);

                    if (!$podeAcessar) {
                        return null;
                    }

                    $progress = $progressMap->get($training->id);

                    $row = new \stdClass;
                    $row->training = $training;
                    $row->progress = $progress;
                    $row->tem_progresso = $progress !== null;
                    $row->concluido = $progress ? $progress->concluido : false;
                    $row->porcentagem_assistida = $progress ? $progress->porcentagem_assistida : 0;
                    $row->tempo_assistido = $progress ? $progress->tempo_assistido : 0;
                    $row->avaliacao_aprovada = $progress ? $progress->avaliacao_aprovada : false;
                    $row->data_inicio = $progress ? $progress->data_inicio : null;
                    $row->data_conclusao = $progress ? $progress->data_conclusao : null;

                    if (!$progress) {
                        $row->status = 'nao_iniciado';
                    } elseif ($progress->concluido) {
                        $row->status = 'concluido';
                    } else {
                        $row->status = 'pendente';
                    }

                    return $row;
                })->filter()->values();

                $focoUsuarioResumo['total'] = $focoUsuarioTreinamentosFull->count();
                $focoUsuarioResumo['concluidos'] = $focoUsuarioTreinamentosFull->where('status', 'concluido')->count();
                $focoUsuarioResumo['pendentes'] = $focoUsuarioTreinamentosFull->where('status', 'pendente')->count();
                $focoUsuarioResumo['nao_iniciados'] = $focoUsuarioTreinamentosFull->where('status', 'nao_iniciado')->count();

                if ($statusProgresso && $statusProgresso !== 'nao_iniciado') {
                    if ($statusProgresso === 'nao_finalizados') {
                        $focoUsuarioTreinamentos = $focoUsuarioTreinamentosFull->filter(function ($item) {
                            return in_array($item->status, ['pendente', 'nao_iniciado']);
                        })->values();
                    } else {
                        $focoUsuarioTreinamentos = $focoUsuarioTreinamentosFull->where('status', $statusProgresso)->values();
                    }
                } else {
                    $focoUsuarioTreinamentos = $focoUsuarioTreinamentosFull;
                }
            }
        }

        // =====================================================================
        // LISTA COMPLETA POR TREINAMENTO (Foco no Treinamento)
        // Quando training_id é selecionado, lista TODOS os usuários elegíveis
        // com seu status (concluído, pendente, não iniciado).
        // =====================================================================
        $focoTreinamento = $trainingFilter;
        $focoTreinamentoUsuarios = collect();
        $focoTreinamentoResumo = ['concluidos' => 0, 'pendentes' => 0, 'nao_iniciados' => 0, 'total' => 0];

        if ($trainingFilter) {
            $todosUsuarios = User::kpiEligible()->orderBy('nome');
            $this->aplicarEscopoUsuariosComuns($todosUsuarios, $request->user());

            if ($request->filled('tipo_usuario')) {
                $todosUsuarios->where('tipo_usuario', $request->input('tipo_usuario'));
            }

            $todosUsuarios = $todosUsuarios->get()->filter(function ($user) use ($trainingFilter) {
                return $user->canAccessTraining($trainingFilter);
            })->values();

            $progressMap = UserProgress::where('training_id', $trainingFilter->id)
                ->whereIn('user_id', $todosUsuarios->pluck('id'))
                ->with('user:id,nome,cpf,tipo_usuario,status')
                ->get()
                ->keyBy('user_id');

            $focoTreinamentoUsuariosFull = $todosUsuarios->map(function ($user) use ($trainingFilter, $progressMap) {
                $progress = $progressMap->get($user->id);

                $row = new \stdClass;
                $row->user = $user;
                $row->progress = $progress;
                $row->tem_progresso = $progress !== null;
                $row->concluido = $progress ? $progress->concluido : false;
                $row->porcentagem_assistida = $progress ? $progress->porcentagem_assistida : 0;
                $row->tempo_assistido = $progress ? $progress->tempo_assistido : 0;
                $row->avaliacao_aprovada = $progress ? $progress->avaliacao_aprovada : false;
                $row->data_inicio = $progress ? $progress->data_inicio : null;
                $row->data_conclusao = $progress ? $progress->data_conclusao : null;

                if (!$progress) {
                    $row->status = 'nao_iniciado';
                } elseif ($progress->concluido) {
                    $row->status = 'concluido';
                } else {
                    $row->status = 'pendente';
                }

                return $row;
            })->values();

            $focoTreinamentoResumo['total'] = $focoTreinamentoUsuariosFull->count();
            $focoTreinamentoResumo['concluidos'] = $focoTreinamentoUsuariosFull->where('status', 'concluido')->count();
            $focoTreinamentoResumo['pendentes'] = $focoTreinamentoUsuariosFull->where('status', 'pendente')->count();
            $focoTreinamentoResumo['nao_iniciados'] = $focoTreinamentoUsuariosFull->where('status', 'nao_iniciado')->count();

            if ($statusProgresso && $statusProgresso !== 'nao_iniciado') {
                if ($statusProgresso === 'nao_finalizados') {
                    $focoTreinamentoUsuarios = $focoTreinamentoUsuariosFull->filter(function ($item) {
                        return in_array($item->status, ['pendente', 'nao_iniciado']);
                    })->values();
                } else {
                    $focoTreinamentoUsuarios = $focoTreinamentoUsuariosFull->where('status', $statusProgresso)->values();
                }
            } else {
                $focoTreinamentoUsuarios = $focoTreinamentoUsuariosFull;
            }
        }

        // Exportação CSV do resumo
        if ($request->filled('exportar_resumo')) {
            $headers = [
                'Content-Type' => 'text/csv',
                'Content-Disposition' => 'attachment; filename="resumo_treinamentos_' . date('Y-m-d_His') . '.csv"',
            ];

            $callback = function () use ($treinamentosResumo, $usuariosPorTreinamento, $statusProgresso) {
                $file = fopen('php://output', 'w');
                fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM UTF-8
                fputcsv($file, ['Treinamento', 'Tipo', 'Participações', 'Concluídas', 'Taxa Conclusão', 'Tempo Total', 'Usuários'], ';');

                foreach ($treinamentosResumo as $resumo) {
                    $taxa = $resumo->assistencias > 0 ? number_format(($resumo->concluidas / $resumo->assistencias) * 100, 1, ',', '.') . '%' : '0,0%';
                    $tempo = gmdate('H:i:s', (int) ($resumo->tempo_total_assistido ?? 0));

                    $usuarios = $usuariosPorTreinamento[$resumo->training_id] ?? null;
                    $nomes = [];
                    if ($usuarios) {
                        foreach ($usuarios['concluidos'] as $u) { $nomes[] = $u->nome . ' (concluído)'; }
                        foreach ($usuarios['pendentes'] as $u) { $nomes[] = $u->nome . ' (pendente)'; }
                        foreach ($usuarios['nao_iniciados'] as $u) { $nomes[] = $u->nome . ' (não iniciado)'; }
                    }

                    fputcsv($file, [
                        optional($resumo->training)->titulo ?? 'Removido',
                        optional($resumo->training)->tipo ?? '',
                        $resumo->assistencias,
                        $resumo->concluidas,
                        $taxa,
                        $tempo,
                        implode(', ', $nomes),
                    ], ';');
                }

                fclose($file);
            };

            return response()->stream($callback, 200, $headers);
        }

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
            'usuariosPorTreinamento' => $usuariosPorTreinamento ?? [],
            'conteudosPorTipo' => $conteudosPorTipo,
            'usuariosEmFerias' => $usuariosEmFerias,
            'usuariosEmFeriasLista' => $usuariosEmFeriasLista,
            'statusProgresso' => $statusProgresso,
            'focoUsuario' => $focoUsuario,
            'focoUsuarioTreinamentos' => $focoUsuarioTreinamentos,
            'focoUsuarioResumo' => $focoUsuarioResumo,
            'focoTreinamento' => $focoTreinamento,
            'focoTreinamentoUsuarios' => $focoTreinamentoUsuarios,
            'focoTreinamentoResumo' => $focoTreinamentoResumo,
        ]);
    }

    /**
     * Export filtered training report as PDF
     */
    public function relatorioTreinamentosPdf(Request $request)
    {
        // Reuse same logic to build $progressos but without pagination
        $statusProgresso = $request->input('status_progresso');
        $somenteFerias = $request->filled('somente_ferias');
        $trainingId = $request->filled('training_id') ? $request->integer('training_id') : null;
        $trainingFilter = $trainingId ? Training::findOrFail($trainingId) : null;

        if ($statusProgresso === 'nao_iniciado') {
            $usersQuery = User::query()->kpiEligible();
            $this->aplicarEscopoUsuariosComuns($usersQuery, $request->user());

            if ($request->filled('tipo_usuario')) {
                $usersQuery->where('tipo_usuario', $request->input('tipo_usuario'));
            }

            if ($request->filled('usuario_id')) {
                $usersQuery->where('id', $request->integer('usuario_id'));
            }

            if ($request->filled('usuario_nome')) {
                $usersQuery->where('nome', 'like', '%'.$request->input('usuario_nome').'%');
            }

            if ($somenteFerias) {
                $usersQuery->vacationInPeriod($request->input('data_inicio'), $request->input('data_fim'));
            }

            if ($trainingId) {
                $treinamentoNaoIniciado = $trainingFilter;
                $usersQuery->eligibleForTrainingKpi($treinamentoNaoIniciado);
                $usersQuery->whereDoesntHave('progress', function ($q) use ($treinamentoNaoIniciado) {
                    $q->where('training_id', $treinamentoNaoIniciado->id);
                });
            } else {
                $usersQuery->whereDoesntHave('progress');
            }

            $usersNaoIniciados = $usersQuery->orderBy('nome')->get();
            $progressos = $usersNaoIniciados->map(function ($user) use ($treinamentoNaoIniciado) {
                $row = new \stdClass;
                $row->user = $user;
                $row->training = $treinamentoNaoIniciado;
                $row->tempo_assistido = 0;
                $row->porcentagem_assistida = 0;
                $row->concluido = false;
                $row->data_inicio = null;
                $row->data_conclusao = null;

                return $row;
            });
        } else {
            $query = UserProgress::query();
            $this->aplicarEscopoUsuariosComuns($query, $request->user(), 'user');
            $query->whereHas('user', function ($userQuery) {
                $userQuery->kpiEligible();
            });

            if ($trainingFilter) {
                $query->whereHas('user', function ($q) use ($trainingFilter) {
                    $q->kpiEligible()->eligibleForTrainingKpi($trainingFilter);
                });
            }

            if ($request->filled('usuario_id')) {
                $query->where('user_id', $request->integer('usuario_id'));
            }

            if ($request->filled('tipo_usuario')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->kpiEligible()->where('tipo_usuario', $request->input('tipo_usuario'));
                });
            }

            if ($trainingFilter) {
                $query->where('training_id', $trainingFilter->id);
            }

            if ($request->filled('usuario_nome')) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->kpiEligible()->where('nome', 'like', '%'.$request->input('usuario_nome').'%');
                });
            }

            if ($somenteFerias) {
                $query->whereHas('user', function ($q) use ($request) {
                    $q->vacationInPeriod($request->input('data_inicio'), $request->input('data_fim'));
                });
            }

            if ($statusProgresso !== null && $statusProgresso !== '') {
                if (in_array($statusProgresso, ['1', 'true', 'concluido'], true)) {
                    $query->where('concluido', true);
                } elseif (in_array($statusProgresso, ['0', 'false', 'pendente', 'nao_finalizados'], true)) {
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
                ->get();
        }

        // Determine if single training filtered
        $multiTraining = $trainingId === null;
        $subtitle = '';
        $trainingObj = null;
        if ($trainingId) {
            $trainingObj = Training::find($trainingId);
            $subtitle = $trainingObj ? $trainingObj->titulo : 'Filtro de treinamento aplicado';
        } else {
            $subtitle = 'Diálogo Semanal de Segurança - DSS';
        }

        // Render HTML view for PDF
        $html = view('relatorios.treinamentos_pdf', [
            'progressos' => $progressos,
            'multiTraining' => $multiTraining,
            'subtitle' => $subtitle,
            'training' => $trainingObj,
        ])->render();

        // Generate PDF with TCPDF (Landscape orientation)
        $pdf = new TCPDF('L');
        $pdf->SetCreator('Plataforma DSS');
        $pdf->SetAuthor('Plataforma DSS');
        $pdf->SetTitle('Relatorio de Treinamentos');
        $pdf->SetMargins(8, 18, 8);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->SetLineWidth(0.2);
        $pdf->AddPage();

        // Add logo if exists
        $logoPath = public_path('images/logo-comav-transportes.png');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 20, 22, '', '', '', '', false, 300, '', false, false, 0);
            $pdf->SetY(18);
        }

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Relatório_Participacoes.pdf', 'D');
    }

    /**
     * Exporta PDF do resumo de desempenho por conteúdo (mesmos filtros da tela)
     */
    public function relatorioTreinamentosResumoPdf(Request $request)
    {
        $statusProgresso = $request->input('status_progresso');
        $somenteFerias = $request->filled('somente_ferias');
        $trainingId = $request->filled('training_id') ? $request->integer('training_id') : null;
        $trainingFilter = $trainingId ? Training::findOrFail($trainingId) : null;

        // Construir a mesma query do relatorioTreinamentos (ramo nao_finalizados/normal)
        $query = UserProgress::query();
        $this->aplicarEscopoUsuariosComuns($query, $request->user(), 'user');
        $query->whereHas('user', function ($userQuery) {
            $userQuery->kpiEligible();
        });

        if ($trainingFilter) {
            $query->whereHas('user', function ($q) use ($trainingFilter) {
                $q->kpiEligible()->eligibleForTrainingKpi($trainingFilter);
            });
        }

        if ($request->filled('usuario_id')) {
            $query->where('user_id', $request->integer('usuario_id'));
        }

        if ($request->filled('tipo_usuario')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->kpiEligible()->where('tipo_usuario', $request->input('tipo_usuario'));
            });
        }

        if ($trainingFilter) {
            $query->where('training_id', $trainingFilter->id);
        }

        if ($request->filled('usuario_nome')) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->kpiEligible()->where('nome', 'like', '%'.$request->input('usuario_nome').'%');
            });
        }

        if ($somenteFerias) {
            $query->whereHas('user', function ($q) use ($request) {
                $q->vacationInPeriod($request->input('data_inicio'), $request->input('data_fim'));
            });
        }

        if ($statusProgresso !== null && $statusProgresso !== '') {
            if (in_array($statusProgresso, ['1', 'true', 'concluido'], true)) {
                $query->where('concluido', true);
            } elseif (in_array($statusProgresso, ['0', 'false', 'pendente', 'nao_finalizados'], true)) {
                $query->where('concluido', false);
            }
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_inicio', '>=', $request->input('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_conclusao', '<=', $request->input('data_fim'));
        }

        $treinamentosResumo = (clone $query)
            ->select('training_id')
            ->selectRaw('COUNT(*) as assistencias')
            ->selectRaw('SUM(CASE WHEN concluido = 1 THEN 1 ELSE 0 END) as concluidas')
            ->selectRaw('SUM(COALESCE(tempo_assistido, 0)) as tempo_total_assistido')
            ->groupBy('training_id')
            ->with(['training:id,titulo,tipo,carga_horaria'])
            ->orderByDesc('assistencias')
            ->get();

        $totalTreinamentos = $treinamentosResumo->count();
        $totalConcluidas = $treinamentosResumo->sum('concluidas');
        $totalAssistencias = $treinamentosResumo->sum('assistencias');
        $taxaGeral = $totalAssistencias > 0 ? number_format(($totalConcluidas / $totalAssistencias) * 100, 2, ',', '.') : '0,00';
        $tempoTotalFormatado = $this->formatarTempoEmHoras($treinamentosResumo->sum('tempo_total_assistido'));

        // Buscar usuários por treinamento
        $trainingIds = $treinamentosResumo->pluck('training_id')->toArray();
        $usuariosPorTreinamento = [];

        if (!empty($trainingIds)) {
            $progressByTraining = UserProgress::whereIn('training_id', $trainingIds)
                ->with('user:id,nome,cpf,tipo_usuario')
                ->whereHas('user', function ($uq) {
                    $uq->kpiEligible();
                })
                ->get()
                ->groupBy('training_id');

            $mostrarNaoIniciados = in_array($statusProgresso, ['nao_iniciado', 'nao_finalizados'], true);
            $mostrarPendentes = in_array($statusProgresso, ['pendente', 'nao_finalizados', ''], true);
            $mostrarConcluidos = in_array($statusProgresso, ['concluido', ''], true);

            foreach ($trainingIds as $tId) {
                $usuariosPorTreinamento[$tId] = [
                    'concluidos' => collect(),
                    'pendentes' => collect(),
                    'nao_iniciados' => collect(),
                ];

                $progressList = $progressByTraining->get($tId, collect());

                foreach ($progressList as $p) {
                    if ($p->concluido && $mostrarConcluidos) {
                        $usuariosPorTreinamento[$tId]['concluidos']->push($p->user);
                    } elseif (!$p->concluido && $mostrarPendentes) {
                        $usuariosPorTreinamento[$tId]['pendentes']->push($p->user);
                    }
                }

                if ($mostrarNaoIniciados) {
                    $userIdsWithProgress = $progressList->pluck('user_id')->toArray();
                    $naoIniciadosQuery = User::kpiEligible()
                        ->whereDoesntHave('progress', function ($pq) use ($tId) {
                            $pq->where('training_id', $tId);
                        });
                    if (!empty($userIdsWithProgress)) {
                        $naoIniciadosQuery->whereNotIn('id', $userIdsWithProgress);
                    }
                    $usuariosPorTreinamento[$tId]['nao_iniciados'] = $naoIniciadosQuery
                        ->orderBy('nome')
                        ->get();
                }
            }
        }

        // Subtítulo com filtro ativo
        $subtituloFiltro = '';
        if ($request->filled('usuario_id')) {
            $usuario = User::find($request->integer('usuario_id'));
            $subtituloFiltro .= 'Usuário: ' . ($usuario ? $usuario->nome : '—') . ' | ';
        }
        if ($trainingFilter) {
            $subtituloFiltro .= 'Treinamento: ' . $trainingFilter->titulo . ' | ';
        }
        if ($statusProgresso) {
            $labelStatus = match($statusProgresso) {
                'concluido' => 'Concluídos',
                'pendente' => 'Pendentes',
                'nao_iniciado' => 'Não iniciados',
                'nao_finalizados' => 'Não finalizados (pendente + não iniciado)',
                default => $statusProgresso,
            };
            $subtituloFiltro .= 'Status: ' . $labelStatus . ' | ';
        }
        if ($request->filled('tipo_usuario')) {
            $subtituloFiltro .= 'Tipo: ' . ucfirst(str_replace('_', ' ', $request->input('tipo_usuario'))) . ' | ';
        }
        $subtituloFiltro = rtrim($subtituloFiltro, ' | ');

        // Render HTML
        $html = view('relatorios.treinamentos_resumo_pdf', [
            'treinamentosResumo' => $treinamentosResumo,
            'usuariosPorTreinamento' => $usuariosPorTreinamento,
            'totalTreinamentos' => $totalTreinamentos,
            'totalConcluidas' => $totalConcluidas,
            'taxaGeral' => $taxaGeral,
            'tempoTotalFormatado' => $tempoTotalFormatado,
            'subtituloFiltro' => $subtituloFiltro,
        ])->render();

        // Gerar PDF
        $pdf = new TCPDF('L');
        $pdf->SetCreator('Plataforma DSS');
        $pdf->SetAuthor('Plataforma DSS');
        $pdf->SetTitle('Resumo de Desempenho por Conteúdo');
        $pdf->SetMargins(8, 18, 8);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->SetLineWidth(0.2);
        $pdf->AddPage();

        $logoPath = public_path('images/logo-comav-transportes.png');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 20, 22, '', '', '', '', false, 300, '', false, false, 0);
            $pdf->SetY(18);
        }

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Resumo_Desempenho_Conteudo.pdf', 'D');
    }

    /**
     * Relatório de usuários com histórico de treinamentos
     */
    public function relatorioUsuarios(Request $request)
    {
        $query = User::query()->kpiEligible();

        // Admin sempre enxerga apenas usuários comuns; Super Admin pode incluir administradores via filtro.
        if ($request->user()->isSuperAdmin() && $request->filled('incluir_adm')) {
            // Sem escopo adicional: visão completa.
        } else {
            $this->aplicarEscopoUsuariosComuns($query, $request->user());
        }

        if ($request->filled('usuario_id')) {
            $query->where('id', request('usuario_id'));
        } elseif ($request->filled('nome')) {
            $query->where('nome', 'like', '%'.request('nome').'%');
        }

        if ($request->filled('cpf')) {
            $cpf = preg_replace('/\D/', '', request('cpf'));
            $query->where('cpf', 'like', '%'.$cpf.'%');
        }

        if ($request->filled('status')) {
            $query->where('status', request('status'));
        }

        if ($request->filled('tipo_usuario')) {
            $query->where('tipo_usuario', $request->input('tipo_usuario'));
        }

        if ($request->filled('somente_ferias')) {
            $query->vacationInPeriod(now(), now());
        }

        $usuarios = (clone $query)
            ->with(['progress', 'certificates'])
            ->withCount(['progress', 'certificates'])
            ->withSum('progress as tempo_total_assistido', 'tempo_assistido')
            ->withMax('progress as ultima_atividade_em', 'data_conclusao')
            ->orderBy('nome')
            ->paginate(15);

        $usuariosEmFeriasBase = User::query()->vacationInPeriod(now(), now());
        $this->aplicarEscopoUsuariosComuns($usuariosEmFeriasBase, $request->user());

        if ($request->filled('tipo_usuario')) {
            $usuariosEmFeriasBase->where('tipo_usuario', $request->input('tipo_usuario'));
        }

        if ($request->filled('usuario_id')) {
            $usuariosEmFeriasBase->where('id', $request->integer('usuario_id'));
        }

        if ($request->filled('nome')) {
            $usuariosEmFeriasBase->where('nome', 'like', '%'.$request->input('nome').'%');
        }

        if ($request->filled('cpf')) {
            $cpf = preg_replace('/\D/', '', request('cpf'));
            $usuariosEmFeriasBase->where('cpf', 'like', '%'.$cpf.'%');
        }

        if ($request->filled('status')) {
            $usuariosEmFeriasBase->where('status', $request->input('status'));
        }

        $usuariosEmFerias = (clone $usuariosEmFeriasBase)->count();
        $usuariosEmFeriasLista = (clone $usuariosEmFeriasBase)
            ->orderBy('nome')
            ->take(8)
            ->get();

        // Tipos e lista de usuários para filtros dinâmicos
        $userTypes = User::kpiEligible()->select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');
        if ($request->filled('tipo_usuario')) {
            $users = User::kpiEligible()->where('tipo_usuario', $request->input('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::kpiEligible()->orderBy('nome')->get();
        }

        $totalUsuarios = (clone $query)->count();
        $usuariosAtivos = (clone $query)->where('status', 'ativo')->count();
        $usuariosComTreinamentos = (clone $query)->whereHas('progress')->count();
        $usuariosComCertificados = (clone $query)->whereHas('certificates')->count();
        $tempoTotalAssistido = UserProgress::query()
            ->whereHas('user', function ($q) use ($request) {
                $q->kpiEligible();

                if ($request->user()->isSuperAdmin() && $request->filled('incluir_adm')) {

                    if ($somenteFerias) {
                        $query->whereHas('user', function ($q) use ($request) {
                            $q->vacationInPeriod($request->input('data_inicio'), $request->input('data_fim'));
                        });
                    }

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
            'usuariosEmFerias' => $usuariosEmFerias,
            'usuariosEmFeriasLista' => $usuariosEmFeriasLista,
            'filtrosAtivos' => $this->verificarFiltrosAtivos($request),
            'userTypes' => $userTypes,
            'users' => $users,
        ]);
    }

    /**
     * Página de IA / Análises (super admin)
     */
    public function relatoriosIa(Request $request)
    {
        $treinamentos = Training::orderBy('titulo')->get();

        return view('admin.relatorios_ia', [
            'treinamentos' => $treinamentos,
        ]);
    }

    /**
     * Análise local (heurística) - retorna o relatório analítico detalhado
     */
    public function analyzeLocal(Request $request, TrainingAnalyzer $analyzer)
    {
        $trainingId = $request->input('training_id');

        // Validar se um treinamento foi selecionado
        if (empty($trainingId)) {
            return response()->json([
                'error' => 'Nenhum treinamento selecionado. Selecione um treinamento para gerar análise.',
                'concluidos' => 0,
            ], 400);
        }

        // Verificar se o treinamento existe
        $training = Training::find($trainingId);
        if (! $training) {
            return response()->json([
                'error' => 'Treinamento não encontrado no sistema.',
                'concluidos' => 0,
            ], 404);
        }

        $report = $analyzer->analyzeDetailed($training);

        // Validar se há dados
        if (empty($report['usuarios']) && ($report['kpis']['concluidos'] ?? 0) === 0) {
            return response()->json(array_merge($report, [
                'error' => "Sem dados de conclusão para '{$training->titulo}'.\n\nNenhum usuário finalizou este treinamento ainda.",
            ]));
        }

        return response()->json($report);
    }

    /**
     * Análise via IA (Gemini) - retorna o relatório detalhado + parecer executivo
     */
    public function analyzeAi(Request $request, TrainingAnalyzer $analyzer, AiSummarizer $ai)
    {
        $trainingId = $request->input('training_id');

        // Validar se um treinamento foi selecionado
        if (empty($trainingId)) {
            return response()->json([
                'source' => 'error',
                'ai_summary' => null,
                'error' => 'Nenhum treinamento selecionado. Selecione um treinamento para gerar análise com IA.',
            ], 400);
        }

        // Verificar se o treinamento existe
        $training = Training::find($trainingId);
        if (! $training) {
            return response()->json([
                'source' => 'error',
                'ai_summary' => null,
                'error' => 'Treinamento não encontrado no sistema.',
            ], 404);
        }

        $report = $analyzer->analyzeDetailed($training);

        // Validar se há dados
        if (empty($report['usuarios']) && ($report['kpis']['concluidos'] ?? 0) === 0) {
            return response()->json([
                'source' => 'error',
                'ai_summary' => null,
                'report' => $report,
                'error' => "Sem dados de conclusão para '{$training->titulo}'.\n\nNenhum usuário finalizou este treinamento ainda. Não há dados para análise.",
            ]);
        }

        $res = $ai->summarize($report, $training->titulo);

        return response()->json(array_merge($res, ['report' => $report]));
    }

    /**
     * Exporta o relatório analítico de IA em PDF (TCPDF)
     */
    public function relatorioIaPdf(Request $request, TrainingAnalyzer $analyzer, AiSummarizer $ai)
    {
        $trainingId = $request->filled('training_id') ? $request->integer('training_id') : null;

        if (! $trainingId) {
            return redirect()->route('relatorios.ia')->with('error', 'Selecione um treinamento para gerar o PDF.');
        }

        $training = Training::findOrFail($trainingId);
        $report = $analyzer->analyzeDetailed($training);
        $parecer = $ai->summarize($report, $training->titulo);

        $html = view('relatorios.relatorio_ia_pdf', [
            'report' => $report,
            'training' => $training,
            'ai_summary' => $parecer['ai_summary'],
            'ai_source' => $parecer['source'],
        ])->render();

        // Generate PDF with TCPDF (Landscape orientation)
        $pdf = new TCPDF('L');
        $pdf->SetCreator('Plataforma DSS');
        $pdf->SetAuthor('Plataforma DSS');
        $pdf->SetTitle('Relatorio Analitico - '.$training->titulo);
        $pdf->SetMargins(8, 18, 8);
        $pdf->SetAutoPageBreak(true, 18);
        $pdf->SetDrawColor(150, 150, 150);
        $pdf->SetLineWidth(0.2);
        $pdf->AddPage();

        // Add logo if exists
        $logoPath = public_path('images/logo-comav-transportes.png');
        if (file_exists($logoPath)) {
            $pdf->Image($logoPath, 10, 20, 22, '', '', '', '', false, 300, '', false, false, 0);
            $pdf->SetY(18);
        }

        $pdf->SetFont('Helvetica', '', 8);
        $pdf->writeHTML($html, true, false, true, false, '');
        $pdf->Output('Relatorio_Analitico_'.str_replace(' ', '_', $training->titulo).'.pdf', 'D');
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
        $trainingFilter = $trainingId ? Training::findOrFail($trainingId) : null;

        $usuariosBase = User::query()->kpiEligible($periodoInicio, $periodoFim);
        if ($trainingFilter) {
            $usuariosBase->eligibleForTrainingKpi($trainingFilter);
        }
        $this->aplicarEscopoUsuariosComuns($usuariosBase, $user);
        if ($request->filled('tipo_usuario')) {
            $usuariosBase->where('tipo_usuario', $request->input('tipo_usuario'));
        }
        if ($request->filled('usuario_id')) {
            $usuariosBase->where('id', $request->integer('usuario_id'));
        }

        $certificadosBase = Certificate::query();
        $this->aplicarEscopoUsuariosComuns($certificadosBase, $user, 'user');
        $certificadosBase->whereHas('user', function ($q) use ($periodoInicio, $periodoFim, $trainingFilter) {
            $q->kpiEligible($periodoInicio, $periodoFim);
            if ($trainingFilter) {
                $q->eligibleForTrainingKpi($trainingFilter);
            }
        });
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
        $progressBase->whereHas('user', function ($q) use ($periodoInicio, $periodoFim, $trainingFilter) {
            $q->kpiEligible($periodoInicio, $periodoFim);
            if ($trainingFilter) {
                $q->eligibleForTrainingKpi($trainingFilter);
            }
        });
        if ($request->filled('tipo_usuario')) {
            $progressBase->whereHas('user', function ($q) use ($request) {
                $q->kpiEligible()->where('tipo_usuario', $request->input('tipo_usuario'));
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

        if ($request->filled('somente_ferias')) {
            $usuariosBase->vacationInPeriod($periodoInicio, $periodoFim);
            $certificadosBase->whereHas('user', function ($q) use ($periodoInicio, $periodoFim) {
                $q->vacationInPeriod($periodoInicio, $periodoFim);
            });
            $progressBase->whereHas('user', function ($q) use ($periodoInicio, $periodoFim) {
                $q->vacationInPeriod($periodoInicio, $periodoFim);
            });
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
        $usuariosEmFeriasBase = User::query()->vacationInPeriod($periodoInicio, $periodoFim);
        $this->aplicarEscopoUsuariosComuns($usuariosEmFeriasBase, $user);
        if ($request->filled('tipo_usuario')) {
            $usuariosEmFeriasBase->where('tipo_usuario', $request->input('tipo_usuario'));
        }
        if ($request->filled('usuario_id')) {
            $usuariosEmFeriasBase->where('id', $request->integer('usuario_id'));
        }
        $usuariosEmFerias = (clone $usuariosEmFeriasBase)->count();
        $usuariosEmFeriasLista = (clone $usuariosEmFeriasBase)
            ->orderBy('nome')
            ->take(8)
            ->get();

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
        $treinamentosComProgressos = Training::withCount(['progress' => function ($q) {
            $q->whereHas('user', function ($u) {
                $u->kpiEligible();
            });
        }])
            ->withCount(['progress as concluidos_count' => function ($q) {
                $q->where('concluido', true)
                    ->whereHas('user', function ($u) {
                        $u->kpiEligible();
                    });
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

        $usuariosSemTreinamentoBase = User::query()->kpiEligible($periodoInicio, $periodoFim)->whereDoesntHave('progress');
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
        $userTypes = User::kpiEligible($periodoInicio, $periodoFim)->select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');
        if ($request->filled('tipo_usuario')) {
            $users = User::kpiEligible($periodoInicio, $periodoFim)->where('tipo_usuario', $request->input('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::kpiEligible($periodoInicio, $periodoFim)->orderBy('nome')->get();
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
            'usuariosEmFerias' => $usuariosEmFerias,
            'usuariosEmFeriasLista' => $usuariosEmFeriasLista,
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
            $query->whereHas('user', function ($q) {
                $q->kpiEligible()->where('nome', 'like', '%'.request('usuario_nome').'%');
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

        $certificados = $query->whereHas('user', function ($q) {
            $q->kpiEligible();
        })->with(['user', 'training'])->get();

        $filename = 'certificados_'.now()->format('Y-m-d_H-i-s').'.csv';

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
               $request->filled('status_validade') ||
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
    private function aplicarEscopoUsuariosComuns($query, User $user, ?string $relation = null): void
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
