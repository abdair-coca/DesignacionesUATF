<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Grupo;
use App\Models\MallaCurricular;
use App\Models\Materia;
use RuntimeException;
use Tests\TestCase;

class GrupoNormalizationTest extends TestCase
{
    public function test_normaliza_grupos_a_malla_y_codigos_numericos(): void
    {
        [$materia, $malla] = $this->crearMateriaConMalla();
        $grupoAlfabetico = Grupo::factory()->create(['materia_id' => $materia->id, 'codigo' => 'A']);
        $grupoNumerico = Grupo::factory()->create(['materia_id' => $materia->id, 'codigo' => '7']);

        $this->ejecutarNormalizacion();

        $this->assertDatabaseHas('grupos', [
            'id' => $grupoAlfabetico->id,
            'malla_curricular_id' => $malla->id,
            'codigo' => '1',
        ]);
        $this->assertDatabaseHas('grupos', [
            'id' => $grupoNumerico->id,
            'malla_curricular_id' => $malla->id,
            'codigo' => '7',
        ]);
    }

    public function test_detecta_colisiones_despues_de_normalizar_codigos(): void
    {
        [$materia] = $this->crearMateriaConMalla();
        $grupoAlfabetico = Grupo::factory()->create(['materia_id' => $materia->id, 'codigo' => 'A']);
        $grupoNumerico = Grupo::factory()->create(['materia_id' => $materia->id, 'codigo' => '1']);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage("grupos {$grupoAlfabetico->id}, {$grupoNumerico->id}");

        $this->ejecutarNormalizacion();
    }

    public function test_reversion_restaura_codigo_y_malla_anterior(): void
    {
        [$materia] = $this->crearMateriaConMalla();
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id, 'codigo' => 'B']);
        $migration = $this->migration();

        $migration->up();
        $migration->down();

        $this->assertDatabaseHas('grupos', [
            'id' => $grupo->id,
            'malla_curricular_id' => null,
            'codigo' => 'B',
        ]);
    }

    /**
     * @return array{Materia, MallaCurricular}
     */
    private function crearMateriaConMalla(): array
    {
        $carrera = Carrera::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $malla = MallaCurricular::create([
            'carrera_id' => $carrera->id,
            'materia_id' => $materia->id,
        ]);

        return [$materia, $malla];
    }

    private function ejecutarNormalizacion(): void
    {
        $this->migration()->up();
    }

    private function migration(): object
    {
        return require database_path('migrations/2026_07_29_184000_normalize_grupos_to_malla_curricular.php');
    }
}
