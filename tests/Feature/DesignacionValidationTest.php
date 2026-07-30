<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use Tests\TestCase;

class DesignacionValidationTest extends TestCase
{
    public function test_director_no_puede_elegir_estado_aprobado_al_crear(): void
    {
        [$director, $materia, $grupo] = $this->contexto();

        $this->actingAs($director)
            ->post('/designaciones', $this->payload($materia, $grupo, ['estado' => 'aprobada']))
            ->assertRedirect('/designaciones');

        $this->assertDatabaseHas('designaciones', [
            'Id_grupo' => $grupo->id,
            'estado' => 'propuesta',
        ]);
    }

    public function test_director_no_puede_usar_grupo_de_otra_carrera(): void
    {
        [$director] = $this->contexto();
        $otraCarrera = Carrera::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $otraCarrera->id]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);

        $this->actingAs($director)
            ->post('/designaciones', $this->payload($materia, $grupo))
            ->assertForbidden();
    }

    public function test_director_no_puede_asignar_materia_distinta_a_la_del_grupo(): void
    {
        [$director, , $grupo] = $this->contexto();
        $materiaDistinta = Materia::factory()->create();

        $this->actingAs($director)
            ->post('/designaciones', $this->payload($materiaDistinta, $grupo))
            ->assertSessionHasErrors('Id_grupo');
    }

    public function test_director_no_puede_aprobar_mediante_payload_de_actualizacion(): void
    {
        [$director, $materia, $grupo] = $this->contexto();
        $designacion = Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'estado' => 'propuesta',
        ]);

        $this->actingAs($director)
            ->put("/designaciones/{$designacion->id}", $this->payload($materia, $grupo, ['estado' => 'aprobada']))
            ->assertRedirect('/designaciones');

        $this->assertDatabaseHas('designaciones', ['id' => $designacion->id, 'estado' => 'propuesta']);
    }

    private function contexto(): array
    {
        $carrera = Carrera::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);

        return [User::factory()->director($carrera)->create(), $materia, $grupo];
    }

    private function payload(Materia $materia, Grupo $grupo, array $extra = []): array
    {
        return array_merge([
            'Id_docente' => Docente::factory()->create()->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => Gestion::factory()->create()->id,
            'Id_periodo' => Periodo::factory()->create()->id,
        ], $extra);
    }
}
