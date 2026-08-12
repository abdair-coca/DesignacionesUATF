<?php

namespace App\Services;

use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\User;
use App\Services\Institutional\InstitutionalDesignacionesService;
use Illuminate\Support\Facades\DB;
use LogicException;

class PropuestaConsultaService
{
    public function __construct(private InstitutionalDesignacionesService $institucional) {}

    /**
     * @return array<string, mixed>
     */
    public function datosListaInstitucional(User $usuario): array
    {
        $carrera = $usuario->carrera;

        if (! $carrera?->sigla) {
            throw new LogicException('El director no tiene una carrera configurada.');
        }

        $items = $this->institucional->listar($carrera->sigla, '0', '0');

        return [
            'institutionalSource' => true,
            'propuestasData' => $items->map(function (array $item): array {
                $estadoInstitucional = strtoupper(trim((string) ($item['estado'] ?? '')));
                $esSolicitado = $estadoInstitucional === 'SOLICITADO';

                return [
                    'id' => $item['id'],
                    'descripcion' => $item['detalle'],
                    'fecha' => $item['fecha'],
                    'gestion' => $item['gestion'],
                    'periodo' => $item['periodo'],
                    'observacion' => $item['observacion'],
                    'estado' => $esSolicitado ? 'oficial' : 'institucional',
                    'estado_label' => $esSolicitado ? 'Oficial' : ($estadoInstitucional ?: 'Sin estado'),
                    'estado_institucional' => $estadoInstitucional,
                    'institutional' => true,
                    'designaciones_count' => 0,
                    'observaciones_filas' => [],
                    'version_pendiente_id' => null,
                    'created_at' => $item['fecha'],
                ];
            })->values(),
            'carreraActual' => $carrera,
            'gestiones' => collect(),
            'periodos' => collect(),
            'anoActual' => date('Y'),
            'gestionActualId' => 1,
        ];
    }

    public function datosLista(User $usuario): array
    {
        $gestiones = Gestion::orderByDesc('nombre')->get();
        $gestionActual = $gestiones->firstWhere('es_actual', true) ?? $gestiones->first();
        $propuestas = Propuesta::with(['gestion', 'periodo', 'versiones' => fn ($query) => $query
            ->with('designaciones.decision')
            ->latest('numero')])
            ->withCount('designaciones')
            ->where('carrera_id', $usuario->carrera_id)
            ->latest('updated_at')
            ->get();

        $propuestasData = $propuestas->map(function (Propuesta $propuesta): array {
            $versionPendiente = $propuesta->versiones->firstWhere('estado', 'pendiente');
            $versionObservada = $propuesta->versiones->firstWhere('estado', 'observada');
            $observacionesFilas = $versionObservada?->designaciones
                ->filter(fn ($fila) => $fila->getRelation('decision')?->getAttribute('decision') === 'observada')
                ->map(function ($fila): array {
                    $decision = $fila->getRelation('decision');

                    return [
                        'materia' => $fila->materia_sigla.' - '.$fila->materia_nombre,
                        'grupo' => (string) $fila->grupo_codigo,
                        'observacion' => $decision?->getAttribute('observacion'),
                    ];
                })
                ->values()
                ->all() ?? [];

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
                'observaciones_filas' => $observacionesFilas,
                'created_at' => $propuesta->created_at?->toIso8601String(),
            ];
        });

        return [
            'propuestas' => $propuestas,
            'propuestasData' => $propuestasData,
            'carreraActual' => $usuario->carrera,
            'gestiones' => $gestiones,
            'periodos' => Periodo::orderBy('nombre')->get(),
            'gestionActual' => $gestionActual,
        ];
    }

    public function datosEdicion(Propuesta $propuesta, User $usuario): array
    {
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
        $docentesAsignadosIds = $propuesta->designaciones
            ->pluck('docente_id')
            ->filter()
            ->unique()
            ->map(fn ($id) => (int) $id)
            ->all();
        $versionObservada = $propuesta->versiones->firstWhere('estado', 'observada');
        $observacionesPorGrupo = $versionObservada?->designaciones
            ->filter(fn ($fila) => $fila->getRelation('decision')?->getAttribute('decision') === 'observada')
            ->filter(function ($fila) use ($designacionesPorGrupo): bool {
                $actual = $designacionesPorGrupo->get($fila->grupo_id);

                if (! $actual) {
                    return false;
                }

                return (int) $actual->docente_id === (int) $fila->docente_id
                    && (int) $actual->materia_id === (int) $fila->materia_id
                    && (int) $actual->horas_pagadas === (int) $fila->horas_pagadas
                    && (int) $actual->horas_no_pagadas === (int) $fila->horas_no_pagadas
                    && trim((string) $actual->observacion_remuneracion) === trim((string) $fila->observacion_remuneracion);
            })
            ->mapWithKeys(fn ($fila) => [$fila->grupo_id => $fila->getRelation('decision')?->getAttribute('observacion')]) ?? collect();
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
            ->sortBy(function (Docente $docente) use ($docentesAsignadosIds): string {
                $asignado = in_array((int) $docente->id, $docentesAsignadosIds, true) ? 0 : 1;

                return sprintf('%d_%s_%d', $asignado, mb_strtolower($docente->nombre), $docente->id);
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
        $roster = $grupos->map(function (Grupo $grupo) use ($designacionesPorGrupo, $observacionesPorGrupo): array {
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
                'designacion' => $designacion ? [
                    'docente' => ['id' => $designacion->docente_id],
                    'horas_pagadas' => (int) ($designacion->horas_pagadas ?? $grupo->mallaCurricular->materia->horas),
                    'horas_no_pagadas' => (int) ($designacion->horas_no_pagadas ?? 0),
                    'observacion_remuneracion' => $designacion->observacion_remuneracion,
                ] : null,
                'bloqueada' => $designacion?->estado === 'aprobada_previamente',
                'observada' => $observacionesPorGrupo->has($grupo->id),
                'observacion_revision' => $observacionesPorGrupo->get($grupo->id),
            ];
        });
        $versionPendiente = $propuesta->versiones->firstWhere('estado', 'pendiente');

        return [
            'propuesta' => $propuesta,
            'carrera' => $propuesta->carrera,
            'designaciones' => $propuesta->designaciones,
            'roster' => $roster,
            'docentes' => $docentes,
            'gestiones' => Gestion::orderByDesc('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'observacionRevisionGeneral' => $observacionesPorGrupo->isNotEmpty()
                ? $versionObservada?->observaciones
                : null,
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
            'puedeEditar' => $usuario->can('update', $propuesta),
        ];
    }
}
