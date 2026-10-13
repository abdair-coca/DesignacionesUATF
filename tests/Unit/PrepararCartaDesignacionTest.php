<?php

namespace Tests\Unit;

use App\Services\Designaciones\PrepararCartaDesignacion;
use Illuminate\Support\Collection;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

class PrepararCartaDesignacionTest extends TestCase
{
    public function test_prepara_datos_de_la_carta_sin_exponer_identificadores_del_docente(): void
    {
        $resultado = (new PrepararCartaDesignacion)->preparar(
            new Collection([$this->fila()]),
            $this->datos(),
        );

        $this->assertSame('DOCENTE ORDINARIO TITULAR', $resultado['carta']['condicion']);
        $this->assertSame('Docente Sintético', $resultado['carta']['docente_nombre']);
        $this->assertSame('8 de octubre de 2026', $resultado['carta']['fecha_resolucion_texto']);
        $this->assertSame('CITE-FCP-45/2026', $resultado['carta']['numero_cite']);
        $this->assertSame('ENTREGA PARA REVISIÓN 23 nov 2023', $resultado['carta']['leyenda_revision']);
        $this->assertSame('Decano De La Facultad de Ciencias Puras', $resultado['carta']['decan_cargo']);
        $this->assertSame('1 de febrero de 2026', $resultado['carta']['fecha_inicio_texto']);
        $this->assertSame('Tiempo Completo', $resultado['carta']['modalidad']);
        $this->assertArrayNotHasKey('ciudad', $resultado['carta']);
        $this->assertArrayNotHasKey('referencia_normativa', $resultado['carta']);
        $this->assertSame(6, $resultado['filas'][0]['horas_total']);
        $this->assertSame('001', $resultado['filas'][0]['grupo_texto']);
        $this->assertArrayNotHasKey('docente_id', $resultado['filas'][0]);
        $this->assertArrayNotHasKey('ci', $resultado['filas'][0]);
    }

    public function test_prepara_la_condicion_de_docente_ordinario_contratado(): void
    {
        $resultado = (new PrepararCartaDesignacion)->preparar(
            new Collection([$this->fila()]),
            array_replace($this->datos(), ['condicion' => 'DOC_ORD_CONTRATADO']),
        );

        $this->assertSame('DOCENTE ORDINARIO CONTRATADO', $resultado['carta']['condicion']);
        $this->assertSame(6, $resultado['filas'][0]['horas_total']);
        $this->assertArrayNotHasKey('docente_id', $resultado['filas'][0]);
        $this->assertArrayNotHasKey('ci', $resultado['filas'][0]);
    }

    public function test_prepara_la_condicion_de_docente_extraordinario(): void
    {
        $resultado = (new PrepararCartaDesignacion)->preparar(
            new Collection([$this->fila()]),
            array_replace($this->datos(), ['condicion' => 'DOC_EXTR']),
        );

        $this->assertSame('DOCENTE EXTRAORDINARIO', $resultado['carta']['condicion']);
        $this->assertSame(6, $resultado['filas'][0]['horas_total']);
        $this->assertArrayNotHasKey('docente_id', $resultado['filas'][0]);
        $this->assertArrayNotHasKey('ci', $resultado['filas'][0]);
    }

    public function test_rechaza_condiciones_de_carta_no_admitidas(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PrepararCartaDesignacion)->preparar(
            new Collection([$this->fila()]),
            array_replace($this->datos(), ['condicion' => 'CONDICION_NO_ADMITIDA']),
        );
    }

    public function test_prepara_la_semestralizacion_elegida_en_el_modal(): void
    {
        $preparador = new PrepararCartaDesignacion;

        $semestralizada = $preparador->preparar(
            new Collection([$this->fila()]),
            array_replace($this->datos(), ['carrera_semestralizada' => 'SI']),
        );
        $noSemestralizada = $preparador->preparar(
            new Collection([$this->fila()]),
            array_replace($this->datos(), ['carrera_semestralizada' => 'NO']),
        );

        $this->assertTrue($semestralizada['carta']['carrera_semestralizada']);
        $this->assertFalse($noSemestralizada['carta']['carrera_semestralizada']);
    }

    public function test_rechaza_filas_de_docentes_distintos(): void
    {
        $this->expectException(InvalidArgumentException::class);

        (new PrepararCartaDesignacion)->preparar(
            new Collection([
                $this->fila(),
                $this->fila(['id' => 2, 'docente_id' => 2, 'docente_nombre' => 'Otro Docente Sintético']),
            ]),
            $this->datos(),
        );
    }

    /** @param array<string, mixed> $cambios */
    private function fila(array $cambios = []): array
    {
        return array_merge([
            'id' => 1,
            'docente_id' => 1,
            'ci' => 'TEST-0001',
            'docente_nombre' => 'Docente Sintético',
            'materia_sigla' => 'INF101',
            'materia_nombre' => 'Asignatura Sintética',
            'grupo_id' => 1,
            'horas_teoricas' => 3,
            'horas_practicas' => 2,
            'horas_laboratorio' => 1,
        ], $cambios);
    }

    /** @return array<string, string> */
    private function datos(): array
    {
        return [
            'condicion' => 'DOC_ORD_TITULAR',
            'grado_academico' => 'Licenciada',
            'facultad' => 'Ciencias Puras',
            'fecha_resolucion' => '2026-10-08',
            'carrera_semestralizada' => 'SI',
            'numero_cite' => 'CITE-FCP-45/2026',
            'leyenda_revision' => 'ENTREGA PARA REVISIÓN 23 nov 2023',
            'numero_resolucion' => '123',
            'modalidad' => 'COMPLETO',
            'fecha_inicio' => '2026-02-01',
            'decan_nombre' => 'Decana Sintética',
            'vicerrector_nombre' => 'Vicerrector Sintético',
            'vicerrector_cargo' => 'VICERRECTOR',
        ];
    }
}
