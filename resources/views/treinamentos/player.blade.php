@extends('layout')

@section('title', $training->titulo)

@section('content')
<style>
    /* Esconde controles de velocidade do vídeo HTML5 */
    video::-webkit-media-controls-panel {
        display: flex !important;
    }

    video::-webkit-media-controls-mute-button,
    video::-webkit-media-controls-volume-slider,
    video::-webkit-media-controls-play-button,
    video::-webkit-media-controls-timeline,
    video::-webkit-media-controls-current-time-display,
    video::-webkit-media-controls-time-remaining-display,
    video::-webkit-media-controls-fullscreen-button,
    video::-webkit-media-controls-download-button,
    video::-webkit-media-controls-picture-in-picture-button {
        display: flex !important;
    }

    /* Remove menu de contexto (botão direito) do player */
    .training-video-container {
        user-select: none;
        -webkit-user-select: none;
    }

    /* Para navegadores que permitem speed control via menu */
    video::-webkit-media-text-track-display {
        display: flex !important;
    }
</style>
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

    <div class="training-video-container bg-white p-4 rounded-2xl shadow-lg mb-8">
        @if($training->tipo_video === 'upload')
            <video
                id="training-video"
                class="w-full rounded-xl bg-black"
                controlsList="nodownload nofullscreen noremoteplayback nospeed"
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

    </div>

    @if($assessment)
    <div id="assessment-panel" class="bg-white p-6 rounded-2xl shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-clipboard-check text-emerald-600 mr-2"></i>Avaliação do treinamento
        </h2>

        <div id="assessment-locked-notice" class="{{ $assessmentUnlocked ? 'hidden' : '' }} rounded-lg bg-amber-50 border border-amber-200 p-4 mb-5">
            <p class="text-sm text-amber-900 font-semibold">
                <i class="fas fa-lock mr-2"></i>A avaliação será liberada para resposta após você concluir 100% do vídeo.
            </p>
            <p class="text-xs text-amber-800 mt-1">
                Progresso atual do vídeo: <span id="assessment-lock-progress">{{ $progress->porcentagem_assistida }}%</span>.
                As perguntas abaixo serão habilitadas automaticamente quando o vídeo terminar.
            </p>
        </div>

        <div id="assessment-unlocked-notice" class="{{ $assessmentUnlocked ? '' : 'hidden' }} rounded-lg bg-emerald-50 border border-emerald-200 p-4 mb-5">
            <p class="text-sm text-emerald-900 font-semibold">
                <i class="fas fa-lock-open mr-2"></i>Vídeo concluído! A avaliação está liberada.
            </p>
            <p class="text-xs text-emerald-800 mt-1">Selecione uma opção e clique em "Responder avaliação".</p>
        </div>

        <form id="assessment-form" class="space-y-5">
            @csrf
            @if($training->tipo === 'treinamento')
                <div>
                    <label for="assessment-senha" class="block text-gray-700 font-semibold mb-1">
                        <i class="fas fa-user-lock mr-1"></i>Sua senha de acesso *
                    </label>
                    <input type="password" id="assessment-senha" autocomplete="current-password"
                        placeholder="Digite sua senha para confirmar sua identificação"
                        class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-900 disabled:bg-gray-100 disabled:cursor-not-allowed"
                        {{ $assessmentUnlocked ? '' : 'disabled' }}>
                    <p class="text-xs text-gray-500 mt-1">Re-identificação individual exigida pela NR-01 Anexo II (4.6.1/4.6.2).</p>
                </div>
            @endif

            <div id="assessment-questoes-container" class="space-y-5 {{ $assessmentUnlocked ? '' : 'opacity-60 pointer-events-none' }}">
                @if($assessment['modo'] === 'banco')
                    @foreach($assessment['questoes'] as $qi => $questao)
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <p class="font-semibold text-gray-800 mb-2">{{ $qi + 1 }}. {{ $questao['pergunta'] }}</p>
                            <div class="space-y-2">
                                @foreach($questao['opcoes'] as $oi => $opcao)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 hover:border-blue-500">
                                        <input type="radio" name="respostas[{{ $questao['id'] }}]" value="{{ $oi }}" class="text-blue-900" required {{ $assessmentUnlocked ? '' : 'disabled' }}>
                                        <span class="text-gray-700">{{ $opcao }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                        <p class="font-semibold text-gray-800 mb-2">{{ $assessment['pergunta'] }}</p>
                        <div class="space-y-2">
                            @foreach($assessment['opcoes'] as $oi => $opcao)
                                <label class="flex cursor-pointer items-center gap-3 rounded-lg border border-gray-200 bg-white p-3 hover:border-blue-500">
                                    <input type="radio" name="answer" value="{{ $oi }}" class="text-blue-900" required {{ $assessmentUnlocked ? '' : 'disabled' }}>
                                    <span class="text-gray-700">{{ $opcao }}</span>
                                </label>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>

            <div id="assessment-message" class="text-sm font-medium"></div>

            <button type="submit" id="assessment-submit-btn"
                class="w-full rounded-lg bg-emerald-600 px-6 py-3 font-bold text-white transition hover:bg-emerald-700 disabled:cursor-not-allowed disabled:bg-gray-300 disabled:text-gray-600"
                {{ $assessmentUnlocked ? '' : 'disabled' }}>
                <i class="fas fa-check mr-2"></i>Responder avaliação
            </button>
        </form>
    </div>
    @endif

    <div class="bg-white p-6 rounded-lg shadow-lg mb-8">
        <h2 class="text-xl font-bold mb-4 flex items-center">
            <i class="fas fa-file-download text-green-600 mr-2"></i>Materiais de Apoio
        </h2>

        @if($training->materials && $training->materials->count() > 0)
            <div class="grid gap-3">
                @foreach($training->materials as $material)
                    <div class="flex items-center justify-between bg-gray-50 p-4 rounded border border-gray-200 hover:border-green-300 transition">
                        <div class="flex items-center gap-3 flex-1">
                            <i class="fas {{ $material->getIcone() }} text-2xl"></i>
                            <div class="flex-1">
                                <p class="font-semibold text-gray-800">{{ $material->nome }}</p>
                                @if($material->descricao)
                                    <p class="text-gray-600 text-sm">{{ $material->descricao }}</p>
                                @endif
                                <p class="text-gray-500 text-xs">{{ $material->getTamanhoFormatado() }}</p>
                            </div>
                        </div>
                        <a href="{{ route('materiais.download', $material->id) }}" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition whitespace-nowrap ml-3">
                            <i class="fas fa-download mr-1"></i>Baixar
                        </a>
                    </div>
                @endforeach
            </div>
        @else
            <p class="text-gray-500 text-sm italic">Nenhum material de apoio disponível para este treinamento.</p>
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
                <li>Assista o vídeo completamente para liberar a avaliação.</li>
                <li><strong>Não é permitido adiantar o vídeo.</strong></li>
                <li><strong>A velocidade de reprodução está bloqueada em 1x (normal).</strong></li>
                <li>As perguntas aparecem abaixo do vídeo e são habilitadas automaticamente após 100% de conclusão.</li>
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

<!-- WhatsApp de apoio (NR-01 Anexo II 4.5) -->
<a href="https://wa.me/5584994017097" target="_blank" rel="noopener noreferrer"
   class="fixed bottom-6 right-6 z-40 flex items-center gap-2 rounded-full bg-emerald-600 px-4 py-3 font-bold text-white shadow-xl transition hover:bg-emerald-700"
   title="Dúvidas sobre o curso? Fale com a equipe de apoio.">
    <i class="fab fa-whatsapp text-xl"></i>
    <span class="hidden sm:inline text-sm">Dúvidas? Fale conosco</span>
</a>

<script>
    const progressUrl = '{{ route('treinamentos.atualizar-progresso', $training->id) }}';
    const assessmentUrl = '{{ route('treinamentos.avaliacao', $training->id) }}';
    const csrfToken = '{{ csrf_token() }}';
    const hasAssessment = {{ $assessment ? 'true' : 'false' }};
    // Re-identificação por senha: somente para o tipo "Treinamento" (DSS dispensa)
    const passwordRequired = {{ $training->tipo === 'treinamento' ? 'true' : 'false' }};
    const trainingType = '{{ $training->tipo_video }}';
    const registeredDurationSeconds = {{ (int) $training->carga_horaria * 60 }};

    let currentProgress = {{ $progress->porcentagem_assistida }};
    let assessmentUnlocked = {{ $assessmentUnlocked ? 'true' : 'false' }};
    let lastUpdateTime = 0;
    let lastSafeTime = {{ (int) $progress->tempo_assistido }};

    let ultimoTempo = lastSafeTime;
    let watchedSeconds = lastSafeTime;
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

    function unlockAssessment() {
        if (!hasAssessment || assessmentUnlocked) return;

        const form = document.getElementById('assessment-form');
        const panel = document.getElementById('assessment-panel');
        if (!form || !panel) return;

        assessmentUnlocked = true;

        form.querySelectorAll('input, button').forEach((el) => { el.disabled = false; });

        document.getElementById('assessment-locked-notice')?.classList.add('hidden');
        document.getElementById('assessment-unlocked-notice')?.classList.remove('hidden');
        document.getElementById('assessment-questoes-container')?.classList.remove('opacity-60', 'pointer-events-none');

        const status = document.getElementById('assessment-status');
        if (status) {
            status.textContent = 'A avaliação está liberada. Responda no painel abaixo do vídeo.';
        }
    }

    function setCertificateSuccessMessage() {
        const status = document.getElementById('assessment-status');

        if (status) {
            status.innerHTML = '<span class="text-green-700 font-semibold">Certificado gerado com sucesso. Você pode acessá-lo na aba de certificados.</span>';
        }

        const submitBtn = document.getElementById('assessment-submit-btn');
        if (submitBtn) {
            submitBtn.disabled = true;
        }
    }

    function updateProgress(percent) {
        currentProgress = Math.floor(percent);
        document.getElementById('progress-percent').textContent = currentProgress + '%';
        document.getElementById('progress-bar').style.width = currentProgress + '%';

        const lockProgress = document.getElementById('assessment-lock-progress');
        if (lockProgress) {
            lockProgress.textContent = currentProgress + '%';
        }

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
            if (data.show_assessment || currentProgress >= 99) {
                unlockAssessment();
            }
        }).catch(e => console.error(e));
    }

    function handleAssessmentSubmit(e) {
        e.preventDefault();

        if (!assessmentUnlocked) return;

        const container = document.getElementById('assessment-questoes-container');
        const message = document.getElementById('assessment-message');
        const submitBtn = document.getElementById('assessment-submit-btn');
        const answers = {};
        const checkedBank = container.querySelectorAll('input[type="radio"]:checked');
        let payload = {};

        if (checkedBank.length > 0) {
            const m = checkedBank[0].name.match(/^respostas\[(\d+)\]$/);
            if (m) {
                checkedBank.forEach(r => {
                    const mm = r.name.match(/^respostas\[(\d+)\]$/);
                    if (mm) answers[mm[1]] = r.value;
                });
                payload = { respostas: answers };
            }
        }

        if (Object.keys(payload).length === 0) {
            const answer = container.querySelector('input[name="answer"]:checked')?.value;
            if (answer === undefined) {
                message.textContent = 'Selecione uma opção antes de responder.';
                message.className = 'text-sm font-medium text-red-600';
                return;
            }
            payload = { answer };
        }

        if (passwordRequired) {
            const senha = (document.getElementById('assessment-senha')?.value || '').trim();
            if (!senha) {
                message.textContent = 'Informe sua senha de acesso para confirmar sua identificação.';
                message.className = 'text-sm font-medium text-red-600';
                return;
            }
            payload.password = senha;
        }

        if (submitBtn) submitBtn.disabled = true;
        message.textContent = 'Enviando respostas...';
        message.className = 'text-sm font-medium text-gray-600';

        fetch(assessmentUrl, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': csrfToken
            },
            body: JSON.stringify(payload)
        }).then(async (r) => {
            const contentType = r.headers.get('content-type') || '';
            let data = null;

            if (contentType.includes('application/json')) {
                data = await r.json();
            } else {
                const text = await r.text();
                throw new Error(`Resposta não-JSON (${r.status}). Possível sessão expirada ou erro de rota. Trecho: ${text.slice(0, 120)}`);
            }

            if (!r.ok) {
                return {
                    success: false,
                    reset_required: !!data.reset_required,
                    message: data.message || data.error || `Erro ${r.status} ao validar avaliação.`,
                };
            }

            return data;
        }).then(data => {
            message.textContent = data.message || 'Resposta processada.';
            message.className = 'text-sm font-medium ' + (data.success ? 'text-green-600' : 'text-red-600');

            if (data.success) {
                setCertificateSuccessMessage();
                setTimeout(() => location.reload(), 1800);
                return;
            }

            if (submitBtn) submitBtn.disabled = false;

            if (passwordRequired) {
                const senhaInput = document.getElementById('assessment-senha');
                if (senhaInput) senhaInput.value = '';
            }

            if (data.reset_required) {
                setTimeout(() => location.reload(), 1200);
            }
        }).catch(e => {
            console.error('[ASSESSMENT] erro:', e);
            message.textContent = e?.message || 'Erro ao processar resposta. Tente novamente.';
            message.className = 'text-sm font-medium text-red-600';
            if (submitBtn) submitBtn.disabled = false;
        });
    }

    document.getElementById('assessment-form')?.addEventListener('submit', handleAssessmentSubmit);

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
        // (não bloqueia digitação/seleção nos campos da avaliação e demais formulários)
        document.addEventListener('keydown', (e) => {
            const target = e.target;
            if (target instanceof HTMLElement &&
                (target.closest('input, textarea, select, button') || target.isContentEditable)) {
                return;
            }

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

            if (percent >= 99 && hasReallyStartedPlayback) {
                unlockAssessment();
            }
        });

        video.addEventListener('ended', function () {
            if (!hasReallyStartedPlayback) return; // não fazer nada se nunca começou
            ultimoTempo = referenceDuration || video.duration;
            watchedSeconds = referenceDuration || Math.floor(video.duration || registeredDurationSeconds);
            // marcar conclusão com horário local
            salvarProgresso(100, true);
            unlockAssessment();
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

                                    if (percent >= 99 && hasReallyStartedPlayback) {
                                        unlockAssessment();
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
                                unlockAssessment();
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

    // =========================================================================
    // BLOQUEIO DE VELOCIDADE DE REPRODUÇÃO
    // =========================================================================
    // Força playbackRate = 1.0 sempre, impedindo que usuário acelere ou desacelere

    if (trainingType === 'upload') {
        const video = document.getElementById('training-video');

        // 1. Bloqueia propriedade playbackRate através de Object.defineProperty
        Object.defineProperty(video, 'playbackRate', {
            get() {
                return 1.0;
            },
            set(value) {
                console.warn('🔒 Velocidade de reprodução bloqueada. Apenas 1x permitido.');
                return 1.0;
            },
            configurable: false
        });

        // 2. Monitora mudanças de playbackRate com ratechange event
        video.addEventListener('ratechange', (e) => {
            if (video.playbackRate !== 1.0) {
                console.warn('🔒 Bloqueado: Tentativa de mudar para playbackRate:', video.playbackRate);
                video.playbackRate = 1.0;
            }
        });

        // 3. Bloqueia atributo playbackRate no HTML (caso modificado dinamicamente)
        const observer = new MutationObserver(() => {
            if (video.playbackRate !== 1.0) {
                video.playbackRate = 1.0;
            }
        });

        observer.observe(video, {
            attributes: true,
            attributeFilter: ['playbackRate']
        });

        console.log('✅ Bloqueio de velocidade ativado para vídeo upload');
    } else if (trainingType === 'youtube') {
        // Para YouTube, o controle de velocidade é gerenciado pelo iframe
        // YouTube Player API não expõe playbackRate, então conferir periodicamente
        setInterval(() => {
            if (youtubePlayer && typeof youtubePlayer.getPlaybackRate === 'function') {
                const currentRate = youtubePlayer.getPlaybackRate();
                if (currentRate !== 1.0) {
                    youtubePlayer.setPlaybackRate(1.0);
                    console.warn('🔒 Bloqueado: Velocidade YouTube restaurada para 1.0');
                }
            }
        }, 500);

        console.log('✅ Bloqueio de velocidade ativado para YouTube');
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
