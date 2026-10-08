<?php

namespace App\Http\Controllers;

use App\Services\Jachasun\JachasunDesignacionesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Throwable;

class VicerrectoradoDesignacionController extends Controller
{
    public function __construct(private JachasunDesignacionesService $designaciones) {}

    public function index(Request $request): View|Response
    {
        $this->autorizar($request);
        $gestion = $this->gestion($request);
        $carrera = $this->carrera($request);

        try {
            $designaciones = $this->designaciones->listarUniversidad($gestion);
        } catch (Throwable $exception) {
            Log::warning('Lista universitaria no disponible.', ['exception' => $exception::class]);

            return response()->view('vicerrectorado.designaciones.index', [
                'designaciones' => collect(),
                'gestion' => $gestion,
                'carrera' => $carrera,
                'total' => 0,
                'errorJachasun' => 'No fue posible consultar las designaciones universitarias.',
            ], 503);
        }

        return view('vicerrectorado.designaciones.index', [
            'designaciones' => $designaciones,
            'gestion' => $gestion,
            'carrera' => $carrera,
            'total' => $designaciones->count(),
        ]);
    }

    public function show(Request $request, int $id): View|Response
    {
        $this->autorizar($request);
        $gestion = $this->gestion($request);
        $carrera = $this->carrera($request);

        try {
            $asignacion = $this->designaciones->obtenerUniversidad($id, $gestion);
        } catch (Throwable $exception) {
            Log::warning('Designacion universitaria no disponible.', ['exception' => $exception::class]);

            return response()->view('vicerrectorado.designaciones.detalle', [
                'asignacion' => null,
                'filas' => collect(),
                'gestion' => $gestion,
                'carrera' => $carrera,
                'errorJachasun' => 'No fue posible consultar la designación solicitada.',
            ], 503);
        }

        abort_if($asignacion === null, 404);

        try {
            $filas = $this->designaciones->detallar($id);
        } catch (Throwable $exception) {
            Log::warning('Detalle universitario no disponible.', ['exception' => $exception::class]);

            return response()->view('vicerrectorado.designaciones.detalle', [
                'asignacion' => $asignacion,
                'filas' => collect(),
                'gestion' => $gestion,
                'carrera' => $carrera,
                'errorJachasun' => 'No fue posible consultar el detalle de la designación.',
            ], 503);
        }

        $decisionesDisponibles = true;
        $estadosDisponibles = true;
        $revisionGeneralConsultable = true;
        $revisionDesignacion = null;

        try {
            $decisionesPorFila = $this->designaciones->listarDecisionesAsignacionDetalle($id)->keyBy('id_detalle');
        } catch (Throwable $exception) {
            Log::warning('Estados de asignacion no disponibles.', ['exception' => $exception::class]);
            $decisionesPorFila = collect();
            $estadosDisponibles = false;
        }

        try {
            $revisionDesignacion = $this->designaciones->obtenerRevisionDesignacion($id);
        } catch (Throwable $exception) {
            Log::warning('Revision general no disponible.', ['exception' => $exception::class]);
            $revisionGeneralConsultable = false;
        }

        $filas = $filas->map(function (array $fila) use ($decisionesPorFila): array {
            $decision = $decisionesPorFila->get($fila['id'], []);
            $fila['estado_decision'] = $decision['estado'] ?? null;
            $fila['observacion_decision'] = $decision['observacion'] ?? null;

            return $fila;
        })->values();

        $decisionesIniciales = $filas->mapWithKeys(fn (array $fila): array => [
            $fila['id'] => [
                'decision' => $fila['estado_decision'] ?? '',
                'observacion' => $fila['observacion_decision'] ?? '',
            ],
        ])->all();

        return view('vicerrectorado.designaciones.detalle', [
            'asignacion' => $asignacion,
            'filas' => $filas,
            'decisionesIniciales' => $decisionesIniciales,
            'decisionesDisponibles' => $decisionesDisponibles,
            'estadosDisponibles' => $estadosDisponibles,
            'observacionRevision' => $revisionDesignacion?->observacion,
            'errorDecision' => $estadosDisponibles ? null : 'No fue posible consultar los estados de las asignaciones.',
            'errorRevisionGeneral' => $revisionGeneralConsultable ? null : 'No fue posible consultar el estado general.',
            'gestion' => $gestion,
            'carrera' => $carrera,
        ]);
    }

