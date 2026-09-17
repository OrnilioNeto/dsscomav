<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Backfill do tenant #1 (cliente atual).
 *
 * Uso (produção): php artisan tenant:backfill --name="DSS Soluções" --slug=cliente
 *
 * - Cria o tenant #1 se não existir.
 * - Preenche tenant_id em todas as linhas das tabelas de domínio.
 * - Sincroniza tenant_id em personal_access_tokens a partir do usuário dono.
 * - Garante tenant_modules com todos os módulos ativos para o tenant.
 */
class BackfillTenantId extends Command
{
    protected $signature = 'tenant:backfill
        {--name= : Nome do tenant #1 (cliente atual)}
        {--slug= : Slug do tenant #1 (usado no subdomínio)}
        {--dominio= : Domínio raiz que atende este tenant}';

    protected $description = 'Cria o tenant #1 e preenche tenant_id em todas as tabelas (idempotente)';

    private const TABLES = [
        'users',
        'trainings',
        'training_materials',
        'training_questions',
        'training_assignments',
        'training_logs',
        'training_rewatch_requests',
        'training_vacation_exemptions',
        'employee_trainings',
        'employee_epis',
        'user_progress',
        'certificates',
        'user_vacations',
        'ranking_settings',
        'ranking_criteria',
        'ranking_rules',
        'ranking_scores',
        'ranking_monthly_scores',
        'ranking_histories',
        'folga_settings',
        'folga_dias',
        'folga_movimentos',
        'folga_saldos_mensais',
        'folga_domingo_saldos',
        'folga_logs',
        'social_posts',
        'social_likes',
        'social_comments',
        'social_follows',
        'splash_contents',
        'training_projetos_pedagogicos',
        'projeto_pedagogico_trainings',
        'ss_epi',
        'ss_colaborador',
        'ss_epi_estoque',
        'ss_epi_entrega',
        'ss_epi_devolucao',
        'ss_epi_variacao',
        'ss_kit',
        'ss_kit_item',
        'ss_filial',
    ];

    /**
     * PKs customizadas das tabelas legadas do EPI (padrão ss_*).
     * As demais usam `id`.
     */
    private const CUSTOM_PKS = [
        'ss_epi' => 'ss_e_nb_id',
        'ss_colaborador' => 'ss_c_nb_id',
        'ss_epi_estoque' => 'ss_e_nb_id',
        'ss_epi_entrega' => 'ss_e_nb_id',
        'ss_epi_devolucao' => 'ss_ed_nb_id',
        'ss_epi_variacao' => 'ss_ev_nb_id',
        'ss_kit' => 'ss_k_nb_id',
        'ss_kit_item' => 'ss_ki_nb_id',
        'ss_filial' => 'ss_f_nb_id',
    ];

    public function handle(): int
    {
        if (! Schema::hasTable('tenants')) {
            $this->error('Tabela tenants não existe. Rode: php artisan migrate --force');

            return self::FAILURE;
        }

        $tenant = Tenant::first();
        if (! $tenant) {
            $tenant = Tenant::create([
                'nome' => $this->option('name') ?: 'Cliente Atual',
                'slug' => $this->option('slug') ?: 'cliente',
                'dominio' => $this->option('dominio') ?: null,
                'status' => 'ativo',
                'plano' => null,
                'nome_exibicao' => $this->option('name') ?: 'Cliente Atual',
            ]);
            $this->info("Tenant #{$tenant->id} criado: {$tenant->nome} (slug: {$tenant->slug})");
        } else {
            $this->info("Usando tenant existente #{$tenant->id}: {$tenant->nome}");
        }

        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            $affected = $this->backfillTable($table, $tenant->id);
            $this->line("  {$table}: {$affected} linha(s) preenchida(s)");
        }

        if (Schema::hasTable('personal_access_tokens') && Schema::hasColumn('personal_access_tokens', 'tenant_id')) {
            $driver = DB::connection()->getDriverName();
            if ($driver === 'mysql' || $driver === 'mariadb') {
                $affected = DB::table('personal_access_tokens as pat')
                    ->join('users as u', 'u.id', '=', 'pat.tokenable_id')
                    ->whereNull('pat.tenant_id')
                    ->update(['pat.tenant_id' => DB::raw('u.tenant_id')]);
            } else {
                $affected = 0;
                foreach (DB::table('personal_access_tokens')->whereNull('tenant_id')->get(['id', 'tokenable_id']) as $pat) {
                    $userId = $pat->tokenable_id;
                    $userTenant = DB::table('users')->where('id', $userId)->value('tenant_id');
                    if ($userTenant !== null) {
                        DB::table('personal_access_tokens')->where('id', $pat->id)->update(['tenant_id' => $userTenant]);
                        $affected++;
                    }
                }
            }
            $this->line("  personal_access_tokens: {$affected} token(s) sincronizado(s)");
        }

        // Super admins são usuários da PLATAFORMA: não pertencem a nenhum tenant.
        if (Schema::hasTable('users') && Schema::hasColumn('users', 'tenant_id')) {
            $this->clearSuperAdminTenant();
            $this->line('  users(super_admin): tenant_id restaurado para NULL (usuários da plataforma)');
        }

        // role_permissions herda o tenant da role (roles de sistema ficam NULL)
        $this->syncRolePermissionTenant();
        $this->line('  role_permissions: tenant_id herdado das roles');

        $modules = array_keys(config('modules', []));
        foreach ($modules as $module) {
            DB::table('tenant_modules')->updateOrInsert(
                ['tenant_id' => $tenant->id, 'module' => $module],
                ['enabled' => true, 'enabled_at' => now(), 'updated_at' => now()]
            );
        }
        $this->info('tenant_modules garantidos para todos os módulos do catálogo.');

        $this->info('Backfill concluído com sucesso.');

        return self::SUCCESS;
    }

    private function clearSuperAdminTenant(): void
    {
        if (! Schema::hasTable('roles')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::table('users as u')
                ->join('roles as r', 'r.id', '=', 'u.role_id')
                ->where('r.nome', 'super_admin')
                ->update(['u.tenant_id' => null]);
        } else {
            foreach (DB::table('users')->whereNotNull('tenant_id')->get(['id', 'role_id']) as $u) {
                $role = DB::table('roles')->where('id', $u->role_id)->value('nome');
                if ($role === 'super_admin') {
                    DB::table('users')->where('id', $u->id)->update(['tenant_id' => null]);
                }
            }
        }
    }

    private function syncRolePermissionTenant(): void
    {
        if (! Schema::hasTable('role_permissions') || ! Schema::hasColumn('role_permissions', 'tenant_id') || ! Schema::hasTable('roles')) {
            return;
        }

        $driver = DB::connection()->getDriverName();
        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::table('role_permissions as rp')
                ->join('roles as r', 'r.id', '=', 'rp.role_id')
                ->whereNull('rp.tenant_id')
                ->update(['rp.tenant_id' => DB::raw('r.tenant_id')]);
        } else {
            foreach (DB::table('role_permissions')->whereNull('tenant_id')->get(['id', 'role_id']) as $rp) {
                $roleTenant = DB::table('roles')->where('id', $rp->role_id)->value('tenant_id');
                if ($roleTenant !== null) {
                    DB::table('role_permissions')->where('id', $rp->id)->update(['tenant_id' => $roleTenant]);
                }
            }
        }
    }

    private function backfillTable(string $table, int $tenantId): int
    {
        $pk = self::CUSTOM_PKS[$table] ?? 'id';
        $affected = 0;
        $batch = 1000;
        $lastId = 0;

        while (true) {
            $ids = DB::table($table)
                ->whereNull('tenant_id')
                ->where($pk, '>', $lastId)
                ->orderBy($pk)
                ->limit($batch)
                ->pluck($pk);

            if ($ids->isEmpty()) {
                break;
            }

            $lastId = $ids->last();
            $affected += DB::table($table)->whereIn($pk, $ids)->update(['tenant_id' => $tenantId]);

            if ($ids->count() < $batch) {
                break;
            }
        }

        return $affected;
    }
}
