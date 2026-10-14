<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Illuminate\Support\Facades\Log;
use Mockery;
use Mockery\MockInterface;
use RuntimeException;
use Tests\TestCase;

class JachasunDesignacionesListTest extends TestCase
{
    public function test_lista_principal_consulta_la_carrera_y_muestra_enlace_al_detalle(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andReturn(collect([$this->fila(2117, 'SEMESTRAL 1/2023')]));

        $this->actingAs($director)->get('/designaciones')->assertOk()
            ->assertSee(asset('resources/assets/css/shared/designaciones-lista.css'), false)
            ->assertSee(asset('resources/assets/css/shared/modales.css'), false)
            ->assertSee(asset('resources/assets/js/designaciones/lista.js'), false)
            ->assertSee('Fecha', false)
            ->assertSee('2117')
            ->assertSee('SEMESTRAL 1/2023')
            ->assertSee('SOLICITADO')
            ->assertSee('designaciones\/__ID__', false)
            ->assertSee('Detalles', false)
            ->assertSee('Imprimir', false)
            ->assertDontSee('Copiar', false)
            ->assertDontSee('Nueva Propuesta', false)
            ->assertDontSee('Enviar', false);
    }

    public function test_formulario_de_creacion_inicia_con_el_contexto_mas_reciente(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andReturn(collect([
                $this->fila(2117, 'SEMESTRAL 1/2023'),
                [
                    ...$this->fila(2138, 'SEMESTRAL 2/2026'),
                    'gestion' => '2026',
                    'periodo' => '2',
                ],
            ]));

        $response = $this->actingAs($director)->get('/designaciones')->assertOk();

        $this->assertSame(['gestion' => '2026', 'periodo' => '2'], $response->viewData('contextoActual'));
        $response->assertSee('class="designacion-confirmacion-modal"', false);
        $response->assertSee(asset('resources/assets/css/shared/modales.css'), false);
    }

    public function test_error_de_lista_no_menciona_el_proveedor_tecnico(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andThrow(new RuntimeException('detalle tecnico no visible'));

        $this->actingAs($director)->get('/designaciones')->assertStatus(503)
            ->assertSee('No fue posible consultar las designaciones.', false)
            ->assertDontSee('Jachasun', false)
            ->assertDontSee('detalle tecnico no visible', false);
    }

    public function test_fallo_jachasun_bloquea_la_lista_con_mensaje_seguro(): void
    {
        Log::spy();
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andThrow(new RuntimeException('SQL password=secret SELECT private_rows'));

        $this->actingAs($director)->get('/designaciones')->assertStatus(503)
            ->assertSee('No fue posible consultar las designaciones.')
            ->assertDontSee('secret')
            ->assertDontSee('private_rows');
        Log::shouldHaveReceived('warning')->once()->with('Lista no disponible.', ['exception' => RuntimeException::class]);
    }

    public function test_lista_entrega_todas_las_designaciones_y_pagina_en_el_navegador(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andReturn(collect(range(1, 25))->map(fn (int $id): array => $this->fila($id, "DETALLE $id")));

        $respuesta = $this->actingAs($director)->get('/designaciones')->assertOk()
            ->assertSee('Mostrando', false)
            ->assertSee('Siguiente')
            ->assertSee('Anterior')
            ->assertSee(asset('resources/assets/js/shared/designaciones-filtros.js'), false);

        $respuesta->assertSee('DETALLE 1')
            ->assertSee('DETALLE 25');
        $this->assertStringContainsString(
            'porPagina: 10',
            file_get_contents(resource_path('assets/js/shared/designaciones-filtros.js')),
        );
    }

    public function test_el_parametro_de_pagina_no_recorta_la_lista(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andReturn(collect(range(1, 25))->map(fn (int $id): array => $this->fila($id, "DETALLE $id")));

        $this->actingAs($director)->get('/designaciones?page=2')->assertOk()
            ->assertSee('DETALLE 11')
            ->assertSee('DETALLE 25');
    }

    public function test_lista_busca_por_descripcion_y_conserva_el_termino(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andReturn(collect([$this->fila(2117, 'SEMESTRAL 1/2023')]));

        $respuesta = $this->actingAs($director)->get('/designaciones?search=SEMESTRAL')->assertOk()
            ->assertSee('SEMESTRAL 1/2023')
            ->assertSee('name="search"', false);

        $this->assertSame('SEMESTRAL', $respuesta->viewData('busqueda'));
    }

    public function test_pagina_fuera_de_rango_no_rompe_la_lista(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andReturn(collect(range(1, 25))->map(fn (int $id): array => $this->fila($id, "DETALLE $id")));

        $this->actingAs($director)->get('/designaciones?page=99')->assertOk()
            ->assertSee('DETALLE 21')
            ->assertSee('DETALLE 25');
        $this->assertStringContainsString(
            'Math.min(this.totalPaginas(), Math.max(1, pagina))',
            file_get_contents(resource_path('assets/js/shared/designaciones-filtros.js')),
        );
    }

    public function test_rutas_heredadas_del_flujo_local_y_revision_no_se_registran(): void
    {
        $director = User::factory()->director(Carrera::factory()->create())->create();

        $this->actingAs($director)->get('/revisiones/pendientes')->assertNotFound();
        $this->actingAs($director)->get('/designaciones/1/importar')->assertNotFound();
    }

    private function mockService(): MockInterface
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $this->app->instance(JachasunDesignacionesService::class, $service);

        return $service;
    }

    /** @return array<string, int|string|null> */
    private function fila(int $id, string $detalle = 'SEMESTRAL 1/2023'): array
    {
        return [
            'id' => $id,
            'programa_codigo' => 'INF',
            'programa_nombre' => 'INGENIERIA INFORMATICA',
            'detalle' => $detalle,
            'fecha' => '2024-10-23',
            'gestion' => '2023',
            'periodo' => '1',
            'observacion' => 'MIGRADO',
            'estado' => 'SOLICITADO',
        ];
    }
}
