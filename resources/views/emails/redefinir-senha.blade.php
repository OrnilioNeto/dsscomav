@php
    $corPrimaria = tenant()?->getCorPrimaria() ?? '#153B2E';
@endphp
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinição de senha</title>
</head>
<body style="margin:0; padding:0; background-color:#f3f4f6; font-family:Arial, Helvetica, sans-serif; color:#1f2937;">
    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background-color:#f3f4f6; padding:24px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="600" cellpadding="0" cellspacing="0" style="max-width:600px; width:100%; background-color:#ffffff; border-radius:8px; overflow:hidden; box-shadow:0 1px 3px rgba(0,0,0,0.08);">
                    <tr>
                        <td style="background-color:{{ $corPrimaria }}; padding:24px; text-align:center;">
                            <span style="color:#ffffff; font-size:20px; font-weight:bold;">{{ $plataformaNome }}</span>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:32px 32px 8px 32px;">
                            <h1 style="margin:0 0 16px 0; font-size:22px; color:#111827;">Redefinição de senha</h1>
                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6;">
                                Olá{{ $nome ? ', '.$nome : '' }},
                            </p>
                            <p style="margin:0 0 16px 0; font-size:15px; line-height:1.6;">
                                Recebemos uma solicitação para redefinir a senha da sua conta em
                                <strong>{{ $plataformaNome }}</strong>. Para criar uma nova senha,
                                clique no botão abaixo:
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:8px 32px 24px 32px;">
                            <a href="{{ $url }}" target="_blank" style="display:inline-block; background-color:{{ $corPrimaria }}; color:#ffffff; text-decoration:none; font-size:15px; font-weight:bold; padding:14px 32px; border-radius:6px;">
                                Criar nova senha
                            </a>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 24px 32px;">
                            <p style="margin:0 0 8px 0; font-size:13px; line-height:1.6; color:#6b7280;">
                                Este link é válido por {{ $expiracaoMinutos }} minutos. Se o botão não
                                funcionar, copie e cole o endereço abaixo no seu navegador:
                            </p>
                            <p style="margin:0; font-size:12px; line-height:1.6; word-break:break-all;">
                                <a href="{{ $url }}" target="_blank" style="color:{{ $corPrimaria }};">{{ $url }}</a>
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="padding:0 32px 32px 32px; border-top:1px solid #e5e7eb;">
                            <p style="margin:16px 0 0 0; font-size:13px; line-height:1.6; color:#6b7280;">
                                Se você não solicitou a redefinição, ignore este e-mail: sua senha
                                atual continua válida e ninguém terá acesso à sua conta.
                            </p>
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#f9fafb; padding:16px 32px; text-align:center;">
                            <span style="font-size:12px; color:#9ca3af;">
                                E-mail automático — não responda esta mensagem.
                            </span>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
