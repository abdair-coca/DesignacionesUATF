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
use App\Models\PropuestaVersionDesignacion;
use App\Models\User;
use App\Services\Institutional\InstitutionalDesignacionesService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class PropuestaVersionadaTest extends TestCase
{
    public function test_director_puede_crear_multiples_propuestas_para_su_gestion_y_periodo_actual(): void
    {
        [$director, $gestion, $periodo] = $this->contextoDirector();

        $this->actingAs($director)
            ->post('/designaciones', [
                'gestion_id' => $gestion->id,
                'periodo_id' => $periodo->id,
                'descripcion' => 'Propuesta inicial',
            ])
            ->assertRedirect();

        $this->actingAs($director)
            ->post('/designaciones', [
                'gestion_id' => $gestion->id,
                'periodo_id' => $periodo->id,
                'descripcion' => 'Segunda propuesta',
            ])
            ->assertRedirect();

        $this->assertSame(2, Propuesta::where('carrera_id', $director->carrera_id)->count());
        $this->assertDatabaseHas('propuestas', ['descripcion' => 'Propuesta inicial', 'estado' => 'borrador']);
        $this->assertDatabaseHas('propuestas', ['descripcion' => 'Segunda propuesta', 'estado' => 'borrador']);

        config(['institutional.enabled' => true]);
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with(strtoupper((string) $director->carrera->sigla), '0', '0')->andReturn(new Collection);
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/designaciones')
            ->assertOk()
            ->assertSee('Designaciones');
    }

    public function test_no_se_puede_abrir_borrador_para_una_gestion_no_actual(): void
    {
        [$director, , $periodo] = $this->contextoDirector();
        $pasada = Gestion::factory()->create(['es_actual' => false]);

        $this->actingAs($director)
            ->post('/designaciones', [
                'gestion_id' => $pasada->id,
                'periodo_id' => $periodo->id,
            ])
            ->assertSessionHasErrors('gestion_id');
    }

    public function test_envio_crea_version_secuencial_y_snapshot_inmutable(): void
    {
        [$director, $gestion, $periodo, $carrera] = $this->contextoDirector();
        [$materia, $grupo] = $this->materiaYGrupo($carrera);
        $docente = Docente::factory()->create(['nombre' => 'Docente original']);
        $propuesta = $this->propuesta($director, $gestion, $periodo);

        $this->guardarFila($director, $propuesta, $grupo, $materia, $docente);

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/enviar")
            ->assertRedirect();

        $version = PropuestaVersion::where('propuesta_id', $propuesta->id)->firstOrFail();
        $snapshot = PropuestaVersionDesignacion::where('propuesta_version_id', $version->id)->firstOrFail();

        $this->assertSame(1, $version->numero);
        $this->assertSame('pendiente', $version->estado);
        $this->assertSame('Docente original', $snapshot->docente_nombre);
        $this->assertSame($materia->horas, $snapshot->materia_horas);
        $this->assertSame((string) $grupo->codigo, $snapshot->grupo_codigo);

        $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}")
            ->assertOk()
            ->assertSee('Historial de revisiones');

        $docente->update(['nombre' => 'Docente modificado']);
        $materia->update(['horas' => 99]);
        $grupo->update(['codigo' => '99']);
        PropuestaDesignacion::where('propuesta_id', $propuesta->id)->update(['docente_id' => Docente::factory()->create()->id]);

        $snapshot->refresh();
        $this->assertSame('Docente original', $snapshot->docente_nombre);
        $this->assertNotSame(99, $snapshot->materia_horas);
        $this->assertNotSame('99', $snapshot->grupo_codigo);
    }

    public function test_base_de_datos_impide_editar_o_borrar_un_snapshot(): void
    {
        [$director, $gestion, $periodo, $carrera] = $this->contextoDirector();
        [$materia, $grupo] = $this->materiaYGrupo($carrera);
        $propuesta = $this->propuesta($director, $gestion, $periodo);
        $this->guardarFila($director, $propuesta, $grupo, $materia, Docente::factory()->create());
        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar");

        $snapshot = PropuestaVersionDesignacion::firstOrFail();

        $this->expectException(QueryException::class);
        $snapshot->update(['docente_nombre' => 'No permitido']);
    }

    public function test_base_de_datos_impide_borrar_un_snapshot(): void
    {
        [$director, $gestion, $periodo, $carrera] = $this->contextoDirector();
        [$materia, $grupo] = $this->materiaYGrupo($carrera);
        $propuesta = $this->propuesta($director, $gestion, $periodo);
        $this->guardarFila($director, $propuesta, $grupo, $materia, Docente::factory()->create());
        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar");

        $this->expectException(QueryException::class);
        PropuestaVersionDesignacion::firstOrFail()->delete();
    }

    public function test_solo_director_remitente_puede_retirar_y_reenviar_crea_nueva_version(): void
    {
        [$director, $gestion, $periodo, $carrera] = $this->contextoDirector();
        $otroDirector = User::factory()->director($carrera)->create();
        [$materia, $grupo] = $this->materiaYGrupo($carrera);
        $propuesta = $this->propuesta($director, $gestion, $periodo);
        $this->guardarFila($director, $propuesta, $grupo, $materia, Docente::factory()->create());
        $this->actingAs($director)->post("/designaciones/{$propuesta->id}/enviar");
        $version = PropuestaVersion::firstOrFail();

        $this->actingAs($otroDirector)
            ->post("/designacion-versiones/{$version->id}/retirar")
            ->assertForbidden();

        $this->actingAs($director)
            ->post("/designacion-versiones/{$version->id}/retirar")
            ->assertRedirect();

        $this->assertDatabaseHas('propuesta_versiones', ['id' => $version->id, 'estado' => 'retirada', 'retirado_por' => $director->id]);

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/enviar")
            ->assertRedirect();

        $this->assertDatabaseHas('propuesta_versiones', ['propuesta_id' => $propuesta->id, 'numero' => 2, 'estado' => 'pendiente']);
        $this->assertDatabaseCount('propuesta_eventos', 3);
    }

    public function test_director_no_puede_consultar_ni_modificar_propuesta_de_otra_carrera(): void
    {
        [$director, $gestion, $periodo] = $this->contextoDirector();
        $otraCarrera = Carrera::factory()->create();
        $otroDirector = User::factory()->director($otraCarrera)->create();
        $ajena = $this->propuesta($otroDirector, $gestion, $periodo);

        $this->actingAs($director)
            ->get("/designaciones/{$ajena->id}")
            ->assertForbidden();

        $this->actingAs($director)
            ->put("/designaciones/{$ajena->id}/asignaciones", ['cambios' => []])
            ->assertForbidden();
    }

    private function contextoDirector(): array
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestion = Gestion::factory()->create(['es_actual' => true]);
        $periodo = Periodo::factory()->create();
        $carrera = Carrera::factory()->create();

        return [User::factory()->director($carrera)->create(), $gestion, $periodo, $carrera];
    }

    private function materiaYGrupo(Carrera $carrera): array
    {
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);

        return [$materia, Grupo::factory()->create(['materia_id' => $materia->id])];
    }

    private function propuesta(User $director, Gestion $gestion, Periodo $periodo): Propuesta
    {
        return Propuesta::create([
            'carrera_id' => $director->carrera_id,
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
            'creado_por' => $director->id,
            'estado' => 'borrador',
        ]);
    }

    private function guardarFila(User $director, Propuesta $propuesta, Grupo $grupo, Materia $materia, Docente $docente): void
    {
        $this->actingAs($director)
            ->put("/designaciones/{$propuesta->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $grupo->id,
                    'materia_id' => $materia->id,
                    'docente_id' => $docente->id,
                ]],
            ])
            ->assertRedirect();
    }
}
