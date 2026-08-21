@extends('layout')

@section('content')
<div class="container-fluid" style="padding: 30px 20px;">
    <!-- Header -->
    <div style="margin-bottom: 25px;">
        <h1 style="margin: 0; font-size: 2rem; color: #1a1a1a; font-weight: 600;">
            <i class="fas fa-chart-line" style="margin-right: 12px; color: #5B21B6;"></i>Relatório Analítico de Treinamento
        </h1>
        <p style="margin: 8px 0 0; color: #666; font-size: 0.95rem;">Análise completa de cobertura, desempenho na avaliação e parecer executivo com IA</p>
    </div>

    <!-- Main Card -->
    <div class="card" style="border: none; box-shadow: 0 2px 8px rgba(0,0,0,0.1); border-radius: 12px; overflow: hidden;">
        <!-- Card Header Com Seleção -->
        <div style="background: linear-gradient(135deg, #5B21B6 0%, #7C3AED 100%); padding: 25px; color: white;">
            <div class="form-group" style="margin: 0;">
                <label style="font-weight: 600; font-size: 1rem; margin-bottom: 12px; display: block;">
                    <i class="fas fa-book" style="margin-right: 8px;"></i>Selecione um Treinamento
                </label>
                <select id="training_id" class="form-control" style="border-radius: 8px; border: 2px solid rgba(255,255,255,0.3); background: white; color: #333; padding: 12px; font-size: 1rem; transition: all 0.3s;">
                    <option value="">-- Selecione um treinamento --</option>
                    @foreach($treinamentos as $t)
                        <option value="{{ $t->id }}">{{ $t->titulo }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body" style="padding: 30px;">
            <!-- Buttons -->
            <div class="no-print" style="display: flex; gap: 12px; margin-bottom: 25px; flex-wrap: wrap;">
                <button type="button" id="localBtn" class="btn" style="background-color: #059669; border: none; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px;" onmouseover="this.style.backgroundColor='#047857'" onmouseout="this.style.backgroundColor='#059669'">
                    <i class="fas fa-bolt"></i>Gerar Relatório
                </button>
                <button type="button" id="aiBtn" class="btn" style="background-color: #5B21B6; border: none; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px;" onmouseover="this.style.backgroundColor='#7C3AED'" onmouseout="this.style.backgroundColor='#5B21B6'">
                    <i class="fas fa-brain"></i>Gerar com Parecer de IA
                </button>
                <button type="button" id="pdfBtn" class="btn" style="background-color: #334155; border: none; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px; opacity: 0.6;" onmouseover="this.style.backgroundColor='#475569'" onmouseout="this.style.backgroundColor='#334155'" disabled>
                    <i class="fas fa-file-pdf"></i>Exportar PDF
                </button>
                <button type="button" id="printBtn" class="btn" style="background-color: #475569; border: none; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px; opacity: 0.6;" onmouseover="this.style.backgroundColor='#64748B'" onmouseout="this.style.backgroundColor='#475569'" disabled>
                    <i class="fas fa-print"></i>Imprimir
                </button>
                <button type="button" id="txtBtn" class="btn" style="background-color: #0e7490; border: none; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px; opacity: 0.6;" onmouseover="this.style.backgroundColor='#0891b2'" onmouseout="this.style.backgroundColor='#0e7490'" disabled>
                    <i class="fas fa-file-alt"></i>Gerar TXT
                </button>
            </div>

            <!-- Status & Result Area -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; border-left: 4px solid #5B21B6;">
                <div id="statusBadge" style="margin-bottom: 12px; font-size: 0.9rem; color: #666; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle" style="color: #5B21B6;"></i>
                    Selecione um treinamento e clique em "Gerar Relatório"
                </div>

                <!-- ================= RELATÓRIO ================= -->
                <div id="reportArea" style="display: none;">

                    <!-- Cabeçalho do relatório -->
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 22px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: flex-start; flex-wrap: wrap; gap: 10px;">
                            <div>
                                <div style="font-size: 0.75rem; text-transform: uppercase; letter-spacing: 0.08em; color: #7c3aed; font-weight: 700; margin-bottom: 4px;">
                                    <i class="fas fa-file-alt" style="margin-right: 6px;"></i>Relatório Analítico
                                </div>
                                <h2 id="repTitulo" style="margin: 0; font-size: 1.5rem; font-weight: 700; color: #111827;"></h2>
                                <div id="repMeta" style="margin-top: 6px; font-size: 0.85rem; color: #6b7280; display: flex; flex-wrap: wrap; gap: 8px 14px;"></div>
                            </div>
                            <div id="sourceBadge" style="padding: 6px 14px; border-radius: 999px; font-size: 0.78rem; font-weight: 700;"></div>
                        </div>
                    </div>

                    <!-- KPI Cards -->
                    <div id="kpiGrid" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(170px, 1fr)); gap: 14px; margin-bottom: 20px;"></div>

                    <!-- Dados do treinamento -->
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                        <h4 style="margin: 0 0 14px; font-size: 1rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-info-circle" style="color: #7c3aed;"></i>Dados do Treinamento
                        </h4>
                        <div id="trainingMeta" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 10px 20px; font-size: 0.88rem;"></div>
                    </div>

                    <!-- Desempenho na avaliação -->
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-clipboard-check" style="color: #7c3aed;"></i>Desempenho na Avaliação
                            </h4>
                            <div id="capturaInfo" style="font-size: 0.78rem; color: #6b7280;"></div>
                        </div>
                        <div id="attemptCards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 14px;"></div>
                        <div id="attemptNote" style="display: none; background: #fffbeb; border-left: 4px solid #f59e0b; border-radius: 6px; padding: 12px 14px; font-size: 0.85rem; color: #92400e; margin-bottom: 14px;"></div>
                        <!-- Listas por grupo -->
                        <div id="groupTables"></div>
                    </div>

                    <!-- Não Concluíram -->
                    <div id="naoConclBox" style="display: none; background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; margin-bottom: 20px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 14px;">
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-user-xmark" style="color: #dc2626;"></i>Não Concluíram
                                <span id="naoConclCount" style="background: #fee2e2; color: #dc2626; font-size: 0.75rem; font-weight: 700; padding: 2px 10px; border-radius: 999px;"></span>
                            </h4>
                        </div>
                        <p style="font-size: 0.82rem; color: #6b7280; margin: 0 0 12px;">Usuários elegíveis que não obtiveram certificado. Usuários em férias são automaticamente excluídos desta lista.</p>
                        <div style="overflow-x: auto;">
                            <table class="table table-sm" style="font-size: 0.82rem; margin: 0;">
                                <thead><tr style="background: #f8fafc;">
                                    <th>Usuário</th><th>Tipo</th><th>CPF</th><th>Motivo</th>
                                </tr></thead>
                                <tbody id="naoConclBody"></tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Parecer executivo -->
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px; margin-bottom: 20px;" id="parecerBox">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px; margin-bottom: 12px;">
                            <h4 style="margin: 0; font-size: 1rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px;">
                                <i class="fas fa-comment-dots" style="color: #7c3aed;"></i>Parecer Executivo
                            </h4>
                            <div id="parecerSourceBadge"></div>
                        </div>
                        <div id="parecerBody" style="font-size: 0.92rem; line-height: 1.7; color: #1f2937;"></div>
                    </div>

                    <!-- Detalhamento por usuário -->
                    <div style="background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 20px;">
                        <h4 style="margin: 0 0 14px; font-size: 1rem; font-weight: 700; color: #111827; display: flex; align-items: center; gap: 8px;">
                            <i class="fas fa-users" style="color: #7c3aed;"></i>Detalhamento por Usuário
                            <span id="usuariosCount" style="background: #f3e8ff; color: #7c3aed; font-size: 0.75rem; font-weight: 700; padding: 2px 10px; border-radius: 999px;"></span>
                        </h4>
                        <div style="overflow-x: auto;">
                            <table class="table table-striped" style="font-size: 0.85rem; margin: 0;">
                                <thead>
                                    <tr style="background: #f8fafc;">
                                        <th>Usuário</th>
                                        <th>Tipo</th>
                                        <th>CPF</th>
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
                                <tbody id="usuariosTableBody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <!-- ================= FIM RELATÓRIO ================= -->

            </div>

            <!-- Info Box -->
            <div class="no-print" style="margin-top: 20px; padding: 15px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 6px; color: #1e40af; font-size: 0.9rem;">
                <i class="fas fa-lightbulb" style="margin-right: 8px;"></i>
                <strong>Dica:</strong> use "Gerar Relatório" para o relatório completo instantâneo ou "Gerar com Parecer de IA" para incluir a análise executiva gerada por inteligência artificial. O relatório pode ser exportado em PDF.
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    .no-print { display: none !important; }
    #statusBadge { display: none !important; }
    body { background: white; }
    .container-fluid { padding: 0 !important; }
    .card { box-shadow: none !important; border: none !important; }
    .card-body { padding: 0 !important; }
    #reportArea { display: block !important; }
    #reportArea .table { font-size: 10px !important; }
    #reportArea .table th, #reportArea .table td { padding: 3px 4px !important; white-space: nowrap; }
    #reportArea .table thead { display: table-header-group; }
    #reportArea .table tr { page-break-inside: avoid; }
    #kpiGrid, #trainingMeta, #attemptCards { grid-template-columns: repeat(3, 1fr) !important; }
}
</style>

