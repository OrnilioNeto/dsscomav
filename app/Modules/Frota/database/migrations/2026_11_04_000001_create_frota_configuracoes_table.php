<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('frota_configuracoes')) {
            return;
        }

        Schema::create('frota_configuracoes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id')->nullable()->index();

            $table->boolean('ativo')->default(false);
            $table->string('base_url')->default('https://aapi3.autotrac-online.com.br/aticapi');
            $table->string('account_code')->nullable();
            $table->text('subscription_key')->nullable();
            $table->string('usuario')->nullable();
            $table->text('senha')->nullable();

            // Regras de presença / acesso
            $table->unsignedInteger('ttl_segundos')->default(60);
            $table->unsignedInteger('grace_minutos')->default(30);
            $table->unsignedInteger('jornada_maxima_horas')->default(12);
            $table->json('tipos_motorista')->nullable();
            $table->string('fail_mode')->default('grace');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('frota_configuracoes');
    }
};
