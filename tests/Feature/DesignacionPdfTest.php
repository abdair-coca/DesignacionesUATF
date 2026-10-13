<?php

namespace Tests\Feature;

use App\Models\Carrera;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Mockery;
use RuntimeException;
use Tests\TestCase;

class DesignacionPdfTest extends TestCase
{
    public function test_director_genera_la_carta_docente_ordinario_titular_en_pdf(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDesignacion(2117, collect([$this->fila()]));

        $response = $this->actingAs($director)
            ->post('/designaciones/2117/carta-pdf', $this->datosCarta());

        $response->assertOk();
        $response->assertHeader('Content-Type', 'application/pdf');
        $this->assertStringContainsString('inline', $response->headers->get('Content-Disposition', ''));
        $this->assertStringContainsString('carta-designacion-docente-2117.pdf', $response->headers->get('Content-Disposition', ''));
        $this->assertStringStartsWith('%PDF', $response->getContent());
    }

    public function test_el_pdf_anterior_de_direccion_conserva_su_ruta_y_vista(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDesignacion(2117, collect([$this->fila()]));

        $pdf = Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('stream')->once()->with('designacion-2117.pdf')
            ->andReturn(response('%PDF-1.7', 200, ['Content-Type' => 'application/pdf']));
        Pdf::shouldReceive('loadView')->once()
            ->with('designaciones.pdf', Mockery::on(fn (array $data): bool => isset($data['fechaImpresion'])
                && ! isset($data['carta'])
                && $data['carrera']->sigla === 'INF'))
            ->andReturn($pdf);

        $this->actingAs($director)->get('/designaciones/2117/pdf')
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_solo_el_detalle_de_direccion_abre_el_formulario_de_carta(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDesignacion(2117, collect([$this->fila()]));

        $this->actingAs($director)->get('/designaciones')->assertOk()
            ->assertDontSee('designacion-pdf-modal', false)
            ->assertDontSee('designacion-pdf-open', false)
            ->assertSee('Imprimir reporte anterior')
            ->assertSee(str_replace('/', '\\/', route('designaciones.pdf', ['id' => '__ID__'])), false);

        $this->actingAs($director)->get('/designaciones/2117')->assertOk()
            ->assertSee('designacion-pdf-open', false)
            ->assertSee('designacion-pdf-modal', false)
            ->assertSee('method="POST"', false)
            ->assertSee('name="condicion"', false)
            ->assertSee('DOC_ORD_CONTRATADO', false)
            ->assertSee('Docente ordinario contratado (ejemplo)')
            ->assertSee('DOC_EXTR', false)
            ->assertSee('Docente extraordinario (ejemplo)')
            ->assertSee('name="numero_cite"', false)
            ->assertSee('name="leyenda_revision"', false)
            ->assertSee('Nombre completo del decano o decana')
            ->assertDontSee('name="ciudad"', false)
            ->assertDontSee('name="referencia_normativa"', false)
            ->assertDontSee('name="decan_cargo"', false)
            ->assertSee('title="Imprimir designación"', false)
            ->assertSee(str_replace('/', '\\/', route('designaciones.carta_pdf', ['id' => '__ID__'])), false);
    }

    public function test_carta_incluye_todas_las_filas_del_mismo_docente(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $filas = collect([
            $this->fila(['materia_sigla' => 'INF101', 'materia_nombre' => 'Asignatura Sintética I']),
            $this->fila([
                'id' => 22326,
                'materia_sigla' => 'INF102',
                'materia_nombre' => 'Asignatura Sintética II',
                'horas_teoricas' => 2,
                'horas_practicas' => 4,
            ]),
        ]);
        $this->mockDesignacion(2117, $filas);

        $pdf = Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(response('%PDF-1.7'));
        Pdf::shouldReceive('loadView')->once()
            ->with('designaciones.carta-pdf', Mockery::on(function (array $data): bool {
                return $data['filas']->count() === 2
                    && $data['filas'][1]['horas_total'] === 6
                    && $data['carta']['condicion'] === 'DOCENTE ORDINARIO TITULAR';
            }))
            ->andReturn($pdf);

        $this->actingAs($director)->post('/designaciones/2117/carta-pdf', $this->datosCarta())->assertOk();
    }

    public function test_director_genera_la_carta_docente_ordinario_contratado(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDesignacion(2117, collect([$this->fila()]));

        $pdf = Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(response('%PDF-1.7'));
        Pdf::shouldReceive('loadView')->once()
            ->with('designaciones.carta-pdf', Mockery::on(fn (array $data): bool => $data['carta']['condicion'] === 'DOCENTE ORDINARIO CONTRATADO'
            ))
            ->andReturn($pdf);

        $this->actingAs($director)
            ->post('/designaciones/2117/carta-pdf', $this->datosCarta(['condicion' => 'DOC_ORD_CONTRATADO']))
            ->assertOk();
    }

    public function test_director_genera_la_carta_docente_extraordinario(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $this->mockDesignacion(2117, collect([$this->fila()]));

        $pdf = Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('setPaper')->once()->with('a4')->andReturnSelf();
        $pdf->shouldReceive('stream')->once()->andReturn(response('%PDF-1.7'));
        Pdf::shouldReceive('loadView')->once()
            ->with('designaciones.carta-pdf', Mockery::on(fn (array $data): bool => $data['carta']['condicion'] === 'DOCENTE EXTRAORDINARIO'
            ))
            ->andReturn($pdf);

        $this->actingAs($director)
            ->post('/designaciones/2117/carta-pdf', $this->datosCarta(['condicion' => 'DOC_EXTR']))
            ->assertOk();
    }

    public function test_carta_no_se_genera_si_la_designacion_contiene_docentes_distintos(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $filas = collect([
            $this->fila(),
            $this->fila(['id' => 22326, 'docente_id' => 975, 'docente_nombre' => 'Otro Docente Sintético']),
        ]);
        $this->mockDesignacion(2117, $filas);
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($director)->post('/designaciones/2117/carta-pdf', $this->datosCarta())
            ->assertStatus(422)
            ->assertSeeText('No fue posible completar la operación.');
    }

    public function test_formulario_pdf_rechaza_datos_faltantes_con_mensaje_seguro(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldNotReceive('listar');
        $this->app->instance(JachasunDesignacionesService::class, $service);
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($director)->post('/designaciones/2117/carta-pdf', [
            'condicion' => 'DOC_ORD_TITULAR',
        ])->assertStatus(422)
            ->assertSeeText('No fue posible completar la operación.');
    }

    public function test_formulario_pdf_rechaza_condiciones_no_admitidas(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldNotReceive('listar');
        $this->app->instance(JachasunDesignacionesService::class, $service);
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($director)
            ->post('/designaciones/2117/carta-pdf', $this->datosCarta(['condicion' => 'CONDICION_NO_ADMITIDA']))
            ->assertStatus(422)
            ->assertSeeText('No fue posible completar la operación.');
    }

    public function test_formulario_pdf_rechaza_valor_no_admitido_de_semestralizacion(): void
    {
        $carrera = Carrera::factory()->create(['sigla' => 'INF', 'nombre' => 'Ingeniería Informática']);
        $director = User::factory()->director($carrera)->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldNotReceive('listar');
        $this->app->instance(JachasunDesignacionesService::class, $service);
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($director)
            ->post('/designaciones/2117/carta-pdf', $this->datosCarta(['carrera_semestralizada' => 'FUERA']))
            ->assertStatus(422)
            ->assertSeeText('No fue posible completar la operación.');
    }

    public function test_pdf_de_otra_carrera_es_prohibido(): void
    {
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect());
        $service->shouldReceive('detallar')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($director)->post('/designaciones/2117/carta-pdf', $this->datosCarta())->assertNotFound();
    }

