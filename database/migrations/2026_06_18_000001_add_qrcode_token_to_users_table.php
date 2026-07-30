<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use App\Models\User;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'qrcode_token')) {
            Schema::table('users', function (Blueprint $table) {
                $table->string('qrcode_token', 64)->nullable()->unique()->after('foto_perfil');
            });

            // Popular usuários existentes com tokens únicos
            User::all()->each(function ($user) {
                $user->update([
                    'qrcode_token' => Str::random(32)
                ]);
            });
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('qrcode_token');
        });
    }
};
