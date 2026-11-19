@extends('epi.pdf._layout')

@section('conteudo')
    <table class="kpi">
        <tr>
            <td>
                <div class="value">{{ $dados['movimentos']->count() }}</div>
                <div class="label">Movimentações listadas</div>
            </td>
            <td>
                <div class="value badge-ok">{{ number_format($dados['total_entradas'], 0, ',', '.') }}</div>
                <div class="label">Entradas + devoluções</div>
            </td>
            <td>
                <div class="value badge-bad">{{ number_format($dados['total_saidas'], 0, ',', '.') }}</div>
                <div class="label">Saídas + substituições</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Movimentações de Inventário</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:10%;">Data / Registro</th>
                <th style="width:11%;">Filial</th>
                <th style="width:19%;">EPI</th>
                <th style="width:8%;">Tipo</th>
                <th style="width:5%;">Qtd</th>
                <th style="width:9%;">Validade lote</th>
                <th style="width:8%;">Valor total</th>
                <th style="width:12%;">NF / Fornecedor</th>
                <th style="width:18%;">Motivo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dados['movimentos'] as $movimento)
                <tr>
                    <td>
                        {{ $movimento->ss_e_tx_data ? date('d/m/Y H:i', strtotime($movimento->ss_e_tx_data)) : '-' }}
                    </td>
                    <td>{{ $dados['filiais'][$movimento->ss_e_nb_empresa_id ?? 0] ?? 'Matriz' }}</td>
                    <td>
                        <strong>{{ $movimento->epi->ss_e_tx_item ?? 'EPI N/D' }}</strong>
                        @if ($movimento->variacao)
                            <br><span class="muted">{{ $movimento->variacao->ss_ev_tx_nome }}</span>
                        @endif
                    </td>
                    <td class="center">
                        @if ($movimento->ss_e_tx_tipo === 'entrada')
                            <span class="badge-ok">Entrada</span>
                        @elseif ($movimento->ss_e_tx_tipo === 'saida')
                            <span class="badge-bad">Saída</span>
                        @elseif ($movimento->ss_e_tx_tipo === 'devolucao')
                            <span class="badge-ok">Devolução</span>
                        @else
                            <span class="badge-warn">Substituição</span>
                        @endif
                    </td>
                    <td class="center"><strong>{{ $movimento->ss_e_nb_quantidade }}</strong></td>
                    <td class="center">
                        {{ $movimento->ss_e_tx_validade ? date('d/m/Y', strtotime($movimento->ss_e_tx_validade)) : '-' }}
                        @if ($movimento->ss_e_tx_data_recebimento)
                            <br><span class="muted">Receb.: {{ date('d/m/Y', strtotime($movimento->ss_e_tx_data_recebimento)) }}</span>
                        @endif
                    </td>
                    <td class="right">
                        {{ $movimento->ss_e_db_valor_total ? 'R$ '.number_format($movimento->ss_e_db_valor_total, 2, ',', '.') : '-' }}
                    </td>
                    <td>
                        @if ($movimento->ss_e_tx_chave_nf)
                            <strong>NF: {{ $movimento->ss_e_tx_chave_nf }}</strong><br>
                        @endif
                        <span class="muted">{{ $movimento->ss_e_tx_fornecedor ?: '-' }}</span>
                    </td>
                    <td>{{ $movimento->ss_e_tx_motivo ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">Nenhuma movimentação encontrada com os filtros informados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        Relatório limitado às {{ number_format($dados['limite'], 0, ',', '.') }} movimentações mais recentes que atendem aos filtros.
        Tipos: entrada e devolução somam ao estoque; saída e substituição reduzem.
    </div>
@endsection
