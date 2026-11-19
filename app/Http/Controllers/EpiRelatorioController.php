<?php

namespace App\Http\Controllers;

use App\Models\Epi;
use App\Models\EpiDevolucao;
use App\Models\EpiEntrega;
use App\Models\EpiEstoque;
use App\Models\EpiFiltro;
use App\Services\AuditLogger;
use App\Services\EpiFardamentoService;
use App\Support\PdfTableWidths;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use TCPDF;

/**
 * Relatórios em PDF das abas com grids de controle do módulo de EPI.
 *
 * Cada relatório respeita os mesmos filtros aplicados na tela (quando a aba
 * permite filtro no servidor) e é gerado sob demanda com TCPDF, sem persistir
 * arquivos — mesmo padrão dos demais relatórios do sistema.
 */
class EpiRelatorioController extends Controller
{
    /**
     * Relatórios disponíveis: título, view e orientação da página.
     */
    private const RELATORIOS = [
        'catalogo' => ['titulo' => 'Catálogo de EPIs', 'view' => 'epi.pdf.catalogo', 'paisagem' => true],
        'estoque' => ['titulo' => 'Controle de Estoque - Saldos', 'view' => 'epi.pdf.estoque', 'paisagem' => true],
        'movimentacoes' => ['titulo' => 'Movimentações de Estoque', 'view' => 'epi.pdf.movimentacoes', 'paisagem' => true],
        'fardamento' => ['titulo' => 'Gestão de Fardamento', 'view' => 'epi.pdf.fardamento', 'paisagem' => true],
        'entregas' => ['titulo' => 'Histórico de Entregas Ativas', 'view' => 'epi.pdf.entregas', 'paisagem' => true],
        'devolucoes' => ['titulo' => 'Devoluções e Inspeções', 'view' => 'epi.pdf.devolucoes', 'paisagem' => true],
        'filtros' => ['titulo' => 'Filtros de Máscara', 'view' => 'epi.pdf.filtros', 'paisagem' => true],
        'filiais' => ['titulo' => 'Filiais Cadastradas', 'view' => 'epi.pdf.filiais', 'paisagem' => false],
    ];

    public function pdf(Request $request, string $relatorio)
    {
        $config = self::RELATORIOS[$relatorio] ?? null;
        abort_if($config === null, 404);

        $dados = match ($relatorio) {
            'catalogo' => $this->catalogo($request),
            'estoque' => $this->estoque($request),
            'movimentacoes' => $this->movimentacoes($request),
            'fardamento' => $this->fardamento($request),
            'entregas' => $this->entregas(),
            'devolucoes' => $this->devolucoes(),
            'filtros' => $this->filtros(),
            'filiais' => $this->filiais(),
        };

        $html = view($config['view'], [
            'titulo' => $config['titulo'],
            'dados' => $dados,
            'filtrosAtivos' => $this->filtrosAtivos($relatorio, $request),
            'geradoEm' => now(),
            'geradoPor' => $request->user()?->nome ?? 'Sistema',
        ])->render();

        // TCPDF ignora as larguras dos <th> ao montar o <tbody>: replica na
        // primeira linha do corpo para manter colunas e dados alinhados.
        $html = PdfTableWidths::align($html);

        app(AuditLogger::class)->log('exported', [
            'module' => 'epi',
            'description' => 'Exportou o relatório "'.$config['titulo'].'" em PDF',
        ]);

        $pdf = new TCPDF($config['paisagem'] ? 'L' : 'P', 'mm', 'A4', true, 'UTF-8', false);
        $pdf->SetCreator(plataforma_nome());
        $pdf->SetTitle($config['titulo'].' - EPI');
        $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);
        $pdf->SetMargins(8, 8, 8);
        $pdf->SetAutoPageBreak(true, 10);
        $pdf->AddPage();
        $pdf->writeHTML($html, true, false, true, false, '');

        $filename = 'EPI_'.Str::slug($config['titulo'], '_').'_'.now()->format('Y-m-d_H-i').'.pdf';

