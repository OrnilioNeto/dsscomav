@extends('epi.pdf._layout')

@section('conteudo')
    <table class="kpi">
        <tr>
            <td>
                <div class="value">{{ $dados['cadastradas']->where('ss_f_tx_status', 'ativo')->count() + 1 }}</div>
                <div class="label">Filiais ativas (inclui Matriz)</div>
            </td>
            <td>
                <div class="value">{{ $dados['cadastradas']->where('ss_f_tx_status', 'inativo')->count() }}</div>
                <div class="label">Filiais inativas</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Filiais Cadastradas</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:14%;">ID / Código</th>
                <th style="width:40%;">Nome da Filial</th>
                <th style="width:26%;">Cidade / UF</th>
                <th style="width:20%;">Status</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td class="center">0 (FIXO)</td>
                <td><strong>Matriz / Sede Principal</strong></td>
                <td>Sede Global</td>
                <td class="center"><span class="badge-ok">Ativo (Padrão)</span></td>
            </tr>
            @forelse ($dados['cadastradas'] as $filial)
                <tr>
                    <td class="center">#{{ $filial->ss_f_nb_id }}{{ $filial->ss_f_tx_codigo ? ' ('.$filial->ss_f_tx_codigo.')' : '' }}</td>
                    <td><strong>{{ $filial->ss_f_tx_nome }}</strong></td>
                    <td>{{ $filial->ss_f_tx_cidade ?: '-' }}</td>
                    <td class="center">
                        @if ($filial->ss_f_tx_status === 'ativo')
                            <span class="badge-ok">Ativo</span>
                        @else
                            <span class="badge-bad">Inativo</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="empty">Nenhuma filial cadastrada além da Matriz.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="note">
        A Matriz (ID 0) é fixa no sistema. Filiais inativas continuam cadastradas, mas não aparecem
        no seletor de filial do módulo.
    </div>
@endsection
