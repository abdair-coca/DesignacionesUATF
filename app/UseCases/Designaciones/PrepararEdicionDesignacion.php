<?php

namespace App\UseCases\Designaciones;

use App\Contracts\DesignacionesReadContract;
use App\Data\Designaciones\DesignacionResumen;
use App\Data\Designaciones\OfertaMateria;
use App\Data\Designaciones\PreparacionEdicionDesignacion;
use Illuminate\Support\Collection;

final class PrepararEdicionDesignacion
{
    public function __construct(private DesignacionesReadContract $lectura) {}

    public function preparar(DesignacionResumen $designacion): PreparacionEdicionDesignacion
    {
        return new PreparacionEdicionDesignacion(
            $designacion,
            $this->lectura->detallar($designacion->id)->all(),
            $this->lectura->ofertaMaterias(
                $designacion->programaCodigo,
                $designacion->gestion,
                $designacion->periodo,
            )->all(),
        );
    }

    /** @return Collection<int, OfertaMateria> */
    public function materiasOferta(string $programa, string|int $gestion, string|int $periodo): Collection
    {
        return $this->lectura->ofertaMaterias($programa, $gestion, $periodo);
    }
}
