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

            <div id="bloco-ementa">
                <label class="block text-gray-700 font-semibold mb-2">Conteúdo Programático (Ementa)</label>
                <textarea name="conteudo_programatico" rows="5" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900" placeholder="Liste os tópicos que serão abordados no treinamento (ex: conceitos, legislação aplicável, procedimentos práticos, avaliação...). Este conteúdo constará como requisito da NR-01 (1.7.1.1).">{{ old('conteudo_programatico') }}</textarea>
            </div>

            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tipo *</label>
                    <select name="tipo" id="tipo-select" required class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="treinamento" {{ old('tipo') === 'dss' ? '' : 'selected' }}>Treinamento</option>
                        <option value="dss" {{ old('tipo') === 'dss' ? 'selected' : '' }}>DSS</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Carga Horária (minutos) *</label>
                    <input type="number" name="carga_horaria" value="{{ old('carga_horaria') }}" required min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                </div>
            </div>

            <div id="bloco-tipo-treinamento" class="grid md:grid-cols-2 gap-4" style="display:none;">
                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Tipo do Treinamento (NR-01 1.7.1.2) *</label>
                    <select name="tipo_treinamento" id="tipo-treinamento-select" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="">-- Selecione --</option>
                        <option value="inicial" {{ old('tipo_treinamento') === 'inicial' ? 'selected' : '' }}>Inicial</option>
                        <option value="periodico" {{ old('tipo_treinamento') === 'periodico' ? 'selected' : '' }}>Periódico</option>
                        <option value="eventual" {{ old('tipo_treinamento') === 'eventual' ? 'selected' : '' }}>Eventual</option>
                    </select>
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Validade (dias)</label>
                    <input type="number" name="dias_validade" value="{{ old('dias_validade') }}" min="1" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900" placeholder="Ex: 365 = renovação anual">
                    <p class="text-xs text-gray-500 mt-1">Após a conclusão, o treinamento expira nesta quantidade de dias (ex: NR-06 renovada anualmente).</p>
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

            <!-- Avaliação para DSS (pergunta única) -->
            <div id="bloco-avaliacao-legada" style="display:none;" class="border-t pt-6 space-y-4">
                <h2 class="text-xl font-bold text-gray-800">Avaliação do DSS</h2>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Pergunta da avaliação *</label>
                    <textarea name="avaliacao_pergunta" rows="3" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">{{ old('avaliacao_pergunta') }}</textarea>
                </div>

                <div class="grid md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 1 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ old('avaliacao_opcoes.0') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Opção 2 *</label>
                        <input type="text" name="avaliacao_opcoes[]" value="{{ old('avaliacao_opcoes.1') }}" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
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
                    <select name="avaliacao_resposta_correta" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <option value="0">Opção 1</option>
                        <option value="1">Opção 2</option>
                        <option value="2">Opção 3</option>
                        <option value="3">Opção 4</option>
                    </select>
                </div>
            </div>

            <!-- Avaliação para Treinamento (banco de questões NR-01 4.6) -->
            <div id="bloco-banco-questoes" class="border-t pt-6">
                <div class="flex items-center justify-between mb-2">
                    <h2 class="text-xl font-bold text-gray-800">Avaliação do Treinamento (Banco de Questões)</h2>
                    <button type="button" onclick="adicionarQuestao()" class="bg-blue-900 text-white text-sm font-semibold px-4 py-2 rounded-lg hover:bg-blue-800">
                        <i class="fas fa-plus mr-1"></i>Adicionar Questão
                    </button>
                </div>
                <p class="text-sm text-gray-500 mb-4">Cadastre até 10 questões. Cada questão pode ter de <strong>2 a 4 opções</strong> e deve indicar a resposta correta. Na prova, as questões e opções são embaralhadas (NR-01 Anexo II 4.6.2).</p>

                <div id="container-questoes" class="space-y-4">
                    <p class="text-gray-500 text-sm italic" id="sem-questoes-aviso">Nenhuma questão cadastrada. Clique em "Adicionar Questão".</p>
                </div>

                <div class="grid md:grid-cols-2 gap-4 mt-4">
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Questões sorteadas na prova</label>
                        <input type="number" name="quantidade_questoes_prova" id="quantidade-questoes-prova" value="{{ old('quantidade_questoes_prova') }}" min="1" max="10" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900" placeholder="Vazio = todas as cadastradas">
                        <p class="text-xs text-gray-500 mt-1">Deixe vazio para sortear entre todas as questões cadastradas.</p>
                    </div>
                    <div>
                        <label class="block text-gray-700 font-semibold mb-2">Nota mínima p/ aprovação (%)</label>
                        <input type="number" name="nota_minima_aprovacao" value="{{ old('nota_minima_aprovacao', 70) }}" min="1" max="100" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                        <p class="text-xs text-gray-500 mt-1">Abaixo da nota = conceito <strong>insatisfatório</strong> (refaz a prova). Igual ou acima = <strong>satisfatório</strong>.</p>
                    </div>
                </div>
            </div>

            <div id="bloco-tipo-usuario">
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

            <div id="bloco-funcionarios" style="display:none;">
                <label class="block text-gray-700 font-semibold mb-2">Funcionários que assistirão</label>
                <p class="text-sm text-gray-500 mb-2">Selecione os funcionários que terão este treinamento liberado para assistir, ou deixe vazio e defina depois ao editar o treinamento. Este conteúdo é direcionado e cobrado por funcionário atribuído.</p>
                <input type="text" id="funcionarios-busca" placeholder="Buscar por nome ou CPF..." class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900 mb-3">
                <div class="max-h-64 overflow-y-auto border border-gray-200 rounded-lg p-3 space-y-2">
                    @forelse($funcionarios as $funcionario)
                        <label class="flex items-center funcionario-item">
                            <input type="checkbox" name="funcionarios[]" value="{{ $funcionario->id }}" {{ in_array($funcionario->id, old('funcionarios', [])) ? 'checked' : '' }} class="mr-2 funcionario-check">
                            <span class="text-gray-700">{{ $funcionario->nome }}</span>
                            <span class="text-xs text-gray-500 ml-2">{{ ucfirst($funcionario->tipo_usuario) }}</span>
                        </label>
                    @empty
                        <p class="text-gray-500 text-sm italic">Nenhum funcionário disponível.</p>
                    @endforelse
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
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Nome do Material (opcional)</label>
                            <input type="text" id="material-nome-create" placeholder="Ex: Manual do Motorista" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Descrição (opcional)</label>
                            <textarea id="material-descricao-create" placeholder="Descrição breve do material..." rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm"></textarea>
                        </div>
                        <div>
                            <label class="block text-gray-700 text-sm font-semibold mb-2">Arquivo (Máx. 100MB)</label>
                            <input type="file" id="material-arquivo-create" accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.gif,.zip,.rar,.txt" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-green-500 text-sm">
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
                        if (!isTreinamento && blocoTipoTreinamento) {
                            const selTipo = document.getElementById('tipo-treinamento-select');
                            if (selTipo) selTipo.required = false;
                        }
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

                    // Garante que os campos de material de apoio sejam opcionais no HTML5
                    const materialNomeInput = document.getElementById('material-nome-create');
                    const materialArquivoInput = document.getElementById('material-arquivo-create');
                    if (materialNomeInput) materialNomeInput.removeAttribute('required');
                    if (materialArquivoInput) materialArquivoInput.removeAttribute('required');

                    // Interceptar submit do formulário de treinamento
                    const treinamentoForm = document.getElementById('treinamento-form');
                    treinamentoForm.addEventListener('submit', async function(e) {
                        e.preventDefault();

                        // Se o usuário selecionou um arquivo mas não clicou em "Adicionar Material",
                        // adiciona automaticamente para não perder o material no envio final.
                        autoAddPendingMaterial();

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

                            const responseText = await response.text();

                            if (response.ok) {
                                // Se o backend devolveu a própria tela de cadastro com erros,
                                // renderizar a resposta HTML para que a validação fique visível.
                                const responsePath = new URL(response.url).pathname;
                                if (responsePath === window.location.pathname) {
                                    document.open();
                                    document.write(responseText);
                                    document.close();
                                    return;
                                }

                                // Redirecionar após criação bem-sucedida
                                const redirectUrl = response.url || '{{ route("treinamentos.index") }}';
                                window.location.href = redirectUrl;
                            } else {
                                alert('Erro ao criar treinamento. Verifique os dados e tente novamente.');
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
                        const nomeFinal = (nome || '').trim() || (arquivo.name || 'Material');

                        const materialTemp = {
                            id: 'temp_' + Date.now(),
                            nome: nomeFinal,
                            descricao: descricao,
                            arquivo: arquivo,
                            tamanho: arquivo.size,
                            tipo: arquivo.name.split('.').pop().toLowerCase()
                        };

                        materiaisTempStorage.push(materialTemp);

                        // Atualizar lista visual
                        updateListaMateriais();

                        // Limpar campos do material sem depender de reset() em um <div>
                        document.getElementById('material-nome-create').value = '';
                        document.getElementById('material-descricao-create').value = '';
                        document.getElementById('material-arquivo-create').value = '';
                        feedback.innerHTML = '<div class="text-green-600 text-sm"><i class="fas fa-check mr-1"></i>Material adicionado! Será enviado ao criar o treinamento.</div>';

                        setTimeout(() => feedback.innerHTML = '', 3000);
                    });

                    function autoAddPendingMaterial() {
                        const nomeEl = document.getElementById('material-nome-create');
                        const descricaoEl = document.getElementById('material-descricao-create');
                        const arquivoEl = document.getElementById('material-arquivo-create');

                        if (!arquivoEl || !arquivoEl.files || !arquivoEl.files[0]) {
                            return;
                        }

                        const arquivo = arquivoEl.files[0];
                        const nome = (nomeEl?.value || '').trim() || (arquivo.name || 'Material');
                        const descricao = descricaoEl?.value || '';

                        materiaisTempStorage.push({
                            id: 'temp_' + Date.now(),
                            nome,
                            descricao,
                            arquivo,
                            tamanho: arquivo.size,
                            tipo: arquivo.name.split('.').pop().toLowerCase()
                        });

                        updateListaMateriais();

                        if (nomeEl) nomeEl.value = '';
                        if (descricaoEl) descricaoEl.value = '';
                        if (arquivoEl) arquivoEl.value = '';
                    }
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

            <script>
                let contadorQuestoes = 0;

                function atualizarAvisoQuestoes() {
                    const aviso = document.getElementById('sem-questoes-aviso');
                    const total = document.querySelectorAll('.questao-item').length;
                    if (aviso) aviso.style.display = total === 0 ? 'block' : 'none';
                    const inputQtd = document.getElementById('quantidade-questoes-prova');
                    if (inputQtd && inputQtd.value && parseInt(inputQtd.value) > total) {
                        inputQtd.value = total || '';
                    }
                }

                function adicionarQuestao() {
                    const container = document.getElementById('container-questoes');
                    const total = document.querySelectorAll('.questao-item').length;
                    if (total >= 10) {
                        alert('Máximo de 10 questões por treinamento.');
                        return;
                    }

                    const idx = contadorQuestoes++;
                    const div = document.createElement('div');
                    div.className = 'questao-item bg-gray-50 border border-gray-200 rounded-lg p-4';
                    div.innerHTML = `
                        <div class="flex items-start justify-between mb-3">
                            <h4 class="font-bold text-gray-800">Questão ${total + 1}</h4>
                            <button type="button" onclick="removerQuestao(this)" class="text-red-600 hover:text-red-800 text-sm font-semibold"><i class="fas fa-trash mr-1"></i>Remover</button>
                        </div>
                        <div class="mb-3">
                            <label class="block text-gray-700 text-sm font-semibold mb-1">Pergunta *</label>
                            <textarea name="questoes[${idx}][pergunta]" rows="2" required class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900"></textarea>
                        </div>
                        <div class="opcoes-questao space-y-2">
                            <div class="flex items-center gap-2 opcao-row">
                                <input type="text" name="questoes[${idx}][opcoes][]" required placeholder="Opção 1" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                                <button type="button" onclick="removerOpcao(this)" class="text-red-600 p-1" title="Remover opção"><i class="fas fa-times"></i></button>
                            </div>
                            <div class="flex items-center gap-2 opcao-row">
                                <input type="text" name="questoes[${idx}][opcoes][]" required placeholder="Opção 2" class="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                                <button type="button" onclick="removerOpcao(this)" class="text-red-600 p-1" title="Remover opção"><i class="fas fa-times"></i></button>
                            </div>
                        </div>
                        <div class="flex items-center gap-3 mt-2">
                            <button type="button" onclick="adicionarOpcao(this)" class="text-blue-700 hover:text-blue-900 text-sm font-semibold"><i class="fas fa-plus mr-1"></i>Adicionar opção</button>
                            <div class="ml-auto flex items-center gap-2">
                                <label class="text-gray-700 text-sm font-semibold">Resposta correta:</label>
                                <select name="questoes[${idx}][resposta_correta]" required class="px-3 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900">
                                    <option value="0">Opção 1</option>
                                    <option value="1">Opção 2</option>
                                </select>
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
            </script>
        </form>
    </div>
</div>
@endsection
