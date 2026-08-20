@extends('layout')

@section('title', ($pp ? 'Editar Projeto Pedagógico' : 'Novo Projeto Pedagógico'))

@section('content')
@php $isEdit = !is_null($pp); @endphp
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-6">
        <div>
            <h1 class="text-2xl font-bold text-gray-800">
                <i class="fas fa-book-open text-blue-900 mr-2"></i>{{ $isEdit ? 'Editar Projeto Pedagógico' : 'Novo Projeto Pedagógico' }}
            </h1>
            <p class="text-sm text-gray-500 mt-1">
                @if($isEdit)
                    Atende a: <strong>{{ $pp->nomes_treinamentos ?: '—' }}</strong>
                @else
                    O projeto pedagógico pode ser vinculado a <strong>um ou mais treinamentos</strong>.
                @endif
            </p>
        </div>
        <div class="flex gap-2">
            @if($isEdit && $pp->assinatura_rt)
                <a href="{{ route('projetos-pedagogicos.download', $pp) }}" class="px-4 py-2 bg-emerald-700 text-white text-sm font-bold rounded-lg hover:bg-emerald-800">
                    <i class="fas fa-file-pdf mr-1"></i> Baixar PDF padrão
                </a>
            @endif
            <a href="{{ route('projetos-pedagogicos.index') }}" class="px-4 py-2 bg-gray-200 text-gray-700 text-sm font-bold rounded-lg hover:bg-gray-300">
                <i class="fas fa-arrow-left mr-1"></i> Voltar
            </a>
        </div>
    </div>

    @if($errors->any())
        <div class="mb-4 px-4 py-3 bg-rose-50 border border-rose-200 text-rose-800 rounded-lg text-sm">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $erro)
                    <li>{{ $erro }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ $isEdit ? route('projetos-pedagogicos.update', $pp) : route('projetos-pedagogicos.store') }}" method="POST" enctype="multipart/form-data" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-1">1. Treinamentos atendidos por este projeto pedagógico</h2>
            <p class="text-xs text-gray-500 mb-3">
                Selecione <strong>um ou mais treinamentos</strong> que usarão este mesmo projeto pedagógico.
                <span class="text-amber-700"><i class="fas fa-shield-alt mr-1"></i>Regra de segurança: treinamentos que já possuem PP próprio não aparecem aqui — para alterá-los, edite o PP específico do treinamento.</span>
            </p>

            <input type="text" id="trainings-busca" placeholder="Buscar treinamento por nome..." class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900 mb-3">
            <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                @forelse($disponiveis as $treinamento)
                    <label class="flex items-center training-item">
                        <input type="checkbox" name="trainings[]" value="{{ $treinamento->id }}"
                            {{ in_array($treinamento->id, old('trainings', $selecionados)) ? 'checked' : '' }}
                            class="mr-2">
                        <span class="text-gray-700">{{ $treinamento->titulo }}</span>
                        <span class="text-xs text-gray-500 ml-2">
                            {{ $treinamento->tipo_treinamento ? ucfirst($treinamento->tipo_treinamento) : strtoupper($treinamento->tipo) }} · {{ $treinamento->carga_horaria }} min
                        </span>
                    </label>
                @empty
                    <p class="text-gray-500 text-sm italic">
                        Nenhum treinamento disponível. Todos os treinamentos já possuem projeto pedagógico.
                    </p>
                @endforelse
            </div>
            <p class="text-[11px] text-gray-400 mt-2"><span id="trainings-contagem">0</span> treinamento(s) selecionado(s).</p>
        </div>

        <div class="bg-amber-50 border-2 border-amber-300 rounded-xl p-5 shadow-sm">
            <h2 class="text-lg font-bold text-amber-900 mb-1">
                <i class="fas fa-wand-magic-sparkles mr-2"></i>Gerar preenchimento a partir do treinamento
            </h2>
            <p class="text-xs text-amber-800 mb-3">
                Selecione qual treinamento será cadastrado. O sistema pré-preenche os campos de estruturação pedagógica (Anexo II 3.1) para você <strong>analisar e ajustar</strong> antes de salvar. O conteúdo gerado é um ponto de partida — revise todos os campos conforme a realidade do curso.
            </p>
            <div class="flex flex-col md:flex-row md:items-center gap-3">
                <select id="pp-template-select" onchange="aplicarTemplatePP(this.value)" class="w-full md:max-w-md px-4 py-2 border border-amber-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-amber-500 text-sm">
                    <option value="">-- Selecione o treinamento a ser cadastrado --</option>
                    @foreach($templates as $slug => $template)
                        <option value="{{ $slug }}">{{ $template['nome'] }}</option>
                    @endforeach
                </select>
                <button type="button" onclick="aplicarTemplatePP(document.getElementById('pp-template-select').value, true)" class="px-4 py-2 bg-amber-600 text-white text-sm font-bold rounded-lg hover:bg-amber-700">
                    <i class="fas fa-wand-magic-sparkles mr-1"></i>Gerar para análise
                </button>
                <span class="text-[11px] text-amber-700"><i class="fas fa-info-circle mr-1"></i>Os campos atuais serão sobrescritos.</span>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-1">2. Identificação do Projeto</h2>
            <p class="text-xs text-gray-500 mb-4">Dados gerais e responsável técnico pela capacitação (Anexo II 3.1d/3.1e).</p>
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Versão</label>
                    <input type="text" name="versao" value="{{ old('versao', $pp?->versao ?? '1.0') }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Data de validação</label>
                    <input type="date" name="data_validacao" value="{{ old('data_validacao', $pp?->data_validacao?->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Próxima revisão (sugerido: 2 anos)</label>
                    <input type="date" name="data_proxima_revisao" value="{{ old('data_proxima_revisao', $pp?->data_proxima_revisao?->format('Y-m-d')) }}" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    <p class="text-xs text-gray-400 mt-1">Se vazio, calculamos automaticamente 2 anos após a validação (Anexo II 3.3).</p>
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Responsável técnico pela capacitação</label>
                    <input type="text" name="responsavel_tecnico_nome" value="{{ old('responsavel_tecnico_nome', $pp?->responsavel_tecnico_nome) }}" placeholder="Nome completo" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Qualificação do responsável técnico</label>
                    <input type="text" name="responsavel_tecnico_qualificacao" value="{{ old('responsavel_tecnico_qualificacao', $pp?->responsavel_tecnico_qualificacao) }}" placeholder="Ex: Técnico de Segurança do Trabalho, RT" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Instrutores (quando aplicável)</label>
                    <input type="text" name="instrutores" value="{{ old('instrutores', $pp?->instrutores) }}" placeholder="Nomes e qualificação" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm space-y-4">
            <h2 class="text-lg font-bold text-gray-800 mb-1">3. Estruturação Pedagógica (Anexo II 3.1)</h2>
            <p class="text-xs text-gray-500 mb-4">Preencha os itens conforme a NR-01. Quanto mais completo, maior o percentual de conformidade exibido na listagem.</p>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">a) Objetivo geral da capacitação</label>
                <textarea name="objetivo_geral" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('objetivo_geral', $pp?->objetivo_geral) }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">b) Princípios e conceitos de proteção à SST (NR)</label>
                <textarea name="principios_sst" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('principios_sst', $pp?->principios_sst) }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">c) Estratégia pedagógica (teórica/prática)</label>
                <textarea name="estrategia_pedagogica" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('estrategia_pedagogica', $pp?->estrategia_pedagogica) }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">g) Conteúdo programático teórico e prático</label>
                <textarea name="conteudo_programatico_pp" rows="3" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('conteudo_programatico_pp', $pp?->conteudo_programatico_pp) }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">h) Objetivo de cada módulo</label>
                <textarea name="objetivo_modulos" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('objetivo_modulos', $pp?->objetivo_modulos) }}</textarea>
            </div>

            <div class="grid md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">i) Carga horária</label>
                    <input type="text" name="carga_horaria_pp" value="{{ old('carga_horaria_pp', $pp?->carga_horaria_pp) }}" placeholder="Ex: 04 horas" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">j) Tempo mínimo de dedicação diária</label>
                    <input type="text" name="tempo_minimo_diario" value="{{ old('tempo_minimo_diario', $pp?->tempo_minimo_diario) }}" placeholder="Ex: 30 minutos" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">k) Prazo máximo de conclusão</label>
                    <input type="text" name="prazo_maximo_conclusao" value="{{ old('prazo_maximo_conclusao', $pp?->prazo_maximo_conclusao) }}" placeholder="Ex: 15 dias" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">l) Público-alvo</label>
                <textarea name="publico_alvo" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('publico_alvo', $pp?->publico_alvo) }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">m) Material didático</label>
                <textarea name="material_didatico" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('material_didatico', $pp?->material_didatico) }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">n) Instrumentos para potencialização do aprendizado</label>
                <textarea name="instrumentos_aprendizado" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('instrumentos_aprendizado', $pp?->instrumentos_aprendizado) }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">o) Avaliação de aprendizagem (com situações práticas da rotina)</label>
                <textarea name="avaliacao_aprendizagem" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('avaliacao_aprendizagem', $pp?->avaliacao_aprendizagem) }}</textarea>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-1">f) Infraestrutura operacional de apoio e controle (AVA)</label>
                <textarea name="infraestrutura_operacional" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('infraestrutura_operacional', $pp?->infraestrutura_operacional) }}</textarea>
            </div>
        </div>

        <div class="bg-white p-6 rounded-xl border border-gray-200 shadow-sm">
            <h2 class="text-lg font-bold text-gray-800 mb-1">4. Assinatura do Responsável Técnico</h2>
            <p class="text-xs text-gray-500 mb-4">Assine diretamente no sistema para compor a versão oficial do projeto pedagógico (PDF gerado pela plataforma). A assinatura identifica o responsável técnico pela capacitação.</p>

            <div class="grid md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Sua assinatura</label>
                    <div class="signature-container border-2 border-dashed border-gray-300 rounded-lg bg-gray-50" style="touch-action: none;">
                        <canvas id="pp-signature-canvas" height="140" class="w-full h-36 rounded-lg cursor-crosshair bg-white"></canvas>
                    </div>
                    <input type="hidden" name="assinatura_rt" id="pp-assinatura-rt">
                    <div class="flex items-center gap-4 mt-2">
                        <button type="button" onclick="limparAssinaturaPP()" class="text-xs text-amber-600 hover:text-amber-800 font-bold">
                            <i class="fas fa-eraser mr-1"></i> Limpar assinatura
                        </button>
                        <label class="flex items-center text-xs text-gray-500 cursor-pointer">
                            <input type="checkbox" name="remover_assinatura" value="1" class="mr-1"> Remover assinatura salva
                        </label>
                    </div>
                    @if($pp?->assinatura_rt)
                        <div class="mt-3 p-3 bg-emerald-50 border border-emerald-200 rounded-lg">
                            <p class="text-[10px] font-bold text-emerald-700 uppercase mb-1">Assinatura atual</p>
                            <img src="{{ $pp->assinatura_rt }}" alt="Assinatura do responsável técnico" class="h-16 bg-white border border-gray-200 rounded">
                            <p class="text-xs text-gray-600 mt-1">
                                {{ $pp->assinatura_rt_nome ?? $pp->responsavel_tecnico_nome }}
                                @if($pp->assinatura_rt_data)
                                    · {{ $pp->assinatura_rt_data->format('d/m/Y H:i') }}
                                @endif
                            </p>
                        </div>
                    @endif
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-1">Documento assinado em PDF (opcional)</label>
                    <p class="text-xs text-gray-500 mb-2">Além da assinatura no sistema, você pode anexar a versão em PDF do projeto pedagógico para arquivamento e disponibilização ao contratante (4.1.1).</p>
                    <input type="file" name="arquivo_pdf" accept=".pdf" class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
                    @if($pp?->arquivo_pdf)
                        <div class="mt-3 text-sm text-gray-600">
                            <i class="fas fa-file-pdf text-red-600 mr-1"></i> Arquivo atual:
                            <a href="{{ route('projetos-pedagogicos.download-arquivo', $pp) }}" class="text-blue-700 font-bold hover:underline">Baixar PDF</a>
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="flex gap-3">
            <button type="submit" class="px-6 py-3 bg-emerald-700 text-white font-bold rounded-lg hover:bg-emerald-800 shadow">
                <i class="fas fa-save mr-2"></i>{{ $isEdit ? 'Salvar Alterações' : 'Cadastrar Projeto Pedagógico' }}
            </button>
            <a href="{{ route('projetos-pedagogicos.index') }}" class="px-6 py-3 bg-gray-300 text-gray-700 font-bold rounded-lg hover:bg-gray-400 text-center">
                Cancelar
            </a>
        </div>
    </form>
