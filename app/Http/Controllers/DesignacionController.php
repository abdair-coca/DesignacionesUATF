<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreDesignacionRequest;
use App\Http\Requests\UpdateDesignacionRequest;
use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use App\Models\Revision;
use App\Support\CargaAcademicaService;
use App\Support\DesignacionReportService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DesignacionController extends Controller
{
    public function __construct(
        private CargaAcademicaService $cargaAcademica,
        private DesignacionReportService $reportes,
    ) {}

    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'gestion_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_id' => ['nullable', 'exists:periodos,id'],
            'estado' => ['nullable', 'in:activas,pendientes,sin'],
        ]);

        $gestionId = (int) ($filtros['gestion_id'] ?? Gestion::max('id') ?? 0);
        $periodoId = (int) ($filtros['periodo_id'] ?? Periodo::min('id') ?? 0);

        $todas = $this->reportes->resumenPorCarrera($gestionId, $periodoId);

        $resumen = [
            'total' => $todas->count(),
            'activas' => $todas->where('situacion', 'activas')->count(),
            'pendientes' => $todas->where('situacion', 'pendientes')->count(),
            'sin' => $todas->where('situacion', 'sin')->count(),
        ];

        $filtradas = $todas
            ->when($filtros['q'] ?? null, fn ($carreras, $q) => $carreras->filter(
                fn ($fila) => stripos($fila['nombre'], $q) !== false || stripos($fila['sigla'], $q) !== false
            ))
            ->when($filtros['estado'] ?? null, fn ($carreras, $estado) => $carreras->where('situacion', $estado))
            ->values();

        $porPagina = 10;
        $paginaActual = LengthAwarePaginator::resolveCurrentPage();
        $carreras = new LengthAwarePaginator(
            $filtradas->forPage($paginaActual, $porPagina)->values(),
            $filtradas->count(),
            $porPagina,
            $paginaActual,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Designaciones/PorCarrera', [
            'carreras' => $carreras,
            'resumen' => $resumen,
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'filtros' => [
                'q' => $filtros['q'] ?? '',
                'gestion_id' => (string) $gestionId,
                'periodo_id' => (string) $periodoId,
                'estado' => $filtros['estado'] ?? '',
            ],
        ]);
    }

    public function lista(Request $request): Response
    {
        $filtros = $request->validate([
            'carrera_id' => ['nullable', 'exists:carreras,id'],
            'materia_id' => ['nullable', 'exists:materias,id'],
            'gestion_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_id' => ['nullable', 'exists:periodos,id'],
            'estado' => ['nullable', 'in:propuesta,aprobada,rechazada'],
        ]);

        $designaciones = Designacion::with(['docente', 'materia', 'grupo', 'gestion', 'periodo'])
            ->when($filtros['carrera_id'] ?? null, fn ($q, $carreraId) => $q->whereHas('materia', fn ($m) => $m->where('carrera_id', $carreraId)))
            ->when($filtros['materia_id'] ?? null, fn ($q, $materiaId) => $q->where('Id_materia', $materiaId))
            ->when($filtros['gestion_id'] ?? null, fn ($q, $gestionId) => $q->where('Id_gestion', $gestionId))
            ->when($filtros['periodo_id'] ?? null, fn ($q, $periodoId) => $q->where('Id_periodo', $periodoId))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Designaciones/Lista', [
            'designaciones' => $designaciones,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'filtros' => [
                'carrera_id' => $filtros['carrera_id'] ?? '',
                'materia_id' => $filtros['materia_id'] ?? '',
                'gestion_id' => $filtros['gestion_id'] ?? '',
                'periodo_id' => $filtros['periodo_id'] ?? '',
                'estado' => $filtros['estado'] ?? '',
            ],
        ]);
    }

    public function carrera(Request $request, Carrera $carrera): Response
    {
        $filtros = $request->validate([
            'gestion_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_id' => ['nullable', 'exists:periodos,id'],
        ]);

        $gestionId = (int) ($filtros['gestion_id'] ?? Gestion::max('id') ?? 0);
        $periodoId = (int) ($filtros['periodo_id'] ?? Periodo::min('id') ?? 0);

        $materias = $this->reportes->resumenPorMateria($carrera, $gestionId, $periodoId);

        $designaciones = Designacion::with(['docente', 'materia', 'grupo'])
            ->where('Id_gestion', $gestionId)
            ->where('Id_periodo', $periodoId)
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $carrera->id))
            ->orderBy('Id_materia')
            ->get();

        $grupos = Grupo::with('materia')
            ->where('estado', 'habilitado')
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $carrera->id))
            ->orderBy('materia_id')
            ->orderBy('codigo')
            ->get();

        $roster = $this->construirRoster($grupos, $designaciones, $gestionId, $periodoId);
        $historialPorGrupo = $this->historialPorGrupo($grupos->pluck('id'));

        // Ultima revision de esta carrera+gestion+periodo
        $revision = Revision::with(['solicitante:id,name', 'revisor:id,name'])
            ->where('carrera_id', $carrera->id)
            ->where('Id_gestion', $gestionId)
            ->where('Id_periodo', $periodoId)
            ->latest('id')
            ->first();

        return Inertia::render('Designaciones/Carrera', [
            'carrera' => $carrera,
            'materias' => $materias,
            'designaciones' => $designaciones,
            'roster' => $roster,
            'historialPorGrupo' => $historialPorGrupo,
            'docentes' => Docente::with('carreraOrigen:id,sigla')
                ->orderBy('nombre')
                ->get(['id', 'nombre', 'carrera_origen_id'])
                ->map(fn (Docente $d) => [
                    'id' => $d->id,
                    'nombre' => $d->nombre,
                    'carreraSigla' => $d->carreraOrigen?->sigla,
                ]),
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'limiteHoras' => CargaAcademicaService::getLimite(),
            'revision' => $revision ? [
                'id' => $revision->id,
                'estado' => $revision->estado,
                'solicitante' => $revision->solicitante->name,
                'solicitado_en' => $revision->solicitado_en?->format('d/m/Y H:i'),
                'revisor' => $revision->revisor?->name,
                'revisado_en' => $revision->revisado_en?->format('d/m/Y H:i'),
            ] : null,
            'is_admin' => $request->user()->is_admin,
            'filtros' => [
                'gestion_id' => (string) $gestionId,
                'periodo_id' => (string) $periodoId,
            ],
        ]);
    }

    public function guardarRoster(Request $request, Carrera $carrera): RedirectResponse
    {
        $data = $request->validate([
            'Id_gestion' => ['required', 'exists:gestiones,id'],
            'Id_periodo' => ['required', 'exists:periodos,id'],
            'cambios' => ['required', 'array', 'min:1'],
            'cambios.*.Id_grupo' => ['required', 'exists:grupos,id'],
            'cambios.*.Id_materia' => ['required', 'exists:materias,id'],
            'cambios.*.Id_docente' => ['nullable', 'exists:docentes,id'],
        ]);

        $saltados = 0;

        DB::transaction(function () use ($data, $request, &$saltados) {
            foreach ($data['cambios'] as $cambio) {
                if ($cambio['Id_docente'] === null) {
                    Designacion::where('Id_grupo', $cambio['Id_grupo'])
                        ->where('Id_gestion', $data['Id_gestion'])
                        ->where('Id_periodo', $data['Id_periodo'])
                        ->delete();

                    continue;
                }

                $grupo = Grupo::with('materia')->find($cambio['Id_grupo']);
                $horasGrupo = $grupo->materia->horas;

                // Buscar designacion existente (incluye rechazadas)
                $existente = Designacion::forGestionPeriodo($data['Id_gestion'], $data['Id_periodo'])
                    ->where('Id_grupo', $cambio['Id_grupo'])
                    ->first();

                if ($existente) {
                    $nuevoEstado = $existente->estado;
                    if ($existente->estado === 'rechazada') {
                        $nuevoEstado = 'propuesta';
                    } elseif ($existente->estado === 'aprobada' && (int) $existente->Id_docente !== (int) $cambio['Id_docente']) {
                        $nuevoEstado = 'propuesta';
                    }

                    $existente->update([
                        'Id_docente' => $cambio['Id_docente'],
                        'estado' => $nuevoEstado,
                    ]);

                    continue;
                }

                // Límite de horas: verificar antes de crear
                Designacion::create([
                    'Id_docente' => $cambio['Id_docente'],
                    'Id_materia' => $cambio['Id_materia'],
                    'Id_grupo' => $cambio['Id_grupo'],
                    'Id_gestion' => $data['Id_gestion'],
                    'Id_periodo' => $data['Id_periodo'],
                    'estado' => 'propuesta',
                    'creado_por' => $request->user()->id,
                ]);
            }
        });

        return redirect()->back()->with('status', 'Cambios guardados.');
    }

    /**
     * Arma una fila por grupo habilitado (tenga o no designación en esta gestión/periodo),
     * para la tabla de asignación rápida de Designaciones/Carrera.
     */
    private function construirRoster(Collection $grupos, Collection $designaciones, int $gestionId, int $periodoId): Collection
    {
        return $grupos->map(function (Grupo $grupo) use ($designaciones, $gestionId, $periodoId) {
            $actual = $designaciones->firstWhere('Id_grupo', $grupo->id);

            $aviso = null;
            if ($actual && $actual->estado !== 'rechazada') {
                $horasProyectadas = $this->cargaAcademica->horasAsignadas($actual->Id_docente, $gestionId, $periodoId, $actual->id)
                    + $grupo->materia->horas;

                $aviso = [
                    'faltaMinimo' => $horasProyectadas < CargaAcademicaService::getMinimo(),
                    'cumpleMinimo' => $horasProyectadas >= CargaAcademicaService::getMinimo(),
                    'horasProyectadas' => $horasProyectadas,
                    'hayChoque' => $this->cargaAcademica->hayChoque($grupo->id, $gestionId, $periodoId, $actual->id),
                ];
            }

            return [
                'id' => $grupo->id,
                'codigo' => $grupo->codigo,
                'horas' => $grupo->materia->horas,
                'materia' => [
                    'id' => $grupo->materia->id,
                    'sigla' => $grupo->materia->sigla,
                    'nombre' => $grupo->materia->nombre,
                ],
                'designacion' => $actual ? [
                    'id' => $actual->id,
                    'estado' => $actual->estado,
                    'motivo_rechazo' => $actual->motivo_rechazo,
                    'docente' => ['id' => $actual->docente->id, 'nombre' => $actual->docente->nombre],
                ] : null,
                'aviso' => $aviso,
            ];
        })->values();
    }

    /**
     * Docentes que dictaron cada grupo en otras gestiones/periodos, más reciente primero,
     * para el selector "elegir un docente del pasado" del roster.
     */
    private function historialPorGrupo(Collection $grupoIds): Collection
    {
        return Designacion::with(['docente', 'gestion', 'periodo'])
            ->whereIn('Id_grupo', $grupoIds)
            ->orderByDesc('Id_gestion')
            ->orderByDesc('Id_periodo')
            ->get()
            ->groupBy('Id_grupo')
            ->map(fn (Collection $items) => $items
                ->take(8)
                ->map(fn (Designacion $d) => [
                    'docente' => ['id' => $d->docente->id, 'nombre' => $d->docente->nombre],
                    'gestion' => $d->gestion->nombre,
                    'periodo' => $d->periodo->nombre,
                    'estado' => $d->estado,
                ])
                ->values()
            );
    }

    public function create(Request $request): Response
    {
        $gestionId = (int) (Gestion::max('id') ?? 0);
        $periodoId = (int) (Periodo::min('id') ?? 0);

        return Inertia::render('Designaciones/Create', array_merge($this->catalogos($gestionId, $periodoId), [
            'gestionActual' => $gestionId,
            'periodoActual' => $periodoId,
            'prefill' => $request->only(['Id_docente', 'Id_materia', 'Id_grupo', 'Id_gestion', 'Id_periodo']),
            'resumenCarga' => $this->reportes->resumenCarga($request->only([
                'Id_docente', 'Id_materia', 'Id_grupo', 'Id_gestion', 'Id_periodo',
            ])),
        ]));
    }

    public function store(StoreDesignacionRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $data['creado_por'] = $request->user()->id;

        Designacion::create($data);

        return redirect()->route('designaciones.index')
            ->with('status', 'Designación creada correctamente.');
    }

    public function edit(Request $request, Designacion $designacion): Response
    {
        $gestionId = $designacion->Id_gestion;
        $periodoId = $designacion->Id_periodo;

        return Inertia::render('Designaciones/Edit', array_merge(
            $this->catalogos($gestionId, $periodoId),
            [
                'designacion' => $designacion,
                'resumenCarga' => $this->reportes->resumenCarga($request->only([
                    'Id_docente', 'Id_materia', 'Id_grupo', 'Id_gestion', 'Id_periodo',
                ]), $designacion->id),
            ]
        ));
    }

    public function update(UpdateDesignacionRequest $request, Designacion $designacion): RedirectResponse
    {
        $designacion->update($request->validated());

        return redirect()->route('designaciones.index')
            ->with('status', 'Designación actualizada correctamente.');
    }

    public function destroy(Designacion $designacion): RedirectResponse
    {
        $designacion->delete();

        return redirect()->back()
            ->with('status', 'Designación eliminada.');
    }

    public function historial(Designacion $designacion): Response
    {
        $designacion->load(['docente', 'materia', 'grupo', 'gestion', 'periodo']);
        $historial = $designacion->historial()->orderByDesc('fecha')->get();

        return Inertia::render('Designaciones/Historial', compact('designacion', 'historial'));
    }

    private function catalogos(int $gestionId = 0, int $periodoId = 0): array
    {
        // Materias que tienen al menos un grupo habilitado, con carrera para filtrado client-side
        $materiasConGrupos = Materia::whereIn('id', function ($q) {
            $q->select('materia_id')->from('grupos')->where('estado', 'habilitado');
        })->orderBy('sigla')->get();

        // Docentes con horas disponibles, enriquecidos con historial de materias
        $docentes = Docente::orderBy('nombre')->get();

        if ($gestionId && $periodoId) {
            $docentes = $docentes->filter(function (Docente $docente) use ($gestionId, $periodoId) {
                $horas = $this->cargaAcademica->horasAsignadas($docente->id, $gestionId, $periodoId);
                return $horas < CargaAcademicaService::getLimite();
            })->values();
        }

        // Enriquecer docentes: materias que dictó antes (para ordenamiento inteligente)
        $historialRows = Designacion::select('Id_docente', 'Id_materia')
            ->distinct()
            ->get()
            ->groupBy('Id_docente')
            ->map(fn ($rows) => $rows->pluck('Id_materia')->values()->all());

        $docentes = $docentes->map(function (Docente $docente) use ($historialRows) {
            $docente->historial_materias = $historialRows[$docente->id] ?? [];
            return $docente;
        });

        return [
            'carreras' => Carrera::orderBy('nombre')->get(),
            'docentes' => $docentes,
            'materias' => $materiasConGrupos,
            'grupos' => Grupo::with('materia')->where('estado', 'habilitado')->get(),
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
        ];
    }
}
