<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('frota_veiculos')) {
            Schema::create('frota_veiculos', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('code')->nullable();
                $table->string('name')->nullable();
                $table->string('address')->nullable();
                $table->string('license_plate')->nullable();
                $table->string('trip_name')->nullable();
                $table->boolean('ativo')->default(true);
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'code'], 'frota_veiculos_tenant_code_unique');
                $table->index(['tenant_id', 'license_plate']);
            });
        }

        if (! Schema::hasTable('frota_motoristas')) {
            Schema::create('frota_motoristas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('code')->nullable();
                $table->string('name')->nullable();
                $table->string('cpf', 20)->nullable()->index();
                $table->unsignedBigInteger('user_id')->nullable()->index();

                // desconhecido | online | offline
                $table->string('status', 20)->default('desconhecido');
                $table->timestamp('verificado_em')->nullable();
                $table->timestamp('ultimo_login_em')->nullable();
                $table->timestamp('ultimo_logout_em')->nullable();
                $table->timestamp('ultimo_online_em')->nullable();
                $table->unsignedBigInteger('veiculo_atual_id')->nullable();
                $table->text('ultimo_erro')->nullable();

                $table->boolean('ativo')->default(true);
                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'code'], 'frota_motoristas_tenant_code_unique');
                $table->index(['tenant_id', 'cpf']);
            });
        }

        if (! Schema::hasTable('frota_jornadas')) {
            Schema::create('frota_jornadas', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('tenant_id')->nullable()->index();
                $table->unsignedBigInteger('frota_veiculo_id')->nullable()->index();
                $table->unsignedBigInteger('frota_motorista_id')->nullable()->index();

                $table->string('driver_name')->nullable();
                $table->string('driver_cpf', 20)->nullable();
                $table->dateTime('login_time');
                $table->dateTime('logout_time')->nullable();
                $table->boolean('aberta')->default(true);
                $table->boolean('excess_journey')->default(false);
                $table->boolean('excess_extended_journey')->default(false);

                $table->timestamp('synced_at')->nullable();
                $table->timestamps();

                $table->unique(
                    ['tenant_id', 'frota_veiculo_id', 'driver_cpf', 'login_time'],
                    'frota_jornadas_unica'
                );
                $table->index(['tenant_id', 'aberta', 'login_time']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('frota_jornadas');
        Schema::dropIfExists('frota_motoristas');
        Schema::dropIfExists('frota_veiculos');
    }
};
