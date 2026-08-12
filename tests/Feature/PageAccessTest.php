<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use App\Services\Institutional\InstitutionalDesignacionesService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['institutional.enabled' => true]);
        $service = Mockery::mock(InstitutionalDesignacionesService::class);
        $service->shouldReceive('listar')->zeroOrMoreTimes()->andReturn(new Collection);
        $this->app->instance(InstitutionalDesignacionesService::class, $service);
    }

    public function test_raiz_y_login_dirigen_a_designaciones_para_director(): void
    {
        $director = User::factory()->director(Carrera::factory()->create())->create(['password' => bcrypt('secret')]);

        $this->actingAs($director)->get('/')->assertRedirect('/designaciones');

        $this->post('/logout');
        $this->post('/login', ['email' => $director->email, 'password' => 'secret'])
            ->assertRedirect('/designaciones');
    }

    public function test_login_de_director_persiste_la_sesion_en_la_siguiente_peticion(): void
    {
        $this->assertSame('file', config('session.driver'));

        $director = User::factory()->director(Carrera::factory()->create())->create(['password' => bcrypt('secret')]);

        $this->post('/login', ['email' => $director->email, 'password' => 'secret'])
            ->assertRedirect('/designaciones');

        $this->get('/designaciones')
            ->assertOk()
            ->assertSee('Designaciones');
    }

    public function test_raiz_y_login_dirigen_a_revisiones_para_vicerrectorado(): void
    {
        $vicerrectorado = User::factory()->vicerrectorado()->create(['password' => bcrypt('secret')]);

        $this->actingAs($vicerrectorado)->get('/')->assertRedirect('/revisiones/pendientes');

        $this->post('/logout');
        $this->post('/login', ['email' => $vicerrectorado->email, 'password' => 'secret'])
            ->assertRedirect('/revisiones/pendientes');
    }

    public function test_director_ve_la_lista_nueva_y_las_rutas_heredadas_no_existen(): void
    {
        $director = User::factory()->director(Carrera::factory()->create())->create();

        $this->actingAs($director)
            ->get('/designaciones')
            ->assertOk()
            ->assertSee('Designaciones')
            ->assertSee('Nueva Propuesta de Designación')
            ->assertDontSee('/designaciones/carrera');
        $this->actingAs($director)->get('/designaciones/create')->assertNotFound();
        $this->actingAs($director)->get('/propuestas')->assertNotFound();
        $this->actingAs($director)->get('/designaciones/lista')->assertNotFound();
    }

    public function test_vicerrectorado_usa_una_unica_bandeja_y_no_accede_a_designaciones(): void
    {
        $vicerrectorado = User::factory()->vicerrectorado()->create();

        $this->actingAs($vicerrectorado)
            ->get('/revisiones/pendientes?folder=todas')
            ->assertOk()
            ->assertSee('Bandeja de Revisiones')
            ->assertSee('Todas las Peticiones')
            ->assertSee('Buscar por director o carrera');
        $this->actingAs($vicerrectorado)->get('/versiones/pendientes')->assertNotFound();
        $this->actingAs($vicerrectorado)->get('/designaciones')->assertForbidden();
    }
}