<!-- Modal TXT -->
<div id="txtModal" style="display:none; position:fixed; inset:0; z-index:9999; background:rgba(0,0,0,0.6); padding:30px; overflow-y:auto;">
    <div style="max-width:700px; margin:0 auto; background:white; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.3); overflow:hidden;">
        <div style="background:linear-gradient(135deg,#0e7490,#0891b2); padding:20px 24px; color:white; display:flex; justify-content:space-between; align-items:center;">
            <h3 style="margin:0; font-size:1.1rem;"><i class="fas fa-file-alt" style="margin-right:8px;"></i>Resumo para E-mail</h3>
            <button onclick="closeTxtModal()" style="background:none; border:none; color:white; font-size:1.3rem; cursor:pointer;">&times;</button>
        </div>
        <div style="padding:20px 24px;">
            <p style="font-size:0.85rem; color:#6b7280; margin:0 0 12px;">Selecione o texto abaixo (Ctrl+A) e copie (Ctrl+C) para colar no e-mail.</p>
            <textarea id="txtContent" readonly style="width:100%; height:420px; border:1px solid #d1d5db; border-radius:8px; padding:14px; font-family:'Courier New',monospace; font-size:13px; line-height:1.6; color:#1f2937; resize:vertical; background:#f9fafb;"></textarea>
            <div style="margin-top:12px; display:flex; gap:10px;">
                <button onclick="copyTxt()" style="background:#0e7490; border:none; color:white; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-copy"></i>Copiar texto
                </button>
                <button onclick="downloadTxt()" style="background:#475569; border:none; color:white; padding:10px 20px; border-radius:8px; font-weight:600; cursor:pointer; display:flex; align-items:center; gap:6px;">
                    <i class="fas fa-download"></i>Baixar .txt
                </button>
            </div>
            <div id="copyOk" style="display:none; margin-top:8px; color:#059669; font-weight:600; font-size:0.85rem;">
                <i class="fas fa-check-circle"></i> Texto copiado com sucesso!
            </div>
        </div>
    </div>
