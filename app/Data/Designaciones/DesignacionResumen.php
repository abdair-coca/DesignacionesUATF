<?php

namespace App\Data\Designaciones;

final readonly class DesignacionResumen
{
    public function __construct(
        public int $id,
        public string $programaCodigo,
        public string $programaNombre,
        public string $detalle,
        public ?string $fecha,
        public string $gestion,
        public string $periodo,
        public ?string $observacion,
        public ?string $estado,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'programa_codigo' => $this->programaCodigo,
            'programa_nombre' => $this->programaNombre,
            'detalle' => $this->detalle,
            'fecha' => $this->fecha,
            'gestion' => $this->gestion,
            'periodo' => $this->periodo,
            'observacion' => $this->observacion,
            'estado' => $this->estado,
        ];
    }
}
