<?php

namespace Tests\Feature;

use App\Auth\Demo\DemoUser;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Illuminate\Support\Facades\Route;
use Mockery;
use Tests\TestCase;

class DesignacionesCharacterizationTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_listado_de_direccion_usa_el_alcance_de_su_carrera_y_pagina_en_la_interfaz(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()
            ->with('INF', '0', '0')
            ->andReturn(collect([$this->designacion()]));
        $service->shouldNotReceive('listarPaginado');
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $response = $this->actingAs($this->director())
            ->get('/designaciones?search=SEMESTRAL');

        $response->assertOk()
            ->assertViewIs('designaciones.lista')
            ->assertSee('Designación sintética')
            ->assertSee('paginadas()', false)
            ->assertSee('filasFiltradas()', false);
        $this->assertSame('SEMESTRAL', $response->viewData('busqueda'));
        $this->assertSame([91], $response->viewData('designaciones')->pluck('id')->all());
    }

    public function test_busqueda_de_docentes_vacia_responde_sin_consultar_el_servicio(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldNotReceive('buscarDocentes');
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($this->director())
            ->get('/designaciones/docentes/buscar?q=%20%20')
            ->assertOk()
            ->assertExactJson([]);
    }

    public function test_direccion_no_edita_cabecera_ni_detalle_fuera_de_su_carrera(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->twice()
            ->with('INF', '0', '0')
            ->andReturn(collect(), collect());
        $service->shouldNotReceive('actualizar');
        $service->shouldNotReceive('guardarDetalle');
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($this->director())
            ->post('/designaciones/91', ['obs' => 'Cambio sintético'])
            ->assertNotFound();

        $this->actingAs($this->director())
            ->post('/designaciones/91/detalle', [
                'id_detalle' => 31,
                'id_docente' => 71,
                'id_materia' => 81,
                'id_grupo' => 1,
                'hrs_teoria' => 2,
                'hrs_practica' => 0,
                'hrs_laboratorio' => 0,
            ])
            ->assertNotFound();
    }

    public function test_vicerrectorado_no_consulta_ni_escribe_ids_ajenos_al_alcance_universitario(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerUniversidad')->times(4)
            ->with(91, '2026')
            ->andReturn(null, null, null, null);
        $service->shouldNotReceive('detallar');
        $service->shouldNotReceive('guardarDecisionAsignacionDetalle');
        $service->shouldNotReceive('guardarRevisionDesignacion');
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $vicerrectorado = $this->vicerrectorado();

        $this->actingAs($vicerrectorado)
            ->get('/vicerrectorado/designaciones/91?gestion=2026')
            ->assertNotFound();

        $this->actingAs($vicerrectorado)
            ->get('/vicerrectorado/designaciones/91/pdf?gestion=2026')
            ->assertNotFound();

        $this->actingAs($vicerrectorado)
            ->postJson('/vicerrectorado/designaciones/91/decisiones?gestion=2026', [
                'filas' => [31],
                'estado' => 'APROBADA',
            ])
            ->assertNotFound();

        $this->actingAs($vicerrectorado)
            ->postJson('/vicerrectorado/designaciones/91/estado?gestion=2026', [
                'estado' => 'APROBADO',
            ])
            ->assertNotFound();
    }

    public function test_rutas_de_designacion_restringen_los_ids_a_numeros(): void
    {
        $nombres = [
            'designaciones.show',
            'designaciones.pdf',
            'designaciones.update',
            'designaciones.actualizar_detalle',
            'vicerrectorado.designaciones.show',
            'vicerrectorado.designaciones.pdf',
            'vicerrectorado.designaciones.decisiones',
            'vicerrectorado.designaciones.estado',
        ];

        foreach ($nombres as $nombre) {
            $restriccion = Route::getRoutes()->getByName($nombre)?->wheres['id'] ?? null;

            $this->assertNotNull($restriccion, "La ruta {$nombre} debe restringir el ID.");
            $this->assertSame(1, preg_match('/^'.$restriccion.'$/D', '91'));
            $this->assertSame(0, preg_match('/^'.$restriccion.'$/D', 'no-numerico'));
        }
    }

    private function director(): DemoUser
    {
        $director = new DemoUser;
        $director->demo_id = 'director-caracterizacion';
        $director->rol = User::ROL_DIRECTOR_CARRERA;
        $director->setRelation('carrera', (object) [
            'sigla' => 'INF',
            'nombre' => 'Carrera sintética',
        ]);

        return $director;
    }

    private function vicerrectorado(): DemoUser
    {
        $vicerrectorado = new DemoUser;
        $vicerrectorado->demo_id = 'vicerrectorado-caracterizacion';
        $vicerrectorado->rol = User::ROL_VICERRECTORADO;

        return $vicerrectorado;
    }

    /** @return array<string, int|string|null> */
    private function designacion(): array
    {
        return [
            'id' => 91,
            'programa_codigo' => 'INF',
            'programa_nombre' => 'Carrera sintética',
            'detalle' => 'Designación sintética',
            'fecha' => '2026-01-15',
            'gestion' => '2026',
            'periodo' => '1',
            'observacion' => null,
            'estado' => 'SOLICITADO',
        ];
    }
}
