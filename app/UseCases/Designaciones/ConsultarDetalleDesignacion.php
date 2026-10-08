<?php

namespace App\UseCases\Designaciones;

use App\Contracts\DesignacionesReadContract;
use App\Data\Designaciones\DecisionAsignacion;
use App\Data\Designaciones\DesignacionResumen;
use App\Data\Designaciones\DetalleAsignacion;
use App\Data\Designaciones\RevisionDesignacion;
use Illuminate\Support\Collection;

final class ConsultarDetalleDesignacion
{
    public function __construct(private DesignacionesReadContract $lectura) {}

    public function porCarrera(int $id, string $programa): ?DesignacionResumen
    {
        return $this->lectura->listar($programa, '0', '0')->first(
            fn (DesignacionResumen $designacion): bool => $designacion->id === $id,
        );
    }

    public function porUniversidad(int $id, string|int $gestion): ?DesignacionResumen
    {
        return $this->lectura->obtenerUniversidad($id, $gestion);
    }

    /** @return Collection<int, DetalleAsignacion> */
    public function filas(int $id): Collection
    {
        return $this->lectura->detallar($id);
    }

    /** @return Collection<int, DecisionAsignacion> */
    public function decisionesPorFila(int $id): Collection
    {
        return $this->lectura->listarDecisionesAsignacionDetalle($id);
    }

    public function revisionGeneral(int $id): RevisionDesignacion
    {
        return $this->lectura->obtenerRevisionDesignacion($id);
    }
}
