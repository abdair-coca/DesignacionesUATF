<?php

namespace App\Contracts;

use App\Data\Designaciones\DecisionAsignacion;
use App\Data\Designaciones\DesignacionCabecera;
use App\Data\Designaciones\DesignacionResumen;
use App\Data\Designaciones\DetalleAsignacion;
use App\Data\Designaciones\DocenteResumen;
use App\Data\Designaciones\OfertaMateria;
use App\Data\Designaciones\RevisionDesignacion;
use Illuminate\Support\Collection;

interface DesignacionesReadContract
{
    /** @return Collection<int, DesignacionResumen> */
    public function listar(string $programa, string|int $gestion, string|int $periodo): Collection;

    /** @return Collection<int, string> */
    public function listarProgramasPorFacultad(int $facultadId): Collection;

    /** @return Collection<int, DesignacionResumen> */
    public function listarAprobadasPorFacultad(int $facultadId): Collection;

    public function obtenerAprobadaPorFacultad(int $id, int $facultadId): ?DesignacionResumen;

    /** @return Collection<int, DocenteResumen> */
    public function listarDocentes(string $programa): Collection;

    /** @return Collection<int, DocenteResumen> */
    public function buscarDocentes(string $termino, ?string $programa = null): Collection;

    /**
     * @return array{items: Collection<int, DesignacionResumen>, total: int}
     */
    public function listarPaginado(
        string $programa,
        string|int $gestion,
        string|int $periodo,
        int $pagina = 1,
        int $porPagina = 10,
        string $busqueda = '',
    ): array;

    /** @return Collection<int, DesignacionResumen> */
    public function listarUniversidad(string|int $gestion): Collection;

    /** @return array{items: Collection<int, DesignacionResumen>, total: int} */
    public function listarUniversidadPaginado(string|int $gestion, int $pagina = 1, int $porPagina = 10): array;

    public function obtenerUniversidad(int $id, string|int $gestion): ?DesignacionResumen;

    /** @return Collection<int, DetalleAsignacion> */
    public function detallar(int $id): Collection;

    /** @return Collection<int, DecisionAsignacion> */
    public function listarDecisionesAsignacionDetalle(int $idAsignacion): Collection;

    public function obtenerRevisionDesignacion(int $idAsignacion): RevisionDesignacion;

    /** @return Collection<int, OfertaMateria> */
    public function ofertaMaterias(string $programa, string|int $gestion, string|int $periodo): Collection;

    /** @return array{gestion: string, periodo: string} */
    public function contextoActual(string $programa): array;

    public function obtener(int $id): ?DesignacionCabecera;
}
