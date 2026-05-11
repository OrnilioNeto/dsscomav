<style>
    body {
        font-family: Helvetica, Arial, sans-serif;
        color: #0f172a;
        font-size: 8.8pt;
    }

    .page {
        border: 1px solid #e6eaf0;
        border-radius: 8px;
        padding: 6px;
    }

    .header {
        /* compact header for PDF */
        background: transparent;
        color: #0f172a;
        padding: 6px 6px;
        border-radius: 6px;
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
        padding: 8px;
        background: #f8fafc;
    }

    .label {
        font-size: 7.5pt;
        text-transform: uppercase;
        letter-spacing: .7px;
        color: #64748b;
        font-weight: 700;
    }
</style>

<div class="page">
    <div class="header">
        <table>
            <tr>
                <td style="text-align: center;">
                    @php
                        $logoPath = file_exists(public_path('images/logo-comav-transportes.png'))
                            ? public_path('images/logo-comav-transportes.png')
                            : public_path('imagens/logo-comav-transportes.png');
                        $logoBase64 = file_exists($logoPath) ? 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath)) : null;
                    @endphp

                    @if($logoBase64)
                        <img src="{{ $logoBase64 }}" width="42" style="display:block; margin:0 auto;">
                    @endif

                    <div style="font-size: 9pt; letter-spacing: 1px; text-transform: uppercase; color: #0b1220; margin-top:6px;">Certificado de Conclusão</div>
                    <div style="font-size: 15pt; font-weight: 800; margin-top: 3px; color: #0b1220;">{{ $certificate->training->titulo }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table style="margin-top: 8px;">
        <tr>
            <td style="width: 70%; padding-right: 10px;">
                <div class="box">
                    <div class="badge">Beneficiário</div>
                    <h2 style="font-size: 15pt; margin: 4px 0 5px 0; color: #0b1220; font-weight: 800;">{{ $certificate->user->nome }}</h2>

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

                    <div style="margin-top: 7px; border-top: 1px solid #e2e8f0; padding-top: 7px;">
                        <div class="badge">Conteúdo concluído</div>
                        <div class="muted" style="margin-top: 4px; font-size: 8pt; line-height: 1.25;">{{ $certificate->training->descricao }}</div>
                    </div>

                    <table style="margin-top: 8px;">
                        <tr>
                            <td style="width: 25%;"><strong>Carga horária:</strong><br>{{ $certificate->training->carga_horaria }} min</td>
                            <td style="width: 25%;"><strong>Início do Treinamento:</strong><br>{{ optional($certificate->data_inicio_assistencia)->format('d/m/Y H:i') }}</td>
                            <td style="width: 25%;"><strong>Fim do Treinamento:</strong><br>{{ optional($certificate->data_finalizacao_assistencia)->format('d/m/Y H:i') }}</td>
                            <td style="width: 25%;"><strong>Tempo assistido:</strong><br>{{ $tempoAssistidoFormatado }}</td>
                        </tr>
                    </table>

                    <div style="margin-top: 8px;">
                        <strong>Data do Conteúdo:</strong>
                        <div style="margin-top: 4px; font-size: 8pt;" class="muted">
                            {{ optional($certificate->training->data_liberacao)->format('d/m/Y H:i') ?? 'Não informada' }}
                        </div>
                    </div>

                    <div style="margin-top: 8px;">
                        <strong>Instrutor:</strong>
                        <div style="margin-top: 4px; font-size: 8pt;" class="muted">Ornilio Machado Neto, Tec Seguranca do trabalho, RG - 10827, Bombeiro Civil</div>
                    </div>
                </div>
            </td>

            <td style="width: 32%;">
                <div class="box" style="text-align: center; min-height: 150px;">

                <div class="badge">QR Code</div>

                <div style="
                    margin-top: 6px;
                    width: 120px;
                    height: 120px;
                    margin-left: auto;
                    margin-right: auto;
                    border: 1px solid #dbe2ea;
                    border-radius: 8px;
                    background: #ffffff;
                    padding: 4px;
                    box-sizing: border-box;
                ">
                    @if(!empty($qrDataUri))
                        <img src="{{ $qrDataUri }}" width="110" height="110" style="display:block;">
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

                <div class="box" style="margin-top: 8px;">
                    <div style="font-weight: bold; margin-bottom: 6px;">Resumo de auditoria</div>
                    <div><strong>Válido:</strong> Sim</div>
                    <div><strong>Código:</strong> <span style="font-family: monospace;">{{ $certificate->codigo_certificado }}</span></div>
                    <div><strong>Emitido em:</strong> {{ $certificate->data_emissao->format('d/m/Y H:i') }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>