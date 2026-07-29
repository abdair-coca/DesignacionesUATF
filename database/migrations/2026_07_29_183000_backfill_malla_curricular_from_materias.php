<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const REGISTRO_BACKFILL = 'malla_curricular_backfill_registros';

    public function up(): void
    {
        $materiasSinCarrera = DB::table('materias as materias')
            ->leftJoin('carreras as carreras', 'carreras.id', '=', 'materias.carrera_id')
            ->where(function ($query) {
                $query->whereNull('materias.carrera_id')
                    ->orWhereNull('carreras.id');
            })
            ->orderBy('materias.id')
            ->pluck('materias.id');

        if ($materiasSinCarrera->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede migrar malla_curricular: materias sin carrera determinable. IDs: '
                .implode(', ', $materiasSinCarrera->all()),
            );
        }

        if (! Schema::hasTable(self::REGISTRO_BACKFILL)) {
            Schema::create(self::REGISTRO_BACKFILL, function (Blueprint $table) {
                $table->id();
                $table->foreignId('malla_curricular_id')
                    ->unique()
                    ->constrained('malla_curricular')
                    ->cascadeOnDelete();
                $table->timestamps();
            });
        }

        DB::transaction(function () {
            DB::table('materias')
                ->select(['id', 'carrera_id'])
                ->orderBy('id')
                ->eachById(function (object $materia) {
                    $existeMalla = DB::table('malla_curricular')
                        ->where('carrera_id', $materia->carrera_id)
                        ->where('materia_id', $materia->id)
                        ->exists();

                    if ($existeMalla) {
                        return;
                    }

                    $ahora = now();
                    $mallaId = DB::table('malla_curricular')->insertGetId([
                        'carrera_id' => $materia->carrera_id,
                        'materia_id' => $materia->id,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);

                    DB::table(self::REGISTRO_BACKFILL)->insert([
                        'malla_curricular_id' => $mallaId,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ]);
                });
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable(self::REGISTRO_BACKFILL)) {
            return;
        }

        $mallasCreadas = DB::table(self::REGISTRO_BACKFILL)->pluck('malla_curricular_id');

        if ($mallasCreadas->isNotEmpty()) {
            DB::table('malla_curricular')
                ->whereIn('id', $mallasCreadas)
                ->delete();
        }

        Schema::drop(self::REGISTRO_BACKFILL);
    }
};
