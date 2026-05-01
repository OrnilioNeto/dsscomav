<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<style>
    * {
        margin: 0;
        padding: 0;
        box-sizing: border-box;
    }

    @page {
        size: A4 landscape;
        margin: 0;
    }

    html, body {
        width: 100%;
        height: 100%;
        background: #ffffff;
        margin: 0;
        padding: 0;
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    }

    body {
        color: #333;
        font-size: 9pt;
    }

    .certificate-container {
        width: 100%;
        background: #ffffff;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
        gap: 12px;
    }

    .certificate-header {
        text-align: left;
        margin-bottom: 6px;
        padding-bottom: 6px;
        border-bottom: 1px solid #e6e6e6;
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        gap: 12px;
    }

    .header-label {
        font-size: 9pt;
        color: #666;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .header-title {
        font-size: 20pt;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 0;
    }

    .code-date {
        font-size: 8pt;
        color: #666;
        display: flex;
        flex-direction: column;
        text-align: right;
        gap: 4px;
    }

    .certificate-content {
        display: flex;
        gap: 18px;
        align-items: flex-start;
    }

    .left-column {
        flex: 1.1;
    }

    .right-column {
        flex: 0.6;
        display: flex;
        flex-direction: column;
        justify-content: flex-start;
        align-items: center;
        padding-left: 12px;
    }

    .section {
        margin-bottom: 18px;
    }

    .section-title {
        font-size: 8pt;
        font-weight: 700;
        color: #1a1a1a;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 8px;
    }

    .field-row {
        display: flex;
        margin-bottom: 6px;
        font-size: 9pt;
    }

    .field-label {
        font-weight: bold;
        color: #1a1a1a;
        width: 100px;
        flex-shrink: 0;
    }

    .field-value {
        color: #333;
        flex: 1;
    }

    .beneficiary-name {
        font-size: 12pt;
        font-weight: 700;
        color: #1a1a1a;
        margin-bottom: 6px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 6px;
        margin-bottom: 10px;
    }

    .small-cards {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 8px;
        margin-top: 8px;
    }

    .info-box {
        padding: 8px 10px;
        border: 1px solid #f0f0f0;
        border-radius: 8px;
        background: #fcfcfc;
        text-align: center;
        font-size: 9pt;
    }

    .info-box .label {
        display: block;
        font-size: 7.5pt;
        color: #666;
        font-weight: 700;
        margin-bottom: 4px;
        text-transform: uppercase;
    }

    .info-box .value {
        font-weight: 700;
        color: #1a1a1a;
    }

    .info-item {
        font-size: 9pt;
    }

    .info-label {
        font-size: 7.5pt;
        font-weight: bold;
        color: #666;
        text-transform: uppercase;
        margin-bottom: 3px;
    }

    .info-value {
        font-size: 10pt;
        color: #1a1a1a;
        font-weight: 500;
    }

    .training-title {
        font-size: 11pt;
        font-weight: bold;
        color: #1a1a1a;
        margin-bottom: 6px;
    }

    .training-desc {
        font-size: 8.5pt;
        color: #555;
        line-height: 1.3;
    }

    .qr-container {
        background: transparent;
        padding: 6px;
        border-radius: 6px;
        margin-bottom: 10px;
    }

    .qr-label {
        font-size: 8pt;
        font-weight: bold;
        color: #666;
        text-transform: uppercase;
        margin-bottom: 8px;
        text-align: center;
    }

    .qr-code {
        width: 120px;
        height: 120px;
        display: block;
        margin: 0 auto;
        border-radius: 8px;
    }

    .qr-url {
        font-size: 7pt;
        color: #666;
        margin-top: 8px;
        text-align: center;
        word-break: break-all;
        line-height: 1.2;
    }

    .validation-info {
        font-size: 9pt;
        text-align: left;
    }

    .validation-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 6px;
        font-size: 8.5pt;
    }

    .validation-label {
        font-weight: bold;
        color: #1a1a1a;
    }

    .validation-value {
        color: #333;
        text-align: right;
        max-width: 100px;
        word-break: break-all;
    }

    .validity-badge {
        display: inline-block;
        background: #10b981;
        color: #fff;
        padding: 3px 8px;
        border-radius: 3px;
        font-size: 7.5pt;
        font-weight: bold;
        text-align: center;
        margin-top: 6px;
        width: 100%;
    }
