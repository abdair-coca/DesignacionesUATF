<?php

namespace App\Support;

use App\Models\Designacion;

class CargaAcademicaService
{
    public function horasAsignadas(int $docenteId, int $gestionId, int $periodoId, ?int $excluirDesignacionId = null): int
    {
        return (int) Designacion::query()
            ->join('materias', 'materias.id', '=', 'designaciones.Id_materia')
            ->where('designaciones.Id_docente', $docenteId)
            ->where('designaciones.Id_gestion', $gestionId)
            ->where('designaciones.Id_periodo', $periodoId)
            ->where('designaciones.estado', '!=', 'rechazada')
            ->when($excluirDesignacionId, fn ($q, $id) => $q->where('designaciones.id', '!=', $id))
            ->sum('materias.horas');
    }

    public function hayChoque(int $grupoId, int $gestionId, int $periodoId, ?int $excluirDesignacionId = null): bool
    {
        return Designacion::query()
            ->where('Id_grupo', $grupoId)
            ->where('Id_gestion', $gestionId)
            ->where('Id_periodo', $periodoId)
            ->where('estado', '!=', 'rechazada')
            ->when($excluirDesignacionId, fn ($q, $id) => $q->where('id', '!=', $id))
            ->exists();
    }
}
