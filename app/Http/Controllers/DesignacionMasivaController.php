<?php

namespace App\Http\Controllers;

use App\Models\Designacion;
use App\Models\Grupo;
use App\Support\CargaAcademicaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DesignacionMasivaController extends Controller
{
    /**
     * Pegar designaciones copiadas (clipboard del navegador) a gestión/periodo destino.
     * POST /designaciones/pegar  (JSON)
     */
    public function pegar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'Id_gestion' => ['required', 'exists:gestiones,id'],
            'Id_periodo' => ['required', 'exists:periodos,id'],
            'filas' => ['required', 'array', 'min:1'],
            'filas.*.Id_docente' => ['required', 'exists:docentes,id'],
            'filas.*.Id_materia' => ['required', 'exists:materias,id'],
            'filas.*.Id_grupo' => ['required', 'exists:grupos,id'],
        ]);

        $gestionId = (int) $data['Id_gestion'];
        $periodoId = (int) $data['Id_periodo'];
        $userId = $request->user()->id;

        // Grupos que ya tienen designación activa en destino
        $gruposOcupados = Designacion::activas()
            ->forGestionPeriodo($gestionId, $periodoId)
            ->pluck('Id_grupo')
            ->toArray();

        $creadas = 0;
        $saltadas = 0;
        $creadasIds = [];
        $cargaService = app(CargaAcademicaService::class);

        DB::transaction(function () use ($data, $gestionId, $periodoId, $userId, &$gruposOcupados, $cargaService, &$creadas, &$saltadas, &$creadasIds) {
            foreach ($data['filas'] as $fila) {
                $grupoId = (int) $fila['Id_grupo'];

                // Skip si grupo ya tiene designación activa en destino
                if (in_array($grupoId, $gruposOcupados)) {
                    $saltadas++;
                    continue;
                }

                // Check límite de horas
                $grupo = Grupo::with('materia')->find($grupoId);
                if (! $grupo) {
                    $saltadas++;
                    continue;
                }

                $horasActuales = $cargaService->horasAsignadas($fila['Id_docente'], $gestionId, $periodoId);
                if ($horasActuales + $grupo->materia->horas > CargaAcademicaService::LIMITE_HORAS) {
                    $saltadas++;
                    continue;
                }

                $nueva = Designacion::create([
                    'Id_docente' => $fila['Id_docente'],
                    'Id_materia' => $fila['Id_materia'],
                    'Id_grupo' => $grupoId,
                    'Id_gestion' => $gestionId,
                    'Id_periodo' => $periodoId,
                    'estado' => 'propuesta',
                    'creado_por' => $userId,
                ]);

                $creadas++;
                $creadasIds[] = $nueva->id;
                $gruposOcupados[] = $grupoId;
            }
        });

        return response()->json([
            'creadas' => $creadas,
            'saltadas' => $saltadas,
            'creadas_ids' => $creadasIds,
        ]);
    }

    /**
     * Deshacer pegado: eliminar designaciones creadas.
     * POST /designaciones/deshacer-pegado  (JSON)
     */
    public function deshacerPegado(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['required', 'array', 'min:1'],
            'ids.*' => ['required', 'exists:designaciones,id'],
        ]);

        $eliminadas = Designacion::whereIn('id', $data['ids'])
            ->where('estado', 'propuesta')
            ->delete();

        return response()->json(['eliminadas' => $eliminadas]);
    }
}
