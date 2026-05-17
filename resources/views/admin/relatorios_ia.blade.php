@extends('layout')

@section('content')
<div class="container-fluid" style="padding: 30px 20px;">
    <!-- Header -->
    <div style="margin-bottom: 30px;">
        <h1 style="margin: 0; font-size: 2rem; color: #1a1a1a; font-weight: 600;">
            <i class="fas fa-chart-line" style="margin-right: 12px; color: #5B21B6;"></i>Análise de Treinamentos
        </h1>
        <p style="margin: 8px 0 0; color: #666; font-size: 0.95rem;">Gere relatórios executivos com análise local ou powered by IA</p>
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
                    <option value="">-- Nenhum (análise geral) --</option>
                    @foreach($treinamentos as $t)
                        <option value="{{ $t->id }}">{{ $t->titulo }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        <!-- Card Body -->
        <div class="card-body" style="padding: 30px;">
            <!-- Buttons -->
            <div style="display: flex; gap: 12px; margin-bottom: 30px;">
                <button type="button" id="localBtn" class="btn" style="background-color: #059669; border: none; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px;" onmouseover="this.style.backgroundColor='#047857'" onmouseout="this.style.backgroundColor='#059669'">
                    <i class="fas fa-bolt"></i>Análise Local
                </button>
                <button type="button" id="aiBtn" class="btn" style="background-color: #5B21B6; border: none; color: white; padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; transition: all 0.3s; display: flex; align-items: center; gap: 8px;" onmouseover="this.style.backgroundColor='#7C3AED'" onmouseout="this.style.backgroundColor='#5B21B6'">
                    <i class="fas fa-brain"></i>Análise com IA
                </button>
            </div>

            <!-- Status & Result Area -->
            <div style="background: #f9fafb; padding: 20px; border-radius: 8px; border-left: 4px solid #5B21B6;">
                <div id="statusBadge" style="margin-bottom: 12px; font-size: 0.9rem; color: #666; font-weight: 500; display: flex; align-items: center; gap: 8px;">
                    <i class="fas fa-info-circle" style="color: #5B21B6;"></i>
                    Selecione um treinamento para gerar análise
                </div>
                <pre id="resultPre" style="white-space: pre-wrap; background: white; padding: 18px; border: 1px solid #e5e7eb; border-radius: 6px; min-height: 150px; font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; font-size: 0.95rem; line-height: 1.5; color: #1f2937; margin: 0;"></pre>
            </div>

            <!-- Info Box -->
            <div style="margin-top: 20px; padding: 15px; background: #eff6ff; border-left: 4px solid #3b82f6; border-radius: 6px; color: #1e40af; font-size: 0.9rem;">
                <i class="fas fa-lightbulb" style="margin-right: 8px;"></i>
                <strong>Dica:</strong> Use "Análise Local" para resultados instantâneos ou "Análise com IA" para resumos executivos com inteligência artificial.
            </div>
        </div>
    </div>
</div>

@endsection


@section('extra_js')
<script>
const statusBadge = document.getElementById('statusBadge');
const resultPre = document.getElementById('resultPre');
const localBtn = document.getElementById('localBtn');
const aiBtn = document.getElementById('aiBtn');
const trainingSelect = document.getElementById('training_id');

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

async function postJson(url, payload, timeoutMs = 45000) {
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

localBtn.addEventListener('click', async function(){
    const id = trainingSelect.value;
    
    if (!id) {
        setIdle('⚠️ Selecione um treinamento para análise', true);
        resultPre.textContent = 'Nenhum treinamento selecionado.\n\nPor favor, escolha um treinamento na lista acima para gerar a análise.';
        return;
    }

    resultPre.textContent = 'Processando...';
    setRunning('Gerando análise local...');
    try {
        const data = await postJson('{{ route('relatorios.ia.analyze_local') }}', {training_id: id}, 20000);
        
        if (data.error) {
            setIdle(data.error, true);
            resultPre.textContent = `[ERRO]\n${data.error}`;
            return;
        }
        
        if (!data.concluidos || data.concluidos === 0) {
            setIdle('Sem dados disponíveis', true);
            resultPre.textContent = 'Este treinamento não possui dados de conclusão registrados.\n\nNão há resumo a gerar.';
            return;
        }

        resultPre.textContent = data.human_summary || JSON.stringify(data, null, 2);
        setIdle('✓ Análise local concluída');
    } catch (error) {
        resultPre.textContent = 'Falha ao gerar análise local.';
        setIdle(error.name === 'AbortError' ? 'Tempo excedido na análise' : 'Erro ao gerar análise', true);
    }
});

aiBtn.addEventListener('click', async function(){
    const id = trainingSelect.value;
    
    if (!id) {
        setIdle('⚠️ Selecione um treinamento para análise', true);
        resultPre.textContent = 'Nenhum treinamento selecionado.\n\nPor favor, escolha um treinamento na lista acima para gerar a análise com IA.';
        return;
    }

    resultPre.textContent = 'Processando...';
    setRunning('Solicitando análise da IA, aguarde até 45 segundos...');
    try {
        const data = await postJson('{{ route('relatorios.ia.analyze_ai') }}', {training_id: id}, 45000);
        
        if (data.error && data.error !== 'null') {
            // Verificar se é "sem dados"
            if (data.error.toLowerCase().includes('sem dados') || data.error.toLowerCase().includes('não há')) {
                setIdle('Sem dados disponível', true);
                resultPre.textContent = data.error;
                return;
            }
        }

        if (data.ai_summary) {
            resultPre.textContent = data.ai_summary;
            const status = data.source === 'ai' ? '✓ Análise com IA gerada com sucesso' : 'Análise local (IA indisponível no momento)';
            setIdle(status, data.source === 'ai' ? false : true);
        } else {
            resultPre.textContent = data.error || 'Nenhum resumo foi gerado.';
            setIdle('Falha ao gerar análise', true);
        }
    } catch (error) {
        resultPre.textContent = 'A requisição da IA foi interrompida ou expirou.';
        setIdle(error.name === 'AbortError' ? 'Tempo excedido na análise com IA' : 'Erro na chamada da IA', true);
    }
});
</script>
@endsection
