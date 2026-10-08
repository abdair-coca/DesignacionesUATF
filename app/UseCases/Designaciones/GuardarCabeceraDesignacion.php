<?php

namespace App\UseCases\Designaciones;

use App\Contracts\DesignacionesReadContract;
use App\Contracts\DesignacionesWriteContract;
use App\Data\Designaciones\DesignacionCabecera;
use App\Data\Designaciones\DesignacionResumen;
use InvalidArgumentException;

final class GuardarCabeceraDesignacion
{
    public function __construct(
        private DesignacionesReadContract $lectura,
        private DesignacionesWriteContract $escritura,
    ) {}

    public function crear(
        string $programa,
        string $fecha,
        string|int $gestion,
        string|int $periodo,
        string $observacion,
    ): ?DesignacionCabecera {
        return $this->escritura->insertar($programa, $fecha, $gestion, $periodo, $observacion);
    }

    public function actualizar(
        int $id,
        string $programa,
        string $fecha,
        string|int $gestion,
        string|int $periodo,
        string $observacion,
    ): ?DesignacionCabecera {
        $designacion = $this->lectura->listar($programa, '0', '0')->first(
            fn (DesignacionResumen $fila): bool => $fila->id === $id,
        );

        if ($designacion === null) {
            throw new InvalidArgumentException('La designacion indicada no pertenece a su carrera.');
        }

        return $this->escritura->actualizar($id, $programa, $fecha, $gestion, $periodo, $observacion);
    }
}
