<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('propuestas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('carrera_id')->constrained('carreras')->restrictOnDelete();
            $table->foreignId('gestion_id')->constrained('gestiones')->restrictOnDelete();
            $table->foreignId('periodo_id')->constrained('periodos')->restrictOnDelete();
            $table->foreignId('creado_por')->constrained('users')->restrictOnDelete();
            $table->string('descripcion', 255)->nullable();
            $table->string('estado', 30)->default('borrador');
            $table->timestamps();

            $table->unique(['carrera_id', 'gestion_id', 'periodo_id'], 'propuestas_carrera_gestion_periodo_unica');
        });

        Schema::create('propuesta_designaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_id')->constrained('propuestas')->restrictOnDelete();
            $table->foreignId('docente_id')->nullable()->constrained('docentes')->restrictOnDelete();
            $table->foreignId('materia_id')->constrained('materias')->restrictOnDelete();
            $table->foreignId('grupo_id')->constrained('grupos')->restrictOnDelete();
            $table->foreignId('malla_curricular_id')->constrained('malla_curricular')->restrictOnDelete();
            $table->string('estado', 30)->default('propuesta');
            $table->timestamps();

            $table->unique(['propuesta_id', 'grupo_id'], 'propuesta_designaciones_grupo_unica');
        });

        Schema::create('propuesta_versiones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_id')->constrained('propuestas')->restrictOnDelete();
            $table->unsignedInteger('numero');
            $table->string('estado', 30)->default('pendiente');
            $table->foreignId('enviado_por')->constrained('users')->restrictOnDelete();
            $table->timestamp('enviado_en');
            $table->foreignId('retirado_por')->nullable()->constrained('users')->restrictOnDelete();
            $table->timestamp('retirado_en')->nullable();
            $table->timestamps();

            $table->unique(['propuesta_id', 'numero'], 'propuesta_versiones_numero_unico');
        });

        Schema::create('propuesta_version_designaciones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_version_id')->constrained('propuesta_versiones')->restrictOnDelete();
            $table->unsignedBigInteger('docente_id')->nullable();
            $table->string('docente_nombre', 255)->nullable();
            $table->unsignedBigInteger('materia_id');
            $table->string('materia_sigla', 100);
            $table->string('materia_nombre', 255);
            $table->unsignedInteger('materia_horas');
            $table->unsignedBigInteger('carrera_id');
            $table->string('carrera_sigla', 100);
            $table->string('carrera_nombre', 255);
            $table->unsignedBigInteger('grupo_id');
            $table->string('grupo_codigo', 50);
            $table->unsignedBigInteger('malla_curricular_id');
            $table->unsignedBigInteger('gestion_id');
            $table->string('gestion_nombre', 100);
            $table->unsignedBigInteger('periodo_id');
            $table->string('periodo_nombre', 100);
            $table->string('estado', 30);
            $table->string('decision', 30)->nullable();
            $table->text('observacion')->nullable();
            $table->timestamps();

            $table->unique(['propuesta_version_id', 'grupo_id'], 'snapshot_version_grupo_unico');
        });

        Schema::create('propuesta_eventos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('propuesta_id')->constrained('propuestas')->restrictOnDelete();
            $table->foreignId('propuesta_version_id')->nullable()->constrained('propuesta_versiones')->restrictOnDelete();
            $table->foreignId('usuario_id')->constrained('users')->restrictOnDelete();
            $table->string('tipo', 50);
            $table->json('datos')->nullable();
            $table->timestamp('ocurrio_en');
        });

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement("CREATE UNIQUE INDEX propuesta_version_pendiente_unica ON propuesta_versiones (propuesta_id) WHERE estado = 'pendiente'");
            DB::statement("CREATE OR REPLACE FUNCTION impedir_mutacion_snapshot_propuesta() RETURNS trigger AS $$ BEGIN RAISE EXCEPTION 'Los snapshots de versiones son inmutables'; END; $$ LANGUAGE plpgsql");
            DB::statement('CREATE TRIGGER propuesta_version_designaciones_inmutables BEFORE UPDATE OR DELETE ON propuesta_version_designaciones FOR EACH ROW EXECUTE FUNCTION impedir_mutacion_snapshot_propuesta()');
        }
    }

    public function down(): void
    {
        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            DB::statement('DROP TRIGGER IF EXISTS propuesta_version_designaciones_inmutables ON propuesta_version_designaciones');
            DB::statement('DROP FUNCTION IF EXISTS impedir_mutacion_snapshot_propuesta()');
            DB::statement('DROP INDEX IF EXISTS propuesta_version_pendiente_unica');
        }

        Schema::dropIfExists('propuesta_eventos');
        Schema::dropIfExists('propuesta_version_designaciones');
        Schema::dropIfExists('propuesta_versiones');
        Schema::dropIfExists('propuesta_designaciones');
        Schema::dropIfExists('propuestas');
    }
};
