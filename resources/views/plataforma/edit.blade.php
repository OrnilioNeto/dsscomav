@extends('layout')

@section('title', 'Plataforma — ' . ($tenant->nome_exibicao ?? $tenant->nome))

@section('content')
<div class="max-w-4xl mx-auto px-4 py-8">
    <div class="flex items-center justify-between mb-8">
        <h1 class="text-4xl font-bold text-gray-800">
            <i class="fas fa-building text-indigo-600 mr-3"></i>{{ $tenant->nome_exibicao ?? $tenant->nome }}
        </h1>
        <a href="{{ route('plataforma.index') }}" class="text-gray-600 hover:text-gray-800 font-semibold">
            <i class="fas fa-arrow-left mr-1"></i>Voltar
        </a>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-8 text-green-800 text-sm">{{ session('success') }}</div>
    @endif

    <form method="POST" action="{{ route('plataforma.update', $tenant) }}" enctype="multipart/form-data" class="bg-white rounded-lg shadow-lg p-8 border border-gray-200 mb-8">
        @csrf
        @method('PUT')

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome do cliente *</label>
                <input type="text" name="nome" required value="{{ old('nome', $tenant->nome) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Slug (subdomínio)</label>
                <input type="text" name="slug" value="{{ old('slug', $tenant->slug) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Domínio próprio (opcional)</label>
                <input type="text" name="dominio" value="{{ old('dominio', $tenant->dominio) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Status</label>
                <select name="status" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                    @foreach(['ativo', 'trial', 'suspenso', 'cancelado'] as $s)
                        <option value="{{ $s }}" {{ old('status', $tenant->status) === $s ? 'selected' : '' }}>{{ ucfirst($s) }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Plano</label>
                <input type="text" name="plano" value="{{ old('plano', $tenant->plano) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome de exibição (white-label)</label>
                <input type="text" name="nome_exibicao" value="{{ old('nome_exibicao', $tenant->nome_exibicao) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">E-mail remetente</label>
                <input type="email" name="email_remetente" value="{{ old('email_remetente', $tenant->email_remetente) }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Cor primária (ex.: #153B2E)</label>
                <input type="text" name="cor_primaria" value="{{ old('cor_primaria', $tenant->cor_primaria) }}" placeholder="#153B2E" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Cor secundária (ex.: #0F2B22)</label>
                <input type="text" name="cor_secundaria" value="{{ old('cor_secundaria', $tenant->cor_secundaria) }}" placeholder="#0F2B22" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Logo (layout/login)</label>
                <input type="file" name="logo" accept="image/png,image/jpeg,image/webp" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @if($tenant->logoUrl())
                    <img src="{{ $tenant->logoUrl() }}" alt="Logo atual" class="mt-2 h-12 object-contain border border-gray-200 rounded p-1 bg-white">
                @endif
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Logo do certificado</label>
                <input type="file" name="logo_certificado" accept="image/png,image/jpeg,image/webp" class="w-full border border-gray-300 rounded-lg px-3 py-2">
                @if($tenant->logoCertificadoUrl())
                    <img src="{{ $tenant->logoCertificadoUrl() }}" alt="Logo do certificado" class="mt-2 h-12 object-contain border border-gray-200 rounded p-1 bg-white">
                @endif
            </div>
        </div>

        <h2 class="text-lg font-bold text-gray-800 mb-3">Instrutor (certificados)</h2>
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome do instrutor</label>
                <input type="text" name="instrutor_nome" value="{{ old('instrutor_nome', $tenant->instrutor_nome) }}" placeholder="Ornilio Machado Neto" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Qualificação</label>
                <input type="text" name="instrutor_qualificacao" value="{{ old('instrutor_qualificacao', $tenant->instrutor_qualificacao) }}" placeholder="Tec Segurança do Trabalho" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">RG</label>
                <input type="text" name="instrutor_rg" value="{{ old('instrutor_rg', $tenant->instrutor_rg) }}" placeholder="10827" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('plataforma.index') }}" class="px-6 py-2 rounded-lg bg-gray-200 text-gray-700 hover:bg-gray-300">Cancelar</a>
            <button type="submit" class="px-6 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700">
                <i class="fas fa-save mr-2"></i>Salvar
            </button>
        </div>
    </form>

    <h2 class="text-lg font-bold text-gray-800 mb-3">Módulos liberados</h2>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        @foreach($modules as $slug => $info)
            @php
                $reg = $tenant->modules->firstWhere('module', $slug);
                $liberado = $reg ? (bool) $reg->enabled : false;
            @endphp
            <div class="flex items-center justify-between bg-white border rounded-lg px-4 py-3 {{ $liberado ? 'border-green-300' : 'border-gray-200' }}">
                <span>
                    <span class="block font-semibold text-gray-800 text-sm">{{ $info['label'] }}</span>
                    <span class="block text-xs text-gray-500">{{ $info['description'] }}</span>
                </span>
                <form method="POST" action="{{ route('plataforma.modules.toggle', [$tenant, $slug]) }}">
                    @csrf
                    <button type="submit" class="px-3 py-1 rounded-full text-xs font-bold {{ $liberado ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}">
                        {{ $liberado ? 'Liberado' : 'Bloqueado' }}
                    </button>
                </form>
            </div>
        @endforeach
    </div>

    <h2 class="text-lg font-bold text-gray-800 mt-10 mb-3">Criar usuário administrador</h2>
    <p class="text-sm text-gray-500 mb-4">Cria o primeiro admin do cliente (acesso pelo subdomínio deste tenant). Você pode criar mais usuários depois, logado no painel do próprio cliente.</p>
    <form method="POST" action="{{ route('plataforma.admins.store', $tenant) }}" class="bg-white rounded-lg shadow-lg p-8 border border-gray-200">
        @csrf
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Nome *</label>
                <input type="text" name="nome" required value="{{ old('nome') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">CPF (11 dígitos) *</label>
                <input type="text" name="cpf" required maxlength="11" value="{{ old('cpf') }}" placeholder="Somente números" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">E-mail</label>
                <input type="email" name="email" value="{{ old('email') }}" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
            <div>
                <label class="block text-sm font-bold text-gray-700 mb-1">Senha *</label>
                <input type="password" name="senha" required minlength="6" class="w-full border border-gray-300 rounded-lg px-3 py-2">
            </div>
        </div>
        <div class="flex justify-end mt-6">
            <button type="submit" class="px-6 py-2 rounded-lg bg-green-600 text-white hover:bg-green-700">
                <i class="fas fa-user-plus mr-2"></i>Criar Admin
            </button>
        </div>
    </form>
</div>
@endsection