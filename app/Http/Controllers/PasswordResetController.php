<?php

namespace App\Http\Controllers;

use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PasswordResetController extends Controller
{
    /**
     * Formulário "Esqueci minha senha".
     */
    public function showLinkRequestForm()
    {
        return view('auth.forgot-password');
    }

    /**
     * Envia o link de redefinição para o e-mail informado.
     *
     * A resposta é sempre a mesma, exista ou não o e-mail, para evitar
     * enumeração de contas.
     */
    public function sendResetLinkEmail(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
        ], [
            'email.required' => 'E-mail é obrigatório',
            'email.email' => 'Informe um e-mail válido',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $email = $this->normalizarEmail((string) $request->email);
        $user = $this->findUserByEmail($email);

        if ($user) {
            $broker = Password::broker();

            try {
                // Evita reenvio em sequência (mesma janela do broker).
                if (! $broker->getRepository()->recentlyCreatedToken($user)) {
                    $token = $broker->createToken($user);
                    $user->sendPasswordResetNotification($token);

                    app(AuditLogger::class)->log('password_reset_requested', [
                        'user_id' => $user->id,
                        'module' => 'auth',
                        'description' => 'Solicitou redefinição de senha por e-mail',
                        'new_values' => ['email' => mask_email($email)],
                    ]);
                }
            } catch (\Throwable $e) {
                // Falha de envio não deve revelar se o e-mail existe.
                try {
                    $broker->deleteToken($user);
                } catch (\Throwable $cleanupError) {
                    report($cleanupError);
                }

                report($e);
            }
        }

        return redirect()->route('password.request')->with(
            'success',
            'Se o e-mail informado estiver cadastrado, enviaremos as instruções de redefinição de senha. Verifique também a caixa de spam.'
        );
    }

    /**
     * Formulário de definição da nova senha (link recebido por e-mail).
     */
    public function showResetForm(Request $request, string $token)
    {
        return view('auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    /**
     * Redefine a senha a partir do token do e-mail.
     */
    public function reset(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'token' => 'required|string',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
        ], [
            'token.required' => 'Link de redefinição inválido',
            'email.required' => 'E-mail é obrigatório',
            'email.email' => 'Informe um e-mail válido',
            'password.required' => 'Senha é obrigatória',
            'password.min' => 'A senha deve ter pelo menos 8 caracteres',
            'password.confirmed' => 'A confirmação de senha não confere',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)
                ->withInput($request->except(['password', 'password_confirmation']));
        }

        $email = $this->normalizarEmail((string) $request->email);
        $user = $this->findUserByEmail($email);
        $broker = Password::broker();

        if (! $user || ! $broker->tokenExists($user, (string) $request->token)) {
            app(AuditLogger::class)->log('password_reset_failed', [
                'user_id' => $user?->id,
                'module' => 'auth',
                'description' => 'Tentativa de redefinição de senha com link inválido ou expirado',
                'new_values' => ['email' => mask_email($email)],
            ]);

            return back()->with('error', 'Link de redefinição inválido ou expirado. Solicite um novo link.');
        }

        $user->forceFill([
            'password' => Hash::make($request->password),
            'remember_token' => Str::random(60),
        ])->save();

        $broker->deleteToken($user);

        event(new PasswordReset($user));

        app(AuditLogger::class)->log('password_reset_completed', [
            'user_id' => $user->id,
            'module' => 'auth',
            'description' => 'Redefiniu a senha via link de e-mail',
        ]);

        return redirect()->route('login')->with('success', 'Senha redefinida com sucesso! Faça login com a nova senha.');
    }

    /**
     * Busca o usuário pelo e-mail com o mesmo critério do login: respeita o
     * tenant atual, mas permite que o super_admin (sem tenant) redefina em
     * qualquer host.
     */
    private function findUserByEmail(string $email): ?User
    {
        $user = User::whereRaw('LOWER(email) = ?', [$email])->first();

        if (! $user) {
            $candidato = User::withoutGlobalScope(TenantScope::class)
                ->whereRaw('LOWER(email) = ?', [$email])
                ->first();

            if ($candidato && $candidato->isSuperAdmin()) {
                $user = $candidato;
            }
        }

        return $user;
    }

    private function normalizarEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }
}
