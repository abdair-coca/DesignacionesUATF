<?php

namespace App\Support;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Support\Collection;

class DesignacionReportService
{
    public function __construct(private CargaAcademicaService $cargaAcademica) {}

    /**
     * Resumen por carrera: materias, grupos habilitados, asignados, situación.
     */
    public function resumenPorCarrera(int $gestionId, int $periodoId): Collection
    {
        $materiasPorCarrera = Materia::selectRaw('carrera_id, count(*) as total')
            ->groupBy('carrera_id')
            ->pluck('total', 'carrera_id');

        $gruposPorCarrera = Grupo::where('grupos.estado', 'habilitado')
            ->join('materias', 'materias.id', '=', 'grupos.materia_id')
            ->selectRaw('materias.carrera_id, count(*) as total')
            ->groupBy('materias.carrera_id')
            ->pluck('total', 'carrera_id');

        $asignadosPorCarrera = Designacion::where('Id_gestion', $gestionId)
            ->where('Id_periodo', $periodoId)
            ->where('designaciones.estado', '!=', 'rechazada')
            ->join('materias', 'materias.id', '=', 'designaciones.Id_materia')
            ->join('grupos', function ($join) {
                $join->on('grupos.id', '=', 'designaciones.Id_grupo')
                    ->where('grupos.estado', 'habilitado');
            })
            ->selectRaw('materias.carrera_id, count(distinct designaciones."Id_grupo") as total')
            ->groupBy('materias.carrera_id')
            ->pluck('total', 'carrera_id');

        return Carrera::orderBy('nombre')->get()->map(function (Carrera $carrera) use ($materiasPorCarrera, $gruposPorCarrera, $asignadosPorCarrera) {
            $grupos = (int) ($gruposPorCarrera[$carrera->id] ?? 0);
            $activas = min((int) ($asignadosPorCarrera[$carrera->id] ?? 0), $grupos);
            $pendientes = $grupos - $activas;
            $situacion = $activas === 0 ? 'sin' : ($pendientes > 0 ? 'pendientes' : 'activas');

            return [
                'id' => $carrera->id,
                'sigla' => $carrera->sigla,
                'nombre' => $carrera->nombre,
                'materias' => (int) ($materiasPorCarrera[$carrera->id] ?? 0),
                'grupos' => $grupos,
                'activas' => $activas,
                'pendientes' => $pendientes,
                'situacion' => $situacion,
            ];
        });
    }

    /**
     * Resumen por materia dentro de una carrera.
     */
    public function resumenPorMateria(Carrera $carrera, int $gestionId, int $periodoId): Collection
    {
        $gruposPorMateria = Grupo::where('estado', 'habilitado')
            ->whereIn('materia_id', $carrera->materias()->select('id'))
            ->selectRaw('materia_id, count(*) as total')
            ->groupBy('materia_id')
            ->pluck('total', 'materia_id');

        $asignadosPorMateria = Designacion::where('Id_gestion', $gestionId)
            ->where('Id_periodo', $periodoId)
            ->where('designaciones.estado', '!=', 'rechazada')
            ->join('grupos', function ($join) {
                $join->on('grupos.id', '=', 'designaciones.Id_grupo')
                    ->where('grupos.estado', 'habilitado');
            })
            ->join('materias', 'materias.id', '=', 'designaciones.Id_materia')
            ->where('materias.carrera_id', $carrera->id)
            ->selectRaw('designaciones."Id_materia" as materia_id, count(distinct designaciones."Id_grupo") as total')
            ->groupBy('designaciones.Id_materia')
            ->pluck('total', 'materia_id');

        return $carrera->materias()->orderBy('sigla')->get()
            ->map(function (Materia $materia) use ($gruposPorMateria, $asignadosPorMateria) {
                $total = (int) ($gruposPorMateria[$materia->id] ?? 0);
                $asignados = min((int) ($asignadosPorMateria[$materia->id] ?? 0), $total);
                $estado = $total === 0 ? 'sin_grupos' : ($asignados >= $total ? 'asignada' : 'por_asignar');

                return [
                    'id' => $materia->id,
                    'sigla' => $materia->sigla,
                    'nombre' => $materia->nombre,
                    'grupos_total' => $total,
                    'grupos_asignados' => $asignados,
                    'estado' => $estado,
                ];
            })
            ->values();
    }

    /**
     * Datos del dashboard: grupos sin designar, conteo por estado, docentes bajo límite.
     */
    public function datosDashboard(int $gestionId, int $periodoId): array
    {
        $gruposSinDesignar = Grupo::with('materia.carrera')
            ->where('estado', 'habilitado')
            ->whereDoesntHave('designaciones', function ($q) use ($gestionId, $periodoId) {
                $q->where('Id_gestion', $gestionId)
                    ->where('Id_periodo', $periodoId)
                    ->where('estado', '!=', 'rechazada');
            })
            ->orderBy('materia_id')
            ->get()
            ->map(fn (Grupo $grupo) => [
                'id' => $grupo->id,
                'codigo' => $grupo->codigo,
                'materia' => [
                    'id' => $grupo->materia->id,
                    'sigla' => $grupo->materia->sigla,
                    'nombre' => $grupo->materia->nombre,
                ],
                'carrera' => ['sigla' => $grupo->materia->carrera->sigla],
            ])
            ->values();

        $porEstado = Designacion::where('Id_gestion', $gestionId)
            ->where('Id_periodo', $periodoId)
            ->selectRaw('estado, count(*) as total')
            ->groupBy('estado')
            ->pluck('total', 'estado');

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

        $docentesBajoLimite = $docentesConHoras
            ->filter(fn ($docente) => (int) $docente->horas < CargaAcademicaService::LIMITE_HORAS)
            ->map(fn ($docente) => ['id' => $docente->id, 'nombre' => $docente->nombre, 'horas' => (int) $docente->horas])
            ->values();

        return [
            'gruposSinDesignar' => $gruposSinDesignar,
            'conteoEstado' => [
                'propuesta' => (int) ($porEstado['propuesta'] ?? 0),
                'aprobada' => (int) ($porEstado['aprobada'] ?? 0),
                'rechazada' => (int) ($porEstado['rechazada'] ?? 0),
            ],
            'docentesBajoLimite' => $docentesBajoLimite,
            'limiteHoras' => CargaAcademicaService::LIMITE_HORAS,
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
            'limite' => CargaAcademicaService::LIMITE_HORAS,
            'excedeLimite' => $horasProyectadas !== null && $horasProyectadas > CargaAcademicaService::LIMITE_HORAS,
            'hayChoque' => $hayChoque,
        ];
    }
}
