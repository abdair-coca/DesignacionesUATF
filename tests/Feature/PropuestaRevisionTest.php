<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\PropuestaDesignacion;
use App\Models\PropuestaVersion;
use App\Models\PropuestaVersionDecision;
use App\Models\User;
use Tests\TestCase;

class PropuestaRevisionTest extends TestCase
{
    public function test_vicerrectorado_ve_versiones_pendientes_y_director_no_puede_abrir_bandeja(): void
    {
        [$director, $vicerrectorado, $propuesta, $version] = $this->propuestaEnviada(1);

        $this->actingAs($vicerrectorado)
            ->get('/revisiones/pendientes')
            ->assertOk()
            ->assertSee($propuesta->carrera->nombre)
            ->assertSee($propuesta->descripcion);

        $this->actingAs($vicerrectorado)
            ->get("/revisiones/{$version->id}/revisar")
            ->assertOk()
            ->assertSee($propuesta->descripcion)
            ->assertSee('Aprobar todas las filas')
            ->assertSee('Confirmar Revisión')
            ->assertSee('modo_revision')
            ->assertSee('decidir_filas')
            ->assertDontSee('Registrar decisiones por fila');

        $this->actingAs($director)
            ->get('/revisiones/pendientes')
            ->assertForbidden();
    }

