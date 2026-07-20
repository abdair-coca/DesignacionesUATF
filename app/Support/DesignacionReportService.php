<?php

namespace App\Support;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Materia;
use App\Models\Periodo;

class DesignacionReportService
{
    public function __construct(private CargaAcademicaService $cargaAcademica) {}

    /**
     * Reporte por carrera (KPIs, lista de materias con estado de asignación).
     */
    public function reporteCarrera(int $carreraId, int $gestionId, int $periodoId): array
    {
        $materias = Materia::where('carrera_id', $carreraId)
            ->with(['grupos' => function ($query) use ($gestionId, $periodoId) {
                $query->where('estado', 'habilitado')
                    ->with(['designaciones' => function ($q) use ($gestionId, $periodoId) {
                        $q->activas()->forGestionPeriodo($gestionId, $periodoId)->with('docente');
                    }]);
            }])
            ->orderBy('sigla')
            ->get();

        $totalGruposHabilitados = 0;
        $totalGruposDesignados = 0;

        foreach ($materias as $materia) {
            foreach ($materia->grupos as $grupo) {
                $totalGruposHabilitados++;
                if ($grupo->designaciones->isNotEmpty()) {
                    $totalGruposDesignados++;
                }
            }
        }

        $cobertura = $totalGruposHabilitados > 0
            ? round(($totalGruposDesignados / $totalGruposHabilitados) * 100, 1)
            : 0;

        return [
            'materias' => $materias,
            'kpis' => [
                'totalGruposHabilitados' => $totalGruposHabilitados,
                'totalGruposDesignados' => $totalGruposDesignados,
                'gruposPendientes' => $totalGruposHabilitados - $totalGruposDesignados,
                'porcentajeCobertura' => $cobertura,
            ],
        ];
    }

    public function resumenPorCarrera(int $gestionId, int $periodoId)
    {
        return Carrera::all()->map(function ($carrera) use ($gestionId, $periodoId) {
            $rep = $this->reporteCarrera($carrera->id, $gestionId, $periodoId);
            $grupos = $rep['kpis']['totalGruposHabilitados'];
            $activas = $rep['kpis']['totalGruposDesignados'];
            $pendientes = $rep['kpis']['gruposPendientes'];

            return [
                'id' => $carrera->id,
                'materias' => $carrera->materias()->count(),
                'grupos' => $grupos,
                'activas' => $activas,
                'pendientes' => $pendientes,
                'situacion' => $activas > 0 ? ($pendientes > 0 ? 'pendientes' : 'activas') : 'sin',
            ];
        });
    }

    public function resumenPorMateria(Carrera $carrera, int $gestionId, int $periodoId)
    {
        $rep = $this->reporteCarrera($carrera->id, $gestionId, $periodoId);

        return $rep['materias']->map(function ($materia) {
            $total = $materia->grupos->count();
            $asignados = $materia->grupos->filter(fn ($g) => $g->designaciones->isNotEmpty())->count();

            return [
                'id' => $materia->id,
                'grupos_total' => $total,
                'grupos_asignados' => $asignados,
                'estado' => $asignados === $total ? 'completa' : ($asignados > 0 ? 'por_asignar' : 'sin_asignar'),
            ];
        });
    }

    /**
     * Dashboard general (KPIs globales, resumen por carrera).
     */
    public function dashboardGeneral(int $gestionId, int $periodoId): array
    {
        $gruposSinDesignar = (int) Materia::join('grupos', 'grupos.materia_id', '=', 'materias.id')
            ->where('grupos.estado', 'habilitado')
            ->whereNotExists(function ($query) use ($gestionId, $periodoId) {
                $query->selectRaw(1)
                    ->from('designaciones')
                    ->whereColumn('designaciones.Id_grupo', 'grupos.id')
                    ->where('designaciones.Id_gestion', $gestionId)
                    ->where('designaciones.Id_periodo', $periodoId)
                    ->where('designaciones.estado', '!=', 'rechazada');
            })
            ->count('grupos.id');

        $porEstado = Designacion::forGestionPeriodo($gestionId, $periodoId)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado')
            ->toArray();

        $docentesConHoras = Docente::query()
            ->leftJoin('designaciones', function ($join) use ($gestionId, $periodoId) {
                $join->on('designaciones.Id_docente', '=', 'docentes.id')
                    ->where('designaciones.Id_gestion', $gestionId)
                    ->where('designaciones.Id_periodo', $periodoId)
                    ->where('designaciones.estado', '!=', 'rechazada');
            })
            ->leftJoin('materias', 'materias.id', '=', 'designaciones.Id_materia')
            ->selectRaw('docentes.id, docentes.nombre, coalesce(sum(materias.horas), 0) as horas')
            ->groupBy('docentes.id', 'docentes.nombre')
            ->orderBy('horas')
            ->orderBy('docentes.nombre')
            ->get();

        $docentesFaltaMinimo = $docentesConHoras
            ->filter(fn ($docente) => (int) $docente->horas < CargaAcademicaService::getMinimo())
            ->map(fn ($docente) => ['id' => $docente->id, 'nombre' => $docente->nombre, 'horas' => (int) $docente->horas])
            ->values();

        return [
            'gruposSinDesignar' => $gruposSinDesignar,
            'conteoEstado' => [
                'propuesta' => (int) ($porEstado['propuesta'] ?? 0),
                'aprobada' => (int) ($porEstado['aprobada'] ?? 0),
                'rechazada' => (int) ($porEstado['rechazada'] ?? 0),
            ],
            'docentesBajoLimite' => $docentesFaltaMinimo,
            'docentesFaltaMinimo' => $docentesFaltaMinimo,
            'minimoHoras' => CargaAcademicaService::getMinimo(),
            'limiteHoras' => CargaAcademicaService::getMinimo(),
        ];
    }

