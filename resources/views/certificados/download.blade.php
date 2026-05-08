<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Certificado - {{ $certificate->training->titulo }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @page {
            size: A4 landscape;
            margin: 12mm;
        }

        @media print {
            .print-hidden {
                display: none !important;
            }

            body {
                background: #ffffff !important;
            }
        }
    </style>
</head>
<body class="min-h-screen bg-gradient-to-br from-slate-50 via-white to-blue-50 text-slate-900">
    <div class="mx-auto flex min-h-screen max-w-6xl items-center justify-center px-4 py-10">
        <div class="w-full overflow-hidden rounded-[2rem] border border-slate-200 bg-white shadow-[0_30px_80px_rgba(15,23,42,0.15)]">
            <div style="background: linear-gradient(90deg,#153B2E 0%, #0F2B22 100%);" class="px-8 py-6 text-white">
                <div class="flex flex-col gap-2 md:flex-row md:items-end md:justify-between">
                    <div>
                        <div class="flex items-center gap-4">
                            <x-logo alt="Logo" height="42px" />
                            <div>
                                <p class="text-xs uppercase tracking-[0.35em]" style="color: rgba(255,255,255,0.85);">Certificado de Conclusão</p>
                                <h1 class="mt-2 text-3xl font-bold md:text-4xl">{{ $certificate->training->titulo }}</h1>
                            </div>
                        </div>
                    </div>
                    <div class="text-sm text-blue-100">
                        <p>Código: <span class="font-mono font-semibold">{{ $certificate->codigo_certificado }}</span></p>
                        <p>Emitido em: {{ $certificate->data_emissao->format('d/m/Y H:i') }}</p>
                    </div>
                </div>
            </div>

            <div class="grid gap-0 lg:grid-cols-[1.55fr_0.85fr]">
                <div class="p-8 md:p-10">
                    <div class="mb-8">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Beneficiário</p>
                        <h2 class="mt-2 text-4xl font-black text-slate-900">{{ $certificate->user->nome }}</h2>
                        <div class="mt-4 grid gap-4 md:grid-cols-2">
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">CPF</p>
                                <p class="text-lg font-semibold">{{ $certificate->user->getCpfFormatted() }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">E-mail</p>
                                <p class="text-lg font-semibold break-all">{{ $certificate->user->email }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Empresa</p>
                                <p class="text-lg font-semibold">{{ $certificate->user->empresa ?? 'Não informada' }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tipo de usuário</p>
                                <p class="text-lg font-semibold">{{ ucfirst($certificate->user->tipo_usuario ?? 'Não informado') }}</p>
                            </div>
                            <div>
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Telefone</p>
                                <p class="text-lg font-semibold">{{ $certificate->user->telefone ?? 'Não informado' }}</p>
                            </div>
                        </div>
                    </div>

                    <div class="rounded-3xl bg-slate-50 p-6 ring-1 ring-slate-200">
                        <p class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500">Conteúdo concluído</p>
                        <p class="mt-2 text-2xl font-bold text-slate-900">{{ $certificate->training->titulo }}</p>
                        <p class="mt-3 text-slate-600">{{ $certificate->training->descricao }}</p>

                        <div class="mt-6 grid gap-4 md:grid-cols-2 xl:grid-cols-4">
                            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Carga horária</p>
                                <p class="mt-2 text-lg font-bold">{{ $certificate->training->carga_horaria }} min</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Início</p>
                                <p class="mt-2 text-lg font-bold">{{ optional($certificate->data_inicio_assistencia)->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Finalização</p>
                                <p class="mt-2 text-lg font-bold">{{ optional($certificate->data_finalizacao_assistencia)->format('d/m/Y H:i') }}</p>
                            </div>
                            <div class="rounded-2xl bg-white p-4 shadow-sm ring-1 ring-slate-200">
                                <p class="text-xs font-semibold uppercase tracking-wide text-slate-500">Tempo assistido</p>
                                <p class="mt-2 text-lg font-bold">{{ $tempoAssistidoFormatado }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="border-t border-slate-200 bg-gradient-to-b from-emerald-50 to-white p-8 lg:border-l lg:border-t-0">
                    <div class="flex h-full flex-col items-center justify-between gap-6">
                        <div class="text-center">
                            <p class="text-sm font-semibold uppercase tracking-[0.25em] text-emerald-700">Validação</p>
                            <div class="mx-auto mt-5 rounded-3xl bg-white p-4 shadow-lg ring-1 ring-emerald-100">
                                <img src="{{ $qrCodeUrl }}" alt="QR Code de validação" class="h-56 w-56">
                            </div>
                            <p class="mt-4 text-sm text-slate-600 break-all">{{ $validationUrl }}</p>
                        </div>

                        <div class="w-full space-y-3 rounded-3xl bg-white p-5 shadow-sm ring-1 ring-slate-200">
                            <div class="flex justify-between gap-4 text-sm">
                                <span class="font-semibold text-slate-500">Código</span>
                                <span class="font-mono text-slate-900">{{ $certificate->codigo_certificado }}</span>
                            </div>
                            <div class="flex justify-between gap-4 text-sm">
                                <span class="font-semibold text-slate-500">Emissão</span>
                                <span class="text-slate-900">{{ $certificate->data_emissao->format('d/m/Y H:i') }}</span>
                            </div>
                            <div class="flex justify-between gap-4 text-sm">
                                <span class="font-semibold text-slate-500">Válido</span>
                                <span class="font-semibold text-emerald-700">Sim</span>
                            </div>
                        </div>

                        <div class="print-hidden flex w-full gap-3">
                            <button onclick="window.print()" class="flex-1 rounded-xl bg-slate-900 px-4 py-3 font-semibold text-white transition hover:bg-slate-800">
                                Imprimir / Salvar PDF
                            </button>
                            <a href="{{ route('certificados.meus') }}" class="rounded-xl bg-slate-200 px-4 py-3 font-semibold text-slate-800 transition hover:bg-slate-300">
                                Voltar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('load', function () {
            setTimeout(() => window.print(), 500);
        });
    </script>
</body>
</html>