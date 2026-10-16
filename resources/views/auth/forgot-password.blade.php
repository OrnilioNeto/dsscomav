@extends('layout')

@section('title', 'Recuperar senha - ' . plataforma_nome())

@section('content')
<div class="min-h-screen flex items-center justify-center" style="background: linear-gradient(135deg,var(--primary) 0%, var(--primary-700) 100%);">
    <div class="w-full max-w-md">
        <div class="bg-white rounded-lg shadow-2xl p-8">
            <div class="text-center mb-8">
                <x-logo alt="Logo" class="mx-auto mb-4" height="54px" />
                <h1 class="text-2xl font-bold text-gray-800">Recuperar senha</h1>
                <p class="text-gray-600 mt-2 text-sm">
                    Informe o e-mail cadastrado na sua conta. Enviaremos um link para você criar uma nova senha.
                </p>
            </div>

            @if(session('error'))
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4 mb-4">
                    {{ session('error') }}
                </div>
            @endif

            <form action="{{ route('password.email') }}" method="POST" class="space-y-4">
                @csrf

                <div>
                    <label class="block text-gray-700 font-semibold mb-2">E-mail</label>
                    <input
                        type="email"
                        name="email"
                        placeholder="seuemail@exemplo.com"
                        value="{{ old('email') }}"
                        class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900"
                        required
                        autofocus
                    >
                </div>

                <button
                    type="submit"
                    class="w-full bg-blue-900 text-white font-semibold py-2 px-4 rounded-lg hover:bg-blue-800 transition"
                >
                    <i class="fas fa-paper-plane mr-2"></i> Enviar link de recuperação
                </button>
            </form>

            <div class="text-center mt-6">
                <a href="{{ route('login') }}" class="text-blue-900 hover:underline text-sm font-semibold">
                    <i class="fas fa-arrow-left mr-1"></i> Voltar para o login
                </a>
            </div>
        </div>
    </div>
</div>
@endsection
