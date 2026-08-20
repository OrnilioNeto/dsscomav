<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('projeto_pedagogico_trainings')) {
            Schema::create('projeto_pedagogico_trainings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('projeto_pedagogico_id');
                $table->unsignedBigInteger('training_id');
                $table->timestamps();

                $table->foreign('projeto_pedagogico_id')->references('id')->on('training_projetos_pedagogicos')->onDelete('cascade');
                $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
                $table->unique('training_id'); // um treinamento só pode pertencer a um único PP
                $table->unique(['projeto_pedagogico_id', 'training_id']);
                $table->index('training_id');
            });
        }

        // Migra os vínculos legados (training_id único da tabela de PP) para o pivot,
        // mantendo a compatibilidade com os registros já cadastrados.
        if (Schema::hasTable('training_projetos_pedagogicos') && Schema::hasColumn('training_projetos_pedagogicos', 'training_id')) {
            $legados = DB::table('training_projetos_pedagogicos')
                ->whereNotNull('training_id')
                ->get(['id', 'training_id']);

            foreach ($legados as $legado) {
                $existe = DB::table('projeto_pedagogico_trainings')
                    ->where('training_id', $legado->training_id)
                    ->exists();

                if (!$existe) {
                    DB::table('projeto_pedagogico_trainings')->insert([
                        'projeto_pedagogico_id' => $legado->id,
                        'training_id' => $legado->training_id,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }
            }
        }
    }

    public function down()
    {
        if (Schema::hasTable('projeto_pedagogico_trainings')) {
            Schema::dropIfExists('projeto_pedagogico_trainings');
        }
    }
};