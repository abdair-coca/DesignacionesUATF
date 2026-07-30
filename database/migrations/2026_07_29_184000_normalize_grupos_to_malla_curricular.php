<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const INDICE_UNICO = 'grupos_malla_codigo_unique';

    private const REGISTRO_NORMALIZACION = 'grupos_normalizacion_registros';

    public function up(): void
    {
        $grupos = DB::table('grupos as grupos')
            ->join('materias as materias', 'materias.id', '=', 'grupos.materia_id')
            ->leftJoin('malla_curricular as mallas', function ($join) {
                $join->on('mallas.materia_id', '=', 'materias.id')
                    ->on('mallas.carrera_id', '=', 'materias.carrera_id');
            })
            ->select([
                'grupos.id',
                'grupos.codigo',
                'grupos.malla_curricular_id',
                'mallas.id as malla_curricular_propia_id',
            ])
            ->orderBy('grupos.id')
            ->get();

        $gruposSinMalla = $grupos
            ->filter(fn (object $grupo) => $grupo->malla_curricular_propia_id === null)
            ->pluck('id');

        if ($gruposSinMalla->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede migrar grupos: no tienen una malla propia determinable. IDs: '
                .implode(', ', $gruposSinMalla->all()),
            );
        }

        $gruposConMallaIncompatible = $grupos
            ->filter(fn (object $grupo) => $grupo->malla_curricular_id !== null
                && (int) $grupo->malla_curricular_id !== (int) $grupo->malla_curricular_propia_id)
            ->pluck('id');

        if ($gruposConMallaIncompatible->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede migrar grupos: la malla existente no coincide con su materia histórica. IDs: '
                .implode(', ', $gruposConMallaIncompatible->all()),
            );
        }

        $gruposNormalizados = $grupos->map(function (object $grupo): object {
            $grupo->codigo_normalizado = $this->normalizarCodigo($grupo->codigo, $grupo->id);

            return $grupo;
        });

        $colisiones = $gruposNormalizados
            ->groupBy(fn (object $grupo) => $grupo->malla_curricular_propia_id.'|'.$grupo->codigo_normalizado)
            ->filter(fn (Collection $coincidencias) => $coincidencias->count() > 1)
            ->map(function (Collection $coincidencias): string {
                $primero = $coincidencias->first();

                return sprintf(
                    'malla %d, código %s: grupos %s',
                    $primero->malla_curricular_propia_id,
                    $primero->codigo_normalizado,
                    implode(', ', $coincidencias->pluck('id')->all()),
                );
            });

        if ($colisiones->isNotEmpty()) {
            throw new RuntimeException(
                'No se puede migrar grupos: códigos que colisionan tras normalizar. '
                .implode('; ', $colisiones->all()),
            );
        }

        if (! Schema::hasTable(self::REGISTRO_NORMALIZACION)) {
            Schema::create(self::REGISTRO_NORMALIZACION, function (Blueprint $table) {
                $table->id();
                $table->foreignId('grupo_id')->unique()->constrained('grupos')->cascadeOnDelete();
                $table->foreignId('malla_curricular_id_anterior')
                    ->nullable()
                    ->constrained('malla_curricular')
                    ->nullOnDelete();
                $table->string('codigo_anterior');
                $table->timestamps();
            });
        }

        DB::transaction(function () use ($gruposNormalizados) {
            foreach ($gruposNormalizados as $grupo) {
                $mallaCambio = (int) $grupo->malla_curricular_id !== (int) $grupo->malla_curricular_propia_id;
                $codigoCambio = $grupo->codigo !== $grupo->codigo_normalizado;

                if (! $mallaCambio && ! $codigoCambio) {
                    continue;
                }

                $ahora = now();
                DB::table(self::REGISTRO_NORMALIZACION)->updateOrInsert(
                    ['grupo_id' => $grupo->id],
                    [
                        'malla_curricular_id_anterior' => $grupo->malla_curricular_id,
                        'codigo_anterior' => $grupo->codigo,
                        'created_at' => $ahora,
                        'updated_at' => $ahora,
                    ],
                );

                DB::table('grupos')
                    ->where('id', $grupo->id)
                    ->update([
                        'malla_curricular_id' => $grupo->malla_curricular_propia_id,
                        'codigo' => $grupo->codigo_normalizado,
                        'updated_at' => $ahora,
                    ]);
            }

            DB::statement('CREATE UNIQUE INDEX IF NOT EXISTS '.self::INDICE_UNICO.' ON grupos (malla_curricular_id, codigo)');
        });
    }

    public function down(): void
    {
        DB::statement('DROP INDEX IF EXISTS '.self::INDICE_UNICO);

        if (! Schema::hasTable(self::REGISTRO_NORMALIZACION)) {
            return;
        }

        DB::transaction(function () {
            DB::table(self::REGISTRO_NORMALIZACION)
                ->orderBy('id')
                ->eachById(function (object $registro) {
                    DB::table('grupos')
                        ->where('id', $registro->grupo_id)
                        ->update([
                            'malla_curricular_id' => $registro->malla_curricular_id_anterior,
                            'codigo' => $registro->codigo_anterior,
                            'updated_at' => now(),
                        ]);
                });
        });

        Schema::drop(self::REGISTRO_NORMALIZACION);
    }

    private function normalizarCodigo(string $codigo, int $grupoId): string
    {
        $codigo = trim($codigo);

        if (preg_match('/^[1-9][0-9]*$/', $codigo)) {
            return $codigo;
        }

        if (preg_match('/^[A-Za-z]$/', $codigo)) {
            return (string) (ord(strtoupper($codigo)) - ord('A') + 1);
        }

        throw new RuntimeException(
            "No se puede migrar grupos: código inválido en grupo {$grupoId}: {$codigo}.",
        );
    }
};