</div>
@endsection


@section('extra_js')
<script>
const statusBadge = document.getElementById('statusBadge');
const localBtn = document.getElementById('localBtn');
const aiBtn = document.getElementById('aiBtn');
const pdfBtn = document.getElementById('pdfBtn');
const printBtn = document.getElementById('printBtn');
const trainingSelect = document.getElementById('training_id');
const reportArea = document.getElementById('reportArea');

let currentReport = null;
let currentParecer = null;
let currentSource = null;

const fmtData = (v) => v || '—';
const badgeCss = (bg, color) => `padding: 6px 14px; border-radius: 999px; font-size: 0.78rem; font-weight: 700; background: ${bg}; color: ${color};`;

const tipoLabel = (t) => ({motorista: 'Motorista', funcionario: 'Funcionário', terceirizado: 'Terceirizado'}[t] || (t ? t.charAt(0).toUpperCase() + t.slice(1) : '—'));
const tipoBadge = (t) => {
    const map = {
        motorista: {bg: '#dbeafe', color: '#1e40af'},
        funcionario: {bg: '#ecfdf5', color: '#059669'},
        terceirizado: {bg: '#f3f4f6', color: '#374151'},
    };
    const c = map[t] || {bg: '#f3f4f6', color: '#374151'};
    return `<span style="background:${c.bg}; color:${c.color}; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 999px; white-space: nowrap;">${tipoLabel(t)}</span>`;
};

function setRunning(message) {
    statusBadge.innerHTML = `<i class="fas fa-spinner fa-spin" style="color: #5B21B6;"></i>${message}`;
    localBtn.disabled = true;
    aiBtn.disabled = true;
    localBtn.style.opacity = '0.6';
    aiBtn.style.opacity = '0.6';
}

function setIdle(message, isError = false) {
    const icon = isError ? 'fa-exclamation-circle' : 'fa-check-circle';
    const color = isError ? '#dc2626' : '#059669';
    statusBadge.innerHTML = `<i class="fas ${icon}" style="color: ${color};"></i>${message}`;
    localBtn.disabled = false;
    aiBtn.disabled = false;
    localBtn.style.opacity = '1';
    aiBtn.style.opacity = '1';
}

async function postJson(url, payload, timeoutMs = 90000) {
    const controller = new AbortController();
    const timeout = setTimeout(() => controller.abort(), timeoutMs);
    try {
        const res = await fetch(url, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify(payload),
            signal: controller.signal
        });
        return await res.json();
    } finally {
        clearTimeout(timeout);
    }
}

