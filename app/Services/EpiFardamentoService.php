<?php

namespace App\Services;

use App\Models\Epi;
use App\Models\User;

/**
 * Consolida as informações de fardamento (uniformes) do módulo de EPI:
 * distribuição de tamanhos dos colaboradores e saldo em banco por item/variação,
 * calculado a partir dos lançamentos do Controle de Estoque.
 *
 * Usado tanto pela tela do módulo (aba Fardamento) quanto pelos relatórios em PDF.
 */
class EpiFardamentoService
{
    /**
     * Distribuição de tamanhos (camisa, calça e bota) dos colaboradores ativos.
     *
     * @return array<string, mixed>
     */
    public function distribuicao(): array
    {
        $funcionarios = User::where('status', 'ativo')
            ->where('usuario_teste', false)
            ->where(function ($q) {
                $q->whereNull('role_id')
                    ->orWhereHas('role', function ($r) {
                        $r->where('nome', '<>', 'super_admin');
                    });
            })
            ->get(['id', 'nome', 'cargo', 'setor', 'empresa', 'camisa_tamanho', 'calca_tamanho', 'bota_numero']);

        $categorias = [
            'camisa' => 'camisa_tamanho',
            'calca' => 'calca_tamanho',
            'bota' => 'bota_numero',
        ];

        $dados = [];
        foreach ($categorias as $chave => $coluna) {
            $grupos = [];
            $totalComMedida = 0;

            foreach ($funcionarios as $f) {
                $tamanho = trim((string) ($f->{$coluna} ?? ''));
                if ($tamanho === '') {
                    continue;
                }
                $totalComMedida++;
                if (! isset($grupos[$tamanho])) {
                    $grupos[$tamanho] = ['qtd' => 0, 'funcionarios' => []];
                }
                $grupos[$tamanho]['qtd']++;
                $grupos[$tamanho]['funcionarios'][] = [
                    'nome' => $f->nome,
                    'cargo' => $f->cargo,
                    'setor' => $f->setor,
                    'empresa' => $f->empresa,
                ];
            }

            // Ordenação numérica sensível (36, 38, 40... e PP, P, M, G...)
            uksort($grupos, function ($a, $b) {
                $numericA = preg_replace('/\D/', '', (string) $a);
                $numericB = preg_replace('/\D/', '', (string) $b);
                if ($numericA !== '' && $numericB !== '' && $numericA !== $numericB) {
                    return (int) $numericA <=> (int) $numericB;
                }

                return strnatcmp((string) $a, (string) $b);
            });

            $dados[$chave] = [
                'total' => $totalComMedida,
                'grupos' => $grupos,
            ];
        }

        // Lista plana de todos os funcionários com os 3 tamanhos (modal de consulta)
        $dados['funcionarios'] = $funcionarios->map(function ($f) {
            return [
                'id' => $f->id,
                'nome' => $f->nome,
                'cargo' => $f->cargo,
                'setor' => $f->setor,
                'empresa' => $f->empresa,
                'camisa' => $f->camisa_tamanho,
                'calca' => $f->calca_tamanho,
                'bota' => $f->bota_numero,
            ];
        })->values()->all();

        return $dados;
    }

