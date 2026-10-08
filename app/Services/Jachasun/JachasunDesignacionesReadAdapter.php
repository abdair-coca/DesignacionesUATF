<?php

namespace App\Services\Jachasun;

use App\Contracts\DesignacionesReadContract;
use App\Data\Designaciones\DecisionAsignacion;
use App\Data\Designaciones\DesignacionCabecera;
use App\Data\Designaciones\DesignacionResumen;
use App\Data\Designaciones\DetalleAsignacion;
use App\Data\Designaciones\DocenteResumen;
use App\Data\Designaciones\OfertaMateria;
use App\Data\Designaciones\RevisionDesignacion;
use App\Exceptions\InvalidDesignacionesResponse;
use App\Models\Docente;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;

final class JachasunDesignacionesReadAdapter implements DesignacionesReadContract
{
    private const PERIODOS_UNIVERSITARIOS = ['1', '2'];

    public function __construct(
        private DatabaseManager $database,
        private DesignacionesResponseMapper $mapper,
    ) {}

    /** @return Collection<int, DesignacionResumen> */
    public function listar(string $programa, string|int $gestion, string|int $periodo): Collection
    {
        [$programa, $gestion, $periodo] = $this->validarParametros($programa, $gestion, $periodo);

        return $this->soloLectura(function ($connection) use ($programa, $gestion, $periodo): Collection {
            return collect($connection->select(
                'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)',
                [$programa, $gestion, $periodo],
            ))->map(fn (object|array $fila): DesignacionResumen => $this->mapper->designacionResumen($fila))->values();
        });
    }

    /** @return Collection<int, string> */
    public function listarProgramasPorFacultad(int $facultadId): Collection
    {
        if ($facultadId < 1) {
            throw new InvalidArgumentException('El identificador de facultad no es valido.');
        }

        return $this->soloLectura(function ($connection) use ($facultadId): Collection {
            return collect($connection->select(
                'SELECT DISTINCT programas.id_programa
FROM academico.alm_programas_facultades AS facultades
INNER JOIN academico.alm_programas AS programas
    ON programas.id_facultad = facultades.id_facultad
WHERE facultades.id_facultad = ?
ORDER BY programas.id_programa',
                [$facultadId],
            ))
                ->map(fn (object|array $fila): string => strtoupper(trim((string) $this->valor($fila, 'id_programa'))))
                ->filter()
                ->unique()
                ->sort()
                ->values();
        });
    }

    /** @return Collection<int, DesignacionResumen> */
    public function listarAprobadasPorFacultad(int $facultadId): Collection
    {
        return $this->listarProgramasPorFacultad($facultadId)
            ->flatMap(fn (string $programa): Collection => $this->listar($programa, '0', '0')
                ->filter(fn (DesignacionResumen $designacion): bool => strtoupper(trim($designacion->programaCodigo)) === $programa
                    && strtoupper(trim((string) $designacion->estado)) === 'APROBADO'))
            ->sort(function (DesignacionResumen $izquierda, DesignacionResumen $derecha): int {
                $fecha = strcmp((string) ($derecha->fecha ?? ''), (string) ($izquierda->fecha ?? ''));

                if ($fecha !== 0) {
                    return $fecha;
                }

                $id = $derecha->id <=> $izquierda->id;

                return $id !== 0 ? $id : strcmp($izquierda->programaCodigo, $derecha->programaCodigo);
            })
            ->values();
    }

    public function obtenerAprobadaPorFacultad(int $id, int $facultadId): ?DesignacionResumen
    {
        if ($id < 1) {
            throw new InvalidArgumentException('El identificador de designacion no es valido.');
        }

        return $this->listarAprobadasPorFacultad($facultadId)->first(
            fn (DesignacionResumen $designacion): bool => $designacion->id === $id,
        );
    }

    /** @return Collection<int, DocenteResumen> */
    public function listarDocentes(string $programa): Collection
    {
        $programa = strtoupper(trim($programa));

        if (! preg_match('/^[A-Z0-9_-]{2,20}$/', $programa)) {
            throw new InvalidArgumentException('El codigo de programa no es valido.');
        }

        return $this->soloLectura(function ($connection) use ($programa): Collection {
            return collect($connection->select(
                'SELECT * FROM academico.f_lista_docentes(?)',
                [$programa],
            ))
                ->map(fn (object|array $fila): ?DocenteResumen => $this->mapper->docenteResumen($fila))
                ->filter()
                ->unique('id')
                ->values();
        });
    }

