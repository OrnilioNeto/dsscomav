<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        if (!Schema::hasTable('training_logs')) {
            Schema::create('training_logs', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('training_id');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->string('evento', 50);
                $table->text('detalhe')->nullable();
                $table->string('ip', 45)->nullable();
                $table->string('user_agent', 500)->nullable();
                $table->timestamp('created_at')->nullable();

                $table->foreign('training_id')->references('id')->on('trainings')->onDelete('cascade');
                $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
                $table->index('training_id');
                $table->index('user_id');
                $table->index('evento');
            });
        }
    }

    public function down()
    {
        if (Schema::hasTable('training_logs')) {
            Schema::dropIfExists('training_logs');
        }
    }
};
