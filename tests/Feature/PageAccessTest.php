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
            ->assertSee('Lista de Designaciones')
            ->assertDontSee('Nueva Propuesta de Designación');
        $this->actingAs($director)->get('/designaciones/create')->assertNotFound();
        $this->actingAs($director)->get('/propuestas')->assertNotFound();
        $this->actingAs($director)->get('/revisiones/pendientes')->assertNotFound();
    }

    public function test_vicerrectorado_no_accede_a_designaciones_de_carrera(): void
    {
        $this->actingAs(User::factory()->vicerrectorado()->create())->get('/designaciones')->assertForbidden();
    }

    public function test_personal_se_dirige_a_su_listado_al_entrar_y_en_la_raiz(): void
    {
        $personal = User::factory()->personal()->create(['password' => bcrypt('secret')]);

        $this->actingAs($personal)->get('/')->assertRedirect('/personal/designaciones');
        $this->post('/logout');
        $this->post('/login', ['email' => $personal->email, 'password' => 'secret'])
            ->assertRedirect('/personal/designaciones');
    }

    public function test_personal_ve_el_listado_de_solo_lectura_con_su_navegacion_y_rol(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listarAprobadasUniversidad')->once()
            ->with(now()->format('Y'))
            ->andReturn(collect([[
                'id' => 2117,
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'detalle' => 'SEMESTRAL 1/2026',
                'fecha' => '2026-01-15',
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => null,
                'estado' => 'APROBADO',
            ]]));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs(User::factory()->personal()->create())->get('/personal/designaciones')
            ->assertOk()
            ->assertSee('Designaciones aprobadas de la universidad', false)
            ->assertSee('<p class="top-user-role">Personal</p>', false)
            ->assertSee('href="'.route('personal.designaciones.index').'"', false)
            ->assertDontSee('href="'.route('designaciones.index').'"', false)
            ->assertDontSee('href="'.route('vicerrectorado.designaciones.index').'"', false)
            ->assertDontSee('Nueva designación', false)
            ->assertDontSee('form-crear', false)
            ->assertDontSee('Buscar docentes', false);
    }

    public function test_personal_ve_detalle_de_solo_lectura_con_impresion_de_la_designacion(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaUniversidad')->once()
            ->with(2117, now()->format('Y'))
            ->andReturn([
                'id' => 2117,
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'detalle' => 'SEMESTRAL 1/2026',
                'fecha' => '2026-01-15',
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => null,
                'estado' => 'APROBADO',
            ]);
        $service->shouldReceive('detallar')->once()->with(2117)->andReturn(collect());
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs(User::factory()->personal()->create())->get('/personal/designaciones/2117')
            ->assertOk()
            ->assertSee('Consulta de Personal', false)
            ->assertSee(route('personal.designaciones.pdf', ['id' => 2117]), false)
            ->assertSee('Imprimir designación', false)
            ->assertSee('href="'.route('personal.designaciones.index').'"', false)
            ->assertDontSee('href="'.route('designaciones.index').'"', false)
            ->assertDontSee(route('designaciones.update', ['id' => 2117]), false)
            ->assertDontSee(route('designaciones.actualizar_detalle', ['id' => 2117]), false)
            ->assertDontSee('Editar', false)
            ->assertDontSee('Nueva designación', false);
    }
}
