<?php

namespace App\Console\Commands;

use App\Models\Tenant;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Diagnóstico de multi-tenancy (somente leitura).
 *
 * Mostra a flag efetiva, os tenants, a distribuição de dados por tenant e
 * alerta riscos de isolamento (ex.: flag desligada com vários clientes).
 */
class SaasDoctor extends Command
{
    protected $signature = 'saas:doctor';

    protected $description = 'Diagnóstico de multi-tenancy: flag, tenants, dados por tenant e riscos de isolamento';

    private const DATA_TABLES = [
        'users',
        'trainings',
        'training_materials',
        'user_progress',
        'certificates',
        'employee_trainings',
        'social_posts',
        'splash_contents',
        'ss_epi',
        'ss_colaborador',
        'ss_epi_entrega',
        'folga_dias',
    ];

    public function handle(): int
    {
        $enabled = (bool) config('saas.enabled');
        $rootDomain = (string) config('saas.root_domain');
        $rootSlug = (string) config('saas.root_tenant_slug', 'cliente');

        $this->newLine();
        $this->info('=== Multi-tenancy: diagnóstico ===');
        $this->table(['Configuração efetiva', 'Valor'], [
            ['SAAS_MULTITENANT_ENABLED', $enabled ? 'true' : 'false'],
            ['SAAS_ROOT_DOMAIN', $rootDomain !== '' ? $rootDomain : '(vazio)'],
            ['SAAS_ROOT_TENANT_SLUG', $rootSlug],
            ['Cache de config', app()->configurationIsCached() ? 'ATIVO (rode config:clear/config:cache após mudar o .env)' : 'inativo'],
        ]);

        if (! Schema::hasTable('tenants')) {
            $this->error('Tabela tenants não existe. Rode: php artisan migrate --force');

            return self::FAILURE;
        }

        $tenants = Tenant::orderBy('id')->get(['id', 'nome', 'slug', 'status', 'dominio']);
        $this->table(
            ['id', 'nome', 'slug', 'status', 'dominio'],
            $tenants->map(fn ($t) => [$t->id, $t->nome, $t->slug, $t->status, $t->dominio ?: '—'])->all()
        );

        $rootTenant = $tenants->firstWhere('slug', $rootSlug);
        $totaisPorTenant = [];
        $linhas = [];

        foreach (self::DATA_TABLES as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            foreach (DB::table($table)->selectRaw('tenant_id, count(*) as total')->groupBy('tenant_id')->orderBy('tenant_id')->get() as $row) {
                $key = $row->tenant_id ?? 'NULL';
                $linhas[] = [$table, $key, $row->total];
                $totaisPorTenant[$key] = ($totaisPorTenant[$key] ?? 0) + $row->total;
            }
        }

        $this->newLine();
        $this->info('Dados por tenant (tabelas de domínio):');
        $this->table(['tabela', 'tenant_id', 'registros'], $linhas);

        $usuariosSemTenantNaoSuper = DB::table('users as u')
            ->leftJoin('roles as r', 'r.id', '=', 'u.role_id')
            ->whereNull('u.tenant_id')
            ->where(function ($q) {
                $q->whereNull('r.nome')->orWhere('r.nome', '!=', 'super_admin');
            })
            ->count();

        $this->newLine();
        $this->info('Resumo por tenant:');

        foreach ($tenants as $tenant) {
            $this->line(sprintf('  #%d %s (slug: %s) — %d registro(s)', $tenant->id, $tenant->nome, $tenant->slug, $totaisPorTenant[$tenant->id] ?? 0));
        }

        if (isset($totaisPorTenant['NULL'])) {
            $this->line(sprintf('  (sem tenant) — %d registro(s) com tenant_id NULL', $totaisPorTenant['NULL']));
        }

        $this->newLine();
        $critico = false;

        if (! $enabled && $tenants->count() > 1) {
            $this->error('CRÍTICO: flag multi-tenant DESLIGADA com '.$tenants->count().' clientes cadastrados — todos os hosts veem TODOS os dados.');
            $this->error('Corrija: defina SAAS_MULTITENANT_ENABLED=true no .env e rode: php artisan optimize:clear && php artisan config:cache');
            $critico = true;
        }

        if ($enabled && ! $rootTenant) {
            $this->error("CRÍTICO: nenhum tenant com slug [{$rootSlug}] — o domínio raiz ({$rootDomain}) retorna 404.");
            $this->error("Crie o tenant raiz: php artisan tenant:backfill --name=\"Cliente Atual\" --slug={$rootSlug}");
            $critico = true;
        }

        if ($rootTenant && $rootTenant->status !== 'ativo') {
            $this->warn("ATENÇÃO: tenant raiz [{$rootTenant->slug}] está com status [{$rootTenant->status}].");
        }

        if ($usuariosSemTenantNaoSuper > 0) {
            $this->warn("ATENÇÃO: {$usuariosSemTenantNaoSuper} usuário(s) não-super_admin com tenant_id NULL — eles enxergam dados de TODOS os tenants.");
            $this->warn('Corrija com: php artisan tenant:backfill --name="Cliente Atual" --slug='.$rootSlug);
        }

        if ($enabled && $rootTenant && $tenants->count() > 1) {
            $totalRaiz = $totaisPorTenant[$rootTenant->id] ?? 0;
            $outros = 0;
            foreach ($tenants as $tenant) {
                if ($tenant->id !== $rootTenant->id) {
                    $outros += $totaisPorTenant[$tenant->id] ?? 0;
                }
            }

            if ($totalRaiz === 0 && $outros > 0) {
                $this->warn('ATENÇÃO: o tenant raiz está sem dados, mas outro tenant possui registros.');
                $this->warn('Isso acontece quando o backfill roda DEPOIS de criar outro cliente (dados atribuídos ao cliente errado).');
                $this->warn('Revise antes de corrigir: pode ser necessário reatribuir tenant_id dos dados.');
            }
        }

        if (! $critico) {
            $this->newLine();
            $this->info('Nenhum problema crítico encontrado.');
        }

        return $critico ? self::FAILURE : self::SUCCESS;
    }
}
