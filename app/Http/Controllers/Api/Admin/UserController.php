<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\UserVacation;
use App\Models\EmployeeTraining;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private const TIPOS_USUARIO_VALIDOS = ['motorista', 'funcionario', 'terceirizado'];

    public function index(Request $request)
    {
        $nome = trim((string) $request->input('nome', ''));
        $tipo = $request->input('tipo');
        $perPage = (int) $request->input('per_page', 20);

        $query = User::with('role');

        if ($nome !== '') {
            $query->where('nome', 'like', '%' . $nome . '%');
        }

        if ($tipo && in_array($tipo, self::TIPOS_USUARIO_VALIDOS, true)) {
            $query->where('tipo_usuario', $tipo);
        }

        $users = $query->orderBy('nome')->paginate($perPage);

        return response()->json([
            'status' => 'success',
            'data' => $users->through(fn ($u) => $this->serialize($u))->items(),
            'meta' => [
                'total' => $users->total(),
                'per_page' => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page' => $users->lastPage(),
            ],
        ]);
    }

    private function serialize(User $user): array
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
            'role_id' => $user->role_id,
            'role' => $user->role?->nome,
            'participa_treinamentos' => (bool) $user->participa_treinamentos,
            'usuario_teste' => (bool) $user->usuario_teste,
            'ferias_inicio' => $user->ferias_inicio?->format('Y-m-d'),
            'ferias_fim' => $user->ferias_fim?->format('Y-m-d'),
            'setor' => $user->setor,
            'cargo' => $user->cargo,
            'empresa' => $user->empresa,
            'cnh' => $user->cnh,
            'categoria_cnh' => $user->categoria_cnh,
            'validade_cnh' => $user->validade_cnh?->format('Y-m-d'),
            'camisa_tamanho' => $user->camisa_tamanho,
            'calca_tamanho' => $user->calca_tamanho,
            'bota_numero' => $user->bota_numero,
            'avatar_url' => $user->getFotoPerfilUrl(),
            'em_ferias' => $user->isOnVacation(),
        ];
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'cpf' => 'required|string|unique:users|regex:/^\d{11}$/',
            'email' => 'required|email|unique:users',
            'password' => 'required|string|min:8',
            'telefone' => 'nullable|string',
            'tipo_usuario' => 'required|in:motorista,funcionario,terceirizado',
            'empresa' => 'nullable|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'setor' => 'nullable|string|max:255',
            'camisa_tamanho' => 'nullable|string|max:20',
            'calca_tamanho' => 'nullable|string|max:20',
            'bota_numero' => 'nullable|string|max:20',
            'cnh' => 'nullable|string|max:20',
            'categoria_cnh' => 'nullable|string|max:10',
            'validade_cnh' => 'nullable|date',
            'ferias_inicio' => 'nullable|date|required_with:ferias_fim',
            'ferias_fim' => 'nullable|date|required_with:ferias_inicio|after_or_equal:ferias_inicio',
            'usuario_teste' => 'nullable|boolean',
            'participa_treinamentos' => 'nullable|boolean',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();
        $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);
        $data['password'] = Hash::make($data['password']);
        $data['ferias_inicio'] = $request->filled('ferias_inicio') ? $request->input('ferias_inicio') : null;
        $data['ferias_fim'] = $request->filled('ferias_fim') ? $request->input('ferias_fim') : null;
        $data['usuario_teste'] = $request->boolean('usuario_teste');
        $data['participa_treinamentos'] = $request->boolean('participa_treinamentos');

        $user = User::create($data);

        if ($request->filled('ferias_inicio') && $request->filled('ferias_fim')) {
            UserVacation::create([
                'user_id' => $user->id,
                'data_inicio' => $request->input('ferias_inicio'),
                'data_fim' => $request->input('ferias_fim'),
            ]);
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário criado com sucesso!',
            'data' => $this->serialize($user),
        ], 201);
    }

    public function show($id)
    {
        $user = User::with('role', 'certificates', 'progress')->findOrFail($id);

        return response()->json([
            'status' => 'success',
            'data' => $this->serialize($user),
        ]);
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $rules = [
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'telefone' => 'nullable|string',
            'tipo_usuario' => 'required|in:motorista,funcionario,terceirizado',
            'empresa' => 'nullable|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'setor' => 'nullable|string|max:255',
            'camisa_tamanho' => 'nullable|string|max:20',
            'calca_tamanho' => 'nullable|string|max:20',
            'bota_numero' => 'nullable|string|max:20',
            'cnh' => 'nullable|string|max:20',
            'categoria_cnh' => 'nullable|string|max:10',
            'validade_cnh' => 'nullable|date',
            'status' => 'required|in:ativo,inativo',
            'ferias_inicio' => 'nullable|date|required_with:ferias_fim',
            'ferias_fim' => 'nullable|date|required_with:ferias_inicio|after_or_equal:ferias_inicio',
            'usuario_teste' => 'nullable|boolean',
        ];

        if (!$usuario->isSuperAdmin()) {
            $rules['role_id'] = 'required|exists:roles,id';
        }

        if (request()->user()->isSuperAdmin()) {
            $rules['password'] = 'nullable|string|min:8';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
                'errors' => $validator->errors(),
            ], 422);
        }

        $data = $request->all();
        $data['ferias_inicio'] = $request->filled('ferias_inicio') ? $request->input('ferias_inicio') : null;
        $data['ferias_fim'] = $request->filled('ferias_fim') ? $request->input('ferias_fim') : null;
        $data['usuario_teste'] = $request->boolean('usuario_teste');

        if ($usuario->isSuperAdmin()) {
            unset($data['role_id']);
        }

        if (request()->user()->isSuperAdmin() && $request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        } else {
            unset($data['password']);
        }

        if ($usuario->isAdmin()) {
            $data['participa_treinamentos'] = $request->boolean('participa_treinamentos');
        }

        $usuario->update($data);

        if ($request->filled('ferias_inicio') && $request->filled('ferias_fim')) {
            $novaInicio = $request->input('ferias_inicio');
            $novaFim = $request->input('ferias_fim');
            $ultimoHistorico = $usuario->vacations()->latest()->first();
            if (!$ultimoHistorico || $ultimoHistorico->data_inicio->format('Y-m-d') !== $novaInicio || $ultimoHistorico->data_fim->format('Y-m-d') !== $novaFim) {
                UserVacation::create([
                    'user_id' => $usuario->id,
                    'data_inicio' => $novaInicio,
                    'data_fim' => $novaFim,
                ]);
            }
        }

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário atualizado!',
            'data' => $this->serialize($usuario),
        ]);
    }

    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário deletado!',
        ]);
    }

    public function roles(Request $request)
    {
        $roles = Role::where('nome', '!=', 'super_admin')
            ->orderBy('nome')
            ->get(['id', 'nome', 'descricao']);

        return response()->json([
            'status' => 'success',
            'data' => $roles,
        ]);
    }

    public function storeExternalTraining(Request $request, $userId)
    {
        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'data_treinamento' => 'required|date',
            'data_validade' => 'nullable|date',
            'observacoes' => 'nullable|string|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'status' => 'error',
                'message' => $validator->errors()->first(),
            ], 422);
        }

        $training = EmployeeTraining::create([
            'user_id' => $userId,
            'nome' => $request->input('nome'),
            'data_treinamento' => $request->input('data_treinamento'),
            'data_validade' => $request->input('data_validade'),
            'observacoes' => $request->input('observacoes'),
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Treinamento externo registrado!',
            'data' => $training,
        ], 201);
    }

    public function destroyExternalTraining($id)
    {
        $training = EmployeeTraining::findOrFail($id);
        $training->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Treinamento externo removido!',
        ]);
    }

    public function regenerateToken($id)
    {
        $user = User::findOrFail($id);
        $user->update(['qrcode_token' => \Illuminate\Support\Str::random(32)]);

        return response()->json([
            'status' => 'success',
            'message' => 'Token da ficha regenerado!',
            'data' => [
                'ficha_url' => $user->ficha_url,
            ],
        ]);
    }
}
