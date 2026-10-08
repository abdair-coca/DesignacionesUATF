<?php

namespace App\UseCases\Designaciones;

use App\Contracts\DesignacionesReadContract;
use App\Contracts\DesignacionesWriteContract;
use App\Data\Designaciones\DetalleAsignacion;
use App\Domain\Designaciones\CargaHoraria;
use App\Domain\Designaciones\SeleccionGrupo;
use Illuminate\Support\Collection;
use InvalidArgumentException;

final class GuardarAsignacion
{
    public function __construct(
        private DesignacionesReadContract $lectura,
        private DesignacionesWriteContract $escritura,
    ) {}

    /** @return Collection<int, DetalleAsignacion> */
    public function ejecutar(
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
        if ($id < 1 || $idDetalle < 0 || $idDocente < 1 || $idMateria < 1 || $idGrupo < 1
            || $hrsTeoria < 0 || $hrsPractica < 0 || $hrsLaboratorio < 0) {
            throw new InvalidArgumentException('Los datos de la asignacion no son validos.');
        }

        $horas = CargaHoraria::validarEntrada($hrsTeoria, $hrsPractica, $hrsLaboratorio);
        $programa = strtoupper(trim($programa));

        if (! preg_match('/^[A-Z0-9_-]{2,20}$/', $programa)) {
            throw new InvalidArgumentException('El codigo de programa no es valido.');
        }

        $designacion = $this->lectura->listar($programa, '0', '0')->first(
            fn ($fila): bool => $fila->id === $id,
        );

        if ($designacion === null) {
            throw new InvalidArgumentException('La designacion indicada no pertenece a su carrera.');
        }

        $filasActuales = $this->lectura->detallar($id);
        $filaActual = $idDetalle > 0
            ? $filasActuales->first(fn (DetalleAsignacion $fila): bool => $fila->id === $idDetalle)
            : null;

        if ($idDetalle > 0 && $filaActual === null) {
            throw new InvalidArgumentException('El detalle indicado no pertenece a la designacion.');
        }

        $oferta = $this->lectura->ofertaMaterias($programa, $designacion->gestion, $designacion->periodo)
            ->first(fn ($materia): bool => $materia->id === $idMateria);

        if ($oferta === null) {
            throw new InvalidArgumentException('La materia o grupo no pertenece a la oferta vigente.');
        }

        $gruposMateria = $filasActuales
            ->filter(fn (DetalleAsignacion $fila): bool => $fila->materiaId === $idMateria)
            ->map(fn (DetalleAsignacion $fila): ?int => $fila->grupoId)
            ->values()
            ->all();
        $seleccionGrupo = SeleccionGrupo::calcular($oferta, $gruposMateria, $filaActual?->grupoId);

        if ($idDetalle === 0) {
            $seleccionGrupo->validarNuevo($idGrupo);
        } else {
            $seleccionGrupo->validarEdicion($idGrupo);
        }

        $duplicada = $filasActuales->contains(fn (DetalleAsignacion $fila): bool => $fila->id !== $idDetalle
            && $fila->materiaId === $idMateria
            && $fila->grupoId === $idGrupo);

        if ($duplicada) {
            throw new InvalidArgumentException('La materia y grupo ya tienen un docente asignado.');
        }

        $horas->validarContraOferta($oferta->horasTeoricas, $oferta->horasPracticas, $oferta->horasLaboratorio);

        return $this->escritura->guardarDetalle(
            $id,
            $idDetalle,
            $idDocente,
            $idMateria,
            $idGrupo,
            $horas->teoricas,
            $horas->practicas,
            $horas->laboratorio,
            (int) $designacion->gestion,
            $idGrupo === $seleccionGrupo->grupoSiguiente,
        );
    }
}