        return response($pdf->Output($filename, 'S'), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="'.$filename.'"',
            // Relatório é sempre gerado na hora: evita o navegador reutilizar um PDF antigo
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma' => 'no-cache',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function catalogo(Request $request): array
    {
        $busca = trim((string) $request->input('busca_catalogo', ''));
        $grupo = trim((string) $request->input('grupo_catalogo', ''));
        $status = trim((string) $request->input('status_catalogo', ''));

        $query = Epi::query();

        if ($busca !== '') {
            $query->where(function ($q) use ($busca) {
                $q->where('ss_e_tx_item', 'LIKE', "%{$busca}%")
                    ->orWhere('ss_e_tx_grupo', 'LIKE', "%{$busca}%")
                    ->orWhere('ss_e_tx_subgrupo', 'LIKE', "%{$busca}%")
                    ->orWhere('ss_e_tx_ca', 'LIKE', "%{$busca}%")
                    ->orWhere('ss_e_tx_fabricante', 'LIKE', "%{$busca}%");
            });
        }

        if ($grupo !== '') {
            $query->where('ss_e_tx_grupo', $grupo);
        }

        if ($status !== '') {
            $query->where('ss_e_tx_status', $status);
        }

        $epis = $query
            ->with(['variacoes' => fn ($q) => $q->where('ss_ev_tx_status', 'ativo')->orderBy('ss_ev_tx_nome')])
            ->orderBy('ss_e_tx_grupo')
            ->orderBy('ss_e_tx_item')
            ->get();

        Epi::preloadSaldosFor($epis);

        $saldos = [];
        $totalRede = 0;

        foreach ($epis as $epi) {
            $saldoEpi = $epi->getSaldoTotalRede();
            $totalRede += $saldoEpi;

            foreach ($epi->variacoes as $variacao) {
                $saldos[$epi->ss_e_nb_id][$variacao->ss_ev_nb_id] = $epi->getSaldoTotalRede($variacao->ss_ev_nb_id);
            }
        }

        return [
            'epis' => $epis,
            'saldos' => $saldos,
            'total_rede' => $totalRede,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function estoque(Request $request): array
    {
        $filialId = (int) $request->input('filial_id', 0);
        $limiar = max(0, (int) $request->input('limiar', 5));
        $statusFiltro = (string) $request->input('status', 'todos');
        $busca = trim((string) $request->input('busca', ''));
        $ordem = (string) $request->input('ordem', 'nome');

        $epis = Epi::where('ss_e_tx_status', 'ativo')
            ->with(['variacoes' => fn ($q) => $q->where('ss_ev_tx_status', 'ativo')->orderBy('ss_ev_tx_nome')])
            ->orderBy('ss_e_tx_grupo')
            ->orderBy('ss_e_tx_item')
            ->get();

        Epi::preloadSaldosFor($epis);

        $itens = [];
        $totalRede = 0;
        $totalFilial = 0;
        $comSaldo = 0;
        $esgotados = 0;
        $baixos = 0;

        foreach ($epis as $epi) {
            $saldoFilial = $epi->getSaldoPorFilial($filialId);
            $saldoRede = $epi->getSaldoTotalRede();

            $totalRede += $saldoRede;
            $totalFilial += $saldoFilial;

            $situacao = $saldoRede <= 0 ? 'esgotado' : ($saldoRede <= $limiar ? 'baixo' : 'ok');

            if ($situacao === 'esgotado') {
                $esgotados++;
            } else {
                $comSaldo++;

                if ($situacao === 'baixo') {
                    $baixos++;
                }
            }

            $variacoes = [];
            foreach ($epi->variacoes as $variacao) {
                $variacoes[] = [
                    'nome' => $variacao->ss_ev_tx_nome,
                    'saldo' => $epi->getSaldoTotalRede($variacao->ss_ev_nb_id),
                ];
            }

            if ($busca !== '') {
                $alvo = mb_strtolower(implode(' ', [
                    (string) $epi->ss_e_tx_item,
                    (string) $epi->ss_e_tx_grupo,
                    (string) $epi->ss_e_tx_subgrupo,
                    (string) $epi->ss_e_tx_ca,
                ]));

                if (! str_contains($alvo, mb_strtolower($busca))) {
                    continue;
                }
            }

            if ($statusFiltro !== '' && $statusFiltro !== 'todos' && $situacao !== $statusFiltro) {
                continue;
            }

            $itens[] = [
                'grupo' => $epi->ss_e_tx_grupo,
                'subgrupo' => $epi->ss_e_tx_subgrupo,
                'item' => $epi->ss_e_tx_item,
                'ca' => $epi->ss_e_tx_ca,
                'variacoes' => $variacoes,
                'saldo_filial' => $saldoFilial,
                'saldo_rede' => $saldoRede,
                'situacao' => $situacao,
            ];
        }

        usort($itens, function (array $a, array $b) use ($ordem) {
            if ($ordem === 'saldo-asc') {
                return $a['saldo_rede'] <=> $b['saldo_rede'];
            }

            if ($ordem === 'saldo-desc') {
                return $b['saldo_rede'] <=> $a['saldo_rede'];
            }

            return strnatcasecmp((string) $a['item'], (string) $b['item']);
        });

        $filiais = $this->filiaisList();

        return [
            'itens' => $itens,
            'filial' => $filiais[$filialId] ?? 'Matriz / Sede Principal',
            'limiar' => $limiar,
            'total_rede' => $totalRede,
            'total_filial' => $totalFilial,
            'com_saldo' => $comSaldo,
            'baixos' => $baixos,
            'esgotados' => $esgotados,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function movimentacoes(Request $request): array
    {
        $tipo = (string) $request->input('tipo', 'todos');
        $periodo = max(0, (int) $request->input('periodo', 0));
        $busca = trim((string) $request->input('busca', ''));
        $limite = 1000;

        $movimentos = EpiEstoque::with(['epi', 'variacao'])
            ->when($tipo !== '' && $tipo !== 'todos', fn ($q) => $q->where('ss_e_tx_tipo', $tipo))
            ->when($periodo > 0, fn ($q) => $q->where('ss_e_tx_data', '>=', now()->subDays($periodo)->startOfDay()))
            ->when($busca !== '', function ($q) use ($busca) {
                $like = "%{$busca}%";

                $q->where(function ($sub) use ($like) {
                    $sub->where('ss_e_tx_motivo', 'LIKE', $like)
                        ->orWhere('ss_e_tx_chave_nf', 'LIKE', $like)
                        ->orWhere('ss_e_tx_fornecedor', 'LIKE', $like)
                        ->orWhereHas('epi', function ($epi) use ($like) {
                            $epi->where('ss_e_tx_item', 'LIKE', $like)
                                ->orWhere('ss_e_tx_grupo', 'LIKE', $like);
                        })
                        ->orWhereHas('variacao', fn ($v) => $v->where('ss_ev_tx_nome', 'LIKE', $like));
                });
            })
            ->orderByDesc('ss_e_tx_data')
            ->orderByDesc('ss_e_nb_id')
            ->limit($limite)
            ->get();

        return [
            'movimentos' => $movimentos,
            'filiais' => $this->filiaisList(),
            'total_entradas' => (int) $movimentos->whereIn('ss_e_tx_tipo', ['entrada', 'devolucao'])->sum('ss_e_nb_quantidade'),
            'total_saidas' => (int) $movimentos->whereIn('ss_e_tx_tipo', ['saida', 'substituicao'])->sum('ss_e_nb_quantidade'),
            'limite' => $limite,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function fardamento(Request $request): array
    {
        $filialId = (int) $request->input('filial_id', 0);

        $servico = app(EpiFardamentoService::class);
        $distribuicao = $servico->distribuicao();
        $resumo = $servico->estoque($filialId, $distribuicao);

        $filiais = $this->filiaisList();

        return [
            'distribuicao' => $distribuicao,
            'categorias' => ['camisa' => 'Camisa', 'calca' => 'Calça', 'bota' => 'Bota'],
            'linhas' => $resumo['linhas'],
            'total' => $resumo['total'],
            'saldo_por_tipo' => $resumo['saldo_por_tipo'],
            'demanda' => $resumo['demanda'],
            'deficit' => $resumo['deficit'],
            'filial' => $filiais[$filialId] ?? 'Matriz / Sede Principal',
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function entregas(): array
    {
        $limite = 500;

        $entregas = EpiEntrega::with(['colaborador', 'epi', 'variacao'])
            ->whereNotIn('ss_e_tx_status', ['inativo', 'devolvido'])
            ->orderByDesc('ss_e_tx_dataCadastro')
            ->orderByDesc('ss_e_nb_id')
            ->limit($limite)
            ->get();

        return [
            'entregas' => $entregas,
            'limite' => $limite,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function devolucoes(): array
    {
        $pendentes = EpiDevolucao::with(['colaborador', 'epi', 'variacao', 'usuarioRegistro'])
            ->where('ss_ed_tx_status', 'pendente')
            ->orderBy('ss_ed_tx_data_registro')
            ->get();

        $historico = EpiDevolucao::with(['colaborador', 'epi', 'variacao', 'usuarioRegistro', 'usuarioDecisao'])
            ->orderByDesc('ss_ed_tx_data_registro')
            ->limit(300)
            ->get();

        return [
            'pendentes' => $pendentes,
            'historico' => $historico,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filtros(): array
    {
        $ativos = EpiFiltro::with(['colaborador', 'tipo', 'epi', 'entrega'])
            ->where('ss_ef_tx_status', 'ativo')
            ->orderBy('ss_ef_tx_vencimento')
            ->get();

        $descartados = EpiFiltro::with(['colaborador', 'tipo', 'epi'])
            ->where('ss_ef_tx_status', 'descartado')
            ->orderByDesc('ss_ef_tx_data_descarte')
            ->limit(300)
            ->get();

        return [
            'ativos' => $ativos,
            'descartados' => $descartados,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function filiais(): array
    {
        $cadastradas = DB::table('ss_filial')
            ->whereTenant('ss_filial')
            ->orderBy('ss_f_nb_id')
            ->get();

        return [
            'cadastradas' => $cadastradas,
        ];
    }

    /**
     * Filiais disponíveis para seleção (0 = Matriz).
     *
     * @return array<int, string>
     */
    private function filiaisList(): array
    {
        $filiais = [0 => 'Matriz / Sede Principal'];

        if (Schema::hasTable('ss_filial')) {
            $registros = DB::table('ss_filial')
                ->whereTenant('ss_filial')
                ->where('ss_f_tx_status', 'ativo')
                ->orderBy('ss_f_tx_nome')
                ->get();

            foreach ($registros as $filial) {
                $filiais[$filial->ss_f_nb_id] = $filial->ss_f_tx_nome.($filial->ss_f_tx_cidade ? " ({$filial->ss_f_tx_cidade})" : '');
            }
        }

        return $filiais;
    }

    /**
     * Filtros aplicados no recorte, exibidos no cabeçalho do PDF.
     *
     * @return array<string, string>
     */
    private function filtrosAtivos(string $relatorio, Request $request): array
    {
        $filtros = match ($relatorio) {
            'catalogo' => [
                'Busca' => (string) $request->input('busca_catalogo', ''),
                'Grupo' => (string) $request->input('grupo_catalogo', ''),
                'Status' => (string) $request->input('status_catalogo', ''),
            ],
            'estoque' => [
                'Filial' => $this->filiaisList()[(int) $request->input('filial_id', 0)] ?? '',
                'Busca' => (string) $request->input('busca', ''),
                'Situação' => [
                    'ok' => 'Com saldo',
                    'baixo' => 'Baixo estoque',
                    'esgotado' => 'Esgotados',
                ][(string) $request->input('status', 'todos')] ?? '',
                'Limiar de baixo estoque' => $request->filled('status') ? 'até '.(int) $request->input('limiar', 5) : '',
            ],
            'movimentacoes' => [
                'Tipo' => [
                    'entrada' => 'Entradas',
                    'saida' => 'Saídas',
                    'devolucao' => 'Devoluções',
                    'substituicao' => 'Substituições',
                ][(string) $request->input('tipo', 'todos')] ?? '',
                'Período' => (int) $request->input('periodo', 0) > 0
                    ? 'Últimos '.(int) $request->input('periodo').' dias'
                    : '',
                'Busca' => (string) $request->input('busca', ''),
            ],
            'fardamento' => [
                'Filial' => $this->filiaisList()[(int) $request->input('filial_id', 0)] ?? '',
            ],
            default => [],
        };

        return array_filter($filtros, fn ($valor) => filled($valor));
    }
}
