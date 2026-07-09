@extends('layout')

@section('title', 'Feed Social')

@section('content')
<div class="max-w-7xl mx-auto px-4 py-8">
    
    <!-- Título principal -->
    <div class="flex items-center justify-between mb-8">
        <div>
            <h1 class="text-4xl font-bold text-gray-800">
                <i class="fas fa-hashtag text-orange-500 mr-2"></i>Rede Social
            </h1>
            <p class="text-gray-600 mt-1">Conecte-se com seus colegas, compartilhe seu dia a dia e celebre suas conquistas.</p>
        </div>
        <a href="{{ route('dashboard') }}" class="bg-gray-500 text-white px-5 py-2 rounded-lg hover:bg-gray-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
    </div>

    <div class="grid lg:grid-cols-3 gap-8">
        
        <!-- Coluna Esquerda e Central (Feed e Postagem) -->
        <div class="lg:col-span-2 space-y-6">
            
            <!-- Caixa de Criação de Postagem -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <div class="flex items-start gap-4">
                    <img src="{{ auth()->user()->getFotoPerfilUrl() }}" alt="{{ auth()->user()->nome }}" class="w-12 h-12 rounded-full object-cover border-2 border-blue-900">
                    <div class="flex-1">
                        <form action="{{ route('social.posts.store') }}" method="POST" enctype="multipart/form-data" class="space-y-4">
                            @csrf
                            <textarea 
                                name="caption" 
                                rows="3" 
                                placeholder="No que você está pensando, {{ explode(' ', auth()->user()->nome)[0] }}? Compartilhe uma foto de uma base, estrada, paisagem..." 
                                class="w-full border-0 focus:ring-0 resize-none text-gray-700 placeholder-gray-400 text-base"
                                required
                            ></textarea>

                            <!-- Preview da Imagem Selecionada -->
                            <div id="image-preview-container" class="hidden relative rounded-xl overflow-hidden bg-gray-50 border border-gray-200">
                                <img id="image-preview" src="#" alt="Preview" class="max-h-80 w-full object-contain mx-auto">
                                <button type="button" id="remove-preview-btn" class="absolute top-2 right-2 bg-red-600 text-white w-8 h-8 rounded-full flex items-center justify-center hover:bg-red-700 shadow transition">
                                    <i class="fas fa-times"></i>
                                </button>
                            </div>

                            <div class="flex flex-wrap items-center justify-between gap-4 pt-3 border-t border-gray-100">
                                <div class="flex items-center gap-3">
                                    <!-- Botão Upload Foto -->
                                    <label class="cursor-pointer flex items-center gap-2 text-gray-600 hover:text-orange-500 transition text-sm font-semibold">
                                        <input type="file" name="photo" id="photo-input" accept="image/*" class="hidden">
                                        <i class="fas fa-camera text-xl text-orange-500"></i>
                                        <span>Adicionar Foto</span>
                                    </label>
                                    
                                    <!-- Input Localização -->
                                    <div class="flex items-center gap-1.5 text-gray-600 border border-gray-200 rounded-full px-3 py-1 text-xs">
                                        <i class="fas fa-map-marker-alt text-red-500"></i>
                                        <input 
                                            type="text" 
                                            name="location" 
                                            placeholder="Local (ex: Base X)" 
                                            class="border-0 p-0 focus:ring-0 w-28 bg-transparent text-xs text-gray-700"
                                        >
                                    </div>
                                </div>
                                <button type="submit" class="bg-blue-900 text-white font-bold px-6 py-2 rounded-lg hover:bg-blue-800 transition shadow-sm text-sm">
                                    Publicar
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <!-- Abas do Feed (Meu Feed / Explorar) -->
            <div class="flex border-b border-gray-200">
                <a 
                    href="{{ route('social.feed', ['tab' => 'meu_feed']) }}" 
                    class="flex-1 py-3 text-center font-bold text-sm border-b-2 transition {{ $tab === 'meu_feed' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                >
                    <i class="fas fa-stream mr-2"></i>Meu Feed
                </a>
                <a 
                    href="{{ route('social.feed', ['tab' => 'explorar']) }}" 
                    class="flex-1 py-3 text-center font-bold text-sm border-b-2 transition {{ $tab === 'explorar' ? 'border-orange-500 text-orange-500' : 'border-transparent text-gray-500 hover:text-gray-700' }}"
                >
                    <i class="fas fa-globe mr-2"></i>Explorar
                </a>
            </div>

            <!-- Listagem de Posts -->
            <div class="space-y-6">
                @forelse($posts as $post)
                    <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden" id="post-card-{{ $post->id }}">
                        
                        <!-- Cabeçalho do Post -->
                        <div class="p-4 flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <a href="{{ route('social.user.profile', $post->user->id) }}" class="flex-shrink-0">
                                    <img src="{{ $post->user->getFotoPerfilUrl() }}" alt="{{ $post->user->nome }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                                </a>
                                <div>
                                    <div class="flex items-center gap-1.5">
                                        <a href="{{ route('social.user.profile', $post->user->id) }}" class="font-bold text-gray-800 hover:underline text-sm sm:text-base">
                                            {{ $post->user->nome }}
                                        </a>
                                        <span class="text-xs text-gray-400 capitalize">• {{ $post->user->tipo_usuario }}</span>
                                    </div>
                                    <div class="flex items-center gap-2 text-xs text-gray-500 mt-0.5">
                                        <span>{{ $post->created_at->diffForHumans() }}</span>
                                        @if($post->location)
                                            <span class="text-red-500">•</span>
                                            <span class="flex items-center text-gray-600 font-medium">
                                                <i class="fas fa-map-marker-alt text-red-500 mr-0.5"></i>{{ $post->location }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>

                            <!-- Ações no Post (Excluir) -->
                            <div class="flex items-center gap-2">
                                <!-- Botão Seguir Dinâmico (caso não seja o próprio usuário e não o siga ainda) -->
                                @if($post->user_id !== auth()->id())
                                    @php $isFollowing = auth()->user()->isFollowing($post->user_id); @endphp
                                    <button 
                                        type="button" 
                                        onclick="toggleFollow(this, {{ $post->user_id }})" 
                                        class="text-xs font-bold px-3 py-1 rounded-full border transition {{ $isFollowing ? 'bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200' : 'bg-orange-500 text-white border-orange-500 hover:bg-orange-600 shadow-sm' }}"
                                    >
                                        {{ $isFollowing ? 'Seguindo' : 'Seguir' }}
                                    </button>
                                @endif

                                @if($post->user_id === auth()->id() || auth()->user()->isAdmin())
                                    <form action="{{ route('social.posts.destroy', $post->id) }}" method="POST" onsubmit="return confirm('Tem certeza que deseja excluir esta publicação?')" class="inline">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="text-gray-400 hover:text-red-600 p-1.5 rounded-full hover:bg-red-50 transition" title="Excluir Post">
                                            <i class="fas fa-trash-alt text-sm"></i>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>

                        <!-- Conteúdo do Post (Foto de Conquista ou Foto Normal) -->
                        @if($post->isTrainingPost())
                            <!-- Card Estilizado de Conquista -->
                            <div class="p-6 bg-gradient-to-br from-[#153B2E] via-[#0F2B22] to-[#153B2E] text-white text-center relative overflow-hidden">
                                <!-- Elementos Decorativos -->
                                <div class="absolute -right-16 -top-16 w-48 h-48 rounded-full bg-orange-500/10 blur-xl"></div>
                                <div class="absolute -left-16 -bottom-16 w-48 h-48 rounded-full bg-blue-500/10 blur-xl"></div>

                                <div class="relative z-10 space-y-4 py-4">
                                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-orange-500/20 text-orange-400 border border-orange-500/30 mb-2 animate-bounce">
                                        <i class="fas fa-trophy text-3xl"></i>
                                    </div>
                                    <div>
                                        <span class="text-xs font-bold tracking-widest text-orange-400 bg-orange-950/50 px-3 py-1 rounded-full border border-orange-500/20 uppercase">
                                            Conquista Confirmada
                                        </span>
                                    </div>
                                    <h3 class="text-2xl font-bold max-w-xl mx-auto px-4 mt-2">
                                        Concluiu o treinamento: <span class="text-yellow-300 block mt-1 text-xl sm:text-2xl">{{ $post->training->titulo }}</span>
                                    </h3>
                                    
                                    <!-- Painel com Ranking e Dados -->
                                    <div class="grid grid-cols-2 gap-4 max-w-md mx-auto pt-4">
                                        <div class="bg-white/5 border border-white/10 rounded-xl p-3 shadow-inner">
                                            <p class="text-xs text-gray-400 font-semibold uppercase">Pontuação Atual</p>
                                            <p class="text-xl font-black text-orange-400 mt-1">
                                                {{ $post->training_score !== null ? number_format($post->training_score, 1) : 'Calculando...' }}
                                            </p>
                                        </div>
                                        <div class="bg-white/5 border border-white/10 rounded-xl p-3 shadow-inner">
                                            <p class="text-xs text-gray-400 font-semibold uppercase">Posição no Ranking</p>
                                            <p class="text-xl font-black text-yellow-300 mt-1">
                                                {{ $post->ranking_position !== null ? "#{$post->ranking_position}" : 'Sem ranking' }}
                                            </p>
                                        </div>
                                    </div>

                                    <div class="text-xs text-emerald-400/80 pt-2 flex items-center justify-center gap-1">
                                        <i class="fas fa-shield-alt text-emerald-400"></i>
                                        <span>Emitido de forma oficial pela Plataforma DSS</span>
                                    </div>
                                </div>
                            </div>
                        @elseif($post->photo_path)
                            <!-- Imagem da Postagem -->
                            <div class="bg-gray-50 border-y border-gray-100 flex items-center justify-center overflow-hidden">
                                <img src="{{ asset('uploads/social/' . $post->photo_path) }}" alt="Post de {{ $post->user->nome }}" class="w-full object-cover max-h-[500px]">
                            </div>
                        @endif

                        <!-- Corpo (Legenda e Curtidas) -->
                        <div class="p-4 space-y-3">
                            <!-- Ações Rápidas (Curtir / Comentar) -->
                            <div class="flex items-center gap-4">
                                @php $hasLiked = $post->isLikedBy(auth()->id()); @endphp
                                <button 
                                    type="button" 
                                    onclick="toggleLike(this, {{ $post->id }})" 
                                    class="flex items-center gap-1.5 group text-sm font-semibold transition focus:outline-none"
                                >
                                    <i class="fa-heart text-xl transition-all duration-200 {{ $hasLiked ? 'fas text-red-500 scale-110' : 'far text-gray-500 group-hover:text-red-500' }}"></i>
                                    <span class="likes-count text-gray-700" id="likes-count-{{ $post->id }}">{{ $post->likes->count() }}</span>
                                </button>
                                
                                <button 
                                    type="button" 
                                    onclick="toggleCommentsSection({{ $post->id }})" 
                                    class="flex items-center gap-1.5 text-gray-500 hover:text-blue-600 text-sm font-semibold transition"
                                >
                                    <i class="far fa-comment text-xl"></i>
                                    <span>{{ $post->comments->count() }}</span>
                                </button>
                            </div>

                            <!-- Legenda -->
                            @if($post->caption)
                                <div class="text-gray-800 text-sm sm:text-base leading-relaxed">
                                    <strong class="font-bold text-gray-900 mr-1">{{ $post->user->nome }}</strong>
                                    {{ $post->caption }}
                                </div>
                            @endif

                            <!-- Seção de Comentários (Visível ou recolhida) -->
                            <div class="comments-section mt-4 pt-4 border-t border-gray-50 space-y-3" id="comments-section-{{ $post->id }}">
                                
                                <!-- Lista de Comentários -->
                                <div class="comments-list space-y-3 max-h-60 overflow-y-auto pr-1" id="comments-list-{{ $post->id }}">
                                    @foreach($post->comments as $comment)
                                        <div class="flex items-start gap-2.5 text-sm">
                                            <a href="{{ route('social.user.profile', $comment->user->id) }}" class="flex-shrink-0 mt-0.5">
                                                <img src="{{ $comment->user->getFotoPerfilUrl() }}" alt="{{ $comment->user->nome }}" class="w-7 h-7 rounded-full object-cover">
                                            </a>
                                            <div class="flex-1 bg-gray-50 rounded-xl p-2.5">
                                                <div class="flex items-center justify-between gap-2">
                                                    <a href="{{ route('social.user.profile', $comment->user->id) }}" class="font-bold text-gray-800 hover:underline">
                                                        {{ $comment->user->nome }}
                                                    </a>
                                                    <span class="text-[10px] text-gray-400 font-medium">{{ $comment->created_at->diffForHumans() }}</span>
                                                </div>
                                                <p class="text-gray-700 mt-1 leading-normal">{{ $comment->content }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                <!-- Formulário para Adicionar Comentário -->
                                <form onsubmit="submitComment(event, {{ $post->id }})" class="flex items-center gap-2 pt-2">
                                    @csrf
                                    <input 
                                        type="text" 
                                        placeholder="Escreva um comentário..." 
                                        class="flex-1 rounded-full border-gray-200 text-xs sm:text-sm focus:border-orange-500 focus:ring-orange-500 bg-gray-50"
                                        required
                                        id="comment-input-{{ $post->id }}"
                                    >
                                    <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white rounded-full w-8 h-8 flex items-center justify-center hover:shadow transition flex-shrink-0">
                                        <i class="fas fa-paper-plane text-xs"></i>
                                    </button>
                                </form>
                            </div>
                        </div>

                    </div>
                @empty
                    <div class="bg-white rounded-xl shadow-md p-8 text-center border border-gray-100">
                        <div class="w-16 h-16 bg-gray-100 text-gray-400 rounded-full flex items-center justify-center mx-auto mb-4">
                            <i class="fas fa-photo-video text-2xl"></i>
                        </div>
                        <h3 class="text-lg font-bold text-gray-800">Nenhuma postagem encontrada</h3>
                        <p class="text-gray-500 mt-2 text-sm max-w-md mx-auto">
                            @if($tab === 'meu_feed')
                                Comece a seguir outros colaboradores ou publique a sua primeira foto/conquista para ver postagens aqui!
                            @else
                                Seja o primeiro a publicar algo interessante no feed!
                            @endif
                        </p>
                    </div>
                @endforelse
            </div>
            
        </div>

        <!-- Coluna Direita (Sugestões de Seguidores) -->
        <div class="lg:col-span-1 space-y-6">
            
            <!-- Perfil Rápido do Usuário Logado -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100 text-center">
                <img src="{{ auth()->user()->getFotoPerfilUrl() }}" alt="{{ auth()->user()->nome }}" class="w-20 h-20 rounded-full object-cover border-4 border-orange-500/20 mx-auto shadow-sm">
                <h3 class="font-bold text-gray-800 text-lg mt-3">{{ auth()->user()->nome }}</h3>
                <p class="text-xs text-gray-400 capitalize mt-0.5">{{ auth()->user()->tipo_usuario }}</p>
                
                <div class="grid grid-cols-2 gap-4 mt-6 pt-4 border-t border-gray-100">
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold">Seguidores</p>
                        <p class="text-lg font-bold text-blue-900 mt-0.5" id="my-followers-count">{{ auth()->user()->followersCount() }}</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400 uppercase font-semibold">Seguindo</p>
                        <p class="text-lg font-bold text-blue-900 mt-0.5" id="my-following-count">{{ auth()->user()->followingCount() }}</p>
                    </div>
                </div>
            </div>

            <!-- Sugestões de pessoas para seguir -->
            <div class="bg-white rounded-xl shadow-md p-6 border border-gray-100">
                <h3 class="font-bold text-gray-800 mb-4 flex items-center">
                    <i class="fas fa-user-plus text-orange-500 mr-2"></i>Sugestões de contatos
                </h3>
                
                <div class="space-y-4">
                    @forelse($suggestions as $suggested)
                        <div class="flex items-center justify-between gap-3 pb-3 border-b border-gray-50 last:border-0 last:pb-0">
                            <div class="flex items-center gap-2">
                                <a href="{{ route('social.user.profile', $suggested->id) }}" class="flex-shrink-0">
                                    <img src="{{ $suggested->getFotoPerfilUrl() }}" alt="{{ $suggested->nome }}" class="w-9 h-9 rounded-full object-cover border">
                                </a>
                                <div class="min-w-0">
                                    <a href="{{ route('social.user.profile', $suggested->id) }}" class="text-xs font-bold text-gray-800 hover:underline block truncate">
                                        {{ $suggested->nome }}
                                    </a>
                                    <span class="text-[10px] text-gray-400 block capitalize">{{ $suggested->tipo_usuario }}</span>
                                </div>
                            </div>
                            <button 
                                type="button" 
                                onclick="toggleFollow(this, {{ $suggested->id }})" 
                                class="bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold py-1 px-3.5 rounded-full transition shadow-sm"
                            >
                                Seguir
                            </button>
                        </div>
                    @empty
                        <p class="text-xs text-gray-400 italic">Você já segue todos os colaboradores da plataforma!</p>
                    @endforelse
                </div>
            </div>

            <div class="bg-gray-100 rounded-xl p-5 border border-gray-200 text-xs text-gray-600 space-y-2">
                <h4 class="font-bold text-gray-800 flex items-center">
                    <i class="fas fa-shield-alt text-blue-900 mr-1.5"></i>Rede Segura & Saudável
                </h4>
                <p>Este espaço é destinado ao compartilhamento profissional e incentivo às metas de saúde e segurança da empresa.</p>
                <p>Evite postar conteúdos pessoais inadequados ou em horários inapropriados.</p>
            </div>
        </div>

    </div>
</div>

<!-- Modal de Compartilhamento de Treinamento (Se acionado via URL) -->
@if($sharedTraining)
<div id="share-training-modal" class="fixed inset-0 z-50 flex items-center justify-center bg-black/70 px-4">
    <div class="w-full max-w-xl rounded-2xl bg-white p-6 shadow-2xl animate-fade-in border border-gray-200">
        
        <div class="mb-4 flex items-center justify-between border-b pb-3">
            <h2 class="text-xl font-bold text-gray-800">
                <i class="fas fa-share-alt text-orange-500 mr-1.5"></i>Compartilhar no Feed
            </h2>
            <button type="button" onclick="closeShareModal()" class="text-gray-500 hover:text-gray-800">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form action="{{ route('social.posts.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="training_id" value="{{ $sharedTraining->id }}">
            <input type="hidden" name="location" value="Plataforma DSS - Treinamentos">

            <!-- Card de Visualização do Post de Conquista -->
            <div class="bg-gradient-to-br from-[#153B2E] to-[#0F2B22] text-white rounded-xl p-5 text-center relative overflow-hidden shadow-md">
                <div class="relative z-10 space-y-2.5">
                    <div class="inline-flex items-center justify-center w-12 h-12 rounded-full bg-orange-500/20 text-orange-400 border border-orange-500/30">
                        <i class="fas fa-trophy text-2xl"></i>
                    </div>
                    <div>
                        <span class="text-[10px] font-bold tracking-widest text-orange-400 bg-orange-950/50 px-2 py-0.5 rounded-full border border-orange-500/20 uppercase">
                            Nova Conquista
                        </span>
                    </div>
                    <h3 class="text-lg font-bold">
                        Treinamento Concluído: <span class="text-yellow-300 block text-base mt-0.5">{{ $sharedTraining->titulo }}</span>
                    </h3>
                    
                    <div class="flex items-center justify-center gap-4 text-xs text-gray-300 pt-1">
                        <div>
                            <span class="block text-[10px] uppercase text-gray-400">Ranking</span>
                            <span class="font-bold text-yellow-300">#{{ $sharedRank }}</span>
                        </div>
                        <div class="w-px h-5 bg-white/20"></div>
                        <div>
                            <span class="block text-[10px] uppercase text-gray-400">Emissão</span>
                            <span class="font-bold text-emerald-400">Oficial</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Campo de Texto para Legenda -->
            <div>
                <label for="caption" class="block text-sm font-semibold text-gray-700 mb-1">Adicione uma legenda (opcional)</label>
                <textarea 
                    name="caption" 
                    id="caption"
                    rows="3" 
                    placeholder="Escreva algo sobre este treinamento para motivar seus colegas de trabalho..." 
                    class="w-full rounded-lg border-gray-300 focus:border-orange-500 focus:ring-orange-500 text-sm"
                >Concluí com sucesso o treinamento "{{ $sharedTraining->titulo }}". Vamos manter a segurança em primeiro lugar!</textarea>
            </div>

            <div class="flex items-center justify-end gap-3 pt-2">
                <button type="button" onclick="closeShareModal()" class="bg-gray-100 hover:bg-gray-200 text-gray-700 font-semibold px-4 py-2 rounded-lg text-sm transition">
                    Cancelar
                </button>
                <button type="submit" class="bg-orange-500 hover:bg-orange-600 text-white font-bold px-6 py-2 rounded-lg text-sm transition shadow">
                    Publicar Conquista
                </button>
            </div>
        </form>
    </div>
</div>
@endif

<script>
    // Live preview para upload de imagem
    const photoInput = document.getElementById('photo-input');
    const imagePreview = document.getElementById('image-preview');
    const imagePreviewContainer = document.getElementById('image-preview-container');
    const removePreviewBtn = document.getElementById('remove-preview-btn');

    if (photoInput) {
        photoInput.addEventListener('change', function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.addEventListener('load', function() {
                    imagePreview.setAttribute('src', this.result);
                    imagePreviewContainer.classList.remove('hidden');
                });
                reader.readAsDataURL(file);
            }
        });
    }

    if (removePreviewBtn) {
        removePreviewBtn.addEventListener('click', function() {
            photoInput.value = '';
            imagePreview.setAttribute('src', '#');
            imagePreviewContainer.classList.add('hidden');
        });
    }

    // Modal de compartilhamento
    function closeShareModal() {
        const modal = document.getElementById('share-training-modal');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    // Toggle de Curtida
    function toggleLike(button, postId) {
        const url = `/social/posts/${postId}/like`;
        const icon = button.querySelector('i');
        const countSpan = document.getElementById(`likes-count-${postId}`);

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.liked) {
                    icon.className = 'fas fa-heart text-xl text-red-500 scale-110';
                } else {
                    icon.className = 'far fa-heart text-xl text-gray-500';
                }
                countSpan.textContent = data.likes_count;
            }
        })
        .catch(error => console.error('Erro ao curtir:', error));
    }

    // Exibe ou recolhe a seção de comentários
    function toggleCommentsSection(postId) {
        const section = document.getElementById(`comments-section-${postId}`);
        if (section) {
            section.classList.toggle('hidden');
        }
    }

    // Envio de comentário por AJAX
    function submitComment(e, postId) {
        e.preventDefault();
        const input = document.getElementById(`comment-input-${postId}`);
        const content = input.value;
        const list = document.getElementById(`comments-list-${postId}`);
        
        if (!content.trim()) return;

        fetch(`/social/posts/${postId}/comment`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            },
            body: JSON.stringify({ content })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Limpar input
                input.value = '';
                
                // Inserir na lista de comentários
                const newCommentHtml = `
                    <div class="flex items-start gap-2.5 text-sm animate-fade-in">
                        <a href="${data.comment.user_profile_url}" class="flex-shrink-0 mt-0.5">
                            <img src="${data.comment.user_avatar}" alt="${data.comment.user_name}" class="w-7 h-7 rounded-full object-cover">
                        </a>
                        <div class="flex-1 bg-gray-50 rounded-xl p-2.5">
                            <div class="flex items-center justify-between gap-2">
                                <a href="${data.comment.user_profile_url}" class="font-bold text-gray-800 hover:underline">
                                    ${data.comment.user_name}
                                </a>
                                <span class="text-[10px] text-gray-400 font-medium">${data.comment.created_at}</span>
                            </div>
                            <p class="text-gray-700 mt-1 leading-normal">${data.comment.content}</p>
                        </div>
                    </div>
                `;
                
                list.insertAdjacentHTML('beforeend', newCommentHtml);
                list.scrollTop = list.scrollHeight;

                // Atualizar o número de comentários no contador
                const commentBtn = button = document.querySelector(`#post-card-${postId} button[onclick="toggleCommentsSection(${postId})"] span`);
                if (commentBtn) {
                    commentBtn.textContent = parseInt(commentBtn.textContent) + 1;
                }
            }
        })
        .catch(error => console.error('Erro ao enviar comentário:', error));
    }

    // Toggle de Seguir/Deixar de seguir (AJAX)
    function toggleFollow(button, userId) {
        const url = `/social/user/${userId}/follow`;

        fetch(url, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const followingCountSpan = document.getElementById('my-following-count');
                
                if (data.following) {
                    button.textContent = 'Seguindo';
                    button.className = 'text-xs font-bold px-3 py-1 rounded-full border bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200 transition';
                    
                    if (followingCountSpan) {
                        followingCountSpan.textContent = parseInt(followingCountSpan.textContent) + 1;
                    }
                } else {
                    button.textContent = 'Seguir';
                    button.className = 'bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold py-1 px-3.5 rounded-full transition shadow-sm';
                    
                    if (followingCountSpan) {
                        followingCountSpan.textContent = Math.max(0, parseInt(followingCountSpan.textContent) - 1);
                    }
                }

                // Recarrega todos os botões que seguem este mesmo usuário nesta página
                const buttons = document.querySelectorAll(`button[onclick="toggleFollow(this, ${userId})"]`);
                buttons.forEach(btn => {
                    if (data.following) {
                        btn.textContent = 'Seguindo';
                        btn.className = 'text-xs font-bold px-3 py-1 rounded-full border bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200 transition';
                    } else {
                        btn.textContent = 'Seguir';
                        btn.className = 'bg-orange-500 hover:bg-orange-600 text-white text-xs font-bold py-1 px-3.5 rounded-full transition shadow-sm';
                    }
                });
            }
        })
        .catch(error => console.error('Erro ao seguir:', error));
    }
</script>

<style>
    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(8px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .animate-fade-in {
        animation: fadeIn 0.3s ease-out forwards;
    }
</style>
@endsection
