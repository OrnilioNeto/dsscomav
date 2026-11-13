@extends('epi.pdf._layout')

@section('conteudo')
    <table class="kpi">
        <tr>
            <td>
                <div class="value">{{ $dados['ativos']->count() }}</div>
                <div class="label">Filtros de máscara ativos</div>
            </td>
            <td>
                <div class="value badge-bad">{{ $dados['descartados']->count() }}</div>
                <div class="label">Filtros descartados no histórico</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Filtros Ativos (instalados nas máscaras)</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:20%;">Colaborador</th>
                <th style="width:21%;">Máscara</th>
                <th style="width:17%;">Filtro</th>
                <th style="width:4%;">Qtd</th>
                <th style="width:9%;">Instalação</th>
                <th style="width:9%;">Vencimento</th>
                <th style="width:9%;">Falta p/ troca</th>
                <th style="width:11%;">Situação</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dados['ativos'] as $filtro)
                @php
                    $diasRestantes = $filtro->ss_ef_tx_vencimento
                        ? (int) now()->startOfDay()->diffInDays(\Carbon\Carbon::parse($filtro->ss_ef_tx_vencimento)->startOfDay(), false)
                        : null;
                @endphp
                <tr>
                    <td><strong>{{ $filtro->colaborador->ss_c_tx_nome ?? 'N/D' }}</strong></td>
                    <td>
                        {{ $filtro->epi->ss_e_tx_item ?? 'Máscara N/D' }}
                        @if ($filtro->epi && $filtro->epi->ss_e_tx_ca)
                            <br><span class="muted">CA {{ $filtro->epi->ss_e_tx_ca }}</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $filtro->ss_ef_tx_tipo_nome }}</strong>
                        @if ($filtro->ss_ef_tx_ca)
                            <br><span class="muted">CA {{ $filtro->ss_ef_tx_ca }}</span>
                        @endif
                    </td>
                    <td class="center"><strong>{{ $filtro->ss_ef_nb_quantidade }}</strong></td>
                    <td class="center">{{ $filtro->ss_ef_tx_data_instalacao ? date('d/m/Y', strtotime($filtro->ss_ef_tx_data_instalacao)) : '-' }}</td>
                    <td class="center">{{ $filtro->ss_ef_tx_vencimento ? date('d/m/Y', strtotime($filtro->ss_ef_tx_vencimento)) : '-' }}</td>
                    <td class="center">
                        @if ($diasRestantes === null)
                            -
                        @elseif ($diasRestantes === 0)
                            Vence hoje
                        @elseif ($diasRestantes > 0)
                            {{ $diasRestantes }} dia(s)
                        @else
                            Vencido há {{ abs($diasRestantes) }}
                        @endif
                    </td>
                    <td class="center">
                        @if ($diasRestantes === null)
                            <span class="muted">Sem data</span>
                        @elseif ($diasRestantes < 0)
                            <span class="badge-bad">Vencido</span>
                        @elseif ($diasRestantes === 0)
                            <span class="badge-bad">Vence hoje</span>
                        @elseif ($diasRestantes <= 30)
                            <span class="badge-warn">Vence em 30 dias</span>
                        @else
                            <span class="badge-ok">Em dia</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Nenhum filtro de máscara ativo.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Histórico de Descartes</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:20%;">Colaborador</th>
                <th style="width:18%;">Filtro</th>
                <th style="width:20%;">Máscara</th>
                <th style="width:5%;">Qtd</th>
                <th style="width:10%;">Instalação</th>
                <th style="width:10%;">Vencimento</th>
                <th style="width:9%;">Motivo</th>
                <th style="width:8%;">Descarte</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dados['descartados'] as $filtro)
                <tr>
                    <td><strong>{{ $filtro->colaborador->ss_c_tx_nome ?? 'N/D' }}</strong></td>
                    <td><strong>{{ $filtro->ss_ef_tx_tipo_nome }}</strong></td>
                    <td>{{ $filtro->epi->ss_e_tx_item ?? 'Máscara N/D' }}</td>
                    <td class="center"><strong>{{ $filtro->ss_ef_nb_quantidade }}</strong></td>
                    <td class="center">{{ $filtro->ss_ef_tx_data_instalacao ? date('d/m/Y', strtotime($filtro->ss_ef_tx_data_instalacao)) : '-' }}</td>
                    <td class="center">{{ $filtro->ss_ef_tx_vencimento ? date('d/m/Y', strtotime($filtro->ss_ef_tx_vencimento)) : '-' }}</td>
                    <td class="center">{{ $filtro->motivo_descarte_label ?? 'Descartado' }}</td>
                    <td class="center">{{ $filtro->ss_ef_tx_data_descarte ? date('d/m/Y', strtotime($filtro->ss_ef_tx_data_descarte)) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Nenhum filtro descartado até o momento.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        Filtros não são EPIs: o descarte encerra apenas o filtro e nunca altera a máscara, a entrega ou o estoque.
        O histórico exibe os últimos 300 descartes.
    </div>
@endsection
