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
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class DesignacionController extends Controller
{
    public function __construct(
        private CargaAcademicaService $cargaAcademica,
        private DesignacionReportService $reportes,
    ) {}

    public function index(Request $request): RedirectResponse
    {
        $user = $request->user();
        if ($user?->is_admin) {
            return redirect()->route('revisiones.pendientes');
        }

        return redirect()->route('designaciones.lista');
    }

    public function lista(Request $request): View|RedirectResponse
    {
        $user = $request->user();
        if ($user?->is_admin) {
            return redirect()->route('revisiones.pendientes');
        }

        $carreraId = $user->carrera_id ?? Carrera::first()?->id;
        $carreraActual = $carreraId ? Carrera::find($carreraId) : Carrera::first();
        if (! $carreraActual) {
            abort(404, 'No hay carreras registradas en el sistema.');
        }

        $gestionParam = $request->query('gestion_id');
        if ($gestionParam) {
            $gestionActual = Gestion::find($gestionParam) ?? Gestion::where('nombre', date('Y'))->first() ?? Gestion::latest('id')->first();
        } else {
            $gestionActual = Gestion::where('nombre', date('Y'))->first() ?? Gestion::latest('id')->first();
        }

        $revisiones = Revision::with(['carrera', 'gestion', 'periodo'])
            ->where('carrera_id', $carreraActual->id)
            ->when($gestionActual, function ($q) use ($gestionActual) {
                $q->where('Id_gestion', $gestionActual->id);
            })
            ->orderBy('created_at', 'asc')
            ->get();

        $propuestasData = $revisiones->map(function ($r) {
            $estadoStr = match ($r->estado) {
                'pendiente' => 'enviado',
                'revisado' => 'oficial',
                'observado' => 'con_observaciones',
                default => 'propuesta',
            };

            return [
                'id' => $r->id,
                'descripcion' => $r->descripcion ?: ("Propuesta de Designación Docente — " . $r->carrera->nombre),
                'gestion' => $r->gestion?->nombre ?? date('Y'),
                'gestion_id' => $r->Id_gestion,
                'periodo' => $r->periodo?->nombre ?? '1',
                'periodo_id' => $r->Id_periodo,
                'estado' => $estadoStr,
                'observacion' => $r->observaciones ?: '',
                'created_at' => $r->created_at?->timestamp ?? 0,
            ];
        })->toArray();

        return view('designaciones.lista', [
            'propuestasData' => $propuestasData,
            'carreraActual' => $carreraActual,
            'gestionActual' => $gestionActual,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
        ]);
    }

    public function carrera(Request $request, Carrera $carrera): View|RedirectResponse
    {
        $user = $request->user();
        if ($user && $user->is_admin) {
            return redirect()->route('revisiones.pendientes');
        }

        if ($user && ! $user->is_admin && $user->carrera_id && (int) $user->carrera_id !== (int) $carrera->id) {
            return redirect()->route('designaciones.carrera', $user->carrera_id);
        }

        $filtros = $request->validate([
            'gestion_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_id' => ['nullable', 'exists:periodos,id'],
        ]);

        $gestionId = (int) ($filtros['gestion_id'] ?? Gestion::max('id') ?? 0);
        $periodoId = (int) ($filtros['periodo_id'] ?? Periodo::where('nombre', '2')->value('id') ?? 0);

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

        // Obtener IDs de docentes que han dictado materias en esta carrera alguna vez
        $docentesHistorialIds = DB::table('designaciones')
            ->join('materias', 'designaciones.Id_materia', '=', 'materias.id')
            ->where('materias.carrera_id', $carrera->id)
            ->whereNotNull('designaciones.Id_docente')
            ->pluck('designaciones.Id_docente')
            ->unique()
            ->toArray();

        // Obtener horas asignadas en OTRAS carreras para cada docente en la misma gestion y periodo
        $horasOtrasCarrerasPorDocente = DB::table('designaciones')
            ->join('materias', 'designaciones.Id_materia', '=', 'materias.id')
            ->where('designaciones.Id_gestion', $gestionId)
            ->where('designaciones.Id_periodo', $periodoId)
            ->where('materias.carrera_id', '!=', $carrera->id)
            ->whereNotNull('designaciones.Id_docente')
            ->groupBy('designaciones.Id_docente')
            ->select('designaciones.Id_docente', DB::raw('SUM(materias.horas) as total_horas'))
            ->pluck('total_horas', 'designaciones.Id_docente')
            ->toArray();

        $docentesOrdenados = Docente::with('carreraOrigen:id,sigla')
            ->get(['id', 'nombre', 'carrera_origen_id'])
            ->sortBy(function (Docente $d) use ($carrera, $docentesHistorialIds) {
                $prioridad = 3; // Resto de docentes
                if ((int) $d->carrera_origen_id === (int) $carrera->id) {
                    $prioridad = 1; // Prioridad 1: Docentes de la carrera
                } elseif (in_array($d->id, $docentesHistorialIds)) {
                    $prioridad = 2; // Prioridad 2: Docentes que dictaron al menos 1 materia en la carrera
                }

                return sprintf('%d_%s', $prioridad, strtolower($d->nombre));
            })
            ->values()
            ->map(function (Docente $d) use ($carrera, $docentesHistorialIds, $horasOtrasCarrerasPorDocente) {
                $prioridad = 3;
                if ((int) $d->carrera_origen_id === (int) $carrera->id) {
                    $prioridad = 1;
                } elseif (in_array($d->id, $docentesHistorialIds)) {
                    $prioridad = 2;
                }

                return [
                    'id' => $d->id,
                    'nombre' => $d->nombre,
                    'carreraSigla' => $d->carreraOrigen?->sigla,
                    'prioridad' => $prioridad,
                    'horasOtrasCarreras' => (int) ($horasOtrasCarrerasPorDocente[$d->id] ?? 0),
                ];
            });

        return view('designaciones.carrera', [
            'carrera' => $carrera,
            'materias' => $materias,
            'designaciones' => $designaciones,
            'roster' => $roster,
            'historialPorGrupo' => $historialPorGrupo,
            'docentes' => $docentesOrdenados,
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'limiteHoras' => CargaAcademicaService::getMinimo(),
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

    public function guardarRoster(Request $request, Carrera $carrera): RedirectResponse|JsonResponse
    {
        $user = $request->user();
        if ($user && ! $user->is_admin && $user->carrera_id && (int) $user->carrera_id !== (int) $carrera->id) {
            return response()->json(['error' => 'No tienes permisos para modificar designaciones en esta carrera.'], 403);
        }

        $data = $request->validate([
            'Id_gestion' => ['required', 'exists:gestiones,id'],
            'Id_periodo' => ['required', 'exists:periodos,id'],
            'cambios' => ['required', 'array', 'min:1'],
            'cambios.*.Id_grupo' => ['required', 'exists:grupos,id'],
            'cambios.*.Id_materia' => ['required', 'exists:materias,id'],
            'cambios.*.Id_docente' => ['nullable', 'exists:docentes,id'],
        ]);

        // Verificar inmutabilidad: no se pueden modificar propuestas pendientes u oficiales
        $revisionExistente = Revision::where('carrera_id', $carrera->id)
            ->where('Id_gestion', $data['Id_gestion'])
            ->where('Id_periodo', $data['Id_periodo'])
            ->latest('id')
            ->first();

        if ($revisionExistente && in_array($revisionExistente->estado, ['pendiente', 'revisado'])) {
            $msg = $revisionExistente->estado === 'revisado'
                ? 'Esta propuesta ya fue aprobada y oficializada por el Vicerrectorado. No se permite realizar modificaciones.'
                : 'Esta propuesta se encuentra pendiente de revisión en el Vicerrectorado. Debes retirar el envío si deseas modificarla.';

            return response()->json(['error' => $msg], 422);
        }

        // Bloqueo estricto: Verificar que ningún docente exceda las 32 horas semanales
        $docentesCambios = collect($data['cambios'])->whereNotNull('Id_docente')->pluck('Id_docente')->unique();
        foreach ($docentesCambios as $docenteId) {
            $docente = \App\Models\Docente::find($docenteId);
            if (! $docente) continue;

            $horasOtrasCarreras = (int) Designacion::forGestionPeriodo($data['Id_gestion'], $data['Id_periodo'])
                ->where('Id_docente', $docenteId)
                ->whereHas('materia', fn ($q) => $q->where('carrera_id', '!=', $carrera->id))
                ->join('materias', 'designaciones.Id_materia', '=', 'materias.id')
                ->sum('materias.horas');

            $gruposDelDocenteEnCambios = collect($data['cambios'])->where('Id_docente', $docenteId)->pluck('Id_grupo');
            $horasNuevasEnCarrera = (int) \App\Models\Grupo::whereIn('grupos.id', $gruposDelDocenteEnCambios)
                ->join('materias', 'grupos.materia_id', '=', 'materias.id')
                ->sum('materias.horas');

            $totalProuesto = $horasOtrasCarreras + $horasNuevasEnCarrera;
            if ($totalProuesto > \App\Support\CargaAcademicaService::MAXIMO_HORAS) {
                return response()->json([
                    'error' => "El docente {$docente->nombre} excede el límite máximo de " . \App\Support\CargaAcademicaService::MAXIMO_HORAS . " horas semanales permitidas (acumularía {$totalProuesto} hrs). Operación cancelada.",
                ], 422);
            }
        }

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
                    $existente->update([
                        'Id_docente' => $cambio['Id_docente'],
                        'estado' => 'propuesta',
                        'motivo_rechazo' => null,
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

        if ($request->wantsJson() || $request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Designación guardada correctamente.',
            ]);
        }

        return redirect()->back()->with('status', 'Cambios guardados.');
    }

    public function copiarAnterior(Request $request, Carrera $carrera): JsonResponse
    {
        $user = $request->user();
        if ($user && ! $user->is_admin && $user->carrera_id && (int) $user->carrera_id !== (int) $carrera->id) {
            return response()->json(['error' => 'No tienes permisos para modificar designaciones en esta carrera.'], 403);
        }
        $data = $request->validate([
            'origen_gestion_id' => ['required', 'exists:gestiones,id'],
            'origen_periodo_id' => ['required', 'exists:periodos,id'],
            'destino_gestion_id' => ['required', 'exists:gestiones,id'],
            'destino_periodo_id' => ['required', 'exists:periodos,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        if ((int) $data['origen_gestion_id'] === (int) $data['destino_gestion_id'] && (int) $data['origen_periodo_id'] === (int) $data['destino_periodo_id']) {
            return response()->json([
                'success' => false,
                'error' => 'La gestión y periodo de origen deben ser distintos a los de destino.',
            ], 422);
        }

        $designacionesOrigen = Designacion::with('materia')
            ->where('Id_gestion', $data['origen_gestion_id'])
            ->where('Id_periodo', $data['origen_periodo_id'])
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $carrera->id))
            ->whereNotNull('Id_docente')
            ->get();

        if ($designacionesOrigen->isEmpty()) {
            return response()->json([
                'success' => false,
                'error' => 'No se encontraron designaciones registradas en el periodo de origen seleccionado.',
            ], 404);
        }

        $copiados = 0;

        DB::transaction(function () use ($designacionesOrigen, $data, $request, $carrera, &$copiados) {
            foreach ($designacionesOrigen as $desig) {
                $existente = Designacion::where('Id_gestion', $data['destino_gestion_id'])
                    ->where('Id_periodo', $data['destino_periodo_id'])
                    ->where('Id_grupo', $desig->Id_grupo)
                    ->first();

                if ($existente) {
                    $existente->update([
                        'Id_docente' => $desig->Id_docente,
                        'estado' => 'propuesta',
                    ]);
                } else {
                    Designacion::create([
                        'Id_docente' => $desig->Id_docente,
                        'Id_materia' => $desig->Id_materia,
                        'Id_grupo' => $desig->Id_grupo,
                        'Id_gestion' => $data['destino_gestion_id'],
                        'Id_periodo' => $data['destino_periodo_id'],
                        'estado' => 'propuesta',
                        'creado_por' => $request->user()->id,
                    ]);
                }

                $copiados++;
            }

            // Crear la entrada de la nueva propuesta independiente en la tabla revisiones
            $gestionDestino = Gestion::find($data['destino_gestion_id']);
            $periodoDestino = Periodo::find($data['destino_periodo_id']);
            $descDefault = 'Propuesta de Designación Copiada — Carrera de ' . $carrera->nombre;
            $desc = ! empty($data['descripcion']) ? trim($data['descripcion']) : $descDefault;

            Revision::create([
                'carrera_id' => $carrera->id,
                'descripcion' => $desc,
                'Id_gestion' => $data['destino_gestion_id'],
                'Id_periodo' => $data['destino_periodo_id'],
                'solicitado_por' => $request->user()->id,
                'estado' => 'propuesta',
            ]);
        });

        $gestionOrigen = Gestion::find($data['origen_gestion_id'])->nombre;
        $periodoOrigen = Periodo::find($data['origen_periodo_id'])->nombre;

        return response()->json([
            'success' => true,
            'copiados' => $copiados,
            'message' => "Se replicaron exitosamente {$copiados} designaciones desde la Gestión {$gestionOrigen} - Periodo {$periodoOrigen}.",
        ]);
    }

    public function previsualizarCopia(Request $request, Carrera $carrera): JsonResponse
    {
        $data = $request->validate([
            'origen_gestion_id' => ['required', 'exists:gestiones,id'],
            'origen_periodo_id' => ['required', 'exists:periodos,id'],
            'destino_gestion_id' => ['required', 'exists:gestiones,id'],
            'destino_periodo_id' => ['required', 'exists:periodos,id'],
        ]);

        $designacionesOrigen = Designacion::with(['docente', 'materia', 'grupo'])
            ->where('Id_gestion', $data['origen_gestion_id'])
            ->where('Id_periodo', $data['origen_periodo_id'])
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $carrera->id))
            ->whereNotNull('Id_docente')
            ->get();

        if ($designacionesOrigen->isEmpty()) {
            return response()->json([
                'success' => false,
                'items' => [],
                'message' => 'No hay designaciones registradas en el periodo de origen seleccionado.',
            ]);
        }

        $designacionesDestino = Designacion::with('docente')
            ->where('Id_gestion', $data['destino_gestion_id'])
            ->where('Id_periodo', $data['destino_periodo_id'])
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $carrera->id))
            ->get()
            ->keyBy('Id_grupo');

        $items = [];
        foreach ($designacionesOrigen as $desig) {
            $actual = $designacionesDestino->get($desig->Id_grupo);

            $impacto = 'Nueva asignación';
            $impactoColor = 'bg-emerald-100 text-emerald-800 border-emerald-300';

            if ($actual && $actual->Id_docente) {
                if ((int) $actual->Id_docente === (int) $desig->Id_docente) {
                    $impacto = 'Sin cambios';
                    $impactoColor = 'bg-gray-100 text-gray-700 border-gray-300';
                } else {
                    $impacto = 'Reemplaza a ' . ($actual->docente?->nombre ?? 'Docente previo');
                    $impactoColor = 'bg-amber-100 text-amber-800 border-amber-300';
                }
            }

            $items[] = [
                'grupo_id' => $desig->Id_grupo,
                'materia_sigla' => $desig->materia?->sigla,
                'materia_nombre' => $desig->materia?->nombre,
                'grupo_codigo' => 'G' . ($desig->grupo?->codigo ?? ''),
                'docente_id' => $desig->Id_docente,
                'docente_nombre' => $desig->docente?->nombre ?? 'Sin docente',
                'horas' => $desig->materia?->horas ?? 0,
                'impacto' => $impacto,
                'impactoColor' => $impactoColor,
            ];
        }

        return response()->json([
            'success' => true,
            'total' => count($items),
            'items' => $items,
        ]);
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
                    'docente' => ['id' => $actual->docente?->id, 'nombre' => $actual->docente?->nombre ?? 'Sin docente'],
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
                    'docente' => ['id' => $d->docente?->id, 'nombre' => $d->docente?->nombre ?? 'Sin docente'],
                    'gestion' => $d->gestion?->nombre ?? '',
                    'periodo' => $d->periodo?->nombre ?? '',
                    'estado' => $d->estado,
                ])
                ->values()
            );
    }

    public function create(Request $request): View
    {
        $gestionId = (int) (Gestion::max('id') ?? 0);
        $periodoId = (int) (Periodo::min('id') ?? 0);

        return view('designaciones.create', array_merge($this->catalogos($gestionId, $periodoId), [
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

    public function edit(Request $request, Designacion $designacion): View
    {
        $gestionId = $designacion->Id_gestion;
        $periodoId = $designacion->Id_periodo;

        return view('designaciones.edit', array_merge(
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

    public function historial(Designacion $designacion): View
    {
        $designacion->load(['docente', 'materia', 'grupo', 'gestion', 'periodo']);
        $historial = $designacion->historial()->orderByDesc('fecha')->get();

        return view('designaciones.historial', compact('designacion', 'historial'));
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
                return $horas < CargaAcademicaService::getMinimo();
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
