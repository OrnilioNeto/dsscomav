<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const TIPOS = ['motorista', 'funcionario', 'terceirizado', 'motorista_monitor'];

    /**
     * Adiciona o tipo "motorista_monitor" ao enum users.tipo_usuario.
     *
     * - MySQL/MariaDB: ALTER do ENUM.
     * - PostgreSQL: recria o CHECK constraint gerado pelo enum do Laravel.
     * - SQLite: banco de testes recriado do zero já nasce com o valor (a
     *   migration original foi atualizada); bancos antigos precisam de
     *   migrate:fresh, pois o SQLite não altera CHECK constraints.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'tipo_usuario')) {
            return;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        $tipos = "'".implode("','", self::TIPOS)."'";

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE users MODIFY tipo_usuario ENUM({$tipos}) NOT NULL DEFAULT 'motorista'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_tipo_usuario_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_tipo_usuario_check CHECK (tipo_usuario IN ({$tipos}))");
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasColumn('users', 'tipo_usuario')) {
            return;
        }

        // Converte os registros do novo tipo antes de restaurar a constraint antiga.
        DB::table('users')->where('tipo_usuario', 'motorista_monitor')->update(['tipo_usuario' => 'motorista']);

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            return;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            DB::statement("ALTER TABLE users MODIFY tipo_usuario ENUM('motorista','funcionario','terceirizado') NOT NULL DEFAULT 'motorista'");

            return;
        }

        if ($driver === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_tipo_usuario_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_tipo_usuario_check CHECK (tipo_usuario IN ('motorista','funcionario','terceirizado'))");
        }
    }
};