</div>
@endsection

@section('extra_js')
<script>
    const ppTemplates = @json($templates);

    // Campos do Anexo II 3.1 que são pré-preenchidos pelo modelo
    const ppTemplateFields = [
        'objetivo_geral', 'principios_sst', 'estrategia_pedagogica', 'conteudo_programatico_pp',
        'objetivo_modulos', 'carga_horaria_pp', 'tempo_minimo_diario', 'prazo_maximo_conclusao',
        'publico_alvo', 'material_didatico', 'instrumentos_aprendizado', 'avaliacao_aprendizagem',
        'infraestrutura_operacional'
    ];

    function aplicarTemplatePP(slug, forcar) {
        const template = ppTemplates[slug];
        if (!template) return;

        if (!forcar) {
            const confirmar = confirm(
                'O sistema vai substituir o conteúdo atual dos campos de estruturação pedagógica pelo modelo do treinamento "' +
                template.nome + '". Deseja continuar?'
            );
            if (!confirmar) {
                document.getElementById('pp-template-select').value = '';
                return;
            }
        }

        ppTemplateFields.forEach(function(campo) {
            const el = document.querySelector('form [name="' + campo + '"]');
            if (el) el.value = template[campo] || '';
        });

        const selecao = document.getElementById('pp-template-select');
        if (selecao) selecao.value = '';

        Swal.fire({
            icon: 'success',
            title: 'Modelo aplicado!',
            text: 'Campos pré-preenchidos. Revise e ajuste antes de salvar.',
            timer: 2500,
            showConfirmButton: false
        });
    }

    // Busca e contagem dos treinamentos selecionados
    document.addEventListener('DOMContentLoaded', function() {
        const busca = document.getElementById('trainings-busca');
        if (busca) {
            busca.addEventListener('input', function() {
                const termo = this.value.toLowerCase().trim();
                document.querySelectorAll('.training-item').forEach(item => {
                    item.style.display = item.textContent.toLowerCase().includes(termo) ? '' : 'none';
                });
            });
        }
        atualizarContagemTrainings();
        document.querySelectorAll('input[name="trainings[]"]').forEach(cb => {
            cb.addEventListener('change', atualizarContagemTrainings);
        });
    });

    function atualizarContagemTrainings() {
        const total = document.querySelectorAll('input[name="trainings[]"]:checked').length;
        const el = document.getElementById('trainings-contagem');
        if (el) el.textContent = total;
    }

    let ppSignatureCanvas = null;

    document.addEventListener('DOMContentLoaded', function() {
        const cvs = document.getElementById('pp-signature-canvas');
        if (!cvs) return;
        const rect = cvs.getBoundingClientRect();
        cvs.width = rect.width || 500;
        cvs.height = rect.height || 140;
        const ctx = cvs.getContext('2d');
        ctx.lineWidth = 2;
        ctx.lineCap = 'round';
        ctx.strokeStyle = '#000';
        ppSignatureCanvas = { canvas: cvs, ctx, isDrawing: false };

        function getPos(e) {
            const r = cvs.getBoundingClientRect();
            let cx = e.clientX, cy = e.clientY;
            if (e.touches && e.touches.length > 0) { cx = e.touches[0].clientX; cy = e.touches[0].clientY; }
            return { x: cx - r.left, y: cy - r.top };
        }
        function start(e) { ppSignatureCanvas.isDrawing = true; const p = getPos(e); ctx.beginPath(); ctx.moveTo(p.x, p.y); e.preventDefault(); }
        function draw(e) { if (!ppSignatureCanvas.isDrawing) return; const p = getPos(e); ctx.lineTo(p.x, p.y); ctx.stroke(); e.preventDefault(); }
        function stop() { ppSignatureCanvas.isDrawing = false; }
        cvs.addEventListener('mousedown', start);
        cvs.addEventListener('mousemove', draw);
        cvs.addEventListener('mouseup', stop);
        cvs.addEventListener('touchstart', start);
        cvs.addEventListener('touchmove', draw);
        cvs.addEventListener('touchend', stop);
    });

    function limparAssinaturaPP() {
        if (!ppSignatureCanvas) return;
        const { canvas, ctx } = ppSignatureCanvas;
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        document.getElementById('pp-assinatura-rt').value = '';
    }

    // Serializa a assinatura no envio do formulário
    document.addEventListener('submit', function(e) {
        if (!e.target.matches('form')) return;
        if (!ppSignatureCanvas) return;
        const { canvas, ctx } = ppSignatureCanvas;
        const data = ctx.getImageData(0, 0, canvas.width, canvas.height).data;
        let vazio = true;
        for (let i = 3; i < data.length; i += 4) {
            if (data[i] !== 0) { vazio = false; break; }
        }
        const hidden = document.getElementById('pp-assinatura-rt');
        if (!hidden) return;
        if (vazio) {
            hidden.value = '';
            return;
        }
        // Converte para JPEG com fundo branco (sem canal alfa) para compatibilidade com o PDF (TCPDF)
        const temp = document.createElement('canvas');
        temp.width = canvas.width;
        temp.height = canvas.height;
        const tctx = temp.getContext('2d');
        tctx.fillStyle = '#ffffff';
        tctx.fillRect(0, 0, temp.width, temp.height);
        tctx.drawImage(canvas, 0, 0);
        hidden.value = temp.toDataURL('image/jpeg', 0.9);
    });
</script>
@endsection