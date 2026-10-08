<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class PageAccessTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->zeroOrMoreTimes()->andReturn(new Collection);
        $service->shouldReceive('listarPaginado')->zeroOrMoreTimes()->andReturn(['items' => new Collection, 'total' => 0]);
        $service->shouldReceive('detallar')->zeroOrMoreTimes()->andReturn(new Collection);
        $this->app->instance(JachasunDesignacionesService::class, $service);
    }

    public function test_raiz_y_login_dirigen_a_designaciones_para_director(): void
    {
        $director = User::factory()->director(Carrera::factory()->create())->create(['password' => bcrypt('secret')]);

        $this->actingAs($director)->get('/')->assertRedirect('/designaciones');
        $this->post('/logout');
        $this->post('/login', ['email' => $director->email, 'password' => 'secret'])->assertRedirect('/designaciones');
    }

    public function test_login_de_director_persiste_la_sesion_en_la_siguiente_peticion(): void
    {
        $director = User::factory()->director(Carrera::factory()->create())->create(['password' => bcrypt('secret')]);

        $this->post('/login', ['email' => $director->email, 'password' => 'secret'])->assertRedirect('/designaciones');
        $this->get('/designaciones')->assertOk()->assertSee('Designaciones');
    }

    public function test_vicerrectorado_se_dirige_a_la_bandeja_universitaria(): void
    {
        $vicerrectorado = User::factory()->vicerrectorado()->create(['password' => bcrypt('secret')]);

        $this->actingAs($vicerrectorado)->get('/')->assertRedirect('/vicerrectorado/designaciones');
        $this->post('/logout');
        $this->post('/login', ['email' => $vicerrectorado->email, 'password' => 'secret'])->assertRedirect('/vicerrectorado/designaciones');
    }

    public function test_director_ve_lista_jachasun_y_rutas_locales_no_existen(): void
    {
        $director = User::factory()->director(Carrera::factory()->create())->create();

        $this->actingAs($director)->get('/designaciones')->assertOk()
            ->assertSee('Designaciones de Jachasun')
            ->assertDontSee('Nueva Propuesta de Designaci�n');
        $this->actingAs($director)->get('/designaciones/create')->assertNotFound();
        $this->actingAs($director)->get('/propuestas')->assertNotFound();
        $this->actingAs($director)->get('/revisiones/pendientes')->assertNotFound();
    }

    public function test_vicerrectorado_no_accede_a_designaciones_de_carrera(): void
    {
        $this->actingAs(User::factory()->vicerrectorado()->create())->get('/designaciones')->assertForbidden();
    }
}
