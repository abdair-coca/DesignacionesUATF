<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private const INDICE_HEREDADO = 'grupos_materia_id_codigo_unique';

    public function up(): void
    {
        DB::statement('ALTER TABLE grupos DROP CONSTRAINT IF EXISTS '.self::INDICE_HEREDADO);
    }

    public function down(): void
    {
        $duplicados = DB::table('grupos')
            ->select(['materia_id', 'codigo'])
            ->selectRaw('array_agg(id ORDER BY id) AS ids')
            ->groupBy(['materia_id', 'codigo'])
            ->havingRaw('COUNT(*) > 1')
            ->orderBy('materia_id')
            ->orderBy('codigo')
            ->get();

        if ($duplicados->isNotEmpty()) {
            $detalles = $duplicados->map(fn (object $duplicado) => sprintf(
                'materia %d, codigo %s: grupos %s',
                $duplicado->materia_id,
                $duplicado->codigo,
                $duplicado->ids,
            ));

            throw new RuntimeException(
                'No se puede restaurar la unicidad heredada materia-codigo. '
                .implode('; ', $detalles->all()),
            );
        }

        DB::statement(
            'ALTER TABLE grupos ADD CONSTRAINT '.self::INDICE_HEREDADO.' UNIQUE (materia_id, codigo)',
        );
    }
};
