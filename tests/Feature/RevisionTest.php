<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Revision;
use App\Models\User;
use Tests\TestCase;

class RevisionTest extends TestCase
{
    public function test_director_envia_revision_de_su_carrera(): void
    {
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $gestion = Gestion::firstOrCreate(['nombre' => (string) date('Y')]);
        $periodo = Periodo::factory()->create();

        $this->actingAs($director)
            ->postJson('/revisiones/solicitar', [
                'carrera_id' => $carrera->id,
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
            ])
            ->assertOk()
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('revisiones', [
            'carrera_id' => $carrera->id,
            'solicitado_por' => $director->id,
            'estado' => 'pendiente',
        ]);
    }

    public function test_director_no_puede_enviar_revision_de_otra_carrera(): void
    {
        $director = User::factory()->director(Carrera::factory()->create())->create();
        $otraCarrera = Carrera::factory()->create();
        $gestion = Gestion::firstOrCreate(['nombre' => (string) date('Y')]);
        $periodo = Periodo::factory()->create();

        $this->actingAs($director)
            ->postJson('/revisiones/solicitar', [
                'carrera_id' => $otraCarrera->id,
                'Id_gestion' => $gestion->id,
                'Id_periodo' => $periodo->id,
            ])
            ->assertForbidden();
    }

    public function test_director_solo_puede_retirar_su_revision_pendiente(): void
    {
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $otroDirector = User::factory()->director($carrera)->create();
        $revision = $this->crearRevision($carrera, $director, 'pendiente');

        $this->actingAs($otroDirector)
            ->postJson("/revisiones/{$revision->id}/retirar")
            ->assertForbidden();

        $this->actingAs($director)
            ->postJson("/revisiones/{$revision->id}/retirar")
            ->assertOk();

        $this->assertDatabaseHas('revisiones', ['id' => $revision->id, 'estado' => 'propuesta']);
    }

    public function test_director_no_puede_eliminar_revision_de_otro_director(): void
    {
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $otroDirector = User::factory()->director($carrera)->create();
        $revision = $this->crearRevision($carrera, $director, 'propuesta');

        $this->actingAs($otroDirector)
            ->deleteJson("/revisiones/{$revision->id}")
            ->assertForbidden();

        $this->assertDatabaseHas('revisiones', ['id' => $revision->id]);
    }

    public function test_vicerrectorado_revisa_una_revision_pendiente_y_sus_filas(): void
    {
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $vicerrectorado = User::factory()->vicerrectorado()->create();
        $revision = $this->crearRevision($carrera, $director, 'pendiente');
        $designacion = $this->crearDesignacion($carrera, $revision);

        $this->actingAs($vicerrectorado)
            ->postJson("/revisiones/{$revision->id}/procesar", [
                'acciones' => [['id' => $designacion->id, 'accion' => 'aprobar']],
            ])
            ->assertOk()
            ->assertJson(['procesadas' => 1]);

        $this->assertDatabaseHas('designaciones', [
            'id' => $designacion->id,
            'estado' => 'aprobada',
            'aprobado_por' => $vicerrectorado->id,
        ]);
    }

    public function test_vicerrectorado_no_puede_procesar_fila_de_otra_revision(): void
    {
        $carreraA = Carrera::factory()->create();
        $carreraB = Carrera::factory()->create();
        $directorA = User::factory()->director($carreraA)->create();
        $directorB = User::factory()->director($carreraB)->create();
        $revisionA = $this->crearRevision($carreraA, $directorA, 'pendiente');
        $revisionB = $this->crearRevision($carreraB, $directorB, 'pendiente');
        $designacionB = $this->crearDesignacion($carreraB, $revisionB);
        $vicerrectorado = User::factory()->vicerrectorado()->create();

        $this->actingAs($vicerrectorado)
            ->postJson("/revisiones/{$revisionA->id}/procesar", [
                'acciones' => [['id' => $designacionB->id, 'accion' => 'aprobar']],
            ])
            ->assertForbidden();

        $this->assertDatabaseHas('designaciones', ['id' => $designacionB->id, 'estado' => 'propuesta']);
    }

    public function test_director_no_puede_acceder_a_la_bandeja_de_vicerrectorado(): void
    {
        $director = User::factory()->create();

        $this->actingAs($director)
            ->get('/revisiones/pendientes')
            ->assertForbidden();
    }

    private function crearRevision(Carrera $carrera, User $director, string $estado): Revision
    {
        return Revision::create([
            'carrera_id' => $carrera->id,
            'Id_gestion' => Gestion::firstOrCreate(['nombre' => (string) date('Y')])->id,
            'Id_periodo' => Periodo::factory()->create()->id,
            'solicitado_por' => $director->id,
            'solicitado_en' => $estado === 'pendiente' ? now() : null,
            'estado' => $estado,
        ]);
    }

    private function crearDesignacion(Carrera $carrera, Revision $revision): Designacion
    {
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);

        return Designacion::factory()->create([
            'Id_materia' => $materia->id,
            'Id_grupo' => $grupo->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'Id_gestion' => $revision->Id_gestion,
            'Id_periodo' => $revision->Id_periodo,
            'estado' => 'propuesta',
        ]);
    }
}
