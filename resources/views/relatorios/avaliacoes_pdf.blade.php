<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 7.5px; color: #1f2937; padding: 6px; }
        .title { font-size: 15px; font-weight: bold; color: #1e3a8a; }
        .subtitle { font-size: 8px; color: #6b7280; margin-top: 2px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        .meta-table td { font-size: 7px; color: #374151; padding: 2px 4px; vertical-align: top; }
        .meta-label { font-weight: bold; color: #111827; width: 90px; }
        .box-note { border: 1px solid #c7d2fe; background: #eef2ff; padding: 5px 7px; margin-top: 6px; font-size: 7px; color: #312e81; }
        .section-title { font-size: 9px; font-weight: bold; color: #111827; border-bottom: 1.5px solid #1e3a8a; padding-bottom: 2px; margin-top: 10px; margin-bottom: 4px; }
        table.data { width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; margin-top: 3px; }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        table.data th { background: #1e3a8a; color: #ffffff; font-size: 6.5px; font-weight: bold; padding: 3px 2px; border: 1px solid #93a3c4; text-align: center; }
        table.data td { font-size: 6.5px; padding: 2.5px 3px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        table.data tr:nth-child(even) td { background: #f9fafb; }
        .center { text-align: center; }
        .kpi td { border: 1px solid #d1d5db; padding: 5px; text-align: center; width: 25%; }
        .kpi .value { font-size: 13px; font-weight: bold; color: #111827; }
        .kpi .label { font-size: 6.5px; color: #6b7280; text-transform: uppercase; }
        .badge-ok { color: #047857; font-weight: bold; }
        .badge-bad { color: #b91c1c; font-weight: bold; }
        .badge-mid { color: #b45309; font-weight: bold; }
        .footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #d1d5db; font-size: 6px; color: #6b7280; text-align: center; }
        .filters { font-size: 6.5px; color: #374151; margin-top: 3px; }
    </style>
</head>
<body>
    <div class="title">{{ plataforma_nome() }} — Relatório de Avaliações e Notas</div>
    <div class="subtitle">Avaliações, notas, tentativas, datas/horas e certificados por usuário e treinamento · Versão {{ app_version() }}</div>

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
        <strong>Base de cálculo:</strong> este relatório desconsidera apenas super_admin e usuários marcados como teste
        ({{ (int) ($metricasExclusao['total'] ?? 0) }} cadastro(s) fora da base).
        As notas seguem a escala 0–100 e o critério mínimo de aprovação de cada treinamento.
        O histórico de tentativas (data/hora de cada submissão) está disponível no CSV de tentativas.
    </div>

    <div class="section-title">Indicadores do recorte</div>
    <table class="kpi">
        <tr>
            <td>
                <div class="value">{{ number_format($resumo['total_resultados'], 0, ',', '.') }}</div>
                <div class="label">Resultados</div>
            </td>
            <td>
                <div class="value badge-ok">{{ number_format($resumo['taxa_aprovacao'], 1, ',', '.') }}%</div>
                <div class="label">Taxa de aprovação</div>
            </td>
            <td>
                <div class="value">{{ $resumo['media_notas'] !== null ? number_format($resumo['media_notas'], 1, ',', '.') : '—' }}</div>
                <div class="label">Média das notas</div>
            </td>
            <td>
                <div class="value">{{ number_format($resumo['tentativas_registradas'], 0, ',', '.') }}</div>
                <div class="label">Tentativas registradas</div>
            </td>
        </tr>
        <tr>
            <td>
                <div class="value badge-ok">{{ number_format($resumo['total_aprovados'], 0, ',', '.') }}</div>
                <div class="label">Aprovados</div>
            </td>
            <td>
                <div class="value badge-bad">{{ number_format($resumo['total_reprovados'], 0, ',', '.') }}</div>
                <div class="label">Reprovados</div>
            </td>
            <td>
                <div class="value badge-mid">{{ number_format($resumo['total_sem_nota'], 0, ',', '.') }}</div>
                <div class="label">Sem nota</div>
            </td>
            <td>
                <div class="value">{{ gmdate('H:i:s', max(0, (int) $resumo['tempo_total_segundos'])) }}</div>
                <div class="label">Tempo assistido</div>
            </td>
        </tr>
    </table>

    <div class="section-title">Desempenho por treinamento</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 42%;">Treinamento</th>
                <th>Participações</th>
                <th>Com nota</th>
                <th>Aprovados</th>
                <th>Média</th>
                <th>Taxa de aprovação</th>
            </tr>
        </thead>
        <tbody>
            @forelse($desempenhoPorTreinamento->take(25) as $linha)
                <tr>
                    <td>
                        <strong>{{ $linha['training']->titulo }}</strong>
                        <span style="color:#6b7280;">· {{ ucfirst($linha['training']->tipo) }}@if($linha['training']->tipo_treinamento) {{ $linha['training']->tipo_treinamento_label }}@endif · mín. {{ (int) ($linha['training']->nota_minima_aprovacao ?? 70) }}%</span>
                    </td>
                    <td class="center">{{ number_format($linha['total'], 0, ',', '.') }}</td>
                    <td class="center">{{ number_format($linha['com_nota'], 0, ',', '.') }}</td>
                    <td class="center badge-ok">{{ number_format($linha['aprovados'], 0, ',', '.') }}</td>
                    <td class="center">{{ $linha['media'] !== null ? number_format($linha['media'], 1, ',', '.') : '—' }}</td>
                    <td class="center">{{ number_format($linha['taxa_aprovacao'], 1, ',', '.') }}%</td>
                </tr>
            @empty
                <tr><td colspan="6" class="center">Nenhum resultado para o filtro atual.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($desempenhoPorTreinamento->count() > 25)
        <div class="filters">Exibindo os 25 conteúdos com mais notas registradas. O CSV contém a visão completa.</div>
    @endif

    <div class="section-title">Resultados por usuário × treinamento</div>
    <table class="data">
        <thead>
            <tr>
                <th style="width: 20%;">Usuário</th>
                <th style="width: 22%;">Treinamento</th>
                <th>Nota</th>
                <th>Resultado</th>
                <th>Tent.</th>
                <th>Início</th>
                <th>Conclusão</th>
                <th>Tempo</th>
                <th style="width: 14%;">Certificado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($resultados as $progresso)
                @php
                    $certificado = $progresso->certificado_anexado ?? null;
                    $aprovado = (bool) $progresso->avaliacao_aprovada;
                    $temNota = $progresso->avaliacao_nota !== null;
                @endphp
                <tr>
                    <td>
                        <strong>{{ optional($progresso->user)->nome ?? '—' }}</strong><br>
                        <span style="color:#6b7280;">{{ optional($progresso->user)?->getCpfFormatted() ?? '' }} · {{ tipo_usuario_label(optional($progresso->user)->tipo_usuario) }}</span>
                    </td>
                    <td>
                        {{ optional($progresso->training)->titulo ?? '—' }}<br>
                        <span style="color:#6b7280;">{{ ucfirst(optional($progresso->training)->tipo ?? '') }}</span>
                    </td>
                    <td class="center {{ $temNota ? ($aprovado ? 'badge-ok' : 'badge-bad') : '' }}">
                        {{ $temNota ? $progresso->avaliacao_nota.'%' : '—' }}
                    </td>
                    <td class="center {{ $aprovado ? 'badge-ok' : ($temNota ? 'badge-bad' : '') }}">
                        {{ $aprovado ? 'Aprovado' : ($temNota ? 'Reprovado' : 'Pendente') }}
                    </td>
                    <td class="center">{{ (int) ($progresso->avaliacao_tentativas ?? 0) }}</td>
                    <td class="center">{{ optional($progresso->data_inicio)->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="center">{{ optional($progresso->data_conclusao)->format('d/m/Y H:i') ?? '—' }}</td>
                    <td class="center" style="font-family: Courier, monospace;">{{ gmdate('H:i:s', (int) ($progresso->tempo_assistido ?? 0)) }}</td>
                    <td class="center">
                        @if($certificado)
                            {{ $certificado->codigo_certificado }}<br>
                            <span style="color:#6b7280;">{{ optional($certificado->data_emissao)->format('d/m/Y') }}
                                @php $sv = $certificado->status_validade; @endphp
                                · {{ ['valido' => 'Válido', 'vencendo' => 'Vencendo', 'vencido' => 'Vencido', 'sem_validade' => 'Sem validade'][$sv] ?? '—' }}
                            </span>
                        @else
                            —
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="9" class="center">Nenhum resultado encontrado.</td></tr>
            @endforelse
        </tbody>
    </table>
    @if($resultados->count() >= 1500)
        <div class="filters">Exibindo os primeiros 1.500 registros. Exporte o CSV para a base completa.</div>
    @endif

    <div class="footer">
        {{ plataforma_nome() }} — documento gerado automaticamente para fins gerenciais e de auditoria ·
        SHA-256 do recorte de dados: {{ $hashRecorte }} · {{ $geradoEm->format('d/m/Y H:i:s') }}
    </div>
</body>
</html>
