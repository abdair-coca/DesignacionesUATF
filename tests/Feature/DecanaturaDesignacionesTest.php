<?php

namespace Tests\Feature;

use App\Auth\Demo\DemoUser;
use App\Models\Carrera;
use App\Models\User;
use App\Services\Jachasun\JachasunDesignacionesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Barryvdh\DomPDF\PDF as DomPdfDocument;
use Mockery;
use Tests\TestCase;

class DecanaturaDesignacionesTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_decanatura_lista_designaciones_aprobadas_de_su_facultad(): void
    {
        $decanatura = $this->decanatura();
        $designaciones = collect([
            $this->designacion(2117, 'INF', 'APROBADO'),
            $this->designacion(3117, 'MED', 'APROBADO'),
        ]);
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listarAprobadasPorFacultad')->once()
            ->with(73001)
            ->andReturn($designaciones);
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $response = $this->actingAs($decanatura)->get('/designaciones')->assertOk();

        $this->assertSame([2117, 3117], $response->viewData('designaciones')->pluck('id')->all());
        $this->assertTrue($response->viewData('fuentesImportacion')->isEmpty());
        $response->assertSee('Designaciones aprobadas de su facultad', false)
            ->assertSee('x-text="designacion.programa_nombre || \'-\' "', false)
            ->assertSee('Detalles', false)
            ->assertSee('Imprimir reporte anterior', false)
            ->assertDontSee('designacion-pdf-open', false)
            ->assertDontSee('designacion-pdf-modal', false)
            ->assertSee('Decanatura', false)
            ->assertSee('href="'.route('designaciones.index').'"', false)
            ->assertDontSee('>Nuevo</button>', false)
            ->assertDontSee('form-crear', false)
            ->assertDontSee('Designaciones universitarias', false);
    }

    public function test_decanatura_no_abre_detalle_fuera_de_su_facultad_o_no_aprobado(): void
    {
        $decanatura = $this->decanatura();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaPorFacultad')->once()
            ->with(2117, 73001)
            ->andReturn(null);
        $service->shouldReceive('detallar')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($decanatura)->get('/designaciones/2117')->assertNotFound();
        $this->actingAs($decanatura)->post('/designaciones/2117/carta-pdf', $this->datosPdf())->assertForbidden();
    }

    public function test_detalle_de_decanatura_usa_la_carrera_de_la_designacion_aprobada(): void
    {
        $decanatura = $this->decanatura();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaPorFacultad')->once()
            ->with(2117, 73001)
            ->andReturn($this->designacion(2117, 'INF', 'APROBADO', 'INGENIERIA INFORMATICA'));
        $service->shouldReceive('detallar')->once()->with(2117)->andReturn(collect());
        $service->shouldReceive('listarDecisionesAsignacionDetalle')->never();
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($decanatura)->get('/designaciones/2117')->assertOk()
            ->assertSee('INGENIERIA INFORMATICA (INF)', false)
            ->assertSee('APROBADO', false)
            ->assertSee('Imprimir reporte anterior', false)
            ->assertDontSee('designacion-pdf-open', false)
            ->assertDontSee('designacion-pdf-modal', false)
            ->assertDontSee('>Editar</button>', false)
            ->assertDontSee('Nueva designación', false)
            ->assertDontSee('Buscar docente o CI', false)
            ->assertDontSee('Buscar por docente, materia o CI', false)
            ->assertDontSee('Estado de asignación', false)
            ->assertDontSee(route('designaciones.docentes.buscar'), false)
            ->assertDontSee('form-editar-fila', false)
            ->assertDontSee('listarDecisionesAsignacionDetalle', false);
    }

    public function test_decanatura_conserva_el_reporte_de_la_designacion_aprobada_de_su_facultad(): void
    {
        $decanatura = $this->decanatura();
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('obtenerAprobadaPorFacultad')->once()
            ->with(2117, 73001)
            ->andReturn($this->designacion(2117, 'MED', 'APROBADO', 'MEDICINA'));
        $filas = collect([[
            'docente_id' => 974,
            'docente_nombre' => 'Docente Sintético',
            'materia_sigla' => 'MED101',
            'materia_nombre' => 'Materia Sintética',
            'grupo_id' => 1,
            'horas_teoricas' => 2,
            'horas_practicas' => 1,
            'horas_laboratorio' => 0,
        ]]);
        $service->shouldReceive('detallar')->once()->with(2117)->andReturn($filas);
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $pdf = Mockery::mock(DomPdfDocument::class);
        $pdf->shouldReceive('stream')->once()->with('designacion-2117.pdf')->andReturn(response('%PDF-1.7'));
        Pdf::shouldReceive('loadView')->once()
            ->with('designaciones.pdf', Mockery::on(function (array $data): bool {
                return $data['carrera']->sigla === 'MED'
                    && $data['carrera']->nombre === 'MEDICINA'
                    && $data['asignacion']['estado'] === 'APROBADO'
                    && isset($data['fechaImpresion'])
                    && $data['filas'][0]['docente_nombre'] === 'Docente Sintético';
            }))
            ->andReturn($pdf);

        $this->actingAs($decanatura)->get('/designaciones/2117/pdf')->assertOk();
    }

    public function test_pdf_compartido_conserva_el_formato_y_muestra_las_filas_sinteticas(): void
    {
        $html = view('designaciones.carta-pdf', [
            'carta' => [
                'condicion' => 'DOCENTE ORDINARIO TITULAR',
                'grado_academico' => 'Licenciada',
                'docente_nombre' => 'Docente Sintético',
                'facultad' => 'Ciencias Puras',
                'carrera_semestralizada' => false,
                'fecha_resolucion_texto' => '8 de octubre de 2026',
                'fecha_resolucion_anio' => '2026',
                'numero_cite' => 'CITE-FCP-45/2026',
                'leyenda_revision' => 'ENTREGA PARA REVISIÓN 23 nov 2023',
                'numero_resolucion' => '123',
                'modalidad' => 'Tiempo Completo',
                'fecha_inicio_texto' => '1 de febrero de 2026',
                'decan_nombre' => 'Decana Sintética',
                'decan_cargo' => 'Decano De La Facultad de Ciencias Puras',
                'vicerrector_nombre' => 'Vicerrector Sintético',
                'vicerrector_cargo' => 'VICERRECTOR DE LA U.A.T.F.',
            ],
            'carrera' => (object) ['nombre' => 'Carrera Sintética', 'sigla' => 'INF'],
            'asignacion' => [
                'gestion' => '2026',
                'periodo' => '1',
            ],
            'filas' => collect([[
                'materia_sigla' => 'INF101',
                'materia_nombre' => 'Asignatura Sintética',
                'grupo_texto' => '001',
                'horas_total' => 3,
            ]]),
        ])->render();

        foreach (['DOCENTE ORDINARIO TITULAR', 'Carrera Sintética', 'Docente Sintético', 'Asignatura Sintética', '3 Hrs.'] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
        $this->assertStringNotContainsString('REPORTE DE DESIGNACIONES', $html);
        $this->assertStringNotContainsString('Semestre I/', $html);
        $this->assertStringNotContainsString('SOLO PARA', $html);
    }

    public function test_raiz_y_login_dirigen_decanatura_a_designaciones_y_conservan_vicerrectorado(): void
    {
        $this->actingAs($this->decanatura())->get('/')->assertRedirect(route('designaciones.index'));
        $this->actingAs($this->vicerrectorado())->get('/')->assertRedirect(route('vicerrectorado.designaciones.index'));

        config([
            'auth.guards.web.provider' => 'demo',
            'demo-auth.password' => 'demo-password',
            'demo-auth.accounts' => [[
                'id' => 'decanatura-login-sintetica',
                'name' => 'Cuenta Sintética',
                'email' => 'decanatura@example.test',
                'rol' => User::ROL_DECANATURA,
                'facultad_id' => 73001,
            ]],
        ]);
        $this->app['auth']->forgetGuards();

        $this->post('/login', [
            'email' => 'decanatura@example.test',
            'password' => 'demo-password',
        ])->assertRedirect(route('designaciones.index'));
    }

    public function test_decanatura_no_puede_enviar_escrituras_ni_buscar_docentes_por_http(): void
    {
        $this->actingAs($this->decanatura());

        $this->post('/designaciones')->assertForbidden();
        $this->post('/designaciones/2117')->assertForbidden();
        $this->post('/designaciones/2117/detalle')->assertForbidden();
        $this->get('/designaciones/docentes/buscar?q=sintetico')->assertForbidden();
        $this->post('/vicerrectorado/designaciones/2117/decisiones')->assertForbidden();
        $this->post('/vicerrectorado/designaciones/2117/estado')->assertForbidden();
    }

    public function test_direccion_conserva_la_accion_de_crear_en_la_lista_compartida(): void
    {
        $director = (new DemoUser)->forceFill([
            'demo_id' => 'director-sintetico',
            'name' => 'Director Sintético',
            'email' => 'director@example.test',
            'rol' => User::ROL_DIRECTOR_CARRERA,
            'carrera_id' => 21,
        ]);
        $director->setRelation('carrera', (new Carrera)->forceFill([
            'id' => 21,
            'sigla' => 'INF',
            'nombre' => 'Carrera Sintética',
        ]));
        $service = Mockery::mock(JachasunDesignacionesService::class);
        $service->shouldReceive('listar')->once()->with('INF', '0', '0')->andReturn(collect());
        $this->app->instance(JachasunDesignacionesService::class, $service);

        $this->actingAs($director)->get('/designaciones')->assertOk()
            ->assertSee('Administración de Designaciones', false)
            ->assertSee('abrirCrear()', false)
            ->assertSee('form-crear', false);
    }

    /** @return array<string, int|string|null> */
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

    private function decanatura(): User
    {
        return (new DemoUser)->forceFill([
            'demo_id' => 'decanatura-sintetica',
            'name' => 'Cuenta Sintética',
            'email' => 'decanatura@example.test',
            'rol' => User::ROL_DECANATURA,
            'facultad_id' => 73001,
        ]);
    }

    private function vicerrectorado(): User
    {
        return (new DemoUser)->forceFill([
            'demo_id' => 'vicerrectorado-sintetico',
            'name' => 'Vicerrectorado Sintético',
            'email' => 'vicerrectorado@example.test',
            'rol' => User::ROL_VICERRECTORADO,
        ]);
    }

    /** @return array<string, string> */
    private function datosPdf(): array
    {
        return [
            'condicion' => 'DOC_ORD_TITULAR',
            'grado_academico' => 'Licenciada',
            'facultad' => 'Ciencias Puras',
            'fecha_resolucion' => '2026-10-08',
            'numero_cite' => 'CITE-FCP-45/2026',
            'leyenda_revision' => 'ENTREGA PARA REVISIÓN 23 nov 2023',
            'numero_resolucion' => '123',
            'modalidad' => 'COMPLETO',
            'fecha_inicio' => '2026-02-01',
            'decan_nombre' => 'Decana Sintética',
            'vicerrector_nombre' => 'Vicerrector Sintético',
            'vicerrector_cargo' => 'VICERRECTOR DE LA U.A.T.F.',
        ];
    }
}
