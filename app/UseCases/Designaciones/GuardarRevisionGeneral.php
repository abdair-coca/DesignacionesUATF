<?php

namespace App\UseCases\Designaciones;

use App\Contracts\DesignacionesWriteContract;
use App\Data\Designaciones\RevisionDesignacion;
use App\Domain\Designaciones\ReglasDecisionDesignacion;
use InvalidArgumentException;

final class GuardarRevisionGeneral
{
    public function __construct(
        private DesignacionesWriteContract $escritura,
        private ReglasDecisionDesignacion $reglas,
    ) {}

    public function ejecutar(int $idAsignacion, string $estado, ?string $observacion): RevisionDesignacion
    {
        if ($idAsignacion < 1) {
            throw new InvalidArgumentException('El identificador de designacion no es valido.');
        }

        $revision = $this->reglas->revisionGeneral($estado, $observacion);

        return $this->escritura->guardarRevisionDesignacion(
            $idAsignacion,
            $revision->estado ?? '',
            $revision->observacion,
        );
    }
}
