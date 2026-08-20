<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Role;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:users,view')->only(['index', 'show', 'relatorioExcluidosKPI']);
        $this->middleware('permission:users,edit')->except(['index', 'show', 'relatorioExcluidosKPI']);
    }

    private const TIPOS_USUARIO_VALIDOS = ['motorista', 'funcionario', 'terceirizado'];

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

    public function index(Request $request)
    {
        $nome = trim((string) $request->input('nome', ''));
        $tiposSelecionados = collect($request->input('tipos', []))
            ->filter(fn ($tipo) => in_array($tipo, self::TIPOS_USUARIO_VALIDOS, true))
            ->values()
            ->all();

        $usuariosQuery = User::with('role');

        if ($nome !== '') {
            $usuariosQuery->where('nome', 'like', '%' . $nome . '%');
        }

        if (! empty($tiposSelecionados)) {
            $usuariosQuery->whereIn('tipo_usuario', $tiposSelecionados);
        }

        $usuarios = $usuariosQuery
            ->orderBy('nome')
            ->paginate(15)
            ->withQueryString();

        return view('usuarios.index', compact('usuarios', 'nome', 'tiposSelecionados'));
    }

    public function create()
    {
        $this->ensureBaseRoles();

        $roles = Role::where('nome', '!=', 'super_admin')
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
            'camisa_tamanho' => 'nullable|string|max:20',
            'calca_tamanho' => 'nullable|string|max:20',
            'bota_numero' => 'nullable|string|max:20',
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

        $roles = Role::where('nome', '!=', 'super_admin')
            ->orderBy('nome')
            ->get();

        return view('usuarios.edit', compact('usuario', 'roles'));
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
            'camisa_tamanho' => 'nullable|string|max:20',
            'calca_tamanho' => 'nullable|string|max:20',
            'bota_numero' => 'nullable|string|max:20',
            'status' => 'required|in:ativo,inativo',
            'ferias_inicio' => 'nullable|date|required_with:ferias_fim',
            'ferias_fim' => 'nullable|date|required_with:ferias_inicio|after_or_equal:ferias_inicio',
            'usuario_teste' => 'nullable|boolean',
        ];

        if (!$usuario->isSuperAdmin()) {
            $rules['role_id'] = 'required|exists:roles,id';
        }

        // Se super_admin, permitir alteração de senha
        if (auth()->user()->isSuperAdmin()) {
            $rules['password'] = 'nullable|string|min:8|confirmed';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = $request->all();
        $data['ferias_inicio'] = $request->filled('ferias_inicio') ? $request->input('ferias_inicio') : null;
        $data['ferias_fim'] = $request->filled('ferias_fim') ? $request->input('ferias_fim') : null;
        $data['usuario_teste'] = $request->boolean('usuario_teste');

        // Registra a data de inativação quando o status muda para 'inativo'
        // e limpa quando o usuário é reativado (para permitir rastrear inativações).
        $novoStatus = $request->input('status');
        if ($usuario->status !== $novoStatus) {
            if ($novoStatus === 'inativo') {
                $data['data_inativacao'] = now();
            } else {
                $data['data_inativacao'] = null;
            }
        }

        if ($usuario->isSuperAdmin()) {
            unset($data['role_id']);
        }
        
        // Se super_admin e preencheu nova senha, hashear e incluir na atualização
        if (auth()->user()->isSuperAdmin() && $request->filled('password')) {
            $data['password'] = Hash::make($request->input('password'));
        } else {
            // Remover da tentativa de update para não alterar senha
            unset($data['password']);
        }
        
        // Remover confirmação de senha da atualização
        unset($data['password_confirmation']);
        
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

    public function relatorioExcluidosKPI()
    {
        // Apenas super_admin pode acessar
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Acesso negado. Apenas super_admin pode acessar este relatório.');
        }

        $today = Carbon::now(config('app.timezone'))->toDateString();

        // Usuários super_admin
        $superAdmins = User::whereHas('role', function ($q) {
            $q->where('nome', 'super_admin');
        })->get();

        // Usuários de teste
        $usuariosTeste = User::where('usuario_teste', true)->get();

        // Admins sem participação em treinamentos
        $adminsSemParticipacaoTreinamentos = User::where('usuario_teste', false)
            ->whereHas('role', function ($q) {
                $q->where('nome', 'admin');
            })
            ->where('participa_treinamentos', false)
            ->get();

        // Usuários em férias (agora), exceto super_admin e exceto usuários de teste
        $usuariosEmFerias = User::where('usuario_teste', false)
            ->where(function ($roleQuery) {
                $roleQuery->whereNull('role_id')
                    ->orWhereHas('role', function ($role) {
                        $role->where('nome', '<>', 'super_admin');
                    });
            })
            ->whereNotNull('ferias_inicio')
            ->whereNotNull('ferias_fim')
            ->whereDate('ferias_inicio', '<=', $today)
            ->whereDate('ferias_fim', '>=', $today)
            ->get();

        // Usuários inativos (bloqueados do sistema e desconsiderados dos treinamentos a partir da inativação)
        $usuariosInativos = User::where('status', 'inativo')
            ->orderByDesc('data_inativacao')
            ->get();

        // Excluídos únicos: super_admin OU usuário_teste OU admin sem participação OU em férias OU inativo
        $totalExcluidosKPI = User::where(function ($query) use ($today) {
            $query->whereHas('role', function ($roleQuery) {
                $roleQuery->where('nome', 'super_admin');
            })
                ->orWhere('usuario_teste', true)
                ->orWhere('status', 'inativo')
                ->orWhere(function ($adminWithoutParticipationQuery) {
                    $adminWithoutParticipationQuery->where('usuario_teste', false)
                        ->whereHas('role', function ($roleQuery) {
                            $roleQuery->where('nome', 'admin');
                        })
                        ->where('participa_treinamentos', false);
                })
                ->orWhere(function ($vacationQuery) use ($today) {
                    $vacationQuery->where('usuario_teste', false)
                        ->where(function ($roleQuery) {
                            $roleQuery->whereNull('role_id')
                                ->orWhereHas('role', function ($role) {
                                    $role->where('nome', '<>', 'super_admin');
                                });
                        })
                        ->whereNotNull('ferias_inicio')
                        ->whereNotNull('ferias_fim')
                        ->whereDate('ferias_inicio', '<=', $today)
                        ->whereDate('ferias_fim', '>=', $today);
                });
        })->count();

        $totalUsuarios = User::count();
        $usuariosEligiveisKPI = User::kpiEligible()->count();

        return view('usuarios.excluidos-kpi', compact(
            'superAdmins',
            'usuariosTeste',
            'adminsSemParticipacaoTreinamentos',
            'usuariosEmFerias',
            'usuariosInativos',
            'usuariosEligiveisKPI',
            'totalUsuarios',
            'totalExcluidosKPI'
        ));
    }
}
