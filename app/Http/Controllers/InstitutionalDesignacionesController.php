<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstitutionalDesignacionesConsultaRequest;
use App\Http\Requests\InstitutionalDesignacionesRequest;
use App\Services\Institutional\InstitutionalDesignacionesService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use LogicException;
use Throwable;

class InstitutionalDesignacionesController extends Controller
{
    public function __construct(private InstitutionalDesignacionesService $designaciones) {}

    public function index(InstitutionalDesignacionesRequest $request): JsonResponse
    {
        $parametros = $request->parametros();

        try {
            $items = $this->designaciones->listar(
                $parametros['programa'],
                $parametros['gestion'],
                $parametros['periodo'],
            );
        } catch (LogicException) {
            return response()->json([
                'message' => 'La integración institucional no está habilitada.',
            ], 503);
        } catch (Throwable $exception) {
            Log::warning('Consulta institucional fallida.', [
                'exception' => $exception::class,
            ]);

            return response()->json([
                'message' => 'No fue posible consultar la fuente institucional.',
            ], 502);
        }

        return response()->json([
            'programa' => $parametros['programa'],
            'gestion' => $parametros['gestion'],
            'periodo' => $parametros['periodo'],
            'items' => $items->values()->all(),
        ]);
    }

    public function consulta(InstitutionalDesignacionesConsultaRequest $request): View
    {
        $parametros = $request->parametros();
        $items = collect();
        $error = null;

        if ($parametros !== null) {
            try {
                $items = $this->designaciones->listar(
                    $parametros['programa'],
                    $parametros['gestion'],
                    $parametros['periodo'],
                );
            } catch (LogicException) {
                $error = 'La integración institucional no está habilitada.';
            } catch (Throwable $exception) {
                Log::warning('Consulta institucional fallida.', [
                    'exception' => $exception::class,
                ]);
                $error = 'No fue posible consultar la fuente institucional.';
            }
        }

        return view('institucional.designaciones', [
            'parametros' => $parametros,
            'items' => $items,
            'error' => $error,
            'programaCarrera' => strtoupper((string) $request->user()->carrera?->sigla),
            'consultado' => $parametros !== null,
        ]);
    }
}
