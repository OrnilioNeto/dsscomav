<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tenant;
use App\Models\TenantModule;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * Painel da Plataforma (somente super_admin, path /plataforma).
 * Gestão de clientes (tenants) e módulos liberados por cliente.
 */
class PlataformaTenantController extends Controller
{
    public function index()
    {
        $tenants = Tenant::withCount('modules')->orderBy('id')->get();

        return view('plataforma.index', compact('tenants'));
    }

    public function create()
    {
        $modules = config('modules', []);

        return view('plataforma.create', compact('modules'));
    }

    public function store(Request $request)
    {
        $data = $this->validar($request, null);

        // Slug gerado automaticamente a partir do nome: garante unicidade
        // (a validação unique só cobre o slug enviado no formulário).
        $slug = $data['slug'] ?? Tenant::slugUnico(Str::slug($data['nome']));

        $tenant = Tenant::create([
            'nome' => $data['nome'],
            'slug' => $slug,
            'dominio' => $request->input('dominio') ?: null,
            'status' => $data['status'],
            'plano' => $request->input('plano') ?: null,
            'nome_exibicao' => $request->input('nome_exibicao') ?: $data['nome'],
            'email_remetente' => $request->input('email_remetente') ?: null,
        ]);

        // Módulos marcados no formulário (padrão: todos ativos)
        $selecionados = $request->input('modules', []);
        foreach (array_keys(config('modules', [])) as $module) {
            $enabled = in_array($module, $selecionados, true);
            TenantModule::updateOrCreate(
                ['tenant_id' => $tenant->id, 'module' => $module],
                ['enabled' => $enabled, 'enabled_at' => $enabled ? now() : null]
            );
        }

        return redirect()->route('plataforma.index')->with('success', "Cliente '{$tenant->nome}' criado!");
    }

    public function show(Tenant $tenant)
    {
        return $this->edit($tenant);
    }

    public function edit(Tenant $tenant)
    {
        $modules = config('modules', []);

        return view('plataforma.edit', compact('tenant', 'modules'));
    }

    public function update(Request $request, Tenant $tenant)
    {
        $data = $this->validar($request, $tenant->id);

        $tenant->update([
            'nome' => $data['nome'],
            'slug' => $data['slug'] ?? $tenant->slug,
            'dominio' => $request->input('dominio') ?: null,
            'status' => $data['status'],
            'plano' => $request->input('plano') ?: null,
            'nome_exibicao' => $request->input('nome_exibicao') ?: $data['nome'],
            'email_remetente' => $request->input('email_remetente') ?: null,
            'cor_primaria' => $request->input('cor_primaria') ?: null,
            'cor_secundaria' => $request->input('cor_secundaria') ?: null,
            'instrutor_nome' => $request->input('instrutor_nome') ?: null,
            'instrutor_qualificacao' => $request->input('instrutor_qualificacao') ?: null,
            'instrutor_rg' => $request->input('instrutor_rg') ?: null,
        ]);

        foreach (['logo' => 'logo', 'logo_certificado' => 'logo_certificado'] as $campo => $subdir) {
            if ($request->hasFile($campo)) {
                $dir = "uploads/{$tenant->id}/logos";
                $file = $request->file($campo);
                $filename = $subdir . '_' . time() . '.' . strtolower($file->getClientOriginalExtension());
                $file->move(public_path($dir), $filename);
                $tenant->update([$campo => $dir . '/' . $filename]);
            }
        }

        return redirect()->route('plataforma.index')->with('success', 'Cliente atualizado!');
    }

    public function toggleModule(Tenant $tenant, string $module)
    {
        if (! array_key_exists($module, config('modules', []))) {
            abort(404);
        }

        $reg = TenantModule::firstOrCreate(
            ['tenant_id' => $tenant->id, 'module' => $module],
            ['enabled' => true, 'enabled_at' => now()]
        );

        $reg->enabled = ! $reg->enabled;
        $reg->enabled_at = $reg->enabled ? now() : null;
        $reg->save();

        return redirect()->route('plataforma.edit', $tenant)->with(
            'success',
            "Módulo '{$module}' " . ($reg->enabled ? 'liberado' : 'bloqueado') . " para {$tenant->nome}."
        );
    }

    private function slugUnico(string $base): string
    {
        return Tenant::slugUnico($base);
    }

    /**
     * Cria o primeiro usuário admin de um tenant (desbloqueio inicial).
     */
    public function createAdmin(Request $request, Tenant $tenant)
    {
        $data = $request->validate([
            'nome' => 'required|string|max:255',
            'cpf' => 'required|digits:11|unique:users,cpf',
            'email' => 'nullable|email|max:255|unique:users,email',
            'senha' => 'required|string|min:6',
        ]);

        $role = \App\Models\Role::firstOrCreate(
            ['nome' => 'admin'],
            ['descricao' => 'Administrador do Cliente']
        );

        $user = \App\Models\User::create([
            'nome' => $data['nome'],
            'cpf' => $data['cpf'],
            'email' => $data['email'] ?? 'admin@' . $tenant->slug . '.com',
            'password' => bcrypt($data['senha']),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
            'tenant_id' => $tenant->id,
        ]);

        return redirect()->route('plataforma.edit', $tenant)->with('success', "Admin '{$user->nome}' criado para {$tenant->nome} (CPF {$user->cpf}).");
    }

    private function validar(Request $request, ?int $ignoreId): array
    {
        return $request->validate([
            'nome' => 'required|string|max:255',
            'slug' => 'nullable|string|max:60|alpha_dash|unique:tenants,slug,' . $ignoreId,
            'dominio' => 'nullable|string|max:255|unique:tenants,dominio,' . $ignoreId,
            'status' => 'required|in:ativo,trial,suspenso,cancelado',
            'plano' => 'nullable|string|max:60',
            'nome_exibicao' => 'nullable|string|max:255',
            'email_remetente' => 'nullable|email|max:255',
            'cor_primaria' => 'nullable|string|max:20',
            'cor_secundaria' => 'nullable|string|max:20',
            'instrutor_nome' => 'nullable|string|max:255',
            'instrutor_qualificacao' => 'nullable|string|max:255',
            'instrutor_rg' => 'nullable|string|max:60',
            'logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
            'logo_certificado' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
        ]);
    }
}