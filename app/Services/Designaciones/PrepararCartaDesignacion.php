<?php

namespace App\Services\Designaciones;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class PrepararCartaDesignacion
{
    /** @param array<string, string> $datos */
    public function preparar(Collection $filas, array $datos): array
    {
        $this->validarDocenteUnico($filas);
        $primeraFila = $filas->first();
        $fechaResolucion = CarbonImmutable::parse($datos['fecha_resolucion']);
        $condicion = match ($datos['condicion']) {
            'DOC_ORD_TITULAR' => 'DOCENTE ORDINARIO TITULAR',
            'DOC_ORD_CONTRATADO' => 'DOCENTE ORDINARIO CONTRATADO',
            'DOC_EXTR' => 'DOCENTE EXTRAORDINARIO',
            default => throw new InvalidArgumentException('La condición de la carta no es válida.'),
        };

        return [
            'carta' => [
                'condicion' => $condicion,
                'grado_academico' => trim($datos['grado_academico']),
                'docente_nombre' => trim($primeraFila['docente_nombre']),
                'facultad' => trim($datos['facultad']),
                'fecha_resolucion_texto' => $this->fechaLarga($fechaResolucion),
                'fecha_resolucion_anio' => (string) $fechaResolucion->year,
                'carrera_semestralizada' => $datos['carrera_semestralizada'] === 'SI',
                'numero_cite' => trim($datos['numero_cite']),
                'numero_resolucion' => trim($datos['numero_resolucion']),
                'leyenda_revision' => trim((string) ($datos['leyenda_revision'] ?? '')),
                'modalidad' => $datos['modalidad'] === 'HORARIO' ? 'Tiempo Horario' : 'Tiempo Completo',
                'fecha_inicio_texto' => $this->fechaLarga(CarbonImmutable::parse($datos['fecha_inicio'])),
                'decan_nombre' => trim($datos['decan_nombre']),
                'decan_cargo' => 'Decano De La Facultad de '.trim($datos['facultad']),
                'vicerrector_nombre' => trim($datos['vicerrector_nombre']),
                'vicerrector_cargo' => trim($datos['vicerrector_cargo']),
            ],
            'filas' => $filas->map(fn (array $fila): array => [
                'materia_sigla' => trim((string) ($fila['materia_sigla'] ?? '')),
                'grupo_texto' => isset($fila['grupo_id'])
                    ? str_pad((string) $fila['grupo_id'], 3, '0', STR_PAD_LEFT)
                    : '',
                'materia_nombre' => trim((string) ($fila['materia_nombre'] ?? '')),
                'horas_total' => (int) ($fila['horas_teoricas'] ?? 0)
                    + (int) ($fila['horas_practicas'] ?? 0)
                    + (int) ($fila['horas_laboratorio'] ?? 0),
            ])->values(),
        ];
    }

    private function validarDocenteUnico(Collection $filas): void
    {
        $docentes = $filas->pluck('docente_id')->map(fn (mixed $id): int => (int) $id)->unique();
        $nombres = $filas->pluck('docente_nombre')->map(fn (mixed $nombre): string => trim((string) $nombre))->unique();

        if ($filas->isEmpty()
            || $docentes->count() !== 1
            || $docentes->first() < 1
            || $nombres->count() !== 1
            || $nombres->first() === '') {
            throw new InvalidArgumentException('La designación no corresponde a un único docente.');
        }
    }

    private function fechaLarga(CarbonImmutable $fecha): string
    {
        $meses = [
            1 => 'enero',
            2 => 'febrero',
            3 => 'marzo',
            4 => 'abril',
            5 => 'mayo',
            6 => 'junio',
            7 => 'julio',
            8 => 'agosto',
            9 => 'septiembre',
            10 => 'octubre',
            11 => 'noviembre',
            12 => 'diciembre',
        ];

        return $fecha->day.' de '.$meses[$fecha->month].' de '.$fecha->year;
    }
}
