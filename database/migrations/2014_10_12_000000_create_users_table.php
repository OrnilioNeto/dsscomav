<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('nome');
            $table->string('cpf')->unique();
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->string('telefone')->nullable();
            $table->date('data_nascimento')->nullable();
            $table->enum('tipo_usuario', ['motorista', 'funcionario', 'terceirizado'])->default('motorista');
            $table->enum('status', ['ativo', 'inativo'])->default('ativo');
            // FK para roles é adicionada em 2026_09_16_000001_add_role_id_foreign_to_users_table
            // (a tabela roles é criada depois, em 2024_01_01_000001).
            $table->unsignedBigInteger('role_id')->nullable();
            $table->string('cnh')->nullable();
            $table->string('categoria_cnh')->nullable();
            $table->date('validade_cnh')->nullable();
            $table->string('setor')->nullable();
            $table->string('cargo')->nullable();
            $table->string('empresa')->nullable();
            $table->string('responsavel')->nullable();
            $table->rememberToken();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};