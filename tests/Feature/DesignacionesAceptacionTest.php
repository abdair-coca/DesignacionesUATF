<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\User;
use Illuminate\Notifications\DatabaseNotification;
use Tests\TestCase;

class DesignacionesAceptacionTest extends TestCase
{
    public function test_flujo_integral_aisla_dos_carreras_y_conserva_historial(): void
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestionActual = Gestion::factory()->create(['es_actual' => true]);
        $gestionOrigen = Gestion::factory()->create(['es_actual' => false]);
        $periodoActual = Periodo::factory()->create();
        $periodoOrigen = Periodo::factory()->create();
        $vicerrectorado = User::factory()->vicerrectorado()->create();

        [$directorA, $propuestaA, $materiaA, $grupoA, $docenteHistoricoA] = $this->crearCarreraConHistorico(
            'Carrera A',
            $gestionActual,
            $periodoActual,
            $gestionOrigen,
            $periodoOrigen,
        );
        [$directorB, $propuestaB, $materiaB, $grupoB, $docenteHistoricoB] = $this->crearCarreraConHistorico(
            'Carrera B',
            $gestionActual,
            $periodoActual,
            $gestionOrigen,
            $periodoOrigen,
        );

        $this->assertNotSame($directorA->carrera_id, $directorB->carrera_id);

        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuestaA->id,
            'docente_id' => $docenteHistoricoA->id,
            'horas_pagadas' => $materiaA->horas,
            'horas_no_pagadas' => 0,
        ]);
        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuestaB->id,
            'docente_id' => $docenteHistoricoB->id,
            'horas_pagadas' => $materiaB->horas,
            'horas_no_pagadas' => 0,
        ]);

        $this->actingAs($directorA)
            ->putJson("/designaciones/{$propuestaA->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $grupoA->id,
                    'materia_id' => $materiaA->id,
                    'docente_id' => $docenteHistoricoA->id,
                    'horas_pagadas' => 4,
                    'horas_no_pagadas' => 2,
                    'observacion_remuneracion' => 'Distribución revisable',
                ]],
            ])
            ->assertOk();

        $this->actingAs($directorB)
            ->putJson("/designaciones/{$propuestaB->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $grupoB->id,
                    'materia_id' => $materiaB->id,
                    'docente_id' => $docenteHistoricoB->id,
                    'horas_pagadas' => 6,
                    'horas_no_pagadas' => 0,
                ]],
            ])
            ->assertOk();

        $this->actingAs($directorA)->post("/designaciones/{$propuestaA->id}/enviar")->assertRedirect();
        $this->actingAs($directorB)->post("/designaciones/{$propuestaB->id}/enviar")->assertRedirect();

        $versionA1 = $propuestaA->versiones()->where('numero', 1)->firstOrFail()->load('designaciones');
        $versionB1 = $propuestaB->versiones()->where('numero', 1)->firstOrFail()->load('designaciones');

        $this->assertSame(4, $versionA1->designaciones[0]->horas_pagadas);
        $this->assertSame(2, $versionA1->designaciones[0]->horas_no_pagadas);
        $this->assertSame(6, $versionB1->designaciones[0]->horas_pagadas);

        $this->actingAs($directorB)
            ->get("/designaciones/{$propuestaA->id}")
            ->assertForbidden();

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$versionA1->id}/decidir", [
                'modo' => 'decidir_filas',
                'observacion_general' => 'Corregir distribución.',
                'decisiones' => [[
                    'snapshot_id' => $versionA1->designaciones[0]->id,
                    'decision' => 'observada',
                    'observacion' => 'Revisar horas pagadas.',
                ]],
            ])
            ->assertRedirect('/revisiones/pendientes');

        $docenteCorregido = Docente::factory()->create(['nombre' => 'Docente corregido A']);
        $this->actingAs($directorA)
            ->putJson("/designaciones/{$propuestaA->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $grupoA->id,
                    'materia_id' => $materiaA->id,
                    'docente_id' => $docenteCorregido->id,
                    'horas_pagadas' => 6,
                    'horas_no_pagadas' => 0,
                ]],
            ])
            ->assertOk();

        $this->actingAs($directorA)->post("/designaciones/{$propuestaA->id}/enviar")->assertRedirect();
        $versionA2 = $propuestaA->versiones()->where('numero', 2)->firstOrFail()->load('designaciones');

        $this->assertDatabaseHas('propuesta_version_designaciones', [
            'propuesta_version_id' => $versionA2->id,
            'docente_id' => $docenteCorregido->id,
            'horas_pagadas' => 6,
            'horas_no_pagadas' => 0,
        ]);
        $this->assertDatabaseHas('propuesta_version_designaciones', [
            'propuesta_version_id' => $versionA1->id,
            'horas_pagadas' => 4,
            'horas_no_pagadas' => 2,
        ]);

        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$versionA2->id}/decidir", ['modo' => 'aprobar_todo'])
            ->assertRedirect('/revisiones/pendientes');
        $this->actingAs($vicerrectorado)
            ->post("/revisiones/{$versionB1->id}/decidir", ['modo' => 'aprobar_todo'])
            ->assertRedirect('/revisiones/pendientes');

        $this->assertDatabaseHas('propuestas', ['id' => $propuestaA->id, 'estado' => 'oficial']);
        $this->assertDatabaseHas('propuestas', ['id' => $propuestaB->id, 'estado' => 'oficial']);
        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuestaA->id,
            'grupo_id' => $grupoA->id,
            'docente_id' => $docenteCorregido->id,
            'estado' => 'oficial',
            'horas_pagadas' => 6,
            'horas_no_pagadas' => 0,
        ]);

        $this->actingAs($directorA)
            ->putJson("/designaciones/{$propuestaA->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $grupoA->id,
                    'materia_id' => $materiaA->id,
                    'docente_id' => $docenteHistoricoA->id,
                ]],
            ])
            ->assertForbidden();

        $eventosDirectorA = DatabaseNotification::where('notifiable_id', $directorA->id)->get()->pluck('data.evento');
        $this->assertTrue($eventosDirectorA->contains('observada'));
        $this->assertTrue($eventosDirectorA->contains('aprobada_final'));
        $this->assertGreaterThanOrEqual(2, DatabaseNotification::where('notifiable_id', $vicerrectorado->id)->count());
    }

    private function crearCarreraConHistorico(
        string $nombre,
        Gestion $gestionActual,
        Periodo $periodoActual,
        Gestion $gestionOrigen,
        Periodo $periodoOrigen,
    ): array {
        $carrera = Carrera::factory()->create(['nombre' => $nombre]);
        $director = User::factory()->director($carrera)->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id, 'horas' => 6]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $docenteHistorico = Docente::factory()->create();

        Designacion::factory()->create([
            'Id_docente' => $docenteHistorico->id,
            'Id_grupo' => $grupo->id,
            'Id_materia' => $materia->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'Id_gestion' => $gestionOrigen->id,
            'Id_periodo' => $periodoOrigen->id,
            'estado' => 'aprobada',
        ]);

        $this->actingAs($director)
            ->post('/designaciones/copiar', [
                'gestion_id' => $gestionActual->id,
                'periodo_id' => $periodoActual->id,
                'descripcion' => "Copia {$nombre}",
                'origen_gestion_id' => $gestionOrigen->id,
                'origen_periodo_id' => $periodoOrigen->id,
            ])
            ->assertRedirect();

        $propuesta = Propuesta::where('carrera_id', $carrera->id)
            ->where('gestion_id', $gestionActual->id)
            ->where('periodo_id', $periodoActual->id)
            ->firstOrFail();

        return [$director, $propuesta, $materia, $grupo, $docenteHistorico];
    }
}
