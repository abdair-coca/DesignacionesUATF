<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use App\Services\Institutional\InstitutionalDesignacionesService;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class InstitutionalDesignacionesScreenTest extends TestCase
{
    public function test_invitado_no_puede_abrir_la_consulta_institucional(): void
    {
        $this->get('/institucional/designaciones/consulta')
            ->assertRedirect('/login');
    }

    public function test_director_ve_la_pantalla_sin_resultados_iniciales(): void
    {
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();

        $this->actingAs($director)
            ->get('/institucional/designaciones/consulta')
            ->assertOk()
            ->assertSee('Consulta institucional')
            ->assertSee('INF')
            ->assertSee('No se ha ejecutado una consulta');
    }

    public function test_director_consulta_su_carrera_y_ve_las_nueve_columnas(): void
    {
        config(['institutional.enabled' => true]);
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')
            ->once()
            ->with('INF', '2023', '1')
            ->andReturn(new Collection([[
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
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/institucional/designaciones/consulta?programa=INF&gestion=2023&periodo=1')
            ->assertOk()
            ->assertSee('2117')
            ->assertSee('INGENIERIA INFORMATICA')
            ->assertSee('SEMESTRAL 1/2023')
            ->assertSee('MIGRADO')
            ->assertSee('SOLICITADO')
            ->assertSee('r_id', false)
            ->assertSee('r_id_programa', false)
            ->assertSee('r_programa', false)
            ->assertSee('r_detalle', false)
            ->assertSee('r_fecha', false)
            ->assertSee('r_id_gestion', false)
            ->assertSee('r_id_periodo', false)
            ->assertSee('r_obs', false)
            ->assertSee('r_estado', false);
    }

    public function test_director_puede_consultar_toda_su_carrera_con_ceros(): void
    {
        config(['institutional.enabled' => true]);
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect());
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/institucional/designaciones/consulta?programa=INF&gestion=0&periodo=0')
            ->assertOk()
            ->assertSee('No se encontraron registros');
    }

    public function test_director_no_puede_consultar_uatf_ni_otra_carrera_en_la_pantalla(): void
    {
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldNotReceive('listar');
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/institucional/designaciones/consulta?programa=UATF&gestion=2024&periodo=1')
            ->assertForbidden();

        $this->actingAs($director)
            ->get('/institucional/designaciones/consulta?programa=CIV&gestion=2024&periodo=1')
            ->assertForbidden();
    }

    public function test_vicerrectorado_puede_consultar_uatf(): void
    {
        config(['institutional.enabled' => true]);
        $vicerrectorado = User::factory()->vicerrectorado()->create();
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('UATF', '2024', '1')->andReturn(collect());
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($vicerrectorado)
            ->get('/institucional/designaciones/consulta?programa=UATF&gestion=2024&periodo=1')
            ->assertOk()
            ->assertSee('Consulta institucional')
            ->assertSee('No se encontraron registros');
    }

    public function test_integracion_deshabilitada_no_abre_la_conexion(): void
    {
        config(['institutional.enabled' => false]);
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $database = Mockery::mock(DatabaseManager::class);
        $database->shouldNotReceive('connection');
        $this->app->instance(DatabaseManager::class, $database);

        $this->actingAs($director)
            ->get('/institucional/designaciones/consulta?programa=INF&gestion=2023&periodo=1')
            ->assertOk()
            ->assertSee('La integración institucional no está habilitada.');
    }

    public function test_fallo_externo_muestra_mensaje_seguro(): void
    {
        config(['institutional.enabled' => true]);
        Log::spy();
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')
            ->once()
            ->andThrow(new RuntimeException('SQL password=super-secret SELECT internals'));
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/institucional/designaciones/consulta?programa=INF&gestion=2023&periodo=1')
            ->assertOk()
            ->assertSee('No fue posible consultar la fuente institucional.')
            ->assertDontSee('super-secret')
            ->assertDontSee('SELECT internals');

        Log::shouldHaveReceived('warning')
            ->once()
            ->with('Consulta institucional fallida.', ['exception' => RuntimeException::class]);
    }

    public function test_parametros_incompletos_o_invalidos_se_rechazan(): void
    {
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldNotReceive('listar');
        $this->app->instance(InstitutionalDesignacionesService::class, $service);

        $this->actingAs($director)
            ->get('/institucional/designaciones/consulta?programa=INF&gestion=23&periodo=1')
            ->assertSessionHasErrors('gestion');

        $this->actingAs($director)
            ->get('/institucional/designaciones/consulta?programa=INF&gestion=2023')
            ->assertSessionHasErrors('periodo');
    }
}
