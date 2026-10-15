<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * E-mail de redefinição de senha (pt-BR) com link para a plataforma.
 */
class RedefinirSenhaNotification extends Notification
{
    private string $token;

    public function __construct(string $token)
    {
        $this->token = $token;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $url = route('password.reset', [
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);

        $plataformaNome = plataforma_nome();

        $message = (new MailMessage)
            ->subject('Redefinição de senha - '.$plataformaNome)
            ->view('emails.redefinir-senha', [
                'nome' => $notifiable->nome ?? '',
                'url' => $url,
                'expiracaoMinutos' => (int) config('auth.passwords.users.expire', 60),
                'plataformaNome' => $plataformaNome,
            ]);

        // White-label: usa o remetente configurado no tenant, quando houver.
        if ($remetente = tenant()?->email_remetente) {
            $message->from($remetente, $plataformaNome);
        }

        return $message;
    }
}
