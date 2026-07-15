<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Unique constraints — previenen datos duplicados
        Schema::table('carreras', function (Blueprint $table) {
            $table->unique('sigla');
        });

        Schema::table('gestiones', function (Blueprint $table) {
            $table->unique('nombre');
        });

        Schema::table('periodos', function (Blueprint $table) {
            $table->unique('nombre');
        });

        Schema::table('malla_curricular', function (Blueprint $table) {
            $table->unique(['carrera_id', 'materia_id']);
        });

        Schema::table('grupos', function (Blueprint $table) {
            $table->unique(['materia_id', 'codigo']);
        });

        Schema::table('designaciones', function (Blueprint $table) {
            $table->unique(['Id_docente', 'Id_materia', 'Id_grupo', 'Id_gestion', 'Id_periodo']);
        });

        // FK constraints — integridad referencial contra users
        Schema::table('designaciones', function (Blueprint $table) {
            $table->foreign('creado_por')->references('id')->on('users')->nullOnDelete();
            $table->foreign('aprobado_por')->references('id')->on('users')->nullOnDelete();
        });

        Schema::table('designaciones_historial', function (Blueprint $table) {
            $table->foreign('usuario_id')->references('id')->on('users')->nullOnDelete();
        });

        // Composite index — acelera queries de CargaAcademicaService
        Schema::table('designaciones', function (Blueprint $table) {
            $table->index(['Id_gestion', 'Id_periodo', 'estado']);
        });
    }

    public function down(): void
    {
        Schema::table('designaciones_historial', function (Blueprint $table) {
            $table->dropForeign(['usuario_id']);
        });

        Schema::table('designaciones', function (Blueprint $table) {
            $table->dropForeign(['creado_por']);
            $table->dropForeign(['aprobado_por']);
            $table->dropIndex(['Id_gestion', 'Id_periodo', 'estado']);
            $table->dropUnique(['Id_docente', 'Id_materia', 'Id_grupo', 'Id_gestion', 'Id_periodo']);
        });

        Schema::table('grupos', function (Blueprint $table) {
            $table->dropUnique(['materia_id', 'codigo']);
        });

        Schema::table('malla_curricular', function (Blueprint $table) {
            $table->dropUnique(['carrera_id', 'materia_id']);
        });

        Schema::table('periodos', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });

        Schema::table('gestiones', function (Blueprint $table) {
            $table->dropUnique(['nombre']);
        });

        Schema::table('carreras', function (Blueprint $table) {
            $table->dropUnique(['sigla']);
        });
    }
};
