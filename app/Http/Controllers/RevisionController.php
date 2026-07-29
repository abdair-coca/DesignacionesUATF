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

        // Buscar revision borrador o pendiente existente
        $revision = Revision::where('carrera_id', $data['carrera_id'])
            ->where('Id_gestion', $data['Id_gestion'])
            ->where('Id_periodo', $data['Id_periodo'])
            ->latest('id')
            ->first();

        if ($revision) {
            if ($revision->estado === 'pendiente') {
                return response()->json([
                    'error' => 'Ya hay una revisión pendiente para esta carrera.',
                ], 422);
            }

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

        $revision->load(['carrera:id,sigla,nombre', 'solicitante:id,name', 'gestion:id,nombre', 'periodo:id,nombre']);

        $designaciones = Designacion::with(['docente:id,nombre', 'materia:id,sigla,nombre', 'grupo:id,codigo'])
            ->where('Id_gestion', $revision->Id_gestion)
            ->where('Id_periodo', $revision->Id_periodo)
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $revision->carrera_id))
            ->orderBy('Id_materia')
            ->get()
            ->map(fn (Designacion $d) => [
                'id' => $d->id,
                'docente_nombre' => $d->docente?->nombre ?? 'Sin asignar',
                'materia_sigla' => $d->materia->sigla,
                'materia_nombre' => $d->materia->nombre,
                'grupo_codigo' => $d->grupo->codigo,
                'estado' => $d->estado,
                'motivo_rechazo' => $d->motivo_rechazo,
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

        return view('revisiones.revisar', [
            'revision' => [
                'id' => $revision->id,
                'carrera_nombre' => $revision->carrera->nombre,
                'carrera_sigla' => $revision->carrera->sigla,
                'gestion_nombre' => $revision->gestion->nombre,
                'periodo_nombre' => $revision->periodo->nombre,
                'solicitante' => $revision->solicitante->name,
                'solicitado_en' => $revision->solicitado_en?->format('d/m/Y H:i'),
                'estado' => $revision->estado,
            ],
            'designaciones' => $designaciones,
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
     * Admin marca la revision como completada y opcionalmente procesa acciones pendientes.
     */
    public function completar(Request $request, Revision $revision): JsonResponse
    {
        if (! $request->user()->is_admin) {
            abort(403);
        }

        if ($revision->estado !== 'pendiente') {
            return response()->json(['error' => 'Esta revisión ya fue completada.'], 422);
        }

        $data = $request->validate([
            'acciones' => ['nullable', 'array'],
            'acciones.*.id' => ['required_with:acciones', 'exists:designaciones,id'],
            'acciones.*.accion' => ['required_with:acciones', 'in:aprobar,rechazar'],
            'acciones.*.motivo_rechazo' => ['nullable', 'string', 'max:500'],
            'observaciones' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($revision, $data, $request) {
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

            // Comprobar si hay designaciones rechazadas en esta propuesta
            $rechazadas = Designacion::where('Id_gestion', $revision->Id_gestion)
                ->where('Id_periodo', $revision->Id_periodo)
                ->whereHas('materia', fn ($q) => $q->where('carrera_id', $revision->carrera_id))
                ->where('estado', 'rechazada')
                ->get();

            if ($rechazadas->isNotEmpty()) {
                $motivosUnicos = $rechazadas->pluck('motivo_rechazo')->filter()->unique()->values()->toArray();
                $textoObservacion = $data['observaciones'] ?? (count($motivosUnicos) > 0 ? implode('; ', $motivosUnicos) : null);
                if (empty($textoObservacion)) {
                    $textoObservacion = 'La propuesta fue devuelta por el Vicerrectorado con observaciones en la asignación de docentes.';
                }

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
