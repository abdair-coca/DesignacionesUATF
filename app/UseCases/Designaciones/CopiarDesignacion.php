<?php

namespace App\UseCases\Designaciones;

use App\Contracts\DesignacionesReadContract;
use App\Contracts\DesignacionesWriteContract;
use App\Data\Designaciones\DesignacionResumen;
use InvalidArgumentException;

final class CopiarDesignacion
{
    public function __construct(
        private DesignacionesReadContract $lectura,
        private DesignacionesWriteContract $escritura,
    ) {}

    public function ejecutar(
        string $programa,
        int $idOrigen,
        string|int $gestion,
        string|int $periodo,
        string $observacion,
    ): void {
        $origen = $this->lectura->listar($programa, '0', '0')->first(
            fn (DesignacionResumen $designacion): bool => $designacion->id === $idOrigen,
        );

        if ($origen === null) {
            throw new InvalidArgumentException('La designacion indicada no pertenece a su carrera.');
        }

        $this->escritura->copiar($idOrigen, $gestion, $periodo, $observacion);
    }
}
