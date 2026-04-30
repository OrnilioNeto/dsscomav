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
                controlsList="nodownload nofullscreen noremoteplayback"
            >
                <source src="{{ $training->url_video }}" type="video/mp4">
                Seu navegador não suporta vídeo HTML5.
            </video>
            <div class="mt-4 flex gap-2 justify-center">
                <button id="play-btn" type="button" class="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-800">
                    <i class="fas fa-play mr-2"></i>Play
                </button>
                <button id="pause-btn" type="button" class="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-800">
                    <i class="fas fa-pause mr-2"></i>Pausa
                </button>
                <button id="mute-btn" type="button" class="bg-blue-900 text-white px-6 py-2 rounded-lg hover:bg-blue-800">
                    <i class="fas fa-volume-mute mr-2"></i>Mudo
                </button>
            </div>
        @else
            <div class="relative w-full overflow-hidden rounded-xl bg-black" style="padding-top: 56.25%;">
                @if($training->tipo_video === 'youtube')
                    <iframe
                        id="training-video"
                        class="absolute inset-0 h-full w-full"
                        src="{{ $training->getVideoEmbed() }}"
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

        <div class="mt-5 flex justify-center">
            <button id="assessment-btn" type="button" class="hidden rounded-lg bg-emerald-600 px-6 py-3 font-bold text-white hover:bg-emerald-700 transition">
                <i class="fas fa-clipboard-check mr-2"></i>Realizar avaliação
            </button>
        </div>
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
                <li><strong>Não é permitido adiantar o vídeo.</strong></li>
                <li>A partir de 90% será liberado o botão para realizar a avaliação.</li>
                <li>Responda corretamente para concluir o treinamento.</li>
            </ul>
        </div>
    </div>

    <div class="flex gap-4">
        <a href="{{ route('dashboard') }}" class="flex-1 bg-gray-400 text-white font-bold py-3 px-6 rounded-lg hover:bg-gray-500 transition text-center">
            <i class="fas fa-arrow-left mr-2"></i>Voltar
        </a>
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

