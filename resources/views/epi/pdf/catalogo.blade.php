@extends('epi.pdf._layout')

@section('conteudo')
    <table class="kpi">
        <tr>
            <td>
                <div class="value">{{ $dados['epis']->count() }}</div>
                <div class="label">EPIs no catálogo</div>
            </td>
            <td>
                <div class="value">{{ number_format($dados['total_rede'], 0, ',', '.') }}</div>
                <div class="label">Saldo total (rede)</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Catálogo Universal de EPIs</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:17%;">Grupo / Subgrupo</th>
                <th style="width:25%;">Item / Descrição</th>
                <th style="width:9%;">CA / Validade</th>
                <th style="width:22%;">Variações (saldo rede)</th>
                <th style="width:8%;">Vida útil</th>
                <th style="width:8%;">Saldo rede</th>
                <th style="width:11%;">Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dados['epis'] as $epi)
                <tr>
                    <td>
                        <strong>{{ $epi->ss_e_tx_grupo }}</strong><br>
                        <span class="muted">{{ $epi->ss_e_tx_subgrupo ?: '-' }}</span>
                    </td>
                    <td>
                        <strong>{{ $epi->ss_e_tx_item }}</strong>
                        @if ($epi->ss_e_tx_descricao)
                            <br><span class="muted">{{ \Illuminate\Support\Str::limit($epi->ss_e_tx_descricao, 90) }}</span>
                        @endif
                    </td>
                    <td class="center">
                        {{ $epi->ss_e_tx_ca ?: '-' }}
                        @if ($epi->ss_e_tx_validade_ca)
                            <br><span class="muted">{{ date('d/m/Y', strtotime($epi->ss_e_tx_validade_ca)) }}</span>
                        @endif
                    </td>
                    <td>
                        @forelse ($epi->variacoes as $variacao)
                            {{ $variacao->ss_ev_tx_nome }} ({{ $dados['saldos'][$epi->ss_e_nb_id][$variacao->ss_ev_nb_id] ?? 0 }})@if (!$loop->last) · @endif
                        @empty
                            <span class="muted">-</span>
                        @endforelse
                    </td>
                    <td class="center">
                        {{ $epi->ss_e_nb_vida_util_dias > 0 ? $epi->ss_e_nb_vida_util_dias.' dias' : 'N/C' }}
                    </td>
                    <td class="center"><strong>{{ $epi->getSaldoTotalRede() }}</strong></td>
                    <td class="center">
                        @if ($epi->ss_e_tx_status === 'ativo')
                            <span class="badge-ok">Ativo</span>
                        @else
                            <span class="badge-bad">Inativo</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="empty">Nenhum EPI encontrado com os filtros informados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        Saldo calculado pela soma das entradas e devoluções menos as saídas e substituições do Controle de Estoque.
        “N/C” indica vida útil em dias não configurada para o item.
    </div>
@endsection
