<?php

namespace App\Data\Designaciones;

final readonly class DocenteResumen
{
    public function __construct(
        public int $id,
        public string $nombre,
        public ?string $ci,
        public ?string $programaCodigo = null,
        public ?string $programaNombre = null,
    ) {}

    /** @return array<string, int|string|null> */
    public function toArray(bool $includePrograma = false): array
    {
        $datos = [
            'id' => $this->id,
            'nombre' => $this->nombre,
            'ci' => $this->ci,
        ];

        if ($includePrograma) {
            $datos['programa_codigo'] = $this->programaCodigo;
            $datos['programa_nombre'] = $this->programaNombre;
        }

        return $datos;
    }
}
