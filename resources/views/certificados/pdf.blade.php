<style>
    body {
        font-family: Helvetica, Arial, sans-serif;
        color: #0f172a;
        font-size: 9pt;
    }

    .page {
        border: 1px solid #e6eaf0;
        border-radius: 12px;
        padding: 8px;
    }

    .header {
        background: #153B2E;
        color: #ffffff;
        padding: 8px 10px;
        border-radius: 10px;
    }

    .muted {
        color: #475569;
    }

    .badge {
        display: inline-block;
        padding: 3px 8px;
        border-radius: 999px;
        background: #FFF4E6;
        color: #F28C2B;
        font-weight: bold;
        font-size: 8pt;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    td {
        vertical-align: top;
    }

    .box {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 9px;
        background: #f8fafc;
    }
</style>

<div class="page">
    <div class="header">
        <table>
            <tr>
                <td>
                    <table>
                        <tr>
                            <!-- LOGO -->
                            <td style="width: 60px; vertical-align: middle;">
                                @php
                                    $logoPath = file_exists(public_path('images/logo-comav-transportes.png'))
                                        ? public_path('images/logo-comav-transportes.png')
                                        : public_path('imagens/logo-comav-transportes.png');
                                    $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
                                @endphp

                                @if($logoBase64)
                                    <img src="{{ $logoBase64 }}" width="50">
                                @endif
                            </td>

                            <!-- TEXTO -->
                            <td style="vertical-align: middle;">
                                <div style="font-size: 8pt; letter-spacing: 1px; text-transform: uppercase; color: #090909;">
                                    Certificado de Conclusão
                                </div>
                                <div style="font-size: 16pt; font-weight: 700; margin-top: 2px;">
                                    {{ $certificate->training->titulo }}
                                </div>
                            </td>
                        </tr>
                    </table>
                </td>
                <td style="text-align: right; font-size: 9pt;">
                    <div>Código: <strong>{{ $certificate->codigo_certificado }}</strong></div>
                    <div>Emissão: <span id="horaEmissaoPdf">{{ $certificate->data_emissao->format('d/m/Y H:i') }}</span></div>
                </td>
            </tr>
        </table>
    </div>

    <table style="margin-top: 10px;">
        <tr>
            <td style="width: 70%; padding-right: 10px;">
                <div class="box">
                    <div class="badge">Beneficiário</div>
                    <h2 style="font-size: 16pt; margin: 4px 0 5px 0; color: #0b1220; font-weight: 800;">{{ $certificate->user->nome }}</h2>

                    <table style="margin-top: 6px;">
                        <tr>
                            <td style="width: 50%; padding-bottom: 6px;"><strong>CPF:</strong> {{ $certificate->user->getCpfFormatted() }}</td>
                            <td style="width: 50%; padding-bottom: 6px;"><strong>E-mail:</strong> {{ $certificate->user->email }}</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 6px;"><strong>Empresa:</strong> {{ $certificate->user->empresa ?? 'Não informada' }}</td>
                            <td style="padding-bottom: 6px;"><strong>Tipo de usuário:</strong> {{ ucfirst($certificate->user->tipo_usuario ?? 'Não informado') }}</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 6px;" colspan="2"><strong>Telefone:</strong> {{ $certificate->user->telefone ?? 'Não informado' }}</td>
                        </tr>
                    </table>

                    <div style="margin-top: 8px; border-top: 1px solid #e2e8f0; padding-top: 8px;">
                        <div class="badge">Conteúdo concluído</div>
                        <div style="font-size: 14pt; font-weight: bold; margin-top: 5px;">{{ $certificate->training->titulo }}</div>
                        <div class="muted" style="margin-top: 2px; font-size: 8pt; line-height: 1.25;">{{ $certificate->training->descricao }}</div>
                    </div>

                    <table style="margin-top: 9px;">
                        <tr>
                            <td style="width: 25%;"><strong>Carga horária:</strong><br>{{ $certificate->training->carga_horaria }} min</td>
                            <td style="width: 25%;"><strong>Início do Treinamento:</strong><br><span id="horaInicioPdf">{{ optional($certificate->data_inicio_assistencia)->format('d/m/Y H:i') }}</span></td>
                            <td style="width: 25%;"><strong>Fim do Treinamento:</strong><br><span id="horaFimPdf">{{ optional($certificate->data_finalizacao_assistencia)->format('d/m/Y H:i') }}</span></td>
                            <td style="width: 25%;"><strong>Tempo assistido:</strong><br>{{ $tempoAssistidoFormatado }}</td>
                        </tr>
                    </table>

                    <div style="margin-top: 9px;">
                        <strong>Código de validação:</strong>
                        <div style="margin-top: 4px; padding: 6px 8px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; font-family: monospace; font-size: 10pt;">{{ $certificate->codigo_certificado }}</div>
                    </div>

                    <div style="margin-top: 9px;">
                        <strong>Instrutor:</strong>
                        <div style="margin-top: 4px; font-size: 8pt;" class="muted">Ornilio Machado Neto, Tec Seguranca do trabalho, RG - 10827, Bombeiro Civil</div>
                    </div>
                </div>
            </td>

            <td style="width: 32%;">
                <div class="box" style="text-align: center; min-height: 200px;">

                <div class="badge">QR Code</div>

                <div style="
                    margin-top: 6px;
                    width: 140px;
                    height: 140px;
                    margin-left: auto;
                    margin-right: auto;
                    border: 1px solid #dbe2ea;
                    border-radius: 10px;
                    background: #ffffff;
                    padding: 5px;
                    box-sizing: border-box;
                ">
                    @if(!empty($qrDataUri))
                        <img src="{{ $qrDataUri }}" width="130" height="130" style="display:block;">
                    @else
                        <div class="muted" style="font-size: 8pt; margin-top: 50px;">QR indisponível</div>
                    @endif
                </div>

                <div class="muted" style="
                    word-break: break-all;
                    font-size: 6pt;
                    margin-top: 6px;
                    line-height: 1.1;
                ">
                    {{ $validationUrl }}
                </div>

            </div>

                <div class="box" style="margin-top: 10px;">
                    <div style="font-weight: bold; margin-bottom: 6px;">Resumo de auditoria</div>
                    <div><strong>Válido:</strong> Sim</div>
                    <div><strong>Emitido em:</strong> <span id="horaEmissaoPdfResumo">{{ $certificate->data_emissao->format('d/m/Y H:i') }}</span></div>
                    <div><strong>Concluído em:</strong> <span id="horaConcluido">{{ optional($certificate->data_finalizacao_assistencia)->format('d/m/Y H:i') }}</span></div>
                </div>
            </td>
        </tr>
    </table>
</div>