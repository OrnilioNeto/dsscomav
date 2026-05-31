@extends('layout')

@section('content')
<div class="container mx-auto px-4 py-8">
    <div class="max-w-2xl mx-auto">
        <h1 class="text-3xl font-bold mb-8">Editar Perfil</h1>

        <!-- Card de Foto de Perfil -->
        <div class="bg-white rounded-lg shadow-lg p-8 mb-8">
            <div class="flex flex-col items-center">
                <!-- Avatar Atual -->
                <div class="relative group mb-6">
                    <img 
                        id="fotoAtual" 
                        src="{{ auth()->user()->getFotoPerfilUrl() }}" 
                        alt="{{ auth()->user()->nome }}"
                        class="w-40 h-40 rounded-full object-cover border-4 border-blue-900 shadow-lg"
                    >
                    
                    <!-- Overlay com ícone de câmera -->
                    <button 
                        type="button"
                        id="btnAbrirCamera"
                        class="absolute inset-0 bg-black bg-opacity-50 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center cursor-pointer"
                        title="Clique para alterar foto"
                    >
                        <svg class="w-12 h-12 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z"></path>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z"></path>
                        </svg>
                    </button>
                </div>

                <h2 class="text-xl font-semibold mb-2">{{ auth()->user()->nome }}</h2>
                <p class="text-gray-600 mb-6">{{ auth()->user()->email }}</p>

                <!-- Informação -->
                <p class="text-sm text-gray-500 text-center mb-6 max-w-xs">
                    Foto de perfil padrão 300x300px. A imagem será automaticamente ajustada.
                </p>

                <!-- Botões -->
                <div class="flex flex-wrap gap-4 justify-center">
                    <button 
                        type="button"
                        id="btnCamera"
                        class="flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M2 6a2 2 0 012-2h12a2 2 0 012 2v8a2 2 0 01-2 2H4a2 2 0 01-2-2V6zM4 6h12v8H4V6z"></path>
                            <circle cx="10" cy="11" r="2" fill="currentColor"></circle>
                        </svg>
                        Câmera
                    </button>
                    
                    <button 
                        type="button"
                        id="btnGaleria"
                        class="flex items-center gap-2 bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path d="M4 3a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V5a2 2 0 00-2-2H4zm12 12H4l4-8 3 6 2-4 3 6z"></path>
                        </svg>
                        Galeria
                    </button>

                    @if(auth()->user()->foto_perfil)
                    <button 
                        type="button"
                        id="btnRemover"
                        class="flex items-center gap-2 bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition"
                        style="display: {{ auth()->user()->foto_perfil ? 'flex' : 'none' }}"
                    >
                        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"></path>
                        </svg>
                        Remover
                    </button>
                    @endif
                </div>

                <!-- Barra de Progresso -->
                <div id="progressoUpload" class="mt-6 w-full hidden">
                    <div class="bg-gray-200 rounded-full h-2 overflow-hidden">
                        <div id="progressBar" class="bg-blue-600 h-full transition-all duration-300" style="width: 0%"></div>
                    </div>
                    <p id="progressText" class="text-sm text-gray-600 mt-2 text-center">Enviando...</p>
                </div>

                <!-- Input Hidden para Upload -->
                <input 
                    type="file" 
                    id="inputFoto" 
                    accept="image/*"
                    class="hidden"
                >

                <!-- Video para Câmera -->
                <div id="cameraContainer" class="hidden w-full mt-6">
                    <video 
                        id="videoCamera" 
                        autoplay 
                        playsinline
                        muted
                        class="w-full rounded-lg border-2 border-gray-300 bg-black"
                        style="max-height: 400px; object-fit: cover;"
                    ></video>
                    <div class="flex gap-3 mt-4">
                        <button 
                            type="button"
                            id="btnCapturar"
                            class="flex-1 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition"
                        >
                            Capturar
                        </button>
                        <button 
                            type="button"
                            id="btnCancelarCamera"
                            class="flex-1 bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition"
                        >
                            Cancelar
                        </button>
                    </div>
                </div>

                <!-- Canvas para prévia da foto capturada -->
                <canvas id="canvasCaptura" class="hidden"></canvas>
            </div>
        </div>

        <!-- Card de Informações Pessoais -->
        <div class="bg-white rounded-lg shadow-lg p-8">
            <h3 class="text-xl font-semibold mb-6">Informações Pessoais</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Nome</label>
                    <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-700">
                        {{ auth()->user()->nome }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">CPF</label>
                    <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-700">
                        {{ auth()->user()->getCpfFormatted() }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Email</label>
                    <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-700">
                        {{ auth()->user()->email }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Telefone</label>
                    <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-700">
                        {{ auth()->user()->telefone ?? 'Não informado' }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Data de Nascimento</label>
                    <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-700">
                        {{ auth()->user()->data_nascimento?->format('d/m/Y') ?? 'Não informado' }}
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">Tipo de Usuário</label>
                    <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-700">
                        <span class="capitalize">{{ auth()->user()->tipo_usuario }}</span>
                    </div>
                </div>
            </div>

            @if(auth()->user()->setor || auth()->user()->cargo || auth()->user()->empresa)
            <div class="border-t border-gray-200 mt-8 pt-8">
                <h4 class="text-lg font-semibold mb-6">Informações Profissionais</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    @if(auth()->user()->empresa)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Empresa</label>
                        <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-700">
                            {{ auth()->user()->empresa }}
                        </div>
                    </div>
                    @endif

                    @if(auth()->user()->setor)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Setor</label>
                        <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-700">
                            {{ auth()->user()->setor }}
                        </div>
                    </div>
                    @endif

                    @if(auth()->user()->cargo)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Cargo</label>
                        <div class="bg-gray-100 px-4 py-2 rounded-lg text-gray-700">
                            {{ auth()->user()->cargo }}
                        </div>
                    </div>
                    @endif
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elementos do DOM
    const btnCamera = document.getElementById('btnCamera');
    const btnGaleria = document.getElementById('btnGaleria');
    const btnRemover = document.getElementById('btnRemover');
    const btnAbrirCamera = document.getElementById('btnAbrirCamera');
    const inputFoto = document.getElementById('inputFoto');
    const cameraContainer = document.getElementById('cameraContainer');
    const videoCamera = document.getElementById('videoCamera');
    const canvasCaptura = document.getElementById('canvasCaptura');
    const btnCapturar = document.getElementById('btnCapturar');
    const btnCancelarCamera = document.getElementById('btnCancelarCamera');
    const fotoAtual = document.getElementById('fotoAtual');
    const progressoUpload = document.getElementById('progressoUpload');
    const progressBar = document.getElementById('progressBar');
    const progressText = document.getElementById('progressText');
    
    let stream = null;

    console.log('Script de edição de perfil carregado');

    // Eventos dos botões
    if (btnCamera) btnCamera.addEventListener('click', abrirCamera);
    if (btnAbrirCamera) btnAbrirCamera.addEventListener('click', abrirCamera);
    if (btnCapturar) btnCapturar.addEventListener('click', capturarFoto);
    if (btnCancelarCamera) btnCancelarCamera.addEventListener('click', fecharCamera);
    if (btnGaleria) btnGaleria.addEventListener('click', () => inputFoto.click());
    if (inputFoto) inputFoto.addEventListener('change', processarFoto);
    if (btnRemover) btnRemover.addEventListener('click', removerFoto);

    // Função: Abrir Câmera
    async function abrirCamera() {
        try {
            console.log('Abrindo câmera...');
            cameraContainer.classList.remove('hidden');
            
            // Compatibilidade cross-browser
            if (!navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
                alert('Sua câmera não é suportada neste navegador.');
                cameraContainer.classList.add('hidden');
                return;
            }

            const constraints = {
                video: {
                    facingMode: 'user',
                    width: { ideal: 1280 },
                    height: { ideal: 720 }
                },
                audio: false
            };
            
            stream = await navigator.mediaDevices.getUserMedia(constraints);
            videoCamera.srcObject = stream;
            videoCamera.muted = true;
            videoCamera.play().catch(e => console.log('Play error:', e));
            console.log('Câmera aberta com sucesso');
        } catch (err) {
            console.error('Erro ao acessar câmera:', err);
            cameraContainer.classList.add('hidden');
            
            let mensagem = 'Erro ao acessar câmera: ';
            if (err.name === 'NotAllowedError' || err.name === 'PermissionDeniedError') {
                mensagem += 'Permissão negada. Verifique as configurações de câmera.';
            } else if (err.name === 'NotFoundError') {
                mensagem += 'Câmera não encontrada no dispositivo.';
            } else if (err.name === 'NotReadableError') {
                mensagem += 'Câmera está sendo usada por outro aplicativo.';
            } else if (err.name === 'TypeError') {
                mensagem += 'Erro de tipo. Verifique o navegador.';
            } else {
                mensagem += err.message || 'Erro desconhecido';
            }
            
            alert(mensagem);
        }
    }

    // Função: Fechar Câmera
    function fecharCamera() {
        console.log('Fechando câmera...');
        if (stream) {
            stream.getTracks().forEach(track => {
                track.stop();
            });
            stream = null;
        }
        cameraContainer.classList.add('hidden');
        videoCamera.srcObject = null;
    }

    // Função: Capturar Foto
    function capturarFoto() {
        try {
            console.log('Capturando foto da câmera...');
            
            if (!videoCamera.videoWidth || !videoCamera.videoHeight) {
                alert('A câmera ainda não está pronta. Aguarde um momento.');
                return;
            }

            const ctx = canvasCaptura.getContext('2d');
            canvasCaptura.width = videoCamera.videoWidth;
            canvasCaptura.height = videoCamera.videoHeight;
            
            ctx.drawImage(videoCamera, 0, 0);
            
            canvasCaptura.toBlob(function(blob) {
                if (!blob) {
                    alert('Erro ao processar a imagem capturada.');
                    return;
                }

                const file = new File([blob], 'camera-photo.jpg', { type: 'image/jpeg' });
                
                try {
                    const dataTransfer = new DataTransfer();
                    dataTransfer.items.add(file);
                    inputFoto.files = dataTransfer.files;
                } catch (e) {
                    console.error('DataTransfer error:', e);
                    inputFoto.value = '';
                }
                
                console.log('Foto capturada com sucesso');
                fecharCamera();
                processarFoto();
            }, 'image/jpeg', 0.9);
        } catch (err) {
            console.error('Erro ao capturar foto:', err);
            alert('Erro ao capturar foto: ' + err.message);
        }
    }

    // Função: Processar Foto
    function processarFoto() {
        try {
            const file = inputFoto.files[0];
            if (!file) {
                console.log('Nenhum arquivo selecionado');
                return;
            }

            console.log('Processando foto:', file.name, 'Tamanho:', file.size);

            if (!file.type.startsWith('image/')) {
                alert('Por favor, selecione uma imagem válida.');
                inputFoto.value = '';
                return;
            }

            if (file.size > 5 * 1024 * 1024) {
                alert('A imagem não pode ter mais de 5MB. Tamanho atual: ' + (file.size / 1024 / 1024).toFixed(2) + 'MB');
                inputFoto.value = '';
                return;
            }

            const reader = new FileReader();
            reader.onload = (e) => {
                fotoAtual.src = e.target.result;
            };
            reader.readAsDataURL(file);

            enviarFoto(file);
        } catch (err) {
            console.error('Erro ao processar foto:', err);
            alert('Erro ao processar foto: ' + err.message);
        }
    }

    // Função: Enviar Foto
    function enviarFoto(file) {
        try {
            const formData = new FormData();
            formData.append('foto', file);

            progressoUpload.classList.remove('hidden');
            progressBar.style.width = '0%';
            progressText.innerHTML = 'Enviando...';

            console.log('Iniciando upload...');

            fetch('{{ route("profile.photo.upload") }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            })
            .then(response => {
                console.log('Resposta recebida:', response.status);
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Dados recebidos:', data);
                
                if (data.success) {
                    progressText.innerHTML = '✓ Foto atualizada com sucesso!';
                    progressBar.style.width = '100%';
                    fotoAtual.src = data.fotoUrl + '?t=' + Date.now();
                    
                    if (btnRemover) {
                        btnRemover.style.display = 'flex';
                    }
                    
                    setTimeout(() => {
                        progressoUpload.classList.add('hidden');
                        inputFoto.value = '';
                    }, 2000);
                } else {
                    throw new Error(data.message || 'Erro ao salvar foto');
                }
            })
            .catch(error => {
                console.error('Erro no upload:', error);
                progressText.innerHTML = '✗ Erro ao enviar foto';
                progressBar.style.backgroundColor = '#ef4444';
                alert('Erro ao enviar foto: ' + error.message);
                
                setTimeout(() => {
                    progressoUpload.classList.add('hidden');
                }, 3000);
            });
        } catch (err) {
            console.error('Erro ao enviar foto:', err);
            alert('Erro ao enviar foto: ' + err.message);
            progressoUpload.classList.add('hidden');
        }
    }

    // Função: Remover Foto
    function removerFoto() {
        if (!confirm('Tem certeza que deseja remover sua foto de perfil?')) {
            return;
        }

        try {
            console.log('Removendo foto de perfil...');

            fetch('{{ route("profile.photo.delete") }}', {
                method: 'DELETE',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                }
            })
            .then(response => {
                console.log('Resposta recebida:', response.status);
                if (!response.ok) {
                    throw new Error('Erro na requisição: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                console.log('Dados recebidos:', data);
                
                if (data.success) {
                    alert('Foto removida com sucesso!');
                    fotoAtual.src = data.fotoUrl;
                    if (btnRemover) {
                        btnRemover.style.display = 'none';
                    }
                } else {
                    throw new Error(data.message || 'Erro ao remover foto');
                }
            })
            .catch(error => {
                console.error('Erro ao remover foto:', error);
                alert('Erro ao remover foto: ' + error.message);
            });
        } catch (err) {
            console.error('Erro ao remover foto:', err);
            alert('Erro ao remover foto: ' + err.message);
        }
    }

    console.log('Listeners de perfil configurados com sucesso');
});
</script>
@endsection
