<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Gestion;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\User;
use App\Services\Institutional\InstitutionalDesignacionesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class InstitutionalDesignacionesListModeTest extends TestCase
{
    public function test_lista_principal_consulta_la_carrera_con_ceros_y_adapta_la_tabla_actual(): void
    {
        config(['institutional.enabled' => true]);
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->crearPropuestaLocal($director, 'Propuesta local que no debe aparecer');
        $this->mockInstitutionalService(new Collection([[
            'id' => 2117,
            'programa_codigo' => 'INF',
            'programa_nombre' => 'INGENIERIA INFORMATICA',
            'detalle' => 'SEMESTRAL 1/2023',
            'fecha' => '2024-10-23 15:07:01.38683',
            'gestion' => '2023',
            'periodo' => '1',
            'observacion' => 'MIGRADO',
            'estado' => 'SOLICITADO',
        ]]));

        $response = $this->actingAs($director)->get('/designaciones');

        $response->assertOk()
            ->assertSee('2117')
            ->assertSee('SEMESTRAL 1\\/2023', false)
            ->assertSee('2024-10-23 15:07:01.38683')
            ->assertSee('2023')
            ->assertSee('MIGRADO')
            ->assertSee('Oficial')
            ->assertSee('Fecha')
            ->assertSee('Observaci', false)
            ->assertDontSee('r_id_programa', false)
            ->assertDontSee('r_programa', false)
            ->assertDontSee('Propuesta local que no debe aparecer')
            ->assertSee('Nueva Propuesta', false)
            ->assertSee('Abrir', false)
            ->assertSee('Imprimir', false)
            ->assertSee('Enviar', false)
            ->assertSee('Retirar', false)
            ->assertSee('disabled', false);

        $this->assertFalse(file_exists(resource_path('views/designaciones/partials/lista-institucional.blade.php')));
    }

    public function test_lista_principal_consulta_jachasun_sin_modo_visual(): void
    {
        config(['institutional.enabled' => true]);
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(new Collection);
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($director)->get('/designaciones')->assertOk();
    }

    public function test_estado_institucional_desconocido_se_muestra_sin_inventar_equivalencia(): void
    {
        config(['institutional.enabled' => true]);
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockInstitutionalService(new Collection([[
            'id' => 1,
            'programa_codigo' => 'INF',
            'programa_nombre' => 'INGENIERIA INFORMATICA',
            'detalle' => 'REGISTRO',
            'fecha' => null,
            'gestion' => '2023',
            'periodo' => '1',
            'observacion' => null,
            'estado' => 'NUEVO_ESTADO',
        ]]));

        $this->actingAs($director)
            ->get('/designaciones')
            ->assertOk()
            ->assertSee('NUEVO_ESTADO')
            ->assertSee('estado_label', false);
    }

    public function test_fallo_institucional_bloquea_la_lista_con_mensaje_seguro(): void
    {
        config(['institutional.enabled' => true]);
        Log::spy();
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')
            ->once()
            ->with('INF', '0', '0')
            ->andThrow(new RuntimeException('SQL password=secret SELECT private_rows'));
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/designaciones')
            ->assertStatus(503)
            ->assertSee('No fue posible consultar las designaciones institucionales.')
            ->assertDontSee('secret')
            ->assertDontSee('private_rows');

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Lista institucional no disponible.', ['exception' => RuntimeException::class]);
    }

    public function test_integracion_deshabilitada_bloquea_la_lista(): void
    {
        config(['institutional.enabled' => false]);
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andThrow(new \LogicException);
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/designaciones')
            ->assertStatus(503)
            ->assertSee('No se puede cargar la lista institucional.');
    }

    private function mockInstitutionalService(Collection $items): void
    {
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')
            ->once()
            ->with('INF', '0', '0')
            ->andReturn($items);
        $this->app->instance(InstitutionalDesignacionesService::class, $service);
    }

    private function crearPropuestaLocal(User $director, string $descripcion): Propuesta
    {
        $gestion = Gestion::factory()->create(['es_actual' => true]);
        $periodo = Periodo::factory()->create();

        return Propuesta::create([
            'carrera_id' => $director->carrera_id,
            'gestion_id' => $gestion->id,
            'periodo_id' => $periodo->id,
            'creado_por' => $director->id,
            'descripcion' => $descripcion,
            'estado' => 'borrador',
        ]);
    }
}
