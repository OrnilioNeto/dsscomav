<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private function ensureBaseRoles(): void
    {
        Role::updateOrCreate(
            ['nome' => 'admin'],
            ['descricao' => 'Administrador - Gestão de usuários, treinamentos e certificados']
        );

        Role::updateOrCreate(
            ['nome' => 'usuario'],
            ['descricao' => 'Usuário - Acesso a treinamentos conforme seu tipo']
        );
    }

    public function index()
    {
        $usuarios = User::with('role')->paginate(15);
        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $this->ensureBaseRoles();

        $roles = Role::whereIn('nome', ['admin', 'usuario'])
            ->orderBy('nome')
            ->get();

        return view('usuarios.create', compact('roles'));
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
            'ferias_inicio' => 'nullable|date|required_with:ferias_fim',
            'ferias_fim' => 'nullable|date|required_with:ferias_inicio|after_or_equal:ferias_inicio',
            'usuario_teste' => 'nullable|boolean',
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Dados específicos conforme tipo de usuário
        $data = $request->all();
        $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);
        $data['password'] = Hash::make($data['password']);
        $data['ferias_inicio'] = $request->filled('ferias_inicio') ? $request->input('ferias_inicio') : null;
        $data['ferias_fim'] = $request->filled('ferias_fim') ? $request->input('ferias_fim') : null;
        $data['usuario_teste'] = $request->boolean('usuario_teste');
        $data['participa_treinamentos'] = $request->boolean('participa_treinamentos');

        User::create($data);

        return redirect()->route('usuarios.index')->with('success', 'Usuário criado com sucesso!');
    }

    public function show($id)
    {
        $usuario = User::with('role', 'certificates', 'progress')->findOrFail($id);
        return view('usuarios.show', compact('usuario'));
    }

    public function edit($id)
    {
        $usuario = User::findOrFail($id);
        $this->ensureBaseRoles();

        $roles = Role::whereIn('nome', ['admin', 'usuario'])
            ->orderBy('nome')
            ->get();

        return view('usuarios.edit', compact('usuario', 'roles'));
    }

    public function update(Request $request, $id)
    {
        $usuario = User::findOrFail($id);

        $validator = Validator::make($request->all(), [
            'nome' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email,' . $id,
            'telefone' => 'nullable|string',
            'tipo_usuario' => 'required|in:motorista,funcionario,terceirizado',
            'empresa' => 'nullable|string|max:255',
            'cargo' => 'nullable|string|max:255',
            'status' => 'required|in:ativo,inativo',
            'ferias_inicio' => 'nullable|date|required_with:ferias_fim',
            'ferias_fim' => 'nullable|date|required_with:ferias_inicio|after_or_equal:ferias_inicio',
            'usuario_teste' => 'nullable|boolean',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        $data['ferias_inicio'] = $request->filled('ferias_inicio') ? $request->input('ferias_inicio') : null;
        $data['ferias_fim'] = $request->filled('ferias_fim') ? $request->input('ferias_fim') : null;
        $data['usuario_teste'] = $request->boolean('usuario_teste');
        
        // Se é um admin, permitir marcar participação em treinamentos
        if ($usuario->isAdmin()) {
            $data['participa_treinamentos'] = $request->has('participa_treinamentos');
        }

        $usuario->update($data);

        return redirect()->route('usuarios.show', $usuario)->with('success', 'Usuário atualizado!');
    }

    public function destroy($id)
    {
        $usuario = User::findOrFail($id);
        $usuario->delete();

        return redirect()->route('usuarios.index')->with('success', 'Usuário deletado!');
    }
}
