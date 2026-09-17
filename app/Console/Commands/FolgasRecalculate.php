<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\FolgaBankService;
use App\Support\TenantManager;
use Illuminate\Console\Command;

class FolgasRecalculate extends Command
{
    protected $signature = 'folgas:recalculate {--month= : Mês (1-12). Padrão: mês atual} {--year= : Ano. Padrão: ano atual}';

    protected $description = 'Recalcula os saldos mensais de folgas para todos os motoristas ativos';

    public function handle(FolgaBankService $bank): int
    {
        $mes = (int) ($this->option('month') ?? now()->month);
        $ano = (int) ($this->option('year') ?? now()->year);

        if ($mes < 1 || $mes > 12) {
            $this->error('Mês inválido. Use 1-12.');

            return 1;
        }

        $manager = app(TenantManager::class);

        $this->info("Recalculando saldos de folgas para {$mes}/{$ano}...");

        $total = 0;

        $manager->runForEachTenant(function ($tenant) use ($bank, $mes, $ano, &$total) {
            if ($tenant) {
                $this->info("  -> Tenant: {$tenant->nome} (#{$tenant->id})");
            }

            $motoristas = User::where('tipo_usuario', 'motorista')
                ->where('status', 'ativo')
                ->orderBy('nome')
                ->get();

            foreach ($motoristas as $motorista) {
                $bank->recalcularMes($motorista, $mes, $ano);
                $total++;
            }
        });

        $this->info("Concluído! {$total} motorista(s) recalculado(s).");

        return 0;
    }
}