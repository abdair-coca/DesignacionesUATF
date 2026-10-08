<?php

namespace App\Services\Jachasun;

use App\Contracts\DesignacionesWriteContract;
use App\Data\Designaciones\DecisionAsignacion;
use App\Data\Designaciones\DesignacionCabecera;
use App\Data\Designaciones\DetalleAsignacion;
use App\Data\Designaciones\RevisionDesignacion;
use Illuminate\Database\DatabaseManager;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class JachasunDesignacionesWriteAdapter implements DesignacionesWriteContract
{
    public function __construct(
        private DatabaseManager $database,
        private DesignacionesResponseMapper $mapper,
    ) {}

    public function copiar(int $id, string|int $gestion, string|int $periodo, string $observacion): void
    {
        $this->escribir(function ($connection) use ($id, $gestion, $periodo, $observacion): void {
            $connection->select(
                'SELECT * FROM designaciones.f_copiar_designacion(?, ?, ?, ?)',
                [$id, (int) $gestion, (int) $periodo, $observacion],
            );
        });
    }

    public function insertar(
        string $programa,
        string $fecha,
        string|int $gestion,
        string|int $periodo,
        string $observacion,
    ): ?DesignacionCabecera {
        return $this->escribir(function ($connection) use ($programa, $fecha, $gestion, $periodo, $observacion): ?DesignacionCabecera {
            $fila = $connection->select(
                "SELECT * FROM designaciones.f_designacion(0, ?, ?, ?, ?, ?, 'INS')",
                [$fecha, $programa, (int) $gestion, (int) $periodo, $observacion],
            )[0] ?? null;

            return $fila === null ? null : $this->mapper->designacionCabecera($fila);
        });
    }

    public function actualizar(
        int $id,
        string $programa,
        string $fecha,
        string|int $gestion,
        string|int $periodo,
        string $observacion,
    ): ?DesignacionCabecera {
        return $this->escribir(function ($connection) use ($id, $programa, $fecha, $gestion, $periodo, $observacion): ?DesignacionCabecera {
            $fila = $connection->select(
                "SELECT * FROM designaciones.f_designacion(?, ?, ?, ?, ?, ?, 'UPD')",
                [$id, $fecha, $programa, (int) $gestion, (int) $periodo, $observacion],
            )[0] ?? null;

            return $fila === null ? null : $this->mapper->designacionCabecera($fila);
        });
    }

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
    ): Collection {
        return $this->escribir(function ($connection) use (
            $id,
            $idDetalle,
            $idDocente,
            $idMateria,
            $idGrupo,
            $horasTeoria,
            $horasPractica,
            $horasLaboratorio,
            $gestion,
            $registrarApertura,
        ): Collection {
            if ($registrarApertura && $connection->getSchemaBuilder()->hasTable('designacion_grupo_aperturas')) {
                $connection->insert(
                    'INSERT INTO designacion_grupo_aperturas (materia_id, gestion, numero, created_at, updated_at) '
                    .'VALUES (?, ?, ?, CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)',
                    [$idMateria, $gestion, $idGrupo],
                );
            }

            $filas = $connection->select(
                'SELECT * FROM designaciones.f_designacion_detalle(?, ?, ?, ?, ?, ?, ?, ?)',
                [$id, $idDetalle, $idDocente, $idMateria, $idGrupo, $horasTeoria, $horasPractica, $horasLaboratorio],
            );

            return collect($filas)
                ->map(fn (object|array $fila): DetalleAsignacion => $this->mapper->detalleAsignacion($fila))
                ->values();
        });
    }

    public function guardarDecisionAsignacionDetalle(
        int $idAsignacion,
        int $idDetalle,
        string $estado,
        ?string $observacion,
    ): DecisionAsignacion {
        return $this->escribir(function ($connection) use ($idAsignacion, $idDetalle, $estado, $observacion): DecisionAsignacion {
            $fila = $connection->select(
                'SELECT * FROM designaciones.f_guardar_decision_asignacion_detalle(?, ?, ?, ?)',
                [$idAsignacion, $idDetalle, $estado, $observacion === '' ? null : $observacion],
            )[0] ?? null;

            if ($fila === null) {
                throw new InvalidArgumentException('La decision de la asignacion no pudo confirmarse.');
            }

            return $this->mapper->decisionAsignacion($fila);
        });
    }

    public function guardarRevisionDesignacion(
        int $idAsignacion,
        string $estado,
        ?string $observacion,
    ): RevisionDesignacion {
        return $this->escribir(function ($connection) use ($idAsignacion, $estado, $observacion): RevisionDesignacion {
            $fila = $connection->select(
                'SELECT * FROM designaciones.f_guardar_revision_designacion(?, ?, ?)',
                [$idAsignacion, $estado, $observacion],
            )[0] ?? null;

            if ($fila === null) {
                throw new InvalidArgumentException('La revision de la designacion no pudo confirmarse.');
            }

            return $this->mapper->revisionDesignacion($fila);
        });
    }

    private function escribir(callable $consulta): mixed
    {
        $connection = $this->database->connection(config('database.default', 'pgsql'));

        return $connection->transaction(fn (): mixed => $consulta($connection));
    }
}
