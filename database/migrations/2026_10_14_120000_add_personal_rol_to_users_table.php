<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_carrera_check');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_check CHECK (rol IN ('director_carrera', 'decanatura', 'vicerrectorado', 'personal'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_carrera_check CHECK (
            (rol = 'director_carrera' AND carrera_id IS NOT NULL AND facultad_id IS NULL)
            OR (rol = 'decanatura' AND carrera_id IS NULL AND facultad_id IS NOT NULL)
            OR (rol = 'vicerrectorado' AND carrera_id IS NULL AND facultad_id IS NULL)
            OR (rol = 'personal' AND carrera_id IS NULL AND facultad_id IS NULL)
        )");
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return;
        }

        if (DB::table('users')->where('rol', 'personal')->exists()) {
            throw new RuntimeException('No se puede retirar el rol personal mientras existan usuarios de Personal.');
        }

        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_carrera_check');
        DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check');
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_check CHECK (rol IN ('director_carrera', 'decanatura', 'vicerrectorado'))");
        DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_carrera_check CHECK (
            (rol = 'director_carrera' AND carrera_id IS NOT NULL AND facultad_id IS NULL)
            OR (rol = 'decanatura' AND carrera_id IS NULL AND facultad_id IS NOT NULL)
            OR (rol = 'vicerrectorado' AND carrera_id IS NULL AND facultad_id IS NULL)
        )");
    }
};
