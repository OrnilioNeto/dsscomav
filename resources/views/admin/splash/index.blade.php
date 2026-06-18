@extends('layout')

@section('title', 'Gerenciar Conteúdo Splash')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-bullhorn text-blue-600 mr-3"></i>Conteúdos de Boas-vindas (Splash)
        </h1>
        <button onclick="openModal('modalCreate')" class="bg-green-600 text-white px-6 py-2 rounded-lg hover:bg-green-700 transition">
            <i class="fas fa-plus mr-2"></i>Novo Conteúdo
        </button>
    </div>

    <div class="bg-blue-50 border-l-4 border-blue-500 p-4 mb-8 text-blue-800 text-sm">
        <p><strong>Dica:</strong> Os conteúdos ativos e dentro do período de validade aparecerão para o usuário logo após o login. Se houver mais de um, eles serão exibidos em sequência de acordo com a ordem definida.</p>
    </div>

    <!-- Listagem -->
    <div class="bg-white rounded-lg shadow-lg overflow-hidden border border-gray-200">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-gray-100 border-b-2 border-gray-300">
                    <tr>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Ordem</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Título</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Material</th>
                        <th class="px-6 py-3 text-left text-sm font-bold text-gray-700">Período de Exibição</th>
                        <th class="px-6 py-3 text-center text-sm font-bold text-gray-700">Status</th>
                        <th class="px-6 py-3 text-right text-sm font-bold text-gray-700">Ações</th>
                    </tr>
                </thead>
                <tbody id="splash-list">
                    @forelse($contents as $content)
                        <tr class="border-b hover:bg-gray-50 transition" data-id="{{ $content->id }}">
                            <td class="px-6 py-4 text-gray-600 font-mono">#{{ $loop->iteration }}</td>
                            <td class="px-6 py-4">
                                <div class="font-bold text-gray-800">{{ $content->titulo }}</div>
                                <div class="text-xs text-gray-500 truncate max-w-xs">{{ Str::limit($content->texto_conteudo, 50) }}</div>
                            </td>
                            <td class="px-6 py-4">
                                @if($content->material_path)
                                    <a href="{{ $content->url }}" target="_blank" class="inline-flex items-center text-blue-600 hover:underline text-sm font-semibold">
                                        <i class="fas {{ $content->isPdf() ? 'fa-file-pdf text-red-500' : 'fa-image text-green-500' }} mr-2 text-lg"></i>
                                        Ver {{ strtoupper($content->material_tipo) }}
                                    </a>
                                @else
                                    <span class="text-gray-400 text-xs italic">Apenas texto</span>
                                @endif
                            </td>
                            <td class="px-6 py-4">
                                <div class="text-sm {{ now()->between($content->data_inicio, $content->data_fim) ? 'text-green-700 font-bold' : 'text-gray-600' }}">
                                    {{ $content->data_inicio->format('d/m/Y') }} até {{ $content->data_fim->format('d/m/Y') }}
                                </div>
                            </td>
                            <td class="px-6 py-4 text-center">
                                <form action="{{ route('admin.splash.toggle', $content->id) }}" method="POST">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold transition {{ $content->status === 'ativo' ? 'bg-green-100 text-green-800 border border-green-200' : 'bg-red-100 text-red-800 border border-red-200' }}">
                                        {{ strtoupper($content->status) }}
                                    </button>
                                </form>
                            </td>
                            <td class="px-6 py-4 text-right space-x-3">
                                <button onclick="fillEditModal({{ json_encode($content) }})" class="text-orange-600 hover:text-orange-900 transition" title="Alterar/Reativar">
                                    <i class="fas fa-edit"></i>
                                </button>
                                <form action="{{ route('admin.splash.destroy', $content->id) }}" method="POST" class="inline" onsubmit="return confirm('Excluir permanentemente este conteúdo?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="text-red-600 hover:text-red-900 transition">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </form>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-inbox text-4xl mb-4 block opacity-20"></i>
                                Nenhum conteúdo configurado ainda.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL: CRIAR -->
