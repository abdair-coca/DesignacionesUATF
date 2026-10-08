<?php

namespace Tests\Feature;

use App\Auth\Demo\DemoUser;
use App\Models\Carrera;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class JachasunDesignacionesEscrituraTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_director_crea_una_designacion_nueva(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('contextoActual')->once()->with('INF')->andReturn(['gestion' => '2026', 'periodo' => '1']);
        $service->shouldReceive('insertar')->once()
            ->with('INF', '2026-08-31 12:00', '2026', '1', 'DESIGNACION')
            ->andReturn([
                'fecha' => '2026-08-31 12:00:00',
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => 'DESIGNACION',
            ]);

        $this->actingAs($director)
            ->post('/designaciones', [
                'fecha' => '2026-08-31 12:00',
                'gestion' => '2026',
                'periodo' => '1',
                'obs' => 'DESIGNACION',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Designación creada correctamente.');
    }

    public function test_crear_importando_de_otra_gestion_llama_a_copiar(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('contextoActual')->once()->with('INF')->andReturn(['gestion' => '2026', 'periodo' => '1']);
        $service->shouldReceive('copiar')->once()
            ->with('INF', 2117, '2026', '1', 'IMPORTADA')
            ->andReturnNull();

        $this->actingAs($director)
            ->post('/designaciones', [
                'gestion' => '2026',
                'periodo' => '1',
                'obs' => 'IMPORTADA',
                'importar_desde' => '2117',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Designación importada desde una gestión anterior.');
    }

    public function test_crear_importando_con_fallo_de_jachasun_no_filtra_detalles(): void
    {
        Log::spy();
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('contextoActual')->once()->with('INF')->andReturn(['gestion' => '2026', 'periodo' => '1']);
        $service->shouldReceive('copiar')->once()
            ->andThrow(new RuntimeException('password=secret private_rows'));

        $this->actingAs($director)
            ->post('/designaciones', [
                'gestion' => '2026',
                'periodo' => '1',
                'importar_desde' => '2117',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'No fue posible crear la designación.')
            ->assertSessionMissing('success');
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_director_actualiza_la_cabecera_de_su_designacion(): void
    {
        $director = $this->demoDirector();
        $service = $this->mockService();
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion('SOLICITADO')]));
        $service->shouldReceive('actualizar')->once()
            ->with(2117, 'INF', '2026-08-31 12:00', '2026', '1', 'OBS NUEVA')
            ->andReturn([
                'fecha' => '2026-08-31 12:00:00',
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => 'OBS NUEVA',
            ]);

        $this->actingAs($director)
            ->post('/designaciones/2117', ['fecha' => '2026-08-31 12:00', 'obs' => 'OBS NUEVA'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Designación actualizada correctamente.');
    }

    public function test_actualizar_sin_fecha_conserva_la_fecha_actual(): void
    {
        $director = $this->demoDirector();
        $service = $this->mockService();
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion('SOLICITADO')]));
        $service->shouldReceive('actualizar')->once()
            ->with(2117, 'INF', '2026-08-31 12:00:00', '2026', '1', 'OBS SIN FECHA')
            ->andReturn([
                'fecha' => '2026-08-31 12:00:00',
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => 'OBS SIN FECHA',
            ]);

        $this->actingAs($director)
            ->post('/designaciones/2117', ['fecha' => '', 'obs' => 'OBS SIN FECHA'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Designación actualizada correctamente.');
    }

    public function test_director_no_puede_actualizar_una_designacion_aprobada(): void
    {
        $director = $this->demoDirector();
        $service = $this->mockService();
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion('APROBADO')]));
        $service->shouldNotReceive('actualizar');

        $this->actingAs($director)
            ->post('/designaciones/2117', ['fecha' => '', 'obs' => 'CAMBIO'])
            ->assertRedirect()
            ->assertSessionHas('error', 'La designación aprobada no se puede modificar.');
    }

    public function test_crear_sin_fecha_envia_fecha_vacia_y_la_bd_la_pone_automatica(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('contextoActual')->once()->with('INF')->andReturn(['gestion' => '2026', 'periodo' => '1']);
        $service->shouldReceive('insertar')->once()
            ->with('INF', '', '2026', '1', 'SIN FECHA')
            ->andReturn([
                'fecha' => '2026-09-03 10:00:00',
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => 'SIN FECHA',
            ]);

        $this->actingAs($director)
            ->post('/designaciones', [
                'gestion' => '2026',
                'periodo' => '1',
                'obs' => 'SIN FECHA',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Designación creada correctamente.');
    }

    public function test_crear_vacia_completa_el_contexto_si_no_se_envia(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('contextoActual')->once()->with('INF')->andReturn(['gestion' => '2026', 'periodo' => '2']);
        $service->shouldReceive('insertar')->once()
            ->with('INF', '', '2026', '2', 'SIN CONTEXTO')
            ->andReturn([]);

        $this->actingAs($director)
            ->post('/designaciones', ['obs' => 'SIN CONTEXTO'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Designación creada correctamente.');
    }

    public function test_copiar_completa_el_contexto_si_no_se_envia(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('contextoActual')->once()->with('INF')->andReturn(['gestion' => '2026', 'periodo' => '2']);
        $service->shouldReceive('copiar')->once()
            ->with('INF', 2117, '2026', '2', '')
            ->andReturnNull();

        $this->actingAs($director)
            ->post('/designaciones', ['importar_desde' => '2117'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Designación importada desde una gestión anterior.');
    }

    public function test_actualizar_sin_fecha_existente_envia_fecha_vacia_para_la_bd(): void
    {
        $director = $this->demoDirector();
        $service = $this->mockService();
        $designacion = $this->designacion('SOLICITADO');
        $designacion['fecha'] = null;
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$designacion]));
        $service->shouldReceive('actualizar')->once()
            ->with(2117, 'INF', '', '2026', '1', 'OBS NUEVA')
            ->andReturn([
                'fecha' => '2026-09-03 10:00:00',
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => 'OBS NUEVA',
            ]);

        $this->actingAs($director)
            ->post('/designaciones/2117', ['obs' => 'OBS NUEVA'])
            ->assertRedirect()
            ->assertSessionHas('success', 'Designación actualizada correctamente.');
    }

    public function test_crear_valida_gestion_y_periodo(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldNotReceive('insertar');
        $service->shouldNotReceive('copiar');

        $this->actingAs($director)
            ->from('/designaciones')
            ->post('/designaciones', ['gestion' => 'abc', 'periodo' => ''])
            ->assertSessionHasErrors(['gestion', 'periodo'])
            ->assertRedirect('/designaciones');
    }

    public function test_ver_detalle_pasa_el_catalogo_de_materias_a_la_vista(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect([
            ['id' => 2117, 'programa_codigo' => 'INF', 'programa_nombre' => 'INGENIERIA INFORMATICA', 'detalle' => 'SEMESTRAL 1/2026', 'fecha' => null, 'gestion' => '2026', 'periodo' => '1', 'observacion' => null, 'estado' => 'SOLICITADO'],
        ]));
        $service->shouldReceive('detallar')->once()->with(2117)->andReturn(collect([
            ['id' => 1, 'docente_id' => 974, 'ci' => '5107607', 'docente_nombre' => 'Lic. A', 'materia_id' => 11233, 'materia_sigla' => 'INF122', 'materia_nombre' => 'FUND', 'grupo_id' => 2, 'horas_teoricas' => 3, 'horas_practicas' => 3, 'horas_laboratorio' => 0],
            ['id' => 2, 'docente_id' => 974, 'ci' => '5107607', 'docente_nombre' => 'Lic. A', 'materia_id' => 11234, 'materia_sigla' => 'INF123', 'materia_nombre' => 'OTRA', 'grupo_id' => 1, 'horas_teoricas' => 2, 'horas_practicas' => 0, 'horas_laboratorio' => 0],
        ]));
        $service->shouldReceive('ofertaMaterias')->once()->with('INF', '2026', '1')->andReturn(collect());

        $response = $this->actingAs($director)->get('/designaciones/2117');

        $response->assertOk();
        $response->assertSee("JSON.parse('", false);
        $materias = $response->viewData('materiasDisponibles');
        $this->assertCount(2, $materias);
        $this->assertSame('INF122', $materias->first()['sigla']);
        $this->assertSame('INF123', $materias->last()['sigla']);
    }

    public function test_director_actualiza_una_fila_de_detalle_de_su_designacion(): void
    {
        $director = $this->demoDirector();
        $service = $this->mockService();
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion('SOLICITADO')]));
        $service->shouldReceive('guardarDetalle')->once()
            ->with(2117, 'INF', 80356, 974, 11233, 2, 3, 3, 0)
            ->andReturn(collect([
                ['id' => 22325, 'docente_id' => 974, 'ci' => '5107607', 'docente_nombre' => 'Lic. ASTETE ARROYO, MIGUEL ANGEL', 'materia_id' => 11233, 'materia_sigla' => 'INF122', 'materia_nombre' => 'FUNDAMENTOS DE TECNOLOGIAS DE INFORMACION', 'grupo_id' => 2, 'horas_teoricas' => 3, 'horas_practicas' => 3, 'horas_laboratorio' => 0],
            ]));

        $this->actingAs($director)
            ->post('/designaciones/2117/detalle', [
                'id_detalle' => '80356',
                'id_docente' => '974',
                'id_materia' => '11233',
                'id_grupo' => '2',
                'hrs_teoria' => '3',
                'hrs_practica' => '3',
                'hrs_laboratorio' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('success', 'Asignación actualizada correctamente.');
    }

    public function test_director_no_puede_modificar_asignaciones_de_una_designacion_aprobada(): void
    {
        $director = $this->demoDirector();
        $service = $this->mockService();
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion('APROBADO')]));
        $service->shouldNotReceive('guardarDetalle');

        $this->actingAs($director)
            ->post('/designaciones/2117/detalle', [
                'id_detalle' => '80356',
                'id_docente' => '974',
                'id_materia' => '11233',
                'id_grupo' => '2',
                'hrs_teoria' => '3',
                'hrs_practica' => '3',
                'hrs_laboratorio' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'La designación aprobada no se puede modificar.');
    }

    public function test_vicerrectorado_no_puede_reasignar_una_fila(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $usuario = User::factory()->create([
            'rol' => User::ROL_VICERRECTORADO,
            'carrera_id' => null,
        ]);
        $this->mockService()->shouldNotReceive('guardarDetalle');

        $this->actingAs($usuario)
            ->post('/designaciones/2117/detalle', [
                'id_detalle' => '80356',
                'id_docente' => '974',
                'id_materia' => '11233',
                'id_grupo' => '2',
                'hrs_teoria' => '3',
                'hrs_practica' => '3',
                'hrs_laboratorio' => '0',
            ])
            ->assertForbidden();
    }

    public function test_actualizar_detalle_con_fallo_de_jachasun_no_filtra_detalles(): void
    {
        Log::spy();
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion('SOLICITADO')]));
        $service->shouldReceive('guardarDetalle')->once()
            ->andThrow(new RuntimeException('password=secret private_rows'));

        $this->actingAs($director)
            ->post('/designaciones/2117/detalle', [
                'id_detalle' => '80356',
                'id_docente' => '974',
                'id_materia' => '11233',
                'id_grupo' => '2',
                'hrs_teoria' => '3',
                'hrs_practica' => '3',
                'hrs_laboratorio' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'No fue posible actualizar la asignación.')
            ->assertSessionMissing('success');
        Log::shouldHaveReceived('warning')->once();
    }

    public function test_fallo_de_actualizacion_no_registra_el_mensaje_externo(): void
    {
        Log::spy();
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion('SOLICITADO')]));
        $service->shouldReceive('guardarDetalle')->once()
            ->andThrow(new RuntimeException('password=secret private_rows'));

        $this->actingAs($director)
            ->post('/designaciones/2117/detalle', [
                'id_detalle' => '80356',
                'id_docente' => '974',
                'id_materia' => '11233',
                'id_grupo' => '2',
                'hrs_teoria' => '3',
                'hrs_practica' => '3',
                'hrs_laboratorio' => '0',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'No fue posible actualizar la asignación.');

        Log::shouldHaveReceived('warning')->once()->with(
            'Actualizacion de detalle no disponible.',
            ['exception' => RuntimeException::class],
        );
    }

    public function test_no_crea_una_designacion_fuera_del_contexto_actual(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('contextoActual')->once()->with('INF')->andReturn(['gestion' => '2026', 'periodo' => '1']);
        $service->shouldNotReceive('insertar');
        $service->shouldNotReceive('copiar');

        $this->actingAs($director)
            ->post('/designaciones', [
                'gestion' => '2025',
                'periodo' => '1',
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'No fue posible crear la designación.');
    }

    public function test_actualizar_detalle_valida_los_parametros(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion('SOLICITADO')]));
        $service->shouldNotReceive('guardarDetalle');

        $this->actingAs($director)
            ->from('/designaciones/2117')
            ->post('/designaciones/2117/detalle', [
                'id_detalle' => '0',
                'id_docente' => '0',
                'id_materia' => '0',
                'id_grupo' => '0',
                'hrs_teoria' => '-1',
                'hrs_practica' => '',
                'hrs_laboratorio' => '',
            ])
            ->assertSessionHasErrors(['id_docente', 'id_materia', 'id_grupo', 'hrs_teoria', 'hrs_practica', 'hrs_laboratorio'])
            ->assertRedirect('/designaciones/2117');
    }

    private function mockService(): MockInterface
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $this->app->instance(JachasunDesignacionesService::class, $service);

        return $service;
    }

    private function demoDirector(): DemoUser
    {
        $director = new DemoUser;
        $director->demo_id = 'director-test';
        $director->rol = User::ROL_DIRECTOR_CARRERA;
        $director->setRelation('carrera', (object) ['sigla' => 'INF']);

        return $director;
    }

    /** @return array<string, int|string|null> */
    private function designacion(string $estado): array
    {
        return [
            'id' => 2117,
            'programa_codigo' => 'INF',
            'programa_nombre' => 'INGENIERIA INFORMATICA',
            'detalle' => 'SEMESTRAL 1/2026',
            'fecha' => '2026-08-31 12:00:00',
            'gestion' => '2026',
            'periodo' => '1',
            'observacion' => 'DESIGNACION',
            'estado' => $estado,
        ];
    }
}
