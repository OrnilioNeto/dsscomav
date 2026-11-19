@extends('epi.pdf._layout')

@section('conteudo')
    <div class="section-title">Distribuição de tamanhos (colaboradores ativos)</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:20%;">Categoria</th>
                <th style="width:20%;">Tamanho</th>
                <th style="width:20%;">Colaboradores</th>
                <th style="width:40%;">Participação</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados['categorias'] as $chave => $rotulo)
                @php $categoria = $dados['distribuicao'][$chave] ?? ['total' => 0, 'grupos' => []]; @endphp
                @forelse ($categoria['grupos'] as $tamanho => $info)
                    <tr>
                        <td><strong>{{ $rotulo }}</strong></td>
                        <td class="center">{{ $tamanho }}</td>
                        <td class="center">{{ $info['qtd'] }}</td>
                        <td class="center">
                            {{ $categoria['total'] > 0 ? round($info['qtd'] / $categoria['total'] * 100) : 0 }}%
                            <span class="muted">de {{ $categoria['total'] }} cadastro(s)</span>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td><strong>{{ $rotulo }}</strong></td>
                        <td colspan="3" class="empty">Nenhum colaborador com tamanho cadastrado.</td>
                    </tr>
                @endforelse
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Demanda x Saldo em banco</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:25%;">Categoria</th>
                <th style="width:15%;">Demanda</th>
                <th style="width:15%;">Saldo em banco</th>
                <th style="width:15%;">Cobertura</th>
                <th style="width:15%;">Déficit p/ pedido</th>
                <th style="width:15%;">Situação</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($dados['categorias'] as $chave => $rotulo)
                @php $def = $dados['deficit'][$chave] ?? ['demanda' => 0, 'saldo' => 0, 'cobertura' => 0, 'deficit' => 0]; @endphp
                <tr>
                    <td><strong>{{ $rotulo }}</strong></td>
                    <td class="center">{{ $def['demanda'] }}</td>
                    <td class="center">{{ $def['saldo'] }}</td>
                    <td class="center">{{ $def['cobertura'] }}%</td>
                    <td class="center">{{ $def['deficit'] > 0 ? $def['deficit'] : '-' }}</td>
                    <td class="center">
                        @if ($def['deficit'] > 0)
                            <span class="badge-bad">Comprar {{ $def['deficit'] }}</span>
                        @else
                            <span class="badge-ok">Atendido</span>
                        @endif
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="section-title">Saldo em banco por item/variação — {{ $dados['filial'] }}</div>

    <table class="data">
        <thead>
            <tr>
                <th style="width:36%;">EPI / Peça</th>
                <th style="width:14%;">Variação / Tamanho</th>
                <th style="width:13%;">Saldo filial ativa</th>
                <th style="width:13%;">Saldo rede</th>
                <th style="width:24%;">Disponibilidade</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($dados['linhas'] as $linha)
                <tr>
                    <td>
                        <strong>{{ $linha['item'] }}</strong><br>
                        <span class="muted">{{ $linha['grupo'] }}{{ $linha['ca'] ? ' · CA '.$linha['ca'] : '' }}</span>
                    </td>
                    <td class="center">{{ $linha['variacao_nome'] ?: 'Geral' }}</td>
                    <td class="center">{{ $linha['saldo_local'] }}</td>
                    <td class="center"><strong>{{ $linha['saldo_rede'] }}</strong></td>
                    <td class="center">
                        @if ($linha['disponibilidade'] === 'local')
                            <span class="badge-ok">Disponível na filial</span>
                        @elseif ($linha['disponibilidade'] === 'externo')
                            <span class="badge-warn">Em outra filial</span>
                        @else
                            <span class="badge-bad">Esgotado na rede</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="empty">Nenhum item de fardamento com saldo no Controle de Estoque.</td>
                </tr>
            @endforelse
            @if (count($dados['linhas']) > 0)
                <tr>
                    <td colspan="3" class="right"><strong>Total em unidades na rede:</strong></td>
                    <td class="center"><strong>{{ number_format($dados['total'], 0, ',', '.') }}</strong></td>
                    <td></td>
                </tr>
            @endif
        </tbody>
    </table>

    <div class="note">
        A distribuição considera os tamanhos informados no cadastro dos colaboradores ativos.
        O saldo em banco é calculado pelos lançamentos de entrada/saída do Controle de Estoque.
    </div>
@endsection
