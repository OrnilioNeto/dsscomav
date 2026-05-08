@extends('layout')

@section('title', 'Login - Plataforma DSS')

@section('content')
<div class="min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg,var(--primary) 0%, var(--primary-700) 100%);">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <div class="text-center mb-8">
                <img src="{{ site_asset('images/logo-comav-transportes.png') }}" alt="Logo" class="mx-auto mb-4" style="height:54px;">
                <h1 class="text-3xl font-bold text-gray-800">Previa Segurança</h1>
                <p class="text-gray-600 mt-2">Treinamentos/DSS</p>
            </div>

            @if($errors->any())
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    @foreach($errors->all() as $error)
                        <p>{{ $error }}</p>
                    @endforeach
                </div>
            @endif

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('login') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">CPF</label>
                    <input 
                        type="text" 
                        name="cpf" 
                        placeholder="000.000.000-00"
                        value="{{ old('cpf') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900"
                        required
                    >
                </div>

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">Senha</label>
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Sua senha"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900"
                        required
                    >
                </div>

                <button 
                    type="submit"
                    class="w-full bg-blue-900 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-800 transition"
                >
                    <i class="fas fa-sign-in-alt mr-2"></i> Entrar
                </button>
            </form>
        </div>
    </div>
</div>
@endsection
