<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class DesignacionesInterfazTest extends TestCase
{
    public function test_docentes_con_materias_asignadas_aparecen_primero_y_el_nombre_ordenado(): void
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestion = Gestion::factory()->create(['es_actual' => true]);
        $periodo = Periodo::factory()->create();
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $materia = Materia::factory()->create(['carrera_id' => $carrera->id]);
        $grupo = Grupo::factory()->create(['materia_id' => $materia->id]);
        $docenteAsignado = Docente::factory()->create(['nombre' => 'Zoe Asignada']);
        $docenteSinAsignacion = Docente::factory()->create(['nombre' => 'Ana Sin Asignacion']);
        $propuesta = Propuesta::create([
            'carrera_id' => $carrera->id,
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
            'creado_por' => $director->id,
            'estado' => 'borrador',
        ]);

        $this->actingAs($director)
            ->putJson("/designaciones/{$propuesta->id}/asignaciones", [
                'cambios' => [[
                    'grupo_id' => $grupo->id,
                    'materia_id' => $materia->id,
                    'docente_id' => $docenteAsignado->id,
                ]],
            ])
            ->assertOk();

        $this->assertDatabaseHas('propuesta_designaciones', [
            'propuesta_id' => $propuesta->id,
            'docente_id' => $docenteAsignado->id,
        ]);

        $html = html_entity_decode($this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}")
            ->assertOk()
            ->getContent());

        $posicionAsignado = strpos($html, 'Zoe Asignada');
        $posicionSinAsignacion = strpos($html, 'Ana Sin Asignacion');

        $this->assertLessThan(
            $posicionSinAsignacion,
            $posicionAsignado,
            'Docentes asignados deben aparecer antes que docentes sin asignación.',
        );
    }

    public function test_boton_de_envio_usa_icono_de_avion_de_papel(): void
    {
        [$director, $propuesta] = $this->propuestaBorrador();

        $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}")
            ->assertOk()
            ->assertSee('M6 12 3.75 3.75 21 12l-17.25 8.25L6 12Zm0 0h7.5', false)
            ->assertDontSee('M12 19l9 2-9-18-9 18 9-2zm0 0v-8', false);
    }

    public function test_lista_y_editor_muestran_impresion_y_navegacion(): void
    {
        [$director, $propuesta] = $this->propuestaBorrador();
        $this->mockJachasunList();

        $this->actingAs($director)
            ->get('/designaciones')
            ->assertOk()
            ->assertSee('modal-imprimir-designaciones', false)
            ->assertSee('abrirModalImprimir', false);

        $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}")
            ->assertOk()
            ->assertSee('modal-imprimir-designaciones', false)
            ->assertSee('abrirModalImprimir', false)
            ->assertSee('aria-label="Página siguiente"', false)
            ->assertSee('aria-label="Página anterior"', false);
    }

    public function test_lista_jachasun_muestra_acciones_deshabilitadas(): void
    {
        [$director, $propuesta] = $this->propuestaBorrador();
        Propuesta::create([
            'carrera_id' => $propuesta->carrera_id,
            'gestion_id' => $propuesta->gestion_id,
            'periodo_id' => $propuesta->periodo_id,
            'creado_por' => $director->id,
            'estado' => 'oficial',
            'descripcion' => 'Propuesta aprobada',
        ]);
        $this->mockJachasunList(new Collection([[
            'id' => 1,
            'programa_codigo' => 'INF',
            'programa_nombre' => 'INGENIERIA INFORMATICA',
            'detalle' => 'REGISTRO',
            'fecha' => null,
            'gestion' => '2024',
            'periodo' => '1',
            'observacion' => null,
            'estado' => 'SOLICITADO',
        ]]));

        $this->actingAs($director)
            ->get('/designaciones')
            ->assertOk()
            ->assertSee('title="Abrir asignación docente"', false)
            ->assertSee('Abrir', false)
            ->assertSee('institutionalSource', false)
            ->assertSee('title="Ver detalle de la asignación"', false)
            ->assertSee('Ver detalle', false)
            ->assertSee('disabled', false)
            ->assertDontSee('title="Editar asignación docente"', false);
    }

    public function test_lista_muestra_todas_las_gestiones_y_pagina_de_diez_filas(): void
    {
        [$director, $propuestaActual] = $this->propuestaBorrador();
        $gestionAnterior = Gestion::factory()->create(['es_actual' => false]);
        $propuestaAnterior = Propuesta::create([
            'carrera_id' => $propuestaActual->carrera_id,
            'gestion_id' => $gestionAnterior->id,
            'periodo_id' => $propuestaActual->periodo_id,
            'creado_por' => $director->id,
            'estado' => 'borrador',
            'descripcion' => 'Propuesta de gestion anterior',
        ]);
        $this->mockJachasunList();

        $this->actingAs($director)
            ->get('/designaciones?gestion_id='.$propuestaActual->gestion_id)
            ->assertOk()
            ->assertSee('Fecha', false)
            ->assertSee('Observacion', false)
            ->assertSee('perPage: 10', false)
            ->assertSee('propuestasPaginadas', false)
            ->assertSee('@click="currentPage++"', false)
            ->assertSee('@click="currentPage--"', false);
    }

    public function test_detalle_de_propuesta_oficial_muestra_aviso_de_aprobacion(): void
    {
        [$director, $propuesta] = $this->propuestaBorrador();
        $propuesta->update(['estado' => 'oficial']);

        $this->actingAs($director)
            ->get("/designaciones/{$propuesta->id}")
            ->assertOk()
            ->assertSee('Esta designación ya ha sido aprobada.', false)
            ->assertDontSee('El borrador esta bloqueado mientras una version este pendiente de revision.', false);
    }

    private function mockJachasunList(?Collection $items = null): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')
            ->once()
            ->andReturn($items ?? new Collection);
        $this->app->instance(JachasunDesignacionesService::class, $service);
    }

    private function propuestaBorrador(): array
    {
        Gestion::query()->update(['es_actual' => false]);
        $gestion = Gestion::factory()->create(['es_actual' => true]);
        $periodo = Periodo::factory()->create();
        $carrera = Carrera::factory()->create();
        $director = User::factory()->director($carrera)->create();
        $propuesta = Propuesta::create([
            'carrera_id' => $carrera->id,
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
            'creado_por' => $director->id,
            'estado' => 'borrador',
        ]);

        return [$director, $propuesta];
    }
}