    public function test_fallo_de_lectura_no_filtra_datos_privados(): void
    {
        Log::spy();
        $director = User::factory()->director(Carrera::factory()->create(['sigla' => 'INF']))->create();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect([
            ['id' => 2117],
        ]));
        $service->shouldReceive('detallar')->once()->with(2117)
            ->andThrow(new RuntimeException('password=secret private_rows'));
        $this->app->instance(JachasunDesignacionesService::class, $service);
        Pdf::shouldReceive('loadView')->never();

        $this->actingAs($director)->post('/designaciones/2117/carta-pdf', $this->datosCarta())
            ->assertStatus(503)
            ->assertDontSee('secret')
            ->assertDontSee('private_rows');
        Log::shouldHaveReceived('warning')->once()
            ->with('Detalle no disponible.', ['exception' => RuntimeException::class]);
    }

    public function test_vista_pdf_renderiza_la_plantilla_ordinaria_titular(): void
    {
        $html = view('designaciones.carta-pdf', [
            'carta' => $this->carta(),
            'carrera' => (object) ['nombre' => 'Ingeniería Informática Sintética', 'sigla' => 'INF'],
            'asignacion' => ['gestion' => '2026', 'periodo' => '1'],
            'filas' => collect([$this->fila(['horas_total' => 6])]),
        ])->render();

        foreach ([
            'ENTREGA PARA REVISIÓN 23 nov 2023',
            'Potosí, 8 de octubre de 2026',
            'N° de cite de la Facultad CITE-FCP-45/2026',
            'Facultad de Ciencias Puras',
            'N° 123/2026',
            'DOCENTE ORDINARIO TITULAR',
            'Tiempo Completo',
            'Artículo 20 del Reglamento del Régimen Académico Docente',
            'Ingeniería Informática Sintética',
            'Docente Sintético',
            'Asignatura Sintética',
            '6 Hrs.',
            'Decana Sintética',
            'Decano De La Facultad de Ciencias Puras',
            'Vicerrector Sintético',
        ] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }

        $this->assertStringNotContainsString('REPORTE DE DESIGNACIONES', $html);
        $this->assertStringNotContainsString('5107607', $html);
    }

    public function test_vista_pdf_renderiza_la_plantilla_ordinaria_contratada_con_articulo_19(): void
    {
        $carta = [...$this->carta(), 'condicion' => 'DOCENTE ORDINARIO CONTRATADO'];
        $html = view('designaciones.carta-pdf', [
            'carta' => $carta,
            'carrera' => (object) ['nombre' => 'Ingeniería Informática Sintética', 'sigla' => 'INF'],
            'asignacion' => ['gestion' => '2026', 'periodo' => '1'],
            'filas' => collect([$this->fila(['horas_total' => 6])]),
        ])->render();

        foreach ([
            'DOCENTE ORDINARIO CONTRATADO',
            'Artículo 19 del Reglamento del Régimen Académico Docente',
            'ha sido designado para la gestión académica 2026',
            'Semestre I/2026 y Semestre II/2026',
            'SOLO PARA LAS CARRERAS SEMESTRALIZADAS',
            'con las siguientes asignaturas:',
            'Asignatura Sintética',
        ] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }

        $this->assertStringNotContainsString('Artículo 20 del Reglamento del Régimen Académico Docente', $html);
        $this->assertStringNotContainsString('ha sido designado docente por la gestión académica 2026', $html);
    }

    public function test_vista_pdf_renderiza_la_plantilla_extraordinaria_con_articulo_11(): void
    {
        $carta = [...$this->carta(), 'condicion' => 'DOCENTE EXTRAORDINARIO'];
        $html = view('designaciones.carta-pdf', [
            'carta' => $carta,
            'carrera' => (object) ['nombre' => 'Ingeniería Informática Sintética', 'sigla' => 'INF'],
            'asignacion' => ['gestion' => '2026', 'periodo' => '1'],
            'filas' => collect([$this->fila(['horas_total' => 6])]),
        ])->render();

        foreach ([
            'DOCENTE EXTRAORDINARIO',
            'Artículo 11 del Reglamento del Régimen Académico Docente',
            'ha sido designado docente por la gestión 2026',
            'Semestre I/2026 y Semestre II/2026',
            'SOLO PARA CARRERAS SEMESTRALIZADAS',
            'con las siguientes asignaturas:',
            'Asignatura Sintética',
        ] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }

        $this->assertStringNotContainsString('Artículo 19 del Reglamento del Régimen Académico Docente', $html);
        $this->assertStringNotContainsString('Artículo 20 del Reglamento del Régimen Académico Docente', $html);
        $this->assertStringNotContainsString('SOLO PARA LAS CARRERAS SEMESTRALIZADAS', $html);
    }

    /** @param array<string, mixed> $reemplazos */
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

    /** @return array<string, string> */
    private function datosCarta(array $reemplazos = []): array
    {
        return array_merge([
            'condicion' => 'DOC_ORD_TITULAR',
            'grado_academico' => 'Licenciada',
            'facultad' => 'Ciencias Puras',
            'fecha_resolucion' => '2026-10-08',
            'carrera_semestralizada' => 'SI',
            'leyenda_revision' => 'ENTREGA PARA REVISIÓN 23 nov 2023',
            'numero_cite' => 'CITE-FCP-45/2026',
            'numero_resolucion' => '123',
            'modalidad' => 'COMPLETO',
            'fecha_inicio' => '2026-02-01',
            'decan_nombre' => 'Decana Sintética',
            'vicerrector_nombre' => 'Vicerrector Sintético',
            'vicerrector_cargo' => 'VICERRECTOR DE LA U.A.T.F.',
        ], $reemplazos);
    }

    /** @return array<string, string> */
    private function carta(): array
    {
        return [
            'condicion' => 'DOCENTE ORDINARIO TITULAR',
            'grado_academico' => 'Licenciada',
            'docente_nombre' => 'Docente Sintético',
            'facultad' => 'Ciencias Puras',
            'carrera_semestralizada' => true,
            'ciudad' => 'Potosí',
            'fecha_resolucion_texto' => '8 de octubre de 2026',
            'numero_cite' => 'CITE-FCP-45/2026',
            'leyenda_revision' => 'ENTREGA PARA REVISIÓN 23 nov 2023',
            'numero_resolucion' => '123',
            'modalidad' => 'Tiempo Completo',
            'fecha_inicio_texto' => '1 de febrero de 2026',
            'referencia_normativa' => 'Artículo 20 del Reglamento del Régimen Académico Docente',
            'decan_nombre' => 'Decana Sintética',
            'decan_cargo' => 'Decano De La Facultad de Ciencias Puras',
            'vicerrector_nombre' => 'Vicerrector Sintético',
            'vicerrector_cargo' => 'VICERRECTOR DE LA U.A.T.F.',
        ];
    }

    /** @param Collection<int, array<string, mixed>> $filas */
    private function mockDesignacion(int $id, Collection $filas): void
    {
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->with('INF', '0', '0')->andReturn(collect([
            [
                'id' => $id,
                'programa_codigo' => 'INF',
                'programa_nombre' => 'Ingeniería Informática',
                'detalle' => 'SEMESTRAL 1/2026',
                'fecha' => null,
                'gestion' => '2026',
                'periodo' => '1',
                'observacion' => null,
                'estado' => 'SOLICITADO',
            ],
        ]));
        $service->shouldReceive('detallar')->with($id)->andReturn($filas);
        $service->shouldReceive('listarDecisionesAsignacionDetalle')->with($id)->andReturn(collect());
        $service->shouldReceive('ofertaMaterias')->with('INF', '2026', '1')->andReturn(collect());
        $this->app->instance(JachasunDesignacionesService::class, $service);
    }
}