    public function datosDashboard(int $gestionId, int $periodoId): array
    {
        $dash = $this->dashboardGeneral($gestionId, $periodoId);

        return [
            'gruposSinDesignar' => Materia::join('grupos', 'grupos.materia_id', '=', 'materias.id')
                ->where('grupos.estado', 'habilitado')
                ->whereNotExists(function ($query) use ($gestionId, $periodoId) {
                    $query->selectRaw(1)
                        ->from('designaciones')
                        ->whereColumn('designaciones.Id_grupo', 'grupos.id')
                        ->where('designaciones.Id_gestion', $gestionId)
                        ->where('designaciones.Id_periodo', $periodoId)
                        ->where('designaciones.estado', '!=', 'rechazada');
                })
                ->select('grupos.*')
                ->get(),
            'conteoEstado' => $dash['conteoEstado'],
            'docentesBajoLimite' => $dash['docentesBajoLimite'],
            'minimoHoras' => CargaAcademicaService::getMinimo(),
        ];
    }

    /**
     * Evolución acumulada de designaciones en el periodo.
     */
    public function evolucionDesignaciones(int $gestionId, int $periodoId): array
    {
        $periodo = Periodo::find($periodoId);
        $periodoNombre = $periodo ? $periodo->nombre : '1';

        $raw = Designacion::activas()
            ->forGestionPeriodo($gestionId, $periodoId)
            ->selectRaw('DATE(created_at) as fecha, count(*) as total')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $acumulado = 0;
        $puntos = [];

        foreach ($raw as $row) {
            $acumulado += (int) $row->total;
            $puntos[] = [
                'fecha' => $row->fecha,
                'acumulado' => $acumulado,
            ];
        }

        return [
            'periodo' => $periodoNombre,
            'puntos' => $puntos,
        ];
    }

    /**
     * Resumen de carga horaria para el form de crear/editar designación.
     */
    public function resumenCarga(array $inputs, ?int $excluirDesignacionId = null): array
    {
        $docenteId = ! empty($inputs['Id_docente']) ? (int) $inputs['Id_docente'] : null;
        $materiaId = ! empty($inputs['Id_materia']) ? (int) $inputs['Id_materia'] : null;
        $grupoId = ! empty($inputs['Id_grupo']) ? (int) $inputs['Id_grupo'] : null;
        $gestionId = ! empty($inputs['Id_gestion']) ? (int) $inputs['Id_gestion'] : null;
        $periodoId = ! empty($inputs['Id_periodo']) ? (int) $inputs['Id_periodo'] : null;

        $horasActuales = null;
        $horasMateria = $materiaId ? (int) (Materia::find($materiaId)?->horas ?? 0) : 0;
        $hayChoque = false;

        if ($docenteId && $gestionId && $periodoId) {
            $horasActuales = $this->cargaAcademica->horasAsignadas($docenteId, $gestionId, $periodoId, $excluirDesignacionId);
        }

        if ($grupoId && $gestionId && $periodoId) {
            $hayChoque = $this->cargaAcademica->hayChoque($grupoId, $gestionId, $periodoId, $excluirDesignacionId);
        }

        $horasProyectadas = $horasActuales === null ? null : $horasActuales + $horasMateria;

        return [
            'horasActuales' => $horasActuales,
            'horasProyectadas' => $horasProyectadas,
            'minimo' => CargaAcademicaService::getMinimo(),
            'limite' => CargaAcademicaService::getMinimo(),
            'cumpleMinimo' => $horasProyectadas !== null && $horasProyectadas >= CargaAcademicaService::getMinimo(),
            'faltaMinimo' => $horasProyectadas !== null && $horasProyectadas < CargaAcademicaService::getMinimo(),
            'excedeLimite' => false,
            'hayChoque' => $hayChoque,
        ];
    }
}