</style>
</head>
<body>

<div class="certificate-container">
    <!-- Header with title and code -->
    <div class="certificate-header">
        <div class="header-label">Certificado de Conclusão</div>
        <div class="header-title">Treinamento - {{ $certificate->training->titulo }}</div>
        <div class="code-date">
            <span>Código: {{ $certificate->codigo_certificado }}</span>
            <span>Emitido em: {{ $certificate->data_emissao->format('d/m/Y H:i') }}</span>
        </div>
    </div>

    <!-- Main content -->
    <div class="certificate-content">
        <!-- Left column: Personal information -->
        <div class="left-column">
            <!-- Beneficiary section -->
            <div class="section">
                <div class="section-title">Beneficiário</div>
                <div class="beneficiary-name">{{ $certificate->user->nome }}</div>
                
                <div class="field-row">
                    <div class="field-label">CPF</div>
                    <div class="field-value">{{ $certificate->user->getCpfFormatted() }}</div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">E-MAIL</div>
                        <div class="info-value">{{ $certificate->user->email }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">TELEFONE</div>
                        <div class="info-value">{{ $certificate->user->telefone ?? 'Não informado' }}</div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">EMPRESA</div>
                        <div class="info-value">{{ $certificate->user->empresa ?? 'Não informada' }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">TIPO DE USUÁRIO</div>
                        <div class="info-value">{{ ucfirst($certificate->user->tipo_usuario ?? 'Não informado') }}</div>
                    </div>
                </div>
            </div>

            <!-- Instructor section -->
            <div class="section">
                <div class="section-title">Instrutor</div>
                <div class="field-row">
                    <div class="field-label">Nome</div>
                    <div class="field-value">Ornilio Machado Neto</div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">QUALIFICAÇÃO</div>
                        <div class="info-value">Tec Segurança do Trabalho</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">RG</div>
                        <div class="info-value">10827</div>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">REGISTRO</div>
                    <div class="field-value">Bombeiro Civil</div>
                </div>
            </div>

            <!-- Completed content section -->
            <div class="section">
                <div class="section-title">Conteúdo Concluído</div>
                <div class="training-title">{{ $certificate->training->titulo }}</div>
                <div class="training-desc">{{ $certificate->training->descricao }}</div>

                <div class="small-cards">
                    <div class="info-box">
                        <span class="label">Carga Horária</span>
                        <span class="value">{{ $certificate->training->carga_horaria }} min</span>
                    </div>
                    <div class="info-box">
                        <span class="label">Tempo Assistido</span>
                        <span class="value">{{ gmdate('H:i:s', (int)$certificate->tempo_assistido_segundos) }}</span>
                    </div>
                    <div class="info-box">
                        <span class="label">Início</span>
                        <span class="value">{{ $certificate->data_inicio_assistencia->format('d/m/Y H:i') }}</span>
                    </div>
                    <div class="info-box">
                        <span class="label">Finalização</span>
                        <span class="value">{{ $certificate->data_finalizacao_assistencia->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Right column: QR Code and validation -->
        <div class="right-column">
            <!-- QR Code -->
            <div class="qr-container">
                <div class="qr-label">Validação</div>
                <img src="{{ $qrDataUri }}" alt="QR Code" class="qr-code" />
                <div class="qr-url">{{ $validationUrl }}</div>
            </div>

            <!-- Validation info -->
            <div class="validation-info">
                <div class="validation-row">
                    <span class="validation-label">Código</span>
                    <span class="validation-value">{{ $certificate->codigo_certificado }}</span>
                </div>
                <div class="validation-row">
                    <span class="validation-label">Emissão</span>
                    <span class="validation-value">{{ $certificate->data_emissao->format('d/m/Y H:i') }}</span>
                </div>
                <div class="validity-badge">✓ Válido</div>
            </div>
        </div>
    </div>
</div>

</body>
</html>
