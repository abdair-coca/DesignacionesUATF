<?php

namespace App\Support;

use App\Models\Designacion;

class CargaAcademicaService
{
    public const MINIMO_HORAS = 6;

    public static function getMinimo(): int
    {
        return (int) config('designaciones.minimo_horas', self::MINIMO_HORAS);
    }

    /**
     * Mantiene compatibilidad regresiva devolviendo el mínimo de horas.
     */
    public static function getLimite(): int
    {
        return self::getMinimo();
    }

    public function horasAsignadas(int $docenteId, int $gestionId, int $periodoId, ?int $excluirDesignacionId = null): int
    {
        return (int) Designacion::activas()
            ->forGestionPeriodo($gestionId, $periodoId)
            ->where('Id_docente', $docenteId)
            ->join('materias', 'materias.id', '=', 'designaciones.Id_materia')
            ->when($excluirDesignacionId, fn ($q, $id) => $q->where('designaciones.id', '!=', $id))
            ->sum('materias.horas');
    }

    public function cumpleMinimo(int $docenteId, int $gestionId, int $periodoId, int $horasAdicionales = 0, ?int $excluirDesignacionId = null): bool
    {
        $total = $this->horasAsignadas($docenteId, $gestionId, $periodoId, $excluirDesignacionId) + $horasAdicionales;

        return $total >= self::getMinimo();
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
