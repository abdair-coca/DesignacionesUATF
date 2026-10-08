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
        $this->mockService()->shouldReceive('listarPaginado')->once()
            ->with('INF', '0', '0', 1, 10)
            ->andReturn(['items' => collect([$this->fila(2117, 'SEMESTRAL 1/2023')]), 'total' => 1]);

        $this->actingAs($director)->get('/designaciones')->assertOk()
            ->assertSee(asset('resources/assets/css/shared/designaciones-lista.css'), false)
            ->assertSee(asset('resources/assets/css/shared/modales.css'), false)
            ->assertSee(asset('resources/assets/js/designaciones/lista.js'), false)
            ->assertSee('Fecha', false)
            ->assertSee('2117')
            ->assertSee('SEMESTRAL 1/2023')
            ->assertSee('SOLICITADO')
            ->assertSee('/designaciones/2117', false)
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
        $this->mockService()->shouldReceive('listarPaginado')->once()
            ->with('INF', '0', '0', 1, 10)
            ->andThrow(new RuntimeException('SQL password=secret SELECT private_rows'));

        $this->actingAs($director)->get('/designaciones')->assertStatus(503)
            ->assertSee('No fue posible consultar las designaciones.')
            ->assertDontSee('secret')
            ->assertDontSee('private_rows');
        Log::shouldHaveReceived('warning')->once()->with('Lista Jachasun no disponible.', ['exception' => RuntimeException::class]);
    }

    public function test_lista_pagina_de_10_con_links_de_navegacion(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listarPaginado')->once()
            ->with('INF', '0', '0', 1, 10)
            ->andReturn(['items' => collect(range(1, 10))->map(fn (int $id): array => $this->fila($id, "DETALLE $id")), 'total' => 25]);

        $respuesta = $this->actingAs($director)->get('/designaciones')->assertOk()
            ->assertSee('Página 1 de 3')
            ->assertSee('Siguiente')
            ->assertSee('Anterior')
            ->assertSee('Mostrando 1–10 de 25 designaciones');

        $respuesta->assertSee('DETALLE 10')
            ->assertDontSee('DETALLE 11');
    }

    public function test_segunda_pagina_muestra_las_siguientes_diez(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listarPaginado')->once()
            ->with('INF', '0', '0', 2, 10)
            ->andReturn(['items' => collect(range(11, 20))->map(fn (int $id): array => $this->fila($id, "DETALLE $id")), 'total' => 25]);

        $respuesta = $this->actingAs($director)->get('/designaciones?page=2')->assertOk()
            ->assertSee('Página 2 de 3')
            ->assertSee('Mostrando 11–20 de 25 designaciones');

        $respuesta->assertSee('DETALLE 11')
            ->assertSee('DETALLE 20')
            ->assertDontSee('DETALLE 10')
            ->assertDontSee('DETALLE 21');
    }

    public function test_lista_busca_por_descripcion_y_conserva_el_termino(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockService()->shouldReceive('listarPaginado')->once()
            ->with('INF', '0', '0', 1, 10, 'SEMESTRAL')
            ->andReturn(['items' => collect([$this->fila(2117, 'SEMESTRAL 1/2023')]), 'total' => 1]);

        $this->actingAs($director)->get('/designaciones?search=SEMESTRAL')->assertOk()
            ->assertSee('SEMESTRAL 1/2023')
            ->assertSee('name="search"', false)
            ->assertSee('value="SEMESTRAL"', false);
    }

    public function test_pagina_fuera_de_rango_se_ajusta_a_la_ultima(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = $this->mockService();
        $service->shouldReceive('listarPaginado')->once()
            ->with('INF', '0', '0', 99, 10)
            ->andReturn(['items' => collect(), 'total' => 25]);
        $service->shouldReceive('listarPaginado')->once()
            ->with('INF', '0', '0', 3, 10)
            ->andReturn(['items' => collect(range(21, 25))->map(fn (int $id): array => $this->fila($id, "DETALLE $id")), 'total' => 25]);

        $this->actingAs($director)->get('/designaciones?page=99')->assertOk()
            ->assertSee('Página 3 de 3')
            ->assertSee('Mostrando 21–25 de 25 designaciones')
            ->assertSee('DETALLE 21')
            ->assertSee('DETALLE 25')
            ->assertDontSee('DETALLE 20');
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
