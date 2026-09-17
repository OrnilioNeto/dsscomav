<?php

namespace App\Http\Controllers;

use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PermissionController extends Controller
{
    /**
     * Módulos exibidos na matriz de permissões.
     * Fonte única: config/modules.php (mesmo catálogo do gate por tenant).
     *
     * @return array<string, string>
     */
    private function modules(): array
    {
        $modules = [];

        foreach (config('modules', []) as $slug => $meta) {
            $modules[$slug] = $meta['label'] ?? $slug;
        }

        return $modules;
    }

    public function index()
    {
        // Apenas super_admin pode acessar o gerenciamento de permissões globais
        if (! auth()->user()->isSuperAdmin()) {
            abort(403, 'Acesso negado. Apenas super_admin pode gerenciar as permissões globais.');
        }

        $roles = Role::where('nome', '!=', 'super_admin')
            ->with('permissions')
            ->orderBy('nome')
            ->get();

        $modules = $this->modules();

        return view('permissoes.index', compact('roles', 'modules'));
    }

    public function storeRole(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $request->validate([
            'display_name' => 'required|string|max:255',
            'descricao' => 'nullable|string|max:255',
        ]);

        $nome = Str::slug($request->input('display_name'), '_');

        // Evitar nomes de sistema reservados
        if (in_array($nome, ['super_admin', 'admin', 'usuario'])) {
            return redirect()->back()->with('error', 'Nome de perfil reservado pelo sistema.');
        }

        // Verificar duplicidade
        if (Role::where('nome', $nome)->exists()) {
            return redirect()->back()->with('error', 'Já existe um perfil com este nome.');
        }

        $role = Role::create([
            'nome' => $nome,
            'descricao' => $request->input('descricao') ?: $request->input('display_name'),
        ]);

        // Inicializar permissões vazias para todos os módulos do novo perfil
        foreach ($this->modules() as $module => $label) {
            $role->permissions()->create([
                'module' => $module,
                'can_view' => false,
                'can_edit' => false,
            ]);
        }

        return redirect()->back()->with('success', 'Perfil criado com sucesso!');
    }

    public function destroyRole($id)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $role = Role::findOrFail($id);

        // Impedir exclusão de perfis padrões de sistema
        if (in_array($role->nome, ['admin', 'usuario', 'super_admin'])) {
            return redirect()->back()->with('error', 'Este perfil é protegido pelo sistema e não pode ser excluído.');
        }

        // Impedir exclusão se houver usuários associados
        if ($role->users()->exists()) {
            return redirect()->back()->with('error', 'Não é possível excluir este perfil pois existem usuários vinculados a ele.');
        }

        $role->delete();

        return redirect()->back()->with('success', 'Perfil excluído com sucesso!');
    }

    public function updatePermissions(Request $request)
    {
        if (! auth()->user()->isSuperAdmin()) {
            abort(403);
        }

        $permissionsData = $request->input('permissions', []);
        $roles = Role::where('nome', '!=', 'super_admin')->get();

        foreach ($roles as $role) {
            foreach ($this->modules() as $module => $label) {
                $canView = isset($permissionsData[$role->id][$module]['view']);
                $canEdit = isset($permissionsData[$role->id][$module]['edit']);

                // Consistência: se pode editar, automaticamente pode visualizar
                if ($canEdit) {
                    $canView = true;
                }

                $role->permissions()->updateOrCreate(
                    ['module' => $module],
                    [
                        'can_view' => $canView,
                        'can_edit' => $canEdit,
                    ]
                );
            }
        }

        return redirect()->back()->with('success', 'Permissões atualizadas com sucesso!');
    }
}
