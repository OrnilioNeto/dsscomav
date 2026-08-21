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
            font-size:7px;
            color:#333;
            padding:15px;
        }

        .header-section{
            text-align:center;
            margin-bottom:10px;
        }

        .title{
            font-size:15px;
            font-weight:bold;
            color:#003d82;
            margin-bottom:3px;
        }

        .subtitle{
            font-size:9px;
            color:#555;
            margin-bottom:2px;
        }

        .meta{
            font-size:6.5px;
            color:#888;
        }

        .section-title{
            font-size:9.5px;
            font-weight:bold;
            color:#003d82;
            margin:12px 0 5px;
            padding-bottom:2px;
            border-bottom:1.5px solid #003d82;
        }

        table{
            width:100%;
            border-collapse:collapse;
            border:1px solid #cfcfcf;
        }

        thead{
            display:table-header-group !important;
        }

        tr{
            page-break-inside: avoid !important;
            break-inside: avoid !important;
        }

        th{
            background:#003d82 !important;
            color:#ffffff !important;
            border:1px solid #000000;
            font-size:6.5px;
            font-weight:bold;
            text-align:center;
            vertical-align:middle;
            padding:4px 2px;
            white-space:nowrap;
        }

        td{
            border:1px solid #ddd;
            padding:3px 4px;
            font-size:6.8px;
            vertical-align:middle;
            white-space:nowrap;
            overflow:hidden;
        }

        tbody tr:nth-child(even){
            background:#f5f5f5;
        }

        .label-cell{
            background:#eef2f8;
            font-weight:bold;
            color:#003d82;
        }

        .kpi-value{
            font-size:13px;
            font-weight:bold;
            color:#003d82;
        }

        .kpi-name{
            font-size:6.5px;
            color:#666;
        }

        .kpi-positive{
            color:#059669;
        }

        .kpi-danger{
            color:#dc2626;
        }

        .left{
            text-align:left;
        }

        .parecer{
            background:#f8fafc;
            border:1px solid #dbe1ea;
            padding:8px;
            line-height:1.55;
            font-size:7.2px;
            color:#1f2937;
        }

        .parecer-head{
            font-size:8px;
            font-weight:bold;
            color:#003d82;
            margin-top:6px;
        }

        .small{
            color:#999;
            font-size:6px;
        }
    </style>
</head>