function requireTraining() {
    const id = trainingSelect.value;
    if (!id) {
        setIdle('⚠️ Selecione um treinamento para gerar o relatório', true);
        return null;
    }
    return id;
}

async function loadReport(url, withParecer) {
    const id = requireTraining();
    if (!id) return;

    reportArea.style.display = 'none';
    setRunning(withParecer ? 'Solicitando análise da IA, aguarde até 90 segundos...' : 'Gerando relatório analítico...');

    try {
        const data = await postJson(url, {training_id: id}, withParecer ? 90000 : 45000);

        if (data.error && !data.report) {
            setIdle(data.error, true);
            return;
        }

        currentReport = data.report || null;
        currentParecer = data.ai_summary || null;
        currentSource = data.source || 'local';

        if (!currentReport) {
            setIdle('Nenhum dado retornado.', true);
            return;
        }

        renderReport(currentReport);
        if (currentParecer) {
            renderParecer(currentParecer, currentSource);
        } else {
            renderParecer(null, null);
        }

        pdfBtn.disabled = false;
        pdfBtn.style.opacity = '1';
        printBtn.disabled = false;
        printBtn.style.opacity = '1';
        txtBtn.disabled = false;
        txtBtn.style.opacity = '1';

        const msg = withParecer
            ? (currentSource === 'ai' ? '✓ Relatório e parecer de IA gerados com sucesso' : '✓ Relatório gerado (parecer local — IA indisponível)')
            : '✓ Relatório gerado com sucesso';
        setIdle(msg, withParecer && currentSource !== 'ai');
    } catch (error) {
        const abort = error.name === 'AbortError';
        setIdle(abort ? 'Tempo excedido na análise' : 'Erro ao gerar relatório', true);
    }
}

// ================= RENDER =================

