<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Projeto Pedagógico - {{ $training->titulo }}</title>
    <style>
        body { font-family: helvetica, sans-serif; font-size: 10pt; color: #1f2937; }
        h1 { font-size: 16pt; color: #153B2E; border-bottom: 2px solid #F28C2B; padding-bottom: 6px; }
        h2 { font-size: 11pt; color: #153B2E; margin-top: 14px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        .header { text-align: center; margin-bottom: 12px; }
        .header .logo { font-size: 14pt; font-weight: bold; color: #153B2E; }
        .header .sub { font-size: 9pt; color: #6b7280; }
        .meta { width: 100%; border-collapse: collapse; margin: 10px 0; font-size: 9pt; }
        .meta td { border: 1px solid #e5e7eb; padding: 4px 6px; }
        .meta .label { background: #f3f4f6; font-weight: bold; width: 35%; }
        .item { margin-bottom: 8px; }
        .item .titulo { font-weight: bold; color: #153B2E; }
        .item .conteudo { color: #374151; }
        .rodape { margin-top: 30px; font-size: 9pt; color: #6b7280; text-align: center; border-top: 1px solid #e5e7eb; padding-top: 8px; }
        .assinatura { margin-top: 40px; }
        .assinatura .linha { border-top: 1px solid #374151; width: 260px; padding-top: 4px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="logo">PLATAFORMA DSS</div>
        <div class="sub">Treinamentos em Segurança e Saúde no Trabalho</div>
    </div>

    <h1>PROJETO PEDAGÓGICO</h1>

    <table class="meta">
        <tr>
            <td class="label">Treinamento</td>
            <td>{{ $training->titulo }}</td>
            <td class="label">Versão</td>
            <td>{{ $pp->versao ?? '1.0' }}</td>
        </tr>
        <tr>
            <td class="label">Tipo</td>
            <td>{{ $training->tipo === 'treinamento' ? 'Treinamento' : 'DSS' }} {{ $training->tipo_treinamento ? '(' . ucfirst($training->tipo_treinamento) . ')' : '' }}</td>
            <td class="label">Carga horária</td>
            <td>{{ $training->carga_horaria }} minutos</td>
        </tr>
        <tr>
            <td class="label">Data de validação</td>
            <td>{{ $pp->data_validacao?->format('d/m/Y') ?? '—' }}</td>
            <td class="label">Próxima revisão</td>
            <td>{{ $pp->data_proxima_revisao?->format('d/m/Y') ?? '—' }}</td>
        </tr>
        <tr>
            <td class="label">Responsável técnico</td>
            <td>{{ $pp->responsavel_tecnico_nome ?? '—' }}</td>
            <td class="label">Qualificação</td>
            <td>{{ $pp->responsavel_tecnico_qualificacao ?? '—' }}</td>
        </tr>
    </table>

    <p><em>Projeto pedagógico elaborado em conformidade com o Anexo II da NR-01 (Portaria MTE 6.730/2020), item 3.1.</em></p>

    <h2>1. Objetivo geral da capacitação (3.1a)</h2>
    <div class="item"><div class="conteudo">{{ $pp->objetivo_geral ?? '—' }}</div></div>

    <h2>2. Princípios e conceitos de proteção à SST (3.1b)</h2>
    <div class="item"><div class="conteudo">{{ $pp->principios_sst ?? '—' }}</div></div>

    <h2>3. Estratégia pedagógica (3.1c)</h2>
    <div class="item"><div class="conteudo">{{ $pp->estrategia_pedagogica ?? '—' }}</div></div>

    <h2>4. Responsável técnico pela capacitação (3.1d)</h2>
    <div class="item"><div class="conteudo">{{ $pp->responsavel_tecnico_nome ?? '—' }} — {{ $pp->responsavel_tecnico_qualificacao ?? '—' }}</div></div>

    <h2>5. Relação de instrutores (3.1e)</h2>
    <div class="item"><div class="conteudo">{{ $pp->instrutores ?? '—' }}</div></div>

    <h2>6. Infraestrutura operacional de apoio e controle (3.1f)</h2>
    <div class="item"><div class="conteudo">{{ $pp->infraestrutura_operacional ?? '—' }}</div></div>

    <h2>7. Conteúdo programático teórico e prático (3.1g)</h2>
    <div class="item"><div class="conteudo">{{ $pp->conteudo_programatico_pp ?? '—' }}</div></div>

    <h2>8. Objetivo de cada módulo (3.1h)</h2>
    <div class="item"><div class="conteudo">{{ $pp->objetivo_modulos ?? '—' }}</div></div>

    <h2>9. Carga horária (3.1i)</h2>
    <div class="item"><div class="conteudo">{{ $pp->carga_horaria_pp ?? $training->carga_horaria . ' minutos' }}</div></div>

    <h2>10. Estimativa de tempo mínimo de dedicação diária (3.1j)</h2>
    <div class="item"><div class="conteudo">{{ $pp->tempo_minimo_diario ?? '—' }}</div></div>

    <h2>11. Prazo máximo para conclusão da capacitação (3.1k)</h2>
    <div class="item"><div class="conteudo">{{ $pp->prazo_maximo_conclusao ?? '—' }}</div></div>

    <h2>12. Público-alvo (3.1l)</h2>
    <div class="item"><div class="conteudo">{{ $pp->publico_alvo ?? '—' }}</div></div>

    <h2>13. Material didático (3.1m)</h2>
    <div class="item"><div class="conteudo">{{ $pp->material_didatico ?? '—' }}</div></div>

    <h2>14. Instrumentos para potencialização do aprendizado (3.1n)</h2>
    <div class="item"><div class="conteudo">{{ $pp->instrumentos_aprendizado ?? '—' }}</div></div>

    <h2>15. Avaliação de aprendizagem (3.1o)</h2>
    <div class="item"><div class="conteudo">{{ $pp->avaliacao_aprendizagem ?? '—' }}</div></div>

    <div class="assinatura">
        <div style="width: 50%; float: left;">
            @if($pp->assinatura_rt)
                <img src="{{ $pp->assinatura_rt }}" style="height: 55px; max-width: 240px; margin-bottom: 2px;" alt="Assinatura do responsável técnico">
            @endif
            <div class="linha" style="width: 240px;">Responsável técnico pela capacitação</div>
            <div style="text-align:center; font-size: 9pt;">
                {{ $pp->assinatura_rt_nome ?? $pp->responsavel_tecnico_nome ?? '' }}<br>{{ $pp->responsavel_tecnico_qualificacao ?? '' }}
                @if($pp->assinatura_rt_data)
                    <br>Assinado em: {{ $pp->assinatura_rt_data->format('d/m/Y H:i') }}
                @endif
            </div>
        </div>
        <div style="width: 50%; float: right;">
            <div class="linha">Validação do projeto pedagógico</div>
            <div style="text-align:center; font-size: 9pt;">{{ $pp->data_validacao?->format('d/m/Y') ?? '' }}<br>Revisão: {{ $pp->data_proxima_revisao?->format('d/m/Y') ?? '' }}</div>
        </div>
        <div style="clear: both;"></div>
    </div>

    <div class="rodape">
        Documento gerado eletronicamente pela Plataforma DSS em {{ now()->format('d/m/Y H:i') }} — válido conforme NR-01 Anexo II, item 4.1.
    </div>
</body>
</html>