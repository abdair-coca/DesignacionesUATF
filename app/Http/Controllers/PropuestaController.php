<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\PropuestaVersion;
use App\Services\ImportacionPropuestaService;
use App\Services\PropuestaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PropuestaController extends Controller
{
    public function __construct(
        private PropuestaService $propuestas,
        private ImportacionPropuestaService $importaciones,
    ) {}

    public function index(Request $request): View
    {
        $gestiones = Gestion::orderByDesc('nombre')->get();
        $gestionActual = $gestiones->firstWhere('es_actual', true) ?? $gestiones->first();
        $gestionId = $request->integer('gestion_id') ?: $gestionActual?->id;
        $propuestas = Propuesta::with(['gestion', 'periodo', 'versiones' => fn ($query) => $query->latest('numero')])
            ->withCount('designaciones')
            ->where('carrera_id', $request->user()->carrera_id)
            ->when($gestionId, fn ($query) => $query->where('gestion_id', $gestionId))
            ->latest('updated_at')
            ->get();

        $propuestasData = $propuestas->map(function (Propuesta $propuesta): array {
            $versionPendiente = $propuesta->versiones->firstWhere('estado', 'pendiente');
            $versionObservada = $propuesta->versiones->firstWhere('estado', 'observada');

            return [
                'id' => $propuesta->id,
                'descripcion' => $propuesta->descripcion ?: 'Designaciones sin descripcion',
                'gestion_id' => $propuesta->gestion_id,
                'periodo_id' => $propuesta->periodo_id,
                'gestion' => $propuesta->gestion->nombre,
                'periodo' => $propuesta->periodo->nombre,
                'estado' => $propuesta->estado === 'oficial'
                    ? 'oficial'
                    : ($versionPendiente ? 'enviado' : ($versionObservada ? 'con_observaciones' : 'propuesta')),
                'observacion' => $versionObservada?->observaciones,
                'version_pendiente_id' => $versionPendiente?->id,
                'designaciones_count' => $propuesta->designaciones_count,
                'created_at' => $propuesta->created_at?->toIso8601String(),
            ];
        });

        return view('designaciones.lista', [
            'propuestas' => $propuestas,
            'propuestasData' => $propuestasData,
            'carreraActual' => $request->user()->carrera,
            'gestiones' => $gestiones,
            'periodos' => Periodo::orderBy('nombre')->get(),
            'gestionActual' => $gestionActual,
        ]);
    }

    public function crear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gestion_id' => ['required', 'exists:gestiones,id'],
            'periodo_id' => ['required', 'exists:periodos,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $propuesta = $this->propuestas->crearBorrador(
            $request->user(),
            Gestion::findOrFail($data['gestion_id']),
            Periodo::findOrFail($data['periodo_id']),
            $data['descripcion'] ?? null,
        );

        return redirect()->route('designaciones.editar', $propuesta)
            ->with('success', 'Borrador listo para editar.');
    }

    public function previsualizarCopia(Request $request): JsonResponse
    {
        $data = $request->validate([
            'gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
            'origen_gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'origen_periodo_id' => ['required', 'integer', 'exists:periodos,id'],
        ]);

        $filas = $this->importaciones->previsualizarNueva(
            $request->user(),
            Gestion::findOrFail($data['gestion_id']),
            Periodo::findOrFail($data['periodo_id']),
            Gestion::findOrFail($data['origen_gestion_id']),
            Periodo::findOrFail($data['origen_periodo_id']),
        );

        return response()->json([
            'filas' => $filas,
            'total' => $filas->count(),
            'importables' => $filas->where('importable', true)->count(),
        ]);
    }

    public function copiar(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'origen_gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'origen_periodo_id' => ['required', 'integer', 'exists:periodos,id'],
        ]);

        [$propuesta, $filas] = DB::transaction(function () use ($request, $data): array {
            $propuesta = $this->propuestas->crearBorrador(
                $request->user(),
                Gestion::findOrFail($data['gestion_id']),
                Periodo::findOrFail($data['periodo_id']),
                $data['descripcion'] ?? null,
            );

            $filas = $this->importaciones->aplicar(
                $propuesta,
                Gestion::findOrFail($data['origen_gestion_id']),
                Periodo::findOrFail($data['origen_periodo_id']),
                $request->user(),
            );

            return [$propuesta, $filas];
        });

        return redirect()->route('designaciones.editar', $propuesta)
            ->with('success', "Propuesta creada con {$filas} designaciones copiadas.");
    }

    public function editar(Propuesta $propuesta): View
    {
        Gate::authorize('view', $propuesta);

        $propuesta->load([
            'carrera',
            'gestion',
            'periodo',
            'designaciones.docente',
            'designaciones.materia',
            'designaciones.grupo',
            'versiones' => fn ($query) => $query
                ->with(['designaciones.decision', 'remitente', 'revisor'])
                ->latest('numero'),
        ]);

        $grupos = Grupo::with('mallaCurricular.materia')
            ->where('estado', 'habilitado')
            ->whereHas('mallaCurricular', fn ($query) => $query->where('carrera_id', $propuesta->carrera_id))
            ->orderBy('malla_curricular_id')
            ->orderBy('codigo')
            ->get();

        $designacionesPorGrupo = $propuesta->designaciones->keyBy('grupo_id');
        $docentesHistoricosIds = DB::table('designaciones')
            ->join('malla_curricular', 'designaciones.malla_curricular_id', '=', 'malla_curricular.id')
            ->where('malla_curricular.carrera_id', $propuesta->carrera_id)
            ->whereNotNull('designaciones.Id_docente')
            ->pluck('designaciones.Id_docente')
            ->unique()
            ->all();
        $horasOtrasCarrerasPorDocente = DB::table('designaciones')
            ->join('materias', 'designaciones.Id_materia', '=', 'materias.id')
            ->join('malla_curricular', 'designaciones.malla_curricular_id', '=', 'malla_curricular.id')
            ->where('designaciones.Id_gestion', $propuesta->gestion_id)
            ->where('designaciones.Id_periodo', $propuesta->periodo_id)
            ->where('malla_curricular.carrera_id', '!=', $propuesta->carrera_id)
            ->whereNotNull('designaciones.Id_docente')
            ->groupBy('designaciones.Id_docente')
            ->select('designaciones.Id_docente', DB::raw('SUM(materias.horas) as total_horas'))
            ->pluck('total_horas', 'designaciones.Id_docente');
        $docentes = Docente::with('carreraOrigen:id,sigla')
            ->get(['id', 'nombre', 'carrera_origen_id'])
            ->sortBy(function (Docente $docente) use ($propuesta, $docentesHistoricosIds): string {
                $prioridad = (int) $docente->carrera_origen_id === $propuesta->carrera_id
                    ? 1
                    : (in_array($docente->id, $docentesHistoricosIds) ? 2 : 3);

                return sprintf('%d_%s', $prioridad, strtolower($docente->nombre));
            })
            ->values()
            ->map(function (Docente $docente) use ($propuesta, $docentesHistoricosIds, $horasOtrasCarrerasPorDocente): array {
                $prioridad = (int) $docente->carrera_origen_id === $propuesta->carrera_id
                    ? 1
                    : (in_array($docente->id, $docentesHistoricosIds) ? 2 : 3);

                return [
                    'id' => $docente->id,
                    'nombre' => $docente->nombre,
                    'carreraSigla' => $docente->carreraOrigen?->sigla,
                    'prioridad' => $prioridad,
                    'horasOtrasCarreras' => (int) ($horasOtrasCarrerasPorDocente[$docente->id] ?? 0),
                ];
            });
        $roster = $grupos->map(function (Grupo $grupo) use ($designacionesPorGrupo): array {
            $designacion = $designacionesPorGrupo->get($grupo->id);

            return [
                'id' => $grupo->id,
                'materia' => [
                    'id' => $grupo->mallaCurricular->materia->id,
                    'nombre' => $grupo->mallaCurricular->materia->nombre,
                    'sigla' => $grupo->mallaCurricular->materia->sigla,
                ],
                'codigo' => $grupo->codigo,
                'horas' => $grupo->mallaCurricular->materia->horas,
                'designacion' => $designacion ? ['docente' => ['id' => $designacion->docente_id]] : null,
                'bloqueada' => $designacion?->estado === 'aprobada_previamente',
            ];
        });
        $versionPendiente = $propuesta->versiones->firstWhere('estado', 'pendiente');

        return view('designaciones.carrera', [
            'propuesta' => $propuesta,
            'carrera' => $propuesta->carrera,
            'designaciones' => $propuesta->designaciones,
            'roster' => $roster,
            'docentes' => $docentes,
            'gestiones' => Gestion::orderByDesc('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'revision' => $versionPendiente ? [
                'id' => $versionPendiente->id,
                'estado' => $versionPendiente->estado,
                'solicitante' => $versionPendiente->remitente?->name,
                'solicitado_en' => $versionPendiente->enviado_en?->format('d/m/Y H:i'),
            ] : null,
            'filtros' => [
                'gestion_id' => (string) $propuesta->gestion_id,
                'periodo_id' => (string) $propuesta->periodo_id,
            ],
            'puedeEditar' => auth()->user()->can('update', $propuesta),
        ]);
    }

    public function guardar(Request $request, Propuesta $propuesta): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $propuesta);

        $data = $request->validate([
            'cambios' => ['required', 'array', 'min:1'],
            'cambios.*.grupo_id' => ['required', 'exists:grupos,id'],
            'cambios.*.materia_id' => ['required', 'exists:materias,id'],
            'cambios.*.docente_id' => ['nullable', 'exists:docentes,id'],
        ]);

        $this->propuestas->guardarCambios($propuesta, $data['cambios']);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Borrador actualizado.']);
        }

        return redirect()->route('designaciones.editar', $propuesta)
            ->with('success', 'Borrador actualizado.');
    }

    public function enviar(Request $request, Propuesta $propuesta): RedirectResponse|JsonResponse
    {
        Gate::authorize('send', $propuesta);

        $version = $this->propuestas->enviar($propuesta, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Version {$version->numero} enviada a revision.",
            ]);
        }

        return redirect()->route('designaciones.editar', $propuesta)
            ->with('success', "Versión {$version->numero} enviada a revisión.");
    }

    public function importar(Request $request, Propuesta $propuesta): View
    {
        Gate::authorize('update', $propuesta);

        return view('propuestas.importar', [
            'propuesta' => $propuesta->load('carrera', 'gestion', 'periodo'),
            'gestiones' => Gestion::orderByDesc('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'origenGestion' => Gestion::find($request->integer('origen_gestion_id')),
            'origenPeriodo' => Periodo::find($request->integer('origen_periodo_id')),
            'previsualizacion' => null,
        ]);
    }

    public function previsualizarImportacion(Request $request, Propuesta $propuesta): View|JsonResponse
    {
        Gate::authorize('update', $propuesta);
        $data = $this->validarOrigenImportacion($request);
        $gestionOrigen = Gestion::findOrFail($data['origen_gestion_id']);
        $periodoOrigen = Periodo::findOrFail($data['origen_periodo_id']);

        $previsualizacion = $this->importaciones->previsualizar($propuesta, $gestionOrigen, $periodoOrigen);

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'items' => $previsualizacion->map(fn (array $fila) => [
                    ...$fila,
                    'impactoColor' => $fila['importable']
                        ? 'bg-cyan-100 text-cyan-800 border-cyan-200'
                        : 'bg-amber-100 text-amber-800 border-amber-200',
                ])->values(),
            ]);
        }

        return view('propuestas.importar', [
            'propuesta' => $propuesta->load('carrera', 'gestion', 'periodo'),
            'gestiones' => Gestion::orderByDesc('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'origenGestion' => $gestionOrigen,
            'origenPeriodo' => $periodoOrigen,
            'previsualizacion' => $previsualizacion,
        ]);
    }

    public function aplicarImportacion(Request $request, Propuesta $propuesta): RedirectResponse|JsonResponse
    {
        Gate::authorize('update', $propuesta);
        $data = $this->validarOrigenImportacion($request);
        $filas = $this->importaciones->aplicar(
            $propuesta,
            Gestion::findOrFail($data['origen_gestion_id']),
            Periodo::findOrFail($data['origen_periodo_id']),
            $request->user(),
        );

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => "Importacion aplicada: {$filas} filas actualizadas en el borrador.",
            ]);
        }

        return redirect()->route('designaciones.editar', $propuesta)
            ->with('success', "Importacion aplicada: {$filas} filas actualizadas en el borrador.");
    }

    public function retirar(Request $request, PropuestaVersion $version): RedirectResponse|JsonResponse
    {
        Gate::authorize('withdraw', $version);

        $this->propuestas->retirar($version, $request->user());

        if ($request->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'La version pendiente fue retirada. El borrador vuelve a estar disponible.',
            ]);
        }

        return redirect()->route('designaciones.editar', $version->propuesta_id)
            ->with('success', 'La versión pendiente fue retirada. El borrador vuelve a estar disponible.');
    }

    private function validarOrigenImportacion(Request $request): array
    {
        return $request->validate([
            'origen_gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'origen_periodo_id' => ['required', 'integer', 'exists:periodos,id'],
        ]);
    }
}
