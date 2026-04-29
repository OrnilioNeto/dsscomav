<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="Content-Security-Policy" content="default-src 'self' 'unsafe-inline' 'unsafe-eval' https:; script-src 'self' 'unsafe-inline' 'unsafe-eval' https: data:; style-src 'self' 'unsafe-inline' https:;">
    <title>@yield('title', 'Plataforma DSS')</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    @yield('extra_css')
</head>
<body class="bg-gray-50">
    @if(Auth::check())
        <nav class="bg-blue-900 text-white shadow-lg">
            <div class="max-w-7xl mx-auto px-4">
                <div class="flex justify-between items-center h-16">
                    <div class="flex items-center">
                        <i class="fas fa-graduation-cap text-2xl mr-3"></i>
                        <span class="font-bold text-lg">Plataforma DSS</span>
                    </div>
                    
                    <div class="flex items-center space-x-6">
                        @if(Auth::user()->isAdmin())
                            <a href="{{ route('usuarios.index') }}" class="hover:text-blue-200">
                                <i class="fas fa-users mr-1"></i> Usuários
                            </a>
                            <a href="{{ route('treinamentos.index') }}" class="hover:text-blue-200">
                                <i class="fas fa-video mr-1"></i> Treinamentos
                            </a>
                        @endif
                        
                        <a href="{{ route('dashboard') }}" class="hover:text-blue-200">
                            <i class="fas fa-home mr-1"></i> Dashboard
                        </a>
                        
                        <a href="{{ route('certificados.meus') }}" class="hover:text-blue-200">
                            <i class="fas fa-certificate mr-1"></i> Certificados
                        </a>

                        <div class="relative group">
                            <button class="flex items-center hover:text-blue-200">
                                <i class="fas fa-user-circle text-xl"></i>
                                <span class="ml-2">{{ Auth::user()->nome }}</span>
                            </button>
                            <div class="hidden group-hover:block absolute right-0 mt-0 w-40 bg-white text-gray-800 rounded shadow-lg z-50">
                                <a href="{{ route('dashboard') }}" class="block px-4 py-2 hover:bg-gray-100">
                                    <i class="fas fa-user mr-2"></i> Meu Perfil
                                </a>
                                <form action="{{ route('logout') }}" method="POST" class="block">
                                    @csrf
                                    <button type="submit" class="w-full text-left px-4 py-2 hover:bg-gray-100">
                                        <i class="fas fa-sign-out-alt mr-2"></i> Sair
                                    </button>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </nav>
    @endif

    <div class="min-h-screen">
        @if($errors->any())
            <div class="max-w-7xl mx-auto mt-4 px-4">
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
                    <p class="font-bold">Erros encontrados:</p>
                    <ul class="list-disc list-inside">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        @endif

        @if(session('success'))
            <div class="max-w-7xl mx-auto mt-4 px-4">
                <div class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4">
                    {{ session('success') }}
                </div>
            </div>
        @endif

        @if(session('error'))
            <div class="max-w-7xl mx-auto mt-4 px-4">
                <div class="bg-red-100 border-l-4 border-red-500 text-red-700 p-4">
                    {{ session('error') }}
                </div>
            </div>
        @endif

        @yield('content')
    </div>

    <footer class="bg-blue-900 text-white mt-16 py-8">
        <div class="max-w-7xl mx-auto px-4 text-center">
            <p>&copy; 2024 Plataforma DSS. Todos os direitos reservados.</p>
        </div>
    </footer>

    @yield('extra_js')
</body>
</html>