function renderReport(r) {
    const t = r.training || {};
    const k = r.kpis || {};
    const a = r.avaliacao || {};

    document.getElementById('repTitulo').textContent = t.titulo || 'Treinamento';
    document.getElementById('repMeta').innerHTML =
        `<span><i class="fas fa-tag" style="margin-right: 5px;"></i>${fmtData(t.tipo_label)}${t.tipo_treinamento_label ? ' (' + t.tipo_treinamento_label + ')' : ''}</span>` +
        `<span><i class="fas fa-calendar-check" style="margin-right: 5px;"></i>Gerado em ${fmtData(r.relatorio_gerado_em)}</span>` +
        (a.captura_inicio ? `<span><i class="fas fa-history" style="margin-right: 5px;"></i>Captura de tentativas desde ${a.captura_inicio}</span>` : '');

    const sb = document.getElementById('sourceBadge');
    if (currentParecer && currentSource === 'ai') {
        sb.innerHTML = '<i class="fas fa-brain" style="margin-right: 6px;"></i>Parecer gerado por IA (Gemini)';
        sb.setAttribute('style', badgeCss('#f3e8ff', '#6d28d9'));
    } else if (currentParecer) {
        sb.innerHTML = '<i class="fas fa-cog" style="margin-right: 6px;"></i>Análise local';
        sb.setAttribute('style', badgeCss('#fef3c7', '#92400e'));
    } else {
        sb.innerHTML = '<i class="fas fa-chart-bar" style="margin-right: 6px;"></i>Relatório analítico';
        sb.setAttribute('style', badgeCss('#dbeafe', '#1e40af'));
    }

    // KPIs
    const kpis = [
        {label: 'Usuários ativos elegíveis', value: k.usuarios_ativos_total ?? 0, icon: 'fa-users', color: '#6366f1'},
        {label: 'Participantes com certificado', value: k.usuarios_com_certificado ?? 0, icon: 'fa-certificate', color: '#059669'},
        {label: 'Cobertura do público efetivo', value: (k.percentual_usuarios_ativos ?? 0) + '%', icon: 'fa-percent', color: (k.percentual_usuarios_ativos ?? 0) >= 75 ? '#059669' : '#dc2626'},
        {label: 'Progressos concluídos', value: k.concluidos ?? 0, icon: 'fa-check-double', color: '#3b82f6'},
        {label: 'Tempo médio assistido', value: k.avg_time_human || '00:00:00', icon: 'fa-clock', color: '#7c3aed'},
        {label: 'Dias médios p/ conclusão', value: k.avg_days_to_complete ?? '—', icon: 'fa-calendar-day', color: '#d97706'},
    ];
    document.getElementById('kpiGrid').innerHTML = kpis.map(kp => `
        <div style="background: white; border: 1px solid #e5e7eb; border-radius: 10px; padding: 16px; text-align: center; box-shadow: 0 1px 3px rgba(0,0,0,0.05);">
            <div style="font-size: 1.6rem; font-weight: 700; color: ${kp.color}; line-height: 1.2;">
                <i class="fas ${kp.icon}" style="font-size: 1rem; margin-right: 6px; opacity: 0.8;"></i>${kp.value}
            </div>
            <div style="font-size: 0.75rem; color: #6b7280; margin-top: 6px; font-weight: 600;">${kp.label}</div>
        </div>
    `).join('');

    // Metadados do treinamento
    const metas = [
        ['Carga horária', t.carga_horaria ? (t.carga_horaria >= 60 ? Math.floor(t.carga_horaria / 60) + 'h ' + (t.carga_horaria % 60 ? (t.carga_horaria % 60) + 'min' : '') : t.carga_horaria + ' min') : '—'],
        ['Validade', t.dias_validade ? t.dias_validade + ' dias' : 'Sem validade'],
        ['Nota mínima p/ aprovação', t.nota_minima_aprovacao ? t.nota_minima_aprovacao + '%' : '—'],
        ['Questões (prova / banco)', (t.quantidade_questoes_prova ?? 0) + ' / ' + (t.total_questoes_banco ?? 0)],
        ['Público-alvo', fmtData(t.publico_alvo_label)],
        ['Status', fmtData(t.status_label) + (t.obrigatorio ? ' · Obrigatório' : '')],
        ['Publicação', fmtData(t.data_publicacao)],
        ['Liberação', fmtData(t.data_liberacao)],
        ['Materiais de apoio', t.total_materiais ?? 0],
    ];
    document.getElementById('trainingMeta').innerHTML = metas.map(([k2, v]) => `
        <div style="display: flex; justify-content: space-between; border-bottom: 1px dashed #e5e7eb; padding-bottom: 6px;">
            <span style="color: #6b7280;">${k2}</span>
            <strong style="color: #111827; text-align: right;">${v}</strong>
        </div>
    `).join('');
    if (t.descricao) {
        document.getElementById('trainingMeta').insertAdjacentHTML('beforeend',
            `<div style="grid-column: 1 / -1; font-size: 0.85rem; color: #4b5563; background: #f9fafb; border-radius: 6px; padding: 10px 12px;">${esc(t.descricao)}</div>`);
    }

    // Captura info
    document.getElementById('capturaInfo').innerHTML = a.captura_inicio
        ? `<i class="fas fa-history" style="margin-right: 5px;"></i>Registro de tentativas desde ${a.captura_inicio}`
        : '<i class="fas fa-info-circle" style="margin-right: 5px;"></i>Nenhuma submissão de avaliação registrada';

    // Cards de tentativas
    const groups = [
        {key: 'aprovados_1a_tentativa', title: 'Aprovados na 1ª tentativa', icon: 'fa-circle-check', bg: '#ecfdf5', color: '#059669'},
        {key: 'aprovados_2a_tentativa', title: 'Aprovados na 2ª tentativa', icon: 'fa-repeat', bg: '#fef3c7', color: '#d97706'},
        {key: 'reassistiram_conteudo', title: 'Reprovaram 2x e reassistiram', icon: 'fa-rotate-left', bg: '#fee2e2', color: '#dc2626'},
        {key: 'aguardando_2a_tentativa', title: 'Aguardando 2ª tentativa', icon: 'fa-hourglass-half', bg: '#e0e7ff', color: '#4f46e5'},
    ];
    document.getElementById('attemptCards').innerHTML = groups.map(g => {
        const grp = a[g.key] || {};
        const total = grp.total || 0;
        return `
            <div style="background: ${g.bg}; border-radius: 10px; padding: 14px 16px; border: 1px solid rgba(0,0,0,0.05);">
                <div style="display: flex; align-items: center; justify-content: space-between;">
                    <span style="font-size: 0.82rem; font-weight: 700; color: #1f2937;"><i class="fas ${g.icon}" style="color: ${g.color}; margin-right: 6px;"></i>${g.title}</span>
                    <span style="background: white; color: ${g.color}; font-weight: 800; font-size: 1.1rem; min-width: 34px; height: 34px; border-radius: 999px; display: flex; align-items: center; justify-content: center; box-shadow: 0 1px 3px rgba(0,0,0,0.1);">${total}</span>
                </div>
            </div>`;
    }).join('');

    // Nota de usuários sem registro
    const semRegistro = a.aprovados_sem_registro_tentativa || 0;
    const notaNote = document.getElementById('attemptNote');
    if (semRegistro > 0) {
        notaNote.style.display = 'block';
        const desdeCaptura = a.captura_inicio
            ? ` (início do registro em ${a.captura_inicio})`
            : '';
        notaNote.innerHTML = `<i class="fas fa-exclamation-triangle" style="margin-right: 6px;"></i><strong>${semRegistro} usuário(s) aprovado(s)</strong> não possuem registro individual de tentativa — concluíram antes do início do registro de tentativas na plataforma${desdeCaptura}. A análise de tentativas acima reflete apenas os dados disponíveis.`;
    } else {
        notaNote.style.display = 'none';
    }

    // Listas por grupo
    const groupTables = document.getElementById('groupTables');
    groupTables.innerHTML = groups.map(g => {
        const grp = a[g.key] || {};
        const usuarios = grp.usuarios || [];
        if (!usuarios.length) return '';
        // Grupo "1ª tentativa" sempre oculto: os dados já constam no "Detalhamento por
        // Usuário" abaixo — não repetir a lista aqui (evita sensação de relatório duplicado).
        if (g.key === 'aprovados_1a_tentativa') {
            return '';
        }
        const rows = usuarios.map(u => {
            let statusHtml = '';
            if (u.status === 'aprovado_apos_reassistir') {
                statusHtml = `<span style="background: #ecfdf5; color: #059669; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 999px;">Aprovado após reassistir</span>`;
            } else if (u.status === 'bloqueado_aguardando') {
                statusHtml = `<span style="background: #fee2e2; color: #dc2626; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 999px;">Aguardando reassistir</span>`;
            } else if (u.status === 'aprovado_1a_tentativa_historico') {
                statusHtml = `<span style="background: #f3f4f6; color: #6b7280; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 999px;">Histórico (sem registro de tentativa)</span>`;
            }
            return `<tr>
                <td><strong>${esc(u.nome)}</strong></td>
                <td>${tipoBadge(u.tipo_usuario)}</td>
                <td>${fmtData(u.cpf)}</td>
                <td>${fmtData(u.setor)}</td>
                <td>${fmtData(u.cargo)}</td>
                <td>${u.nota !== null && u.nota !== undefined ? u.nota + '%' : '—'}</td>
                <td>${u.tentativas ?? '—'}</td>
                <td>${statusHtml || ''}</td>
            </tr>`;
        }).join('');
        return `
            <div style="margin-bottom: 16px;">
                <div style="font-weight: 700; font-size: 0.88rem; color: #374151; margin-bottom: 8px;">
                    <i class="fas ${g.icon}" style="color: ${g.color}; margin-right: 6px;"></i>${g.title} <span style="color: #9ca3af; font-weight: 600;">(${usuarios.length})</span>
                </div>
                <div style="overflow-x: auto; border: 1px solid #e5e7eb; border-radius: 8px;">
                    <table class="table table-sm" style="font-size: 0.82rem; margin: 0;">
                        <thead><tr style="background: #f8fafc;">
                            <th>Usuário</th><th>Tipo</th><th>CPF</th><th>Setor</th><th>Cargo</th><th>Nota</th><th>Tentativas</th><th>Situação</th>
                        </tr></thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>`;
    }).join('');

    // Não Concluíram
    const nc = r.nao_concluiram || [];
    const ncBox = document.getElementById('naoConclBox');
    const ncCount = document.getElementById('naoConclCount');
    const ncBody = document.getElementById('naoConclBody');
    if (nc.length > 0) {
        ncBox.style.display = 'block';
        ncCount.textContent = nc.length + ' usuário(s)';
        ncBody.innerHTML = nc.map(u => `
            <tr>
                <td><strong>${esc(u.nome)}</strong></td>
                <td>${tipoBadge(u.tipo_usuario)}</td>
                <td>${fmtData(u.cpf)}</td>
                <td>${esc(u.motivo)}</td>
            </tr>
        `).join('');
    } else {
        ncBox.style.display = 'none';
    }

    // Tabela por usuário
    const usuarios = r.usuarios || [];
    document.getElementById('usuariosCount').textContent = usuarios.length + ' usuário(s)';

    document.getElementById('usuariosTableBody').innerHTML = usuarios.length
        ? usuarios.map(u => `
            <tr>
                <td><strong>${esc(u.nome)}</strong></td>
                <td>${tipoBadge(u.tipo_usuario)}</td>
                <td>${fmtData(u.cpf)}</td>
                <td>${fmtData(u.setor)}</td>
                <td>${fmtData(u.cargo)}</td>
                <td>${fmtData(u.data_inicio)}</td>
                <td>${fmtData(u.data_conclusao)}</td>
                <td>${u.nota !== null && u.nota !== undefined ? u.nota + '%' : '—'}</td>
                <td>${u.tentativas ?? '—'}</td>
                <td>${u.porcentagem_assistida}%</td>
                <td>${fmtData(u.tempo_assistido_human)}</td>
                <td>${u.concluido
                    ? '<span style="background: #ecfdf5; color: #059669; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 999px;">Concluído</span>'
                    : '<span style="background: #fef3c7; color: #b45309; font-size: 0.72rem; font-weight: 700; padding: 2px 8px; border-radius: 999px;">Pendente</span>'}</td>
            </tr>`).join('')
        : '<tr><td colspan="12" style="text-align: center; color: #9ca3af; padding: 20px;">Nenhum usuário com progresso registrado para este treinamento.</td></tr>';

    reportArea.style.display = 'block';
}

