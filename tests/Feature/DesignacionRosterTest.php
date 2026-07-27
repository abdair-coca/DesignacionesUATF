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
    public function test_guardar_roster_crea_nuevas_designaciones(): void
    {
        $user = User::factory()->create();
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id, 'horas' => 4]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $docente = Docente::factory()->create();

        $this->actingAs($user)
            ->post("/designaciones/carrera/{$carrera->id}/guardar", [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'cambios' => [
                    [
                        'Id_grupo' => $grupo->id,
                        'Id_materia' => $materia->id,
                        'Id_docente' => $docente->id,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('designaciones', [
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
            'creado_por' => $user->id,
        ]);
    }

    public function test_guardar_roster_actualiza_designacion_existente(): void
    {
        $user = User::factory()->create();
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id, 'horas' => 4]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $docenteViejo = Docente::factory()->create();
        $docenteNuevo = Docente::factory()->create();

        $designacion = Designacion::factory()->create([
            'Id_docente' => $docenteViejo->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $this->actingAs($user)
            ->post("/designaciones/carrera/{$carrera->id}/guardar", [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'cambios' => [
                    [
                        'Id_grupo' => $grupo->id,
                        'Id_materia' => $materia->id,
                        'Id_docente' => $docenteNuevo->id,
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('designaciones', [
            'id' => $designacion->id,
            'Id_docente' => $docenteNuevo->id,
        ]);
    }

    public function test_guardar_roster_elimina_designacion_cuando_docente_id_vacio(): void
    {
        $user = User::factory()->create();
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id, 'horas' => 4]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $docente = Docente::factory()->create();

        $designacion = Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $this->actingAs($user)
            ->post("/designaciones/carrera/{$carrera->id}/guardar", [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'cambios' => [
                    [
                        'Id_grupo' => $grupo->id,
                        'Id_materia' => $materia->id,
                        'Id_docente' => '',
                    ],
                ],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('designaciones', ['id' => $designacion->id]);
    }

    public function test_guardar_roster_requiere_cambios_minimo_uno(): void
    {
        $user = User::factory()->create();
        $carrera = Carrera::factory()->create();
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();

        $this->actingAs($user)
            ->post("/designaciones/carrera/{$carrera->id}/guardar", [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'cambios' => [],
            ])
            ->assertSessionHasErrors('cambios');
    }

    public function test_guardar_roster_requiere_gestion_y_periodo(): void
    {
        $user = User::factory()->create();
        $carrera = Carrera::factory()->create();

        $this->actingAs($user)
            ->post("/designaciones/carrera/{$carrera->id}/guardar", [
                'cambios' => [
                    ['Id_grupo' => 1, 'Id_materia' => 1],
                ],
            ])
            ->assertSessionHasErrors(['Id_gestion', 'Id_periodo']);
    }
}
