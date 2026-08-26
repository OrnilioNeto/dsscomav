<html>
<head>
    <meta charset="UTF-8">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:Helvetica,Arial,sans-serif;font-size:7px;color:#333;padding:15px;}

        .header-section{text-align:center;margin-bottom:12px;}
        .title{font-size:14px;font-weight:bold;color:#003d82;margin-bottom:3px;}
        .subtitle{font-size:8px;color:#777;}
        .filtro-info{font-size:7px;color:#555;margin-top:4px;background:#f0f4f8;padding:4px 8px;border-radius:3px;}

        table{width:100%;border-collapse:collapse;border:1px solid #cfcfcf;margin-bottom:10px;}
        thead{display:table-header-group !important;}
        tbody{display:table-row-group;}
        tr{page-break-inside:avoid !important;break-inside:avoid !important;}

        th{background:#003d82 !important;color:#ffffff !important;border:1px solid #000;font-size:6.5px;font-weight:bold;text-align:center;vertical-align:middle;padding:4px 2px;height:20px;white-space:nowrap;}
        td{border:1px solid #ddd;padding:3px;font-size:6.5px;text-align:center;vertical-align:middle;page-break-inside:avoid !important;}
        tbody tr:nth-child(even){background:#f5f5f5;}
        tbody tr:nth-child(odd){background:#ffffff;}

        .col-treinamento{width:18%;}
        .col-tipo{width:8%;}
        .col-part{width:7%;}
        .col-concl{width:7%;}
        .col-taxa{width:8%;}
        .col-tempo{width:8%;}
        .col-usuarios{width:44%;text-align:left !important;}

        .badge{display:inline-block;padding:1px 4px;border-radius:8px;font-size:5.5px;font-weight:bold;margin:1px 0;}
        .badge-verde{background:#d1fae5;color:#065f46;}
        .badge-amarelo{background:#fef3c7;color:#92400e;}
        .badge-cinza{background:#e2e8f0;color:#475569;}

        .resumo-box{display:inline-block;background:#e0e7ff;color:#3730a3;padding:2px 6px;border-radius:3px;font-size:6.5px;font-weight:bold;margin:2px;}
    </style>
</head>
<body>

    <div class="header-section">
        <div class="title">Resumo de Desempenho por Conteúdo</div>
        <div class="subtitle">Diálogo Semanal de Segurança - DSS</div>
        @if($subtituloFiltro)
            <div class="filtro-info">{{ $subtituloFiltro }}</div>
        @endif
        <div style="margin-top:6px;">
            <span class="resumo-box">Total: {{ $totalTreinamentos }}</span>
            <span class="resumo-box">Concluídos: {{ $totalConcluidas }}</span>
            <span class="resumo-box">Taxa Geral: {{ $taxaGeral }}%</span>
            <span class="resumo-box">Tempo Total: {{ $tempoTotalFormatado }}</span>
        </div>
    </div>

    @if($treinamentosResumo->isEmpty())
        <table>
            <tbody>
                <tr><td style="height:40px;">Nenhum registro encontrado</td></tr>
            </tbody>
        </table>
    @else
        <table>
            <thead>
                <tr>
                    <th class="col-treinamento">Treinamento</th>
                    <th class="col-tipo">Tipo</th>
                    <th class="col-part">Participações</th>
                    <th class="col-concl">Concluídas</th>
                    <th class="col-taxa">Taxa Conclusão</th>
                    <th class="col-tempo">Tempo Total</th>
                    <th class="col-usuarios">Usuários</th>
                </tr>
            </thead>
            <tbody>
                @foreach($treinamentosResumo as $resumo)
                    @php
                        $taxa = $resumo->assistencias > 0 ? number_format(($resumo->concluidas / $resumo->assistencias) * 100, 1, ',', '.') . '%' : '0,0%';
                        $usuariosTreino = $usuariosPorTreinamento[$resumo->training_id] ?? null;
                    @endphp
                    <tr>
                        <td class="col-treinamento" style="text-align:left;font-weight:bold;">
                            {{ optional($resumo->training)->titulo ?? 'Removido' }}
                        </td>
                        <td class="col-tipo">
                            {{ optional($resumo->training)->tipo ? ucfirst(optional($resumo->training)->tipo) : '—' }}
                        </td>
                        <td class="col-part">{{ $resumo->assistencias }}</td>
                        <td class="col-concl">{{ $resumo->concluidas }}</td>
                        <td class="col-taxa">{{ $taxa }}</td>
                        <td class="col-tempo">{{ gmdate('H:i:s', (int) ($resumo->tempo_total_assistido ?? 0)) }}</td>
                        <td class="col-usuarios">
                            @if($usuariosTreino)
                                @foreach($usuariosTreino['concluidos'] as $u)
                                    <span class="badge badge-verde">{{ $u->nome }}</span>
                                @endforeach
                                @foreach($usuariosTreino['pendentes'] as $u)
                                    <span class="badge badge-amarelo">{{ $u->nome }}</span>
                                @endforeach
                                @foreach($usuariosTreino['nao_iniciados'] as $u)
                                    <span class="badge badge-cinza">{{ $u->nome }}</span>
                                @endforeach
                                @if($usuariosTreino['concluidos']->isEmpty() && $usuariosTreino['pendentes']->isEmpty() && $usuariosTreino['nao_iniciados']->isEmpty())
                                    —
                                @endif
                            @else
                                —
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <div style="margin-top:8px;font-size:6px;color:#999;text-align:right;">
        Gerado em {{ now()->format('d/m/Y H:i') }} — Plataforma DSS
    </div>

</body>
</html>
