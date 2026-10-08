<?php

namespace App\UseCases\Designaciones;

use App\Contracts\DesignacionesWriteContract;
use App\Data\Designaciones\DecisionAsignacion;
use App\Domain\Designaciones\ReglasDecisionDesignacion;
use InvalidArgumentException;

final class GuardarDecisionPorFila
{
    public function __construct(
        private DesignacionesWriteContract $escritura,
        private ReglasDecisionDesignacion $reglas,
    ) {}

    public function ejecutar(
        int $idAsignacion,
        int $idDetalle,
        string $estado,
        ?string $observacion,
    ): DecisionAsignacion {
        if ($idAsignacion < 1) {
            throw new InvalidArgumentException('El identificador de asignacion no es valido.');
        }

        $decision = $this->reglas->decisionFila($idDetalle, $estado, $observacion);

        return $this->escritura->guardarDecisionAsignacionDetalle(
            $idAsignacion,
            $decision->idDetalle,
            (string) $decision->estado,
            $decision->observacion,
        );
    }
}
