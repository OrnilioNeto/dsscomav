<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Training;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\RelatorioAuditoriaService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use TCPDF;

/**
 * Relatório de auditoria gerencial de alta conformidade.
 *
 * Base: exclui SOMENTE super_admin e usuários marcados como teste — a
 * exclusão é declarada no topo do relatório e nos arquivos exportados.
 */
class RelatorioAuditoriaController extends Controller
{
    public function index(Request $request)
    {
        $service = $this->service($request);

        $conteudoSelecionado = $request->filled('training_id')
            ? Training::find($request->integer('training_id'))
            : null;

        return view('relatorios.auditoria', [
            'resumo' => $service->resumo(),
            'metricasExclusao' => $service->metricasExclusao(),
            'conteudos' => $service->conteudosPaginados(),
            'usuarios' => $service->usuariosPaginados(),
            'conteudoSelecionado' => $conteudoSelecionado,
            'usuariosDoConteudo' => $conteudoSelecionado ? $service->usuariosDoConteudo($conteudoSelecionado) : null,
            'ausencias' => $service->ausencias(),
            'evolucao' => $service->evolucao(),
            'trilha' => $service->trilha(),
            'podeVerTrilha' => $service->podeVerTrilha(),
            'userTypes' => User::query()->auditEligible()->visibleToViewer($request->user())
                ->select('tipo_usuario')->distinct()->orderBy('tipo_usuario')->pluck('tipo_usuario'),
            'users' => User::query()->auditEligible()->visibleToViewer($request->user())->orderBy('nome')->get(['id', 'nome']),
            'treinamentos' => Training::orderByReleaseDate('desc')->get(['id', 'titulo']),
            'setores' => User::query()->auditEligible()->visibleToViewer($request->user())
                ->whereNotNull('setor')->where('setor', '<>', '')->distinct()->orderBy('setor')->pluck('setor'),
            'empresas' => User::query()->auditEligible()->visibleToViewer($request->user())
                ->whereNotNull('empresa')->where('empresa', '<>', '')->distinct()->orderBy('empresa')->pluck('empresa'),
        ]);
    }

