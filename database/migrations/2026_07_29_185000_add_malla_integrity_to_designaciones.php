<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDICE_ACTIVA = 'designaciones_grupo_gestion_periodo_activa_unique';

    private const REGISTRO_BACKFILL = 'designaciones_malla_backfill_registros';

    public function up(): void
    {
        Schema::table('designaciones', function (Blueprint $table) {
            $table->foreignId('malla_curricular_id')->nullable();
        });

        $designacionesSinMalla = DB::table('designaciones as designaciones')
            ->join('grupos as grupos', 'grupos.id', '=', 'designaciones.Id_grupo')
            ->whereNull('grupos.malla_curricular_id')
            ->orderBy('designaciones.id')
            ->pluck('designaciones.id');

        if ($designacionesSinMalla->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede migrar designaciones: grupos sin malla curricular. IDs: '
                .implode(', ', $designacionesSinMalla->all()),
            );
        }

        $designacionesIncompatibles = DB::table('designaciones as designaciones')
            ->join('grupos as grupos', 'grupos.id', '=', 'designaciones.Id_grupo')
            ->join('malla_curricular as mallas', 'mallas.id', '=', 'grupos.malla_curricular_id')
            ->whereColumn('designaciones.Id_materia', '!=', 'mallas.materia_id')
            ->orderBy('designaciones.id')
            ->pluck('designaciones.id');

        if ($designacionesIncompatibles->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede migrar designaciones: materia incompatible con grupo y malla. IDs: '
                .implode(', ', $designacionesIncompatibles->all()),
            );
        }

        $duplicadosActivos = DB::table('designaciones')
            ->where('estado', '!=', 'rechazada')
            ->select(['Id_grupo', 'Id_gestion', 'Id_periodo'])
            ->groupBy(['Id_grupo', 'Id_gestion', 'Id_periodo'])
            ->havingRaw('COUNT(*) > 1')
            ->get();

        if ($duplicadosActivos->isNotEmpty()) {
            $detalles = $duplicadosActivos->map(function (object $duplicado): string {
                $ids = DB::table('designaciones')
                    ->where('Id_grupo', $duplicado->Id_grupo)
                    ->where('Id_gestion', $duplicado->Id_gestion)
                    ->where('Id_periodo', $duplicado->Id_periodo)
                    ->where('estado', '!=', 'rechazada')
                    ->orderBy('id')
                    ->pluck('id');

                return sprintf(
                    'grupo %d, gestión %d, período %d: designaciones %s',
                    $duplicado->Id_grupo,
                    $duplicado->Id_gestion,
                    $duplicado->Id_periodo,
                    implode(', ', $ids->all()),
                );
            });

            throw new RuntimeException(
                'No se puede migrar designaciones: existen grupos activos duplicados. '
                .implode('; ', $detalles->all()),
            );
        }

        Schema::create(self::REGISTRO_BACKFILL, function (Blueprint $table) {
            $table->id();
            $table->foreignId('designacion_id')->unique()->constrained('designaciones')->cascadeOnDelete();
            $table->foreignId('malla_curricular_id_anterior')
                ->nullable()
                ->constrained('malla_curricular')
                ->nullOnDelete();
            $table->timestamps();
        });

        DB::transaction(function () {
            DB::table('designaciones as designaciones')
                ->join('grupos as grupos', 'grupos.id', '=', 'designaciones.Id_grupo')
                ->select([
                    'designaciones.id',
                    'designaciones.malla_curricular_id',
                    'grupos.malla_curricular_id as malla_curricular_grupo_id',
                ])
                ->orderBy('designaciones.id')
                ->eachById(function (object $designacion) {
                    $ahora = now();
                    DB::table(self::REGISTRO_BACKFILL)->insert([
                        'designacion_id' => $designacion->id,
                        'malla_curricular_id_anterior' => $designacion->malla_curricular_id,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);

                    DB::table('designaciones')
                        ->where('id', $designacion->id)
                        ->update([
                            'malla_curricular_id' => $designacion->malla_curricular_grupo_id,
                            'updated_at' => $ahora,
                        ]);
                }, 1000, 'designaciones.id', 'id');
        });

        DB::statement('ALTER TABLE designaciones ALTER COLUMN malla_curricular_id SET NOT NULL');
        DB::statement('ALTER TABLE malla_curricular ADD CONSTRAINT malla_curricular_id_materia_unique UNIQUE (id, materia_id)');
        DB::statement('ALTER TABLE grupos ADD CONSTRAINT grupos_id_malla_curricular_unique UNIQUE (id, malla_curricular_id)');
        DB::statement('ALTER TABLE designaciones DROP CONSTRAINT IF EXISTS designaciones_id_docente_id_materia_id_grupo_id_gestion_id_periodo_unique');
        DB::statement('ALTER TABLE designaciones ADD CONSTRAINT designaciones_grupo_malla_foreign FOREIGN KEY ("Id_grupo", malla_curricular_id) REFERENCES grupos (id, malla_curricular_id) ON DELETE RESTRICT');
        DB::statement('ALTER TABLE designaciones ADD CONSTRAINT designaciones_malla_materia_foreign FOREIGN KEY (malla_curricular_id, "Id_materia") REFERENCES malla_curricular (id, materia_id) ON DELETE RESTRICT');
        DB::statement('CREATE UNIQUE INDEX '.self::INDICE_ACTIVA.' ON designaciones ("Id_grupo", "Id_gestion", "Id_periodo") WHERE estado <> \'rechazada\'');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDICE_ACTIVA);
        DB::statement('ALTER TABLE designaciones DROP CONSTRAINT IF EXISTS designaciones_grupo_malla_foreign');
        DB::statement('ALTER TABLE designaciones DROP CONSTRAINT IF EXISTS designaciones_malla_materia_foreign');
        DB::statement('ALTER TABLE grupos DROP CONSTRAINT IF EXISTS grupos_id_malla_curricular_unique');
        DB::statement('ALTER TABLE malla_curricular DROP CONSTRAINT IF EXISTS malla_curricular_id_materia_unique');
        DB::statement('ALTER TABLE designaciones ALTER COLUMN malla_curricular_id DROP NOT NULL');

        DB::transaction(function () {
            DB::table(self::REGISTRO_BACKFILL)
                ->orderBy('id')
                ->eachById(function (object $registro) {
                    DB::table('designaciones')
                        ->where('id', $registro->designacion_id)
                        ->update([
                            'malla_curricular_id' => $registro->malla_curricular_id_anterior,
                            'updated_at' => now(),
                        ]);
                });
        });

        Schema::dropIfExists(self::REGISTRO_BACKFILL);
        Schema::table('designaciones', function (Blueprint $table) {
            $table->dropColumn('malla_curricular_id');
            $table->unique(['Id_docente', 'Id_materia', 'Id_grupo', 'Id_gestion', 'Id_periodo']);
        });
    }
};
