<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use App\Services\Institutional\InstitutionalDesignacionesService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class InstitutionalDesignacionesEndpointTest extends TestCase
{
    public function test_director_consulta_solo_el_programa_de_su_carrera(): void
    {
        config(['institutional.enabled' => true]);
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();

        $client = Mockery::mock(InstitutionalDesignacionesService::class);
        $client->shouldReceive('listar')
            ->once()
            ->with('INF', '2023', '1')
            ->andReturn(collect([[
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
        $this->app->instance(InstitutionalDesignacionesService::class, $client);

        $this->actingAs($director)
            ->getJson('/institucional/designaciones?programa=INF&gestion=2023&periodo=1')
            ->assertOk()
            ->assertJsonPath('programa', 'INF')
            ->assertJsonPath('items.0.id', 2117)
            ->assertJsonPath('items.0.programa_codigo', 'INF');
    }

    public function test_director_puede_consultar_toda_su_carrera_con_ceros(): void
    {
        config(['institutional.enabled' => true]);
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();

        $client = Mockery::mock(InstitutionalDesignacionesService::class);
        $client->shouldReceive('listar')
            ->once()
            ->with('INF', '0', '0')
            ->andReturn(collect());
        $this->app->instance(InstitutionalDesignacionesService::class, $client);

        $this->actingAs($director)
            ->getJson('/institucional/designaciones?programa=INF&gestion=0&periodo=0')
            ->assertOk()
            ->assertJsonPath('programa', 'INF')
            ->assertJsonPath('gestion', '0')
            ->assertJsonPath('periodo', '0');
    }

    public function test_director_no_puede_consultar_otro_programa(): void
    {
        config(['institutional.enabled' => true]);
        $carrera = Carrera::factory()->create(['sigla' => 'INF']);
        $director = User::factory()->director($carrera)->create();

        $client = Mockery::mock(InstitutionalDesignacionesService::class);
        $client->shouldNotReceive('listar');
        $this->app->instance(InstitutionalDesignacionesService::class, $client);

        $this->actingAs($director)
            ->getJson('/institucional/designaciones?programa=CIV&gestion=2023&periodo=1')
            ->assertForbidden();
    }

    public function test_vicerrectorado_puede_consultar_toda_la_universidad(): void
    {
        config(['institutional.enabled' => true]);
        $vicerrectorado = User::factory()->vicerrectorado()->create();

        $client = Mockery::mock(InstitutionalDesignacionesService::class);
        $client->shouldReceive('listar')
            ->once()
            ->with('UATF', '2024', '1')
            ->andReturn(new Collection);
        $this->app->instance(InstitutionalDesignacionesService::class, $client);

        $this->actingAs($vicerrectorado)
            ->getJson('/institucional/designaciones?programa=UATF&gestion=2024&periodo=1')
            ->assertOk()
            ->assertJsonPath('programa', 'UATF')
            ->assertJsonPath('items', []);
    }
}
