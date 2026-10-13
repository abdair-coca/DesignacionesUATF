<?php

namespace Tests\Unit;

use Tests\TestCase;

class DesignacionCartaSemestralizacionTest extends TestCase
{
    protected $connectionsToTransact = [];

    public function test_modal_ofrece_si_o_no_para_carrera_semestralizada(): void
    {
        $html = view('partials.modal-designacion-pdf', [
            'rutaPdf' => '/designaciones/1/carta-pdf',
        ])->render();

        foreach ([
            'name="carrera_semestralizada"',
            'value="SI"',
            'value="NO"',
            'Sí (ejemplo)',
            '>No</option>',
        ] as $texto) {
            $this->assertStringContainsString($texto, $html);
        }
    }

    public function test_muestra_los_semestres_entre_parentesis_solo_si_la_carrera_es_semestralizada(): void
    {
        foreach ($this->condiciones() as $condicion) {
            $html = $this->renderCarta($condicion, true);

            $this->assertStringContainsString('(Semestre I/2026 y Semestre II/2026)', $html);
            $this->assertStringNotContainsString('SOLO PARA', $html);
        }
    }

    public function test_omite_el_parentesis_de_semestres_si_la_carrera_no_es_semestralizada(): void
    {
        foreach ($this->condiciones() as $condicion) {
            $html = $this->renderCarta($condicion, false);

            $this->assertStringNotContainsString('Semestre I/', $html);
            $this->assertStringNotContainsString('Semestre II/', $html);
            $this->assertStringNotContainsString('SOLO PARA', $html);
        }
    }

    /** @return array<int, string> */
    private function condiciones(): array
    {
        return [
            'DOCENTE ORDINARIO TITULAR',
            'DOCENTE ORDINARIO CONTRATADO',
            'DOCENTE EXTRAORDINARIO',
        ];
    }

    private function renderCarta(string $condicion, bool $semestralizada): string
    {
        $carta = [
            'condicion' => $condicion,
            'grado_academico' => 'Grado Sintético',
            'docente_nombre' => 'Docente Sintético',
            'facultad' => 'Facultad Sintética',
            'carrera_semestralizada' => $semestralizada,
            'fecha_resolucion_texto' => '8 de octubre de 2026',
            'fecha_resolucion_anio' => '2026',
            'numero_cite' => 'CITE-SINTETICO-001',
            'leyenda_revision' => '',
            'numero_resolucion' => '001-SINTETICA',
            'modalidad' => 'Tiempo Completo',
            'fecha_inicio_texto' => '1 de febrero de 2026',
            'decan_nombre' => 'Decana Sintética',
            'decan_cargo' => 'Decana de Facultad Sintética',
            'vicerrector_nombre' => 'Vicerrector Sintético',
            'vicerrector_cargo' => 'VICERRECTOR',
        ];

        return view('designaciones.carta-pdf', [
            'carta' => $carta,
            'carrera' => (object) ['nombre' => 'Carrera Sintética', 'sigla' => 'SNT'],
            'asignacion' => ['gestion' => '2026', 'periodo' => '1'],
            'filas' => collect([[
                'materia_sigla' => 'SNT101',
                'grupo_texto' => '001',
                'materia_nombre' => 'Materia Sintética',
                'horas_total' => 6,
            ]]),
        ])->render();
    }
}