    /** @return Collection<int, DocenteResumen> */
    public function buscarDocentes(string $termino, ?string $programa = null): Collection
    {
        $termino = $this->normalizarTermino($termino);

        if ($termino === '') {
            return collect();
        }

        return $this->soloLectura(function ($connection) use ($termino, $programa): Collection {
            return $this->docentesLocales($connection, $programa)
                ->filter(fn (DocenteResumen $docente): bool => $this->coincideBusqueda(
                    $docente->nombre.' '.$docente->ci,
                    $termino,
                ))
                ->unique(fn (DocenteResumen $docente): int => $docente->id)
                ->take(100)
                ->values();
        });
    }

    /**
     * @return Collection<int, DocenteResumen>
     */
    private function docentesLocales($connection, ?string $programa = null): Collection
    {
        $schema = $connection->getSchemaBuilder();

        // Se conserva sin cambios la prioridad y la alternativa vigentes del catálogo.
        if ($schema->hasTable('docentes')) {
            $query = Docente::on($connection->getName())
                ->newQuery()
                ->select(['id', 'nombre', 'ci'])
                ->orderBy('nombre')
                ->orderBy('id');

            if ($programa) {
                $query->where('carrera_origen_id', $programa);
            }

            return $query->get()
                ->map(fn (Docente $docente): ?DocenteResumen => $this->mapper->docenteResumen([
                    'id' => $docente->getAttribute('id'),
                    'nombre' => $docente->getAttribute('nombre'),
                    'ci' => $docente->getAttribute('ci'),
                ]))
                ->filter()
                ->values();
        }

        if (! $schema->hasTable('academico.docentes')) {
            throw new InvalidArgumentException('El catalogo local de docentes no esta disponible.');
        }

        $query = $connection->table('academico.docentes')
            ->selectRaw("id_docente AS id, concat_ws(' ', paterno, materno, nombres) AS nombre, ci")
            ->orderBy('nombres')
            ->orderBy('id_docente');

        if ($programa) {
            $query->where('id_programa', $programa);
        }

        return $query->get()
            ->map(fn (object|array $fila): ?DocenteResumen => $this->mapper->docenteResumen($fila))
            ->filter()
            ->values();
    }

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
    ): array {
        [$programa, $gestion, $periodo] = $this->validarParametros($programa, $gestion, $periodo);
        $pagina = max(1, $pagina);
        $porPagina = min(50, max(1, $porPagina));
        $busqueda = mb_substr(trim($busqueda), 0, 100);

        return $this->soloLectura(function ($connection) use ($programa, $gestion, $periodo, $pagina, $porPagina, $busqueda): array {
            $consulta = 'SELECT * FROM (
    SELECT *, COUNT(*) OVER() AS total_filas
    FROM designaciones.f_asignaciones(?, ?, ?)
) AS asignaciones';
            $parametros = [$programa, $gestion, $periodo];

            if ($busqueda !== '') {
                $consulta .= "\nWHERE asignaciones.r_detalle ILIKE ?";
                $parametros[] = '%'.$busqueda.'%';
            }

            $consulta .= "\nORDER BY r_id DESC\nLIMIT ? OFFSET ?";
            $parametros[] = $porPagina;
            $parametros[] = ($pagina - 1) * $porPagina;

            $filas = $connection->select($consulta, $parametros);
            $items = collect($filas)
                ->map(fn (object|array $fila): DesignacionResumen => $this->mapper->designacionResumen($fila))
                ->values();

            return [
                'items' => $items,
                'total' => (int) ($this->valor($filas[0] ?? [], 'total_filas') ?? 0),
            ];
        });
    }

    /** @return Collection<int, DesignacionResumen> */
    public function listarUniversidad(string|int $gestion): Collection
    {
        [, $gestion] = $this->validarParametros('UATF', $gestion, '0');

        return $this->soloLectura(function ($connection) use ($gestion): Collection {
            $consultas = collect(self::PERIODOS_UNIVERSITARIOS)
                ->map(fn (string $periodo): string => 'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)')
                ->implode("\nUNION ALL\n");
            $parametros = collect(self::PERIODOS_UNIVERSITARIOS)
                ->flatMap(fn (string $periodo): array => ['UATF', $gestion, $periodo])
                ->values()
                ->all();
            $consulta = implode("\n", [
                'SELECT todas.*',
                'FROM (',
                $consultas,
                ') AS todas',
                'ORDER BY r_fecha DESC NULLS LAST, r_id DESC',
            ]);

            return collect($connection->select($consulta, $parametros))
                ->map(fn (object|array $fila): DesignacionResumen => $this->mapper->designacionResumen($fila))
                ->values();
        });
    }

    /** @return array{items: Collection<int, DesignacionResumen>, total: int} */
    public function listarUniversidadPaginado(string|int $gestion, int $pagina = 1, int $porPagina = 10): array
    {
        [, $gestion] = $this->validarParametros('UATF', $gestion, '0');
        $pagina = max(1, $pagina);
        $porPagina = min(50, max(1, $porPagina));

        return $this->soloLectura(function ($connection) use ($gestion, $pagina, $porPagina): array {
            $consultas = collect(self::PERIODOS_UNIVERSITARIOS)
                ->map(fn (string $periodo): string => 'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)')
                ->implode("\nUNION ALL\n");
            $parametros = collect(self::PERIODOS_UNIVERSITARIOS)
                ->flatMap(fn (string $periodo): array => ['UATF', $gestion, $periodo])
                ->values()
                ->all();
            $parametros[] = $porPagina;
            $parametros[] = ($pagina - 1) * $porPagina;
            $consulta = implode("\n", [
                'SELECT * FROM (',
                '    SELECT todas.*, COUNT(*) OVER() AS total_filas',
                '    FROM (',
                $consultas,
                ') AS todas',
                ') AS asignaciones',
                'ORDER BY r_fecha DESC NULLS LAST, r_id DESC',
                'LIMIT ? OFFSET ?',
            ]);
            $filas = $connection->select($consulta, $parametros);

            return [
                'items' => collect($filas)
                    ->map(fn (object|array $fila): DesignacionResumen => $this->mapper->designacionResumen($fila))
                    ->values(),
                'total' => (int) ($this->valor($filas[0] ?? [], 'total_filas') ?? 0),
            ];
        });
    }

    public function obtenerUniversidad(int $id, string|int $gestion): ?DesignacionResumen
    {
        if ($id < 1) {
            throw new InvalidArgumentException('El identificador de designacion no es valido.');
        }

        [, $gestion] = $this->validarParametros('UATF', $gestion, '0');

        return $this->soloLectura(function ($connection) use ($id, $gestion): ?DesignacionResumen {
            $consultas = collect(self::PERIODOS_UNIVERSITARIOS)
                ->map(fn (string $periodo): string => 'SELECT * FROM designaciones.f_asignaciones(?, ?, ?)')
                ->implode("\nUNION ALL\n");
            $parametros = collect(self::PERIODOS_UNIVERSITARIOS)
                ->flatMap(fn (string $periodo): array => ['UATF', $gestion, $periodo])
                ->values()
                ->all();
            $parametros[] = $id;
            $fila = $connection->select(
                'SELECT * FROM ('.$consultas.') AS asignaciones WHERE r_id = ? LIMIT 1',
                $parametros,
            )[0] ?? null;

            return $fila === null ? null : $this->mapper->designacionResumen($fila);
        });
    }

    /** @return Collection<int, DetalleAsignacion> */
    public function detallar(int $id): Collection
    {
        if ($id < 1) {
            throw new InvalidArgumentException('El identificador de designacion no es valido.');
        }

        return $this->soloLectura(function ($connection) use ($id): Collection {
            return collect($connection->select(
                'SELECT * FROM designaciones.f_asignaciones_detalles(?)',
                [$id],
            ))->map(fn (object|array $fila): DetalleAsignacion => $this->mapper->detalleAsignacion($fila))->values();
        });
    }

    /** @return Collection<int, DecisionAsignacion> */
    public function listarDecisionesAsignacionDetalle(int $idAsignacion): Collection
    {
        if ($idAsignacion < 1) {
            throw new InvalidArgumentException('El identificador de designacion no es valido.');
        }

        return $this->soloLectura(function ($connection) use ($idAsignacion): Collection {
            return collect($connection->select(
                'SELECT * FROM designaciones.f_listar_decisiones_asignacion_detalle(?)',
                [$idAsignacion],
            ))->map(fn (object|array $fila): DecisionAsignacion => $this->mapper->decisionAsignacion($fila))->values();
        });
    }

    public function obtenerRevisionDesignacion(int $idAsignacion): RevisionDesignacion
    {
        if ($idAsignacion < 1) {
            throw new InvalidArgumentException('El identificador de designacion no es valido.');
        }

        return $this->soloLectura(function ($connection) use ($idAsignacion): RevisionDesignacion {
            $fila = $connection->select(
                'SELECT * FROM designaciones.f_obtener_revision_designacion(?)',
                [$idAsignacion],
            )[0] ?? null;

            if ($fila === null) {
                throw new InvalidDesignacionesResponse;
            }

            return $this->mapper->revisionDesignacion($fila);
        });
    }

    /** @return Collection<int, OfertaMateria> */
    public function ofertaMaterias(string $programa, string|int $gestion, string|int $periodo): Collection
    {
        [$programa, $gestion, $periodo] = $this->validarParametros($programa, $gestion, $periodo);

        if ($gestion === '0' || $periodo === '0') {
            throw new InvalidArgumentException('La oferta requiere una gestion y periodo validos.');
        }

        try {
            return $this->soloLectura(function ($connection) use ($programa, $gestion, $periodo): Collection {
                $filas = $connection->select(
                    'SELECT oferta.r_id_programa,
    oferta.r_id_gestion,
    oferta.r_id_periodo,
    oferta.r_id_materia,
    oferta.r_sigla,
    oferta.r_materia,
    oferta.r_nivel_academico,
    oferta.r_id_mencion,
    pm.hrs_teoricas,
    pm.hrs_practicas,
    pm.hrs_laboratorio,
    dct.id_grupo
FROM academico.f_oferta_materias(?, ?, ?) AS oferta
INNER JOIN academico.pln_materias AS pm
        ON pm.id_materia = oferta.r_id_materia
LEFT JOIN academico.dct_asignaciones AS dct
       ON dct.id_programa = oferta.r_id_programa
      AND dct.id_gestion = oferta.r_id_gestion
      AND dct.id_periodo = oferta.r_id_periodo
      AND dct.id_materia = oferta.r_id_materia
ORDER BY oferta.r_nivel_academico, oferta.r_sigla, dct.id_grupo',
                    [$programa, (int) $gestion, (int) $periodo],
                );

                return $this->normalizarOferta($filas, true, $this->leerAperturas($connection, (int) $gestion));
            });
        } catch (QueryException) {
            return $this->soloLectura(function ($connection) use ($programa, $gestion, $periodo): Collection {
                $filas = $connection->select(
                    'SELECT oferta.r_id_programa,
    oferta.r_id_gestion,
    oferta.r_id_periodo,
    oferta.r_id_materia,
    oferta.r_sigla,
    oferta.r_materia,
    oferta.r_nivel_academico,
    oferta.r_id_mencion,
    pm.hrs_teoricas,
    pm.hrs_practicas,
    pm.hrs_laboratorio
FROM academico.f_oferta_materias(?, ?, ?) AS oferta
INNER JOIN academico.pln_materias AS pm
        ON pm.id_materia = oferta.r_id_materia
ORDER BY oferta.r_nivel_academico, oferta.r_sigla',
                    [$programa, (int) $gestion, (int) $periodo],
                );

                return $this->normalizarOferta($filas, false);
            });
        }
    }

    /** @return array{gestion: string, periodo: string} */
    public function contextoActual(string $programa): array
    {
        $programa = strtoupper(trim($programa));

        if (! preg_match('/^[A-Z0-9_-]{2,20}$/', $programa)) {
            throw new InvalidArgumentException('El codigo de programa no es valido.');
        }

        try {
            return $this->soloLectura(function ($connection) use ($programa): array {
                $fila = $connection->selectOne(
                    'SELECT gestion, periodo
FROM public.gestion_periodo_directores
WHERE id_programa = ?',
                    [$programa],
                );

                if ($fila === null) {
                    throw new InvalidArgumentException('No existe un contexto academico vigente.');
                }

                return [
                    'gestion' => (string) $this->valor($fila, 'gestion'),
                    'periodo' => (string) $this->valor($fila, 'periodo'),
                ];
            });
        } catch (QueryException) {
            $ultima = $this->listar($programa, '0', '0')
                ->filter(fn (DesignacionResumen $designacion): bool => (int) $designacion->gestion > 0
                    && (int) $designacion->periodo > 0)
                ->sortByDesc(fn (DesignacionResumen $designacion): int => (int) $designacion->gestion * 100
                    + (int) $designacion->periodo)
                ->first();

            if ($ultima === null) {
                throw new InvalidArgumentException('No existe un contexto academico vigente.');
            }

            return ['gestion' => $ultima->gestion, 'periodo' => $ultima->periodo];
        }
    }

    public function obtener(int $id): ?DesignacionCabecera
    {
        if ($id < 1) {
            throw new InvalidArgumentException('El identificador de designacion no es valido.');
        }

        return $this->soloLectura(function ($connection) use ($id): ?DesignacionCabecera {
            $fila = $connection->select(
                "SELECT * FROM designaciones.f_designacion(?, NULL, '', 0, 0, NULL, '')",
                [$id],
            )[0] ?? null;

            return $fila === null ? null : $this->mapper->designacionCabecera($fila);
        });
    }

    /**
     * @param  array<int, object|array<string, mixed>>  $filas
     * @param  array<int, object|array<string, mixed>>  $aperturas
     * @return Collection<int, OfertaMateria>
     */
    private function normalizarOferta(array $filas, bool $gruposDisponibles, array $aperturas = []): Collection
    {
        $ultimoGrupoAbierto = collect($aperturas)->mapWithKeys(
            fn (object|array $apertura): array => [
                (string) $this->valor($apertura, 'materia_id') => (int) $this->valor($apertura, 'ultimo_grupo'),
            ],
        );

        return collect($filas)
            ->groupBy(fn (object|array $fila): string => (string) $this->valor($fila, 'r_id_materia'))
            ->map(function (Collection $materia) use ($gruposDisponibles, $ultimoGrupoAbierto): OfertaMateria {
                $fila = $materia->first();
                $materiaId = (int) $this->valor($fila, 'r_id_materia');
                $gruposCatalogo = $materia
                    ->map(fn (object|array $grupo): ?int => $this->entero($this->valor($grupo, 'id_grupo')))
                    ->filter(fn (?int $grupo): bool => $grupo !== null)
                    ->unique()
                    ->sort()
                    ->values();
                $ultimoGrupo = max($gruposCatalogo->max() ?? 0, $ultimoGrupoAbierto[(string) $materiaId] ?? 0);
                $grupoSiguiente = $gruposDisponibles ? $ultimoGrupo + 1 : null;

                return $this->mapper->ofertaMateria(
                    $fila,
                    $gruposDisponibles ? range(1, $ultimoGrupo + 1) : [],
                    $grupoSiguiente,
                    $gruposDisponibles,
                );
            })
            ->values();
    }

    private function leerAperturas($connection, int $gestion): array
    {
        try {
            return $connection->select(
                'SELECT materia_id, ultimo_grupo FROM v_designacion_grupos_abiertos WHERE gestion = ?',
                [$gestion],
            );
        } catch (\Throwable) {
            return [];
        }
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

    private function normalizarBusqueda(mixed $valor): string
    {
        $valor = Str::ascii(mb_strtolower(trim((string) $valor)));

        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', $valor) ?? '');
    }

    private function normalizarTermino(string $termino): string
    {
        return $this->normalizarBusqueda(mb_substr(trim($termino), 0, 100));
    }

    private function coincideBusqueda(string $valor, string $termino): bool
    {
        $texto = $this->normalizarBusqueda($valor);
        $tokens = array_filter(explode(' ', $termino));

        return $tokens !== [] && collect($tokens)->every(fn (string $token): bool => str_contains($texto, $token));
    }

    private function soloLectura(callable $consulta): mixed
    {
        $connection = $this->database->connection(config('database.default', 'pgsql'));

        return $connection->transaction(function () use ($connection, $consulta): mixed {
            $connection->statement('SET TRANSACTION READ ONLY');

            return $consulta($connection);
        });
    }

    private function valor(object|array $fila, string $campo): mixed
    {
        return is_array($fila) ? ($fila[$campo] ?? null) : ($fila->{$campo} ?? null);
    }

    private function entero(mixed $valor): ?int
    {
        return $valor === null ? null : (int) $valor;
    }
}
