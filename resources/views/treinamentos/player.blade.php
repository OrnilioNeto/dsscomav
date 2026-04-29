@extends('layout')

@section('title', $training->titulo)

@section('content')
<div class="max-w-5xl mx-auto px-4 py-8">
    <div class="flex items-start justify-between mb-6">
        <div>
            <h1 class="text-4xl font-bold text-gray-800">{{ $training->titulo }}</h1>
            <span class="inline-block mt-3 px-3 py-1 rounded-full text-sm font-semibold
                {{ $training->tipo === 'dss' ? 'bg-red-100 text-red-900' : 'bg-blue-100 text-blue-900' }}
            ">
                {{ strtoupper($training->tipo) }}
            </span>
        </div>
        <div class="text-right">
            <p class="text-3xl font-bold text-blue-900">{{ $training->carga_horaria }}</p>
            <p class="text-gray-600">minutos</p>
        </div>
    </div>

    <p class="text-gray-700 text-lg mb-8">{{ $training->descricao }}</p>

    <div class="bg-white p-4 rounded-2xl shadow-lg mb-8">
        @if($training->tipo_video === 'upload')
            <video
                id="training-video"
                class="w-full rounded-xl bg-black"
                controls
                style="max-width: 100%; height: auto; display: block;"
            >
                <source src="{{ $training->url_video }}" type="video/mp4">
                Seu navegador não suporta vídeo HTML5.
            </video>
        @else
            <div class="relative w-full overflow-hidden rounded-xl bg-black" style="padding-top: 56.25%;">
                @if($training->tipo_video === 'youtube')
                    <iframe
                        id="training-video"
                        class="absolute inset-0 h-full w-full"
                        src="https://www.youtube.com/embed/{{ preg_match('/(?:youtube\.com\/watch\?v=|youtu\.be\/)([^&\n?#]+)/', $training->url_video, $matches) ? ($matches[1] ?? '') : '' }}?autoplay=0"
                        title="{{ $training->titulo }}"
                        frameborder="0"
                        allow="autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                @else
                    <iframe
                        id="training-video"
                        class="absolute inset-0 h-full w-full"
                        src="{{ $training->getVideoEmbed() }}"
                        title="{{ $training->titulo }}"
                        frameborder="0"
                        allow="autoplay; encrypted-media; picture-in-picture"
                        allowfullscreen
                    ></iframe>
                @endif
            </div>
        @endif
    </div>

    <div class="grid md:grid-cols-2 gap-6 mb-8">
        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">Seu Progresso</h2>
            <div class="space-y-3">
                <div class="flex justify-between items-center">
                    <span class="text-gray-700">Assistido</span>
                    <span id="progress-percent" class="font-bold text-blue-900">{{ $progress->porcentagem_assistida }}%</span>
                </div>
                <div class="w-full bg-gray-200 rounded-full h-4">
                    <div id="progress-bar" class="bg-blue-900 h-4 rounded-full transition-all" style="width: {{ $progress->porcentagem_assistida }}%"></div>
                </div>
                <div id="assessment-status" class="text-sm text-gray-600"></div>
                @if($progress->concluido)
                    <div class="text-green-600 font-semibold">
                        <i class="fas fa-check-circle mr-2"></i>Concluído em {{ $progress->data_conclusao->format('d/m/Y H:i') }}
                    </div>
                @endif
            </div>
        </div>

        <div class="bg-white p-6 rounded-lg shadow-lg">
            <h2 class="text-xl font-bold mb-4">Instruções</h2>
            <ul class="space-y-2 text-gray-700 list-disc list-inside">
                <li>Assista o vídeo completamente para desbloquear a avaliação.</li>
                <li>A avaliação abre automaticamente ao atingir 90% do tempo.</li>
                <li>Responda corretamente para concluir o treinamento.</li>
            </ul>
        </div>
    </div>

    <div class="flex gap-4">
        <a href="{{ route('dashboard') }}" class="flex-1 bg-gray-400 text-white font-bold py-3 px-6 rounded-lg hover:bg-gray-500 transition text-center">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>

        @if($progress && $progress->concluido)
            <button onclick="downloadCertificate({{ $training->id }})" class="flex-1 bg-orange-600 text-white font-bold py-3 px-6 rounded-lg hover:bg-orange-700 transition">
                <i class="fas fa-certificate mr-2"></i>Baixar Certificado
            </button>
        @endif
    </div>
</div>

