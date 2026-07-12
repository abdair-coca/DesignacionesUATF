<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->dropForeign('materias_carrera_id_foreign');
            $table->foreign('carrera_id')->references('id')->on('carreras')->restrictOnDelete();
        });

        Schema::table('grupos', function (Blueprint $table) {
            $table->dropForeign('grupos_materia_id_foreign');
            $table->foreign('materia_id')->references('id')->on('materias')->restrictOnDelete();
        });

        Schema::table('designaciones', function (Blueprint $table) {
            $table->dropForeign('designaciones_id_docente_foreign');
            $table->dropForeign('designaciones_id_materia_foreign');
            $table->dropForeign('designaciones_id_grupo_foreign');
            $table->dropForeign('designaciones_id_gestion_foreign');
            $table->dropForeign('designaciones_id_periodo_foreign');

            $table->foreign('Id_docente')->references('id')->on('docentes')->restrictOnDelete();
            $table->foreign('Id_materia')->references('id')->on('materias')->restrictOnDelete();
            $table->foreign('Id_grupo')->references('id')->on('grupos')->restrictOnDelete();
            $table->foreign('Id_gestion')->references('id')->on('gestiones')->restrictOnDelete();
            $table->foreign('Id_periodo')->references('id')->on('periodos')->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('materias', function (Blueprint $table) {
            $table->dropForeign('materias_carrera_id_foreign');
            $table->foreign('carrera_id')->references('id')->on('carreras')->cascadeOnDelete();
        });

        Schema::table('grupos', function (Blueprint $table) {
            $table->dropForeign('grupos_materia_id_foreign');
            $table->foreign('materia_id')->references('id')->on('materias')->cascadeOnDelete();
        });

        Schema::table('designaciones', function (Blueprint $table) {
            $table->dropForeign('designaciones_id_docente_foreign');
            $table->dropForeign('designaciones_id_materia_foreign');
            $table->dropForeign('designaciones_id_grupo_foreign');
            $table->dropForeign('designaciones_id_gestion_foreign');
            $table->dropForeign('designaciones_id_periodo_foreign');

            $table->foreign('Id_docente')->references('id')->on('docentes')->cascadeOnDelete();
            $table->foreign('Id_materia')->references('id')->on('materias')->cascadeOnDelete();
            $table->foreign('Id_grupo')->references('id')->on('grupos')->cascadeOnDelete();
            $table->foreign('Id_gestion')->references('id')->on('gestiones')->cascadeOnDelete();
            $table->foreign('Id_periodo')->references('id')->on('periodos')->cascadeOnDelete();
        });
    }
};
