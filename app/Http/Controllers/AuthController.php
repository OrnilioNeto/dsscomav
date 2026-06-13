<?php

namespace App\Http\Controllers;

use App\Models\User;
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
            return redirect('login')->withErrors($validator)->withInput();
        }

        // Remove máscara do CPF
        $cpf = preg_replace('/\D/', '', $request->cpf);

        try {
            if (Auth::attempt(['cpf' => $cpf, 'password' => $request->password])) {
                $request->session()->regenerate();
                // Flag para exibir o splash da Copa do Mundo no primeiro acesso da sessão
                $request->session()->put('show_copa_splash', true);
                return redirect()->route('dashboard');
            }
        } catch (\Throwable $e) {
            report($e);

            return back()
                ->withInput()
                ->with('error', 'Não foi possível processar o login agora. Verifique o log do servidor.');
        }

        return back()->withInput()->with('error', 'CPF ou senha inválidos');
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect('/');
    }
}
