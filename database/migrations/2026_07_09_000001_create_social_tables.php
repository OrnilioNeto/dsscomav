<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Tabela de Postagens (Social Posts)
        Schema::create('social_posts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('photo_path')->nullable();
            $table->text('caption')->nullable();
            $table->string('location')->nullable();
            
            // Associação opcional com Treinamento (para posts de conquista)
            $table->foreignId('training_id')->nullable()->constrained('trainings')->onDelete('set null');
            $table->double('training_score')->nullable();
            $table->integer('ranking_position')->nullable();
            
            $table->timestamps();
        });

        // 2. Tabela de Curtidas (Social Likes)
        Schema::create('social_likes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('post_id')->constrained('social_posts')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['user_id', 'post_id']);
        });

        // 3. Tabela de Seguidores (Social Follows)
        Schema::create('social_follows', function (Blueprint $table) {
            $table->id();
            $table->foreignId('follower_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('following_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();

            $table->unique(['follower_id', 'following_id']);
        });

        // 4. Tabela de Comentários (Social Comments)
        Schema::create('social_comments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->foreignId('post_id')->constrained('social_posts')->onDelete('cascade');
            $table->text('content');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_comments');
        Schema::dropIfExists('social_follows');
        Schema::dropIfExists('social_likes');
        Schema::dropIfExists('social_posts');
    }
};
