<?php

namespace App\Modules\Frota\Console\Commands;

use App\Modules\Frota\Models\FrotaConfiguracao;
use App\Modules\Frota\Services\JornadaSyncService;
use App\Support\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SyncFrota extends Command
{
    protected $signature = 'frota:sync
        {--only= : Executa apenas uma etapa: drivers|vehicles|status}
        {--force : Sincroniza mesmo com a verificação de jornada desativada}';

    protected $description = 'Sincroniza motoristas, veículos e status de jornada com a Autotrac (ATIC API)';

    public function handle(TenantManager $tenants): int
    {
        if (! config('autotrac.gate_enabled', false) && ! $this->option('force')) {
            $this->info('Verificação de jornada desativada (AUTOTRAC_GATE_ENABLED=false). Nada a sincronizar.');
            $this->line('Para testar a integração mesmo assim: php artisan frota:sync --force');

            return self::SUCCESS;
        }

        $only = (string) $this->option('only');
        $permitidas = ['', 'drivers', 'vehicles', 'status'];

        if (! in_array($only, $permitidas, true)) {
            $this->error('Opção --only inválida. Use drivers, vehicles ou status.');

            return self::FAILURE;
        }

        $falhas = 0;

        $tenants->runForEachTenant(function ($tenant) use ($only, &$falhas) {
            $rotulo = $tenant ? 'tenant #'.$tenant->id.' ('.$tenant->nome.')' : 'single-tenant';

            $config = FrotaConfiguracao::atual();

            if (! $config->ativo) {
                $this->line("[{$rotulo}] Integração desativada — ignorado.");

                return;
            }

            if (! $config->estaConfigurada()) {
                $this->warn("[{$rotulo}] Credenciais incompletas — configure em /admin/frota/configuracao.");
                $falhas++;

                return;
            }

            $sync = new JornadaSyncService;

            if ($only === 'drivers') {
                $resultado = $sync->sincronizarMotoristas();
                $this->line("[{$rotulo}] Motoristas: ".($resultado['ok'] ? $resultado['processados'].' processado(s).' : $resultado['error']));
                $falhas += $resultado['ok'] ? 0 : 1;

                return;
            }

            if ($only === 'vehicles') {
                $resultado = $sync->sincronizarVeiculos();
                $this->line("[{$rotulo}] Veículos: ".($resultado['ok'] ? $resultado['processados'].' processado(s).' : $resultado['error']));
                $falhas += $resultado['ok'] ? 0 : 1;

                return;
            }

            if ($only === 'status') {
                $resultado = $sync->sincronizarStatus();
                $this->line("[{$rotulo}] Status: ".($resultado['ok'] ? $resultado['processados'].' processado(s).' : $resultado['error']));
                $falhas += $resultado['ok'] ? 0 : 1;

                return;
            }

            $resultado = $sync->sincronizarTudo();

            $this->info(sprintf(
                '[%s] Motoristas: %d | Veículos: %d | Status: %d',
                $rotulo,
                $resultado['motoristas'],
                $resultado['veiculos'],
                $resultado['status']
            ));

            foreach ($resultado['erros'] as $erro) {
                $this->warn('  - '.$erro);
            }

            $falhas += $resultado['erros'] !== [] ? 1 : 0;

            Cache::put('frota:ultimo_sync:'.($tenant?->id ?? 'single'), [
                'em' => now()->toIso8601String(),
                'resultado' => $resultado,
            ], now()->addDay());
        });

        return $falhas > 0 ? self::FAILURE : self::SUCCESS;
    }
}
