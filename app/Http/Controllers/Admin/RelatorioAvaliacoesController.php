<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use App\Models\Training;
use App\Models\TrainingLog;
use App\Models\User;
use App\Models\UserProgress;
use App\Services\AuditLogger;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use TCPDF;

/**
 * Relatório gerencial de avaliações: traz, por usuário e treinamento,
 * a nota obtida, o resultado (aprovado/reprovado/pendente), tentativas,
 * datas e horários de início/conclusão e o certificado emitido.
 */
class RelatorioAvaliacoesController extends Controller
{
    private const LIMITE_CSV = 50000;

    public function index(Request $request)
    {
        $viewer = $request->user();

        $resultados = $this->resultadosQuery($request, $viewer)
            ->with(['user', 'training'])
            ->orderByDesc('data_conclusao')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $this->anexarCertificados($resultados->getCollection());

        $tentativas = $this->tentativasQuery($request, $viewer)
            ->with(['user', 'training'])
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(25, ['*'], 'page_tentativas')
            ->withQueryString();

        return view('relatorios.avaliacoes', [
            'resultados' => $resultados,
            'tentativas' => $tentativas,
            'resumo' => $this->resumo($request, $viewer),
            'desempenhoPorTreinamento' => $this->desempenhoPorTreinamento($request, $viewer),
            'metricasExclusao' => $this->metricasExclusao(),
            'userTypes' => User::query()->auditEligible()->visibleToViewer($viewer)
                ->select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario'),
            'users' => User::query()->auditEligible()->visibleToViewer($viewer)->orderBy('nome')->get(),
            'treinamentos' => Training::orderByReleaseDate('desc')->get(),
            'eventos' => $this->eventosDisponiveis(),
        ]);
    }

    public function csv(Request $request)
    {
        $viewer = $request->user();
        $tipo = $request->input('tipo_exportacao', 'resultados');

        app(AuditLogger::class)->log('exported', [
            'module' => 'certificates',
            'description' => 'Exportou o relatório de avaliações/notas em CSV ('.$tipo.')',
        ]);

        return $tipo === 'tentativas'
            ? $this->csvTentativas($request, $viewer)
            : $this->csvResultados($request, $viewer);
    }

