<?php

namespace App\Support;

use App\Models\Designacion;

class CargaAcademicaService
{
    public const LIMITE_HORAS = 6;

    public function horasAsignadas(int $docenteId, int $gestionId, int $periodoId, ?int $excluirDesignacionId = null): int
    {
        return (int) Designacion::activas()
            ->forGestionPeriodo($gestionId, $periodoId)
            ->where('Id_docente', $docenteId)
            ->join('materias', 'materias.id', '=', 'designaciones.Id_materia')
            ->when($excluirDesignacionId, fn ($q, $id) => $q->where('designaciones.id', '!=', $id))
            ->sum('materias.horas');
    }

    public function hayChoque(int $grupoId, int $gestionId, int $periodoId, ?int $excluirDesignacionId = null): bool
    {
        return Designacion::activas()
            ->forGestionPeriodo($gestionId, $periodoId)
            ->where('Id_grupo', $grupoId)
            ->when($excluirDesignacionId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists();
    }
}
