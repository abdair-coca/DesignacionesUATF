<?php

namespace Tests\Feature;

use App\Auth\Demo\DemoUser;
use App\Models\Carrera;
use App\Models\Docente;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class JachasunDesignacionesDetailTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_selector_de_grupo_se_mantiene_editable(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117, [1, 2]);

        $this->actingAs($director)->get('/designaciones/2117')
            ->assertOk()
            ->assertDontSee(':disabled="!gruposOfertaDisponibles"', false);
    }

    public function test_modal_de_nueva_designacion_muestra_solo_el_siguiente_grupo(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117, [1, 2]);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee(asset('resources/assets/js/designaciones/carrera.js'), false)
            ->assertSee('grupo_siguiente', false);
    }

    public function test_modal_muestra_la_ayuda_del_siguiente_grupo_de_la_materia(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117, [1, 2]);

        $response = $this->actingAs($director)->get('/designaciones/2117')->assertOk();

        $response->assertSee('La materia ya tiene designaciones.', false);
        $response->assertSee('siguiente grupo disponible', false);
        $response->assertSee('grupoSiguienteMateria()', false);
    }

    public function test_modal_permite_materia_sin_docente_y_envia_horas_y_grupo(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117, [1, 2]);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertDontSee(':disabled="!editFilaForm.docente_id"', false)
            ->assertDontSee('Selecciona un docente primero', false)
            ->assertSee('form="form-editar-fila"', false)
            ->assertSee('name="id_grupo"', false)
            ->assertSee('name="hrs_teoria"', false)
            ->assertSee('name="hrs_practica"', false)
            ->assertSee('name="hrs_laboratorio"', false)
            ->assertSee('name="id_docente"', false)
            ->assertSee('name="id_materia"', false)
            ->assertSee('formulario.requestSubmit()', false);
    }

    public function test_detalle_es_imprimible_y_muestra_acciones_por_fila_y_de_cabecera(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117);

        $response = $this->actingAs($director)->get('/designaciones/2117?print=1')->assertOk()
            ->assertSee('Detalle de designación', false)
            ->assertSee('Ada Lovelace', false)
            ->assertSee('5107607', false)
            ->assertSee('Algoritmos', false)
            ->assertSee('>Estado</th>', false)
            ->assertSee('class="badge detail-status-badge"', false)
            ->assertSee('x-text="etiquetaEstadoFila(fila.id)"', false)
            ->assertSee('x-show="estadoModalOpen"', false)
            ->assertSee('x-text="observacionEstadoFila()"', false)
            ->assertSee('La asignación ha sido aprobada.', false)
            ->assertSee('Observación de la revisión', false)
            ->assertSee('APROBADA', false)
            ->assertSee('Motivo de prueba', false)
            ->assertSee('3', false)
            ->assertSee('Acciones', false)
            ->assertSee('Editar', false)
            ->assertSee('Desasignar', false)
            ->assertSee('Editar designación', false)
            ->assertSee('Guardar cambios', false)
            ->assertDontSee('Copiar a nueva gestión', false)
            ->assertDontSee(':disabled="!editFilaForm.docente_id"', false)
            ->assertSee(asset('resources/assets/css/shared/designaciones-detalle.css'), false)
            ->assertSee('/designaciones/2117/pdf', false)
            ->assertDontSee('Enviar Propuesta', false);

        $this->assertGreaterThanOrEqual(2, substr_count($response->getContent(), '/designaciones/2117/pdf'));
    }

    public function test_designacion_aprobada_se_muestra_solo_para_consulta_a_direccion(): void
    {
        $director = $this->demoDirector();
        $this->mockDetail(2117, [1], true, 'APROBADO');

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee('Consulta', false)
            ->assertDontSee('Consulta y edición', false)
            ->assertDontSee('>Editar</button>', false)
            ->assertDontSee('>Nueva designación</button>', false)
            ->assertDontSee('>Reasignar</button>', false)
            ->assertDontSee('>Desasignar</button>', false)
            ->assertDontSee('id="form-editar"', false)
            ->assertDontSee('id="form-editar-fila"', false)
            ->assertSee('APROBADO', false);
    }

    public function test_modal_de_edicion_usa_combobox_buscables_para_docente_y_materia(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee('Detalle de designación', false)
            ->assertSee('Buscar docente o CI', false)
            ->assertSee('aria-label="Buscar docente"', false)
            ->assertSee('<svg class="h-4 w-4"', false)
            ->assertSee('@submit.prevent="buscarDocentesUniversidad()"', false)
            ->assertSee('@keydown.enter.prevent="seleccionarOBuscarDocente()"', false)
            ->assertSee('seleccionarOBuscarDocente()', false)
            ->assertSee('Buscar materia o sigla', false)
            ->assertSee('@input="actualizarFiltroMateria($event.target.value)"', false)
            ->assertSee('lista-docentes', false)
            ->assertSee('lista-materias', false)
            ->assertSee('docentesFiltrados', false)
            ->assertSee('materiasFiltradas', false)
            ->assertSee('gruposMateriaSeleccionada', false)
            ->assertSee('horas_teoricas', false)
            ->assertSee(asset('resources/assets/js/designaciones/carrera.js'), false)
            ->assertSee(asset('resources/assets/css/shared/designaciones-detalle.css'), false)
            ->assertDontSee(':disabled="!editFilaForm.docente_id"', false)
            ->assertSee('Sin coincidencias', false)
            ->assertDontSee('<select id="editar-fila-docente"', false)
            ->assertDontSee('<select id="editar-fila-materia"', false);
    }

    public function test_toolbar_ubica_filtros_junto_al_input_y_abre_modal_para_nueva_designacion(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee('detail-search-controls', false)
            ->assertSee('Nueva designación', false)
            ->assertSee('@click="abrirNuevaDesignacion()"', false)
            ->assertSee('x-text="editFilaForm.id ? \'Editar asignación\' : \'Nueva designación\'"', false);
    }

    public function test_modal_de_confirmacion_queda_por_encima_del_modal_de_edicion(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee('class="designacion-confirmacion-modal"', false)
            ->assertSee(asset('resources/assets/css/shared/modales.css'), false)
            ->assertSee('type="button"', false);
    }

    public function test_modal_busca_docentes_solo_con_el_catalogo_global(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee(asset('resources/assets/js/designaciones/carrera.js'), false)
            ->assertDontSee('Resultados de docentes de la carrera.', false)
            ->assertDontSee('Resultados de toda la universidad.', false);
    }

    public function test_busqueda_de_docentes_usa_el_catalogo_local_por_nombre_y_ci(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $docente = Docente::factory()->create([
            'nombre' => 'Áda María Pérez',
            'ci' => '5107607',
        ]);

        $this->actingAs($director)
            ->get('/designaciones/docentes/buscar?q=%20%20ADA%20%20%20PEREZ%20')
            ->assertOk()
            ->assertJsonStructure([['id', 'nombre', 'ci']])
            ->assertJsonFragment([
                'id' => $docente->id,
                'nombre' => 'Áda María Pérez',
                'ci' => '5107607',
            ])
            ->assertJsonMissingPath('0.programa_codigo');

        $this->actingAs($director)
            ->get('/designaciones/docentes/buscar?q=510760')
            ->assertOk()
            ->assertJsonFragment(['id' => $docente->id]);
    }

    public function test_busqueda_local_ordena_establemente_y_limita_resultados(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        Docente::factory()->count(101)->create(['nombre' => 'Docente Coincidencia']);

        $items = $this->actingAs($director)
            ->get('/designaciones/docentes/buscar?q=coincidencia')
            ->assertOk()
            ->json();

        $this->assertCount(100, $items);
        $this->assertSame(100, collect($items)->pluck('id')->unique()->count());
        $this->assertSame(
            collect($items)->pluck('id')->sort()->values()->all(),
            collect($items)->pluck('id')->values()->all(),
        );
    }

    public function test_busqueda_global_devuelve_docentes_para_reasignacion(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('buscarDocentes')->once()->with('Ada')->andReturn(collect([
            [
                'id' => 974,
                'nombre' => 'Ada Lovelace',
                'ci' => '5107607',
                'programa_codigo' => 'MED',
                'programa_nombre' => 'MEDICINA',
            ],
        ]));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/designaciones/docentes/buscar?q=Ada')
            ->assertOk()
            ->assertJsonPath('0.id', 974)
            ->assertJsonPath('0.nombre', 'Ada Lovelace')
            ->assertJsonPath('0.ci', '5107607')
            ->assertJsonMissingPath('0.programa_codigo');
    }

    public function test_error_de_busqueda_global_no_filtra_detalles_tecnicos(): void
    {
        Log::spy();
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('buscarDocentes')->once()->with('Ada')->andThrow(new RuntimeException('password=secret private_rows'));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/designaciones/docentes/buscar?q=Ada')
            ->assertStatus(503)
            ->assertJson(['message' => 'No fue posible buscar docentes.'])
            ->assertDontSee('secret')
            ->assertDontSee('private_rows');

        Log::shouldHaveReceived('warning')->once()->with('Busqueda de docentes no disponible.', ['exception' => RuntimeException::class]);
    }

    public function test_modal_valida_la_fila_y_guarda_sin_confirmacion_adicional(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117, [1, 2]);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee('erroresFila', false)
            ->assertSee('validarFila()', false)
            ->assertSee('@click="guardarFila()"', false)
            ->assertDontSee('@click="confirmarEdicionFila()"', false)
            ->assertSee('@click="confirmarEdicionCabecera()"', false)
            ->assertSee('Seleccione un docente.', false)
            ->assertSee('Seleccione una materia.', false)
            ->assertSee('Seleccione un grupo.', false)
            ->assertSee('Las horas deben ser enteros no negativos.', false)
            ->assertSee('Las horas no pueden ser todas cero.', false);
    }

    public function test_detalle_sigue_disponible_si_no_se_pueden_cargar_grupos(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117, [], false);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertDontSee('Los grupos de la oferta no estan disponibles', false)
            ->assertSee('gruposOfertaDisponibles', false)
            ->assertDontSee(':disabled="!editFilaForm.docente_id"', false)
            ->assertDontSee(':disabled="!gruposOfertaDisponibles"', false);
    }

    public function test_detalle_pasa_la_oferta_actual_al_modal(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect([
            [
                'id' => 2117,
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'detalle' => 'SEMESTRAL 1/2026',
                'fecha' => null,
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => null,
                'estado' => 'SOLICITADO',
            ],
        ]));
        $service->shouldReceive('detallar')->once()->with(2117)->andReturn(collect());
        $service->shouldReceive('ofertaMaterias')->once()->with('INF', '2026', '1')->andReturn(collect([
            [
                'id' => 11233,
                'sigla' => 'INF101',
                'nombre' => 'ALGORITMOS',
                'nivel_academico' => 1,
                'mencion_id' => null,
                'horas_teoricas' => 3,
                'horas_practicas' => 3,
                'horas_laboratorio' => 0,
                'grupos' => [1, 2],
            ],
        ]));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $response = $this->actingAs($director)->get('/designaciones/2117');

        $response->assertOk();
        $materias = $response->viewData('materiasOferta');
        $this->assertSame('INF101', $materias->first()['sigla']);
        $this->assertSame([1, 2], $materias->first()['grupos']);
    }

    public function test_detalle_conserva_la_materia_actual_fuera_de_la_oferta_para_reasignar(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect([
            [
                'id' => 2117,
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'detalle' => 'SEMESTRAL 2026/1',
                'fecha' => null,
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => null,
                'estado' => 'SOLICITADO',
            ],
        ]));
        $service->shouldReceive('detallar')->once()->with(2117)->andReturn(collect([[
            'id' => 22325,
            'docente_id' => 974,
            'ci' => '5107607',
            'docente_nombre' => 'Ada Lovelace',
            'materia_id' => 9988,
            'materia_sigla' => 'INF199',
            'materia_nombre' => 'Materia actual',
            'grupo_id' => 4,
            'horas_teoricas' => 2,
            'horas_practicas' => 1,
            'horas_laboratorio' => 0,
        ]]));
        $service->shouldReceive('ofertaMaterias')->once()->with('INF', '2026', '1')->andReturn(collect([[
            'id' => 11233,
            'sigla' => 'INF101',
            'nombre' => 'Algoritmos',
            'nivel_academico' => 1,
            'mencion_id' => null,
            'horas_teoricas' => 3,
            'horas_practicas' => 3,
            'horas_laboratorio' => 0,
            'grupos' => [1, 2],
        ]]));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $response = $this->actingAs($director)->get('/designaciones/2117')->assertOk();

        $materiaActual = $response->viewData('materiasOferta')->firstWhere('id', 9988);
        $this->assertNotNull($materiaActual);
        $this->assertSame('INF199', $materiaActual['sigla']);
        $this->assertTrue($materiaActual['solo_edicion']);
        $this->assertSame([4], $materiaActual['grupos']);
    }

    public function test_detalle_de_otra_carrera_es_prohibido(): void
    {
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect());
        $service->shouldReceive('detallar')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($director)->get('/designaciones/2117')->assertNotFound();
    }

    public function test_fallo_del_detalle_no_filtra_datos_externos(): void
    {
        Log::spy();
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect([
            ['id' => 2117],
        ]));
        $service->shouldReceive('detallar')->once()->with(2117)->andThrow(new RuntimeException('password=secret private_rows'));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($director)->get('/designaciones/2117')->assertStatus(503)
            ->assertSee('No fue posible consultar el detalle de la designación.')
            ->assertDontSee('secret')
            ->assertDontSee('private_rows');
        Log::shouldHaveReceived('warning')->once()->with('Detalle Jachasun no disponible.', ['exception' => RuntimeException::class]);
    }

    public function test_modal_permite_modificar_horas_aunque_no_haya_catalogo_de_grupos(): void
    {
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $this->mockDetail(2117, [], false);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee('name="hrs_teoria"', false)
            ->assertSee('name="hrs_practica"', false)
            ->assertSee('name="hrs_laboratorio"', false);
    }

    private function mockDetail(
        int $id,
        array $grupos = [1],
        bool $gruposDisponibles = true,
        string $estado = 'SOLICITADO',
    ): void {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect([
            [
                'id' => $id,
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'detalle' => 'SEMESTRAL 1/2023',
                'fecha' => null,
                'gestion' => '2023',
                'periodo' => '1',
                'observacion' => null,
                'estado' => $estado,
            ],
        ]));
        $service->shouldReceive('detallar')->once()->with($id)->andReturn(collect([[
            'id' => 22325,
            'docente_id' => 974,
            'ci' => '5107607',
            'docente_nombre' => 'Ada Lovelace',
            'materia_id' => 11233,
            'materia_sigla' => 'INF101',
            'materia_nombre' => 'Algoritmos',
            'grupo_id' => 1,
            'horas_teoricas' => 3,
            'horas_practicas' => 3,
            'horas_laboratorio' => 0,
        ]]));
        $service->shouldReceive('listarDecisionesAsignacionDetalle')->once()->with($id)->andReturn(collect([[
            'id_detalle' => 22325,
            'estado' => 'APROBADA',
            'observacion' => 'Motivo de prueba',
        ]]));
        if ($estado !== 'APROBADO') {
            $service->shouldReceive('ofertaMaterias')->once()->with('INF', '2023', '1')->andReturn(collect([[
                'id' => 11233,
                'sigla' => 'INF101',
                'nombre' => 'Algoritmos',
                'nivel_academico' => 1,
                'mencion_id' => null,
                'horas_teoricas' => 3,
                'horas_practicas' => 3,
                'horas_laboratorio' => 0,
                'grupos' => $grupos,
                'grupos_disponibles' => $gruposDisponibles,
            ]]));
        }
        $this->app->instance(JachasunDesignacionesService::class, $service);
    }

    private function demoDirector(): DemoUser
    {
        $director = new DemoUser;
        $director->demo_id = 'director-test';
        $director->rol = User::ROL_DIRECTOR_CARRERA;
        $director->setRelation('carrera', (object) ['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);

        return $director;
    }
}
