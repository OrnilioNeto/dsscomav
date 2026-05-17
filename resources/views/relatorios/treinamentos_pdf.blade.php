<html>
<head>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; line-height: 1.4; margin: 0; padding: 0; }
        .header-section { margin-bottom: 16px; padding-bottom: 12px; text-align: center; }
        .title { font-size: 18px; font-weight: 700; color: #003d82; margin-bottom: 4px; }
        .subtitle { font-size: 11px; color: #666; margin-top: 2px; font-weight: 500; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; page-break-inside: avoid; }
        thead { display: table-header-group; }
        tbody tr { page-break-inside: avoid; page-break-after: auto; }
        th, td { border: 1px solid #bbb; padding: 6px 3px; font-size: 8px; text-align: left; vertical-align: middle; }
        th { background: #003d82; font-weight: 700; color: white; }
        tr:nth-child(even) { background: #fafbfc; }
        .text-center { text-align: center; }
        .small { font-size: 8px; color: #666; }
    </style>
</head>
<body>
<div class="header-section">
<div class="title">Relatório de Treinamentos</div>
<div class="subtitle">{{ $subtitle ?? '' }}</div>
</div>
<table>
<thead>
<tr>
<th class="text-center">Usuario</th>
<th class="text-center">Funcao</th>
<th class="text-center">Treinamento</th>
<th class="text-center">Tempo Assistido</th>
<th class="text-center">Progresso</th>
<th class="text-center">Status</th>
<th class="text-center">Data de Postagem</th>
<th>Pergunta da Avaliacao</th>
<th>Resposta do Usuario</th>
<th class="text-center">Inicio</th>
<th class="text-center">Conclusao</th>
</tr>
</thead>
<tbody>
@forelse($progressos as $p)
<tr>
<td>{{ optional($p->user)->nome ?? '—' }}</td>
<td class="text-center">@if(optional($p->user)->tipo_usuario === 'motorista') Motorista @elseif(optional($p->user)->tipo_usuario === 'funcionario') {{ optional($p->user)->cargo ?? '—' }} @else {{ optional($p->user)->tipo_usuario ? ucfirst(str_replace('_',' ', optional($p->user)->tipo_usuario)) : '—' }} @endif</td>
<td>{{ optional($p->training)->titulo ?? '—' }}</td>
<td class="text-center">{{ isset($p->tempo_assistido) ? gmdate('H:i:s', (int)$p->tempo_assistido) : '—' }}</td>
<td class="text-center">{{ $p->porcentagem_assistida ?? 0 }}%</td>
<td class="text-center">@if(($p->status_progresso ?? null) === 'nao_iniciado') Nao iniciado @elseif($p->concluido) Concluido @else Pendente @endif</td>
<td class="text-center">{{ optional($p->training)->data_liberacao ? \Carbon\Carbon::parse(optional($p->training)->data_liberacao)->format('d/m/Y H:i') : '—' }}</td>
<td>{{ optional($p->training)->avaliacao_pergunta ?? '—' }}</td>
<td>
@php
    $opcoesAvaliacao = optional($p->training)->avaliacao_opcoes;
    $respostaUsuario = $p->avaliacao_resposta_usuario ?? null;
    $respostaCorreta = optional($p->training)->avaliacao_resposta_correta;
@endphp
@if($p->concluido && $p->avaliacao_aprovada)
    @if(is_array($opcoesAvaliacao) && $respostaUsuario !== null)
        {{ $opcoesAvaliacao[$respostaUsuario] ?? 'Resposta invalida' }}
    @elseif(is_array($opcoesAvaliacao) && $respostaCorreta !== null)
        {{ $opcoesAvaliacao[$respostaCorreta] ?? '—' }}<br>
    @else
        —
    @endif
@else
    <span class="small">—</span>
@endif
</td>
<td class="text-center">{{ $p->data_inicio ? \Carbon\Carbon::parse($p->data_inicio)->format('d/m/Y H:i') : '—' }}</td>
<td class="text-center">{{ $p->data_conclusao ? \Carbon\Carbon::parse($p->data_conclusao)->format('d/m/Y H:i') : '—' }}</td>
</tr>
@empty
<tr>
<td colspan="11" class="text-center">Nenhum registro encontrado</td>
</tr>
@endforelse
</tbody>
</table>
</body>
</html>
