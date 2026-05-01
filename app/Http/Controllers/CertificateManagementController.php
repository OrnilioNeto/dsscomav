<?php

namespace App\Http\Controllers;

use App\Models\Certificate;
use App\Models\Training;
use App\Models\User;
use App\Models\UserProgress;
use Illuminate\Http\Request;

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
        $query = UserProgress::query();
        $this->aplicarEscopoUsuariosComuns($query, $request->user(), 'user');

        // Filtros
        // Filtrar por tipo de usuário ou por usuário específico
        if ($request->filled('usuario_id')) {
            $query->where('user_id', request('usuario_id'));
        } elseif ($request->filled('tipo_usuario')) {
            $query->whereHas('user', function($q) {
                $q->where('tipo_usuario', request('tipo_usuario'));
            });
        }

        if ($request->filled('training_id')) {
            $query->where('training_id', request('training_id'));
        }

        if ($request->filled('usuario_nome')) {
            $query->whereHas('user', function($q) {
                $q->where('nome', 'like', '%' . request('usuario_nome') . '%');
            });
        }

        if ($request->filled('concluido')) {
            $query->where('concluido', request('concluido') === '1');
        }

        if ($request->filled('data_inicio')) {
            $query->whereDate('data_inicio_assistencia', '>=', request('data_inicio'));
        }

        if ($request->filled('data_fim')) {
            $query->whereDate('data_finalizacao_assistencia', '<=', request('data_fim'));
        }

        $progressos = $query->with(['user', 'training'])->paginate(15);
        $treinamentos = Training::orderBy('titulo')->get();

        // Tipos de usuário para filtro e lista dinâmica de usuários
        $userTypes = User::select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');
        if ($request->filled('tipo_usuario')) {
            $users = User::where('tipo_usuario', request('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::orderBy('nome')->get();
        }

        // Estatísticas
        $totalAssistencias = UserProgress::count();
        $concluidas = UserProgress::where('concluido', true)->count();
        $taxaGeral = $totalAssistencias > 0 ? ($concluidas / $totalAssistencias) * 100 : 0;

        $tempoMedioAssistido = UserProgress::avg('tempo_assistido');
        $tempoMedioFormatado = $tempoMedioAssistido ? gmdate('H:i:s', $tempoMedioAssistido) : '0:00:00';

        return view('relatorios.treinamentos', [
            'progressos' => $progressos,
            'treinamentos' => $treinamentos,
            'totalAssistencias' => $totalAssistencias,
            'concluidas' => $concluidas,
            'taxaGeral' => number_format($taxaGeral, 2, ',', '.'),
            'tempoMedioFormatado' => $tempoMedioFormatado,
            'filtrosAtivos' => $this->verificarFiltrosAtivos($request),
            'userTypes' => $userTypes,
            'users' => $users,
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
            $query->where('tipo_usuario', request('tipo_usuario'));
        }

        $usuarios = $query->with(['progress', 'certificates'])
            ->orderBy('nome')
            ->paginate(15);

        // Tipos e lista de usuários para filtros dinâmicos
        $userTypes = User::select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');
        if ($request->filled('tipo_usuario')) {
            $users = User::where('tipo_usuario', request('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::orderBy('nome')->get();
        }

        $totalUsuarios = User::count();
        $usuariosAtivos = User::where('status', 'ativo')->count();

        return view('relatorios.usuarios', [
            'usuarios' => $usuarios,
            'totalUsuarios' => $totalUsuarios,
            'usuariosAtivos' => $usuariosAtivos,
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

        // Estatísticas gerais
        $usuariosBase = User::query();
        $this->aplicarEscopoUsuariosComuns($usuariosBase, $user);
        if ($request->filled('tipo_usuario')) {
            $usuariosBase->where('tipo_usuario', request('tipo_usuario'));
        }
        if ($request->filled('usuario_id')) {
            $usuariosBase->where('id', request('usuario_id'));
        }
        $totalUsuarios = $usuariosBase->count();

        $totalTreinamentos = Training::count();
        $certificadosBase = Certificate::query();
        $this->aplicarEscopoUsuariosComuns($certificadosBase, $user, 'user');
        if ($request->filled('usuario_id')) {
            $certificadosBase->where('user_id', request('usuario_id'));
        }
        if ($request->filled('training_tipo')) {
            $certificadosBase->whereHas('training', function ($q) {
                $q->where('tipo', request('training_tipo'));
            });
        }
        $totalCertificados = $certificadosBase->count();

        $usuariosAtivosBase = User::where('status', 'ativo');
        $this->aplicarEscopoUsuariosComuns($usuariosAtivosBase, $user);
        $usuariosAtivos = $usuariosAtivosBase->count();

        // Por tipo de usuário
        $usuariosPorTipoBase = User::query();
        $this->aplicarEscopoUsuariosComuns($usuariosPorTipoBase, $user);
        if ($request->filled('tipo_usuario')) {
            $usuariosPorTipoBase->where('tipo_usuario', request('tipo_usuario'));
        }
        $usuariosPorTipo = $usuariosPorTipoBase
            ->groupBy('tipo_usuario')
            ->selectRaw('tipo_usuario, count(*) as total')
            ->get();

        // Treinamentos mais assistidos
        $treinamentosMaisAssistidos = Training::withCount(['progress'])
            ->orderBy('progress_count', 'desc')
            ->take(10)
            ->get();

        // Taxa de conclusão por treinamento
        $taxaConclusao = [];
        foreach (Training::all() as $training) {
            $total = $training->progress()->count();
            $concluidos = $training->progress()->where('concluido', true)->count();
            $taxaConclusao[$training->id] = $total > 0 ? ($concluidos / $total) * 100 : 0;
        }

        // Tempo médio de assistência
        $tempoMedioBase = UserProgress::query();
        $this->aplicarEscopoUsuariosComuns($tempoMedioBase, $user, 'user');
        $tempoMedioTotal = $tempoMedioBase->avg('tempo_assistido');
        $tempoMedioFormatado = gmdate('H:i:s', $tempoMedioTotal ?? 0);

        // Usuários sem nenhum treinamento
        $usuariosSemTreinamentoBase = User::whereDoesntHave('progress');
        $this->aplicarEscopoUsuariosComuns($usuariosSemTreinamentoBase, $user);
        $usuariosSemTreinamento = $usuariosSemTreinamentoBase->count();

        // Certificados por mês (últimos 12 meses)
        $certificadosPorMesBase = Certificate::query();
        $this->aplicarEscopoUsuariosComuns($certificadosPorMesBase, $user, 'user');
        // Usar strftime para compatibilidade com SQLite
        $certificadosPorMes = $certificadosPorMesBase
            ->selectRaw("strftime('%m', data_emissao) as mes, strftime('%Y', data_emissao) as ano, COUNT(*) as total")
            ->whereRaw("strftime('%Y', data_emissao) >= ?", [now()->subYear()->year])
            ->groupBy('ano', 'mes')
            ->orderBy('ano', 'asc')
            ->orderBy('mes', 'asc')
            ->get();

        // Tipos e usuários para filtros dinâmicos
        $userTypes = User::select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario');
        if ($request->filled('tipo_usuario')) {
            $users = User::where('tipo_usuario', request('tipo_usuario'))->orderBy('nome')->get();
        } else {
            $users = User::orderBy('nome')->get();
        }

        return view('relatorios.auditoria', [
            'totalUsuarios' => $totalUsuarios,
            'totalTreinamentos' => $totalTreinamentos,
            'totalCertificados' => $totalCertificados,
            'usuariosAtivos' => $usuariosAtivos,
            'usuariosPorTipo' => $usuariosPorTipo,
            'treinamentosMaisAssistidos' => $treinamentosMaisAssistidos,
            'taxaConclusao' => $taxaConclusao,
            'tempoMedioFormatado' => $tempoMedioFormatado,
            'usuariosSemTreinamento' => $usuariosSemTreinamento,
            'certificadosPorMes' => $certificadosPorMes,
            'userTypes' => $userTypes,
            'users' => $users,
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
