<?php

namespace App\Http\Controllers;

use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Support\CargaAcademicaService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'gestion_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_id' => ['nullable', 'exists:periodos,id'],
        ]);

        $gestionId = (int) ($filtros['gestion_id'] ?? Gestion::max('id') ?? 0);
        $periodoId = (int) ($filtros['periodo_id'] ?? Periodo::min('id') ?? 0);

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

        return Inertia::render('Dashboard/Index', [
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'filtros' => [
                'gestion_id' => (string) $gestionId,
                'periodo_id' => (string) $periodoId,
            ],
            'gruposSinDesignar' => $gruposSinDesignar,
            'conteoEstado' => [
                'propuesta' => (int) ($porEstado['propuesta'] ?? 0),
                'aprobada' => (int) ($porEstado['aprobada'] ?? 0),
                'rechazada' => (int) ($porEstado['rechazada'] ?? 0),
            ],
            'docentesBajoLimite' => $docentesBajoLimite,
            'limiteHoras' => CargaAcademicaService::LIMITE_HORAS,
        ]);
    }
}
