<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    private function serializeUser(User $user): array
    {
        return [
            'id' => $user->id,
            'nome' => $user->nome,
            'cpf' => $user->cpf,
            'cpf_formatado' => $user->getCpfFormatted(),
            'email' => $user->email,
            'telefone' => $user->telefone,
            'data_nascimento' => $user->data_nascimento?->format('Y-m-d'),
            'tipo_usuario' => $user->tipo_usuario,
            'status' => $user->status,
            'role' => $user->role?->nome ?? 'usuario',
            'participa_treinamentos' => (bool) $user->participa_treinamentos,
            'usuario_teste' => (bool) $user->usuario_teste,
            'ferias_inicio' => $user->ferias_inicio?->format('Y-m-d'),
            'ferias_fim' => $user->ferias_fim?->format('Y-m-d'),
            'setor' => $user->setor,
            'cargo' => $user->cargo,
            'empresa' => $user->empresa,
            'camisa_tamanho' => $user->camisa_tamanho,
            'calca_tamanho' => $user->calca_tamanho,
            'bota_numero' => $user->bota_numero,
            'foto_perfil' => $user->foto_perfil,
            'avatar_url' => $user->getFotoPerfilUrl(),
            'ficha_url' => $user->qrcode_token
                ? url('/ficha/' . $user->qrcode_token)
                : null,
            'is_admin' => $user->isAdmin(),
            'is_super_admin' => $user->isSuperAdmin(),
            'permissions' => $this->getPermissions($user),
            'em_ferias' => $user->isOnVacation(),
        ];
    }

    private function getPermissions(User $user): array
    {
        $modules = ['users', 'trainings', 'certificates', 'rankings', 'splash', 'social', 'epi', 'permissions'];
        $permissions = [];

        if ($user->isSuperAdmin()) {
            foreach ($modules as $module) {
                $permissions[$module] = ['view' => true, 'edit' => true];
            }

            return $permissions;
        }

        foreach ($modules as $module) {
            $permissions[$module] = [
                'view' => $user->hasPermission($module, 'view'),
                'edit' => $user->hasPermission($module, 'edit'),
            ];
        }

        return $permissions;
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
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $cpf = preg_replace('/\D/', '', $request->cpf);

        try {
            $user = User::where('cpf', $cpf)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'CPF ou senha inválidos',
                ], 401);
            }

            if ($user->status !== 'ativo') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Usuário inativo. Contate o administrador.',
                ], 403);
            }

            $user->tokens()->delete();

            $token = $user->createToken('app-dss', ['*'])->plainTextToken;

            return response()->json([
                'status' => 'success',
                'message' => 'Login realizado com sucesso!',
                'token' => $token,
                'user' => $this->serializeUser($user),
            ]);
        } catch (\Throwable $e) {
            report($e);

            return response()->json([
                'status' => 'error',
                'message' => 'Não foi possível processar o login agora. Verifique o log do servidor.',
            ], 500);
        }
    }

    public function me(Request $request)
    {
        $user = $request->user();

        return response()->json([
            'status' => 'success',
            'user' => $this->serializeUser($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso!',
        ]);
    }

    public function logoutAllDevices(Request $request)
    {
        $request->user()->tokens()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Sessão encerrada em todos os dispositivos.',
        ]);
    }
}
