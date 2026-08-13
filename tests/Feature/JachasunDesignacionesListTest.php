<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\Gestion;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class JachasunDesignacionesListTest extends TestCase
{
    public function test_lista_principal_consulta_la_carrera_con_ceros_y_adapta_la_tabla_actual(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->crearPropuestaLocal($director, 'Propuesta local que no debe aparecer');
        $this->mockJachasunService(new Collection([[
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

    public function test_estado_jachasun_desconocido_se_muestra_sin_inventar_equivalencia(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $this->mockJachasunService(new Collection([[
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

    public function test_tres_carreras_reciben_sus_designaciones_con_ceros(): void
    {
        $carreras = collect([
            ['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica'],
            ['sigla' => 'CIV', 'nombre' => 'Ingenieria Civil'],
            ['sigla' => 'IND', 'nombre' => 'Ingenieria Industrial'],
        ])->map(fn (array $datos): array => [
            'carrera' => Carrera::factory()->create($datos),
        ])->map(function (array $contexto): array {
            $contexto['director'] = User::factory()->director($contexto['carrera'])->create();

            return $contexto;
        });

        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')
            ->times(3)
            ->withArgs(fn (string $sigla, string $gestion, string $periodo): bool => in_array($sigla, ['INF', 'CIV', 'IND'], true)
                && $gestion === '0'
                && $periodo === '0')
            ->andReturnUsing(fn (string $sigla): Collection => new Collection([[
                'id' => match ($sigla) {
                    'INF' => 101,
                    'CIV' => 202,
                    'IND' => 303,
                },
                'programa_codigo' => $sigla,
                'programa_nombre' => $sigla,
                'detalle' => 'DESIGNACIONES '.$sigla,
                'fecha' => null,
                'gestion' => '2024',
                'periodo' => '1',
                'observacion' => null,
                'estado' => 'SOLICITADO',
            ]]));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        foreach ($carreras as $contexto) {
            $this->actingAs($contexto['director'])
                ->get('/designaciones')
                ->assertOk()
                ->assertSee('DESIGNACIONES '.$contexto['carrera']->sigla, false)
                ->assertSee((string) match ($contexto['carrera']->sigla) {
                    'INF' => 101,
                    'CIV' => 202,
                    'IND' => 303,
                });
        }
    }

    public function test_fallo_jachasun_bloquea_la_lista_con_mensaje_seguro(): void
    {
        Log::spy();
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')
            ->once()
            ->with('INF', '0', '0')
            ->andThrow(new RuntimeException('SQL password=secret SELECT private_rows'));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/designaciones')
            ->assertStatus(503)
            ->assertSee('No fue posible consultar las designaciones institucionales.')
            ->assertDontSee('secret')
            ->assertDontSee('private_rows');

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Lista Jachasun no disponible.', ['exception' => RuntimeException::class]);
    }

    private function mockJachasunService(Collection $items): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')
            ->once()
            ->with('INF', '0', '0')
            ->andReturn($items);
        $this->app->instance(JachasunDesignacionesService::class, $service);
    }

    private function crearPropuestaLocal(User $director, string $descripcion): Propuesta
    {
        Gestion::query()->update(['es_actual' => false]);
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
