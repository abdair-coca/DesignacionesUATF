<?php

namespace App\Data\Designaciones;

final readonly class PreparacionEdicionDesignacion
{
    /**
     * @param array<int, DetalleAsignacion> $filas
     * @param array<int, OfertaMateria> $materiasOferta
     */
    public function __construct(
        public DesignacionResumen $designacion,
        public array $filas,
        public array $materiasOferta,
    ) {}
}
