<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cronograma_conteudos', function (Blueprint $table) {
            $table->boolean('pptx_ok')->default(false)->after('observacoes');
            $table->boolean('pdf_ok')->default(false)->after('pptx_ok');
            $table->boolean('video_ok')->default(false)->after('pdf_ok');
            $table->boolean('youtube_ok')->default(false)->after('video_ok');
        });
    }

    public function down(): void
    {
        Schema::table('cronograma_conteudos', function (Blueprint $table) {
            $table->dropColumn(['pptx_ok', 'pdf_ok', 'video_ok', 'youtube_ok']);
        });
    }
};