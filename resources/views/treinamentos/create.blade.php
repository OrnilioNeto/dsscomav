@extends('layout')

@section('title', 'Novo Treinamento')

@section('content')
<div class="max-w-2xl mx-auto px-4 py-8">
    <h1 class="text-3xl font-bold text-gray-800 mb-8">
        <i class="fas fa-plus text-blue-900 mr-3"></i>Criar Novo Treinamento
    </h1>

    <div class="bg-white p-6 sm:p-8 rounded-lg shadow-lg">
        <form id="treinamento-form" action="{{ route('treinamentos.store') }}" method="POST" class="space-y-6">
            @csrf

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Título *</label>
                <input type="text" name="titulo" value="{{ old('titulo') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Descrição</label>
                <textarea name="descricao" rows="4" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('descricao') }}</textarea>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tipo *</label>
                    <select name="tipo" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="treinamento">Treinamento</option>
                        <option value="dss">DSS</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Carga Horária (minutos) *</label>
                    <input type="number" name="carga_horaria" value="{{ old('carga_horaria') }}" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tipo de Vídeo *</label>
                    <select name="tipo_video" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="youtube">YouTube</option>
                        <option value="vimeo">Vimeo</option>
                        <option value="upload">Upload Local</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">URL do Vídeo *</label>
                    <input type="url" name="url_video" value="{{ old('url_video') }}" required placeholder="https://..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <div class="border-t pt-6 space-y-4">
                <h2 class="text-xl font-bold text-gray-800">Avaliação do Treinamento</h2>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Pergunta da avaliação *</label>
                    <textarea name="avaliacao_pergunta" rows="3" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('avaliacao_pergunta') }}</textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 1 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ old('avaliacao_opcoes.0') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 2 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ old('avaliacao_opcoes.1') }}" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 3</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ old('avaliacao_opcoes.2') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 4</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ old('avaliacao_opcoes.3') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Resposta correta *</label>
                    <select name="avaliacao_resposta_correta" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="0">Opção 1</option>
                        <option value="1">Opção 2</option>
                        <option value="2">Opção 3</option>
                        <option value="3">Opção 4</option>
                    </select>
                </div>
            </div>

            <div>
                <label class="block text-gray-700 font-semibold mb-2">Permitido para *</label>
                <div class="space-y-2">
                    <label class="flex items-center">
                        <input type="checkbox" name="tipo_usuario_permitido[]" value="motorista" class="mr-2">
                        <span class="text-gray-700">Motoristas</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="tipo_usuario_permitido[]" value="funcionario" class="mr-2">
                        <span class="text-gray-700">Funcionários</span>
                    </label>
                    <label class="flex items-center">
                        <input type="checkbox" name="tipo_usuario_permitido[]" value="terceirizado" class="mr-2">
                        <span class="text-gray-700">Terceirizados</span>
                    </label>
                </div>
            </div>

            <div class="border-t pt-6">
                <h2 class="text-xl font-bold text-gray-800 mb-4">
                    <i class="fas fa-file-download mr-2 text-green-600"></i>Materiais de Apoio (Opcional)
                </h2>

                <!-- Aviso -->
                <p class="text-sm text-gray-600 mb-4">Adicione PDFs, imagens ou outros arquivos que servirão como material de suporte para os usuários durante o treinamento.</p>

                <!-- Upload de novo material -->
                <div class="bg-blue-50 p-4 rounded-lg border border-blue-200 mb-4">
                    <h3 class="font-semibold text-gray-800 mb-3">Carregar materiais</h3>
                    <div id="material-upload-form-create" class="space-y-3">
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Nome do Material *</label>
                            <input type="text" id="material-nome-create" placeholder="Ex: Manual do Motorista" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm" required>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Descrição (opcional)</label>
                            <textarea id="material-descricao-create" placeholder="Descrição breve do material..." rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Arquivo * (Máx. 100MB)</label>
                            <input type="file" id="material-arquivo-create" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar,.txt" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm" required>
                        </div>
                        <button type="button" id="material-adicionar-create" class="w-full bg-green-600 text-white font-semibold py-2 px-4 rounded-lg hover:bg-green-700 transition text-sm">
                            <i class="fas fa-upload mr-2"></i>Adicionar Material
                        </button>
                    </div>
                    <div id="upload-feedback-create" class="mt-2"></div>
                </div>

                <!-- Lista de materiais em preparação -->
                <div id="materiais-container-create">
                    <p class="text-gray-500 text-sm italic">Nenhum material adicionado ainda.</p>
                </div>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="flex items-center">
                        <input type="checkbox" name="obrigatorio" value="1" {{ old('obrigatorio') ? 'checked' : '' }} class="mr-2">
                        <span class="text-gray-700 font-semibold">Treinamento Obrigatório</span>
                    </label>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Status</label>
                    <select name="status" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="ativo">Ativo</option>
                        <option value="inativo">Inativo</option>
                    </select>
                </div>
            </div>

            <div class="mt-4">
                <label class="block text-gray-700 font-semibold mb-2">Liberar Conteúdo (data e hora - fuso: Brasil UTC-3)</label>
                <div class="flex gap-2 items-center">
                    <input type="datetime-local" id="data-liberacao-local" name="data_liberacao" class="px-4 py-2 border border-gray-300 rounded-lg" placeholder="DD/MM/AAAA HH:MM">
                    <p class="text-sm text-gray-500">Hora de São Paulo.</p>
                </div>
            </div>

            <div class="pt-4">
                <div class="flex flex-col md:flex-row md:space-x-4 space-y-3 md:space-y-0">
                    <button type="submit" class="w-full md:flex-1 bg-green-600 text-white font-semibold py-3 px-4 rounded-lg hover:bg-green-700 transition">
                        <i class="fas fa-save mr-2"></i>Criar Treinamento
                    </button>
                    <a href="{{ route('treinamentos.index') }}" class="w-full md:flex-1 bg-gray-400 text-white font-semibold py-3 px-4 rounded-lg hover:bg-gray-500 transition text-center">
                        <i class="fas fa-times mr-2"></i>Cancelar
                    </a>
                </div>
            </div>

            <script>
                const materiaisTempStorage = [];

                document.addEventListener('DOMContentLoaded', function() {
                    // Interceptar submit do formulário de treinamento
                    const treinamentoForm = document.getElementById('treinamento-form');
                    treinamentoForm.addEventListener('submit', async function(e) {
                        e.preventDefault();

                        const submitBtn = treinamentoForm.querySelector('button[type="submit"]');
                        const originalText = submitBtn.innerHTML;
                        submitBtn.disabled = true;
                        submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i>Criando...';

                        try {
                            // FormData coleta automaticamente todos os inputs, incluindo data_liberacao
                            const formData = new FormData(treinamentoForm);

                            // Adicionar ficheiros de materiais
                            materiaisTempStorage.forEach((material, index) => {
                                formData.append(`materiais[${index}][nome]`, material.nome);
                                formData.append(`materiais[${index}][descricao]`, material.descricao);
                                formData.append(`materiais[${index}][arquivo]`, material.arquivo);
                            });

                            // Enviar via fetch (para compatibilidade com ficheiros)
                            const response = await fetch(treinamentoForm.action, {
                                method: 'POST',
                                body: formData,
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest',
                                }
                            });

                            if (response.ok) {
                                // Redirecionar ou mostrar sucesso
                                const redirectUrl = response.url || '{{ route("treinamentos.index") }}';
                                window.location.href = redirectUrl;
                            } else {
                                const text = await response.text();
                                if (text.includes('error') || text.includes('Error')) {
                                    alert('Erro ao criar treinamento. Verifique os dados e tente novamente.');
                                }
                                submitBtn.disabled = false;
                                submitBtn.innerHTML = originalText;
                            }
                        } catch (error) {
                            console.error('Erro:', error);
                            alert('Erro ao enviar: ' + error.message);
                            submitBtn.disabled = false;
                            submitBtn.innerHTML = originalText;
                        }
                    });

                    // Formulário de upload de materiais
                    const addMaterialBtn = document.getElementById('material-adicionar-create');
                    const form = document.getElementById('material-upload-form-create');
                    if (!addMaterialBtn || !form) return;

                    addMaterialBtn.addEventListener('click', function(e) {
                        e.preventDefault();

                        const nome = document.getElementById('material-nome-create').value;
                        const descricao = document.getElementById('material-descricao-create').value;
                        const arquivo = document.getElementById('material-arquivo-create').files[0];
                        const feedback = document.getElementById('upload-feedback-create');

                        if (!arquivo) {
                            feedback.innerHTML = '<div class="text-red-600 text-sm">✗ Por favor, selecione um arquivo</div>';
                            return;
                        }

                        // Armazenar material temporariamente no array
                        const materialTemp = {
                            id: 'temp_' + Date.now(),
                            nome: nome,
                            descricao: descricao,
                            arquivo: arquivo,
                            tamanho: arquivo.size,
                            tipo: arquivo.name.split('.').pop().toLowerCase()
                        };

                        materiaisTempStorage.push(materialTemp);

                        // Atualizar lista visual
                        updateListaMateriais();

                        // Limpar formulário
                        form.reset();
                        feedback.innerHTML = '<div class="text-green-600 text-sm"><i class="fas fa-check mr-1"></i>Material adicionado! Será enviado ao criar o treinamento.</div>';

                        setTimeout(() => feedback.innerHTML = '', 3000);
                    });
                });

                function getIconeArquivo(extensao) {
                    const iconesMap = {
                        'pdf': 'fa-file-pdf text-red-600',
                        'doc': 'fa-file-word text-blue-600',
                        'docx': 'fa-file-word text-blue-600',
                        'xls': 'fa-file-excel text-green-600',
                        'xlsx': 'fa-file-excel text-green-600',
                        'jpg': 'fa-file-image text-purple-600',
                        'jpeg': 'fa-file-image text-purple-600',
                        'png': 'fa-file-image text-purple-600',
                        'gif': 'fa-file-image text-purple-600',
                        'zip': 'fa-file-archive text-yellow-600',
                        'rar': 'fa-file-archive text-yellow-600',
                        'txt': 'fa-file-text text-gray-600'
                    };
                    return iconesMap[extensao] || 'fa-file text-gray-600';
                }

                function formatarTamanho(bytes) {
                    if (bytes === 0) return '0 B';
                    const k = 1024;
                    const sizes = ['B', 'KB', 'MB', 'GB'];
                    const i = Math.floor(Math.log(bytes) / Math.log(k));
                    return Math.round(bytes / Math.pow(k, i) * 100) / 100 + ' ' + sizes[i];
                }

                function updateListaMateriais() {
                    const container = document.getElementById('materiais-container-create');

                    if (materiaisTempStorage.length === 0) {
                        container.innerHTML = '<p class="text-gray-500 text-sm italic">Nenhum material adicionado ainda.</p>';
                        return;
                    }

                    let html = '<h3 class="font-semibold text-gray-800 mb-3">Materiais adicionados</h3><ul class="space-y-2">';

                    materiaisTempStorage.forEach(material => {
                        const icone = getIconeArquivo(material.tipo);
                        html += `
                            <li class="flex items-center justify-between bg-white p-3 rounded border border-gray-200" data-material-id="${material.id}">
                                <div class="flex items-center gap-3 flex-1">
                                    <i class="fas ${icone} text-lg"></i>
                                    <div class="flex-1">
                                        <p class="font-semibold text-gray-800 text-sm">${material.nome}</p>
                                        ${material.descricao ? `<p class="text-gray-600 text-xs">${material.descricao}</p>` : ''}
                                        <p class="text-gray-500 text-xs">${formatarTamanho(material.tamanho)}</p>
                                    </div>
                                </div>
                                <button type="button" class="delete-material-temp bg-red-600 text-white px-3 py-2 rounded text-xs hover:bg-red-700" data-material-id="${material.id}">
                                    <i class="fas fa-trash mr-1"></i>Remover
                                </button>
                            </li>
                        `;
                    });

                    html += '</ul>';
                    container.innerHTML = html;

                    // Adicionar listeners aos botões de deletar
                    document.querySelectorAll('.delete-material-temp').forEach(btn => {
                        btn.addEventListener('click', function(e) {
                            e.preventDefault();
                            const materialId = this.getAttribute('data-material-id');
                            const index = materiaisTempStorage.findIndex(m => m.id === materialId);
                            if (index > -1) {
                                materiaisTempStorage.splice(index, 1);
                                updateListaMateriais();
                            }
                        });
                    });
                }
            </script>
        </form>
    </div>
</div>
@endsection
