@extends('layout')

@section('title', 'Editar Treinamento')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-edit text-blue-900 mr-3"></i>Editar Treinamento
    </h1>

    <div class="bg-white p-8 rounded-lg shadow-lg">
        <form action="{{ route('treinamentos.update', $training) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Título *</label>
                <input type="text" name="titulo" value="{{ $training->titulo }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Descrição</label>
                <textarea name="descricao" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ $training->descricao }}</textarea>
            </div>

            <div id="bloco-ementa">
                <label class="block text-gray-700 font-semibold mb-2">Conteúdo Programático (Ementa)</label>
                <textarea name="conteudo_programatico" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900" placeholder="Liste os tópicos que serão abordados no treinamento (ex: conceitos, legislação aplicável, procedimentos práticos, avaliação...). Este conteúdo constará como requisito da NR-01 (1.7.1.1).">{{ old('conteudo_programatico', $training->conteudo_programatico) }}</textarea>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tipo *</label>
                    <select name="tipo" id="tipo-select" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="treinamento" {{ $training->tipo === 'treinamento' ? 'selected' : '' }}>Treinamento</option>
                        <option value="dss" {{ $training->tipo === 'dss' ? 'selected' : '' }}>DSS</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Carga Horária (minutos) *</label>
                    <input type="number" name="carga_horaria" value="{{ $training->carga_horaria }}" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <div id="bloco-tipo-treinamento" class="grid md:grid-cols-2 gap-4" style="{{ $training->tipo === 'treinamento' ? '' : 'display:none;' }}">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tipo do Treinamento (NR-01 1.7.1.2) *</label>
                    <select name="tipo_treinamento" id="tipo-treinamento-select" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="">-- Selecione --</option>
                        <option value="inicial" {{ old('tipo_treinamento', $training->tipo_treinamento) === 'inicial' ? 'selected' : '' }}>Inicial</option>
                        <option value="periodico" {{ old('tipo_treinamento', $training->tipo_treinamento) === 'periodico' ? 'selected' : '' }}>Periódico</option>
                        <option value="eventual" {{ old('tipo_treinamento', $training->tipo_treinamento) === 'eventual' ? 'selected' : '' }}>Eventual</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Validade (dias)</label>
                    <input type="number" name="dias_validade" value="{{ old('dias_validade', $training->dias_validade) }}" min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900" placeholder="Ex: 365 = renovação anual">
                    <p class="text-xs text-gray-500 mt-1">Após a conclusão, o treinamento expira nesta quantidade de dias (ex: NR-06 renovada anualmente).</p>
                </div>
            </div>

            <div id="bloco-tipo-usuario">
                <label class="block text-gray-700 font-semibold mb-2">Permitido para *</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="tipo_usuario_permitido[]" value="motorista" {{ in_array('motorista', (array)$training->tipo_usuario_permitido) ? 'checked' : '' }} class="mr-2">
                        <span class="text-gray-700">Motoristas</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="tipo_usuario_permitido[]" value="funcionario" {{ in_array('funcionario', (array)$training->tipo_usuario_permitido) ? 'checked' : '' }} class="mr-2">
                        <span class="text-gray-700">Funcionários</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="tipo_usuario_permitido[]" value="terceirizado" {{ in_array('terceirizado', (array)$training->tipo_usuario_permitido) ? 'checked' : '' }} class="mr-2">
                        <span class="text-gray-700">Terceirizados</span>
                    </label>
                </div>
            </div>

            <div id="bloco-funcionarios" style="display:none;">
                <label class="block text-gray-700 font-semibold mb-2">Funcionários que assistirão</label>
                <p class="text-sm text-gray-500 mb-2">Selecione os funcionários que terão este treinamento liberado para assistir. Este conteúdo é direcionado e cobrado por funcionário atribuído. Sem atribuições, apenas administradores podem assistir.</p>
                <input type="text" id="funcionarios-busca" placeholder="Buscar por nome ou CPF..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900 mb-3">
                <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                    @php
                        $atribuidosIds = $training->assignedUsers->pluck('id')->all();
                        $antigosFuncionarios = old('funcionarios', $atribuidosIds);
                    @endphp
                    @forelse($funcionarios as $funcionario)
                        <label class="flex items-center funcionario-item">
                            <input type="checkbox" name="funcionarios[]" value="{{ $funcionario->id }}" {{ in_array($funcionario->id, $antigosFuncionarios) ? 'checked' : '' }} class="mr-2 funcionario-check">
                            <span class="text-gray-700">{{ $funcionario->nome }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ ucfirst($funcionario->tipo_usuario) }}</span>
                        </label>
                    @empty
                        <p class="text-gray-500 text-sm italic">Nenhum funcionário disponível.</p>
                    @endforelse
                </div>
            </div>

            <!-- Avaliação para DSS (pergunta única) -->
            <div id="bloco-avaliacao-legada" style="{{ $training->tipo === 'dss' ? '' : 'display:none;' }}" class="border-t pt-6 space-y-4">
                <h2 class="text-xl font-bold text-gray-800">Avaliação do DSS</h2>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Pergunta da avaliação *</label>
                    <textarea name="avaliacao_pergunta" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('avaliacao_pergunta', $training->avaliacao_pergunta) }}</textarea>
                </div>

                @php
                    $assessmentOptions = $training->avaliacao_opcoes ?? [];
                @endphp

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 1 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[0] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 2 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[1] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 3</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[2] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 4</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[3] ?? '' }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Resposta correta *</label>
                    <select name="avaliacao_resposta_correta" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="0" {{ (int) $training->avaliacao_resposta_correta === 0 ? 'selected' : '' }}>Opção 1</option>
                        <option value="1" {{ (int) $training->avaliacao_resposta_correta === 1 ? 'selected' : '' }}>Opção 2</option>
                        <option value="2" {{ (int) $training->avaliacao_resposta_correta === 2 ? 'selected' : '' }}>Opção 3</option>
                        <option value="3" {{ (int) $training->avaliacao_resposta_correta === 3 ? 'selected' : '' }}>Opção 4</option>
                    </select>
                </div>
            </div>

            <!-- Avaliação para Treinamento (banco de questões NR-01 4.6) -->
            <div id="bloco-banco-questoes" style="{{ $training->tipo === 'treinamento' ? '' : 'display:none;' }}" class="border-t pt-6">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-bold text-gray-800">Avaliação do Treinamento (Banco de Questões)</h2>
                    <button type="button" onclick="adicionarQuestao()" class="bg-blue-900 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-blue-800">
                        <i class="fas fa-plus mr-1"></i>Adicionar Questão
                    </button>
                </div>
                <p class="text-sm text-gray-500 mb-4">Cadastre até 10 questões. Cada questão pode ter de <strong>2 a 4 opções</strong> e deve indicar a resposta correta. Na prova, as questões e opções são embaralhadas (NR-01 Anexo II 4.6.2).</p>

                <div id="container-questoes" class="space-y-4">
                    <p class="text-gray-500 text-sm italic" id="sem-questoes-aviso" style="{{ $training->questions->count() > 0 ? 'display:none;' : '' }}">Nenhuma questão cadastrada. Clique em "Adicionar Questão".</p>
                </div>

                <div class="grid md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Questões sorteadas na prova</label>
                        <input type="number" name="quantidade_questoes_prova" id="quantidade-questoes-prova" value="{{ old('quantidade_questoes_prova', $training->quantidade_questoes_prova) }}" min="1" max="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900" placeholder="Vazio = todas as cadastradas">
                        <p class="text-xs text-gray-500 mt-1">Deixe vazio para sortear entre todas as questões cadastradas.</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Nota mínima p/ aprovação (%)</label>
                        <input type="number" name="nota_minima_aprovacao" value="{{ old('nota_minima_aprovacao', $training->nota_minima_aprovacao ?? 70) }}" min="1" max="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <p class="text-xs text-gray-500 mt-1">Abaixo da nota = conceito <strong>insatisfatório</strong> (refaz a prova). Igual ou acima = <strong>satisfatório</strong>.</p>
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Status</label>
                <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    <option value="ativo" {{ $training->status === 'ativo' ? 'selected' : '' }}>Ativo</option>
                    <option value="inativo" {{ $training->status === 'inativo' ? 'selected' : '' }}>Inativo</option>
                </select>
            </div>

            <div>
                <label class="flex items-center">
                    <input type="checkbox" name="obrigatorio" value="1" {{ old('obrigatorio', $training->obrigatorio) ? 'checked' : '' }} class="mr-2">
                    <span class="text-gray-700 font-semibold">Treinamento Obrigatório</span>
                </label>
            </div>

            <div class="border-t pt-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-file-download mr-2 text-green-600"></i>Materiais de Apoio
                </h2>

                <div class="mt-4 mb-4">
                    <label class="block text-gray-700 font-semibold mb-2">Liberar Conteúdo (data e hora - fuso: Brasil UTC-3)</label>
                    <div class="flex gap-2 items-center">
                        <input type="datetime-local" id="data-liberacao-local-edit" name="data_liberacao" value="{{ old('data_liberacao', optional($training->data_liberacao)->format('Y-m-d\TH:i')) }}" class="px-4 py-2 border border-gray-300 rounded-lg" placeholder="DD/MM/AAAA HH:MM">
                        <p class="text-sm text-gray-500">Hora de São Paulo.</p>
                    </div>
                </div>

                <!-- Aviso -->
                <p class="text-sm text-gray-600 mb-4">Adicione PDFs, imagens ou outros arquivos que servirão como material de suporte para os usuários durante o treinamento.</p>

                <!-- Upload de novo material -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mb-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Carregar novo material</h3>
                    <div id="material-upload-form" class="space-y-3">
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Nome do Material (opcional)</label>
                            <input type="text" id="material-nome" placeholder="Ex: Manual do Motorista" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Descrição (opcional)</label>
                            <textarea id="material-descricao" placeholder="Descrição breve do material..." rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Arquivo (Máx. 100MB)</label>
                            <input type="file" id="material-arquivo" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar,.txt" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        </div>
                        <button type="button" id="material-upload-btn" class="w-full bg-green-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-green-700 transition text-sm">
                            <i class="fas fa-upload mr-2"></i>Carregar Material
                        </button>
                    </div>
                    <div id="upload-feedback" class="mt-2"></div>
                </div>

                <!-- Lista de materiais cadastrados -->
                <div id="materiais-container">
                    @if($training->materials->count() > 0)
                        <h3 class="font-semibold text-gray-800 mb-3">Materiais cadastrados</h3>
                        <ul id="materiais-list" class="space-y-2">
                            @foreach($training->materials as $material)
                                <li class="flex items-center justify-between bg-white p-3 rounded border border-gray-200 material-item" data-material-id="{{ $material->id }}">
                                    <div class="flex items-center gap-3 flex-1">
                                        <i class="fas {{ $material->getIcone() }} text-lg"></i>
                                        <div class="flex-1">
                                            <p class="font-semibold text-gray-800 text-sm">{{ $material->nome }}</p>
                                            @if($material->descricao)
                                                <p class="text-gray-600 text-xs">{{ $material->descricao }}</p>
                                            @endif
                                            <p class="text-gray-500 text-xs">{{ $material->getTamanhoFormatado() }}</p>
                                        </div>
                                    </div>
                                    <button type="button" class="delete-material bg-red-600 text-white px-3 py-2 rounded text-xs hover:bg-red-700" data-material-id="{{ $material->id }}">
                                        <i class="fas fa-trash mr-1"></i>Remover
                                    </button>
                                </li>
                            @endforeach
                        </ul>
                    @else
                        <p class="text-gray-500 text-sm italic">Nenhum material de apoio cadastrado ainda.</p>
                    @endif
                </div>
            </div>

            <script>
                // Aguardar o DOM estar pronto
                document.addEventListener('DOMContentLoaded', function() {
                    // Toggle de comportamento conforme o tipo de conteúdo
                    const tipoSelect = document.getElementById('tipo-select');
                    const blocoTipoUsuario = document.getElementById('bloco-tipo-usuario');
                    const blocoFuncionarios = document.getElementById('bloco-funcionarios');
                    const blocoTipoTreinamento = document.getElementById('bloco-tipo-treinamento');
                    const blocoAvaliacaoLegada = document.getElementById('bloco-avaliacao-legada');
                    const blocoBancoQuestoes = document.getElementById('bloco-banco-questoes');

                    function atualizarTipoConteudo() {
                        const isTreinamento = tipoSelect.value === 'treinamento';
                        blocoTipoUsuario.style.display = isTreinamento ? 'none' : 'block';
                        blocoFuncionarios.style.display = isTreinamento ? 'block' : 'none';
                        blocoTipoTreinamento.style.display = isTreinamento ? 'grid' : 'none';
                        blocoAvaliacaoLegada.style.display = isTreinamento ? 'none' : 'block';
                        blocoBancoQuestoes.style.display = isTreinamento ? 'block' : 'none';
                        const selTipo = document.getElementById('tipo-treinamento-select');
                        if (selTipo) selTipo.required = isTreinamento;
                    }

                    if (tipoSelect) {
                        tipoSelect.addEventListener('change', atualizarTipoConteudo);
                        atualizarTipoConteudo();
                    }

                    // Busca de funcionários
                    const buscaInput = document.getElementById('funcionarios-busca');
                    if (buscaInput) {
                        buscaInput.addEventListener('input', function() {
                            const termo = this.value.toLowerCase().trim();
                            document.querySelectorAll('.funcionario-item').forEach(item => {
                                item.style.display = item.textContent.toLowerCase().includes(termo) ? '' : 'none';
                            });
                        });
                    }

                    const form = document.getElementById('material-upload-form');
                    const uploadBtn = document.getElementById('material-upload-btn');
                    if (!form || !uploadBtn) return;

                    uploadBtn.addEventListener('click', async function(e) {
                        e.preventDefault();

                        const nome = document.getElementById('material-nome').value;
                        const descricao = document.getElementById('material-descricao').value;
                        const arquivo = document.getElementById('material-arquivo').files[0];
                        const feedback = document.getElementById('upload-feedback');
                        const maxFileSize = 250 * 1024 * 1024;

                        if (!arquivo) {
                            feedback.innerHTML = '<div class="text-red-600 text-sm">✗ Por favor, selecione um arquivo</div>';
                            return;
                        }

                        if (arquivo.size > maxFileSize) {
                            feedback.innerHTML = '<div class="text-red-600 text-sm">✗ O arquivo selecionado ultrapassa 250 MB.</div>';
                            return;
                        }

                        uploadBtn.disabled = true;
                        uploadBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Enviando...';
                        feedback.innerHTML = '';

                        try {
                            await uploadFileInChunks(arquivo, nome, descricao, feedback, uploadBtn);
                        } catch (err) {
                            feedback.innerHTML = `<div class="text-red-600 text-sm">✗ Erro ao enviar: ${err.message}</div>`;
                        } finally {
                            uploadBtn.disabled = false;
                            uploadBtn.innerHTML = '<i class="fas fa-upload mr-2"></i>Carregar Material';
                        }
                    });

                    // Listeners para deletar materiais
                    attachDeleteListeners();
                });

                async function uploadFileInChunks(file, nome, descricao, feedbackEl, uploadBtn) {
                    const chunkSize = 1024 * 1024; // 1MB
                    const chunkCount = Math.ceil(file.size / chunkSize);
                    const uploadId = Date.now().toString(36) + '-' + Math.random().toString(36).slice(2,8);
                    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;

                    let uploaded = 0;

                    for (let i = 0; i < chunkCount; i++) {
                        const start = i * chunkSize;
                        const end = Math.min(start + chunkSize, file.size);
                        const chunkBlob = file.slice(start, end);

                        const fd = new FormData();
                        fd.append('upload_id', uploadId);
                        fd.append('chunk_index', i);
                        fd.append('chunk_count', chunkCount);
                        fd.append('original_name', file.name);
                        fd.append('chunk', chunkBlob, file.name);

                        // enviar nome/descricao apenas no último chunk para facilitar
                        if (i === chunkCount - 1) {
                            fd.append('nome', nome || file.name);
                            fd.append('descricao', descricao || '');
                        }

                        const response = await fetch('{{ route("materiais.upload", $training->id) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': csrfToken
                            },
                            body: fd
                        });

                        if (!response.ok) {
                            let text = await response.text();
                            try { text = JSON.parse(text); } catch(e) {}
                            throw new Error((text && text.error) ? text.error : 'Falha no upload do chunk ' + i);
                        }

                        uploaded += (end - start);
                        const pct = Math.round((uploaded / file.size) * 100);
                        feedbackEl.innerHTML = `<div class="text-sm text-gray-700">Enviando... ${pct}%</div>`;
                    }

                    feedbackEl.innerHTML = '<div class="text-green-600 text-sm"><i class="fas fa-check mr-1"></i>Material enviado com sucesso!</div>';
                    document.getElementById('material-nome').value = '';
                    document.getElementById('material-descricao').value = '';
                    document.getElementById('material-arquivo').value = '';

                    setTimeout(() => location.reload(), 1200);
                }

                function attachDeleteListeners() {
                    document.querySelectorAll('.delete-material').forEach(btn => {
                        btn.onclick = async function(e) {
                            e.preventDefault();
                            if (!confirm('Tem certeza que deseja remover este material?')) return;

                            const materialId = this.getAttribute('data-material-id');
                            this.disabled = true;
                            this.innerHTML = '<i class="fas fa-spinner fa-spin mr-1"></i>...';

                            try {
                                const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
                                const response = await fetch(`{{ url('materiais') }}/${materialId}`, {
                                    method: 'DELETE',
                                    headers: {
                                        'X-CSRF-TOKEN': csrfToken
                                    }
                                });

                                if (response.ok) {
                                    const li = document.querySelector(`[data-material-id="${materialId}"]`);
                                    li.style.opacity = '0';
                                    setTimeout(() => li.remove(), 300);
                                } else {
                                    alert('Erro ao remover material');
                                    this.disabled = false;
                                    this.innerHTML = '<i class="fas fa-trash mr-1"></i>Remover';
                                }
                            } catch (error) {
                                alert('Erro: ' + error.message);
                                this.disabled = false;
                                this.innerHTML = '<i class="fas fa-trash mr-1"></i>Remover';
                            }
                        };
                    });
                }
            </script>

            <script>
                let contadorQuestoes = 0;
                const questoesExistentes = @json($training->questions->map(function ($q) {
                    return ['pergunta' => $q->pergunta, 'opcoes' => $q->opcoes, 'resposta_correta' => $q->resposta_correta];
                }));

                function atualizarAvisoQuestoes() {
                    const aviso = document.getElementById('sem-questoes-aviso');
                    const total = document.querySelectorAll('.questao-item').length;
                    if (aviso) aviso.style.display = total === 0 ? 'block' : 'none';
                    const inputQtd = document.getElementById('quantidade-questoes-prova');
                    if (inputQtd && inputQtd.value && parseInt(inputQtd.value) > total) {
                        inputQtd.value = total || '';
                    }
                }

                function adicionarQuestao(dados) {
                    const container = document.getElementById('container-questoes');
                    const total = document.querySelectorAll('.questao-item').length;
                    if (total >= 10) {
                        alert('Máximo de 10 questões por treinamento.');
                        return;
                    }

                    dados = dados || { pergunta: '', opcoes: ['', ''], resposta_correta: 0 };
                    const idx = contadorQuestoes++;
                    const div = document.createElement('div');
                    div.className = 'questao-item bg-gray-50 border border-gray-200 rounded-lg p-4';
                    let opcoesHtml = '';
                    dados.opcoes.forEach((opcao, i) => {
                        opcoesHtml += `
                            <div class="flex items-center gap-2 opcao-row">
                                <input type="text" name="questoes[${idx}][opcoes][]" value="${(opcao || '').replace(/"/g, '&quot;')}" placeholder="Opção ${i + 1}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                                <button type="button" onclick="removerOpcao(this)" class="text-red-600 p-1" title="Remover opção"><i class="fas fa-times"></i></button>
                            </div>`;
                    });
                    const selectOpcoes = dados.opcoes.map((o, i) =>
                        `<option value="${i}" ${parseInt(dados.resposta_correta) === i ? 'selected' : ''}>Opção ${i + 1}</option>`).join('');

                    div.innerHTML = `
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-bold text-gray-800">Questão ${total + 1}</h4>
                            <button type="button" onclick="removerQuestao(this)" class="text-red-600 hover:text-red-800 text-sm font-semibold"><i class="fas fa-trash mr-1"></i>Remover</button>
                        </div>
                        <div class="mb-3">
                            <label class="block text-gray-700 text-sm font-semibold mb-1">Pergunta *</label>
                            <textarea name="questoes[${idx}][pergunta]" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">${(dados.pergunta || '').replace(/</g, '&lt;')}</textarea>
                        </div>
                        <div class="opcoes-questao space-y-2">${opcoesHtml}</div>
                        <div class="flex items-center gap-3 mt-2">
                            <button type="button" onclick="adicionarOpcao(this)" class="text-blue-700 hover:text-blue-900 text-sm font-semibold"><i class="fas fa-plus mr-1"></i>Adicionar opção</button>
                            <div class="ml-auto flex items-center gap-2">
                                <label class="text-gray-700 text-sm font-semibold">Resposta correta:</label>
                                <select name="questoes[${idx}][resposta_correta]" class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">${selectOpcoes}</select>
                            </div>
                        </div>
                    `;
                    container.appendChild(div);
                    atualizarAvisoQuestoes();
                }

                function removerQuestao(btn) {
                    btn.closest('.questao-item').remove();
                    atualizarAvisoQuestoes();
                    document.querySelectorAll('.questao-item h4').forEach((h, i) => {
                        h.textContent = 'Questão ' + (i + 1);
                    });
                }

                function adicionarOpcao(btn) {
                    const bloco = btn.closest('.questao-item');
                    const rows = bloco.querySelectorAll('.opcao-row');
                    if (rows.length >= 4) {
                        alert('Máximo de 4 opções por questão.');
                        return;
                    }
                    const baseName = rows[0].querySelector('input').name.replace(/\[\]$/, '');
                    const row = document.createElement('div');
                    row.className = 'flex items-center gap-2 opcao-row';
                    row.innerHTML = `
                        <input type="text" name="${baseName}[]" required placeholder="Opção ${rows.length + 1}" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <button type="button" onclick="removerOpcao(this)" class="text-red-600 p-1" title="Remover opção"><i class="fas fa-times"></i></button>
                    `;
                    rows[rows.length - 1].after(row);
                    const select = bloco.querySelector('select[name$="[resposta_correta]"]');
                    const opt = document.createElement('option');
                    opt.value = String(rows.length);
                    opt.textContent = 'Opção ' + (rows.length + 1);
                    select.appendChild(opt);
                }

                function removerOpcao(btn) {
                    const bloco = btn.closest('.questao-item');
                    const rows = bloco.querySelectorAll('.opcao-row');
                    if (rows.length <= 2) {
                        alert('Mínimo de 2 opções por questão.');
                        return;
                    }
                    btn.closest('.opcao-row').remove();
                    const select = bloco.querySelector('select[name$="[resposta_correta]"]');
                    const novosRows = bloco.querySelectorAll('.opcao-row');
                    const atual = select.value;
                    select.innerHTML = '';
                    novosRows.forEach((row, i) => {
                        row.querySelector('input').placeholder = 'Opção ' + (i + 1);
                        const opt = document.createElement('option');
                        opt.value = String(i);
                        opt.textContent = 'Opção ' + (i + 1);
                        select.appendChild(opt);
                    });
                    if (parseInt(atual) < novosRows.length) select.value = atual;
                    else select.value = String(novosRows.length - 1);
                }

                window.adicionarQuestao = adicionarQuestao;
                window.removerQuestao = removerQuestao;
                window.adicionarOpcao = adicionarOpcao;
                window.removerOpcao = removerOpcao;

                document.addEventListener('DOMContentLoaded', function() {
                    (questoesExistentes || []).forEach(q => adicionarQuestao(q));
                });
            </script>

            <div class="pt-4">
                <div class="flex flex-col md:flex-row md:space-x-4 space-y-3 md:space-y-0">
                    <button type="submit" class="w-full md:flex-1 bg-green-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-save mr-2"></i>Salvar Alterações
                    </button>
                    <a href="{{ route('treinamentos.show', $training) }}" class="w-full md:flex-1 bg-gray-400 text-white font-semibold py-3 px-4 rounded-lg hover:bg-gray-500 transition text-center">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
