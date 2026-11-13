@extends('epi.pdf._layout')

@section('conteudo')
    <table class="kpi">
        <tr>
            <td>
                <div class="value">{{ number_format($dados['total_rede'], 0, ',', '.') }}</div>
                <div class="label">Saldo total (rede)</div>
            </td>
            <td>
                <div class="value">{{ number_format($dados['total_filial'], 0, ',', '.') }}</div>
                <div class="label">Saldo na filial</div>
            </td>
            <td>
                <div class="value badge-ok">{{ number_format($dados['com_saldo'], 0, ',', '.') }}</div>
                <div class="label">Itens com saldo</div>
            </td>
            <td>
                <div class="value badge-warn">{{ number_format($dados['baixos'], 0, ',', '.') }}</div>
                <div class="label">Baixo estoque (até {{ $dados['limiar'] }})</div>
            </td>
            <td>
                <div class="value badge-bad">{{ number_format($dados['esgotados'], 0, ',', '.') }}</div>
                <div class="label">Esgotados</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Saldos por EPI — {{ $dados['filial'] }}</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:30%;">EPI</th>
                <th style="width:26%;">Variações (saldo rede)</th>
                <th style="width:10%;">Saldo na filial</th>
                <th style="width:10%;">Saldo total (rede)</th>
                <th style="width:12%;">Situação</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dados['itens'] as $item)
                <tr>
                    <td>
                        <strong>{{ $item['item'] }}</strong><br>
                        <span class="muted">
                            {{ $item['grupo'] }}{{ $item['subgrupo'] ? ' · '.$item['subgrupo'] : '' }}{{ $item['ca'] ? ' · CA '.$item['ca'] : '' }}
                        </span>
                    </td>
                    <td>
                        @forelse ($item['variacoes'] as $variacao)
                            {{ $variacao['nome'] }} ({{ $variacao['saldo'] }})@if (!$loop->last) · @endif
                        @empty
                            <span class="muted">-</span>
                        @endforelse
                    </td>
                    <td class="center">{{ $item['saldo_filial'] }}</td>
                    <td class="center"><strong>{{ $item['saldo_rede'] }}</strong></td>
                    <td class="center">
                        @if ($item['situacao'] === 'ok')
                            <span class="badge-ok">Em estoque</span>
                        @elseif ($item['situacao'] === 'baixo')
                            <span class="badge-warn">Estoque baixo</span>
                        @else
                            <span class="badge-bad">Esgotado</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty">Nenhum item encontrado com os filtros informados.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        Os saldos acompanham a filial selecionada no topo do módulo. “Saldo total (rede)” soma todas as filiais.
        Baixo estoque considera o limiar de {{ $dados['limiar'] }} unidade(s) definido na tela.
    </div>
@endsection
