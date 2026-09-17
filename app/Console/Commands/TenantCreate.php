<?php

namespace App\Console\Commands;

use App\Models\Role;
use App\Models\Tenant;
use App\Models\TenantModule;
use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Provisiona um novo cliente (tenant) de ponta a ponta:
 * tenant + primeiro admin + módulos ativos + dados mestres de ranking.
 *
 * Ex.: php artisan tenant:create --name="Transportes XYZ" --admin-cpf=55555555555 --admin-senha=Segura123
 */
class TenantCreate extends Command
{
    protected $signature = 'tenant:create
        {--name= : Nome do cliente (obrigatório)}
        {--slug= : Slug do subdomínio (padrão: derivado do nome, com sufixo se já existir)}
        {--dominio= : Domínio próprio opcional}
        {--plano= : Plano opcional}
        {--status=ativo : ativo|trial|suspenso|cancelado}
        {--admin-nome=Administrador : Nome do primeiro admin}
        {--admin-cpf= : CPF do primeiro admin (obrigatório)}
        {--admin-email= : E-mail opcional do primeiro admin}
        {--admin-senha= : Senha do primeiro admin (obrigatório)}';

    protected $description = 'Cria cliente + admin inicial + módulos + dados mestres (provisionamento SaaS)';

    public function handle(): int
    {
        $name = $this->option('name');
        $cpf = preg_replace('/\D/', '', (string) $this->option('admin-cpf'));
        $senha = $this->option('admin-senha');

        if (! $name || strlen($cpf) !== 11 || ! $senha) {
            $this->error('Uso: tenant:create --name="Cliente" --admin-cpf=00000000000 --admin-senha=SuaSenha');

            return self::FAILURE;
        }

        $slug = $this->option('slug') ?: Tenant::slugUnico(Str::slug($name));

        $tenant = Tenant::create([
            'nome' => $name,
            'slug' => $slug,
            'dominio' => $this->option('dominio') ?: null,
            'status' => $this->option('status') ?: 'ativo',
            'plano' => $this->option('plano') ?: null,
            'nome_exibicao' => $name,
        ]);
        $this->info("Tenant #{$tenant->id} criado: {$tenant->nome} (slug: {$tenant->slug})");

        // Módulos ativos
        $modules = array_keys(config('modules', []));
        foreach ($modules as $module) {
            TenantModule::create([
                'tenant_id' => $tenant->id,
                'module' => $module,
                'enabled' => true,
                'enabled_at' => now(),
            ]);
        }
        $this->info(count($modules) . ' módulos ativados.');

        // Dados mestres de ranking (critérios + regras), copiados do tenant de referência
        $this->seedRankingCriteria($tenant->id);
        $this->info('Critérios/regras de ranking semeados.');

        // Primeiro admin
        $this->ensureBaseRoles();
        $role = Role::where('nome', 'admin')->first();
        if (! $role) {
            $this->warn('Perfil admin não encontrado — admin não criado. Rode os seeders.');

            return self::SUCCESS;
        }

        $admin = User::create([
            'nome' => $this->option('admin-nome') ?: 'Administrador',
            'cpf' => $cpf,
            'email' => $this->option('admin-email') ?: 'admin@' . $slug . '.com',
            'password' => bcrypt($senha),
            'tipo_usuario' => 'funcionario',
            'status' => 'ativo',
            'role_id' => $role->id,
            'tenant_id' => $tenant->id,
        ]);
        $this->info("Admin criado: {$admin->nome} (CPF {$cpf})");
        $this->info('Acesso: https://' . $slug . '.' . rtrim((string) config('saas.root_domain', 'dominio'), '/') . '/login');

        return self::SUCCESS;
    }

    private function ensureBaseRoles(): void
    {
        foreach ([
            'super_admin' => 'Super Administrador da Plataforma',
            'admin' => 'Administrador do Cliente',
            'usuario' => 'Usuário padrão',
        ] as $nome => $descricao) {
            Role::firstOrCreate(['nome' => $nome], ['descricao' => $descricao]);
        }
    }

    private function seedRankingCriteria(int $tenantId): void
    {
        if (! DB::getSchemaBuilder()->hasTable('ranking_criteria')) {
            return;
        }

        // Tenant de referência: critérios do tenant #1 (ou os sem tenant).
        $referencia = DB::table('ranking_criteria')
            ->where('tenant_id', 1)
            ->orWhereNull('tenant_id')
            ->get();

        if ($referencia->isEmpty()) {
            return;
        }

        foreach ($referencia as $criterio) {
            $dados = [
                'name' => $criterio->name,
                'slug' => $criterio->slug,
                'description' => $criterio->description,
                'sort_order' => $criterio->sort_order,
                'tenant_id' => $tenantId,
                'created_at' => now(),
                'updated_at' => now(),
            ];

            if (DB::getSchemaBuilder()->hasColumn('ranking_criteria', 'is_active')) {
                $dados['is_active'] = $criterio->is_active ?? true;
            }

            $novoId = DB::table('ranking_criteria')->insertGetId($dados);

            $regras = DB::table('ranking_rules')->where('criterion_id', $criterio->id)->get();
            foreach ($regras as $regra) {
                DB::table('ranking_rules')->insert([
                    'criterion_id' => $novoId,
                    'label' => $regra->label,
                    'min_value' => $regra->min_value,
                    'max_value' => $regra->max_value,
                    'points' => $regra->points,
                    'sort_order' => $regra->sort_order,
                    'tenant_id' => $tenantId,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }
    }
}