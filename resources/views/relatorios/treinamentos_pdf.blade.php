<html>
<head>
    <meta charset="UTF-8">

    <style>
        *{
            margin:0;
            padding:0;
            box-sizing:border-box;
        }

        body{
            font-family: Helvetica, Arial, sans-serif;
            font-size:6.5px;
            color:#333;
            padding:15px;
        }

        /* ===============================
           CABEÇALHO
        =============================== */
        .header-section{
            text-align:center;
            margin-bottom:12px;
        }

        .title{
            font-size:15px;
            font-weight:bold;
            color:#003d82;
            margin-bottom:3px;
        }

        .subtitle{
            font-size:8px;
            color:#777;
        }

        /* ===============================
           TABELA
        =============================== */
        table{
            width:100%;
            border-collapse:collapse;
            table-layout:fixed;
            border:1px solid #cfcfcf;
        }

        /* Repetir cabeçalho no PDF */
        thead{
            display:table-header-group !important;
        }

        tbody{
            display:table-row-group;
        }

        /* Não quebrar linha entre páginas */
        tr{
            page-break-inside: avoid !important;
            break-inside: avoid !important;
            page-break-after: auto;
        }

        /* ===============================
           CABEÇALHO DA TABELA
        =============================== */
        th{
            background:#003d82 !important;
            color:#ffffff !important;
            border:1px solid #000000;

            font-size:6.5px;
            font-weight:bold;

            text-align:center;
            vertical-align:middle;

            padding:4px 2px;
            height:24px;

            white-space:nowrap;
            overflow:hidden;
        }

        /* ===============================
           CÉLULAS
        =============================== */
        td{
            border:1px solid #ddd;
            padding:2px;

            font-size:6.5px;
            text-align:center;
            vertical-align:middle;

            /* altura fixa */
            height:24px;
            line-height:24px;

            overflow:hidden;
            white-space:nowrap;
            text-overflow:ellipsis;
            /* NÃO deixa cortar no meio da página */
            page-break-inside: avoid !important;
        }

        /* Zebra */
        tbody tr:nth-child(even){
            background:#f5f5f5;
        }

        tbody tr:nth-child(odd){
            background:#ffffff;
        }

        /* ===============================
           LARGURA FIXA DAS COLUNAS
        =============================== */
        .col-usuario     { width:10%; }
        .col-funcao      { width:14%; }
        .col-treinamento { width:18%; }
        .col-tempo       { width:5%; }
        .col-status      { width:6%; }
        .col-datapost    { width:7%; }
        .col-pergunta    { width:14%; }
        .col-resposta    { width:10%; }
        .col-inicio      { width:6%; }
        .col-conclusao   { width:6%; }

        .small{
            color:#999;
            font-size:6px;
        }

        .text-center{
            text-align:center;
        }

    </style>
</head>

<body>

    <!-- Cabeçalho -->
    <div class="header-section">
        <div class="title">
            Relatório de Treinamentos
        </div>

        @if(!empty($subtitle))
            <div class="subtitle">
                {{ $subtitle }}
            </div>
        @endif
    </div>

    @if($progressos->isEmpty())

        <table>
            <tbody>
                <tr nobr="true">
                    <td style="height:40px;">
                        Nenhum registro encontrado
                    </td>
                </tr>
            </tbody>
        </table>

    @else

        <table>

            <thead>
                <tr>
                    <th class="col-usuario">Usuário</th>
                    <th class="col-funcao">Função</th>
                    <th class="col-treinamento">Treinamento</th>
                    <th class="col-tempo">Tempo</th>
                    <th class="col-status">Status</th>
                    <th class="col-datapost">Data Postagem</th>
                    <th class="col-pergunta">Pergunta Da Avaliação</th>
                    <th class="col-resposta">Resposta do Usuário</th>
                    <th class="col-inicio">Início</th>
                    <th class="col-conclusao">Conclusão</th>
                </tr>
            </thead>

            <tbody>

                @foreach($progressos as $p)

                    <tr nobr="true">

                        <!-- Usuário -->
                        <td class="col-usuario">
                            {{ optional($p->user)->nome ?? '—' }}
                        </td>

                        <!-- Função -->
                        <td class="col-funcao">
                            @if(optional($p->user)->tipo_usuario === 'motorista')
                                Motorista
                            @elseif(optional($p->user)->tipo_usuario === 'funcionario')
                                {{ optional($p->user)->cargo ?? '—' }}
                            @else
                                {{ optional($p->user)->tipo_usuario
                                    ? ucfirst(optional($p->user)->tipo_usuario)
                                    : '—' }}
                            @endif
                        </td>

                        <!-- Treinamento -->
                        <td class="col-treinamento">
                            {{ optional($p->training)->titulo ?? '—' }}
                        </td>

                        <!-- Tempo -->
                        <td class="col-tempo">
                            {{ isset($p->tempo_assistido)
                                ? gmdate('H:i', (int)$p->tempo_assistido)
                                : '—' }}
                        </td>

                        <!-- Status -->
                        <td class="col-status">
                            @if(($p->status_progresso ?? null) === 'nao_iniciado')
                                Não Iniciado
                            @elseif($p->concluido)
                                Concluído
                            @else
                                Pendente
                            @endif
                        </td>

                        <!-- Data Post -->
                        <td class="col-datapost">
                            {{ optional($p->training)->data_liberacao
                                ? \Carbon\Carbon::parse(
                                    optional($p->training)->data_liberacao
                                  )->format('d/m H:i')
                                : '—' }}
                        </td>

                        <!-- Pergunta -->
                        <td class="col-pergunta">
                            {{ optional($p->training)->avaliacao_pergunta ?? '—' }}
                        </td>

                        <!-- Resposta -->
                        <td class="col-resposta">

                            @php
                                $opcoesAvaliacao = optional($p->training)->avaliacao_opcoes;
                                $respostaUsuario = $p->avaliacao_resposta_usuario ?? null;
                                $respostaCorreta = optional($p->training)->avaliacao_resposta_correta;
                            @endphp

                            @if($p->concluido && $p->avaliacao_aprovada)

                                @if(is_array($opcoesAvaliacao) && $respostaUsuario !== null)
                                    {{ $opcoesAvaliacao[$respostaUsuario] ?? 'Inv' }}

                                @elseif(is_array($opcoesAvaliacao) && $respostaCorreta !== null)
                                    {{ $opcoesAvaliacao[$respostaCorreta] ?? '—' }}

                                @else
                                    —
                                @endif

                            @else
                                <span class="small">—</span>
                            @endif

                        </td>

                        <!-- Início -->
                        <td class="col-inicio">
                            {{ $p->data_inicio
                                ? \Carbon\Carbon::parse($p->data_inicio)
                                    ->format('d/m H:i')
                                : '—' }}
                        </td>

                        <!-- Conclusão -->
                        <td class="col-conclusao">
                            {{ $p->data_conclusao
                                ? \Carbon\Carbon::parse($p->data_conclusao)
                                    ->format('d/m H:i')
                                : '—' }}
                        </td>

                    </tr>

                @endforeach

            </tbody>

        </table>

    @endif

</body>
</html>