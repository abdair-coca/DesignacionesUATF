<?php

namespace App\Http\Controllers;

use App\Http\Requests\GenerarCartaPersonalRequest;
use App\Services\Designaciones\PrepararCartaDesignacion;
use App\Services\Jachasun\JachasunDesignacionesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class PersonalDesignacionController extends Controller
{
    public function __construct(
        private JachasunDesignacionesService $designaciones,
        private PrepararCartaDesignacion $prepararCarta,
    ) {}

    public function index(Request $request): View|Response
    {
        $gestion = $this->gestion($request);
        $busqueda = mb_substr(trim($request->string('search')->toString()), 0, 100);

        try {
            $designaciones = $this->designaciones->listarAprobadasUniversidad($gestion);
        } catch (Throwable $exception) {
            Log::warning('Lista de Personal no disponible.', ['exception' => $exception::class]);

            return response()->view('personal.designaciones.lista', [
                'designaciones' => collect(),
                'busqueda' => $busqueda,
                'gestion' => $gestion,
                'errorJachasun' => 'No fue posible consultar las designaciones.',
            ], 503);
        }

        return view('personal.designaciones.lista', [
            'designaciones' => $designaciones,
            'busqueda' => $busqueda,
            'gestion' => $gestion,
            'errorJachasun' => null,
        ]);
    }

    public function show(Request $request, int $id): View|Response
    {
        $gestion = $this->gestion($request);

        try {
            $asignacion = $this->designaciones->obtenerAprobadaUniversidad($id, $gestion);
        } catch (Throwable $exception) {
            Log::warning('Designacion de Personal no disponible.', ['exception' => $exception::class]);

            return response('No fue posible consultar la designación solicitada.', 503);
        }

        abort_if($asignacion === null, 404);

        try {
            $filas = $this->designaciones->detallar($id);
        } catch (Throwable $exception) {
            Log::warning('Detalle de Personal no disponible.', ['exception' => $exception::class]);

            return response('No fue posible consultar el detalle de la designación.', 503);
        }

        return view('personal.designaciones.detalle', [
            'carrera' => $this->carreraDeDesignacion($asignacion),
            'asignacion' => $asignacion,
            'filas' => $filas,
            'gestion' => $gestion,
            'errorJachasun' => null,
        ]);
    }

    public function pdf(Request $request, int $id): Response
    {
        $gestion = $this->gestion($request);

        try {
            $asignacion = $this->designaciones->obtenerAprobadaUniversidad($id, $gestion);
        } catch (Throwable $exception) {
            Log::warning('PDF de Personal no disponible.', ['exception' => $exception::class]);

            return response('No fue posible consultar la designación solicitada.', 503);
        }

        abort_if($asignacion === null, 404);

        try {
            $filas = $this->designaciones->detallar($id);
        } catch (Throwable $exception) {
            Log::warning('Detalle de PDF de Personal no disponible.', ['exception' => $exception::class]);

            return response('No fue posible consultar el detalle de la designación.', 503);
        }

        return Pdf::loadView('designaciones.pdf', [
            'carrera' => $this->carreraDeDesignacion($asignacion),
            'asignacion' => $asignacion,
            'filas' => $filas,
            'fechaImpresion' => now()->format('d/m/Y H:i'),
        ])->stream("designacion-{$id}.pdf");
    }

    public function cartaPdf(GenerarCartaPersonalRequest $request, int $id): Response
    {
        $gestion = $this->gestion($request);

        try {
            $asignacion = $this->designaciones->obtenerAprobadaUniversidad($id, $gestion);
        } catch (Throwable $exception) {
            Log::warning('Carta de Personal no disponible.', ['exception' => $exception::class]);

            return response('No fue posible consultar la designación solicitada.', 503);
        }

        abort_if($asignacion === null, 404);

        try {
            $filas = $this->designaciones->detallar($id);
        } catch (Throwable $exception) {
            Log::warning('Detalle de carta de Personal no disponible.', ['exception' => $exception::class]);

            return response('No fue posible consultar el detalle de la designación.', 503);
        }

        try {
            $datosCarta = $request->validated();
            $filaSeleccionada = $filas->firstWhere('id', (int) $datosCarta['fila_id']);

            if ($filaSeleccionada === null) {
                throw new InvalidArgumentException('La fila seleccionada no pertenece a la designación.');
            }

            $docenteId = (int) ($filaSeleccionada['docente_id'] ?? 0);
            $filasDocente = $filas
                ->filter(fn (array $fila): bool => (int) ($fila['docente_id'] ?? 0) === $docenteId)
                ->values();

            $cartaData = $this->prepararCarta->preparar($filasDocente, $datosCarta);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Datos de carta de Personal no válidos.', ['exception' => $exception::class]);

            return response('No fue posible completar la operación.', 422);
        }

        try {
            return Pdf::loadView('designaciones.carta-pdf', [
                ...$cartaData,
                'carrera' => $this->carreraDeDesignacion($asignacion),
                'asignacion' => $asignacion,
            ])->setPaper('a4')->stream("carta-designacion-docente-{$id}.pdf");
        } catch (Throwable $exception) {
            Log::warning('Carta de Personal no disponible.', ['exception' => $exception::class]);

            return response('No fue posible completar la operación.', 503);
        }
    }

    private function gestion(Request $request): string
    {
        $datos = $request->validate([
            'gestion' => ['nullable', 'regex:/^\d{4}$/'],
        ]);

        return (string) ($datos['gestion'] ?? now()->format('Y'));
    }

    /** @param array<string, mixed> $asignacion */
    private function carreraDeDesignacion(array $asignacion): object
    {
        return (object) [
            'nombre' => (string) ($asignacion['programa_nombre'] ?? ''),
            'sigla' => (string) ($asignacion['programa_codigo'] ?? ''),
        ];
    }
}