<script>
    const progressUrl = '{{ route('treinamentos.atualizar-progresso', $training->id) }}';
    const assessmentUrl = '{{ route('treinamentos.avaliacao', $training->id) }}';
    const csrfToken = '{{ csrf_token() }}';
    const hasAssessment = {{ $training->hasAssessment() ? 'true' : 'false' }};
    const trainingType = '{{ $training->tipo_video }}';
    const registeredDurationSeconds = {{ (int) $training->carga_horaria * 60 }};

    let currentProgress = {{ $progress->porcentagem_assistida }};
    let assessmentOpened = {{ $progress->avaliacao_aprovada ? 'true' : 'false' }};
    let assessmentUnlocked = {{ $progress->porcentagem_assistida >= 90 ? 'true' : 'false' }};
    let lastUpdateTime = 0;
    let lastSafeTime = {{ (int) $progress->tempo_assistido }};
    let assessmentAttempt = {{ (int) ($progress->avaliacao_tentativas ?? 0) }};

    let ultimoTempo = lastSafeTime;
    let watchedSeconds = 0; // SEMPRE começa do 0 - será restaurado quando p play foi realmente acionado
    let dataInicioLocal = null; // ISO string from client local time
    let referenceDuration = registeredDurationSeconds; // will be overridden by video.duration when available
    let ultimoEnvio = 0;
    let playStartedAt = null;
    let playBaseTime = lastSafeTime;
    let hasReallyStartedPlayback = false; // flag para evitar contar sem play real
    let youtubePlayer = null;
    let youtubeTrackingTimer = null;
    let youtubeDuration = registeredDurationSeconds;
    
    // Constantes de bloqueio (conforme prompt)
    const AVANÇO_MÁXIMO_UX = 2; // segundos - limiar cliente (UX)
    const AVANÇO_MÁXIMO_SERVIDOR = 10; // segundos - validado no servidor

    // Para upload, zera a UI de progresso até que realmente toque
    if (trainingType === 'upload') {
        currentProgress = 0;
        document.getElementById('progress-percent').textContent = '0%';
        document.getElementById('progress-bar').style.width = '0%';
    }

    function podeAvancar(tempoAtual) {
        return tempoAtual <= ultimoTempo;
    }

    function unlockAssessmentButton() {
        if (!hasAssessment || assessmentOpened) return;

        assessmentUnlocked = true;
        const button = document.getElementById('assessment-btn');
        const status = document.getElementById('assessment-status');

        if (button) {
            button.classList.remove('hidden');
        }

        if (status) {
            status.textContent = 'A avaliação já está liberada. Clique no botão abaixo do vídeo para continuar.';
        }
    }

    function setCertificateSuccessMessage() {
        const status = document.getElementById('assessment-status');
        const button = document.getElementById('assessment-btn');

        if (status) {
            status.innerHTML = '<span class="text-green-700 font-semibold">Certificado gerado com sucesso. Você pode acessá-lo na aba de certificados.</span>';
        }

        if (button) {
            button.classList.add('hidden');
        }
    }

    function openAssessment() {
        if (!hasAssessment || assessmentOpened || !assessmentUnlocked) return;
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
                tempo_assistido: watchedSeconds,
                porcentagem_assistida: currentProgress
            })
        }).then(r => r.json()).then(data => {
            if (data.show_assessment || currentProgress >= 90) {
                unlockAssessmentButton();
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
            if (data.success) {
                setCertificateSuccessMessage();
                setTimeout(() => closeAssessment(), 900);
                return;
            }

            if (data.reset_required) {
                closeAssessment();
                setTimeout(() => location.reload(), 1200);
            }
        }).catch(e => console.error(e));
    }

    document.getElementById('assessment-form').addEventListener('submit', handleAssessmentSubmit);
    document.getElementById('assessment-btn')?.addEventListener('click', openAssessment);

    if (currentProgress >= 90) {
        unlockAssessmentButton();
    }

    if (trainingType === 'upload') {
        const video = document.getElementById('training-video');
        video.controls = false;

        // Aguarda metadata para obter video.duration
        video.addEventListener('loadedmetadata', () => {
            const videoDuration = Math.floor(video.duration || registeredDurationSeconds);
            referenceDuration = Math.max(1, Math.min(videoDuration, registeredDurationSeconds));
            // Ajusta currentTime para último progresso
            video.currentTime = Math.min(ultimoTempo, referenceDuration);
            console.log('[INIT] ultimoTempo=' + ultimoTempo + ', videoDuration=' + video.duration + ', referencia=' + referenceDuration + 's');
        });

        // CAMADA 1: RAF LOOP removido - causing loop oscillation
        // Bloqueio agora feito via seeking event + timeupdate

        // CAMADA 2: BLOQUEAR TECLADO - TODAS as keys que avançam vídeo
        document.addEventListener('keydown', (e) => {
            if (['ArrowRight', 'ArrowLeft', ' ', 'j', 'l', 'k'].includes(e.key)) {
                e.preventDefault();
                e.stopPropagation();
                return false;
            }
        }, true);

        // Também bloquear wheel (scroll)
        video.addEventListener('wheel', (e) => {
            e.preventDefault();
        }, false);

        // CAMADA 3: SEEKING EVENT - detecta clique na barra - BLOQUEIO PRINCIPAL
        let blockSeeking = false;
        video.addEventListener('seeking', function () {
            const tempo = video.currentTime;
            
            // Bloqueia qualquer tentativa de avanço além de ultimoTempo
            if (tempo > ultimoTempo + 0.01) {
                blockSeeking = true;
                video.currentTime = ultimoTempo;
                console.log('SEEKING BLK: tentou ' + tempo.toFixed(2) + ' -> revert para ' + ultimoTempo.toFixed(2));
                
                // Desbloqueia após 500ms para permitir play normal depois
                setTimeout(() => { blockSeeking = false; }, 500);
            }
        }, false);

        video.addEventListener('play', function () {
            hasReallyStartedPlayback = true; // marcar que play foi acionado
            playStartedAt = Date.now();
            playBaseTime = Math.max(0, watchedSeconds); // base é o que foi contado até agora
            if (!dataInicioLocal) {
                dataInicioLocal = new Date().toISOString();
                salvarProgresso((watchedSeconds / referenceDuration) * 100);
            }
            console.log('[PLAY] watchedSeconds=' + watchedSeconds + ' playBaseTime=' + playBaseTime);
        });

        video.addEventListener('pause', function () {
            if (hasReallyStartedPlayback && playStartedAt) {
                const elapsed = Math.max(0, (Date.now() - playStartedAt) / 1000);
                watchedSeconds = Math.max(watchedSeconds, Math.min(referenceDuration, Math.floor(playBaseTime + elapsed)));
                ultimoTempo = Math.max(ultimoTempo, video.currentTime);
                salvarProgresso((watchedSeconds / referenceDuration) * 100);
                console.log('[PAUSE] watchedSeconds=' + watchedSeconds);
            }
            playStartedAt = null;
        });

        video.addEventListener('timeupdate', function () {
            const tempo = video.currentTime;

            // Se está em processo de bloqueio, ignora tudo
            if (blockSeeking) {
                return;
            }

            // FALLBACK: Se por algum motivo chegou além de ultimoTempo, reverte
            if (tempo > ultimoTempo + 0.01) {
                video.currentTime = ultimoTempo;
                console.log('TIMEUPDATE BLK: tentou ' + tempo.toFixed(2) + ' -> revert para ' + ultimoTempo.toFixed(2));
                return;
            }

            // CRÍTICO: NUNCA incrementa watchedSeconds sem play real ter sido acionado
            if (hasReallyStartedPlayback && playStartedAt && !video.paused) {
                const elapsed = Math.max(0, (Date.now() - playStartedAt) / 1000);
                watchedSeconds = Math.max(watchedSeconds, Math.min(referenceDuration, Math.floor(playBaseTime + elapsed)));
            }

            const tempoAntes = ultimoTempo;
            ultimoTempo = Math.max(ultimoTempo, tempo);
            if (ultimoTempo > tempoAntes) {
                console.log('UPDATE: ultimoTempo ' + tempoAntes.toFixed(2) + ' -> ' + ultimoTempo.toFixed(2));
            }

            const ref = referenceDuration || Math.max(1, registeredDurationSeconds);
            const percent = Math.min(100, (watchedSeconds / ref) * 100);
            updateProgress(percent);

            const agora = Date.now();
            if (agora - ultimoEnvio > 5000) {
                salvarProgresso(percent);
                ultimoEnvio = agora;
            }

            if (percent >= 90 && hasReallyStartedPlayback) {
                unlockAssessmentButton();
            }
        });

        video.addEventListener('ended', function () {
            if (!hasReallyStartedPlayback) return; // não fazer nada se nunca começou
            ultimoTempo = referenceDuration || video.duration;
            watchedSeconds = referenceDuration || Math.floor(video.duration || registeredDurationSeconds);
            // marcar conclusão com horário local
            salvarProgresso(100, true);
            playStartedAt = null;
        });

        document.getElementById('play-btn').addEventListener('click', () => video.play());
        document.getElementById('pause-btn').addEventListener('click', () => video.pause());
        document.getElementById('mute-btn').addEventListener('click', () => {
            video.muted = !video.muted;
        });

        video.addEventListener('contextmenu', (e) => e.preventDefault());

    } else if (trainingType === 'youtube') {
        let youtubeBlockSeeking = false;
        let youtubeLastTempo = 0; // rastreia o último tempo conhecido
        
        function loadYoutubeApi() {
            if (window.YT && window.YT.Player) {
                initializeYoutubePlayer();
                return;
            }

            if (window.__youtubeApiLoading) {
                return;
            }

            window.__youtubeApiLoading = true;
            window.onYouTubeIframeAPIReady = function () {
                initializeYoutubePlayer();
            };

            const script = document.createElement('script');
            script.src = 'https://www.youtube.com/iframe_api';
            document.head.appendChild(script);
        }

        function initializeYoutubePlayer() {
            youtubePlayer = new YT.Player('training-video', {
                events: {
                    onReady: function () {
                        const duration = Math.floor(youtubePlayer.getDuration() || registeredDurationSeconds);
                        youtubeDuration = Math.max(1, Math.min(duration, registeredDurationSeconds));
                        const startTime = Math.min(ultimoTempo, youtubeDuration);
                        youtubePlayer.seekTo(startTime, true);
                        youtubeLastTempo = startTime;
                        console.log('[YOUTUBE INIT] duration=' + youtubeDuration + ' start=' + startTime);
                    },
                    onStateChange: function (event) {
                        if (event.data === YT.PlayerState.PLAYING) {
                            hasReallyStartedPlayback = true;
                            playStartedAt = Date.now();
                            playBaseTime = Math.max(0, watchedSeconds);
                            youtubeBlockSeeking = false;
                            if (!dataInicioLocal) {
                                dataInicioLocal = new Date().toISOString();
                                salvarProgresso((watchedSeconds / youtubeDuration) * 100);
                            }

                            if (!youtubeTrackingTimer) {
                                youtubeTrackingTimer = setInterval(() => {
                                    if (!youtubePlayer || typeof youtubePlayer.getCurrentTime !== 'function') {
                                        return;
                                    }

                                    const tempo = youtubePlayer.getCurrentTime();
                                    const deltaTempo = tempo - youtubeLastTempo; // quanto avançou desde última leitura

                                    // Se fez um PULO grande (> 2s em uma leitura), bloqueia UMA VEZ
                                    if (deltaTempo > AVANÇO_MÁXIMO_UX && !youtubeBlockSeeking) {
                                        youtubeBlockSeeking = true;
                                        const maxPermitido = youtubeLastTempo + AVANÇO_MÁXIMO_UX;
                                        youtubePlayer.seekTo(maxPermitido, true);
                                        youtubeLastTempo = maxPermitido;
                                        console.log('[YOUTUBE] BLOQUEADO: tentou pulo de ' + tempo.toFixed(2) + ' (Δ=' + deltaTempo.toFixed(2) + 's) -> revert para ' + maxPermitido.toFixed(2));
                                        
                                        setTimeout(() => { youtubeBlockSeeking = false; }, 1000);
                                        return;
                                    }

                                    // Playback normal: atualiza referências
                                    if (hasReallyStartedPlayback && playStartedAt) {
                                        const elapsed = Math.max(0, (Date.now() - playStartedAt) / 1000);
                                        watchedSeconds = Math.max(watchedSeconds, Math.min(youtubeDuration, Math.floor(playBaseTime + elapsed)));
                                    }

                                    ultimoTempo = Math.max(ultimoTempo, tempo);
                                    youtubeLastTempo = tempo; // sempre atualiza lastTempo

                                    const percent = Math.min(100, (watchedSeconds / Math.max(1, youtubeDuration)) * 100);
                                    updateProgress(percent);

                                    const agora = Date.now();
                                    if (agora - ultimoEnvio > 5000) {
                                        salvarProgresso(percent);
                                        ultimoEnvio = agora;
                                    }

                                    if (percent >= 90 && hasReallyStartedPlayback) {
                                        unlockAssessmentButton();
                                    }
                                }, 1000);
                            }
                        }

                        if (event.data === YT.PlayerState.PAUSED || event.data === YT.PlayerState.ENDED) {
                            if (hasReallyStartedPlayback && playStartedAt && youtubePlayer) {
                                const tempo = Math.max(0, Math.floor(youtubePlayer.getCurrentTime() || 0));
                                const elapsed = Math.max(0, (Date.now() - playStartedAt) / 1000);
                                watchedSeconds = Math.max(watchedSeconds, Math.min(youtubeDuration, Math.floor(playBaseTime + elapsed)));
                                ultimoTempo = Math.max(ultimoTempo, tempo);
                                youtubeLastTempo = tempo;
                                salvarProgresso((watchedSeconds / Math.max(1, youtubeDuration)) * 100);
                            }

                            playStartedAt = null;

                            if (event.data === YT.PlayerState.ENDED) {
                                if (!hasReallyStartedPlayback) return;
                                ultimoTempo = youtubeDuration;
                                watchedSeconds = youtubeDuration;
                                salvarProgresso(100, true);
                                closeAssessment();
                            }

                            if (youtubeTrackingTimer) {
                                clearInterval(youtubeTrackingTimer);
                                youtubeTrackingTimer = null;
                            }
                        }
                    }
                }
            });
        }

        loadYoutubeApi();
    }

    function salvarProgresso(percent) {
        const payload = {
            tempo_assistido: watchedSeconds,
            porcentagem_assistida: Math.floor(percent),
        };

        if (dataInicioLocal) payload.data_inicio_assistencia = dataInicioLocal;
        if (percent >= 100) payload.data_finalizacao_assistencia = new Date().toISOString();

        fetch(progressUrl, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        }).catch(e => console.error(e));
    }

</script>
@endsection
