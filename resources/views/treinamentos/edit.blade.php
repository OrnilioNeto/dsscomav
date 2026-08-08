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

            <div class="border-t pt-6 space-y-4">
                <h2 class="text-xl font-bold text-gray-800">Avaliação do Treinamento</h2>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Pergunta da avaliação *</label>
                    <textarea name="avaliacao_pergunta" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ $training->avaliacao_pergunta }}</textarea>
                </div>

                @php
                    $assessmentOptions = $training->avaliacao_opcoes ?? [];
                @endphp

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 1 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[0] ?? '' }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 2 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ $assessmentOptions[1] ?? '' }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
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
                    <select name="avaliacao_resposta_correta" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="0" {{ (int) $training->avaliacao_resposta_correta === 0 ? 'selected' : '' }}>Opção 1</option>
                        <option value="1" {{ (int) $training->avaliacao_resposta_correta === 1 ? 'selected' : '' }}>Opção 2</option>
                        <option value="2" {{ (int) $training->avaliacao_resposta_correta === 2 ? 'selected' : '' }}>Opção 3</option>
                        <option value="3" {{ (int) $training->avaliacao_resposta_correta === 3 ? 'selected' : '' }}>Opção 4</option>
                    </select>
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

                    function atualizarTipoConteudo() {
                        const isTreinamento = tipoSelect.value === 'treinamento';
                        blocoTipoUsuario.style.display = isTreinamento ? 'none' : 'block';
                        blocoFuncionarios.style.display = isTreinamento ? 'block' : 'none';
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
