<!-- Avatar do usuário - Uso: @component('components.user-avatar', ['user' => auth()->user(), 'size' => 'md']) -->
<div class="flex items-center gap-3">
    <!-- Foto/Avatar -->
    <div class="flex-shrink-0">
        <img 
            src="{{ $user->getFotoPerfilUrl() }}" 
            alt="{{ $user->nome }}"
            class="w-{{ $size === 'lg' ? '16' : ($size === 'md' ? '10' : '8') }} h-{{ $size === 'lg' ? '16' : ($size === 'md' ? '10' : '8') }} rounded-full object-cover border-2 border-blue-900"
            title="{{ $user->nome }}"
        >
    </div>

    <!-- Informações do usuário -->
    @if($showInfo ?? false)
    <div class="flex-1 min-w-0">
        <p class="text-sm font-semibold text-gray-900 truncate">{{ $user->nome }}</p>
        <p class="text-xs text-gray-500 truncate capitalize">{{ $user->tipo_usuario }}</p>
    </div>
    @endif
</div>
