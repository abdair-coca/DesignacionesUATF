<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Dompdf\Canvas;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DesignacionPdfTest extends TestCase
{
    public function test_pdf_se_abre_en_pestana_nueva_como_pdf_inline_descargable(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117);

        $response = $this->actingAs($director)->get('/designaciones/2117/pdf');

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition', ''));
        $this->assertStringContainsString('designacion-2117.pdf', $response->headers->get('Content-Disposition', ''));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_lista_y_detalle_enlazan_al_pdf_en_pestana_nueva_sin_impresion_directa(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117);

        $this->actingAs($director)->get('/designaciones')->assertOk()
            ->assertSee('rutaPdf', false)
            ->assertSee('designaciones\/__ID__\/pdf', false)
            ->assertSee('target="_blank"', false)
            ->assertDontSee('?print=1', false)
            ->assertDontSee('rutaImprimir', false);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee('/designaciones/2117/pdf', false)
            ->assertSee('target="_blank"', false)
            ->assertDontSee('window.print', false)
            ->assertDontSee('?print=1', false);
    }

    public function test_vista_pdf_sigue_el_modelo_de_cabecera_institucional(): void
    {
        $html = view('designaciones.pdf', [
            'carrera' => (object) ['nombre' => 'Ingenieria Informatica', 'sigla' => 'INF'],
            'asignacion' => [
                'detalle' => 'SEMESTRAL 1/2023',
                'gestion' => '2023',
                'periodo' => '1',
                'estado' => 'SOLICITADO',
                'observacion' => null,
            ],
            'filas' => collect([[
                'docente_nombre' => 'Ada Lovelace',
                'ci' => '5107607',
                'materia_sigla' => 'INF101',
                'materia_nombre' => 'Algoritmos',
                'grupo_id' => 1,
                'horas_teoricas' => 3,
                'horas_practicas' => 3,
                'horas_laboratorio' => 0,
            ]]),
            'fechaImpresion' => '09/09/2026 16:35',
        ])->render();

        foreach (['DATA CENTER', 'TOM', 'REPORTE DE DESIGNACIONES', 'CARRERA:', 'Ingenieria Informatica', 'DOCENTE', 'LABORATORIO', 'Ada Lovelace', 'Carrera:', 'FECHA DE IMPRESI'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
        $this->assertStringNotContainsString('window.print', $html);
    }

    public function test_vista_pdf_declara_el_contenedor_body_para_aplicar_el_margen_de_pagina(): void
    {
        $html = view('designaciones.pdf', [
            'carrera' => (object) ['nombre' => 'Ingenieria Informatica', 'sigla' => 'INF'],
            'asignacion' => [
                'detalle' => 'SEMESTRAL 1/2023',
                'gestion' => '2023',
                'periodo' => '1',
            ],
            'filas' => collect(),
            'fechaImpresion' => '09/09/2026 16:35',
        ])->render();

        $this->assertMatchesRegularExpression('/<body(?:\s[^>]*)?>/i', $html);
        $this->assertMatchesRegularExpression('/body\s*\{\s*margin:\s*0;\s*padding:\s*0\s+34pt\s+70pt;/i', $html);
        $this->assertStringContainsString('pdf-cell-padded', $html);
    }

    public function test_vista_pdf_reserva_el_footer_y_conserva_la_paginacion_de_tabla(): void
    {
        $html = view('designaciones.pdf', [
            'carrera' => (object) ['nombre' => 'Ingenieria Informatica', 'sigla' => 'INF'],
            'asignacion' => ['detalle' => 'SEMESTRAL 1/2023', 'gestion' => '2023', 'periodo' => '1'],
            'filas' => collect(),
            'fechaImpresion' => '11/09/2026 12:00',
        ])->render();

        $this->assertStringContainsString('margin: 10pt 34pt 70pt 34pt;', $html);
        $this->assertStringContainsString('width: 83%;', $html);
        $this->assertStringContainsString('display: table-row-group;', $html);
        $this->assertStringContainsString('footer-blue', $html);
        $this->assertStringContainsString('width: 83%;', $html);
        $this->assertStringNotContainsString('counter(page)', $html);
        $this->assertStringNotContainsString('counter(pages)', $html);
    }

    public function test_controlador_renderiza_el_pdf_y_configura_el_contador_en_canvas(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingenieria Informatica']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDetail(2117);

        $pdf = Mockery::mock(\Barryvdh\DomPDF\PDF::class);
        $canvas = Mockery::mock(Canvas::class);
        $pageScript = null;

        $canvas->shouldReceive('page_script')->once()->withArgs(function ($callback) use (&$pageScript): bool {
            $pageScript = $callback;

            return is_callable($callback);
        });
        $pdf->shouldReceive('render')->once();
        $pdf->shouldReceive('getCanvas')->once()->andReturn($canvas);
        $pdf->shouldReceive('stream')->once()->andReturn(response('%PDF-1.7'));
        Pdf::shouldReceive('loadView')->once()->andReturn($pdf);

        $this->actingAs($director)->get('/designaciones/2117/pdf')->assertOk();

        $this->assertIsCallable($pageScript);
    }

    public function test_pdf_de_otra_carrera_es_prohibido(): void
    {
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect());
        $service->shouldReceive('detallar')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($director)->get('/designaciones/2117/pdf')->assertNotFound();
    }

    public function test_fallo_del_pdf_no_filtra_datos_externos(): void
    {
        Log::spy();
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect([
            ['id' => 2117],
        ]));
        $service->shouldReceive('detallar')->once()->with(2117)->andThrow(new RuntimeException('password=secret private_rows'));
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($director)->get('/designaciones/2117/pdf')->assertStatus(503);
        Log::shouldHaveReceived('warning')->once()->with('Detalle Jachasun no disponible.', ['exception' => RuntimeException::class]);
    }

    private function mockDetail(int $id): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->with('INF', '0', '0')->andReturn(collect([
            [
                'id' => $id,
                'programa_codigo' => 'INF',
                'programa_nombre' => 'INGENIERIA INFORMATICA',
                'detalle' => 'SEMESTRAL 1/2023',
                'fecha' => null,
                'gestion' => '2023',
                'periodo' => '1',
                'observacion' => null,
                'estado' => 'SOLICITADO',
            ],
        ]));
        $service->shouldReceive('detallar')->with($id)->andReturn(collect([[
            'id' => 22325,
            'docente_id' => 974,
            'ci' => '5107607',
            'docente_nombre' => 'Ada Lovelace',
            'materia_id' => 11233,
            'materia_sigla' => 'INF101',
            'materia_nombre' => 'Algoritmos',
            'grupo_id' => 1,
            'horas_teoricas' => 3,
            'horas_practicas' => 3,
            'horas_laboratorio' => 0,
        ]]));
        $service->shouldReceive('ofertaMaterias')->with('INF', '2023', '1')->andReturn(collect());
        $this->app->instance(JachasunDesignacionesService::class, $service);
    }
}
