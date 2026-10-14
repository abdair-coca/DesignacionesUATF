<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Support\Collection;
use Mockery;
use Tests\TestCase;

class PersonalCartaPdfTest extends TestCase
{
    public function test_personal_genera_la_carta_solo_con_las_materias_del_docente_elegido(): void
    {
        $this->mockDesignacion(2117, collect([
            $this->fila(),
            $this->fila(['id' => 22326, 'materia_sigla' => 'INF102', 'materia_nombre' => 'Otra Asignatura Sintética']),
            $this->fila(['id' => 22327, 'docente_id' => 555, 'docente_nombre' => 'Otro Docente Sintético']),
        ]));

        $pdf = Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->with('carta-designacion-docente-2117.pdf')
            ->andReturn(response('%PDF-1.7', 200, ['Content-Type' => 'application/pdf']));
        Pdf::shouldReceive('loadView')->once()
            ->with('designaciones.carta-pdf', Mockery::on(function (array $data): bool {
                return $data['carta']['condicion'] === 'DOCENTE ORDINARIO TITULAR'
                    && $data['carta']['docente_nombre'] === 'Docente Sintético'
                    && $data['carrera']->nombre === 'INGENIERIA INFORMATICA'
                    && $data['asignacion']['estado'] === 'APROBADO'
                    && count($data['filas']) === 2
                    && $data['filas'][1]['materia_sigla'] === 'INF102';
            }))
            ->andReturn($pdf);

        $this->actingAs($this->personal())
            ->post('/personal/designaciones/2117/carta-pdf?gestion=2026', $this->datosCarta(['fila_id' => 22325]))
            ->assertOk();
    }

    public function test_personal_genera_un_pdf_real_de_la_carta_de_una_designacion_aprobada(): void
    {
        $this->mockDesignacion(2117, collect([$this->fila()]));

        $response = $this->actingAs($this->personal())
            ->post('/personal/designaciones/2117/carta-pdf?gestion=2026', $this->datosCarta(['fila_id' => 22325]));

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('carta-designacion-docente-2117.pdf', $response->headers->get('Content-Disposition', ''));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_carta_de_designacion_no_aprobada_responde_404_sin_generar_documento(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaUniversidad')->once()->with(2117, '2026')->andReturn(null);
        $service->shouldReceive('detallar')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($this->personal())
            ->post('/personal/designaciones/2117/carta-pdf?gestion=2026', $this->datosCarta(['fila_id' => 22325]))
            ->assertNotFound();
    }

    public function test_fila_de_otra_designacion_responde_422_sin_generar_documento(): void
    {
        $this->mockDesignacion(2117, collect([$this->fila()]));
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($this->personal())
            ->post('/personal/designaciones/2117/carta-pdf?gestion=2026', $this->datosCarta(['fila_id' => 99999]))
            ->assertStatus(422)
            ->assertSee('No fue posible completar la operación.', false);
    }

    public function test_condicion_invalida_responde_422_sin_generar_documento(): void
    {
        $this->mockDesignacion(2117, collect([$this->fila()]));
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($this->personal())
            ->post('/personal/designaciones/2117/carta-pdf?gestion=2026', $this->datosCarta(['fila_id' => 22325, 'condicion' => 'OTRA']))
            ->assertStatus(422);
    }

    public function test_la_carta_de_personal_no_escribe_en_jachasun(): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaUniversidad')->once()->with(2117, '2026')
            ->andReturn($this->designacion(2117, 'INF', 'APROBADO', 'INGENIERIA INFORMATICA'));
        $service->shouldReceive('detallar')->once()->with(2117)->andReturn(collect([$this->fila()]));
        $service->shouldReceive('insertar')->never();
        $service->shouldReceive('actualizar')->never();
        $service->shouldReceive('copiar')->never();
        $service->shouldReceive('guardarDetalle')->never();
        $service->shouldReceive('guardarDecisionAsignacionDetalle')->never();
        $service->shouldReceive('guardarRevisionDesignacion')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $pdf = Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(response('%PDF-1.7'));
        Pdf::shouldReceive('loadView')->once()->andReturn($pdf);

        $this->actingAs($this->personal())
            ->post('/personal/designaciones/2117/carta-pdf?gestion=2026', $this->datosCarta(['fila_id' => 22325]))
            ->assertOk();
    }

    public function test_otros_roles_no_generan_la_carta_de_personal(): void
    {
        $carrera = Carrera::factory()->create();
        $usuarios = [
            User::factory()->director($carrera)->create(),
            User::factory()->decanatura(73001)->create(),
            User::factory()->vicerrectorado()->create(),
        ];
        Pdf::shouldReceive('loadView')->never();

        foreach ($usuarios as $usuario) {
            $this->actingAs($usuario)
                ->post('/personal/designaciones/2117/carta-pdf?gestion=2026', $this->datosCarta(['fila_id' => 22325]))
                ->assertForbidden();
        }
    }

    private function personal(): User
    {
        return User::factory()->personal()->create();
    }

    private function mockDesignacion(int $id, Collection $filas): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaUniversidad')->with($id, '2026')
            ->andReturn($this->designacion($id, 'INF', 'APROBADO', 'INGENIERIA INFORMATICA'));
        $service->shouldReceive('detallar')->with($id)->andReturn($filas);
        $this->app->instance(JachasunDesignacionesService::class, $service);
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

    private function fila(array $reemplazos = []): array
    {
        return array_merge([
            'id' => 22325,
            'docente_id' => 974,
            'ci' => 'TEST-0001',
            'docente_nombre' => 'Docente Sintético',
            'materia_id' => 11233,
            'materia_sigla' => 'INF101',
            'materia_nombre' => 'Asignatura Sintética',
            'grupo_id' => 1,
            'horas_teoricas' => 3,
            'horas_practicas' => 3,
            'horas_laboratorio' => 0,
        ], $reemplazos);
    }

    private function datosCarta(array $reemplazos = []): array
    {
        return array_merge([
            'condicion' => 'DOC_ORD_TITULAR',
            'grado_academico' => 'Licenciada',
            'facultad' => 'Ciencias Puras',
            'fecha_resolucion' => '2026-10-08',
            'carrera_semestralizada' => 'SI',
            'leyenda_revision' => 'EJEMPLO FICTICIO - REEMPLAZAR',
            'numero_cite' => 'CITE-FICTICIO-001',
            'numero_resolucion' => '001-FICTICIO',
            'modalidad' => 'COMPLETO',
            'fecha_inicio' => '2026-02-01',
            'decan_nombre' => 'Decano Ficticio',
            'vicerrector_nombre' => 'Vicerrector Ficticio',
            'vicerrector_cargo' => 'Vicerrector Ficticio',
        ], $reemplazos);
    }
}