<div id="assessment-modal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/70 px-4">
    <div class="w-full max-w-2xl rounded-2xl bg-white p-6 shadow-2xl">
        <div class="mb-4 flex items-start justify-between gap-4">
            <div>
                <h2 class="text-2xl font-bold text-gray-800">Avaliação do treinamento</h2>
                <p class="text-gray-600">Responda para liberar a conclusão.</p>
            </div>
            <button type="button" onclick="closeAssessment()" class="text-gray-500 hover:text-gray-800">
                <i class="fas fa-times text-xl"></i>
            </button>
        </div>

        <form id="assessment-form" class="space-y-4">
            @csrf
            <p class="font-semibold text-gray-800">{{ $training->avaliacao_pergunta }}</p>

            <div class="space-y-3">
                @foreach(($training->avaliacao_opcoes ?? []) as $index => $option)
                    @if(!empty($option))
                        <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 p-3 hover:border-blue-500">
                            <input type="radio" name="answer" value="{{ $index }}" class="text-blue-900" required>
                            <span class="text-gray-700">{{ $option }}</span>
                        </label>
                    @endif
                @endforeach
            </div>

            <div id="assessment-message" class="text-sm font-medium"></div>

            <div class="flex gap-3 pt-2">
                <button type="submit" class="flex-1 rounded-lg bg-blue-900 px-4 py-3 font-semibold text-white hover:bg-blue-800">
                    Enviar resposta
                </button>
            </div>
        </form>
    </div>
</div>

<script>console.log('INLINE SCRIPT LOADED');</script>

<script>
    const progressUrl = '{{ route('treinamentos.atualizar-progresso', $training->id) }}';
    const assessmentUrl = '{{ route('treinamentos.avaliacao', $training->id) }}';
    const csrfToken = '{{ csrf_token() }}';
    const hasAssessment = {{ $training->hasAssessment() ? 'true' : 'false' }};
    const trainingType = '{{ $training->tipo_video }}';
    const durationSeconds = {{ (int) $training->carga_horaria * 60 }};

    let currentProgress = {{ $progress->porcentagem_assistida }};
    let assessmentOpened = {{ $progress->avaliacao_aprovada ? 'true' : 'false' }};
    let lastUpdateTime = 0;

    function openAssessment() {
        if (!hasAssessment || assessmentOpened) return;
        assessmentOpened = true;
        document.getElementById('assessment-modal').classList.remove('hidden');
        document.getElementById('assessment-modal').classList.add('flex');
    }

    function closeAssessment() {
        document.getElementById('assessment-modal').classList.add('hidden');
        document.getElementById('assessment-modal').classList.remove('flex');
    }

    function updateProgress(percent) {
        currentProgress = Math.floor(percent);
        document.getElementById('progress-percent').textContent = currentProgress + '%';
        document.getElementById('progress-bar').style.width = currentProgress + '%';

        // Enviar ao servidor a cada 5 segundos
        const now = Date.now();
        if (now - lastUpdateTime < 5000) return;
        lastUpdateTime = now;

        fetch(progressUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({
                tempo_assistido: Math.floor((percent / 100) * durationSeconds),
                porcentagem_assistida: currentProgress
            })
        }).then(r => r.json()).then(data => {
            if (data.show_assessment) {
                openAssessment();
            }
        }).catch(e => console.error(e));
    }

    function handleAssessmentSubmit(e) {
        e.preventDefault();
        const answer = document.querySelector('input[name="answer"]:checked')?.value;
        if (!answer) return;

        fetch(assessmentUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify({ answer })
        }).then(r => r.json()).then(data => {
            document.getElementById('assessment-message').textContent = data.message;
            document.getElementById('assessment-message').className = 'text-sm font-medium text-' + (data.success ? 'green' : 'red') + '-600';
            if (data.success) setTimeout(() => location.reload(), 1000);
        }).catch(e => console.error(e));
    }

    document.getElementById('assessment-form').addEventListener('submit', handleAssessmentSubmit);

    // Rastreamento de progresso simples
    if (trainingType === 'upload') {
        const video = document.getElementById('training-video');
        video.addEventListener('timeupdate', () => {
            const percent = (video.currentTime / video.duration) * 100;
            updateProgress(percent);
            if (percent >= 90) openAssessment();
        });
    } else {
        // Para YouTube e Vimeo, simular progresso a cada 2 segundos
        let simulatedProgress = currentProgress;
        setInterval(() => {
            if (simulatedProgress < 100) {
                simulatedProgress += Math.random() * 5;
                updateProgress(Math.min(simulatedProgress, 100));
                if (simulatedProgress >= 90) openAssessment();
            }
        }, 2000);
    }

    function downloadCertificate(trainingId) {
        alert('Certificado liberado para download na próxima etapa do fluxo.');
    }
</script>
@endsection
