<?php

namespace App\UseCases\Designaciones;

use App\Contracts\DesignacionesReadContract;
use App\Data\Designaciones\DesignacionCabecera;
use App\Data\Designaciones\DesignacionResumen;
use App\Data\Designaciones\DocenteResumen;
use Illuminate\Support\Collection;

final class ConsultarDesignaciones
{
    public function __construct(private DesignacionesReadContract $lectura) {}

    /** @return Collection<int, DesignacionResumen> */
    public function porCarrera(string $programa, string|int $gestion, string|int $periodo): Collection
    {
        return $this->lectura->listar($programa, $gestion, $periodo);
    }

    /** @return Collection<int, string> */
    public function programasDeFacultad(int $facultadId): Collection
    {
        return $this->lectura->listarProgramasPorFacultad($facultadId);
    }

    /** @return Collection<int, DesignacionResumen> */
    public function aprobadasDeFacultad(int $facultadId): Collection
    {
        return $this->lectura->listarAprobadasPorFacultad($facultadId);
    }

    public function aprobadaDeFacultad(int $id, int $facultadId): ?DesignacionResumen
    {
        return $this->lectura->obtenerAprobadaPorFacultad($id, $facultadId);
    }

    /** @return Collection<int, DesignacionResumen> */
    public function universidad(string|int $gestion): Collection
    {
        return $this->lectura->listarUniversidad($gestion);
    }

    /** @return array{items: Collection<int, DesignacionResumen>, total: int} */
    public function universidadPaginada(string|int $gestion, int $pagina = 1, int $porPagina = 10): array
    {
        return $this->lectura->listarUniversidadPaginado($gestion, $pagina, $porPagina);
    }

    /** @return array{items: Collection<int, DesignacionResumen>, total: int} */
    public function carreraPaginada(
        string $programa,
        string|int $gestion,
        string|int $periodo,
        int $pagina = 1,
        int $porPagina = 10,
        string $busqueda = '',
    ): array {
        return $this->lectura->listarPaginado($programa, $gestion, $periodo, $pagina, $porPagina, $busqueda);
    }

    public function universidadPorId(int $id, string|int $gestion): ?DesignacionResumen
    {
        return $this->lectura->obtenerUniversidad($id, $gestion);
    }

    /** @return Collection<int, DocenteResumen> */
    public function docentesPorCarrera(string $programa): Collection
    {
        return $this->lectura->listarDocentes($programa);
    }

    /** @return Collection<int, DocenteResumen> */
    public function buscarDocentes(string $termino, ?string $programa = null): Collection
    {
        return $this->lectura->buscarDocentes($termino, $programa);
    }

    public function contextoActual(string $programa): array
    {
        return $this->lectura->contextoActual($programa);
    }

    public function cabeceraPorId(int $id): ?DesignacionCabecera
    {
        return $this->lectura->obtener($id);
    }
}
