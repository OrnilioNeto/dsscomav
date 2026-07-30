<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('role_permissions')) {
            return;
        }

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('role_id')->constrained('roles')->onDelete('cascade');
            $table->string('module');
            $table->boolean('can_view')->default(false);
            $table->boolean('can_edit')->default(false);
            $table->timestamps();

            // Garantir que um perfil tenha apenas uma definição por módulo
            $table->unique(['role_id', 'module']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('role_permissions');
    }
};
