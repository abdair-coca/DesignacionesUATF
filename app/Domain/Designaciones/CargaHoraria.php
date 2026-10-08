<?php

namespace App\Domain\Designaciones;

use InvalidArgumentException;

final readonly class CargaHoraria
{
    private function __construct(
        public int $teoricas,
        public int $practicas,
        public int $laboratorio,
    ) {}

    public static function validarEntrada(int $teoricas, int $practicas, int $laboratorio): self
    {
        if ($teoricas < 0 || $practicas < 0 || $laboratorio < 0) {
            throw new InvalidArgumentException('Las horas deben ser enteros no negativos.');
        }

        if ($teoricas + $practicas + $laboratorio < 1) {
            throw new InvalidArgumentException('Debe registrar al menos una hora en la asignacion.');
        }

        return new self($teoricas, $practicas, $laboratorio);
    }

    public function validarContraOferta(int $teoricas, int $practicas, int $laboratorio): void
    {
        if ($teoricas < 0 || $practicas < 0 || $laboratorio < 0
            || $this->teoricas > $teoricas
            || $this->practicas > $practicas
            || $this->laboratorio > $laboratorio) {
            throw new InvalidArgumentException('Las horas no pueden superar las horas oficiales de la materia.');
        }
    }
}
