<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: Helvetica, Arial, sans-serif; font-size: 7.5px; color: #1f2937; padding: 4px; }
        .title { font-size: 15px; font-weight: bold; color: #064e3b; }
        .subtitle { font-size: 8px; color: #6b7280; margin-top: 2px; }
        .meta-table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        .meta-table td { font-size: 7px; color: #374151; padding: 2px 4px; vertical-align: top; }
        .meta-label { font-weight: bold; color: #111827; width: 85px; }
        .filters { font-size: 7px; color: #374151; margin-top: 2px; }
        .section-title { font-size: 9.5px; font-weight: bold; color: #111827; border-bottom: 1.5px solid #065f46; padding-bottom: 2px; margin-top: 10px; margin-bottom: 4px; }
        table.data { width: 100%; border-collapse: collapse; border: 1px solid #d1d5db; margin-top: 3px; }
        table.data thead { display: table-header-group; }
        table.data tr { page-break-inside: avoid; }
        table.data th { background-color: #065f46; color: #ffffff; font-size: 6.5px; font-weight: bold; padding: 3px 2px; border: 1px solid #a7b8b2; text-align: center; }
        table.data td { font-size: 6.5px; padding: 2.5px 3px; border-bottom: 1px solid #e5e7eb; vertical-align: top; }
        table.data tr:nth-child(even) td { background-color: #f9fafb; }
        .center { text-align: center; }
        .right { text-align: right; }
        .muted { color: #6b7280; }
        .empty { text-align: center; color: #6b7280; padding: 8px; font-size: 7px; }
        .kpi { width: 100%; border-collapse: collapse; margin-top: 4px; }
        .kpi td { border: 1px solid #d1d5db; padding: 4px; text-align: center; }
        .kpi .value { font-size: 12px; font-weight: bold; color: #111827; }
        .kpi .label { font-size: 6.5px; color: #6b7280; text-transform: uppercase; }
        .badge-ok { color: #047857; font-weight: bold; }
        .badge-warn { color: #b45309; font-weight: bold; }
        .badge-bad { color: #b91c1c; font-weight: bold; }
        .kpi .value.badge-ok { color: #047857; }
        .kpi .value.badge-warn { color: #b45309; }
        .kpi .value.badge-bad { color: #b91c1c; }
        .note { border: 1px solid #d1d5db; background-color: #f9fafb; padding: 4px 6px; margin-top: 6px; font-size: 6.5px; color: #374151; }
        .footer { margin-top: 10px; padding-top: 4px; border-top: 1px solid #d1d5db; font-size: 6px; color: #6b7280; text-align: center; }
    </style>
</head>
<body>
    <div class="title">{{ plataforma_nome() }} — {{ $titulo }}</div>
    <div class="subtitle">Módulo de Saúde e Segurança (EPI) · Versão {{ app_version() }}</div>

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
                @if (empty($filtrosAtivos))
                    Nenhum filtro aplicado (base completa)
                @else
                    @foreach ($filtrosAtivos as $rotulo => $valor)
                        {{ $rotulo }}: {{ $valor }}@if (!$loop->last) · @endif
                    @endforeach
                @endif
            </td>
        </tr>
    </table>

    @yield('conteudo')

    <div class="footer">
        {{ plataforma_nome() }} · Relatório gerado pelo módulo de EPI em {{ $geradoEm->format('d/m/Y H:i') }}
    </div>
</body>
</html>
