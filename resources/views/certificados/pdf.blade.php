<style>
    body {
        font-family: Helvetica, Arial, sans-serif;
        color: #0f172a;
        font-size: 10pt;
    }

    .page {
        border: 1px solid #e6eaf0;
        border-radius: 12px;
        padding: 12px;
    }

    .header {
        background: #0f172a;
        color: #ffffff;
        padding: 10px 12px;
        border-radius: 10px;
    }

    .muted {
        color: #475569;
    }

    .badge {
        display: inline-block;
        padding: 4px 10px;
        border-radius: 999px;
        background: #ecfeff;
        color: #155e75;
        font-weight: bold;
        font-size: 9pt;
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
        padding: 12px;
        background: #f8fafc;
    }
</style>

<div class="page">
    <div class="header">
        <table>
            <tr>
                <td>
                    <div style="font-size: 8pt; letter-spacing: 1px; text-transform: uppercase; color: #c7d2fe;">Certificado de Conclusão</div>
                    <div style="font-size: 18pt; font-weight: 700; margin-top: 4px;">{{ $certificate->training->titulo }}</div>
                </td>
                <td style="text-align: right; font-size: 10pt;">
                    <div>Código: <strong>{{ $certificate->codigo_certificado }}</strong></div>
                    <div>Emissão: {{ $certificate->data_emissao->format('d/m/Y H:i') }}</div>
                </td>
            </tr>
        </table>
    </div>

    <table style="margin-top: 18px;">
        <tr>
            <td style="width: 68%; padding-right: 14px;">
                <div class="box">
                    <div class="badge">Beneficiário</div>
                    <h2 style="font-size: 18pt; margin: 6px 0 6px 0; color: #0b1220; font-weight:800;">{{ $certificate->user->nome }}</h2>

                    <table style="margin-top: 10px;">
                        <tr>
                            <td style="width: 50%; padding-bottom: 10px;"><strong>CPF:</strong> {{ $certificate->user->getCpfFormatted() }}</td>
                            <td style="width: 50%; padding-bottom: 10px;"><strong>E-mail:</strong> {{ $certificate->user->email }}</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 10px;"><strong>Empresa:</strong> {{ $certificate->user->empresa ?? 'Não informada' }}</td>
                            <td style="padding-bottom: 10px;"><strong>Cargo:</strong> {{ $certificate->user->cargo ?? 'Não informado' }}</td>
                        </tr>
                        <tr>
                            <td style="padding-bottom: 10px;"><strong>Telefone:</strong> {{ $certificate->user->telefone ?? 'Não informado' }}</td>
                            <td style="padding-bottom: 10px;"><strong>Tipo de usuário:</strong> {{ $certificate->user->tipo_usuario ?? 'Não informado' }}</td>
                        </tr>
                    </table>

                    <div style="margin-top: 12px; border-top: 1px solid #e2e8f0; padding-top: 12px;">
                        <div class="badge">Conteúdo concluído</div>
                        <div style="font-size: 16pt; font-weight: bold; margin-top: 8px;">{{ $certificate->training->titulo }}</div>
                        <div class="muted" style="margin-top: 4px;">{{ $certificate->training->descricao }}</div>
                    </div>

                    <table style="margin-top: 14px;">
                        <tr>
                            <td style="width: 25%;"><strong>Carga horária:</strong><br>{{ $certificate->training->carga_horaria }} min</td>
                            <td style="width: 25%;"><strong>Início:</strong><br>{{ optional($certificate->data_inicio_assistencia)->format('d/m/Y H:i') }}</td>
                            <td style="width: 25%;"><strong>Finalização:</strong><br>{{ optional($certificate->data_finalizacao_assistencia)->format('d/m/Y H:i') }}</td>
                            <td style="width: 25%;"><strong>Tempo assistido:</strong><br>{{ $tempoAssistidoFormatado }}</td>
                        </tr>
                    </table>

                    <div style="margin-top: 14px;">
                        <strong>Código de validação:</strong>
                        <div style="margin-top: 6px; padding: 8px 10px; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; font-family: monospace; font-size: 11pt;">{{ $certificate->codigo_certificado }}</div>
                    </div>

                    <div style="margin-top: 12px;">
                        <strong>Instrutor:</strong>
                        <div style="margin-top:6px;" class="muted">Ornilio Machado Neto, Tec Seguranca do trabalho, RG - 10827, Bombeiro Civil</div>
                    </div>
                </div>
            </td>

            <td style="width: 32%;">
                <div class="box" style="text-align: center; min-height: 240px;">
                    <div class="badge">QR Code</div>
                    <div style="margin-top: 12px; width: 140px; height: 140px; margin-left: auto; margin-right: auto; border: 1px solid #dbe2ea; border-radius: 12px; background: #ffffff;"></div>
                    <div class="muted" style="word-break: break-all; font-size: 8pt; margin-top: 16px; line-height: 1.2;">{{ $validationUrl }}</div>
                </div>

                <div class="box" style="margin-top: 14px;">
                    <div style="font-weight: bold; margin-bottom: 8px;">Resumo de auditoria</div>
                    <div><strong>Válido:</strong> Sim</div>
                    <div><strong>Emitido em:</strong> {{ $certificate->data_emissao->format('d/m/Y H:i') }}</div>
                    <div><strong>Concluído em:</strong> {{ optional($certificate->data_finalizacao_assistencia)->format('d/m/Y H:i') }}</div>
                </div>
            </td>
        </tr>
    </table>
</div>