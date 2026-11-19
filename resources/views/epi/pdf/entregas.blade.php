@extends('epi.pdf._layout')

@section('conteudo')
    <table class="kpi">
        <tr>
            <td>
                <div class="value">{{ $dados['entregas']->count() }}</div>
                <div class="label">Entregas ativas listadas</div>
            </td>
            <td>
                <div class="value">{{ $dados['entregas']->sum('ss_e_nb_quantidade') }}</div>
                <div class="label">Itens entregues</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Histórico de Entregas Ativas (mais recentes)</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:9%;">Data</th>
                <th style="width:20%;">Colaborador</th>
                <th style="width:28%;">EPI entregue</th>
                <th style="width:5%;">Qtd</th>
                <th style="width:11%;">Vencimento</th>
                <th style="width:10%;">Comprovante</th>
                <th style="width:9%;">Assinatura</th>
                <th style="width:8%;">Origem</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dados['entregas'] as $entrega)
                <tr>
                    <td>{{ $entrega->ss_e_tx_data_entrega ? date('d/m/Y', strtotime($entrega->ss_e_tx_data_entrega)) : '-' }}</td>
                    <td>
                        <strong>{{ $entrega->colaborador->ss_c_tx_nome ?? 'N/D' }}</strong>
                        @if ($entrega->colaborador && $entrega->colaborador->ss_c_tx_matricula)
                            <br><span class="muted">Mat.: {{ $entrega->colaborador->ss_c_tx_matricula }}</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ $entrega->epi->ss_e_tx_item ?? 'EPI N/D' }}</strong>
                        @if ($entrega->variacao)
                            <br><span class="muted">{{ $entrega->variacao->ss_ev_tx_nome }}</span>
                        @endif
                        @if ($entrega->epi && $entrega->epi->ss_e_tx_ca)
                            <span class="muted"> · CA {{ $entrega->epi->ss_e_tx_ca }}</span>
                        @endif
                    </td>
                    <td class="center"><strong>{{ $entrega->ss_e_nb_quantidade }}</strong></td>
                    <td class="center">
                        @if ($entrega->ss_e_tx_vencimento)
                            {{ date('d/m/Y', strtotime($entrega->ss_e_tx_vencimento)) }}
                            @if ($entrega->ss_e_tx_vencimento < now()->format('Y-m-d'))
                                <br><span class="badge-bad">Vencida</span>
                            @elseif ($entrega->ss_e_tx_vencimento <= now()->addDays(30)->format('Y-m-d'))
                                <br><span class="badge-warn">Vence em 30 dias</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">
                        @if ($entrega->ss_e_tx_assinatura)
                            Assinatura
                        @elseif ($entrega->ss_e_tx_foto)
                            Foto do recibo
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">
                        @if ($entrega->ss_e_tx_requer_assinatura)
                            @if ($entrega->ss_e_tx_status_assinatura === 'assinada')
                                <span class="badge-ok">Assinada</span>
                            @elseif ($entrega->ss_e_tx_status_assinatura === 'negada')
                                <span class="badge-bad">Recusada</span>
                            @else
                                <span class="badge-warn">Pendente</span>
                            @endif
                        @else
                            -
                        @endif
                    </td>
                    <td class="center">
                        {{ $entrega->ss_e_tx_retroativo ? 'Retroativa' : 'Normal' }}
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" class="empty">Nenhuma entrega ativa registrada.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        Exibe as {{ number_format($dados['limite'], 0, ',', '.') }} entregas ativas mais recentes (lançamentos cadastrados),
        omitindo entregas inativadas e devolvidas. “Retroativa” indica registro sem baixa de estoque.
    </div>
@endsection
