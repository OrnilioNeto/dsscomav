<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Projeto Pedagógico</title>
    <style>
        body { font-family: helvetica, sans-serif; font-size: 10pt; color: #1f2937; line-height: 1.4; }
        h1 { font-size: 15pt; color: #153B2E; text-align: center; letter-spacing: 1px; margin: 0 0 2px 0; }
        .subtitulo { font-size: 9pt; color: #6b7280; text-align: center; margin-bottom: 10px; }
        .barra { border-bottom: 3px solid #F28C2B; margin: 0 0 12px 0; }
        h2 { font-size: 10.5pt; color: #153B2E; margin-top: 14px; border-bottom: 1px solid #e5e7eb; padding-bottom: 3px; }
        .cabecalho { border: 1px solid #d1d5db; border-left: 5px solid #153B2E; border-radius: 4px; padding: 8px 10px; margin-bottom: 12px; }
        table.meta { width: 100%; border-collapse: collapse; font-size: 9pt; }
        table.meta td { border: 1px solid #e5e7eb; padding: 5px 7px; }
        table.meta .label { background: #f3f4f6; font-weight: bold; width: 22%; color: #153B2E; }
        .item .conteudo { color: #374151; }
        .nota { font-size: 8.5pt; color: #6b7280; font-style: italic; margin: 8px 0 12px 0; }
    </style>
</head>
<body>
    <h1>PROJETO PEDAGÓGICO</h1>
    <div class="subtitulo">Capacitações em Segurança e Saúde no Trabalho — Plataforma DSS</div>
    <div class="barra"></div>

    <div class="cabecalho">
        <table class="meta">
            <tr>
                <td class="label">Treinamentos atendidos</td>
                <td colspan="3">
                    @forelse($treinamentos as $treinamento)
                        <strong>{{ $treinamento->titulo }}</strong>
                        @if($treinamento->tipo_treinamento) ({{ ucfirst($treinamento->tipo_treinamento) }}) @endif
                        — {{ $treinamento->carga_horaria }} minutos
                        @if(!$loop->last)<br>@endif
                    @empty
                        —
                    @endforelse
                </td>
            </tr>
            <tr>
                <td class="label">Versão</td>
                <td>{{ $pp->versao ?? '1.0' }}</td>
                <td class="label">Data de validação</td>
                <td>{{ $pp->data_validacao?->format('d/m/Y') ?? '—' }}</td>
            </tr>
            <tr>
                <td class="label">Responsável técnico</td>
                <td>{{ $pp->responsavel_tecnico_nome ?? '—' }}</td>
                <td class="label">Próxima revisão</td>
                <td>{{ $pp->data_proxima_revisao?->format('d/m/Y') ?? '—' }}</td>
            </tr>
        </table>
    </div>

    <p class="nota">Projeto pedagógico elaborado em conformidade com o Anexo II da NR-01 (Portaria MTE nº 6.730/2020), item 3.1, e revisado conforme o item 3.3.</p>

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
    <div class="item"><div class="conteudo">{{ $pp->carga_horaria_pp ?? '—' }}</div></div>

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
</body>
</html>