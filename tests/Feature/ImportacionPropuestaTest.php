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
use App\Models\PropuestaDesignacion;
use App\Models\PropuestaVersion;
use App\Models\User;
use Tests\TestCase;

class ImportacionPropuestaTest extends TestCase
{
    public function test_director_previsualiza_y_confirma_importacion_historica_de_su_carrera(): void
    {
        [$director, $propuesta, $gestionOrigen, $periodoOrigen, $grupo, $materia] = $this->contextoImportacion();
        $docenteOrigen = Docente::factory()->create(['nombre' => 'Docente del historico']);
        $docenteDestino = Docente::factory()->create(['nombre' => 'Docente del borrador']);
        $designacionHistorica = Designacion::factory()->create([
            'Id_docente' => $docenteOrigen->id,
            'Id_grupo' => $grupo->id,
            'Id_materia' => $materia->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'Id_gestion' => $gestionOrigen->id,
            'Id_periodo' => $periodoOrigen->id,
            'estado' => 'aprobada',
        ]);
        PropuestaDesignacion::create([
            'propuesta_id' => $propuesta->id,
            'docente_id' => $docenteDestino->id,
            'materia_id' => $materia->id,
            'grupo_id' => $grupo->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'estado' => 'propuesta',
        ]);

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/importar/previsualizar", [
                'origen_gestion_id' => $gestionOrigen->id,
                'origen_periodo_id' => $periodoOrigen->id,
            ])
            ->assertOk()
            ->assertSee('Docente del historico')
            ->assertSee('Reemplaza a Docente del borrador');

        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuesta->id,
            'grupo_id' => $grupo->id,
            'docente_id' => $docenteDestino->id,
        ]);

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/importar", [
                'origen_gestion_id' => $gestionOrigen->id,
                'origen_periodo_id' => $periodoOrigen->id,
            ])
            ->assertRedirect("/designaciones/{$propuesta->id}");

        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuesta->id,
            'grupo_id' => $grupo->id,
            'docente_id' => $docenteOrigen->id,
            'estado' => 'propuesta',
        ]);
        $this->assertDatabaseHas('propuesta_eventos', [
            'propuesta_id' => $propuesta->id,
            'usuario_id' => $director->id,
            'tipo' => 'importada',
        ]);
        $this->assertDatabaseHas('designaciones', ['id' => $designacionHistorica->id, 'Id_docente' => $docenteOrigen->id]);
    }

    public function test_importacion_respeta_filas_aprobadas_previamente_y_borradores_no_editables(): void
    {
        [$director, $propuesta, $gestionOrigen, $periodoOrigen, $grupo, $materia] = $this->contextoImportacion();
        $docenteBloqueado = Docente::factory()->create();
        $docenteOrigen = Docente::factory()->create();
        Designacion::factory()->create([
            'Id_docente' => $docenteOrigen->id,
            'Id_grupo' => $grupo->id,
            'Id_materia' => $materia->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'Id_gestion' => $gestionOrigen->id,
            'Id_periodo' => $periodoOrigen->id,
        ]);
        PropuestaDesignacion::create([
            'propuesta_id' => $propuesta->id,
            'docente_id' => $docenteBloqueado->id,
            'materia_id' => $materia->id,
            'grupo_id' => $grupo->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'estado' => 'aprobada_previamente',
        ]);

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/importar/previsualizar", [
                'origen_gestion_id' => $gestionOrigen->id,
                'origen_periodo_id' => $periodoOrigen->id,
            ])
            ->assertOk()
            ->assertSee('Aprobada previamente: se conserva');

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/importar", [
                'origen_gestion_id' => $gestionOrigen->id,
                'origen_periodo_id' => $periodoOrigen->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuesta->id,
            'grupo_id' => $grupo->id,
            'docente_id' => $docenteBloqueado->id,
            'estado' => 'aprobada_previamente',
        ]);

        PropuestaVersion::create([
            'propuesta_id' => $propuesta->id,
            'numero' => 1,
            'estado' => 'pendiente',
            'enviado_por' => $director->id,
            'enviado_en' => now(),
        ]);

        $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}/importar")
            ->assertForbidden();
    }

    public function test_importacion_rechaza_origen_igual_y_usuarios_ajenos(): void
    {
        [$director, $propuesta, $gestionOrigen, $periodoOrigen] = $this->contextoImportacion();
        $otroDirector = User::factory()->director(Carrera::factory()->create())->create();
        $vicerrectorado = User::factory()->vicerrectorado()->create();

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/importar/previsualizar", [
                'origen_gestion_id' => $propuesta->gestion_id,
                'origen_periodo_id' => $propuesta->periodo_id,
            ])
            ->assertSessionHasErrors('origen_gestion_id');

        $this->actingAs($otroDirector)
            ->get("/designaciones/{$propuesta->id}/importar")
            ->assertForbidden();

        $this->actingAs($vicerrectorado)
            ->get("/designaciones/{$propuesta->id}/importar")
            ->assertForbidden();
    }

    public function test_importacion_no_esta_disponible_para_borrador_de_gestion_no_actual(): void
    {
        [$director, $propuesta] = $this->contextoImportacion();
        $propuesta->gestion->update(['es_actual' => false]);

        $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}/importar")
            ->assertForbidden();
    }

    public function test_importacion_tambien_usa_propuesta_oficial_historica_de_la_carrera(): void
    {
        [$director, $propuesta, $gestionOrigen, $periodoOrigen, $grupo, $materia] = $this->contextoImportacion();
        $docenteOrigen = Docente::factory()->create(['nombre' => 'Docente de propuesta oficial']);
        $propuestaHistorica = Propuesta::create([
            'carrera_id' => $director->carrera_id,
            'gestion_id' => $gestionOrigen->id,
            'periodo_id' => $periodoOrigen->id,
            'creado_por' => $director->id,
            'estado' => 'oficial',
        ]);
        PropuestaDesignacion::create([
            'propuesta_id' => $propuestaHistorica->id,
            'docente_id' => $docenteOrigen->id,
            'materia_id' => $materia->id,
            'grupo_id' => $grupo->id,
            'malla_curricular_id' => $grupo->malla_curricular_id,
            'estado' => 'oficial',
        ]);

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/importar/previsualizar", [
                'origen_gestion_id' => $gestionOrigen->id,
                'origen_periodo_id' => $periodoOrigen->id,
            ])
            ->assertOk()
            ->assertSee('Docente de propuesta oficial');

        $this->actingAs($director)
            ->post("/designaciones/{$propuesta->id}/importar", [
                'origen_gestion_id' => $gestionOrigen->id,
                'origen_periodo_id' => $periodoOrigen->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuesta->id,
            'docente_id' => $docenteOrigen->id,
        ]);
    }

    private function contextoImportacion(): array
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestionActual = Gestion::factory()->create(['es_actual' => true]);
        $gestionOrigen = Gestion::factory()->create(['es_actual' => false]);
        $periodoDestino = Periodo::factory()->create();
        $periodoOrigen = Periodo::factory()->create();
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $propuesta = Propuesta::create([
            'carrera_id' => $carrera->id,
            'gestion_id' => $gestionActual->id,
            'periodo_id' => $periodoDestino->id,
            'creado_por' => $director->id,
            'estado' => 'borrador',
        ]);

        return [$director, $propuesta, $gestionOrigen, $periodoOrigen, $grupo, $materia];
    }
}
