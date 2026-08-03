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

class PropuestaDistribucionTest extends TestCase
{
    public function test_acepta_distribuciones_validas_incluyendo_horas_adicionales(): void
    {
        [$director, $propuesta, $materia, $grupo, $docente] = $this->contexto(6);

        foreach ([[6, 0], [4, 2], [0, 6], [6, 2]] as [$pagadas, $noPagadas]) {
            $this->guardar($director, $propuesta, $materia, $grupo, $docente, $pagadas, $noPagadas)
                ->assertOk();
        }

        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuesta->id,
            'horas_pagadas' => 6,
            'horas_no_pagadas' => 2,
        ]);
    }

    public function test_rechaza_negativos_decimales_deficit_y_pagadas_superiores(): void
    {
        [$director, $propuesta, $materia, $grupo, $docente] = $this->contexto(6);

        foreach ([[-1, 7], [4.5, 2], [4, 1], [7, 0]] as [$pagadas, $noPagadas]) {
            $this->guardar($director, $propuesta, $materia, $grupo, $docente, $pagadas, $noPagadas)
                ->assertSessionHasErrors();
        }

        $this->assertDatabaseCount('propuesta_designaciones', 0);
    }

    public function test_persistencia_snapshot_inmutable_y_revision_solo_lectura_muestran_distribucion(): void
    {
        [$director, $propuesta, $materia, $grupo, $docente] = $this->contexto(6);
        $vicerrectorado = User::factory()->vicerrectorado()->create();

        $this->guardar($director, $propuesta, $materia, $grupo, $docente, 4, 2, 'Justificación de prueba')->assertOk();
        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar")->assertRedirect();

        $version = PropuestaVersion::firstOrFail();
        $snapshot = $version->designaciones()->firstOrFail();

        $this->assertSame(4, $snapshot->horas_pagadas);
        $this->assertSame(2, $snapshot->horas_no_pagadas);
        $this->assertSame('Justificación de prueba', $snapshot->observacion_remuneracion);

        $this->actingAs($vicerrectorado)
            ->get("/revisiones/{$version->id}/revisar")
            ->assertOk()
            ->assertSee('4 h')
            ->assertSee('2 h')
            ->assertDontSee('name="horas_pagadas"');
    }

    public function test_envio_rechaza_grupo_habilitado_sin_docente(): void
    {
        [$director, $propuesta, $materia, $grupo, $docente, $carrera] = $this->contexto(6);
        $segundaMateria = Materia::factory()->create(['carrera_id' => $carrera->id, 'horas' => 4]);
        $segundoGrupo = Grupo::factory()->create(['materia_id' => $segundaMateria->id]);

        $this->guardar($director, $propuesta, $materia, $grupo, $docente, 6, 0)->assertOk();

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/enviar")
            ->assertSessionHasErrors('propuesta');

        $this->assertDatabaseMissing('propuesta_versiones', ['propuesta_id' => $propuesta->id]);
        $this->assertDatabaseMissing('propuesta_designaciones', ['grupo_id' => $segundoGrupo->id]);
    }

    public function test_carga_menor_a_seis_y_mayor_a_treinta_y_dos_no_bloquea_envio(): void
    {
        [$director, $propuesta, $materia, $grupo, $docente, $carrera] = $this->contexto(4);
        $segundaMateria = Materia::factory()->create(['carrera_id' => $carrera->id, 'horas' => 40]);
        $segundoGrupo = Grupo::factory()->create(['materia_id' => $segundaMateria->id]);
        $segundoDocente = Docente::factory()->create();

        $this->guardar($director, $propuesta, $materia, $grupo, $docente, 4, 0)->assertOk();
        $this->guardar($director, $propuesta, $segundaMateria, $segundoGrupo, $segundoDocente, 40, 0)->assertOk();

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/enviar")
            ->assertRedirect();
    }

    private function contexto(int $horas): array
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestion = Gestion::factory()->create(['es_actual' => true]);
        $periodo = Periodo::factory()->create();
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id, 'horas' => $horas]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $docente = Docente::factory()->create();
        $propuesta = Propuesta::create([
            'carrera_id' => $carrera->id,
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
            'creado_por' => $director->id,
            'estado' => 'borrador',
        ]);

        return [$director, $propuesta, $materia, $grupo, $docente, $carrera];
    }

    private function guardar(
        User $director,
        Propuesta $propuesta,
        Materia $materia,
        Grupo $grupo,
        Docente $docente,
        mixed $pagadas,
        mixed $noPagadas,
        ?string $observacion = null,
    ) {
        return $this->actingAs($director)->putJson("/designaciones/{$propuesta->id}/asignaciones", [
            'cambios' => [[
                'grupo_id' => $grupo->id,
                'materia_id' => $materia->id,
                'docente_id' => $docente->id,
                'horas_pagadas' => $pagadas,
                'horas_no_pagadas' => $noPagadas,
                'observacion_remuneracion' => $observacion,
            ]],
        ]);
    }
}
