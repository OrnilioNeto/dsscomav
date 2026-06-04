<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRankingTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('ranking_settings', function (Blueprint $table) {
            $table->id();
            $table->boolean('is_active')->default(true);
            $table->string('default_period')->nullable();
            $table->timestamps();
        });

        Schema::create('ranking_criteria', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ranking_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('criterion_id')->constrained('ranking_criteria')->onDelete('cascade');
            $table->string('label');
            $table->double('min_value')->nullable();
            $table->double('max_value')->nullable();
            $table->integer('points')->default(0);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('ranking_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->unsignedBigInteger('training_id')->nullable()->index();
            $table->unsignedBigInteger('content_id')->nullable()->index();
            $table->tinyInteger('month_reference');
            $table->smallInteger('year_reference');
            $table->double('raw_score')->default(0);
            $table->double('normalized_score')->default(0);
            $table->timestamp('calculated_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'month_reference', 'year_reference'], 'ranking_user_period_idx');
        });

        Schema::create('ranking_monthly_scores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('month_reference');
            $table->smallInteger('year_reference');
            $table->double('average_score')->default(0);
            $table->integer('position')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'month_reference', 'year_reference'], 'ranking_monthly_unique_user_period');
            $table->index(['month_reference', 'year_reference'], 'ranking_month_idx');
        });

        Schema::create('ranking_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->tinyInteger('month_reference');
            $table->smallInteger('year_reference');
            $table->double('score')->default(0);
            $table->timestamp('created_at')->useCurrent();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('ranking_histories');
        Schema::dropIfExists('ranking_monthly_scores');
        Schema::dropIfExists('ranking_scores');
        Schema::dropIfExists('ranking_rules');
        Schema::dropIfExists('ranking_criteria');
        Schema::dropIfExists('ranking_settings');
    }
}
