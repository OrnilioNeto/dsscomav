<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('training_questions')) {
            Schema::create('training_questions', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('training_id');
                $table->text('pergunta');
                $table->json('opcoes');
                $table->unsignedTinyInteger('resposta_correta');
                $table->unsignedTinyInteger('ordem')->default(0);
                $table->timestamps();

                $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
                $table->index('training_id');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('training_questions')) {
            Schema::dropIfExists('training_questions');
        }
    }
};
