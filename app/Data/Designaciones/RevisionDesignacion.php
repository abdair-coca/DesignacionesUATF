<?php

namespace App\Data\Designaciones;

final readonly class RevisionDesignacion
{
    public function __construct(
        public ?string $estado,
        public ?string $observacion,
    ) {}

    /** @return array{estado: string|null, observacion: string|null} */
    public function toArray(): array
    {
        return [
            'estado' => $this->estado,
            'observacion' => $this->observacion,
        ];
    }
}
