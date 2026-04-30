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
        font-size: 10pt;
    }

    .certificate-container {
        width: 100%;
        height: 100vh;
        background: #ffffff;
        padding: 30px 40px;
        display: flex;
        flex-direction: column;
    }

    .certificate-header {
        text-align: center;
        margin-bottom: 15px;
        padding-bottom: 10px;
        border-bottom: 2px solid #999;
    }

    .header-label {
        font-size: 9pt;
        color: #666;
        letter-spacing: 2px;
        text-transform: uppercase;
        margin-bottom: 5px;
    }

    .header-title {
        font-size: 28pt;
        font-weight: bold;
        color: #1a1a1a;
        margin-bottom: 2px;
    }

    .code-date {
        font-size: 8.5pt;
        color: #666;
        margin-top: 8px;
        display: flex;
        justify-content: space-between;
        max-width: 300px;
        margin-left: auto;
        margin-right: auto;
        gap: 40px;
    }

    .certificate-content {
        display: flex;
        gap: 30px;
        flex: 1;
    }

    .left-column {
        flex: 1.2;
    }

    .right-column {
        flex: 0.8;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        border-left: 1px solid #ddd;
        padding-left: 20px;
    }

    .section {
        margin-bottom: 18px;
    }

    .section-title {
        font-size: 8.5pt;
        font-weight: bold;
        color: #1a1a1a;
        text-transform: uppercase;
        letter-spacing: 1px;
        margin-bottom: 10px;
        padding-bottom: 5px;
        border-bottom: 1px solid #ddd;
    }

    .field-row {
        display: flex;
        margin-bottom: 7px;
        font-size: 9.5pt;
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
        font-size: 14pt;
        font-weight: bold;
        color: #1a1a1a;
        margin-bottom: 8px;
    }

    .info-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 8px;
        margin-bottom: 12px;
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
        background: #f5f5f5;
        padding: 12px;
        border-radius: 4px;
        margin-bottom: 12px;
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
        width: 140px;
        height: 140px;
        display: block;
        margin: 0 auto;
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
                        <div class="info-label">CARGO</div>
                        <div class="info-value">{{ $certificate->user->cargo ?? 'Não informado' }}</div>
                    </div>
                </div>

                <div class="field-row">
                    <div class="field-label">TIPO DE USUÁRIO</div>
                    <div class="field-value">{{ $certificate->user->tipo_usuario }}</div>
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

                <div class="info-grid" style="margin-top: 10px;">
                    <div class="info-item">
                        <div class="info-label">CARGA HORÁRIA</div>
                        <div class="info-value">{{ $certificate->training->carga_horaria }} min</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">TEMPO ASSISTIDO</div>
                        <div class="info-value">{{ gmdate('H:i:s', (int)$certificate->tempo_assistido_segundos) }}</div>
                    </div>
                </div>

                <div class="info-grid">
                    <div class="info-item">
                        <div class="info-label">INÍCIO</div>
                        <div class="info-value">{{ $certificate->data_inicio_assistencia->format('d/m/Y H:i') }}</div>
                    </div>
                    <div class="info-item">
                        <div class="info-label">FINALIZAÇÃO</div>
                        <div class="info-value">{{ $certificate->data_finalizacao_assistencia->format('d/m/Y H:i') }}</div>
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
