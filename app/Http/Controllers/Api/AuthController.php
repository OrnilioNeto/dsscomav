<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Scopes\TenantScope;
use App\Models\User;
use App\Services\AuditLogger;
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
                ? url('/ficha/'.$user->qrcode_token)
                : null,
            'is_admin' => $user->isAdmin(),
            'is_super_admin' => $user->isSuperAdmin(),
            'permissions' => $this->getPermissions($user),
            'em_ferias' => $user->isOnVacation(),
        ];
    }

    private function getPermissions(User $user): array
    {
        $modules = ['users', 'trainings', 'certificates', 'rankings', 'splash', 'social', 'epi', 'projeto_pedagogico', 'permissions'];
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
                    'description' => 'CPF ou senha inválidos (API)',
                    'new_values' => ['cpf' => mask_cpf($cpf)],
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'CPF ou senha inválidos',
                ], 401);
            }

            if ($user->status !== 'ativo') {
                app(AuditLogger::class)->log('login_blocked', [
                    'user_id' => $user->id,
                    'module' => 'auth',
                    'description' => 'Login bloqueado na API: usuário inativo',
                    'new_values' => ['cpf' => mask_cpf($cpf)],
                ]);

                return response()->json([
                    'status' => 'error',
                    'message' => 'Usuário inativo. Contate o administrador.',
                ], 403);
            }

            $user->tokens()->delete();

            $newToken = $user->createToken('app-dss', ['*']);
            $token = $newToken->plainTextToken;

            // Grava o tenant no token para auditoria/isolamento da API
            if ($user->tenant_id) {
                $newToken->accessToken->forceFill(['tenant_id' => $user->tenant_id])->save();
            }

            app(AuditLogger::class)->log('login', [
                'user_id' => $user->id,
                'module' => 'auth',
                'description' => 'Login realizado via API: '.$user->nome,
            ]);

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
        $user = $request->user();
        $user->currentAccessToken()->delete();

        app(AuditLogger::class)->log('logout', [
            'user_id' => $user->id,
            'module' => 'auth',
            'description' => 'Logout realizado via API: '.$user->nome,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso!',
        ]);
    }

    public function logoutAllDevices(Request $request)
    {
        $user = $request->user();
        $user->tokens()->delete();

        app(AuditLogger::class)->log('logout', [
            'user_id' => $user->id,
            'module' => 'auth',
            'description' => 'Logout em todos os dispositivos via API: '.$user->nome,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Sessão encerrada em todos os dispositivos.',
        ]);
    }
}
