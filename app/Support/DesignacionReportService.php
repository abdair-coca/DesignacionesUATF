<?php

namespace App\Support;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\MallaCurricular;
use App\Models\Materia;
use App\Models\Periodo;
use Illuminate\Support\Collection;

class DesignacionReportService
{
    public function __construct(private CargaAcademicaService $cargaAcademica) {}

    /**
     * Reporte por carrera (KPIs, lista de materias con estado de asignación).
     */
    public function reporteCarrera(int $carreraId, int $gestionId, int $periodoId): array
    {
        $materias = MallaCurricular::where('carrera_id', $carreraId)
            ->with(['materia', 'grupos' => function ($query) use ($gestionId, $periodoId) {
                $query->where('estado', 'habilitado')
                    ->with(['designaciones' => function ($q) use ($gestionId, $periodoId) {
                        $q->activas()->forGestionPeriodo($gestionId, $periodoId)->with('docente');
                    }]);
            }])
            ->get()
            ->sortBy('materia.sigla')
            ->map(function (MallaCurricular $malla) {
                $materia = $malla->materia;
                $materia->setRelation('grupos', $malla->grupos);

                return $materia;
            })
            ->values();

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

    public function resumenPorCarrera(int $gestionId, int $periodoId): Collection
    {
        $rows = Carrera::selectRaw('
                carreras.id, carreras.nombre, carreras.sigla,
                COUNT(DISTINCT materias.id) as total_materias,
                COUNT(DISTINCT grupos.id) as total_grupos,
                COUNT(DISTINCT CASE WHEN designaciones.id IS NOT NULL AND designaciones.estado != ? THEN grupos.id END) as grupos_designados
            ', ['rechazada'])
            ->leftJoin('malla_curricular', 'malla_curricular.carrera_id', '=', 'carreras.id')
            ->leftJoin('materias', 'materias.id', '=', 'malla_curricular.materia_id')
            ->leftJoin('grupos', function ($j) {
                $j->on('grupos.malla_curricular_id', '=', 'malla_curricular.id')
                    ->where('grupos.estado', 'habilitado');
            })
            ->leftJoin('designaciones', function ($j) use ($gestionId, $periodoId) {
                $j->on('designaciones.Id_grupo', '=', 'grupos.id')
                    ->where('designaciones.Id_gestion', $gestionId)
                    ->where('designaciones.Id_periodo', $periodoId);
            })
            ->groupBy('carreras.id', 'carreras.nombre', 'carreras.sigla')
            ->orderBy('carreras.sigla')
            ->get();

        return $rows->map(function ($carrera) {
            $grupos = (int) $carrera->total_grupos;
            $activas = (int) $carrera->grupos_designados;
            $pendientes = $grupos - $activas;

            return [
                'id' => $carrera->id,
                'nombre' => $carrera->nombre,
                'sigla' => $carrera->sigla,
                'materias' => (int) $carrera->total_materias,
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
        $gruposSinDesignar = (int) Grupo::join('malla_curricular', 'malla_curricular.id', '=', 'grupos.malla_curricular_id')
            ->join('materias', 'materias.id', '=', 'malla_curricular.materia_id')
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
            'gruposSinDesignar' => Grupo::with(['mallaCurricular.materia', 'mallaCurricular.carrera'])
                ->where('estado', 'habilitado')
                ->whereNotExists(function ($query) use ($gestionId, $periodoId) {
                    $query->selectRaw(1)
                        ->from('designaciones')
                        ->whereColumn('designaciones.Id_grupo', 'grupos.id')
                        ->where('designaciones.Id_gestion', $gestionId)
                        ->where('designaciones.Id_periodo', $periodoId)
                        ->where('designaciones.estado', '!=', 'rechazada');
                })
                ->get(),
            'conteoEstado' => $dash['conteoEstado'],
            'docentesBajoLimite' => $dash['docentesBajoLimite'],
            'minimoHoras' => CargaAcademicaService::getMinimo(),
            'limiteHoras' => CargaAcademicaService::getMinimo(),
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

        return collect($puntos)->map(fn ($p) => [
            'label' => $p['fecha'],
            'valor' => $p['acumulado'],
        ])->values()->toArray();
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