    /**
     * Saldo em banco das peças de fardamento (via Controle de Estoque) e
     * comparativo entre demanda cadastrada e saldo disponível na rede.
     *
     * @param  int  $filialId  Filial ativa (0 = Matriz)
     * @param  array<string, mixed>|null  $distribuicao  Saída de distribuicao() (evita repetir a consulta)
     * @return array{linhas: array<int, array<string, mixed>>, total: int, saldo_por_tipo: array<string, int>, demanda: array<string, int>, deficit: array<string, array<string, int>>}
     */
    public function estoque(int $filialId = 0, ?array $distribuicao = null): array
    {
        $distribuicao ??= $this->distribuicao();

        $fardamentoEpis = Epi::where('ss_e_tx_status', 'ativo')
            ->where(function ($q) {
                $q->where(function ($r) {
                    $r->where('ss_e_tx_grupo', 'LIKE', '%fardamento%')
                        ->orWhere('ss_e_tx_grupo', 'LIKE', '%uniforme%')
                        ->orWhere('ss_e_tx_subgrupo', 'LIKE', '%fardamento%')
                        ->orWhere('ss_e_tx_subgrupo', 'LIKE', '%uniforme%');
                })
                    ->orWhere(function ($r) {
                        $r->where('ss_e_tx_item', 'LIKE', '%camisa%')
                            ->orWhere('ss_e_tx_item', 'LIKE', '%calça%')
                            ->orWhere('ss_e_tx_item', 'LIKE', '%calca%')
                            ->orWhere('ss_e_tx_item', 'LIKE', '%bota%');
                    });
            })
            ->with(['variacoes' => function ($q) {
                $q->where('ss_ev_tx_status', 'ativo');
            }])
            ->orderBy('ss_e_tx_grupo')
            ->orderBy('ss_e_tx_item')
            ->get();

        Epi::preloadSaldosFor($fardamentoEpis);

        $linhas = [];
        $saldoPorTipo = ['camisa' => 0, 'calca' => 0, 'bota' => 0];

        foreach ($fardamentoEpis as $epi) {
            $tipo = $this->detectarTipo($epi);

            if ($epi->variacoes->isNotEmpty()) {
                foreach ($epi->variacoes as $v) {
                    $saldoLocal = $epi->getSaldoPorFilial($filialId, $v->ss_ev_nb_id);
                    $saldoRede = $epi->getSaldoTotalRede($v->ss_ev_nb_id);

                    if ($tipo !== null) {
                        $saldoPorTipo[$tipo] += $saldoRede;
                    }

                    $linhas[] = [
                        'epi_id' => $epi->ss_e_nb_id,
                        'grupo' => $epi->ss_e_tx_grupo,
                        'item' => $epi->ss_e_tx_item,
                        'ca' => $epi->ss_e_tx_ca,
                        'variacao_id' => $v->ss_ev_nb_id,
                        'variacao_nome' => $v->ss_ev_tx_nome,
                        'saldo_local' => $saldoLocal,
                        'saldo_rede' => $saldoRede,
                        'disponibilidade' => $saldoLocal > 0 ? 'local' : ($saldoRede > 0 ? 'externo' : 'esgotado'),
                    ];
                }

                continue;
            }

            $saldoLocal = $epi->getSaldoPorFilial($filialId);
            $saldoRede = $epi->getSaldoTotalRede();

            if ($tipo !== null) {
                $saldoPorTipo[$tipo] += $saldoRede;
            }

            $linhas[] = [
                'epi_id' => $epi->ss_e_nb_id,
                'grupo' => $epi->ss_e_tx_grupo,
                'item' => $epi->ss_e_tx_item,
                'ca' => $epi->ss_e_tx_ca,
                'variacao_id' => null,
                'variacao_nome' => null,
                'saldo_local' => $saldoLocal,
                'saldo_rede' => $saldoRede,
                'disponibilidade' => $saldoLocal > 0 ? 'local' : ($saldoRede > 0 ? 'externo' : 'esgotado'),
            ];
        }

        $demanda = [
            'camisa' => $distribuicao['camisa']['total'] ?? 0,
            'calca' => $distribuicao['calca']['total'] ?? 0,
            'bota' => $distribuicao['bota']['total'] ?? 0,
        ];

        $deficit = [];
        foreach ($demanda as $tipo => $qtdDemanda) {
            $saldo = $saldoPorTipo[$tipo] ?? 0;
            $deficit[$tipo] = [
                'demanda' => $qtdDemanda,
                'saldo' => $saldo,
                'cobertura' => $qtdDemanda > 0 ? min(100, (int) round($saldo / $qtdDemanda * 100)) : 0,
                'deficit' => max(0, $qtdDemanda - $saldo),
            ];
        }

        return [
            'linhas' => $linhas,
            'total' => (int) array_sum(array_column($linhas, 'saldo_rede')),
            'saldo_por_tipo' => $saldoPorTipo,
            'demanda' => $demanda,
            'deficit' => $deficit,
        ];
    }

    /**
     * Identifica a categoria de fardamento (camisa, calça ou bota) de um EPI
     * analisando grupo, subgrupo e nome do item no catálogo.
     */
    public function detectarTipo(Epi $epi): ?string
    {
        $texto = mb_strtolower(trim(($epi->ss_e_tx_grupo ?? '').' '.($epi->ss_e_tx_subgrupo ?? '').' '.($epi->ss_e_tx_item ?? '')));

        if (str_contains($texto, 'camisa')) {
            return 'camisa';
        }

        if (str_contains($texto, 'calça') || str_contains($texto, 'calca')) {
            return 'calca';
        }

        if (str_contains($texto, 'bota')) {
            return 'bota';
        }

        return null;
    }
}