    public function pdf(Request $request)
    {
        $viewer = $request->user();

        app(AuditLogger::class)->log('exported', [
            'module' => 'certificates',
            'description' => 'Exportou o relatório de avaliações/notas em PDF',
        ]);

        $desempenho = $this->desempenhoPorTreinamento($request, $viewer);

        $resultados = $this->resultadosQuery($request, $viewer)
            ->with(['user', 'training'])
            ->orderByDesc('data_conclusao')
            ->orderByDesc('id')
            ->limit(1500)
            ->get();
        $this->anexarCertificados($resultados);

        $html = view('relatorios.avaliacoes_pdf', [
            'resumo' => $this->resumo($request, $viewer),
            'desempenhoPorTreinamento' => $desempenho,
            'resultados' => $resultados,
            'metricasExclusao' => $this->metricasExclusao(),
            'filtrosAtivos' => $this->filtrosAtivos($request),
            'geradoEm' => now(),
            'geradoPor' => $viewer->nome,
            'hashRecorte' => $this->hashRecorte($request, $viewer),
        ])->render();

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(plataforma_nome());
        $pdf->SetTitle('Relatório de Avaliações e Notas');
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'Relatorio_Avaliacoes_'.now()->format('Y-m-d_H-i').'.pdf';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // =========================================================================
    // Consultas base
    // =========================================================================

    private function resultadosQuery(Request $request, User $viewer): Builder
    {
        [$inicio, $fim] = $this->periodo($request);
        $colunaPeriodo = $request->input('base_data', 'conclusao') === 'inicio' ? 'data_inicio' : 'data_conclusao';

        return UserProgress::query()
            ->whereHas('user', function ($q) use ($request, $viewer) {
                $q->auditEligible()->visibleToViewer($viewer);
                $this->aplicarFiltrosUsuario($q, $request);
            })
            ->when($request->filled('training_id'), fn ($q) => $q->where('training_id', $request->integer('training_id')))
            ->when($request->filled('training_tipo'), function ($q) use ($request) {
                $q->whereHas('training', fn ($t) => $t->where('tipo', $request->input('training_tipo')));
            })
            ->when($request->filled('tipo_treinamento'), function ($q) use ($request) {
                $q->whereHas('training', fn ($t) => $t->where('tipo_treinamento', $request->input('tipo_treinamento')));
            })
            ->when($request->filled('somente_com_prova'), function ($q) {
                $q->whereHas('training', function ($t) {
                    $t->whereNotNull('avaliacao_pergunta')->orWhereHas('questions');
                });
            })
            ->when($request->filled('nota_min'), fn ($q) => $q->where('avaliacao_nota', '>=', $request->integer('nota_min')))
            ->when($request->filled('nota_max'), fn ($q) => $q->where('avaliacao_nota', '<=', $request->integer('nota_max')))
            ->when($request->filled('resultado'), function ($q) use ($request) {
                match ($request->input('resultado')) {
                    'aprovado' => $q->where('avaliacao_aprovada', true),
                    'reprovado' => $q->where('avaliacao_aprovada', false)->whereNotNull('avaliacao_nota'),
                    'sem_nota' => $q->whereNull('avaliacao_nota'),
                    'concluido' => $q->where('concluido', true),
                    'em_andamento' => $q->where('concluido', false),
                    default => null,
                };
            })
            ->when($inicio && $fim, fn ($q) => $q->whereBetween($colunaPeriodo, [$inicio, $fim]))
            ->when($inicio && ! $fim, fn ($q) => $q->where($colunaPeriodo, '>=', $inicio))
            ->when(! $inicio && $fim, fn ($q) => $q->where($colunaPeriodo, '<=', $fim));
    }

    private function tentativasQuery(Request $request, User $viewer): Builder
    {
        [$inicio, $fim] = $this->periodo($request);

        $evento = match ($request->input('evento_avaliacao')) {
            'resets' => ['avaliacao_reset'],
            'senha' => ['avaliacao_senha_invalida'],
            'todas' => ['avaliacao_submetida', 'avaliacao_reset', 'avaliacao_senha_invalida'],
            default => ['avaliacao_submetida'],
        };

        return TrainingLog::query()
            ->whereIn('evento', $evento)
            ->whereHas('user', function ($q) use ($request, $viewer) {
                $q->auditEligible()->visibleToViewer($viewer);
                $this->aplicarFiltrosUsuario($q, $request);
            })
            ->when($request->filled('training_id'), fn ($q) => $q->where('training_id', $request->integer('training_id')))
            ->when($request->filled('training_tipo'), function ($q) use ($request) {
                $q->whereHas('training', fn ($t) => $t->where('tipo', $request->input('training_tipo')));
            })
            ->when($inicio && $fim, fn ($q) => $q->whereBetween('created_at', [$inicio, $fim]))
            ->when($inicio && ! $fim, fn ($q) => $q->where('created_at', '>=', $inicio))
            ->when(! $inicio && $fim, fn ($q) => $q->where('created_at', '<=', $fim));
    }

    /**
     * Período selecionado (início/fim), independente da coluna aplicada.
     *
     * @return array{0:?Carbon,1:?Carbon}
     */
    private function periodo(Request $request): array
    {
        try {
            $inicio = $request->filled('periodo_inicio') ? Carbon::parse($request->input('periodo_inicio'))->startOfDay() : null;
            $fim = $request->filled('periodo_fim') ? Carbon::parse($request->input('periodo_fim'))->endOfDay() : null;
        } catch (\Throwable) {
            return [null, null];
        }

        return [$inicio, $fim];
    }

    private function aplicarFiltrosUsuario(Builder $query, Request $request): void
    {
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

    // =========================================================================
    // Indicadores
    // =========================================================================

    /**
     * @return array<string, mixed>
     */
    private function resumo(Request $request, User $viewer): array
    {
        $base = $this->resultadosQuery($request, $viewer);
        $comNota = (clone $base)->whereNotNull('avaliacao_nota');
        $aprovados = (clone $base)->where('avaliacao_aprovada', true);

        $total = (clone $base)->count();
        $totalComNota = (clone $comNota)->count();
        $totalAprovados = (clone $aprovados)->count();
        $media = $totalComNota > 0 ? (float) (clone $comNota)->avg('avaliacao_nota') : null;
        $tempoTotal = (int) (clone $base)->sum('tempo_assistido');

        return [
            'total_resultados' => $total,
            'total_com_nota' => $totalComNota,
            'total_aprovados' => $totalAprovados,
            'total_reprovados' => (clone $base)->where('avaliacao_aprovada', false)->whereNotNull('avaliacao_nota')->count(),
            'total_sem_nota' => (clone $base)->whereNull('avaliacao_nota')->count(),
            'media_notas' => $media,
            'taxa_aprovacao' => $totalComNota > 0 ? ($totalAprovados / $totalComNota) * 100 : 0.0,
            'tempo_total_segundos' => $tempoTotal,
            'tentativas_registradas' => (clone $this->tentativasQuery($request, $viewer))->count(),
        ];
    }

    /**
     * @return Collection<int, array<string, mixed>>
     */
    private function desempenhoPorTreinamento(Request $request, User $viewer)
    {
        $base = $this->resultadosQuery($request, $viewer);

        $totais = (clone $base)
            ->selectRaw('training_id, COUNT(*) as total')
            ->groupBy('training_id')
            ->pluck('total', 'training_id');

        $notas = (clone $base)
            ->whereNotNull('avaliacao_nota')
            ->selectRaw('training_id, COUNT(*) as com_nota, AVG(avaliacao_nota) as media')
            ->groupBy('training_id')
            ->get()
            ->keyBy('training_id');

        $aprovados = (clone $base)
            ->where('avaliacao_aprovada', true)
            ->selectRaw('training_id, COUNT(*) as aprovados')
            ->groupBy('training_id')
            ->pluck('aprovados', 'training_id');

        $trainingIds = $totais->keys()->merge($notas->keys())->unique()->filter()->values();

        if ($trainingIds->isEmpty()) {
            return collect();
        }

        $treinamentos = Training::whereIn('id', $trainingIds)->get()->keyBy('id');

        return $trainingIds
            ->map(function ($trainingId) use ($totais, $notas, $aprovados, $treinamentos) {
                $training = $treinamentos->get($trainingId);
                if (! $training) {
                    return null;
                }

                $comNota = (int) ($notas->get($trainingId)->com_nota ?? 0);
                $qtdAprovados = (int) ($aprovados->get($trainingId) ?? 0);

                return [
                    'training' => $training,
                    'total' => (int) ($totais->get($trainingId) ?? 0),
                    'com_nota' => $comNota,
                    'aprovados' => $qtdAprovados,
                    'media' => $comNota > 0 ? round((float) $notas->get($trainingId)->media, 1) : null,
                    'taxa_aprovacao' => $comNota > 0 ? ($qtdAprovados / $comNota) * 100 : 0.0,
                ];
            })
            ->filter()
            ->sortByDesc('com_nota')
            ->values();
    }

    /**
     * Usuários fora da base de cálculo: super_admin e marcados como teste.
     *
     * @return array{super_admin:int, teste:int, total:int}
     */
    private function metricasExclusao(): array
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
     * @return array<int, string>
     */
    private function eventosDisponiveis(): array
    {
        return [
            'submetidas' => 'Avaliações submetidas',
            'resets' => 'Resets por reprovação',
            'senha' => 'Tentativas com senha inválida',
            'todas' => 'Todos os eventos de avaliação',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function filtrosAtivos(Request $request): array
    {
        $labels = [];

        if ($request->filled('periodo_inicio') || $request->filled('periodo_fim')) {
            $base = $request->input('base_data', 'conclusao') === 'inicio' ? 'início' : 'conclusão';
            $labels['Período ('.$base.')'] = ($request->input('periodo_inicio') ? Carbon::parse($request->input('periodo_inicio'))->format('d/m/Y') : '...')
                .' a '.($request->input('periodo_fim') ? Carbon::parse($request->input('periodo_fim'))->format('d/m/Y') : '...');
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

        if ($request->filled('training_id')) {
            $labels['Treinamento'] = optional(Training::find($request->integer('training_id')))->titulo ?? ('#'.$request->input('training_id'));
        }

        if ($request->filled('training_tipo')) {
            $labels['Tipo de conteúdo'] = strtoupper($request->input('training_tipo'));
        }

        if ($request->filled('resultado')) {
            $labels['Resultado'] = ucfirst(str_replace('_', ' ', $request->input('resultado')));
        }

        if ($request->filled('nota_min') || $request->filled('nota_max')) {
            $labels['Nota'] = ($request->input('nota_min') !== null && $request->input('nota_min') !== '' ? $request->input('nota_min') : '0')
                .'% a '.($request->input('nota_max') !== null && $request->input('nota_max') !== '' ? $request->input('nota_max') : '100').'%';
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

        if ($request->filled('somente_com_prova')) {
            $labels['Somente com prova'] = 'Sim';
        }

        return $labels;
    }

    // =========================================================================
    // Exportações
    // =========================================================================

    private function csvResultados(Request $request, User $viewer)
    {
        $query = $this->resultadosQuery($request, $viewer)
            ->with(['user', 'training'])
            ->orderBy('user_id')
            ->orderBy('training_id');

        $linhas = $query->limit(self::LIMITE_CSV + 1)->get();
        $truncado = $linhas->count() > self::LIMITE_CSV;
        $linhas = $linhas->take(self::LIMITE_CSV);
        $this->anexarCertificados($linhas);

        $hash = $this->hashDosRegistros($linhas);

        $cabecalho = [
            'Usuario', 'CPF', 'Tipo', 'Status', 'Setor', 'Cargo', 'Empresa',
            'Treinamento', 'Tipo conteudo', 'Tipo treinamento', 'Nota (%)', 'Resultado',
            'Tentativas', 'Inicio', 'Conclusao', 'Tempo assistido', '% assistida',
            'Certificado', 'Emissao certificado', 'Validade certificado', 'Status validade',
        ];

        return response()->streamDownload(function () use ($request, $viewer, $linhas, $cabecalho, $hash, $truncado) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach ($this->linhasMetadados('Relatório de Avaliações e Notas', $request, $viewer, $hash) as $linha) {
                fwrite($out, $linha."\n");
            }

            if ($truncado) {
                fwrite($out, '# AVISO: exportação limitada a '.self::LIMITE_CSV." linhas. Refine os filtros para o conjunto completo.\n");
            }

            fputcsv($out, $cabecalho, ';');

            foreach ($linhas as $progresso) {
                $certificado = $progresso->certificado_anexado ?? null;

                fputcsv($out, [
                    optional($progresso->user)->nome ?? '—',
                    optional($progresso->user)?->getCpfFormatted() ?? '—',
                    User::tipoUsuarioLabel(optional($progresso->user)->tipo_usuario),
                    ucfirst((string) optional($progresso->user)->status),
                    optional($progresso->user)->setor ?? '',
                    optional($progresso->user)->cargo ?? '',
                    optional($progresso->user)->empresa ?? '',
                    optional($progresso->training)->titulo ?? '—',
                    optional($progresso->training)->tipo ?? '',
                    optional($progresso->training)->tipo_treinamento ?? '',
                    $progresso->avaliacao_nota !== null ? $progresso->avaliacao_nota : '',
                    $this->resultadoLabel($progresso),
                    $progresso->avaliacao_tentativas ?? 0,
                    optional($progresso->data_inicio)->format('d/m/Y H:i'),
                    optional($progresso->data_conclusao)->format('d/m/Y H:i'),
                    gmdate('H:i:s', (int) ($progresso->tempo_assistido ?? 0)),
                    (int) ($progresso->porcentagem_assistida ?? 0).'%',
                    $certificado?->codigo_certificado ?? '',
                    optional($certificado?->data_emissao)->format('d/m/Y H:i'),
                    optional($certificado?->data_validade)->format('d/m/Y'),
                    $certificado ? $this->validadeLabel($certificado) : '',
                ], ';');
            }

            fclose($out);
        }, 'relatorio_avaliacoes_'.now()->format('Y-m-d_H-i').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function csvTentativas(Request $request, User $viewer)
    {
        $tentativas = $this->tentativasQuery($request, $viewer)
            ->with(['user', 'training'])
            ->orderBy('created_at')
            ->limit(self::LIMITE_CSV + 1)
            ->get();
        $truncado = $tentativas->count() > self::LIMITE_CSV;
        $tentativas = $tentativas->take(self::LIMITE_CSV);

        $hash = hash('sha256', $tentativas->map(
            fn ($log) => implode('|', [$log->id, $log->user_id, $log->training_id, $log->evento, optional($log->created_at)->toIso8601String()])
        )->implode("\n"));

        return response()->streamDownload(function () use ($request, $viewer, $tentativas, $hash, $truncado) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");

            foreach ($this->linhasMetadados('Histórico de tentativas de avaliação', $request, $viewer, $hash) as $linha) {
                fwrite($out, $linha."\n");
            }

            if ($truncado) {
                fwrite($out, '# AVISO: exportação limitada a '.self::LIMITE_CSV." linhas.\n");
            }

            fputcsv($out, ['Data/Hora', 'Usuario', 'CPF', 'Treinamento', 'Evento', 'Detalhe', 'IP'], ';');

            foreach ($tentativas as $log) {
                fputcsv($out, [
                    optional($log->created_at)->format('d/m/Y H:i:s'),
                    optional($log->user)->nome ?? '—',
                    optional($log->user)?->getCpfFormatted() ?? '—',
                    optional($log->training)->titulo ?? '—',
                    $log->evento,
                    $log->detalhe,
                    $log->ip,
                ], ';');
            }

            fclose($out);
        }, 'relatorio_avaliacoes_tentativas_'.now()->format('Y-m-d_H-i').'.csv', [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    /**
     * @return array<int, string>
     */
    private function linhasMetadados(string $titulo, Request $request, User $viewer, string $hash): array
    {
        $filtros = $this->filtrosAtivos($request);

        $linhas = [
            '# '.plataforma_nome().' — '.$titulo,
            '# Gerado em: '.now()->format('d/m/Y H:i:s').' por '.$viewer->nome,
            '# Base: exclui super_admin e usuários marcados como teste.',
            '# Filtros: '.($filtros === [] ? 'nenhum' : json_encode($filtros, JSON_UNESCAPED_UNICODE)),
            '# SHA-256 do recorte: '.$hash,
        ];

        return $linhas;
    }

    // =========================================================================
    // Auxiliares
    // =========================================================================

    private function anexarCertificados($linhas): void
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

        foreach ($linhas as $linha) {
            $linha->certificado_anexado = $certificados->get($linha->user_id.'-'.$linha->training_id);
        }
    }

    private function resultadoLabel(UserProgress $progresso): string
    {
        if ($progresso->avaliacao_aprovada) {
            return 'Aprovado';
        }

        if ($progresso->avaliacao_nota !== null) {
            return 'Reprovado';
        }

        return 'Pendente';
    }

    private function validadeLabel(Certificate $certificado): string
    {
        return match ($certificado->status_validade) {
            'vencido' => 'Vencido',
            'vencendo' => 'Vencendo (30 dias)',
            'valido' => 'Válido',
            default => 'Sem validade',
        };
    }

    private function hashRecorte(Request $request, User $viewer): string
    {
        $linhas = $this->resultadosQuery($request, $viewer)
            ->orderBy('user_id')
            ->orderBy('training_id')
            ->limit(self::LIMITE_CSV)
            ->get([
                'user_id', 'training_id', 'avaliacao_nota', 'avaliacao_aprovada',
                'concluido', 'data_inicio', 'data_conclusao', 'tempo_assistido',
            ]);

        return $this->hashDosRegistros($linhas);
    }

    private function hashDosRegistros($linhas): string
    {
        try {
            return hash('sha256', $linhas->map(function ($linha) {
                return implode('|', [
                    $linha->user_id,
                    $linha->training_id,
                    $linha->avaliacao_nota ?? '',
                    (int) (bool) $linha->avaliacao_aprovada,
                    (int) (bool) $linha->concluido,
                    optional($linha->data_inicio)->toIso8601String(),
                    optional($linha->data_conclusao)->toIso8601String(),
                    (int) ($linha->tempo_assistido ?? 0),
                ]);
            })->implode("\n"));
        } catch (\Throwable $e) {
            Log::warning('Falha ao calcular hash do relatório de avaliações: '.$e->getMessage());

            return 'indisponível';
        }
    }
}
