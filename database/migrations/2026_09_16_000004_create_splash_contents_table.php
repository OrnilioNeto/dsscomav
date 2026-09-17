<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Conteúdos de splash (avisos exibidos após login).
     * Antes criada apenas em runtime pelo SplashContentController (web e API).
     */
    public function up(): void
    {
        if (! Schema::hasTable('splash_contents')) {
            Schema::create('splash_contents', function (Blueprint $table) {
                $table->id();
                $table->string('titulo');
                $table->text('texto_conteudo')->nullable();
                $table->string('material_path')->nullable();
                $table->string('material_tipo')->nullable(); // imagem, pdf
                $table->date('data_inicio');
                $table->date('data_fim');
                $table->string('status')->default('ativo'); // ativo, inativo
                $table->integer('ordem')->default(0);
                $table->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('splash_contents');
    }
};
