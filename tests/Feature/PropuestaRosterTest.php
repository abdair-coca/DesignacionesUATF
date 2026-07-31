<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\PropuestaVersion;
use App\Models\User;
use Tests\TestCase;

class PropuestaRosterTest extends TestCase
{
    public function test_director_usa_el_roster_recuperado_con_el_flujo_versionado_actual(): void
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestion = Gestion::factory()->create(['es_actual' => true]);
        $periodo = Periodo::factory()->create();
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id, 'horas' => 6]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $docente = Docente::factory()->create(['nombre' => 'Docente del roster']);
        $propuesta = Propuesta::create([
            'carrera_id' => $carrera->id,
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
            'creado_por' => $director->id,
            'estado' => 'borrador',
        ]);

        $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}")
            ->assertOk()
            ->assertSee('Asignar')
            ->assertSee('Historial de revisiones');

        $this->actingAs($director)
            ->putJson("/designaciones/{$propuesta->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $grupo->id,
                    'materia_id' => $materia->id,
                    'docente_id' => $docente->id,
                ]],
            ])
            ->assertOk()
            ->assertJsonPath('success', true);

        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuesta->id,
            'grupo_id' => $grupo->id,
            'docente_id' => $docente->id,
        ]);
        $this->assertDatabaseCount('designaciones', 0);

        $this->actingAs($director)
            ->postJson("/designaciones/{$propuesta->id}/enviar")
            ->assertOk()
            ->assertJsonPath('success', true);

        $version = PropuestaVersion::where('propuesta_id', $propuesta->id)->firstOrFail();

        $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}")
            ->assertOk()
            ->assertSee('Solo lectura')
            ->assertSee((string) $version->numero);

        $this->actingAs($director)
            ->postJson("/designacion-versiones/{$version->id}/retirar")
            ->assertOk()
            ->assertJsonPath('success', true);
    }
}
