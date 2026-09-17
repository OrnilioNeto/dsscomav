<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Adiciona a FK users.role_id -> roles.id de forma idempotente.
     *
     * A migration original de users (2014) criava a FK antes de a tabela roles
     * existir (2024), o que quebra `migrate` do zero em MySQL/MariaDB.
     */
    public function up(): void
    {
        if (! Schema::hasTable('users') || ! Schema::hasTable('roles') || ! Schema::hasColumn('users', 'role_id')) {
            return;
        }

        if (DB::connection()->getDriverName() === 'sqlite') {
            // SQLite não suporta adicionar FK após a criação da tabela;
            // em testes a integridade referencial é garantida pela aplicação.
            return;
        }

        if ($this->foreignKeyExists()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->foreign('role_id')->references('id')->on('roles')->nullOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite' || ! $this->foreignKeyExists()) {
            return;
        }

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['role_id']);
        });
    }

    private function foreignKeyExists(): bool
    {
        if (! Schema::hasTable('users')) {
            return false;
        }

        $driver = DB::connection()->getDriverName();

        if ($driver === 'sqlite') {
            foreach (DB::select("PRAGMA foreign_key_list('users')") as $fk) {
                if (($fk->from ?? null) === 'role_id' && ($fk->table ?? null) === 'roles') {
                    return true;
                }
            }

            return false;
        }

        if ($driver === 'mysql' || $driver === 'mariadb') {
            return DB::selectOne(
                "SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
                 WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'users'
                   AND CONSTRAINT_NAME = 'users_role_id_foreign'"
            ) !== null;
        }

        return DB::selectOne(
            "SELECT constraint_name FROM information_schema.table_constraints
             WHERE table_name = 'users' AND constraint_name = 'users_role_id_foreign'"
        ) !== null;
    }
};
