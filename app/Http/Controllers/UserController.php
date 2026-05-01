<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index()
    {
        $usuarios = User::with('role')->paginate(15);
        return view('usuarios.index', compact('usuarios'));
    }

    public function create()
    {
        $roles = Role::all();
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
            'role_id' => 'required|exists:roles,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Dados específicos conforme tipo de usuário
        $data = $request->all();
        $data['cpf'] = preg_replace('/\D/', '', $data['cpf']);
        $data['password'] = Hash::make($data['password']);

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
        $roles = Role::all();
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
            'status' => 'required|in:ativo,inativo',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        
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
