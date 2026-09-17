<?php

namespace App\Http\Controllers;

use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'cpf' => 'required|string',
            'password' => 'required|string',
        ], [
            'cpf.required' => 'CPF é obrigatório',
            'password.required' => 'Senha é obrigatória',
        ]);

        if ($validator->fails()) {
            return redirect('login')->withErrors($validator)->withInput($request->except('password'));
        }

        // Remove máscara do CPF
        $cpf = preg_replace('/\D/', '', $request->cpf);

        try {
            $user = User::where('cpf', $cpf)->first();

            // Usuário da plataforma (super_admin) pode logar em qualquer host,
            // mesmo quando há um tenant resolvido (ele não pertence a nenhum tenant).
            if (! $user) {
                $candidato = User::withoutGlobalScope(TenantScope::class)
                    ->where('cpf', $cpf)
                    ->first();

                if ($candidato && $candidato->isSuperAdmin()) {
                    $user = $candidato;
                }
            }

            if (! $user || ! Hash::check($request->password, $user->password)) {
                app(AuditLogger::class)->log('login_failed', [
                    'user_id' => $user?->id,
                    'module' => 'auth',
                    'description' => 'CPF ou senha inválidos',
                    'new_values' => ['cpf' => mask_cpf($cpf)],
                ]);

                return back()->withInput($request->except('password'))->with('error', 'CPF ou senha inválidos');
            }

            // Bloqueia usuários inativos (status = inativo)
            if ($user->status !== 'ativo') {
                app(AuditLogger::class)->log('login_blocked', [
                    'user_id' => $user->id,
                    'module' => 'auth',
                    'description' => 'Login bloqueado: usuário inativo',
                    'new_values' => ['cpf' => mask_cpf($cpf)],
                ]);

                return back()->withInput($request->except('password'))->with('error', 'Acesso bloqueado: usuário inativo. Contate o administrador.');
            }

            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput($request->except('password'))
                ->with('error', 'Não foi possível processar o login agora. Verifique o log do servidor.');
        }
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
