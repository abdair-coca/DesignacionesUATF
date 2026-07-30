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
     * Grupos con designación activa en una gestión/periodo específicos.
     */
    private function gruposOcupados(int $gestionId, int $periodoId): array
    {
        return Designacion::activas()
            ->forGestionPeriodo($gestionId, $periodoId)
            ->pluck('Id_grupo')
            ->toArray();
    }

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

        $gruposOcupados = $this->gruposOcupados($gestionId, $periodoId);

        $creadas = 0;
        $saltadas = 0;
        $creadasIds = [];
        $cargaService = app(CargaAcademicaService::class);

        DB::transaction(function () use ($data, $gestionId, $periodoId, $userId, &$gruposOcupados, &$creadas, &$saltadas, &$creadasIds) {
            foreach ($data['filas'] as $fila) {
                $grupoId = (int) $fila['Id_grupo'];

                // Skip si grupo ya tiene designación activa en destino
                if (in_array($grupoId, $gruposOcupados)) {
                    $saltadas++;

                    continue;
                }

                $grupo = Grupo::with(['materia', 'mallaCurricular'])->find($grupoId);
                if (! $grupo || $grupo->mallaCurricular?->materia_id !== (int) $fila['Id_materia']) {
                    $saltadas++;

                    continue;
                }

                // Check límite de 32 horas máximo semanales por docente en UATF
                $docenteId = $fila['Id_docente'];
                $horasActuales = (int) Designacion::forGestionPeriodo($gestionId, $periodoId)
                    ->where('Id_docente', $docenteId)
                    ->join('materias', 'designaciones.Id_materia', '=', 'materias.id')
                    ->sum('materias.horas');

                $horasNuevas = (int) ($grupo->materia->horas ?? 0);
                if (($horasActuales + $horasNuevas) > CargaAcademicaService::MAXIMO_HORAS) {
                    $saltadas++;

                    continue;
                }

                $nueva = Designacion::create([
                    'Id_docente' => $fila['Id_docente'],
                    'Id_materia' => $grupo->mallaCurricular->materia_id,
                    'Id_grupo' => $grupoId,
                    'malla_curricular_id' => $grupo->malla_curricular_id,
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
     * Previsualizar pegado: valida filas sin crear nada.
     * POST /designaciones/previsualizar-pegado  (JSON)
     */
    public function previsualizar(Request $request): JsonResponse
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

        $gruposOcupados = $this->gruposOcupados($gestionId, $periodoId);

        $resultados = [];

        foreach ($data['filas'] as $i => $fila) {
            $grupoId = (int) $fila['Id_grupo'];
            $estado = 'ok';
            $motivo = null;

            if (in_array($grupoId, $gruposOcupados)) {
                $estado = 'grupo_ocupado';
                $motivo = 'Este grupo ya tiene una designación activa en el periodo destino.';
            }

            $resultados[] = [
                'idx' => $i,
                'Id_docente' => $fila['Id_docente'],
                'Id_materia' => $fila['Id_materia'],
                'Id_grupo' => $grupoId,
                'estado' => $estado,
                'motivo' => $motivo,
            ];
        }

        return response()->json([
            'resultados' => $resultados,
            'resumen' => [
                'ok' => count(array_filter($resultados, fn ($r) => $r['estado'] === 'ok')),
                'saltadas' => count(array_filter($resultados, fn ($r) => $r['estado'] !== 'ok')),
            ],
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
