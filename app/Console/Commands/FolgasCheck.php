<?php

namespace App\Console\Commands;

use App\Models\FolgaDia;
use App\Models\FolgaMovimento;
use App\Models\FolgaSaldoMensal;
use App\Models\User;
use App\Services\FolgaRulesService;
use App\Support\TenantManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FolgasCheck extends Command
{
    protected $signature = 'folgas:check {--month= : Mês (1-12). Padrão: mês atual} {--year= : Ano. Padrão: ano atual}';

    protected $description = 'Verifica consistência dos dados de folgas (diagnóstico)';

    public function handle(FolgaRulesService $rules): int
    {
        $mes = (int) ($this->option('month') ?? now()->month);
        $ano = (int) ($this->option('year') ?? now()->year);

        $manager = app(TenantManager::class);

        $this->info("Verificando dados de folgas para {$mes}/{$ano}...");
        $this->newLine();

        $problemasGlobal = 0;

        $manager->runForEachTenant(function ($tenant) use ($rules, $mes, $ano, &$problemasGlobal) {
            if ($tenant) {
                $this->info("-> Tenant: {$tenant->nome} (#{$tenant->id})");
            }

            $motoristas = User::where('tipo_usuario', 'motorista')
                ->where('status', 'ativo')
                ->get();

            $problemas = 0;

            // 1. Verificar registros duplicados
            $duplicados = FolgaDia::whereMonth('data', $mes)
                ->whereYear('data', $ano)
                ->select('user_id', 'data', DB::raw('COUNT(*) as cnt'))
                ->groupBy('user_id', 'data')
                ->having('cnt', '>', 1)
                ->get();

            if ($duplicados->isNotEmpty()) {
                $this->error('REGISTROS DUPLICADOS ENCONTRADOS:');
                foreach ($duplicados as $dup) {
                    $user = User::find($dup->user_id);
                    $this->error("  - Motorista #{$dup->user_id} ({$user?->nome}) em {$dup->data}: {$dup->cnt} registros");
                    $problemas++;
                }
                $this->newLine();
            }

            // 2. Verificar saldos divergentes
            $this->info('Verificando saldos mensais...');
            foreach ($motoristas as $motorista) {
                $snapshot = $rules->computeSnapshot($motorista, $mes, $ano);
                $salvo = FolgaSaldoMensal::where('user_id', $motorista->id)
                    ->where('mes', $mes)
                    ->where('ano', $ano)
                    ->first();

                if ($salvo) {
                    if ($salvo->saldo_acumulado !== $snapshot['saldo_acumulado']) {
                        $this->warn("  ⚠ {$motorista->nome}: saldo salvo={$salvo->saldo_acumulado}, calculado={$snapshot['saldo_acumulado']}");
                        $problemas++;
                    }
                    if ($salvo->previstas !== $snapshot['previstas_ganhas']) {
                        $this->warn("  ⚠ {$motorista->nome}: previstas ganhas salvo={$salvo->previstas}, calculado={$snapshot['previstas_ganhas']}");
                        $problemas++;
                    }
                }
            }

            // 3. Verificar movimentos órfãos
            $movimentosSemDia = FolgaMovimento::where('referencia_mes', $mes)
                ->where('referencia_ano', $ano)
                ->where('tipo', 'debito_folga')
                ->whereDoesntHave('user.folgaDias', function ($q) use ($mes, $ano) {
                    $q->whereMonth('data', $mes)->whereYear('data', $ano)->where('tipo', 'folga');
                })
                ->count();

            if ($movimentosSemDia > 0) {
                $this->warn("MOVIMENTOS DE DÉBITO SEM DIA DE FOLGA CORRESPONDENTE: {$movimentosSemDia}");
                $problemas++;
            }

            $problemasGlobal += $problemas;
        });

        // 4. Resumo
        $this->newLine();
        if ($problemasGlobal === 0) {
            $this->info("✅ Todos os dados estão consistentes para {$mes}/{$ano}.");
        } else {
            $this->error("⚠ {$problemasGlobal} problema(s) encontrado(s). Execute 'php artisan folgas:recalculate' para corrigir.");
        }

        return $problemasGlobal === 0 ? 0 : 1;
    }
}
