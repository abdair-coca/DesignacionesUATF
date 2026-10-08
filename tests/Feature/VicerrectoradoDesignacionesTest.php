<?php

namespace Tests\Feature;

use App\Auth\Demo\DemoUser;
use App\Data\Designaciones\RevisionDesignacion;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Mockery;
use Mockery\MockInterface;
use Tests\TestCase;

class VicerrectoradoDesignacionesTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_vicerrectorado_ve_todas_las_designaciones_de_la_gestion(): void
    {
        $this->mockService()->shouldReceive('listarUniversidad')->once()
            ->with('2026')
            ->andReturn(collect([$this->fila('MED', 'MEDICINA', 'SOLICITADO')]));

        $response = $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones?gestion=2026');

        $response->assertOk()
            ->assertSee(asset('resources/assets/css/shared/designaciones-lista.css'), false)
            ->assertSee(asset('resources/assets/css/shared/modales.css'), false)
            ->assertSee(asset('resources/assets/js/vicerrectorado/designaciones/lista.js'), false)
            ->assertSee('Designaciones universitarias')
            ->assertSee('MEDICINA')
            ->assertSee('SOLICITADO')
            ->assertSee('x-model="filtro"', false)
            ->assertSee('x-model="carrera"', false)
            ->assertSee('name="carrera"', false)
            ->assertSee('Descripción, observación, carrera, estado…')
            ->assertSee('Todas las carreras')
            ->assertSee('breadcrumb float-xl-right', false)
            ->assertSee('page-header', false)
            ->assertSee('panel panel-inverse', false)
            ->assertSee('id="tdLista"', false)
            ->assertSee('table table-striped table-bordered table-td-valign-middle dt-responsive', false)
            ->assertSee('vicerrectorado-designaciones designaciones-screen', false)
            ->assertSee('<th scope="col" class="text-nowrap text-center">Fecha</th>', false)
            ->assertSee('<th scope="col" class="text-nowrap">Descripción</th>', false)
            ->assertDontSee('<th scope="col" class="text-nowrap">Carrera</th>', false)
            ->assertSee("'badge-info': String(designacion.estado || '').toUpperCase() === 'SOLICITADO'", false)
            ->assertSee('class="record-actions"', false)
            ->assertSee('class="btn btn-info">Detalles</a>', false)
            ->assertSee('class="btn btn-dark"', false)
            ->assertSee(str_replace('/', '\\/', route('vicerrectorado.designaciones.show', ['id' => '__ID__', 'gestion' => '2026'])), false)
            ->assertSee(str_replace('/', '\\/', route('vicerrectorado.designaciones.pdf', ['id' => '__ID__', 'gestion' => '2026'])), false)
            ->assertSee('target="_blank" rel="noopener"', false)
            ->assertDontSee('btn-group dropup', false)
            ->assertDontSee('Nuevo')
            ->assertDontSee('Editar');
    }

    public function test_el_listado_distingue_los_estados_generales(): void
    {
        $this->mockService()->shouldReceive('listarUniversidad')->once()
            ->with('2026')
            ->andReturn(collect([
                $this->fila('MED', 'MEDICINA', 'SOLICITADO'),
                array_merge($this->fila('INF', 'INFORMATICA', 'APROBADO'), ['id' => 23]),
                array_merge($this->fila('DER', 'DERECHO', 'OBSERVADA'), ['id' => 24]),
            ]));

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones?gestion=2026')
            ->assertOk()
            ->assertSee("'badge-info': String(designacion.estado || '').toUpperCase() === 'SOLICITADO'", false)
            ->assertSee("'badge-success': String(designacion.estado || '').toUpperCase() === 'APROBADO'", false)
            ->assertSee("'badge-warning': String(designacion.estado || '').toUpperCase() === 'OBSERVADA'", false)
            ->assertSee('SOLICITADO')
            ->assertSee('APROBADO')
            ->assertSee('OBSERVADA');
    }

    public function test_el_listado_entrega_todas_las_filas_para_buscar_y_paginar_en_la_interfaz(): void
    {
        $filas = collect(range(1, 12))->map(fn (int $numero): array => array_merge(
            $this->fila($numero % 2 === 0 ? 'MED' : 'INF', $numero % 2 === 0 ? 'MEDICINA' : 'INFORMATICA', 'SOLICITADO'),
            [
                'id' => 100 + $numero,
                'detalle' => 'Designación de prueba '.$numero,
                'observacion' => $numero === 12 ? 'Observación que también debe encontrarse' : null,
            ],
        ));

        $this->mockService()->shouldReceive('listarUniversidad')->once()
            ->with('2026')
            ->andReturn($filas);

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones?gestion=2026&carrera=MED')
            ->assertOk()
            ->assertSee('Designación de prueba 12')
            ->assertSee('Observación que también debe encontrarse')
            ->assertSee("carrera: 'MED'", false)
            ->assertSee('designacion in paginadas()', false)
            ->assertSee('filasFiltradas().length', false);
    }

    public function test_director_no_puede_acceder_a_la_pantalla_global(): void
    {
        $this->actingAs($this->usuario(User::ROL_DIRECTOR_CARRERA))
            ->get('/vicerrectorado/designaciones')
            ->assertForbidden();
    }

    public function test_la_gestion_actual_se_usa_si_no_se_envia_un_parametro(): void
    {
        Carbon::setTestNow(Carbon::create(2026, 9, 29));
        $this->mockService()->shouldReceive('listarUniversidad')->once()
            ->with('2026')
            ->andReturn(collect());

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones')
            ->assertOk()
            ->assertSee('value="2026"', false);

        Carbon::setTestNow();
    }

    public function test_vicerrectorado_puede_abrir_el_detalle_y_revisar_la_designacion(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('obtenerUniversidad')->once()
            ->with(22, '2026')
            ->andReturn($this->fila('MED', 'MEDICINA', 'SOLICITADO'));
        $service->shouldReceive('detallar')->once()->with(22)->andReturn(new Collection([[
            'id' => 901,
            'docente_nombre' => 'Docente de prueba',
            'ci' => 'CI-DEMO',
            'materia_sigla' => 'MAT-101',
            'materia_nombre' => 'Materia de prueba',
            'grupo_id' => 1,
            'horas_teoricas' => 2,
            'horas_practicas' => 1,
            'horas_laboratorio' => 0,
        ]]));
        $service->shouldReceive('listarDecisionesAsignacionDetalle')->once()
            ->with(22)
            ->andReturn(new Collection([['id_detalle' => 901, 'estado' => 'APROBADA', 'observacion' => null]]));
        $service->shouldReceive('obtenerRevisionDesignacion')->once()
            ->with(22)
            ->andReturn(new RevisionDesignacion('SOLICITADO', null));

        $response = $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones/22?gestion=2026&carrera=MED');

        $response->assertOk()
            ->assertSee(asset('resources/assets/css/shared/designaciones-detalle.css'), false)
            ->assertSee(asset('resources/assets/css/shared/modales.css'), false)
            ->assertSee(asset('resources/assets/js/vicerrectorado/designaciones/detalle.js'), false)
            ->assertSee('Detalle de designación')
            ->assertSee('MEDICINA')
            ->assertSee('SOLICITADO')
            ->assertSee('Aprobar designación')
            ->assertSee('Observar designación')
            ->assertSee('Motivo de observación')
            ->assertSee('Docente de prueba')
            ->assertSee('CI-DEMO')
            ->assertSee('MAT-101 — Materia de prueba')
            ->assertSee('APROBADA')
            ->assertSee('class="designaciones-detail vicerrectorado-designaciones', false)
            ->assertSee('class="print-card detail-header"', false)
            ->assertSee('class="detail-summary"', false)
            ->assertSee('panel panel-inverse', false)
            ->assertSee('class="detail-table"', false)
            ->assertSee('>CI</th>', false)
            ->assertSee('>Estado</th>', false)
            ->assertSee('class="badge detail-status-badge"', false)
            ->assertSee(':class="claseEstadoFila(901)"', false)
            ->assertSee('@click="mostrarEstadoFila(901)"', false)
            ->assertSee('x-text="etiquetaEstadoFila(901)">APROBADA</strong>', false)
            ->assertSee('x-show="estadoModalOpen"', false)
            ->assertSee('class="app-modal no-print"', false)
            ->assertSee('aria-labelledby="estado-fila-title"', false)
            ->assertSee('@keydown.escape.window="cerrarEstadoFila()"', false)
            ->assertSee('@click.outside="cerrarEstadoFila()"', false)
            ->assertSee('La asignación ha sido aprobada.')
            ->assertSee('Esta asignación aún no ha sido revisada.')
            ->assertSee('La asignación ha sido rechazada.')
            ->assertSee('x-text="observacionEstadoFila()"', false)
            ->assertSee('>Teóricas</th>', false)
            ->assertSee('carrera=MED', false)
            ->assertSee(route('vicerrectorado.designaciones.pdf', ['id' => 22, 'gestion' => '2026']), false)
            ->assertSee('Revisión de designación')
            ->assertSee('x-data="vicerrectoradoRevision({ filas:', false)
            ->assertSee('>Acciones</th>', false)
            ->assertSee('aria-label="Seleccionar todas las asignaciones"', false)
            ->assertSee('@change="alternarSeleccion(901, $event.target.checked)"', false)
            ->assertSee('@click="abrirDecision(901, \'APROBADA\')"', false)
            ->assertSee('title="Aceptar asignación" aria-label="Aceptar asignación"', false)
            ->assertSee('>check_circle</span>', false)
            ->assertSee('@click="abrirDecision(901, \'RECHAZADA\')"', false)
            ->assertSee('title="Rechazar asignación" aria-label="Rechazar asignación"', false)
            ->assertSee('>cancel</span>', false)
            ->assertDontSee('>Observar</button>', false)
            ->assertSee('@click="abrirDecisionSeleccionadas(\'APROBADA\')"', false)
            ->assertSee('title="Aceptar filas seleccionadas"', false)
            ->assertSee('@click="abrirDecisionSeleccionadas(\'RECHAZADA\')"', false)
            ->assertSee('@click="abrirRevisionDesignacion(\'APROBADO\')"', false)
            ->assertSee('@click="abrirRevisionDesignacion(\'OBSERVADA\')"', false)
            ->assertSee('title="Rechazar filas seleccionadas"', false)
            ->assertSee('x-show="decisionModalOpen"', false)
            ->assertSee('class="app-modal-content" @submit.prevent="guardarDecision()"', false)
            ->assertSee('aria-labelledby="decision-fila-title"', false)
            ->assertSee('id="observacion-decision" x-model="observacionTexto" maxlength="1000"', false)
            ->assertDontSee('id="observacion-decision" x-model="observacionTexto" required', false)
            ->assertSee('Observación (opcional)')
            ->assertSee('Puedes dejar la observación vacía.')
            ->assertSee('guardarDecision()', false)
            ->assertSee('Guardar cambios')
            ->assertSee('@click="confirmarRevision()"', false)
            ->assertDontSee('Actualizar')
            ->assertDontSee('Reasignar')
            ->assertDontSee('Desasignar');
    }

    public function test_una_asignacion_rechazada_puede_aprobarse_y_limpiarse_su_observacion(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('obtenerUniversidad')->times(3)
            ->with(22, '2026')
            ->andReturn(
                $this->fila('MED', 'MEDICINA', 'SOLICITADO'),
                $this->fila('MED', 'MEDICINA', 'SOLICITADO'),
                $this->fila('MED', 'MEDICINA', 'APROBADO'),
            );
        $service->shouldReceive('detallar')->once()->with(22)->andReturn(new Collection([[
            'id' => 901,
            'docente_nombre' => 'Docente de prueba',
            'ci' => 'CI-DEMO',
            'materia_sigla' => 'MAT-101',
            'materia_nombre' => 'Materia de prueba',
            'grupo_id' => 1,
            'horas_teoricas' => 2,
            'horas_practicas' => 1,
            'horas_laboratorio' => 0,
        ]]));
        $service->shouldReceive('listarDecisionesAsignacionDetalle')->once()
            ->with(22)
            ->andReturn(new Collection([[
                'id_detalle' => 901,
                'estado' => 'RECHAZADA',
                'observacion' => 'Corregir distribución de horas',
            ]]));
        $service->shouldReceive('obtenerRevisionDesignacion')->once()
            ->with(22)
            ->andReturn(new RevisionDesignacion('SOLICITADO', null));
        $service->shouldReceive('guardarDecisionAsignacionDetalle')->once()
            ->with(22, 901, 'APROBADA', '')
            ->andReturn(['id_detalle' => 901, 'estado' => 'APROBADA', 'observacion' => null]);

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones/22?gestion=2026')
            ->assertOk()
            ->assertSee('RECHAZADA')
            ->assertSee('Corregir distribución de horas')
            ->assertSee('Si la dejas vacía, se eliminará la observación anterior.')
            ->assertSee('class="badge detail-status-badge"', false)
            ->assertSee('La asignación ha sido rechazada.');

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->postJson(route('vicerrectorado.designaciones.decisiones', ['id' => 22, 'gestion' => '2026']), [
                'filas' => [901],
                'estado' => 'APROBADA',
                'observacion' => '',
            ])
            ->assertOk()
            ->assertExactJson(['actualizadas' => [901], 'fallidas' => [], 'estado_designacion' => 'APROBADO']);
    }

    public function test_el_detalle_muestra_por_separado_el_motivo_general_vigente(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('obtenerUniversidad')->once()
            ->with(22, '2026')
            ->andReturn($this->fila('MED', 'MEDICINA', 'OBSERVADA'));
        $service->shouldReceive('detallar')->once()->with(22)->andReturn(new Collection);
        $service->shouldReceive('listarDecisionesAsignacionDetalle')->once()
            ->with(22)
            ->andReturn(new Collection);
        $service->shouldReceive('obtenerRevisionDesignacion')->once()
            ->with(22)
            ->andReturn(new RevisionDesignacion('OBSERVADA', 'Completar información'));

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones/22?gestion=2026')
            ->assertOk()
            ->assertSee('OBSERVADA')
            ->assertSee('Observación de Vicerrectorado:')
            ->assertSee('Completar información');
    }

    public function test_el_fallo_al_consultar_revision_general_no_oculta_las_decisiones_por_fila(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('obtenerUniversidad')->once()
            ->with(22, '2026')
            ->andReturn($this->fila('MED', 'MEDICINA', 'SOLICITADO'));
        $service->shouldReceive('detallar')->once()->with(22)->andReturn(new Collection([[
            'id' => 901,
            'docente_nombre' => 'Docente de prueba',
            'ci' => 'CI-DEMO',
            'materia_sigla' => 'MAT-101',
            'materia_nombre' => 'Materia de prueba',
            'grupo_id' => 1,
            'horas_teoricas' => 2,
            'horas_practicas' => 1,
            'horas_laboratorio' => 0,
        ]]));
        $service->shouldReceive('listarDecisionesAsignacionDetalle')->once()
            ->with(22)
            ->andReturn(new Collection([['id_detalle' => 901, 'estado' => 'APROBADA', 'observacion' => null]]));
        $service->shouldReceive('obtenerRevisionDesignacion')->once()
            ->with(22)
            ->andThrow(new \RuntimeException('Consulta general no disponible'));

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones/22?gestion=2026')
            ->assertOk()
            ->assertSee('No fue posible consultar el estado general.')
            ->assertSee('decisionesDisponibles: true', false)
            ->assertSee('estadosDisponibles: true', false)
            ->assertSee('Aprobar designación')
            ->assertSee('x-text="etiquetaEstadoFila(901)">APROBADA</strong>', false)
            ->assertSee(':disabled="!decisionesDisponibles || revisionCerrada', false)
            ->assertDontSee('No fue posible habilitar las decisiones.');
    }

    public function test_si_no_se_pueden_leer_estados_puede_guardar_y_muestra_sin_estado(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('obtenerUniversidad')->once()
            ->with(22, '2026')
            ->andReturn($this->fila('MED', 'MEDICINA', 'SOLICITADO'));
        $service->shouldReceive('detallar')->once()->with(22)->andReturn(new Collection([[
            'id' => 901,
            'docente_nombre' => 'Docente de prueba',
            'ci' => 'CI-DEMO',
            'materia_sigla' => 'MAT-101',
            'materia_nombre' => 'Materia de prueba',
            'grupo_id' => 1,
            'horas_teoricas' => 2,
            'horas_practicas' => 1,
            'horas_laboratorio' => 0,
        ]]));
        $service->shouldReceive('listarDecisionesAsignacionDetalle')->once()
            ->with(22)
            ->andThrow(new \RuntimeException('Lectura de estados no disponible'));
        $service->shouldReceive('obtenerRevisionDesignacion')->once()
            ->with(22)
            ->andReturn(new RevisionDesignacion('SOLICITADO', null));

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones/22?gestion=2026')
            ->assertOk()
            ->assertSee('decisionesDisponibles: true', false)
            ->assertSee('estadosDisponibles: false', false)
            ->assertSee('Aprobar designación')
            ->assertSee('>Sin estado</strong>', false)
            ->assertSee('No fue posible consultar los estados de las asignaciones.')
            ->assertSee(':disabled="!decisionesDisponibles || revisionCerrada', false)
            ->assertDontSee('No fue posible habilitar las decisiones.');
    }

    public function test_vicerrectorado_puede_generar_el_pdf_de_una_designacion(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('obtenerUniversidad')->once()
            ->with(22, '2026')
            ->andReturn($this->fila('MED', 'MEDICINA', 'SOLICITADO'));
        $service->shouldReceive('detallar')->once()->with(22)->andReturn(new Collection);

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->get('/vicerrectorado/designaciones/22/pdf?gestion=2026')
            ->assertOk()
            ->assertHeader('content-type', 'application/pdf');
    }

    public function test_vicerrectorado_guarda_las_filas_validas_y_reporta_las_fallidas(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('obtenerUniversidad')->twice()
            ->with(22, '2026')
            ->andReturn($this->fila('MED', 'MEDICINA', 'SOLICITADO'));
        $service->shouldReceive('guardarDecisionAsignacionDetalle')->once()
            ->with(22, 901, 'RECHAZADA', 'Observación de prueba')
            ->andReturn(['id_detalle' => 901, 'estado' => 'RECHAZADA', 'observacion' => 'Observación de prueba']);
        $service->shouldReceive('guardarDecisionAsignacionDetalle')->once()
            ->with(22, 902, 'RECHAZADA', 'Observación de prueba')
            ->andThrow(new \RuntimeException('Fallo de prueba'));

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->postJson(route('vicerrectorado.designaciones.decisiones', ['id' => 22, 'gestion' => '2026']), [
                'filas' => [901, 902],
                'estado' => 'RECHAZADA',
                'observacion' => 'Observación de prueba',
            ])
            ->assertOk()
            ->assertExactJson(['actualizadas' => [901], 'fallidas' => [902], 'estado_designacion' => 'SOLICITADO']);
    }

    public function test_vicerrectorado_puede_observar_la_designacion_con_filas_pendientes(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('obtenerUniversidad')->once()
            ->with(22, '2026')
            ->andReturn($this->fila('MED', 'MEDICINA', 'SOLICITADO'));
        $service->shouldReceive('guardarRevisionDesignacion')->once()
            ->with(22, 'OBSERVADA', 'Completar información')
            ->andReturn(['estado' => 'OBSERVADA', 'observacion' => 'Completar información']);

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->postJson(route('vicerrectorado.designaciones.estado', ['id' => 22, 'gestion' => '2026']), [
                'estado' => 'OBSERVADA',
                'observacion' => 'Completar información',
            ])
            ->assertOk()
            ->assertExactJson([
                'estado_designacion' => 'OBSERVADA',
                'observacion_revision' => 'Completar información',
                'message' => 'Operación confirmada.',
            ]);
    }

    public function test_vicerrectorado_puede_aprobar_designacion_sin_decidir_filas_y_limpiar_motivo(): void
    {
        $service = $this->mockService();
        $service->shouldReceive('obtenerUniversidad')->once()
            ->with(22, '2026')
            ->andReturn($this->fila('MED', 'MEDICINA', 'OBSERVADA'));
        $service->shouldReceive('guardarRevisionDesignacion')->once()
            ->with(22, 'APROBADO', null)
            ->andReturn(['estado' => 'APROBADO', 'observacion' => null]);

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->postJson(route('vicerrectorado.designaciones.estado', ['id' => 22, 'gestion' => '2026']), [
                'estado' => 'APROBADO',
            ])
            ->assertOk()
            ->assertJsonPath('estado_designacion', 'APROBADO')
            ->assertJsonPath('observacion_revision', null);
    }

    public function test_observar_designacion_exige_un_motivo(): void
    {
        $service = $this->mockService();

        $this->actingAs($this->usuario(User::ROL_VICERRECTORADO))
            ->postJson(route('vicerrectorado.designaciones.estado', ['id' => 22, 'gestion' => '2026']), [
                'estado' => 'OBSERVADA',
            ])
            ->assertUnprocessable()
            ->assertExactJson(['message' => 'No fue posible completar la operación.']);

        $service->shouldNotReceive('guardarRevisionDesignacion');
    }

    public function test_director_no_puede_guardar_decisiones_de_vicerrectorado(): void
    {
        $this->actingAs($this->usuario(User::ROL_DIRECTOR_CARRERA))
            ->postJson(route('vicerrectorado.designaciones.decisiones', ['id' => 22, 'gestion' => '2026']), [
                'filas' => [901],
                'estado' => 'APROBADA',
            ])
            ->assertForbidden();
    }

    public function test_director_no_puede_cambiar_el_estado_general(): void
    {
        $this->actingAs($this->usuario(User::ROL_DIRECTOR_CARRERA))
            ->postJson(route('vicerrectorado.designaciones.estado', ['id' => 22, 'gestion' => '2026']), [
                'estado' => 'APROBADO',
            ])
            ->assertForbidden();
    }

    private function mockService(): MockInterface
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $this->app->instance(JachasunDesignacionesService::class, $service);

        return $service;
    }

    private function usuario(string $rol): DemoUser
    {
        $usuario = new DemoUser;
        $usuario->demo_id = 'test-'.$rol;
        $usuario->name = 'Usuario de prueba';
        $usuario->email = 'usuario-'.$rol.'@example.test';
        $usuario->rol = $rol;

        return $usuario;
    }

    /** @return array<string, int|string|null> */
    private function fila(string $codigo, string $nombre, string $estado): array
    {
        return [
            'id' => 22,
            'programa_codigo' => $codigo,
            'programa_nombre' => $nombre,
            'detalle' => 'SEMESTRAL 1/2026',
            'fecha' => '2026-09-20',
            'gestion' => '2026',
            'periodo' => '1',
            'observacion' => null,
            'estado' => $estado,
        ];
    }
}
