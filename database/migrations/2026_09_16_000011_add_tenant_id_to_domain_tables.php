<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabelas de domínio que recebem tenant_id (aditivo, nullable + índice).
     * O backfill (comando tenant:backfill) preenche os valores após esta migration.
     */
    private const TABLES = [
        'users',
        'roles',
        'role_permissions',
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
        'personal_access_tokens',
    ];

    public function up(): void
    {
        foreach (self::TABLES as $table) {
            if (! Schema::hasTable($table) || Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->unsignedBigInteger('tenant_id')->nullable();
                $t->index('tenant_id');
            });
        }
    }

    public function down(): void
    {
        foreach (array_reverse(self::TABLES) as $table) {
            if (! Schema::hasTable($table) || ! Schema::hasColumn($table, 'tenant_id')) {
                continue;
            }

            Schema::table($table, function (Blueprint $t) {
                $t->dropIndex(['tenant_id']);
                $t->dropColumn('tenant_id');
            });
        }
    }
};
