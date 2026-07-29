<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\MallaCurricular;
use App\Models\Materia;
use Tests\TestCase;

class MallaCurricularBackfillTest extends TestCase
{
    public function test_backfill_crea_y_registra_la_malla_propia_faltante(): void
    {
        $carrera = Carrera::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);

        $this->ejecutarBackfill();

        $malla = MallaCurricular::query()
            ->where('carrera_id', $carrera->id)
            ->where('materia_id', $materia->id)
            ->firstOrFail();

        $this->assertDatabaseHas('malla_curricular_backfill_registros', [
            'malla_curricular_id' => $malla->id,
        ]);
    }

    public function test_backfill_conserva_mallas_historicas_ya_existentes(): void
    {
        $carreraOrigen = Carrera::factory()->create();
        $carreraDestino = Carrera::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carreraOrigen->id]);

        MallaCurricular::create([
            'carrera_id' => $carreraDestino->id,
            'materia_id' => $materia->id,
        ]);

        $this->ejecutarBackfill();

        $this->assertSame(2, MallaCurricular::query()
            ->where('materia_id', $materia->id)
            ->count());
    }

    private function ejecutarBackfill(): void
    {
        $migration = require database_path('migrations/2026_07_29_183000_backfill_malla_curricular_from_materias.php');

        $migration->up();
    }
}
