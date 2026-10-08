<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->integer('facultad_id')->nullable()->after('carrera_id');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_carrera_check');
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_check CHECK (rol IN ('director_carrera', 'decanatura', 'vicerrectorado'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_carrera_check CHECK (
                (rol = 'director_carrera' AND carrera_id IS NOT NULL AND facultad_id IS NULL)
                OR (rol = 'decanatura' AND carrera_id IS NULL AND facultad_id IS NOT NULL)
                OR (rol = 'vicerrectorado' AND carrera_id IS NULL AND facultad_id IS NULL)
            )");
        }
    }

    public function down(): void
    {
        if (DB::table('users')->where('rol', 'decanatura')->exists()) {
            throw new RuntimeException('No se puede retirar el alcance de facultad mientras existan usuarios de Decanatura.');
        }

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_carrera_check');
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check');
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_check CHECK (rol IN ('director_carrera', 'vicerrectorado'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_carrera_check CHECK (
                (rol = 'director_carrera' AND carrera_id IS NOT NULL)
                OR (rol = 'vicerrectorado' AND carrera_id IS NULL)
            )");
        }

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn('facultad_id');
        });
    }
};
