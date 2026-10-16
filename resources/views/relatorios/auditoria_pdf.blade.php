<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 7px; color: #1f2937; padding: 6px; }
        .title { font-size: 15px; font-weight: bold; color: #3b0764; }
        .subtitle { font-size: 8px; color: #6b7280; margin-top: 2px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-top: 7px; }
        .meta-table td { font-size: 6.8px; color: #374151; padding: 2px 4px; vertical-align: top; }
        .meta-label { font-weight: bold; color: #111827; width: 95px; }
        .box-note { border: 1px solid #ddd6fe; background: #f5f3ff; padding: 5px 7px; margin-top: 6px; font-size: 6.8px; color: #4c1d95; }
        .section-title { font-size: 9px; font-weight: bold; color: #111827; border-bottom: 1.5px solid #3b0764; padding-bottom: 2px; margin-top: 10px; margin-bottom: 4px; }
        table.data { width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; margin-top: 3px; }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        table.data th { background: #3b0764; color: #ffffff; font-size: 6px; font-weight: bold; padding: 3px 2px; border: 1px solid #7c5ba6; text-align: center; }
        table.data td { font-size: 6.3px; padding: 2.5px 3px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f9fafb; }
        .center { text-align: center; }
        .kpi td { border: 1px solid #d1d5db; padding: 5px; text-align: center; }
        .kpi .value { font-size: 12px; font-weight: bold; color: #111827; }
        .kpi .label { font-size: 6px; color: #6b7280; text-transform: uppercase; }
        .ok { color: #047857; font-weight: bold; }
        .warn { color: #b45309; font-weight: bold; }
        .bad { color: #b91c1c; font-weight: bold; }
        .muted { color: #6b7280; }
        .footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #d1d5db; font-size: 6px; color: #6b7280; text-align: center; }
        .filters { font-size: 6.5px; color: #374151; margin-top: 3px; }
    </style>
</head>
<body>
    <div class="title">{{ plataforma_nome() }} — Relatório de Auditoria Gerencial</div>
    <div class="subtitle">Conformidade de treinamentos, ausências justificadas e trilha de evidências · Versão {{ app_version() }}</div>

    <table class="meta-table">
        <tr>
            <td class="meta-label">Gerado em:</td>
            <td>{{ $geradoEm->format('d/m/Y H:i:s') }}</td>
            <td class="meta-label">Gerado por:</td>
            <td>{{ $geradoPor }}</td>
        </tr>
        <tr>
            <td class="meta-label">Filtros:</td>
            <td colspan="3">
                @if(empty($filtrosAtivos))
                    Nenhum filtro aplicado (toda a base histórica)
                @else
                    @foreach($filtrosAtivos as $rotulo => $valor)
                        {{ $rotulo }}: {{ $valor }}@if(!$loop->last) · @endif
                    @endforeach
                @endif
            </td>
        </tr>
        <tr>
            <td class="meta-label">SHA-256:</td>
            <td colspan="3" style="font-family: Courier, monospace;">{{ $hashRecorte }}</td>
        </tr>
    </table>

    <div class="box-note">
        <strong>Base de cálculo e transparência:</strong> exclusão apenas de <strong>super_admin</strong> e <strong>usuários de teste</strong>
        ({{ (int) ($metricasExclusao['total'] ?? 0) }} cadastro(s) fora da base). Admins, inativos e afastados permanecem visíveis e filtráveis.
        <strong>Elegibilidade</strong>: corte por cadastro, público-alvo/atribuição, isenção de férias e férias na liberação.
        <strong>Prazo efetivo</strong>: extensão concedida quando existir; senão 23:00 do domingo subsequente à liberação.
        <strong>Conformidade</strong>: concluídos dentro do prazo ÷ pares elegíveis (afastados justificados fora do denominador por padrão).
    </div>

    <div class="section-title">Indicadores gerais</div>
    <table class="kpi">
        <tr>
            <td><div class="value">{{ number_format($resumo['total_usuarios'], 0, ',', '.') }}</div><div class="label">Base auditada</div></td>
            <td><div class="value">{{ number_format($resumo['total_ativos'], 0, ',', '.') }}</div><div class="label">Ativos</div></td>
            <td><div class="value">{{ number_format($resumo['total_treinamentos'], 0, ',', '.') }}</div><div class="label">Conteúdos</div></td>
            <td><div class="value">{{ number_format($resumo['pares_elegiveis'], 0, ',', '.') }}</div><div class="label">Pares elegíveis</div></td>
            <td><div class="value {{ ($resumo['indice_conformidade'] ?? 0) >= 80 ? 'ok' : (($resumo['indice_conformidade'] ?? 0) >= 50 ? 'warn' : 'bad') }}">{{ number_format($resumo['indice_conformidade'], 1, ',', '.') }}%</div><div class="label">Conformidade</div></td>
            <td><div class="value ok">{{ number_format($resumo['concluidos_em_dia'], 0, ',', '.') }}</div><div class="label">Em dia</div></td>
            <td><div class="value warn">{{ number_format($resumo['concluidos_atraso'], 0, ',', '.') }}</div><div class="label">Em atraso</div></td>
            <td><div class="value bad">{{ number_format($resumo['vencidos'], 0, ',', '.') }}</div><div class="label">Prazo expirado</div></td>
        </tr>
        <tr>
            <td><div class="value">{{ number_format($resumo['andamento'], 0, ',', '.') }}</div><div class="label">Em andamento</div></td>
            <td><div class="value">{{ number_format($resumo['nao_iniciados'], 0, ',', '.') }}</div><div class="label">Não iniciados</div></td>
            <td><div class="value">{{ number_format($resumo['isentos'], 0, ',', '.') }}</div><div class="label">Isentos (férias)</div></td>
            <td><div class="value">{{ number_format($resumo['afastados_liberacao'], 0, ',', '.') }}</div><div class="label">Afastados na liberação</div></td>
            <td><div class="value">{{ number_format($resumo['participacoes'], 0, ',', '.') }}</div><div class="label">Participações</div></td>
            <td><div class="value">{{ gmdate('H:i:s', max(0, (int) $resumo['tempo_total_segundos'])) }}</div><div class="label">Tempo total</div></td>
            <td><div class="value">{{ number_format($resumo['certificados']['total'], 0, ',', '.') }}</div><div class="label">Certificados</div></td>
            <td><div class="value {{ $resumo['certificados']['vencidos'] > 0 ? 'bad' : 'ok' }}">{{ number_format($resumo['certificados']['vencidos'], 0, ',', '.') }}</div><div class="label">Certif. vencidos</div></td>
        </tr>
    </table>

    <div class="section-title">Ausências justificadas no período</div>
    <table class="kpi">
        <tr>
            <td><div class="value">{{ number_format($ausencias['totais']['ferias_pessoas'], 0, ',', '.') }}</div><div class="label">Pessoas em férias</div></td>
            <td><div class="value">{{ number_format($ausencias['totais']['ferias_dias'], 0, ',', '.') }}</div><div class="label">Dias de férias</div></td>
            <td><div class="value">{{ number_format($ausencias['totais']['folgas_dias'], 0, ',', '.') }}</div><div class="label">Dias de folga</div></td>
            <td><div class="value">{{ number_format($ausencias['totais']['atestados_dias'], 0, ',', '.') }}</div><div class="label">Dias de atestado</div></td>
            <td><div class="value">{{ number_format($ausencias['totais']['licencas_dias'], 0, ',', '.') }}</div><div class="label">Dias de licença</div></td>
            <td><div class="value">{{ number_format($ausencias['totais']['isencoes_total'], 0, ',', '.') }}</div><div class="label">Isenções de conteúdo</div></td>
            <td><div class="value">{{ number_format($ausencias['totais']['prorrogacoes_total'], 0, ',', '.') }}</div><div class="label">Prazos estendidos</div></td>
        </tr>
    </table>

    <div class="section-title">Conformidade por conteúdo</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 30%;">Conteúdo</th>
                <th>Elegíveis</th>
                <th>Em dia</th>
                <th>Atraso</th>
                <th>Andamento</th>
                <th>Não in.</th>
                <th>Vencidos</th>
                <th>Isentos</th>
                <th>Afast. liberação</th>
                <th>Conformidade</th>
            </tr>
        </thead>
        <tbody>
            @forelse($conteudos->take(30) as $linha)
                <tr>
                    <td>
                        <strong>{{ $linha['training']->titulo }}</strong>
                        <span class="muted">· {{ ucfirst($linha['training']->tipo) }}@if($linha['training']->obrigatorio) · Obrigatório @endif · Liberação {{ optional($linha['training']->data_liberacao_exibicao)->format('d/m/Y') ?? '—' }}</span>
                    </td>
                    <td class="center">{{ number_format($linha['stats']['elegiveis'], 0, ',', '.') }}</td>
                    <td class="center ok">{{ number_format($linha['stats']['em_dia'], 0, ',', '.') }}</td>
                    <td class="center warn">{{ number_format($linha['stats']['atraso'], 0, ',', '.') }}</td>
                    <td class="center">{{ number_format($linha['stats']['andamento'], 0, ',', '.') }}</td>
                    <td class="center">{{ number_format($linha['stats']['nao_iniciados'], 0, ',', '.') }}</td>
                    <td class="center bad">{{ number_format($linha['stats']['vencidos'], 0, ',', '.') }}</td>
                    <td class="center">{{ number_format($linha['stats']['isentos'], 0, ',', '.') }}</td>
                    <td class="center">{{ number_format($linha['stats']['afastados_liberacao'], 0, ',', '.') }}</td>
                    <td class="center {{ $linha['stats']['conformidade'] >= 80 ? 'ok' : ($linha['stats']['conformidade'] >= 50 ? 'warn' : 'bad') }}">{{ number_format($linha['stats']['conformidade'], 1, ',', '.') }}%</td>
                </tr>
            @empty
                <tr><td colspan="10" class="center">Nenhum conteúdo no filtro atual.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($conteudos->count() > 30)
        <div class="filters">Exibindo os 30 conteúdos com mais elegíveis. O CSV detalhado contém a visão completa.</div>
    @endif

    <div class="section-title">Ausências justificadas — detalhamento</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 22%;">Usuário</th>
                <th style="width: 14%;">Tipo</th>
                <th>Início</th>
                <th>Fim</th>
                <th>Dias</th>
                <th>Motivo</th>
                <th>Autorizado por</th>
                <th style="width: 24%;">Observação</th>
            </tr>
        </thead>
        <tbody>
            @foreach($ausencias['ferias']->take(20) as $ferias)
                <tr>
                    <td>{{ optional($ferias->user)->nome ?? '—' }}<span class="muted"> · {{ optional($ferias->user)?->getCpfFormatted() ?? '' }}</span></td>
                    <td class="center">Férias</td>
                    <td class="center">{{ $ferias->data_inicio->format('d/m/Y') }}</td>
                    <td class="center">{{ $ferias->data_fim->format('d/m/Y') }}</td>
                    <td class="center">{{ $ferias->dias_periodo }}</td>
                    <td>—</td>
                    <td>—</td>
                    <td>Histórico de férias (user_vacations)</td>
                </tr>
            @endforeach
            @foreach($ausencias['folgas']->take(20) as $folga)
                <tr>
                    <td>{{ optional($folga->user)->nome ?? '—' }}<span class="muted"> · {{ optional($folga->user)?->getCpfFormatted() ?? '' }}</span></td>
                    <td class="center">{{ ['folga' => 'Folga', 'atestado' => 'Atestado', 'licenca' => 'Licença'][$folga->tipo] ?? $folga->tipo }}</td>
                    <td class="center">{{ $folga->data->format('d/m/Y') }}</td>
                    <td class="center">{{ $folga->data->format('d/m/Y') }}</td>
                    <td class="center">1</td>
                    <td>{{ $folga->motivo_folga ?? '—' }}</td>
                    <td>{{ optional($folga->lancadoPor)->nome ?? '—' }}</td>
                    <td>{{ \Illuminate\Support\Str::limit((string) ($folga->observacao ?? '—'), 80) }}</td>
                </tr>
            @endforeach
            @foreach($ausencias['isencoes']->take(10) as $isencao)
                <tr>
                    <td>{{ optional($isencao->user)->nome ?? '—' }}</td>
                    <td class="center">Isenção</td>
                    <td class="center">{{ $isencao->data_inicio->format('d/m/Y') }}</td>
                    <td class="center">{{ $isencao->data_fim->format('d/m/Y') }}</td>
                    <td class="center">{{ (int) $isencao->data_inicio->diffInDays($isencao->data_fim) + 1 }}</td>
                    <td>{{ $isencao->motivo ?? '—' }}</td>
                    <td>{{ optional($isencao->creator)->nome ?? '—' }}</td>
                    <td>Conteúdo: {{ optional($isencao->training)->titulo }}</td>
                </tr>
            @endforeach
            @foreach($ausencias['prorrogacoes']->take(10) as $prorrogacao)
                <tr>
                    <td>{{ optional($prorrogacao->user)->nome ?? '—' }}</td>
                    <td class="center">Prazo estendido</td>
                    <td class="center">{{ optional($prorrogacao->prazo_estendido_em)->format('d/m/Y') ?? '—' }}</td>
                    <td class="center">{{ optional($prorrogacao->prazo_estendido_ate)->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="center">—</td>
                    <td>{{ $prorrogacao->prazo_estendido_motivo ?? '—' }}</td>
                    <td>{{ optional(\App\Models\User::find($prorrogacao->prazo_estendido_por))->nome ?? '—' }}</td>
                    <td>Conteúdo: {{ optional($prorrogacao->training)->titulo }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
    <div class="filters">Lista resumida — as listas completas (férias, folgas, isenções e prorrogações) constam no CSV de ausências com o mesmo recorte.</div>

    @if($trilha)
        <div class="section-title">Trilha de auditoria (ações no sistema no período)</div>
        <table class="kpi">
            <tr>
                <td><div class="value">{{ number_format($trilha['total'], 0, ',', '.') }}</div><div class="label">Eventos no período</div></td>
                @foreach($trilha['por_evento']->take(5) as $evento => $total)
                    <td><div class="value">{{ number_format($total, 0, ',', '.') }}</div><div class="label">{{ ucfirst(str_replace('_', ' ', (string) $evento)) }}</div></td>
                @endforeach
            </tr>
        </table>
    @endif

    <div class="footer">
        {{ plataforma_nome() }} — documento gerado automaticamente para fins gerenciais e de auditoria ·
        SHA-256 do recorte de dados: {{ $hashRecorte }} · {{ $geradoEm->format('d/m/Y H:i:s') }} ·
        Base: exclui somente super_admin e usuários de teste
    </div>
</body>
</html>
