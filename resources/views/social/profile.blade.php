@extends('layout')

@section('title', 'Perfil de ' . $user->nome)

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">

    <!-- Voltar link -->
    <div class="flex justify-between items-center mb-8">
        <a href="{{ route('social.feed') }}" class="inline-flex items-center text-sm font-semibold text-orange-500 hover:text-orange-600 transition">
            <i class="fas fa-arrow-left mr-2"></i>Voltar ao Feed
        </a>
        <h1 class="text-xl font-bold text-gray-800">Perfil do Colaborador</h1>
    </div>

    <!-- Cabeçalho do Perfil (Card Premium) -->
    <div class="bg-white rounded-2xl shadow-md p-6 sm:p-8 border border-gray-100 mb-8 relative overflow-hidden">
        <div class="absolute -right-16 -top-16 w-36 h-36 rounded-full bg-blue-500/5 blur-xl"></div>
        
        <div class="flex flex-col sm:flex-row items-center gap-6 sm:gap-8 relative z-10">
            <!-- Avatar do Usuário -->
            <div class="flex-shrink-0">
                <img 
                    src="{{ $user->getFotoPerfilUrl() }}" 
                    alt="{{ $user->nome }}" 
                    class="w-28 h-28 sm:w-32 sm:h-32 rounded-full object-cover border-4 border-orange-500/10 shadow-sm"
                >
            </div>
            
            <!-- Detalhes do Usuário -->
            <div class="flex-1 text-center sm:text-left space-y-4">
                <div>
                    <div class="flex flex-wrap items-center justify-center sm:justify-start gap-3">
                        <h2 class="text-2xl sm:text-3xl font-black text-gray-800">{{ $user->nome }}</h2>
                        <span class="px-3 py-0.5 bg-gray-100 text-gray-500 rounded-full text-xs font-semibold uppercase tracking-wider capitalize">
                            {{ $user->tipo_usuario }}
                        </span>
                    </div>
                    <p class="text-sm text-gray-500 mt-1 font-mono">ID Colaborador: #{{ $user->id }}</p>
                </div>
                
                <!-- Estatísticas de Rede Social -->
                <div class="flex items-center justify-center sm:justify-start gap-8 py-2">
                    <div class="text-center sm:text-left">
                        <span class="block text-xl font-bold text-blue-900">{{ $posts->count() }}</span>
                        <span class="text-xs text-gray-400 font-medium uppercase tracking-wider">Posts</span>
                    </div>
                    <div class="text-center sm:text-left border-l border-gray-100 pl-8">
                        <span class="block text-xl font-bold text-blue-900" id="followers-count">{{ $user->followersCount() }}</span>
                        <span class="text-xs text-gray-400 font-medium uppercase tracking-wider">Seguidores</span>
                    </div>
                    <div class="text-center sm:text-left border-l border-gray-100 pl-8">
                        <span class="block text-xl font-bold text-blue-900">{{ $user->followingCount() }}</span>
                        <span class="text-xs text-gray-400 font-medium uppercase tracking-wider">Seguindo</span>
                    </div>
                </div>

                <!-- Botão Seguir / Ação -->
                <div class="pt-2">
                    @if($user->id !== auth()->id())
                        @php $isFollowing = auth()->user()->isFollowing($user->id); @endphp
                        <button 
                            type="button" 
                            onclick="toggleFollowProfile(this, {{ $user->id }})" 
                            class="w-full sm:w-auto font-bold px-8 py-2.5 rounded-lg border transition shadow-sm text-sm {{ $isFollowing ? 'bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200' : 'bg-orange-500 text-white border-orange-500 hover:bg-orange-600' }}"
                        >
                            {{ $isFollowing ? 'Seguindo' : 'Seguir Colaborador' }}
                        </button>
                    @else
                        <a href="{{ route('profile.edit') }}" class="inline-flex items-center justify-center bg-gray-100 hover:bg-gray-200 text-gray-700 font-bold px-6 py-2.5 rounded-lg text-sm transition">
                            <i class="fas fa-edit mr-2 text-blue-600"></i>Editar Minha Foto de Perfil
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- Título da seção de postagens -->
    <h3 class="text-lg font-bold text-gray-800 mb-6 flex items-center">
        <i class="fas fa-images text-orange-500 mr-2"></i>Publicações de {{ explode(' ', $user->nome)[0] }}
    </h3>

    <!-- Feed de publicações deste usuário específico -->
    <div class="space-y-6 max-w-2xl mx-auto">
        @forelse($posts as $post)
            <div class="bg-white rounded-xl shadow-md border border-gray-100 overflow-hidden" id="post-card-{{ $post->id }}">
                
                <!-- Cabeçalho do Post -->
                <div class="p-4 flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <img src="{{ $post->user->getFotoPerfilUrl() }}" alt="{{ $post->user->nome }}" class="w-10 h-10 rounded-full object-cover border border-gray-200">
                        <div>
                            <div class="flex items-center gap-1.5">
                                <span class="font-bold text-gray-800">{{ $post->user->nome }}</span>
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

                <!-- Conteúdo do Post -->
                @if($post->isTrainingPost())
                    <!-- Card Estilizado de Conquista -->
                    <div class="p-6 bg-gradient-to-br from-[#153B2E] via-[#0F2B22] to-[#153B2E] text-white text-center relative overflow-hidden">
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
                                    <p class="text-xs text-gray-400 font-semibold uppercase">Pontuação Obtida</p>
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
                        </div>
                    </div>
                @elseif($post->photo_path)
                    <div class="bg-gray-50 border-y border-gray-100 flex items-center justify-center overflow-hidden">
                        <img src="{{ asset('uploads/social/' . $post->photo_path) }}" alt="Post de {{ $post->user->nome }}" class="w-full object-cover max-h-[500px]">
                    </div>
                @endif

                <!-- Corpo (Legenda e Curtidas) -->
                <div class="p-4 space-y-3">
                    <div class="flex items-center gap-4">
                        @php $hasLiked = $post->isLikedBy(auth()->id()); @endphp
                        <button 
                            type="button" 
                            onclick="toggleLikeProfile(this, {{ $post->id }})" 
                            class="flex items-center gap-1.5 group text-sm font-semibold transition focus:outline-none"
                        >
                            <i class="fa-heart text-xl transition-all duration-200 {{ $hasLiked ? 'fas text-red-500 scale-110' : 'far text-gray-500 group-hover:text-red-500' }}"></i>
                            <span class="likes-count text-gray-700">{{ $post->likes->count() }}</span>
                        </button>
                        
                        <button 
                            type="button" 
                            onclick="toggleCommentsProfile({{ $post->id }})" 
                            class="flex items-center gap-1.5 text-gray-500 hover:text-blue-600 text-sm font-semibold transition"
                        >
                            <i class="far fa-comment text-xl"></i>
                            <span>{{ $post->comments->count() }}</span>
                        </button>
                    </div>

                    @if($post->caption)
                        <div class="text-gray-800 text-sm sm:text-base leading-relaxed">
                            <strong class="font-bold text-gray-900 mr-1">{{ $post->user->nome }}</strong>
                            {{ $post->caption }}
                        </div>
                    @endif

                    <!-- Comentários -->
                    <div class="comments-section mt-4 pt-4 border-t border-gray-50 space-y-3" id="comments-section-{{ $post->id }}">
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

                        <form onsubmit="submitCommentProfile(event, {{ $post->id }})" class="flex items-center gap-2 pt-2">
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
                <h3 class="text-lg font-bold text-gray-800">Nenhuma postagem ainda</h3>
                <p class="text-gray-500 mt-2 text-sm max-w-sm mx-auto">Este usuário ainda não publicou fotos ou conquistas na plataforma.</p>
            </div>
        @endforelse
    </div>

