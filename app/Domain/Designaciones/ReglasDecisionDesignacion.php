<?php

namespace App\Domain\Designaciones;

use App\Data\Designaciones\DecisionAsignacion;
use App\Data\Designaciones\RevisionDesignacion;
use InvalidArgumentException;

final class ReglasDecisionDesignacion
{
    public function decisionFila(int $idDetalle, string $estado, ?string $observacion): DecisionAsignacion
    {
        if ($idDetalle < 1) {
            throw new InvalidArgumentException('El identificador de asignacion no es valido.');
        }

        $estado = strtoupper(trim($estado));

        if (! in_array($estado, ['APROBADA', 'RECHAZADA'], true)) {
            throw new InvalidArgumentException('El estado de decision no es valido.');
        }

        $observacion = trim((string) $observacion);

        if (mb_strlen($observacion) > 1000) {
            throw new InvalidArgumentException('La observacion supera el maximo permitido.');
        }

        return new DecisionAsignacion($idDetalle, $estado, $observacion === '' ? null : $observacion);
    }

    public function revisionGeneral(string $estado, ?string $observacion): RevisionDesignacion
    {
        $estado = strtoupper(trim($estado));

        if (! in_array($estado, ['APROBADO', 'OBSERVADA'], true)) {
            throw new InvalidArgumentException('El estado de revision no es valido.');
        }

        $observacion = trim((string) $observacion);

        if (mb_strlen($observacion) > 1000) {
            throw new InvalidArgumentException('La observacion supera el maximo permitido.');
        }

        if ($estado === 'OBSERVADA' && $observacion === '') {
            throw new InvalidArgumentException('La observacion es obligatoria.');
        }

        return new RevisionDesignacion($estado, $estado === 'APROBADO' || $observacion === '' ? null : $observacion);
    }

    /**
     * Un estado ausente o no reconocido representa una fila pendiente, igual
     * que el contrato vigente de revisión general.
     *
     * @param array<int, string|null> $decisiones
     */
    public function estadoGeneralCalculado(array $decisiones): string
    {
        if ($decisiones === []) {
            return 'SOLICITADO';
        }

        $tieneRechazada = false;

        foreach ($decisiones as $decision) {
            $estado = strtoupper(trim((string) $decision));

            if (! in_array($estado, ['APROBADA', 'RECHAZADA'], true)) {
                return 'SOLICITADO';
            }

            $tieneRechazada = $tieneRechazada || $estado === 'RECHAZADA';
        }

        return $tieneRechazada ? 'OBSERVADA' : 'APROBADO';
    }
}
