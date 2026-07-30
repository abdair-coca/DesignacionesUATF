<?php

namespace Tests\Feature;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\User;
use Tests\TestCase;

class DesignacionMasivaTest extends TestCase
{
    public function test_previsualizar_pegado_retorna_filas_ok_cuando_no_hay_conflictos(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $docente = Docente::factory()->create();
        $materia = Materia::factory()->create(['horas' => 4]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $usuario = User::factory()->director($grupo->mallaCurricular->carrera_id)->create();

        $response = $this->actingAs($usuario)
            ->postJson('/designaciones/previsualizar-pegado', [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'filas' => [
                    [
                        'Id_docente' => $docente->id,
                        'Id_materia' => $materia->id,
                        'Id_grupo' => $grupo->id,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'resumen' => [
                    'ok' => 1,
                    'saltadas' => 0,
                ],
                'resultados' => [
                    [
                        'idx' => 0,
                        'estado' => 'ok',
                        'motivo' => null,
                    ],
                ],
            ]);
    }

    public function test_previsualizar_pegado_detecta_grupo_ocupado(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $docenteA = Docente::factory()->create();
        $docenteB = Docente::factory()->create();
        $materia = Materia::factory()->create(['horas' => 4]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $usuario = User::factory()->director($grupo->mallaCurricular->carrera_id)->create();

        // Crear una designación activa para este grupo en el periodo destino
        Designacion::factory()->create([
            'Id_docente' => $docenteA->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
        ]);

        $response = $this->actingAs($usuario)
            ->postJson('/designaciones/previsualizar-pegado', [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'filas' => [
                    [
                        'Id_docente' => $docenteB->id,
                        'Id_materia' => $materia->id,
                        'Id_grupo' => $grupo->id,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'resumen' => [
                    'ok' => 0,
                    'saltadas' => 1,
                ],
                'resultados' => [
                    [
                        'idx' => 0,
                        'estado' => 'grupo_ocupado',
                    ],
                ],
            ]);
    }

    public function test_previsualizar_pegado_permite_mas_de_6_horas_sin_limite(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $docente = Docente::factory()->create();

        // Asignar 5 horas
        $materiaExistente = Materia::factory()->create(['horas' => 5]);
        $grupoExistente = Grupo::factory()->create(['materia_id' => $materiaExistente->id]);
        Designacion::factory()->create([
            'Id_docente' => $docente->id,
            'Id_materia' => $materiaExistente->id,
            'Id_grupo' => $grupoExistente->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'aprobada',
        ]);

        // Agregar otra materia de 4 horas (total seria 9h > 6h -> Permitido sin limite)
        $materiaNueva = Materia::factory()->create(['horas' => 4]);
        $grupoNuevo = Grupo::factory()->create(['materia_id' => $materiaNueva->id]);
        $usuario = User::factory()->director($grupoNuevo->mallaCurricular->carrera_id)->create();

        $response = $this->actingAs($usuario)
            ->postJson('/designaciones/previsualizar-pegado', [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'filas' => [
                    [
                        'Id_docente' => $docente->id,
                        'Id_materia' => $materiaNueva->id,
                        'Id_grupo' => $grupoNuevo->id,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'resumen' => [
                    'ok' => 1,
                    'saltadas' => 0,
                ],
                'resultados' => [
                    [
                        'idx' => 0,
                        'estado' => 'ok',
                    ],
                ],
            ]);
    }

    public function test_pegar_crea_designaciones_en_lote_con_estado_propuesta(): void
    {
        $gestion = Gestion::factory()->create();
        $periodo = Periodo::factory()->create();
        $docente = Docente::factory()->create();
        $materia = Materia::factory()->create(['horas' => 4]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $usuario = User::factory()->director($grupo->mallaCurricular->carrera_id)->create();

        $response = $this->actingAs($usuario)
            ->postJson('/designaciones/pegar', [
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
                'filas' => [
                    [
                        'Id_docente' => $docente->id,
                        'Id_materia' => $materia->id,
                        'Id_grupo' => $grupo->id,
                    ],
                ],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'creadas' => 1,
                'saltadas' => 0,
            ]);

        $this->assertDatabaseHas('designaciones', [
            'Id_docente' => $docente->id,
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'Id_gestion' => $gestion->id,
            'Id_periodo' => $periodo->id,
            'estado' => 'propuesta',
            'creado_por' => $usuario->id,
        ]);
    }

    public function test_deshacer_pegado_elimina_solo_propuestas(): void
    {
        $designacionPropuesta = Designacion::factory()->create(['estado' => 'propuesta']);
        $designacionAprobada = Designacion::factory()->create([
            'Id_materia' => $designacionPropuesta->Id_materia,
            'Id_grupo' => $designacionPropuesta->Id_grupo,
            'malla_curricular_id' => $designacionPropuesta->malla_curricular_id,
            'Id_gestion' => Gestion::factory()->create()->id,
            'Id_periodo' => Periodo::factory()->create()->id,
            'estado' => 'aprobada',
        ]);
        $usuario = User::factory()->director($designacionPropuesta->mallaCurricular->carrera_id)->create();

        $response = $this->actingAs($usuario)
            ->postJson('/designaciones/deshacer-pegado', [
                'ids' => [$designacionPropuesta->id, $designacionAprobada->id],
            ]);

        $response->assertStatus(200)
            ->assertJson([
                'eliminadas' => 1,
            ]);

        $this->assertDatabaseMissing('designaciones', ['id' => $designacionPropuesta->id]);
        $this->assertDatabaseHas('designaciones', ['id' => $designacionAprobada->id]);
    }
}
