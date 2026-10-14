<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class PersonalDesignacionesTest extends TestCase
{
    public function test_personal_lista_las_designaciones_aprobadas_de_la_gestion_pedida(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listarAprobadasUniversidad')->once()
            ->with('2026')
            ->andReturn(collect([
                $this->designacion(2117, 'INF', 'APROBADO'),
                $this->designacion(3117, 'MED', 'APROBADO'),
            ]));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $response = $this->actingAs($this->personal())->get('/personal/designaciones?gestion=2026')->assertOk();

        $this->assertSame([2117, 3117], $response->viewData('designaciones')->pluck('id')->all());
        $response->assertDontSee('>Nuevo</button>', false)
            ->assertDontSee('form-crear', false);
    }

    public function test_personal_lista_sin_gestion_usa_el_anio_actual(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listarAprobadasUniversidad')->once()
            ->with(now()->format('Y'))
            ->andReturn(collect());
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($this->personal())->get('/personal/designaciones')->assertOk();
    }

    public function test_personal_lista_rechaza_una_gestion_invalida_sin_consultar_jachasun(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listarAprobadasUniversidad')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($this->personal())->get('/personal/designaciones?gestion=20x6')
            ->assertSessionHasErrors('gestion');
    }

    public function test_personal_lista_con_fallo_de_jachasun_responde_503_sin_detalles_tecnicos(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listarAprobadasUniversidad')->once()
            ->andThrow(new RuntimeException('detalle interno de conexión'));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($this->personal())->get('/personal/designaciones?gestion=2026')
            ->assertStatus(503)
            ->assertSee('No fue posible consultar las designaciones.', false)
            ->assertDontSee('detalle interno de conexión', false);
    }

    public function test_personal_detalle_de_designacion_aprobada_es_de_solo_lectura(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaUniversidad')->once()
            ->with(2117, '2026')
            ->andReturn($this->designacion(2117, 'INF', 'APROBADO', 'INGENIERIA INFORMATICA'));
        $service->shouldReceive('detallar')->once()->with(2117)->andReturn(collect([$this->fila()]));
        $service->shouldReceive('listarDecisionesAsignacionDetalle')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($this->personal())->get('/personal/designaciones/2117?gestion=2026')
            ->assertOk()
            ->assertSee('INGENIERIA INFORMATICA (INF)', false)
            ->assertSee('Docente Sintético', false)
            ->assertSee("designacion-pdf-open', { id: 2117, filaId: 22325 })", false)
            ->assertSee('designacionPdfModal', false)
            ->assertDontSee('>Editar</button>', false)
            ->assertDontSee('Nueva designación', false);
    }

    public function test_personal_detalle_de_designacion_no_aprobada_responde_404(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaUniversidad')->once()
            ->with(2117, '2026')
            ->andReturn(null);
        $service->shouldReceive('detallar')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($this->personal())->get('/personal/designaciones/2117?gestion=2026')->assertNotFound();
    }

    public function test_personal_pdf_de_designacion_aprobada(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaUniversidad')->once()
            ->with(2117, '2026')
            ->andReturn($this->designacion(2117, 'MED', 'APROBADO', 'MEDICINA'));
        $service->shouldReceive('detallar')->once()->with(2117)->andReturn(collect([$this->fila()]));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $pdf = Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('stream')->once()->with('designacion-2117.pdf')->andReturn(response('%PDF-1.7'));
        Pdf::shouldReceive('loadView')->once()
            ->with('designaciones.pdf', Mockery::on(function (array $data): bool {
                return $data['carrera']->sigla === 'MED'
                    && $data['carrera']->nombre === 'MEDICINA'
                    && $data['asignacion']['estado'] === 'APROBADO'
                    && isset($data['fechaImpresion'])
                    && count($data['filas']) === 1;
            }))
            ->andReturn($pdf);

        $this->actingAs($this->personal())->get('/personal/designaciones/2117/pdf?gestion=2026')->assertOk();
    }

    public function test_personal_pdf_de_designacion_no_aprobada_responde_404_sin_generar_documento(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaUniversidad')->once()
            ->with(2117, '2026')
            ->andReturn(null);
        $service->shouldReceive('detallar')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($this->personal())->get('/personal/designaciones/2117/pdf?gestion=2026')->assertNotFound();
    }

    public function test_ids_no_numericos_no_resuelven_designaciones(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaUniversidad')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($this->personal())->get('/personal/designaciones/abc')->assertNotFound();
        $this->actingAs($this->personal())->get('/personal/designaciones/abc/pdf')->assertNotFound();
    }

    private function personal(): User
    {
        return User::factory()->personal()->create();
    }

    private function designacion(int $id, string $programa, string $estado, string $nombre = ''): array
    {
        return [
            'id' => $id,
            'programa_codigo' => $programa,
            'programa_nombre' => $nombre !== '' ? $nombre : $programa,
            'detalle' => 'SEMESTRAL 1/2026',
            'fecha' => '2026-01-15',
            'gestion' => '2026',
            'periodo' => '1',
            'observacion' => null,
            'estado' => $estado,
        ];
    }

    private function fila(): array
    {
        return [
            'id' => 22325,
            'docente_id' => 974,
            'docente_nombre' => 'Docente Sintético',
            'materia_sigla' => 'MED101',
            'materia_nombre' => 'Materia Sintética',
            'grupo_id' => 1,
            'horas_teoricas' => 2,
            'horas_practicas' => 1,
            'horas_laboratorio' => 0,
        ];
    }
}