function renderParecer(text, source) {
    const box = document.getElementById('parecerBox');
    const badge = document.getElementById('parecerSourceBadge');
    const body = document.getElementById('parecerBody');

    if (!text) {
        box.style.display = 'none';
        return;
    }
    box.style.display = 'block';

    if (source === 'ai') {
        badge.innerHTML = '<i class="fas fa-brain" style="margin-right: 5px;"></i>IA (Gemini)';
        badge.setAttribute('style', badgeCss('#f3e8ff', '#6d28d9'));
    } else {
        badge.innerHTML = '<i class="fas fa-cog" style="margin-right: 5px;"></i>Análise local';
        badge.setAttribute('style', badgeCss('#fef3c7', '#92400e'));
    }

    const lines = String(text).split(/\r?\n/);
    let html = '';
    lines.forEach(line => {
        const m = line.match(/^##\s+(.+)$/);
        if (m) {
            html += `<h5 style="margin: 16px 0 6px; font-size: 0.95rem; font-weight: 700; color: #6d28d9; display: flex; align-items: center; gap: 8px;"><span style="width: 4px; height: 16px; background: #7c3aed; border-radius: 2px; display: inline-block;"></span>${esc(m[1])}</h5>`;
        } else if (line.trim()) {
            html += `<p style="margin: 0 0 8px;">${esc(line)}</p>`;
        }
    });
    body.innerHTML = html;
}

function esc(v) {
    const d = document.createElement('div');
    d.textContent = v == null ? '' : String(v);
    return d.innerHTML;
}

// ================= EVENTOS =================

localBtn.addEventListener('click', () => loadReport('{{ route('relatorios.ia.analyze_local') }}', false));
aiBtn.addEventListener('click', () => loadReport('{{ route('relatorios.ia.analyze_ai') }}', true));

pdfBtn.addEventListener('click', function () {
    const id = trainingSelect.value;
    if (!id) {
        setIdle('⚠️ Selecione um treinamento para exportar o PDF', true);
        return;
    }
    window.location.href = '{{ route('relatorios.ia.pdf') }}?training_id=' + encodeURIComponent(id);
});

printBtn.addEventListener('click', () => window.print());

// ================= TXT =================

const txtBtn = document.getElementById('txtBtn');

txtBtn.addEventListener('click', function () {
    if (!currentReport) {
        setIdle('⚠️ Gere um relatório primeiro para gerar o TXT', true);
        return;
    }
    document.getElementById('txtContent').value = buildTxt(currentReport, currentParecer, currentSource);
    document.getElementById('copyOk').style.display = 'none';
    document.getElementById('txtModal').style.display = 'block';
});

function closeTxtModal() {
    document.getElementById('txtModal').style.display = 'none';
}

function copyTxt() {
    const ta = document.getElementById('txtContent');
    ta.select();
    navigator.clipboard.writeText(ta.value).then(() => {
        document.getElementById('copyOk').style.display = 'block';
        setTimeout(() => { document.getElementById('copyOk').style.display = 'none'; }, 3000);
    }).catch(() => {
        document.execCommand('copy');
        document.getElementById('copyOk').style.display = 'block';
        setTimeout(() => { document.getElementById('copyOk').style.display = 'none'; }, 3000);
    });
}

function downloadTxt() {
    const t = currentReport.training || {};
    const nome = (t.titulo || 'relatorio').replace(/[^a-zA-Z0-9]/g, '_');
    const blob = new Blob([document.getElementById('txtContent').value], {type: 'text/plain;charset=utf-8'});
    const a = document.createElement('a');
    a.href = URL.createObjectURL(blob);
    a.download = 'Relatorio_' + nome + '.txt';
    a.click();
    URL.revokeObjectURL(a.href);
}

function buildTxt(r, parecer, source) {
    const t = r.training || {};
    const k = r.kpis || {};
    const a = r.avaliacao || {};
    const tipoLabel = {motorista: 'Motorista', funcionario: 'Funcionario', terceirizado: 'Terceirizado'};
    const linhas = [];

    linhas.push('RELATORIO ANALITICO DE TREINAMENTO');
    linhas.push('='.repeat(40));
    linhas.push('');
    linhas.push('Treinamento: ' + (t.titulo || ''));
    linhas.push('Tipo: ' + (t.tipo_label || '') + (t.tipo_treinamento_label ? ' (' + t.tipo_treinamento_label + ')' : ''));
    linhas.push('Publico-alvo: ' + (t.publico_alvo_label || ''));
    linhas.push('Carga horaria: ' + (t.carga_horaria ? (t.carga_horaria >= 60 ? Math.floor(t.carga_horaria / 60) + 'h ' + (t.carga_horaria % 60 ? (t.carga_horaria % 60) + 'min' : '') : t.carga_horaria + ' min') : '—'));
    linhas.push('Validade: ' + (t.dias_validade ? t.dias_validade + ' dias' : 'Sem validade'));
    linhas.push('Nota minima para aprovacao: ' + (t.nota_minima_aprovacao ? t.nota_minima_aprovacao + '%' : '—'));
    linhas.push('Status: ' + (t.status_label || '') + (t.obrigatorio ? ' — Obrigatorio' : ''));
    linhas.push('Publicacao: ' + (t.data_publicacao || '—') + ' — Liberacao: ' + (t.data_liberacao || '—'));
    linhas.push('Materiais de apoio: ' + (t.total_materiais ?? 0));
    linhas.push('Relatorio gerado em: ' + (r.relatorio_gerado_em || ''));
    linhas.push('');

    linhas.push('INDICADORES DE COBERTURA');
    linhas.push('-'.repeat(30));
    linhas.push('Usuarios ativos elegiveis: ' + (k.usuarios_ativos_total ?? 0));
    linhas.push('Participantes com certificado: ' + (k.usuarios_com_certificado ?? 0));
    linhas.push('Cobertura do publico efetivo: ' + (k.percentual_usuarios_ativos ?? 0) + '%');
    linhas.push('Progressos concluidos: ' + (k.concluidos ?? 0));
    linhas.push('Tempo medio assistido: ' + (k.avg_time_human || '00:00:00'));
    linhas.push('Dias medios para conclusao: ' + (k.avg_days_to_complete ?? '—'));
    linhas.push('');

    linhas.push('DESEMPENHO NA AVALIACAO');
    linhas.push('-'.repeat(30));
    linhas.push('Aprovados na 1a tentativa: ' + (a.aprovados_1a_tentativa?.total ?? 0));
    linhas.push('Aprovados na 2a tentativa: ' + (a.aprovados_2a_tentativa?.total ?? 0));
    linhas.push('Reprovaram 2x e reassistiram: ' + (a.reassistiram_conteudo?.total ?? 0));
    linhas.push('Aguardando 2a tentativa: ' + (a.aguardando_2a_tentativa?.total ?? 0));
    if ((a.aprovados_sem_registro_tentativa ?? 0) > 0) {
        linhas.push('Nota: ' + a.aprovados_sem_registro_tentativa + ' aprovado(s) sem registro individual de tentativa (concluido antes do inicio do registro na plataforma).');
    }
    if (a.nota_media_submissoes !== null && a.nota_media_submissoes !== undefined) {
        linhas.push('Nota media (submissoes / aprovacoes): ' + a.nota_media_submissoes + '% / ' + (a.nota_media_aprovacoes ?? '—') + '%');
    }
    linhas.push('');

    linhas.push('DETALHAMENTO POR USUARIO');
    linhas.push('-'.repeat(30));
    (r.usuarios || []).forEach((u, i) => {
        linhas.push((i + 1) + '. ' + u.nome + ' | ' + (tipoLabel[u.tipo_usuario] || u.tipo_usuario || '—') + ' | Status: ' + (u.concluido ? 'Concluido' : 'Pendente'));
    });
    linhas.push('');

    const nc = r.nao_concluiram || [];
    if (nc.length > 0) {
        linhas.push('NAO CONCLUIRAM (' + nc.length + ')');
        linhas.push('-'.repeat(30));
        linhas.push('Usuarios elegiveis que nao obtiveram certificado (ferias excluidos).');
        linhas.push('');
        nc.forEach(u => {
            linhas.push('  - ' + u.nome + ' | ' + (tipoLabel[u.tipo_usuario] || u.tipo_usuario || '—') + ' | ' + u.motivo);
        });
        linhas.push('');
    }

    if (parecer) {
        linhas.push('PARECER EXECUTIVO' + (source === 'ai' ? ' (IA)' : ' (analise local)'));
        linhas.push('-'.repeat(30));
        parecer.split(/\r?\n/).forEach(line => {
            if (line.startsWith('## ')) {
                linhas.push('');
                linhas.push(line.replace('## ', '').toUpperCase());
                linhas.push('');
            } else {
                linhas.push(line);
            }
        });
    }

    return linhas.join('\n');
}
</script>
@endsection