<body>

    <!-- Cabeçalho -->
    <div class="header-section">
        <div class="title">Relatório Analítico de Treinamento</div>
        <div class="subtitle">{{ $report['training']['titulo'] ?? $training->titulo }}</div>
        <div class="meta">
            Gerado em {{ $report['relatorio_gerado_em'] ?? now()->format('d/m/Y H:i') }}
            @if(isset($report['avaliacao']['captura_inicio']))
                &nbsp;|&nbsp; Captura de tentativas desde {{ $report['avaliacao']['captura_inicio'] }}
            @endif
        </div>
    </div>

    <!-- Dados do Treinamento -->
    <div class="section-title">1. Dados do Treinamento</div>
    <table>
        <colgroup>
            <col width="22%">
            <col width="28%">
            <col width="22%">
            <col width="28%">
        </colgroup>
        <tbody>
            <tr nobr="true">
                <td class="label-cell">Tipo</td>
                <td>{{ $report['training']['tipo_label'] ?? '—' }}@if(!empty($report['training']['tipo_treinamento_label'])) ({{ $report['training']['tipo_treinamento_label'] }})@endif</td>
                <td class="label-cell">Público-alvo</td>
                <td>{{ $report['training']['publico_alvo_label'] ?? '—' }}</td>
            </tr>
            <tr nobr="true">
                <td class="label-cell">Carga horária</td>
                <td>
                    @if($report['training']['carga_horaria'])
                        @php $ch = (int)$report['training']['carga_horaria']; @endphp
                        {{ $ch >= 60 ? floor($ch / 60).'h'.(($ch % 60) ? ' '.($ch % 60).'min' : '') : $ch.' min' }}
                    @else
                        —
                    @endif
                </td>
                <td class="label-cell">Validade</td>
                <td>{{ $report['training']['dias_validade'] ? $report['training']['dias_validade'].' dias' : 'Sem validade' }}</td>
            </tr>
            <tr nobr="true">
                <td class="label-cell">Nota mínima p/ aprovação</td>
                <td>{{ $report['training']['nota_minima_aprovacao'] ? $report['training']['nota_minima_aprovacao'].'%' : '—' }}</td>
                <td class="label-cell">Questões</td>
                <td>{{ $report['training']['quantidade_questoes_prova'] ?? 0 }} na prova / {{ $report['training']['total_questoes_banco'] ?? 0 }} no banco</td>
            </tr>
            <tr nobr="true">
                <td class="label-cell">Status</td>
                <td>{{ $report['training']['status_label'] ?? '—' }} @if($report['training']['obrigatorio']) · Obrigatório @endif</td>
                <td class="label-cell">Publicação / Liberação</td>
                <td>{{ $report['training']['data_publicacao'] ?? '—' }} / {{ $report['training']['data_liberacao'] ?? '—' }}</td>
            </tr>
        </tbody>
    </table>

    <!-- KPIs -->
    <div class="section-title">2. Indicadores de Cobertura</div>
    <table>
        <colgroup>
            <col width="16%">
            <col width="16%">
            <col width="17%">
            <col width="17%">
            <col width="17%">
            <col width="17%">
        </colgroup>
        <tbody>
            <tr nobr="true">
                <td style="text-align:center;">
                    <div class="kpi-value">{{ $report['kpis']['usuarios_ativos_total'] ?? 0 }}</div>
                    <div class="kpi-name">Usuários ativos elegíveis</div>
                </td>
                <td style="text-align:center;">
                    <div class="kpi-value">{{ $report['kpis']['usuarios_com_certificado'] ?? 0 }}</div>
                    <div class="kpi-name">Participantes com certificado</div>
                </td>
                <td style="text-align:center;">
                    <div class="kpi-value {{ ($report['kpis']['percentual_usuarios_ativos'] ?? 0) >= 75 ? 'kpi-positive' : 'kpi-danger' }}">{{ $report['kpis']['percentual_usuarios_ativos'] ?? 0 }}%</div>
                    <div class="kpi-name">Cobertura do público efetivo</div>
                </td>
                <td style="text-align:center;">
                    <div class="kpi-value">{{ $report['kpis']['concluidos'] ?? 0 }}</div>
                    <div class="kpi-name">Progressos concluídos</div>
                </td>
                <td style="text-align:center;">
                    <div class="kpi-value">{{ $report['kpis']['avg_time_human'] ?? '00:00:00' }}</div>
                    <div class="kpi-name">Tempo médio assistido</div>
                </td>
                <td style="text-align:center;">
                    <div class="kpi-value">{{ $report['kpis']['avg_days_to_complete'] ?? '—' }}</div>
                    <div class="kpi-name">Dias médios p/ conclusão</div>
                </td>
            </tr>
        </tbody>
    </table>

    <!-- Análise de Avaliação -->
    <div class="section-title">3. Desempenho na Avaliação</div>
    <table>
        <colgroup>
            <col width="20%">
            <col width="8%">
            <col width="72%">
        </colgroup>
        <thead>
            <tr nobr="true">
                <th>Grupo</th>
                <th>Qtd.</th>
                <th>Usuários</th>
            </tr>
        </thead>
        <tbody>
            @php
                $gruposNomes = [
                    'aprovados_1a_tentativa' => 'Aprovados na 1ª tentativa',
                    'aprovados_2a_tentativa' => 'Aprovados na 2ª tentativa',
                    'reassistiram_conteudo' => 'Reprovaram 2x e reassistiram o conteúdo',
                    'aguardando_2a_tentativa' => 'Aguardando a 2ª tentativa',
                ];
            @endphp
            @foreach($gruposNomes as $chave => $rotulo)
                @php
                    $grp = $report['avaliacao'][$chave] ?? ['total' => 0, 'usuarios' => []];
                    $nomes = array_column($grp['usuarios'] ?? [], 'nome');
                    $celulaNomes = count($nomes) <= 10
                        ? implode('; ', $nomes)
                        : count($nomes).' usuários — ver seção 5';
                @endphp
                <tr nobr="true">
                    <td class="left">{{ $rotulo }}</td>
                    <td>{{ $grp['total'] ?? 0 }}</td>
                    <td class="left">{{ count($nomes) ? $celulaNomes : '—' }}</td>
                </tr>
            @endforeach
            <tr nobr="true">
                <td class="left">Nota média (submissões / aprovações)</td>
                <td colspan="2" class="left">
                    @if(isset($report['avaliacao']['nota_media_submissoes']))
                        {{ $report['avaliacao']['nota_media_submissoes'] }}% / {{ $report['avaliacao']['nota_media_aprovacoes'] ?? '—' }}%
                    @else
                        Sem dados registrados
                    @endif
                </td>
            </tr>
        </tbody>
    </table>
    @if(($report['avaliacao']['aprovados_sem_registro_tentativa'] ?? 0) > 0)
        <div class="small" style="margin-top:3px;">
            Observação: o grupo "Aprovados na 1ª tentativa" inclui {{ $report['avaliacao']['aprovados_sem_registro_tentativa'] }} usuário(s) que concluíram antes do início do registro de tentativas{{ !empty($report['avaliacao']['captura_inicio']) ? ' ('.$report['avaliacao']['captura_inicio'].')' : ' na plataforma' }} e, portanto, não possuem registro individual de tentativa.
        </div>
    @endif

    <!-- Parecer Executivo -->
    <div class="section-title">4. Parecer Executivo @if($ai_source === 'ai') (IA) @else (análise local) @endif</div>
    <div class="parecer">
        @php
            $linhas = preg_split('/\R/', trim($ai_summary));
        @endphp
        @foreach($linhas as $linha)
            @if(preg_match('/^##\s+(.+)$/', $linha, $m))
                <div class="parecer-head">{{ $m[1] }}</div>
            @elseif(trim($linha) !== '')
                <div>{{ $linha }}</div>
            @endif
        @endforeach
    </div>

    <!-- Tabela por Usuário -->
    <div class="section-title">5. Detalhamento por Usuário</div>
    <table>
        <colgroup>
            <col width="18%">
            <col width="7%">
            <col width="10%">
            <col width="10%">
            <col width="8%">
            <col width="8%">
            <col width="7%">
            <col width="7%">
            <col width="7%">
            <col width="8%">
            <col width="10%">
        </colgroup>
        <thead>
            <tr nobr="true">
                <th>Usuário</th>
                <th>Tipo</th>
                <th>Setor</th>
                <th>Cargo</th>
                <th>Início</th>
                <th>Conclusão</th>
                <th>Nota</th>
                <th>Tentativas</th>
                <th>Progresso</th>
                <th>Tempo</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($report['usuarios'] as $u)
                <tr nobr="true">
                    <td class="left">{{ $u['nome'] }}</td>
                    <td>
                        @php
                            $tipos = ['motorista' => 'Motorista', 'funcionario' => 'Funcionário', 'terceirizado' => 'Terceirizado'];
                        @endphp
                        {{ $tipos[$u['tipo_usuario']] ?? ($u['tipo_usuario'] ? ucfirst($u['tipo_usuario']) : '—') }}
                    </td>
                    <td>{{ $u['setor'] ?? '—' }}</td>
                    <td>{{ $u['cargo'] ?? '—' }}</td>
                    <td>{{ $u['data_inicio'] ?? '—' }}</td>
                    <td>{{ $u['data_conclusao'] ?? '—' }}</td>
                    <td>{{ $u['nota'] !== null ? $u['nota'].'%' : '—' }}</td>
                    <td>{{ $u['tentativas'] ?? '—' }}</td>
                    <td>{{ $u['porcentagem_assistida'] }}%</td>
                    <td>{{ $u['tempo_assistido_human'] }}</td>
                    <td>{{ $u['concluido'] ? 'Concluído' : 'Pendente' }}</td>
                </tr>
            @empty
                <tr nobr="true"><td colspan="11">Nenhum usuário com progresso registrado.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- Não Concluíram -->
    @php $nc = $report['nao_concluiram'] ?? []; @endphp
    @if(count($nc) > 0)
        <div class="section-title">6. Não Concluíram ({{ count($nc) }})</div>
        <div class="small" style="margin-bottom:4px;">Usuários elegíveis que não obtiveram certificado. Usuários em férias são automaticamente excluídos desta lista.</div>
        <table>
            <colgroup>
                <col width="35%">
                <col width="15%">
                <col width="22%">
                <col width="28%">
            </colgroup>
            <thead>
                <tr nobr="true">
                    <th>Usuário</th>
                    <th>Tipo</th>
                    <th>CPF</th>
                    <th>Motivo</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $tiposLabel = ['motorista' => 'Motorista', 'funcionario' => 'Funcionário', 'terceirizado' => 'Terceirizado'];
                @endphp
                @foreach($nc as $u)
                    <tr nobr="true">
                        <td class="left">{{ $u['nome'] }}</td>
                        <td>{{ $tiposLabel[$u['tipo_usuario']] ?? ($u['tipo_usuario'] ? ucfirst($u['tipo_usuario']) : '—') }}</td>
                        <td>{{ $u['cpf'] }}</td>
                        <td class="left">{{ $u['motivo'] }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

</body>
</html>