    public function guardarDecisiones(Request $request, int $id): JsonResponse|Response
    {
        $this->autorizar($request);
        $gestion = $this->gestion($request);
        $datos = $request->validate([
            'filas' => ['required', 'array', 'min:1', 'max:500'],
            'filas.*' => ['required', 'integer', 'min:1', 'distinct'],
            'estado' => ['required', 'in:APROBADA,RECHAZADA'],
            'observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        try {
            $asignacion = $this->designaciones->obtenerUniversidad($id, $gestion);
        } catch (Throwable $exception) {
            Log::warning('Decision universitaria no disponible.', ['exception' => $exception::class]);

            return response()->json([
                'message' => 'No fue posible consultar la designación para guardar las decisiones.',
            ], 503);
        }

        abort_if($asignacion === null, 404);

        $actualizadas = [];
        $fallidas = [];

        foreach ($datos['filas'] as $idDetalle) {
            try {
                $this->designaciones->guardarDecisionAsignacionDetalle(
                    $id,
                    (int) $idDetalle,
                    $datos['estado'],
                    $datos['observacion'] ?? null,
                );
                $actualizadas[] = (int) $idDetalle;
            } catch (Throwable $exception) {
                Log::warning('Decision de asignacion no disponible.', ['exception' => $exception::class]);
                $fallidas[] = (int) $idDetalle;
            }
        }

        $estadoDesignacion = null;

        try {
            $estadoDesignacion = $this->designaciones->obtenerUniversidad($id, $gestion)['estado'] ?? null;
        } catch (Throwable $exception) {
            Log::warning('Estado de designacion no disponible.', ['exception' => $exception::class]);
        }

        return response()->json([
            'actualizadas' => $actualizadas,
            'fallidas' => $fallidas,
            'estado_designacion' => $estadoDesignacion,
        ]);
    }

    public function guardarRevision(Request $request, int $id): JsonResponse
    {
        $this->autorizar($request);
        $gestion = $this->gestion($request);
        $validador = Validator::make($request->all(), [
            'estado' => ['required', 'in:APROBADO,OBSERVADA'],
            'observacion' => ['required_if:estado,OBSERVADA', 'nullable', 'string', 'max:1000'],
        ]);

        if ($validador->fails()) {
            return response()->json(['message' => 'No fue posible completar la operación.'], 422);
        }

        $datos = $validador->validated();

        try {
            $asignacion = $this->designaciones->obtenerUniversidad($id, $gestion);
        } catch (Throwable $exception) {
            Log::warning('Revision universitaria no disponible.', ['exception' => $exception::class]);

            return response()->json(['message' => 'No fue posible completar la operación.'], 503);
        }

        abort_if($asignacion === null, 404);

        try {
            $revision = $this->designaciones->guardarRevisionDesignacion(
                $id,
                $datos['estado'],
                $datos['observacion'] ?? null,
            );
        } catch (Throwable $exception) {
            Log::warning('Revision de designacion no guardada.', ['exception' => $exception::class]);

            return response()->json(['message' => 'No fue posible completar la operación.'], 503);
        }

        return response()->json([
            'estado_designacion' => $revision['estado'],
            'observacion_revision' => $revision['observacion'],
            'message' => 'Operación confirmada.',
        ]);
    }

    public function pdf(Request $request, int $id): Response
    {
        $this->autorizar($request);
        $gestion = $this->gestion($request);

        try {
            $asignacion = $this->designaciones->obtenerUniversidad($id, $gestion);

            if ($asignacion === null) {
                abort(404);
            }

            $filas = $this->designaciones->detallar($id);
        } catch (HttpException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            Log::warning('PDF universitario no disponible.', ['exception' => $exception::class]);

            return response('No fue posible generar el reporte solicitado.', 503);
        }

        return Pdf::loadView('designaciones.pdf', [
            'carrera' => (object) [
                'sigla' => $asignacion['programa_codigo'],
                'nombre' => $asignacion['programa_nombre'],
            ],
            'asignacion' => $asignacion,
            'filas' => $filas,
            'fechaImpresion' => now()->format('d/m/Y H:i'),
        ])->stream('designacion.pdf');
    }

    private function autorizar(Request $request): void
    {
        abort_unless($request->user()?->esVicerrectorado(), 403);
    }

    private function gestion(Request $request): string
    {
        $datos = $request->validate([
            'gestion' => ['nullable', 'regex:/^\d{4}$/'],
        ]);

        return (string) ($datos['gestion'] ?? now()->format('Y'));
    }

    private function carrera(Request $request): string
    {
        $datos = $request->validate([
            'carrera' => ['nullable', 'string', 'max:50'],
        ]);

        return (string) ($datos['carrera'] ?? '');
    }
}
