<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 30)->nullable()->after('password');
        });

        DB::table('users')->where('is_admin', true)->update(['rol' => 'vicerrectorado']);
        DB::table('users')->where('is_admin', false)->update(['rol' => 'director_carrera']);

        if (DB::table('users')->where('rol', 'director_carrera')->whereNull('carrera_id')->exists()) {
            throw new RuntimeException('No se puede migrar usuarios directores sin carrera asignada. Corrija los datos antes de continuar.');
        }

        if (DB::table('users')->where('rol', 'vicerrectorado')->whereNotNull('carrera_id')->exists()) {
            throw new RuntimeException('No se puede migrar usuarios de Vicerrectorado con carrera asignada. Corrija los datos antes de continuar.');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->string('rol', 30)->nullable(false)->change();
            $table->dropColumn('is_admin');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_check CHECK (rol IN ('director_carrera', 'vicerrectorado'))");
            DB::statement("ALTER TABLE users ADD CONSTRAINT users_rol_carrera_check CHECK ((rol = 'director_carrera' AND carrera_id IS NOT NULL) OR (rol = 'vicerrectorado' AND carrera_id IS NULL))");
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_carrera_check');
            DB::statement('ALTER TABLE users DROP CONSTRAINT IF EXISTS users_rol_check');
        }

        Schema::table('users', function (Blueprint $table) {
            $table->boolean('is_admin')->default(false)->after('password');
        });

        DB::table('users')->where('rol', 'vicerrectorado')->update(['is_admin' => true]);

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('rol');
        });
    }
};
