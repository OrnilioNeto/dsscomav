<!DOCTYPE html>
<html>
<head>
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    @page {
        size: A4 landscape;
        margin: 0;
        padding: 0;
    }

    html, body {
        width: 100%;
        height: 100%;
        background: #ffffff;
        margin: 0;
        padding: 0;
    }

    body {
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
        color: #1e3a5f;
        font-size: 11pt;
        line-height: 1.4;
    }

    .certificate {
        width: 100%;
        height: 100vh;
        background: linear-gradient(135deg, #0f172a 0%, #1e3a5f 100%);
        color: #ffffff;
        padding: 50px 70px;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        page-break-after: always;
    }

    .certificate-header {
        text-align: center;
        margin-bottom: 20px;
    }

    .certificate-title {
        font-size: 52pt;
        font-weight: bold;
        letter-spacing: 3px;
        margin-bottom: 8px;
        text-transform: uppercase;
        text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    }

    .certificate-subtitle {
        font-size: 32pt;
        font-weight: bold;
        color: #bfdbfe;
        margin-bottom: 0;
    }

    .certificate-body {
        background: rgba(255, 255, 255, 0.98);
        color: #1e3a5f;
        padding: 45px;
        border-radius: 12px;
        flex-grow: 1;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.2);
    }

    .body-top {
        text-align: center;
        margin-bottom: 20px;
    }

    .recipient-intro {
        font-size: 11pt;
        color: #475569;
        margin-bottom: 12px;
        letter-spacing: 0.5px;
    }

    .recipient-name {
        font-size: 36pt;
        font-weight: bold;
        color: #0f172a;
        margin: 15px 0;
        text-transform: uppercase;
        letter-spacing: 2px;
        text-shadow: 0 1px 1px rgba(0, 0, 0, 0.1);
    }

    .recipient-cpf {
        font-size: 10pt;
        color: #475569;
        margin-bottom: 20px;
    }

    .completion-text {
        font-size: 11pt;
        color: #475569;
        line-height: 1.6;
        margin-bottom: 15px;
        letter-spacing: 0.3px;
    }

    .course-info {
        margin: 15px 0 20px 0;
        padding: 20px;
        background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%);
        border-left: 5px solid #0f172a;
        border-radius: 6px;
    }

    .course-title {
        font-size: 18pt;
        font-weight: bold;
        color: #0f172a;
        margin-bottom: 8px;
    }

    .course-desc {
        font-size: 10pt;
        color: #475569;
        line-height: 1.5;
    }

    .completion-grid {
        display: table;
        width: 100%;
        margin: 20px 0;
        border-collapse: collapse;
    }

    .grid-cell {
        display: table-cell;
        padding: 15px 10px;
        text-align: center;
        border-right: 1px solid #e2e8f0;
        border-bottom: 1px solid #e2e8f0;
    }

    .grid-cell:last-child {
        border-right: none;
    }

    .grid-cell:nth-child(4n) {
        border-right: none;
    }

    .grid-label {
        font-size: 9pt;
        font-weight: bold;
        color: #0f172a;
        text-transform: uppercase;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }

    .grid-value {
        font-size: 12pt;
        font-weight: bold;
        color: #1e3a5f;
    }

    .body-bottom {
        display: table;
        width: 100%;
        margin-top: 15px;
    }

    .qr-column {
        display: table-cell;
        width: 220px;
        text-align: center;
        padding: 15px;
        vertical-align: middle;
        border-right: 1px solid #e2e8f0;
        padding-right: 20px;
    }

    .qr-label {
        font-size: 9pt;
        font-weight: bold;
        color: #0f172a;
        margin-bottom: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .qr-code-container {
        background: #ffffff;
        padding: 10px;
        border: 2px solid #e2e8f0;
        border-radius: 6px;
        display: inline-block;
    }

    .qr-code-img {
        width: 160px;
        height: 160px;
        display: block;
    }

    .qr-url {
        font-size: 7pt;
        color: #475569;
        margin-top: 8px;
        word-break: break-all;
        line-height: 1.2;
    }

    .info-column {
        display: table-cell;
        flex-grow: 1;
        padding: 15px 20px;
        vertical-align: middle;
    }

    .info-section {
        margin-bottom: 15px;
    }

    .info-title {
        font-size: 10pt;
        font-weight: bold;
        color: #0f172a;
        margin-bottom: 8px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    .info-row {
        font-size: 9pt;
        color: #475569;
        line-height: 1.8;
    }

    .info-row strong {
        color: #0f172a;
    }

    .divider {
        height: 1px;
        background: #e2e8f0;
        margin: 12px 0;
    }

    .instructor-info {
        background: #f0f9ff;
        padding: 12px;
        border-radius: 6px;
        border-left: 3px solid #0f172a;
    }

    .instructor-info .info-title {
        margin-bottom: 6px;
    }

    .instructor-info .info-row {
        font-size: 8.5pt;
        line-height: 1.6;
    }

    .certificate-footer {
        text-align: center;
        padding-top: 20px;
        border-top: 2px solid #0f172a;
        margin-top: 20px;
    }

    .footer-code-label {
        font-size: 9pt;
        font-weight: bold;
        color: #0f172a;
        text-transform: uppercase;
        margin-bottom: 6px;
        letter-spacing: 0.5px;
    }

    .footer-code-value {
        font-size: 14pt;
        font-weight: bold;
        font-family: 'Courier New', monospace;
        color: #0f172a;
        letter-spacing: 2px;
        margin-bottom: 6px;
    }

    .footer-note {
        font-size: 8pt;
        color: #475569;
    }

    .validity-badge {
        display: inline-block;
        background: #10b981;
        color: #ffffff;
        padding: 4px 12px;
        border-radius: 20px;
        font-size: 9pt;
        font-weight: bold;
        margin-top: 8px;
    }
</style>
</head>
<body>

<div class="certificate">
    <div class="certificate-header">
        <div class="certificate-title">Certificado</div>
        <div class="certificate-subtitle">de Conclusão</div>
    </div>

    <div class="certificate-body">
        <div class="body-top">
            <div class="recipient-intro">Certificamos que</div>
            <div class="recipient-name">{{ $certificate->user->nome }}</div>
            <div class="recipient-cpf">
                <strong>CPF:</strong> {{ $certificate->user->getCpfFormatted() }}
            </div>

            <div class="completion-text">
                Completou com sucesso e obteve aprovação no treinamento:
            </div>

            <div class="course-info">
                <div class="course-title">{{ $certificate->training->titulo }}</div>
                <div class="course-desc">{{ $certificate->training->descricao }}</div>
            </div>

            <div class="completion-grid">
                <div class="grid-cell" style="width: 25%;">
                    <div class="grid-label">Carga Horária</div>
                    <div class="grid-value">{{ $certificate->training->carga_horaria }} min</div>
                </div>
                <div class="grid-cell" style="width: 25%;">
                    <div class="grid-label">Tempo Assistido</div>
                    <div class="grid-value">{{ $tempoAssistidoFormatado }}</div>
                </div>
                <div class="grid-cell" style="width: 25%;">
                    <div class="grid-label">Início</div>
                    <div class="grid-value">{{ optional($certificate->data_inicio_assistencia)->format('d/m/Y') }}</div>
                </div>
                <div class="grid-cell" style="width: 25%;">
                    <div class="grid-label">Conclusão</div>
                    <div class="grid-value">{{ optional($certificate->data_finalizacao_assistencia)->format('d/m/Y') }}</div>
                </div>
            </div>
        </div>

        <div class="body-bottom">
            <div class="qr-column">
                <div class="qr-label">Código QR de Validação</div>
                <div class="qr-code-container">
                    <img src="{{ $qrDataUri }}" alt="QR Code" class="qr-code-img" />
                </div>
                <div class="qr-url">{{ $validationUrl }}</div>
            </div>

            <div class="info-column">
                <div class="info-section">
                    <div class="info-title">Informações de Validação</div>
                    <div class="info-row">
                        <strong>Código:</strong> {{ $certificate->codigo_certificado }}
                    </div>
                    <div class="info-row">
                        <strong>Emitido em:</strong> {{ $certificate->data_emissao->format('d/m/Y H:i') }}
                    </div>
                    <div class="validity-badge">✓ VÁLIDO</div>
                </div>

                <div class="divider"></div>

                <div class="instructor-info">
                    <div class="info-title">Instrutor Responsável</div>
                    <div class="info-row">Ornilio Machado Neto</div>
                    <div class="info-row">Técnico em Segurança do Trabalho</div>
                    <div class="info-row">RG: 10827 | Bombeiro Civil</div>
                </div>
            </div>
        </div>

        <div class="certificate-footer">
            <div class="footer-code-label">Código de Validação</div>
            <div class="footer-code-value">{{ $certificate->codigo_certificado }}</div>
            <div class="footer-note">Este certificado é válido e pode ser verificado através do QR Code acima</div>
        </div>
    </div>
</div>

</body>
</html>
