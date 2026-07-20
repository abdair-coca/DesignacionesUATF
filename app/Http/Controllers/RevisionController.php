<?php

namespace App\Http\Controllers;

use App\Models\Designacion;
use App\Models\Revision;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class RevisionController extends Controller
{
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
        ]);

        // Verificar si ya hay una revision pendiente
        $pendiente = Revision::where('carrera_id', $data['carrera_id'])
            ->where('Id_gestion', $data['Id_gestion'])
            ->where('Id_periodo', $data['Id_periodo'])
            ->where('estado', 'pendiente')
            ->exists();

        if ($pendiente) {
            return response()->json([
                'error' => 'Ya hay una revisión pendiente para esta carrera.',
            ], 422);
        }

        $revision = Revision::create([
            'carrera_id' => $data['carrera_id'],
            'Id_gestion' => $data['Id_gestion'],
            'Id_periodo' => $data['Id_periodo'],
            'solicitado_por' => $request->user()->id,
            'solicitado_en' => now(),
            'estado' => 'pendiente',
        ]);

        return response()->json([
            'success' => true,
            'revision_id' => $revision->id,
        ]);
    }

    /**
     * GET /revisiones/pendientes
     * Admin ve lista de carreras pendientes de revision.
     */
    public function pendientes(Request $request): Response
    {
        if (! $request->user()->is_admin) {
            abort(403, 'Solo administradores pueden ver revisiones pendientes.');
        }

        $revisiones = Revision::with(['carrera:id,sigla,nombre', 'solicitante:id,name', 'gestion:id,nombre', 'periodo:id,nombre'])
            ->where('estado', 'pendiente')
            ->latest('solicitado_en')
            ->get();

        $pendientes = $revisiones->map(fn (Revision $r) => [
            'id' => $r->id,
            'carrera_id' => $r->carrera_id,
            'carrera_nombre' => $r->carrera?->nombre ?? '',
            'carrera_sigla' => $r->carrera?->sigla ?? '',
            'gestion_nombre' => $r->gestion?->nombre ?? '',
            'periodo_nombre' => $r->periodo?->nombre ?? '',
            'solicitante' => $r->solicitante?->name ?? '',
            'solicitado_en' => $r->solicitado_en?->format('d/m/Y H:i') ?? '',
        ]);

        return Inertia::render('Revisiones/Pendientes', [
            'pendientes' => $pendientes,
        ]);
    }

    /**
     * GET /revisiones/{revision}/revisar
     * Admin revisa todas las designaciones de una carrera.
     */
    public function revisar(Request $request, Revision $revision): Response
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

        return Inertia::render('Revisiones/Revisar', [
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

            $revision->update([
                'estado' => 'revisado',
                'revisado_por' => $request->user()->id,
                'revisado_en' => now(),
            ]);
        });

        return response()->json(['success' => true]);
    }
}
