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

class DesignacionRosterTest extends TestCase
{
    public function test_director_guarda_designacion_en_su_carrera(): void
    {
        [$carrera, $materia, $grupo, $director] = $this->contextoDirector();
        $docente = Docente::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs($director)
            ->postJson(route('designaciones.carrera.guardar', $carrera), [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'cambios' => [[
                    'Id_grupo' => $grupo->id,
                    'Id_materia' => $materia->id,
                    'Id_docente' => $docente->id,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('designaciones', [
            'Id_grupo' => $grupo->id,
            'Id_docente' => $docente->id,
            'creado_por' => $director->id,
        ]);
    }

    public function test_director_no_puede_guardar_roster_de_otra_carrera(): void
    {
        [, $materia, $grupo, $director] = $this->contextoDirector();
        $otraCarrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs($director)
            ->postJson(route('designaciones.carrera.guardar', $otraCarrera), [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'cambios' => [[
                    'Id_grupo' => $grupo->id,
                    'Id_materia' => $materia->id,
                    'Id_docente' => Docente::factory()->create()->id,
                ]],
            ])
            ->assertForbidden();
    }

    public function test_director_no_puede_enviar_grupo_de_otra_carrera_en_el_payload(): void
    {
        [$carrera, , , $director] = $this->contextoDirector();
        $otraCarrera = Carrera::factory()->create();
        $materiaExterna = Materia::factory()->create(['carrera_id' => $otraCarrera->id]);
        $grupoExterno = Grupo::factory()->create(['materia_id' => $materiaExterna->id]);

        $this->actingAs($director)
            ->postJson(route('designaciones.carrera.guardar', $carrera), [
                'Id_gestion' => Gestion::factory()->create()->id,
                'Id_periodo' => Periodo::factory()->create()->id,
                'cambios' => [[
                    'Id_grupo' => $grupoExterno->id,
                    'Id_materia' => $materiaExterna->id,
                    'Id_docente' => Docente::factory()->create()->id,
                ]],
            ])
            ->assertForbidden();
    }

    public function test_director_puede_quitar_docente_de_su_propuesta(): void
    {
        [$carrera, $materia, $grupo, $director] = $this->contextoDirector();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $designacion = Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $this->actingAs($director)
            ->postJson(route('designaciones.carrera.guardar', $carrera), [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'cambios' => [[
                    'Id_grupo' => $grupo->id,
                    'Id_materia' => $materia->id,
                    'Id_docente' => null,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseMissing('designaciones', ['id' => $designacion->id]);
    }

    private function contextoDirector(): array
    {
        $carrera = Carrera::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);

        return [$carrera, $materia, $grupo, User::factory()->director($carrera)->create()];
    }
}