</div>

<script>
    // Toggle Curtida
    function toggleLikeProfile(button, postId) {
        const url = `/social/posts/${postId}/like`;
        const icon = button.querySelector('i');
        const countSpan = button.querySelector('.likes-count');

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

    // Toggle Comentários
    function toggleCommentsProfile(postId) {
        const section = document.getElementById(`comments-section-${postId}`);
        if (section) {
            section.classList.toggle('hidden');
        }
    }

    // Enviar Comentário
    function submitCommentProfile(e, postId) {
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
                input.value = '';
                
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

                const commentCountSpan = document.querySelector(`#post-card-${postId} button[onclick="toggleCommentsProfile(${postId})"] span`);
                if (commentCountSpan) {
                    commentCountSpan.textContent = parseInt(commentCountSpan.textContent) + 1;
                }
            }
        })
        .catch(error => console.error('Erro ao comentar:', error));
    }

    // Toggle Seguir / Deixar de Seguir (do Perfil)
    function toggleFollowProfile(button, userId) {
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
                const followersSpan = document.getElementById('followers-count');
                
                if (data.following) {
                    button.textContent = 'Seguindo';
                    button.className = 'w-full sm:w-auto font-bold px-8 py-2.5 rounded-lg border bg-gray-100 text-gray-600 border-gray-200 hover:bg-gray-200 transition shadow-sm text-sm';
                } else {
                    button.textContent = 'Seguir Colaborador';
                    button.className = 'w-full sm:w-auto font-bold px-8 py-2.5 rounded-lg border bg-orange-500 text-white border-orange-500 hover:bg-orange-600 transition shadow-sm text-sm';
                }

                if (followersSpan) {
                    followersSpan.textContent = data.followers_count;
                }
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
