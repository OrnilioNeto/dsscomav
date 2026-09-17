<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Configuração global do módulo (single row)
        if (! Schema::hasTable('folga_settings')) {
            Schema::create('folga_settings', function (Blueprint $table) {
                $table->id();
                $table->integer('dias_para_folga')->default(6)->comment('Dias trabalhados consecutivos para ganhar 1 folga');
                $table->boolean('exige_domingo')->default(true)->comment('Exigir pelo menos 1 folga no domingo por mês');
                $table->boolean('bloquear_sem_domingo')->default(false)->comment('Bloquear fechamento do mês se domingo não cumprido');
                $table->timestamps();
            });

            // Inserir configuração padrão
            DB::table('folga_settings')->insert([
                'dias_para_folga' => 6,
                'exige_domingo' => true,
                'bloquear_sem_domingo' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Registro dia a dia (escala do motorista)
        if (! Schema::hasTable('folga_dias')) {
            Schema::create('folga_dias', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('data');
                $table->enum('tipo', ['trabalho', 'folga', 'atestado', 'licenca'])->default('trabalho');
                $table->string('motivo_folga')->nullable()->comment('Motivo da folga (ex: compensatória, domingo obrigatório)');
                $table->text('observacao')->nullable();
                $table->enum('origem', ['manual', 'csv', 'sistema'])->default('manual');
                $table->unsignedBigInteger('lancado_por')->nullable()->index();
                $table->timestamp('lancado_em')->nullable();
                $table->timestamps();

                $table->unique(['user_id', 'data']);
                $table->index('data');
                $table->index('tipo');
            });
        }

        // Movimentos do banco de folgas (trilha financeira)
        if (! Schema::hasTable('folga_movimentos')) {
            Schema::create('folga_movimentos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->date('data');
                $table->enum('tipo', ['credito_escala', 'debito_folga', 'ajuste', 'estorno']);
                $table->integer('quantidade')->default(1)->comment('Quantidade de folgas (negativo para estorno/ajuste negativo)');
                $table->unsignedTinyInteger('referencia_mes');
                $table->unsignedSmallInteger('referencia_ano');
                $table->text('observacao')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'referencia_ano', 'referencia_mes']);
            });
        }

        // Snapshot mensal consolidado (similar a ranking_monthly_scores)
        if (! Schema::hasTable('folga_saldos_mensais')) {
            Schema::create('folga_saldos_mensais', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index();
                $table->unsignedTinyInteger('mes');
                $table->unsignedSmallInteger('ano');
                $table->integer('previstas')->default(0)->comment('Créditos ganhos no mês (sequências de 6 completas)');
                $table->integer('tiradas')->default(0)->comment('Folgas efetivamente tiradas no mês');
                $table->integer('ajustes')->default(0)->comment('Ajustes manuais no mês');
                $table->integer('saldo_anterior')->default(0)->comment('Saldo acumulado do mês anterior');
                $table->integer('saldo_acumulado')->default(0)->comment('Saldo total após fechamento do mês');
                $table->boolean('domingo_cumprido')->default(false)->comment('Pelo menos 1 folga no domingo no mês');
                $table->integer('dias_trabalhados')->default(0);
                $table->integer('dias_atestado')->default(0);
                $table->integer('dias_licenca')->default(0);
                $table->timestamps();

                $table->unique(['user_id', 'mes', 'ano']);
            });
        }

        // Trilha de auditoria completa
        if (! Schema::hasTable('folga_logs')) {
            Schema::create('folga_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('user_id')->index()->comment('Motorista afetado');
                $table->string('acao');
                $table->string('tabela')->nullable();
                $table->unsignedBigInteger('registro_id')->nullable();
                $table->json('dados_antes')->nullable();
                $table->json('dados_depois')->nullable();
                $table->text('observacao')->nullable();
                $table->unsignedBigInteger('created_by')->nullable()->index();
                $table->timestamps();

                $table->index(['user_id', 'created_at']);
                $table->index('acao');
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('folga_logs');
        Schema::dropIfExists('folga_saldos_mensais');
        Schema::dropIfExists('folga_movimentos');
        Schema::dropIfExists('folga_dias');
        Schema::dropIfExists('folga_settings');
    }
};
