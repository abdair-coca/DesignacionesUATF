<?php

namespace App\Services\Jachasun;

use App\Contracts\DesignacionesReadContract;
use App\Contracts\DesignacionesWriteContract;
use App\Data\Designaciones\PreparacionEdicionDesignacion;
use App\Data\Designaciones\RevisionDesignacion;
use App\Domain\Designaciones\ReglasDecisionDesignacion;
use App\UseCases\Designaciones\ConsultarDesignaciones;
use App\UseCases\Designaciones\ConsultarDetalleDesignacion;
use App\UseCases\Designaciones\CopiarDesignacion;
use App\UseCases\Designaciones\GuardarAsignacion;
use App\UseCases\Designaciones\GuardarCabeceraDesignacion;
use App\UseCases\Designaciones\GuardarDecisionPorFila;
use App\UseCases\Designaciones\GuardarRevisionGeneral;
use App\UseCases\Designaciones\PrepararEdicionDesignacion;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class JachasunDesignacionesService
{
    private readonly DesignacionesReadContract $lectura;

    private readonly DesignacionesWriteContract $escritura;

    private readonly ConsultarDesignaciones $consultarDesignaciones;

    private readonly ConsultarDetalleDesignacion $consultarDetalle;

    private readonly PrepararEdicionDesignacion $prepararEdicion;

    private readonly CopiarDesignacion $copiarDesignacion;

    private readonly GuardarCabeceraDesignacion $guardarCabecera;

    private readonly GuardarAsignacion $guardarAsignacion;

    private readonly GuardarDecisionPorFila $guardarDecision;

    private readonly GuardarRevisionGeneral $guardarRevision;

    public function __construct(
        ?DatabaseManager $database = null,
        ?DesignacionesReadContract $lectura = null,
        ?DesignacionesWriteContract $escritura = null,
    ) {
        if ($lectura === null || $escritura === null) {
            $database ??= app(DatabaseManager::class);
            $mapper = new DesignacionesResponseMapper;
            $lectura ??= new JachasunDesignacionesReadAdapter($database, $mapper);
            $escritura ??= new JachasunDesignacionesWriteAdapter($database, $mapper);
        }

        $this->lectura = $lectura;
        $this->escritura = $escritura;
        $this->consultarDesignaciones = new ConsultarDesignaciones($lectura);
        $this->consultarDetalle = new ConsultarDetalleDesignacion($lectura);
        $this->prepararEdicion = new PrepararEdicionDesignacion($lectura);
        $this->copiarDesignacion = new CopiarDesignacion($lectura, $escritura);
        $this->guardarCabecera = new GuardarCabeceraDesignacion($lectura, $escritura);
        $this->guardarAsignacion = new GuardarAsignacion($lectura, $escritura);
        $reglasDecision = new ReglasDecisionDesignacion;
        $this->guardarDecision = new GuardarDecisionPorFila($escritura, $reglasDecision);
        $this->guardarRevision = new GuardarRevisionGeneral($escritura, $reglasDecision);
    }

    /**
     * @return Collection<int, array<string, int|string|null>>
     */
    public function listar(string $programa, string|int $gestion, string|int $periodo): Collection
    {
        return $this->consultarDesignaciones->porCarrera($programa, $gestion, $periodo)
            ->map(fn ($designacion): array => $designacion->toArray())
            ->values();
    }

    /** @return Collection<int, string> */
    public function listarProgramasPorFacultad(int $facultadId): Collection
    {
        return $this->consultarDesignaciones->programasDeFacultad($facultadId);
    }

    /**
     * Lista únicamente las designaciones aprobadas de todas las carreras de una
     * facultad, con orden estable para la búsqueda y paginación del listado.
     *
     * @return Collection<int, array<string, int|string|null>>
     */
    public function listarAprobadasPorFacultad(int $facultadId): Collection
    {
        return $this->consultarDesignaciones->aprobadasDeFacultad($facultadId)
            ->map(fn ($designacion): array => $designacion->toArray())
            ->values();
    }

    /**
     * @return array<string, int|string|null>|null
     */
    public function obtenerAprobadaPorFacultad(int $id, int $facultadId): ?array
    {
        return $this->consultarDesignaciones->aprobadaDeFacultad($id, $facultadId)?->toArray();
    }

    /**
     * @return Collection<int, array<string, int|string|null>>
     */
    public function listarDocentes(string $programa): Collection
    {
        return $this->consultarDesignaciones->docentesPorCarrera($programa)
            ->map(fn ($docente): array => $docente->toArray(includePrograma: true))
            ->values();
    }

    /**
     * @return Collection<int, array{id: int, nombre: string, ci: string|null}>
     */
    public function buscarDocentes(string $termino, ?string $programa = null): Collection
    {
        return $this->consultarDesignaciones->buscarDocentes($termino, $programa)
            ->map(fn ($docente): array => $docente->toArray())
            ->values();
    }

    /**
     * @return array{items: Collection<int, array<string, int|string|null>>, total: int}
     */
    public function listarPaginado(
        string $programa,
        string|int $gestion,
        string|int $periodo,
        int $pagina = 1,
        int $porPagina = 10,
        string $busqueda = '',
    ): array {
        $resultado = $this->consultarDesignaciones->carreraPaginada(
            $programa,
            $gestion,
            $periodo,
            $pagina,
            $porPagina,
            $busqueda,
        );

        return [
            'items' => $resultado['items']->map(fn ($designacion): array => $designacion->toArray())->values(),
            'total' => $resultado['total'],
        ];
    }

    /**
     * Lista las designaciones de toda la universidad para una gestion.
     *
     * @return Collection<int, array<string, int|string|null>>
     */
    public function listarUniversidad(string|int $gestion): Collection
    {
        return $this->consultarDesignaciones->universidad($gestion)
            ->map(fn ($designacion): array => $designacion->toArray())
            ->values();
    }

    /**
     * @return array{items: Collection<int, array<string, int|string|null>>, total: int}
     */
    public function listarUniversidadPaginado(string|int $gestion, int $pagina = 1, int $porPagina = 10): array
    {
        $resultado = $this->consultarDesignaciones->universidadPaginada($gestion, $pagina, $porPagina);

        return [
            'items' => $resultado['items']->map(fn ($designacion): array => $designacion->toArray())->values(),
            'total' => $resultado['total'],
        ];
    }

    /** @return array<string, int|string|null>|null */
    public function obtenerUniversidad(int $id, string|int $gestion): ?array
    {
        return $this->consultarDesignaciones->universidadPorId($id, $gestion)?->toArray();
    }

    /**
     * @return Collection<int, array<string, int|string|null>>
     */
    public function detallar(int $id): Collection
    {
        return $this->consultarDetalle->filas($id)
            ->map(fn ($detalle): array => $detalle->toArray())
            ->values();
    }

    public function prepararEdicion(int $id, string $programa): ?PreparacionEdicionDesignacion
    {
        $designacion = $this->consultarDetalle->porCarrera($id, $programa);

        return $designacion === null ? null : $this->prepararEdicion->preparar($designacion);
    }

    /**
     * @return Collection<int, array{id_detalle: int, estado: string|null, observacion: string|null}>
     */
    public function listarDecisionesAsignacionDetalle(int $idAsignacion): Collection
    {
        return $this->consultarDetalle->decisionesPorFila($idAsignacion)
            ->map(fn ($decision): array => $decision->toArray())
            ->values();
    }

    public function obtenerRevisionDesignacion(int $idAsignacion): RevisionDesignacion
    {
        return $this->consultarDetalle->revisionGeneral($idAsignacion);
    }

    /**
     * @return Collection<int, array<string, int|string|null|array<int, int>>>
     */
    public function ofertaMaterias(string $programa, string|int $gestion, string|int $periodo): Collection
    {
        return $this->prepararEdicion->materiasOferta($programa, $gestion, $periodo)
            ->map(fn ($materia): array => $materia->toArray())
            ->values();
    }

    /** @return array{gestion: string, periodo: string} */
    public function contextoActual(string $programa): array
    {
        return $this->consultarDesignaciones->contextoActual($programa);
    }

    /** @return array<string, string|null>|null */
    public function obtener(int $id): ?array
    {
        return $this->consultarDesignaciones->cabeceraPorId($id)?->toArray();
    }

    /** Copia una designacion origen a una gestion/periodo nuevo. */
    public function copiar(string $programa, int $id, string|int $gestion, string|int $periodo, string $obs): void
    {
        [$programa, $gestion, $periodo] = $this->validarParametros($programa, $gestion, $periodo);
        $this->copiarDesignacion->ejecutar($programa, $id, $gestion, $periodo, $obs);
    }

    /**
     * Inserta una designacion nueva y devuelve su cabecera normalizada.
     *
     * @return array<string, int|string|null>
     */
    public function insertar(string $programa, string $fecha, string|int $gestion, string|int $periodo, string $obs): array
    {
        [$programa, $gestion, $periodo] = $this->validarParametros($programa, $gestion, $periodo);
        $fecha = $this->validarFecha($fecha);

        return $this->guardarCabecera->crear($programa, $fecha, $gestion, $periodo, $obs)?->toArray() ?? [];
    }

    /**
     * Actualiza una cabecera después de confirmar su pertenencia al programa.
     *
     * @return array<string, int|string|null>
     */
    public function actualizar(int $id, string $programa, string $fecha, string|int $gestion, string|int $periodo, string $obs): array
    {
        if ($id < 1) {
            throw new InvalidArgumentException('El identificador de designacion no es valido.');
        }

        [$programa, $gestion, $periodo] = $this->validarParametros($programa, $gestion, $periodo);
        $fecha = $this->validarFecha($fecha);

        return $this->guardarCabecera->actualizar($id, $programa, $fecha, $gestion, $periodo, $obs)?->toArray() ?? [];
    }

    /**
     * Inserta o actualiza una asignación luego de validar el alcance y las reglas vigentes.
     *
     * @return Collection<int, array<string, int|string|null>>
     */
    public function guardarDetalle(
        int $id,
        string $programa,
        int $idDetalle,
        int $idDocente,
        int $idMateria,
        int $idGrupo,
        int $hrsTeoria,
        int $hrsPractica,
        int $hrsLaboratorio,
    ): Collection {
        return $this->guardarAsignacion->ejecutar(
            $id,
            $programa,
            $idDetalle,
            $idDocente,
            $idMateria,
            $idGrupo,
            $hrsTeoria,
            $hrsPractica,
            $hrsLaboratorio,
        )->map(fn ($detalle): array => $detalle->toArray())->values();
    }

    /** @return array{estado: string, observacion: string|null} */
    public function guardarRevisionDesignacion(int $idAsignacion, string $estado, ?string $observacion): array
    {
        return $this->guardarRevision->ejecutar($idAsignacion, $estado, $observacion)->toArray();
    }

    /** @return array{id_detalle: int, estado: string, observacion: string|null} */
    public function guardarDecisionAsignacionDetalle(
        int $idAsignacion,
        int $idDetalle,
        string $estado,
        ?string $observacion,
    ): array {
        return $this->guardarDecision->ejecutar($idAsignacion, $idDetalle, $estado, $observacion)->toArray();
    }

    /** @return array{string, string, string} */
    private function validarParametros(string $programa, string|int $gestion, string|int $periodo): array
    {
        $programa = strtoupper(trim($programa));
        $gestion = trim((string) $gestion);
        $periodo = trim((string) $periodo);

        if (! preg_match('/^[A-Z0-9_-]{2,20}$/', $programa)) {
            throw new InvalidArgumentException('El codigo de programa no es valido.');
        }

        if (! preg_match('/^(?:0|\d{4})$/', $gestion)) {
            throw new InvalidArgumentException('La gestion debe tener cuatro digitos o ser 0.');
        }

        if (! preg_match('/^(?:0|\d{1,2})$/', $periodo)) {
            throw new InvalidArgumentException('El periodo debe ser numerico o ser 0.');
        }

        return [$programa, $gestion, $periodo];
    }

    private function validarFecha(string $fecha): string
    {
        $fecha = trim($fecha);

        if ($fecha !== '' && strtotime($fecha) === false) {
            throw new InvalidArgumentException('La fecha no es valida.');
        }

        return $fecha;
    }
}
