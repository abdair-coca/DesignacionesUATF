<?php

namespace App\Http\Controllers;

use App\Models\Designacion;
use App\Models\Revision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class RevisionController extends Controller
{
    /**
     * POST /revisiones/crear-propuesta
     * Director crea un borrador de propuesta con descripcion personalizada.
     */
    public function crearPropuesta(Request $request): JsonResponse
    {
        $data = $request->validate([
            'descripcion' => ['required', 'string', 'max:255'],
            'gestion_id' => ['required', 'exists:gestiones,id'],
            'periodo_id' => ['required', 'exists:periodos,id'],
        ]);

        $user = $request->user();
        $carreraId = $user->carrera_id ?? \App\Models\Carrera::first()?->id ?? 1;

        $revision = Revision::create([
            'carrera_id' => $carreraId,
            'descripcion' => $data['descripcion'],
            'Id_gestion' => $data['gestion_id'],
            'Id_periodo' => $data['periodo_id'],
            'solicitado_por' => $user->id,
            'estado' => 'propuesta',
        ]);

        return response()->json([
            'success' => true,
            'revision' => $revision,
        ]);
    }

    /**
     * POST /revisiones/solicitar
     * Usuario normal envia designaciones de una carrera a revision.
     */
    public function solicitar(Request $request): JsonResponse
    {
        $data = $request->validate([
            'carrera_id' => ['required', 'exists:carreras,id'],
            'Id_gestion' => ['required', 'exists:gestiones,id'],
            'Id_periodo' => ['required', 'exists:periodos,id'],
            'revision_id' => ['nullable', 'exists:revisiones,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $gestion = \App\Models\Gestion::find($data['Id_gestion']);
        $anioActual = (string) date('Y');

        if ($gestion && (string) $gestion->nombre !== $anioActual) {
            return response()->json([
                'error' => "Únicamente se pueden enviar a revisión las designaciones correspondientes a la gestión actual ({$anioActual}).",
            ], 422);
        }

        // Verificar 100% asignación de grupos HABILITADOS en la carrera
        $gruposTotales = \App\Models\Grupo::where('estado', 'habilitado')
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $data['carrera_id']))
            ->count();

        $gruposAsignados = Designacion::forGestionPeriodo($data['Id_gestion'], $data['Id_periodo'])
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $data['carrera_id']))
            ->whereNotNull('Id_docente')
            ->count();

        if ($gruposTotales > 0 && $gruposAsignados < $gruposTotales) {
            $pendientes = $gruposTotales - $gruposAsignados;
            return response()->json([
                'error' => "No se puede enviar a revisión al Vicerrectorado. Quedan {$pendientes} materias/grupos sin docente asignado.",
            ], 422);
        }

        if (! empty($data['revision_id'])) {
            $revision = Revision::find($data['revision_id']);
            if ($revision) {
                if ($revision->estado === 'pendiente') {
                    return response()->json([
                        'error' => 'Esta propuesta ya se encuentra pendiente de revisión por el Vicerrectorado.',
                    ], 422);
                }
                if ($revision->estado === 'revisado') {
                    return response()->json([
                        'error' => 'Esta propuesta ya fue aprobada y oficializada por el Vicerrectorado.',
                    ], 422);
                }

                $yaPendiente = Revision::where('carrera_id', $revision->carrera_id)
                    ->where('Id_gestion', $revision->Id_gestion)
                    ->where('Id_periodo', $revision->Id_periodo)
                    ->where('estado', 'pendiente')
                    ->where('id', '!=', $revision->id)
                    ->exists();

                if ($yaPendiente) {
                    return response()->json([
                        'error' => 'Ya hay una revisión pendiente para esta carrera.',
                    ], 422);
                }

                $revision->update([
                    'solicitado_por' => $request->user()->id,
                    'solicitado_en' => now(),
                    'estado' => 'pendiente',
                    'descripcion' => ! empty($data['descripcion']) ? trim($data['descripcion']) : $revision->descripcion,
                ]);

                return response()->json([
                    'success' => true,
                    'revision_id' => $revision->id,
                ]);
            }
        }

        $yaPendiente = Revision::where('carrera_id', $data['carrera_id'])
            ->where('Id_gestion', $data['Id_gestion'])
            ->where('Id_periodo', $data['Id_periodo'])
            ->where('estado', 'pendiente')
            ->exists();

        if ($yaPendiente) {
            return response()->json([
                'error' => 'Ya hay una revisión pendiente para esta carrera.',
            ], 422);
        }

        // Fallback: Buscar propuesta borrador existente o crear una nueva
        $revision = Revision::where('carrera_id', $data['carrera_id'])
            ->where('Id_gestion', $data['Id_gestion'])
            ->where('Id_periodo', $data['Id_periodo'])
            ->where('estado', 'propuesta')
            ->latest('id')
            ->first();

        if ($revision) {
            $revision->update([
                'solicitado_por' => $request->user()->id,
                'solicitado_en' => now(),
                'estado' => 'pendiente',
                'descripcion' => $data['descripcion'] ?? $revision->descripcion ?? ('Propuesta de Designación — ' . ($gestion->nombre ?? date('Y'))),
            ]);
        } else {
            $revision = Revision::create([
                'carrera_id' => $data['carrera_id'],
                'descripcion' => $data['descripcion'] ?? ('Propuesta de Designación — ' . ($gestion->nombre ?? date('Y'))),
                'Id_gestion' => $data['Id_gestion'],
                'Id_periodo' => $data['Id_periodo'],
                'solicitado_por' => $request->user()->id,
                'solicitado_en' => now(),
                'estado' => 'pendiente',
            ]);
        }

        return response()->json([
            'success' => true,
            'revision_id' => $revision->id,
        ]);
    }

    /**
     * POST /revisiones/{revision}/retirar
     * Director cancela/retira envio a revisión pendiente.
     */
    public function retirar(Request $request, Revision $revision): JsonResponse
    {
        if ($revision->estado !== 'pendiente') {
            return response()->json([
                'error' => 'Únicamente se pueden retirar solicitudes enviadas que aún estén pendientes.',
            ], 422);
        }

        $revision->update([
            'estado' => 'propuesta',
            'solicitado_en' => null,
            'observaciones' => null,
        ]);

        return response()->json(['success' => true]);
    }

    /**
     * GET /revisiones/pendientes
     * Admin ve lista de carreras pendientes de revision.
     */
    public function pendientes(Request $request): View
    {
        if (! $request->user()->is_admin) {
            abort(403, 'Solo administradores pueden ver la bandeja de revisiones.');
        }

        $folder = $request->query('folder', 'inbox');
        $q = $request->query('q', '');

        $query = Revision::with(['carrera:id,sigla,nombre', 'solicitante:id,name', 'gestion:id,nombre', 'periodo:id,nombre']);

        if ($folder === 'pendientes' || $folder === 'inbox') {
            $query->where('estado', 'pendiente');
        } elseif ($folder === 'revisadas') {
            $query->where('estado', 'revisado');
        }

        if (! empty($q)) {
            $query->whereHas('carrera', function ($cQuery) use ($q) {
                $cQuery->where('nombre', 'like', "%{$q}%")
                    ->orWhere('sigla', 'like', "%{$q}%");
            })->orWhereHas('solicitante', function ($sQuery) use ($q) {
                $sQuery->where('name', 'like', "%{$q}%");
            });
        }

        $revisionesList = $query->latest('solicitado_en')->get();

        $pendientesCount = Revision::where('estado', 'pendiente')->count();
        $revisadasCount = Revision::where('estado', 'revisado')->count();
        $todasCount = Revision::count();

        $pendientes = $revisionesList->map(function (Revision $r) {
            $cantDesignaciones = Designacion::where('Id_gestion', $r->Id_gestion)
                ->where('Id_periodo', $r->Id_periodo)
                ->whereHas('materia', fn ($q) => $q->where('carrera_id', $r->carrera_id))
                ->count();

            return [
                'id' => $r->id,
                'carrera_id' => $r->carrera_id,
                'carrera_nombre' => $r->carrera?->nombre ?? '',
                'carrera_sigla' => $r->carrera?->sigla ?? '',
                'gestion_nombre' => $r->gestion?->nombre ?? '',
                'periodo_nombre' => $r->periodo?->nombre ?? '',
                'solicitante' => $r->solicitante?->name ?? 'Director de Carrera',
                'solicitado_en' => $r->solicitado_en?->format('d/m/Y H:i') ?? '',
                'hace_tiempo' => $r->solicitado_en?->diffForHumans() ?? '',
                'estado' => $r->estado,
                'descripcion' => $r->descripcion,
                'cant_designaciones' => $cantDesignaciones,
            ];
        });

        return view('revisiones.pendientes', [
            'pendientes' => $pendientes,
            'counts' => [
                'inbox' => $pendientesCount,
                'pendientes' => $pendientesCount,
                'revisadas' => $revisadasCount,
                'todas' => $todasCount,
            ],
            'folder' => $folder,
            'q' => $q,
        ]);
    }

    /**
     * GET /revisiones/{revision}/revisar
     * Admin revisa todas las designaciones de una carrera.
     */
    public function revisar(Request $request, Revision $revision): View
    {
        if (! $request->user()->is_admin) {
            abort(403);
        }

        $revision->load(['carrera:id,sigla,nombre', 'solicitante:id,name', 'revisor:id,name', 'gestion:id,nombre', 'periodo:id,nombre']);

        $designacionesRaw = Designacion::with(['docente:id,nombre', 'materia:id,sigla,nombre,horas', 'grupo:id,codigo'])
            ->where('Id_gestion', $revision->Id_gestion)
            ->where('Id_periodo', $revision->Id_periodo)
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $revision->carrera_id))
            ->orderBy('Id_materia')
            ->get();

        // Calcular carga total docente global para detectar sobrecargas
        $docentesIds = $designacionesRaw->pluck('Id_docente')->filter()->unique()->toArray();
        $cargasGlobales = [];
        foreach ($docentesIds as $docId) {
            $totalHorasGlobal = Designacion::where('Id_gestion', $revision->Id_gestion)
                ->where('Id_periodo', $revision->Id_periodo)
                ->where('Id_docente', $docId)
                ->join('materias', 'designaciones.Id_materia', '=', 'materias.id')
                ->sum('materias.horas');
            $cargasGlobales[$docId] = (int) $totalHorasGlobal;
        }

        $designaciones = $designacionesRaw->map(fn (Designacion $d) => [
            'id' => $d->id,
            'docente_id' => $d->Id_docente,
            'docente_nombre' => $d->docente?->nombre ?? 'Sin asignar',
            'materia_sigla' => $d->materia->sigla,
            'materia_nombre' => $d->materia->nombre,
            'materia_horas' => $d->materia->horas ?? 0,
            'grupo_codigo' => $d->grupo->codigo,
            'estado' => $d->estado,
            'motivo_rechazo' => $d->motivo_rechazo,
            'carga_global' => $d->Id_docente ? ($cargasGlobales[$d->Id_docente] ?? 0) : 0,
            'es_sobrecarga' => $d->Id_docente && (($cargasGlobales[$d->Id_docente] ?? 0) > 32),
        ]);

        $totalGrupos = $designaciones->count();
        $gruposAsignados = $designaciones->filter(fn ($d) => $d['docente_nombre'] !== 'Sin asignar')->count();
        $docentesAsignados = $designaciones->pluck('docente_nombre')->filter(fn ($n) => $n !== 'Sin asignar')->unique()->count();

        $totalHoras = Designacion::where('Id_gestion', $revision->Id_gestion)
            ->where('Id_periodo', $revision->Id_periodo)
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $revision->carrera_id))
            ->whereNotNull('Id_docente')
            ->join('materias', 'designaciones.Id_materia', '=', 'materias.id')
            ->sum('materias.horas');

        $cobertura = $totalGrupos > 0 ? (int) round(($gruposAsignados / $totalGrupos) * 100) : 0;

        // Historial de revisiones previas para esta misma carrera y gestión/periodo
        $historialPrevio = Revision::with(['solicitante:id,name', 'revisor:id,name'])
            ->where('carrera_id', $revision->carrera_id)
            ->where('Id_gestion', $revision->Id_gestion)
            ->where('Id_periodo', $revision->Id_periodo)
            ->where('id', '!=', $revision->id)
            ->orderBy('id', 'desc')
            ->get()
            ->map(fn (Revision $r) => [
                'id' => $r->id,
                'descripcion' => $r->descripcion,
                'estado' => $r->estado,
                'observaciones' => $r->observaciones,
                'solicitado_en' => $r->solicitado_en?->format('d/m/Y H:i') ?? '—',
                'revisado_en' => $r->revisado_en?->format('d/m/Y H:i') ?? '—',
                'revisor_nombre' => $r->revisor?->name ?? 'Vicerrectorado',
            ]);

        return view('revisiones.revisar', [
            'revision' => [
                'id' => $revision->id,
                'descripcion' => $revision->descripcion ?? ('Propuesta — Carrera de ' . $revision->carrera->nombre),
                'carrera_nombre' => $revision->carrera->nombre,
                'carrera_sigla' => $revision->carrera->sigla,
                'gestion_nombre' => $revision->gestion->nombre,
                'periodo_nombre' => $revision->periodo->nombre,
                'solicitante' => $revision->solicitante->name,
                'solicitado_en' => $revision->solicitado_en?->format('d/m/Y H:i'),
                'estado' => $revision->estado,
                'observaciones' => $revision->observaciones,
            ],
            'designaciones' => $designaciones,
            'historialPrevio' => $historialPrevio,
            'stats' => [
                'cobertura' => $cobertura,
                'total_grupos' => $totalGrupos,
                'grupos_asignados' => $gruposAsignados,
                'docentes' => $docentesAsignados,
                'total_horas' => (int) $totalHoras,
            ],
        ]);
    }

    /**
     * POST /revisiones/{revision}/procesar
     * Admin aprueba/rechaza designaciones en lote.
     */
    public function procesar(Request $request, Revision $revision): JsonResponse
    {
        if (! $request->user()->is_admin) {
            abort(403);
        }

        if ($revision->estado !== 'pendiente') {
            return response()->json(['error' => 'Esta revisión ya fue completada.'], 422);
        }

        $data = $request->validate([
            'acciones' => ['required', 'array', 'min:1'],
            'acciones.*.id' => ['required', 'exists:designaciones,id'],
            'acciones.*.accion' => ['required', 'in:aprobar,rechazar'],
            'acciones.*.motivo_rechazo' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($data, $request) {
            foreach ($data['acciones'] as $accion) {
                $designacion = Designacion::find($accion['id']);

                if (! $designacion) {
                    continue;
                }

                if ($accion['accion'] === 'aprobar') {
                    $designacion->update([
                        'estado' => 'aprobada',
                        'aprobado_por' => $request->user()->id,
                        'motivo_rechazo' => null,
                    ]);
                } else {
                    $designacion->update([
                        'estado' => 'rechazada',
                        'motivo_rechazo' => $accion['motivo_rechazo'] ?? null,
                    ]);
                }
            }
        });

        return response()->json([
            'success' => true,
            'procesadas' => count($data['acciones']),
        ]);
    }

    /**
     * POST /revisiones/{revision}/completar
     * Admin toma una decisión explícita sobre la propuesta.
     */
    public function completar(Request $request, Revision $revision): JsonResponse
    {
        if (! $request->user()->is_admin) {
            abort(403);
        }

        if ($revision->estado !== 'pendiente') {
            return response()->json(['error' => 'Esta revisión ya fue procesada anteriormente.'], 422);
        }

        $data = $request->validate([
            'decision' => ['nullable', 'in:aprobar_todo,observar,lote'],
            'acciones' => ['nullable', 'array'],
            'acciones.*.id' => ['required_with:acciones', 'exists:designaciones,id'],
            'acciones.*.accion' => ['required_with:acciones', 'in:aprobar,rechazar'],
            'acciones.*.motivo_rechazo' => ['nullable', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        $decision = $data['decision'] ?? null;

        DB::transaction(function () use ($revision, $data, $decision, $request) {
            $designacionesCarrera = Designacion::where('Id_gestion', $revision->Id_gestion)
                ->where('Id_periodo', $revision->Id_periodo)
                ->whereHas('materia', fn ($q) => $q->where('carrera_id', $revision->carrera_id))
                ->get();

            if ($decision === 'aprobar_todo') {
                foreach ($designacionesCarrera as $desig) {
                    $desig->update([
                        'estado' => 'aprobada',
                        'aprobado_por' => $request->user()->id,
                        'motivo_rechazo' => null,
                    ]);
                }

                $revision->update([
                    'estado' => 'revisado',
                    'observaciones' => null,
                    'revisado_por' => $request->user()->id,
                    'revisado_en' => now(),
                ]);

                return;
            }

            if (! empty($data['acciones'])) {
                foreach ($data['acciones'] as $accion) {
                    $designacion = Designacion::find($accion['id']);
                    if (! $designacion) {
                        continue;
                    }

                    if ($accion['accion'] === 'aprobar') {
                        $designacion->update([
                            'estado' => 'aprobada',
                            'aprobado_por' => $request->user()->id,
                            'motivo_rechazo' => null,
                        ]);
                    } else {
                        $designacion->update([
                            'estado' => 'rechazada',
                            'motivo_rechazo' => $accion['motivo_rechazo'] ?? null,
                        ]);
                    }
                }
            }

            // Si hay designaciones rechazadas o la decisión fue observar explícitamente
            $rechazadas = Designacion::where('Id_gestion', $revision->Id_gestion)
                ->where('Id_periodo', $revision->Id_periodo)
                ->whereHas('materia', fn ($q) => $q->where('carrera_id', $revision->carrera_id))
                ->where('estado', 'rechazada')
                ->get();

            if ($decision === 'observar' || $rechazadas->isNotEmpty()) {
                $motivosUnicos = $rechazadas->pluck('motivo_rechazo')->filter()->unique()->values()->toArray();
                $textoObservacion = ! empty($data['observaciones'])
                    ? trim($data['observaciones'])
                    : (count($motivosUnicos) > 0 ? implode('; ', $motivosUnicos) : 'Devuelto con observaciones por el Vicerrectorado.');

                $revision->update([
                    'estado' => 'observado',
                    'observaciones' => $textoObservacion,
                    'revisado_por' => $request->user()->id,
                    'revisado_en' => now(),
                ]);
            } else {
                $revision->update([
                    'estado' => 'revisado',
                    'observaciones' => null,
                    'revisado_por' => $request->user()->id,
                    'revisado_en' => now(),
                ]);
            }
        });

        return response()->json(['success' => true]);
    }

    /**
     * DELETE /revisiones/{revision}
     * Eliminar propuesta no oficial.
     */
    public function destroy(Request $request, Revision $revision): JsonResponse
    {
        if ($revision->estado === 'revisado') {
            return response()->json([
                'error' => 'No se pueden eliminar propuestas que ya han sido aprobadas y oficializadas por el Vicerrectorado.',
            ], 422);
        }

        $revision->delete();

        return response()->json([
            'success' => true,
            'message' => 'La propuesta fue eliminada correctamente.',
        ]);
    }
}
