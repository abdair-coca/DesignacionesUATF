<?php

namespace App\Data\Designaciones;

final readonly class DecisionAsignacion
{
    public function __construct(
        public int $idDetalle,
        public ?string $estado,
        public ?string $observacion,
    ) {}

    /** @return array{id_detalle: int, estado: string|null, observacion: string|null} */
    public function toArray(): array
    {
        return [
            'id_detalle' => $this->idDetalle,
            'estado' => $this->estado,
            'observacion' => $this->observacion,
        ];
    }
}