<div id="modalCreate" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="bg-blue-900 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="text-xl font-bold">Novo Conteúdo Splash</h3>
            <button onclick="closeModal('modalCreate')" class="hover:opacity-70"><i class="fas fa-times"></i></button>
        </div>
        <form action="{{ route('admin.splash.store') }}" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Título do Conteúdo *</label>
                <input type="text" name="titulo" required class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
            </div>
            
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Data Início *</label>
                    <input type="date" name="data_inicio" required value="{{ date('Y-m-d') }}" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1">Data Fim *</label>
                    <input type="date" name="data_fim" required class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Material (Imagem ou PDF)</label>
                <input type="file" name="material" accept="image/*,.pdf" class="w-full text-sm text-gray-500 file:mr-4 file:py-2 file:px-4 file:rounded-full file:border-0 file:text-sm file:font-semibold file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100">
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Texto do Conteúdo (Opcional)</label>
                <textarea name="texto_conteudo" rows="4" class="w-full rounded-lg border-gray-300 focus:ring-blue-500 focus:border-blue-500 shadow-sm" placeholder="Escreva a mensagem que aparecerá abaixo do material..."></textarea>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="submit" class="flex-1 bg-green-600 text-white font-bold py-3 rounded-lg hover:bg-green-700 transition">Salvar e Ativar</button>
                <button type="button" onclick="closeModal('modalCreate')" class="flex-1 bg-gray-200 text-gray-800 font-bold py-3 rounded-lg hover:bg-gray-300 transition">Cancelar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDITAR -->
<div id="modalEdit" class="fixed inset-0 z-50 hidden bg-black/60 backdrop-blur-sm flex items-center justify-center p-4">
    <div class="bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden">
        <div class="bg-orange-600 px-6 py-4 flex justify-between items-center text-white">
            <h3 class="text-xl font-bold">Editar / Reativar Conteúdo</h3>
            <button onclick="closeModal('modalEdit')" class="hover:opacity-70"><i class="fas fa-times"></i></button>
        </div>
        <form id="formEdit" method="POST" enctype="multipart/form-data" class="p-6 space-y-4">
            @csrf
            @method('PUT')
            
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Título do Conteúdo *</label>
                <input type="text" name="titulo" id="edit_titulo" required class="w-full rounded-lg border-gray-300 shadow-sm">
            </div>
            
            <div class="grid md:grid-cols-2 gap-4">
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1 text-orange-700">Novo Período: Início *</label>
                    <input type="date" name="data_inicio" id="edit_data_inicio" required class="w-full rounded-lg border-orange-200 bg-orange-50 shadow-sm">
                </div>
                <div>
                    <label class="block text-sm font-bold text-gray-700 mb-1 text-orange-700">Novo Período: Fim *</label>
                    <input type="date" name="data_fim" id="edit_data_fim" required class="w-full rounded-lg border-orange-200 bg-orange-50 shadow-sm">
                </div>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Substituir Material (Opcional)</label>
                <input type="file" name="material" accept="image/*,.pdf" class="w-full text-sm text-gray-500">
                <p class="text-[10px] text-gray-500 mt-1 italic">Deixe em branco para manter o arquivo atual.</p>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Status</label>
                <select name="status" id="edit_status" class="w-full rounded-lg border-gray-300">
                    <option value="ativo">Ativo</option>
                    <option value="inativo">Inativo</option>
                </select>
            </div>

            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Texto do Conteúdo</label>
                <textarea name="texto_conteudo" id="edit_texto" rows="4" class="w-full rounded-lg border-gray-300 shadow-sm"></textarea>
            </div>

            <div class="pt-4 flex gap-3">
                <button type="submit" class="flex-1 bg-orange-600 text-white font-bold py-3 rounded-lg hover:bg-orange-700 transition">Atualizar Configurações</button>
                <button type="button" onclick="closeModal('modalEdit')" class="flex-1 bg-gray-200 text-gray-800 font-bold py-3 rounded-lg hover:bg-gray-300 transition">Cancelar</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('extra_js')
<script>
    function openModal(id) {
        const modal = document.getElementById(id);
        modal.classList.remove('hidden');
        modal.classList.add('flex');
    }

    function closeModal(id) {
        const modal = document.getElementById(id);
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }

    function fillEditModal(content) {
        const form = document.getElementById('formEdit');
        // Gerar a URL correta dinamicamente substituindo o marcador
        form.action = `/admin/splash/${content.id}`;

        document.getElementById('edit_titulo').value = content.titulo;
        document.getElementById('edit_texto').value = content.texto_conteudo || '';
        document.getElementById('edit_status').value = content.status;
        
        // Formatar datas para o input type=date (YYYY-MM-DD)
        if (content.data_inicio) {
            document.getElementById('edit_data_inicio').value = content.data_inicio.split('T')[0];
        }
        if (content.data_fim) {
            document.getElementById('edit_data_fim').value = content.data_fim.split('T')[0];
        }

        openModal('modalEdit');
    }

    // Fechar modais ao clicar fora
    window.onclick = function(event) {
        if (event.target.id.startsWith('modal')) {
            closeModal(event.target.id);
        }
    }

    // Opcional: Reordenar via Drag and Drop simples (Exemplo conceitual se usar Sortable.js no futuro)
    // Aqui poderíamos adicionar a lógica de disparar a rota `admin.splash.reorder`
</script>
@endsection