<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tabela de clientes (tenants) e módulos liberados por cliente.
     */
    public function up(): void
    {
        if (! Schema::hasTable('tenants')) {
            Schema::create('tenants', function (Blueprint $table) {
                $table->id();
                $table->string('nome');
                $table->string('slug')->unique();
                $table->string('dominio')->nullable()->unique();
                $table->string('status')->default('ativo'); // ativo, trial, suspenso, cancelado
                $table->string('plano')->nullable();
                $table->string('nome_exibicao')->nullable();
                $table->string('logo')->nullable();
                $table->string('logo_certificado')->nullable();
                $table->string('cor_primaria', 20)->nullable();
                $table->string('cor_secundaria', 20)->nullable();
                $table->string('email_remetente')->nullable();
                $table->timestamps();
                $table->softDeletes();
            });
        }

        if (! Schema::hasTable('tenant_modules')) {
            Schema::create('tenant_modules', function (Blueprint $table) {
                $table->id();
                $table->foreignId('tenant_id')->constrained('tenants')->cascadeOnDelete();
                $table->string('module');
                $table->boolean('enabled')->default(true);
                $table->timestamp('enabled_at')->nullable();
                $table->timestamp('expires_at')->nullable();
                $table->timestamps();

                $table->unique(['tenant_id', 'module']);
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('tenant_modules');
        Schema::dropIfExists('tenants');
    }
};
