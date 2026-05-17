<html>
<head>
    <style>
        body { font-family: Helvetica, Arial, sans-serif; font-size: 9px; line-height: 1.3; margin: 0; padding: 0; }
        .header-section { margin-bottom: 16px; padding-bottom: 12px; text-align: center; }
        .title { font-size: 18px; font-weight: 700; color: #003d82; margin-bottom: 4px; }
        .subtitle { font-size: 11px; color: #666; margin-top: 2px; font-weight: 500; }
        .audit-header { margin-bottom: 12px; background: #f0f4f8; padding: 10px; border-left: 5px solid #003d82; font-size: 9px; }
        .audit-header-label { font-weight: 700; color: #003d82; margin-top: 6px; }
        .audit-header-label:first-child { margin-top: 0; }
        .audit-header-value { color: #333; margin-top: 2px; padding: 4px 0; }
        .audit-empty { color: #999; font-style: italic; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #bbb; padding: 5px 4px; font-size: 8px; text-align: left; }
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
<div class="audit-header">
<div class="audit-header-label">Pergunta Cadastrada:</div>
<div class="audit-header-value">
@if(!$multiTraining && $training)
{{ $training->avaliacao_pergunta ?? '—' }}
@else
<span class="audit-empty">Multiplos treinamentos - Consulte tabela abaixo</span>
@endif
</div>
<div class="audit-header-label">Resposta Correta Cadastrada:</div>
<div class="audit-header-value">
@if(!$multiTraining && $training)
@php
$opcoes = $training->avaliacao_opcoes;
$respostaCorretaIdx = $training->avaliacao_resposta_correta;
@endphp
@if(is_array($opcoes) && $respostaCorretaIdx !== null && isset($opcoes[$respostaCorretaIdx]))
{{ $opcoes[$respostaCorretaIdx] }}
@else
—
@endif
@else
<span class="audit-empty">Multiplos treinamentos - Consulte tabela abaixo</span>
@endif
</div>
</div>
<table>
<thead>
<tr>
<th class="text-center">Usuario</th>
<th class="text-center">Ocupacao</th>
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
<td class="text-center">{{ optional($p->user)->tipo_usuario ? ucfirst(str_replace('_',' ', optional($p->user)->tipo_usuario)) : '—' }}</td>
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
        <span class="small">(registrado na liberacao)</span>
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
