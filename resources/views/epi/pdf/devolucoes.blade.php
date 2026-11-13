@extends('epi.pdf._layout')

@php
    $motivos = [
        'avaria' => 'Avaria / Danificado',
        'perdido' => 'Perdido',
        'extraviado' => 'Extraviado',
        'devolvido_empresa' => 'Devolvido à empresa',
        'vencido' => 'Vencido',
        'outro' => 'Outro',
    ];
    $destinos = [
        'estoque' => 'Retorno ao estoque',
        'descarte' => 'Descarte',
        'inspecao' => 'Inspeção',
    ];
    $resultados = [
        'estornado' => 'Estornado ao estoque',
        'descartado' => 'Descartado',
    ];
@endphp

@section('conteudo')
    <table class="kpi">
        <tr>
            <td>
                <div class="value badge-warn">{{ $dados['pendentes']->count() }}</div>
                <div class="label">Pendências de inspeção</div>
            </td>
            <td>
                <div class="value">{{ $dados['historico']->count() }}</div>
                <div class="label">Devoluções no histórico</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Pendências de Inspeção</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:23%;">Colaborador</th>
                <th style="width:27%;">EPI</th>
                <th style="width:5%;">Qtd</th>
                <th style="width:14%;">Motivo</th>
                <th style="width:14%;">Registrado por</th>
                <th style="width:17%;">Data</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dados['pendentes'] as $devolucao)
                <tr>
                    <td><strong>{{ $devolucao->colaborador->ss_c_tx_nome ?? 'N/D' }}</strong></td>
                    <td>
                        <strong>{{ $devolucao->epi->ss_e_tx_item ?? 'EPI N/D' }}</strong>
                        @if ($devolucao->variacao)
                            <br><span class="muted">{{ $devolucao->variacao->ss_ev_tx_nome }}</span>
                        @endif
                    </td>
                    <td class="center"><strong>{{ $devolucao->ss_ed_nb_quantidade }}</strong></td>
                    <td class="center">{{ $motivos[$devolucao->ss_ed_tx_motivo] ?? $devolucao->ss_ed_tx_motivo }}</td>
                    <td class="center">{{ $devolucao->usuarioRegistro->nome ?? '-' }}</td>
                    <td class="center">{{ $devolucao->ss_ed_tx_data_registro ? date('d/m/Y H:i', strtotime($devolucao->ss_ed_tx_data_registro)) : '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="empty">Nenhuma pendência de inspeção no momento.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="section-title">Histórico de Devoluções (auditoria)</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:9%;">Data</th>
                <th style="width:16%;">Colaborador</th>
                <th style="width:19%;">EPI</th>
                <th style="width:4%;">Qtd</th>
                <th style="width:10%;">Motivo</th>
                <th style="width:9%;">Destino</th>
                <th style="width:10%;">Situação</th>
                <th style="width:9%;">Registrado por</th>
                <th style="width:9%;">Decidido por</th>
                <th style="width:5%;">Obs.</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dados['historico'] as $devolucao)
                <tr>
                    <td>{{ $devolucao->ss_ed_tx_data_registro ? date('d/m/Y H:i', strtotime($devolucao->ss_ed_tx_data_registro)) : '-' }}</td>
                    <td><strong>{{ $devolucao->colaborador->ss_c_tx_nome ?? 'N/D' }}</strong></td>
                    <td>
                        <strong>{{ $devolucao->epi->ss_e_tx_item ?? 'EPI N/D' }}</strong>
                        @if ($devolucao->variacao)
                            <br><span class="muted">{{ $devolucao->variacao->ss_ev_tx_nome }}</span>
                        @endif
                    </td>
                    <td class="center"><strong>{{ $devolucao->ss_ed_nb_quantidade }}</strong></td>
                    <td class="center">{{ $motivos[$devolucao->ss_ed_tx_motivo] ?? $devolucao->ss_ed_tx_motivo }}</td>
                    <td class="center">{{ $destinos[$devolucao->ss_ed_tx_destino] ?? $devolucao->ss_ed_tx_destino }}</td>
                    <td class="center">
                        @if ($devolucao->ss_ed_tx_status === 'pendente')
                            <span class="badge-warn">Aguardando decisão</span>
                        @elseif ($devolucao->ss_ed_tx_resultado_inspecao)
                            {{ $resultados[$devolucao->ss_ed_tx_resultado_inspecao] ?? $devolucao->ss_ed_tx_resultado_inspecao }}
                        @else
                            Concluída
                        @endif
                    </td>
                    <td class="center">{{ $devolucao->usuarioRegistro->nome ?? '-' }}</td>
                    <td class="center">{{ $devolucao->usuarioDecisao->nome ?? '-' }}</td>
                    <td>{{ $devolucao->ss_ed_tx_observacao ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" class="empty">Nenhuma devolução registrada ainda.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        Devoluções em inspeção ficam pendentes até o gestor estornar o item ao estoque ou confirmar o descarte.
        O histórico preserva os últimos 300 lançamentos para auditoria.
    </div>
@endsection
