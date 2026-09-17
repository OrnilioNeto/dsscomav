<?php

namespace App\Console\Commands;

use App\Models\AuditLog;
use App\Models\Scopes\TenantScope;
use App\Models\Tenant;
use App\Support\TenantManager;
use Illuminate\Console\Command;

class AuditPrune extends Command
{
    protected $signature = 'audit:prune
                            {--days= : Dias de retenção (default: config audit.retention_days)}
                            {--tenant= : ID do tenant específico (opcional)}';

    protected $description = 'Remove registros de auditoria mais antigos que o período de retenção';

    public function handle(): int
    {
        $days = (int) ($this->option('days') ?: config('audit.retention_days', 365));

        if ($days < 1) {
            $this->error('O período de retenção deve ser de pelo menos 1 dia.');

            return self::FAILURE;
        }

        $cutoff = now()->subDays($days);
        $manager = app(TenantManager::class);
        $total = 0;

        $tenantId = $this->option('tenant');

        if ($tenantId) {
            $tenant = Tenant::findOrFail((int) $tenantId);
            $manager->set($tenant);
            $total = AuditLog::where('created_at', '<', $cutoff)->delete();
            $manager->clear();
        } else {
            $manager->runForEachTenant(function () use ($cutoff, &$total) {
                $total += AuditLog::where('created_at', '<', $cutoff)->delete();
            });

            if ($manager->isEnabled()) {
                $total += AuditLog::withoutGlobalScope(TenantScope::class)
                    ->whereNull('tenant_id')
                    ->where('created_at', '<', $cutoff)
                    ->delete();
            }
        }

        $this->info("Auditoria: {$total} registro(s) removido(s) anterior(es) a {$cutoff->format('d/m/Y H:i')}.");

        return self::SUCCESS;
    }
}
