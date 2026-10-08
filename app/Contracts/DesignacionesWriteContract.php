<?php

namespace App\Contracts;

use App\Data\Designaciones\DecisionAsignacion;
use App\Data\Designaciones\DesignacionCabecera;
use App\Data\Designaciones\DetalleAsignacion;
use App\Data\Designaciones\RevisionDesignacion;
use Illuminate\Support\Collection;

interface DesignacionesWriteContract
{
    public function copiar(int $id, string|int $gestion, string|int $periodo, string $observacion): void;

    public function insertar(
        string $programa,
        string $fecha,
        string|int $gestion,
        string|int $periodo,
        string $observacion,
    ): ?DesignacionCabecera;

    public function actualizar(
        int $id,
        string $programa,
        string $fecha,
        string|int $gestion,
        string|int $periodo,
        string $observacion,
    ): ?DesignacionCabecera;

    /** @return Collection<int, DetalleAsignacion> */
    public function guardarDetalle(
        int $id,
        int $idDetalle,
        int $idDocente,
        int $idMateria,
        int $idGrupo,
        int $horasTeoria,
        int $horasPractica,
        int $horasLaboratorio,
        int $gestion,
        bool $registrarApertura,
    ): Collection;

    public function guardarDecisionAsignacionDetalle(
        int $idAsignacion,
        int $idDetalle,
        string $estado,
        ?string $observacion,
    ): DecisionAsignacion;

    public function guardarRevisionDesignacion(
        int $idAsignacion,
        string $estado,
        ?string $observacion,
    ): RevisionDesignacion;
}
