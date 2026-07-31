<?php

namespace App\Services;

use App\Models\Designacion;
use App\Models\Gestion;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\PropuestaDesignacion;
use App\Models\PropuestaEvento;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ImportacionPropuestaService
{
    public function previsualizar(Propuesta $propuesta, Gestion $gestionOrigen, Periodo $periodoOrigen): Collection
    {
        $this->validarOrigen($propuesta, $gestionOrigen, $periodoOrigen);

        $propuesta->loadMissing('designaciones.docente');
        $destino = $propuesta->designaciones->keyBy('grupo_id');

        return $this->filasOrigen($propuesta, $gestionOrigen, $periodoOrigen)
            ->map(function (array $origen) use ($destino) {
                $actual = $destino->get($origen['grupo_id']);
                $resultado = [
                    ...$origen,
                    'docente_actual' => $actual?->docente?->nombre,
                    'importable' => true,
                    'impacto' => 'Nueva asignacion',
                ];

                if ($actual?->estado === 'aprobada_previamente') {
                    $resultado['importable'] = false;
                    $resultado['impacto'] = 'Aprobada previamente: se conserva';
                } elseif ($actual && (int) $actual->docente_id === (int) $origen['docente_id']) {
                    $resultado['impacto'] = 'Sin cambios';
                } elseif ($actual) {
                    $resultado['impacto'] = 'Reemplaza a '.$resultado['docente_actual'];
                }

                return $resultado;
            })
            ->values();
    }

    public function previsualizarNueva(
        User $usuario,
        Gestion $gestionDestino,
        Periodo $periodoDestino,
        Gestion $gestionOrigen,
        Periodo $periodoOrigen,
    ): Collection {
        if (! $gestionDestino->es_actual) {
            throw ValidationException::withMessages([
                'gestion_id' => 'Solo se puede crear una propuesta para la gestion actual.',
            ]);
        }

        $this->validarOrigenIds($gestionDestino->id, $periodoDestino->id, $gestionOrigen->id, $periodoOrigen->id);

        return $this->filasOrigenPorCarrera($usuario->carrera_id, $gestionOrigen, $periodoOrigen)
            ->map(fn (array $origen) => [
                ...$origen,
                'docente_actual' => null,
                'importable' => true,
                'impacto' => 'Nueva asignacion',
            ])
            ->values();
    }

    public function aplicar(
        Propuesta $propuesta,
        Gestion $gestionOrigen,
        Periodo $periodoOrigen,
        User $usuario,
    ): int {
        $this->validarOrigen($propuesta, $gestionOrigen, $periodoOrigen);

        return DB::transaction(function () use ($propuesta, $gestionOrigen, $periodoOrigen, $usuario) {
            $propuesta = Propuesta::with('gestion')->lockForUpdate()->findOrFail($propuesta->id);
            $this->asegurarBorradorImportable($propuesta);
            $filasOrigen = $this->filasOrigen($propuesta, $gestionOrigen, $periodoOrigen);

            if ($filasOrigen->isEmpty()) {
                throw ValidationException::withMessages([
                    'origen_gestion_id' => 'No hay designaciones historicas importables para el origen seleccionado.',
                ]);
            }

            $filasActuales = $propuesta->designaciones()->lockForUpdate()->get()->keyBy('grupo_id');
            $aplicadas = 0;

            foreach ($filasOrigen as $origen) {
                $actual = $filasActuales->get($origen['grupo_id']);

                if ($actual?->estado === 'aprobada_previamente') {
                    continue;
                }

                if ($actual && (int) $actual->docente_id === (int) $origen['docente_id']) {
                    continue;
                }

                PropuestaDesignacion::updateOrCreate(
                    ['propuesta_id' => $propuesta->id, 'grupo_id' => $origen['grupo_id']],
                    [
                        'docente_id' => $origen['docente_id'],
                        'materia_id' => $origen['materia_id'],
                        'malla_curricular_id' => $origen['malla_curricular_id'],
                        'estado' => 'propuesta',
                    ],
                );
                $aplicadas++;
            }

            PropuestaEvento::create([
                'propuesta_id' => $propuesta->id,
                'usuario_id' => $usuario->id,
                'tipo' => 'importada',
                'datos' => [
                    'origen_gestion_id' => $gestionOrigen->id,
                    'origen_periodo_id' => $periodoOrigen->id,
                    'filas_aplicadas' => $aplicadas,
                ],
                'ocurrio_en' => now(),
            ]);

            return $aplicadas;
        });
    }

    private function filasOrigen(Propuesta $propuesta, Gestion $gestionOrigen, Periodo $periodoOrigen): Collection
    {
        return $this->filasOrigenPorCarrera($propuesta->carrera_id, $gestionOrigen, $periodoOrigen);
    }

    private function filasOrigenPorCarrera(int $carreraId, Gestion $gestionOrigen, Periodo $periodoOrigen): Collection
    {
        $desdePropuestaOficial = PropuestaDesignacion::query()
            ->with(['docente', 'materia', 'grupo.mallaCurricular'])
            ->whereHas('propuesta', fn ($query) => $query
                ->where('carrera_id', $carreraId)
                ->where('gestion_id', $gestionOrigen->id)
                ->where('periodo_id', $periodoOrigen->id)
                ->where('estado', 'oficial'))
            ->whereHas('grupo', fn ($query) => $query->where('estado', 'habilitado'))
            ->get()
            ->map(fn (PropuestaDesignacion $fila) => $this->normalizarFila(
                $fila->grupo_id,
                $fila->malla_curricular_id,
                $fila->materia_id,
                $fila->docente_id,
                $fila->grupo?->codigo,
                $fila->materia?->sigla,
                $fila->materia?->nombre,
                $fila->docente?->nombre,
            ));

        $gruposOficiales = $desdePropuestaOficial->pluck('grupo_id');
        $desdeHistorico = Designacion::query()
            ->with(['docente', 'materia', 'grupo.mallaCurricular'])
            ->where('Id_gestion', $gestionOrigen->id)
            ->where('Id_periodo', $periodoOrigen->id)
            ->where('estado', '!=', 'rechazada')
            ->whereHas('mallaCurricular', fn ($query) => $query->where('carrera_id', $carreraId))
            ->whereHas('grupo', fn ($query) => $query->where('estado', 'habilitado'))
            ->when($gruposOficiales->isNotEmpty(), fn ($query) => $query->whereNotIn('Id_grupo', $gruposOficiales))
            ->get()
            ->map(fn (Designacion $fila) => $this->normalizarFila(
                $fila->Id_grupo,
                $fila->malla_curricular_id,
                $fila->Id_materia,
                $fila->Id_docente,
                $fila->grupo?->codigo,
                $fila->materia?->sigla,
                $fila->materia?->nombre,
                $fila->docente?->nombre,
            ));

        return $desdePropuestaOficial
            ->concat($desdeHistorico)
            ->filter(fn (array $fila) => $fila['docente_id'] !== null)
            ->sortBy(['materia_sigla', 'grupo_codigo'])
            ->values();
    }

    private function normalizarFila(
        int $grupoId,
        int $mallaCurricularId,
        int $materiaId,
        ?int $docenteId,
        ?string $grupoCodigo,
        ?string $materiaSigla,
        ?string $materiaNombre,
        ?string $docenteNombre,
    ): array {
        return [
            'grupo_id' => $grupoId,
            'malla_curricular_id' => $mallaCurricularId,
            'materia_id' => $materiaId,
            'docente_id' => $docenteId,
            'grupo_codigo' => $grupoCodigo,
            'materia_sigla' => $materiaSigla,
            'materia_nombre' => $materiaNombre,
            'docente_nombre' => $docenteNombre,
        ];
    }

    private function validarOrigen(Propuesta $propuesta, Gestion $gestionOrigen, Periodo $periodoOrigen): void
    {
        $this->validarOrigenIds($propuesta->gestion_id, $propuesta->periodo_id, $gestionOrigen->id, $periodoOrigen->id);
    }

    private function validarOrigenIds(int $gestionDestinoId, int $periodoDestinoId, int $gestionOrigenId, int $periodoOrigenId): void
    {
        if ($gestionDestinoId === $gestionOrigenId && $periodoDestinoId === $periodoOrigenId) {
            throw ValidationException::withMessages([
                'origen_gestion_id' => 'El origen debe corresponder a otra gestion o periodo historico.',
            ]);
        }
    }

    private function asegurarBorradorImportable(Propuesta $propuesta): void
    {
        if (! $propuesta->gestion->es_actual) {
            throw ValidationException::withMessages([
                'propuesta' => 'Solo se puede importar sobre un borrador de la gestion actual.',
            ]);
        }

        if ($propuesta->estado !== 'borrador' || $propuesta->versiones()->where('estado', 'pendiente')->exists()) {
            throw ValidationException::withMessages([
                'propuesta' => 'La propuesta no esta disponible para importar mientras este enviada u oficial.',
            ]);
        }
    }
}
