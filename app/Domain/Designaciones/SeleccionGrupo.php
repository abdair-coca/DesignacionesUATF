<?php

namespace App\Domain\Designaciones;

use App\Data\Designaciones\OfertaMateria;
use InvalidArgumentException;

final readonly class SeleccionGrupo
{
    /**
     * @param array<int, int> $gruposPermitidos
     */
    private function __construct(
        public int $grupoSiguiente,
        public array $gruposPermitidos,
        public bool $catalogoDisponible,
        public ?int $grupoActual,
    ) {}

    /**
     * @param array<int, int|null> $gruposAsignados
     */
    public static function calcular(
        OfertaMateria $oferta,
        array $gruposAsignados,
        ?int $grupoActual,
    ): self {
        $ultimoGrupoAsignado = 0;

        foreach ($gruposAsignados as $grupo) {
            if ($grupo !== null) {
                $ultimoGrupoAsignado = max($ultimoGrupoAsignado, $grupo);
            }
        }

        $grupoSiguiente = max($oferta->grupoSiguiente ?? 0, $ultimoGrupoAsignado + 1);
        $grupoSiguiente = max(1, $grupoSiguiente);
        $gruposPermitidos = array_values(array_unique([
            ...$oferta->grupos,
            $grupoSiguiente,
            ...($grupoActual === null ? [] : [$grupoActual]),
        ]));

        return new self($grupoSiguiente, $gruposPermitidos, $oferta->gruposDisponibles, $grupoActual);
    }

    public function validarNuevo(int $grupo): void
    {
        if ($grupo !== $this->grupoSiguiente) {
            throw new InvalidArgumentException('La nueva asignacion debe usar el siguiente grupo disponible.');
        }
    }

    public function validarEdicion(int $grupo): void
    {
        if (! $this->catalogoDisponible && $grupo !== $this->grupoActual && $grupo !== $this->grupoSiguiente) {
            throw new InvalidArgumentException('No se puede validar el grupo en este momento; conserva la materia y el grupo actuales.');
        }

        if ($this->catalogoDisponible && ! in_array($grupo, $this->gruposPermitidos, true)) {
            throw new InvalidArgumentException('El grupo seleccionado no esta disponible.');
        }
    }
}