    public function csv(Request $request)
    {
        $service = $this->service($request);

        app(AuditLogger::class)->log('exported', [
            'module' => 'certificates',
            'description' => 'Exportou o relatório de auditoria gerencial em CSV',
        ]);

        $tipo = $request->input('tipo_exportacao', 'detalhado');
        $hash = $service->hashRecorte();

        $conteudo = $tipo === 'ausencias'
            ? $this->csvAusencias($request, $service)
            : $this->csvDetalhado($request, $service, $hash);

        $filename = $tipo === 'ausencias'
            ? 'auditoria_ausencias_'.now()->format('Y-m-d_H-i').'.csv'
            : 'auditoria_detalhado_'.now()->format('Y-m-d_H-i').'.csv';

        return Response::make($conteudo, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }

    public function pdf(Request $request)
    {
        $service = $this->service($request);

        app(AuditLogger::class)->log('exported', [
            'module' => 'certificates',
            'description' => 'Exportou o relatório de auditoria gerencial em PDF',
        ]);

        $html = view('relatorios.auditoria_pdf', [
            'resumo' => $service->resumo(),
            'metricasExclusao' => $service->metricasExclusao(),
            'conteudos' => $service->treinamentosQuery()->get()->map(fn (Training $t) => [
                'training' => $t,
                'stats' => $service->statsConteudo($t),
            ])->sortByDesc(fn ($linha) => $linha['stats']['elegiveis'])->values(),
            'ausencias' => $service->ausencias(),
            'trilha' => $service->trilha(),
            'filtrosAtivos' => $service->filtrosAtivos(),
            'geradoEm' => now(),
            'geradoPor' => $request->user()->nome,
            'hashRecorte' => $service->hashRecorte(),
        ])->render();

        $pdf = new TCPDF('L', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(plataforma_nome());
        $pdf->SetTitle('Relatório de Auditoria Gerencial');
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 12);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'Relatorio_Auditoria_'.now()->format('Y-m-d_H-i').'.pdf';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
        ]);
    }

    // =========================================================================

    private function service(Request $request): RelatorioAuditoriaService
    {
        return new RelatorioAuditoriaService($request, $request->user());
    }

    private function csvDetalhado(Request $request, RelatorioAuditoriaService $service, string $hash): string
    {
        $linhas = $service->linhasDetalhado();

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");

        foreach ($this->metadados('Relatório de Auditoria Gerencial — Detalhado', $request, $service, $hash) as $linha) {
            fwrite($out, $linha."\n");
        }

        fputcsv($out, [
            'Usuario', 'CPF', 'Tipo', 'Status', 'Setor', 'Cargo', 'Empresa',
            'Treinamento', 'Tipo conteudo', 'Tipo treinamento', 'Obrigatorio',
            'Liberacao', 'Prazo', 'Inicio', 'Conclusao', 'Situacao',
            'Tempo assistido', '% assistida', 'Nota', 'Aprovado', 'Tentativas',
            'Prorrogado ate', 'Motivo prorrogacao', 'Afastamento no periodo', 'Isento',
            'Certificado', 'Emissao certificado', 'Validade certificado', 'Status validade',
        ], ';');

        foreach ($linhas as $linha) {
            $certificado = $linha->certificado ?? null;
            $prazo = $linha->prazo_efetivo ?? $linha->prazo_estendido_ate ?? optional($linha->training)->prazoBaseConclusao();

            fputcsv($out, [
                optional($linha->user)->nome ?? '—',
                optional($linha->user)?->getCpfFormatted() ?? '—',
                User::tipoUsuarioLabel(optional($linha->user)->tipo_usuario),
                ucfirst((string) optional($linha->user)->status),
                optional($linha->user)->setor ?? '',
                optional($linha->user)->cargo ?? '',
                optional($linha->user)->empresa ?? '',
                optional($linha->training)->titulo ?? '—',
                optional($linha->training)->tipo ?? '',
                optional($linha->training)->tipo_treinamento ?? '',
                optional($linha->training)->obrigatorio ? 'Sim' : 'Não',
                optional(optional($linha->training)->releaseDate())->format('d/m/Y'),
                optional($prazo)->format('d/m/Y H:i'),
                optional($linha->data_inicio)->format('d/m/Y H:i'),
                optional($linha->data_conclusao)->format('d/m/Y H:i'),
                $linha->situacao_detalhada ?? '',
                gmdate('H:i:s', (int) ($linha->tempo_assistido ?? 0)),
                (int) ($linha->porcentagem_assistida ?? 0).'%',
                $linha->avaliacao_nota ?? '',
                $linha->avaliacao_aprovada ? 'Sim' : ($linha->avaliacao_nota !== null ? 'Não' : ''),
                (int) ($linha->avaliacao_tentativas ?? 0),
                optional($linha->prazo_estendido_ate)->format('d/m/Y H:i'),
                $linha->prazo_estendido_motivo ?? '',
                $linha->afastamento_periodo ?? '',
                $linha->isencao ?? '',
                $certificado?->codigo_certificado ?? '',
                optional($certificado?->data_emissao)->format('d/m/Y H:i'),
                optional($certificado?->data_validade)->format('d/m/Y'),
                $certificado ? match ($certificado->status_validade) {
                    'valido' => 'Válido',
                    'vencendo' => 'Vencendo',
                    'vencido' => 'Vencido',
                    default => 'Sem validade',
                } : '',
            ], ';');
        }

        rewind($out);
        $conteudo = stream_get_contents($out);
        fclose($out);

        return $conteudo;
    }

    private function csvAusencias(Request $request, RelatorioAuditoriaService $service): string
    {
        $linhas = $service->linhasAusencias();

        $out = fopen('php://temp', 'r+');
        fwrite($out, "\xEF\xBB\xBF");

        foreach ($this->metadados('Relatório de Auditoria — Ausências e Justificativas', $request, $service, hash('sha256', json_encode($linhas->all()))) as $linha) {
            fwrite($out, $linha."\n");
        }

        fputcsv($out, ['Usuario', 'CPF', 'Tipo', 'Inicio', 'Fim', 'Dias', 'Motivo', 'Autorizado por', 'Observacao'], ';');

        foreach ($linhas as $linha) {
            fputcsv($out, [
                $linha['nome'], $linha['cpf'], $linha['tipo'], $linha['inicio'],
                $linha['fim'], $linha['dias'], $linha['motivo'], $linha['autorizado_por'], $linha['observacao'],
            ], ';');
        }

        rewind($out);
        $conteudo = stream_get_contents($out);
        fclose($out);

        return $conteudo;
    }

    /**
     * @return array<int, string>
     */
    private function metadados(string $titulo, Request $request, RelatorioAuditoriaService $service, string $hash): array
    {
        $filtros = $service->filtrosAtivos();

        return [
            '# '.plataforma_nome().' — '.$titulo,
            '# Gerado em: '.now()->format('d/m/Y H:i:s').' por '.$request->user()->nome,
            '# Base: exclui somente super_admin e usuários marcados como teste.',
            '# Filtros: '.($filtros === [] ? 'nenhum' : json_encode($filtros, JSON_UNESCAPED_UNICODE)),
            '# SHA-256 do recorte: '.$hash,
        ];
    }
}
