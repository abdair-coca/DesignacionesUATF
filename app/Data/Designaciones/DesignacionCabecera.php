<?php

namespace App\Data\Designaciones;

final readonly class DesignacionCabecera
{
    public function __construct(
        public ?string $fecha,
        public string $programaCodigo,
        public string $programaNombre,
        public string $gestion,
        public string $periodo,
        public ?string $observacion,
    ) {}

    /** @return array<string, string|null> */
    public function toArray(): array
    {
        return [
            'fecha' => $this->fecha,
            'programa_codigo' => $this->programaCodigo,
            'programa_nombre' => $this->programaNombre,
            'gestion' => $this->gestion,
            'periodo' => $this->periodo,
            'observacion' => $this->observacion,
        ];
    }
}
