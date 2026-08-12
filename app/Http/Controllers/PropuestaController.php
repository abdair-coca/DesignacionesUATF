<?php

namespace App\Http\Controllers;

use App\Http\Requests\CopiarPropuestaRequest;
use App\Http\Requests\CrearPropuestaRequest;
use App\Http\Requests\GuardarAsignacionesRequest;
use App\Http\Requests\ImportarPropuestaRequest;
use App\Models\Gestion;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\PropuestaVersion;
use App\Services\ImportacionPropuestaService;
use App\Services\PropuestaConsultaService;
use App\Services\PropuestaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use LogicException;
use Throwable;

class PropuestaController extends Controller
{
    public function __construct(
        private PropuestaService $propuestas,
        private ImportacionPropuestaService $importaciones,
        private PropuestaConsultaService $consultas,
    ) {}

    public function index(Request $request): View|Response
    {
        try {
            return view('designaciones.lista', $this->consultas->datosListaInstitucional($request->user()));
        } catch (LogicException) {
            $error = 'La integración institucional no está habilitada.';
        } catch (Throwable $exception) {
            Log::warning('Lista institucional no disponible.', [
                'exception' => $exception::class,
            ]);
            $error = 'No fue posible consultar las designaciones institucionales.';
        }

        return response()->view('designaciones.lista', [
            'institutionalSource' => true,
            'propuestasData' => collect(),
            'institutionalError' => $error,
            'carreraActual' => $request->user()->carrera,
            'gestiones' => collect(),
            'periodos' => collect(),
            'anoActual' => date('Y'),
            'gestionActualId' => 1,
        ], 503);
    }

    public function crear(CrearPropuestaRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $propuesta = $this->propuestas->crearBorrador(
            $request->user(),
            Gestion::findOrFail($data['gestion_id']),
            Periodo::findOrFail($data['periodo_id']),
            $data['descripcion'] ?? null,
        );

        return redirect()->route('designaciones.editar', $propuesta)
            ->with('success', 'Borrador listo para editar.');
    }

    public function previsualizarCopia(CopiarPropuestaRequest $request): JsonResponse
    {
        $data = $request->validated();

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

    public function copiar(CopiarPropuestaRequest $request): RedirectResponse
    {
        $data = $request->validated();

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

    public function editar(Request $request, Propuesta $propuesta): View
    {
        Gate::authorize('view', $propuesta);

        return view('designaciones.carrera', $this->consultas->datosEdicion($propuesta, $request->user()));
    }

    public function guardar(GuardarAsignacionesRequest $request, Propuesta $propuesta): RedirectResponse|JsonResponse
    {
        $data = $request->validated();

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

    public function previsualizarImportacion(ImportarPropuestaRequest $request, Propuesta $propuesta): View|JsonResponse
    {
        $data = $request->validated();
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

    public function aplicarImportacion(ImportarPropuestaRequest $request, Propuesta $propuesta): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
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
}