    public function test_decisiones_por_fila_observan_el_mismo_borrador_y_bloquean_las_aprobadas(): void
    {
        [$director, $vicerrectorado, $propuesta, $version, $filas] = $this->propuestaEnviada(2);
        [$filaObservada, $filaAprobada] = $filas;

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$version->id}/decidir", [
                'modo' => 'decidir_filas',
                'observacion_general' => 'Corregir la primera asignación.',
                'decisiones' => [
                    ['snapshot_id' => $filaObservada->id, 'decision' => 'observada', 'observacion' => 'Cambiar docente.'],
                    ['snapshot_id' => $filaAprobada->id, 'decision' => 'aprobada'],
                ],
            ])
            ->assertRedirect('/revisiones/pendientes');

        $this->assertDatabaseHas('propuesta_versiones', [
            'id' => $version->id,
            'estado' => 'observada',
            'observaciones' => 'Corregir la primera asignación.',
            'revisado_por' => $vicerrectorado->id,
        ]);
        $this->assertDatabaseHas('propuesta_version_decisiones', [
            'propuesta_version_designacion_id' => $filaObservada->id,
            'decision' => 'observada',
            'observacion' => 'Cambiar docente.',
        ]);
        $this->assertDatabaseHas('propuesta_version_decisiones', [
            'propuesta_version_designacion_id' => $filaAprobada->id,
            'decision' => 'aprobada',
        ]);

        $aprobadaEnBorrador = PropuestaDesignacion::where('propuesta_id', $propuesta->id)->where('grupo_id', $filaAprobada->grupo_id)->firstOrFail();
        $this->assertSame('aprobada_previamente', $aprobadaEnBorrador->estado);

        $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}")
            ->assertOk()
            ->assertSee('Corregir la primera asignación.')
            ->assertSee('Cambiar docente.')
            ->assertSee('Aprobada previamente')
            ->assertSee('return asignadoAlActual || !ocupadoPorOtro;');

        $this->actingAs($director)
            ->put("/designaciones/{$propuesta->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $filaAprobada->grupo_id,
                    'materia_id' => $filaAprobada->materia_id,
                    'docente_id' => Docente::factory()->create()->id,
                ]],
            ])
            ->assertSessionHasErrors('cambios');

        $docenteCorregido = Docente::factory()->create(['nombre' => 'Docente corregido']);
        $this->actingAs($director)
            ->put("/designaciones/{$propuesta->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $filaObservada->grupo_id,
                    'materia_id' => $filaObservada->materia_id,
                    'docente_id' => $docenteCorregido->id,
                ]],
            ])
            ->assertRedirect();

        $respuestaEditada = $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}");

        $respuestaEditada->assertOk();
        $contenidoEditado = html_entity_decode(html_entity_decode($respuestaEditada->getContent()));
        $this->assertStringNotContainsString('Cambiar docente.', $contenidoEditado);
        $this->assertStringContainsString('"observada":false', $contenidoEditado);

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/enviar")
            ->assertRedirect();

        $segundaVersion = PropuestaVersion::where('propuesta_id', $propuesta->id)->where('numero', 2)->firstOrFail();
        $this->assertDatabaseHas('propuesta_version_designaciones', [
            'propuesta_version_id' => $segundaVersion->id,
            'grupo_id' => $filaAprobada->grupo_id,
            'estado' => 'aprobada_previamente',
        ]);
        $this->assertDatabaseHas('propuesta_version_designaciones', [
            'propuesta_version_id' => $segundaVersion->id,
            'grupo_id' => $filaObservada->grupo_id,
            'docente_id' => $docenteCorregido->id,
            'estado' => 'propuesta',
        ]);

        $this->actingAs($vicerrectorado)
            ->get("/revisiones/{$segundaVersion->id}/revisar")
            ->assertOk()
            ->assertSee('Aprobada previamente');
    }

    public function test_aprobar_version_completa_oficializa_propuesta_y_todas_sus_filas(): void
    {
        [$director, $vicerrectorado, $propuesta, $version] = $this->propuestaEnviada(2);

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$version->id}/decidir", ['modo' => 'aprobar_todo'])
            ->assertRedirect('/revisiones/pendientes');

        $this->assertDatabaseHas('propuesta_versiones', ['id' => $version->id, 'estado' => 'aprobada']);
        $this->assertDatabaseHas('propuestas', ['id' => $propuesta->id, 'estado' => 'oficial']);
        $this->assertSame(2, PropuestaDesignacion::where('propuesta_id', $propuesta->id)->where('estado', 'oficial')->count());
        $this->assertSame(2, PropuestaVersionDecision::whereIn('propuesta_version_designacion_id', $version->designaciones->pluck('id'))->where('decision', 'aprobada')->count());

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/enviar")
            ->assertForbidden();
    }

    public function test_vicerrectorado_no_puede_decidir_una_fila_que_no_pertenece_a_version(): void
    {
        [, $vicerrectorado, , $versionA] = $this->propuestaEnviada(1);
        [, , , $versionB, $filasB] = $this->propuestaEnviada(1);

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$versionA->id}/decidir", [
                'modo' => 'decidir_filas',
                'decisiones' => [
                    ['snapshot_id' => $filasB[0]->id, 'decision' => 'aprobada'],
                ],
            ])
            ->assertSessionHasErrors('decisiones');

        $this->assertDatabaseHas('propuesta_versiones', ['id' => $versionA->id, 'estado' => 'pendiente']);
        $this->assertDatabaseHas('propuesta_versiones', ['id' => $versionB->id, 'estado' => 'pendiente']);
    }

    public function test_decision_por_filas_exige_decision_para_cada_fila_pendiente(): void
    {
        [, $vicerrectorado, , $version, $filas] = $this->propuestaEnviada(2);

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$version->id}/decidir", [
                'modo' => 'decidir_filas',
                'decisiones' => [
                    ['snapshot_id' => $filas[0]->id, 'decision' => 'aprobada'],
                ],
            ])
            ->assertSessionHasErrors('decisiones');

        $this->assertDatabaseCount('propuesta_version_decisiones', 0);
    }

    public function test_fila_observada_requiere_observacion_por_fila_o_general(): void
    {
        [, $vicerrectorado, , $version, $filas] = $this->propuestaEnviada(1);

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$version->id}/decidir", [
                'modo' => 'decidir_filas',
                'decisiones' => [
                    ['snapshot_id' => $filas[0]->id, 'decision' => 'observada'],
                ],
            ])
            ->assertSessionHasErrors('decisiones');
    }

    public function test_fila_aprobada_no_acepta_observacion_por_fila(): void
    {
        [, $vicerrectorado, , $version, $filas] = $this->propuestaEnviada(1);

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$version->id}/decidir", [
                'modo' => 'decidir_filas',
                'decisiones' => [[
                    'snapshot_id' => $filas[0]->id,
                    'decision' => 'aprobada',
                    'observacion' => 'No me parece, pero aprobar.',
                ]],
            ])
            ->assertSessionHasErrors('decisiones');

        $this->assertDatabaseCount('propuesta_version_decisiones', 0);
        $this->assertDatabaseHas('propuesta_versiones', [
            'id' => $version->id,
            'estado' => 'pendiente',
        ]);
    }

    private function propuestaEnviada(int $cantidadFilas): array
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestion = Gestion::factory()->create(['es_actual' => true]);
        $periodo = Periodo::factory()->create();
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $vicerrectorado = User::factory()->vicerrectorado()->create();
        $propuesta = Propuesta::create([
            'carrera_id' => $carrera->id,
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
            'creado_por' => $director->id,
            'descripcion' => 'Propuesta de prueba para revisión',
            'estado' => 'borrador',
        ]);

        for ($indice = 0; $indice < $cantidadFilas; $indice++) {
            $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
            $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
            $this->actingAs($director)->put("/designaciones/{$propuesta->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $grupo->id,
                    'materia_id' => $materia->id,
                    'docente_id' => Docente::factory()->create()->id,
                ]],
            ])->assertRedirect();
        }

        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar")->assertRedirect();
        $version = PropuestaVersion::where('propuesta_id', $propuesta->id)->firstOrFail();

        return [$director, $vicerrectorado, $propuesta->load('carrera'), $version->load('designaciones'), $version->designaciones->values()];
    }
}
