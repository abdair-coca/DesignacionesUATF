<?php

namespace App\Http\Controllers;

use App\Models\PropuestaVersion;
use App\Services\RevisionPropuestaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class RevisionPropuestaController extends Controller
{
    public function __construct(private RevisionPropuestaService $revisiones) {}

    public function pendientes(Request $request): View
    {
        $folder = $request->string('folder')->toString();
        $folder = in_array($folder, ['inbox', 'pendientes', 'revisadas', 'todas'], true) ? $folder : 'inbox';
        $q = trim($request->string('q')->toString());
        $base = PropuestaVersion::with(['propuesta.carrera', 'propuesta.gestion', 'propuesta.periodo', 'remitente'])
            ->withCount('designaciones');

        $versiones = (clone $base)
            ->when(
                in_array($folder, ['inbox', 'pendientes'], true),
                fn ($query) => $query->where('estado', 'pendiente'),
                fn ($query) => $query->when($folder === 'revisadas', fn ($query) => $query->whereIn('estado', ['aprobada', 'observada']))
            )
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($query) use ($q) {
                    $query->whereHas('remitente', fn ($query) => $query->where('name', 'ilike', "%{$q}%"))
                        ->orWhereHas('propuesta.carrera', fn ($query) => $query->where('nombre', 'ilike', "%{$q}%")->orWhere('sigla', 'ilike', "%{$q}%"));
                });
            })
            ->latest('enviado_en')
            ->get();

        $pendientes = $versiones->map(fn (PropuestaVersion $version) => [
            'id' => $version->id,
            'carrera_nombre' => $version->propuesta->carrera->nombre,
            'carrera_sigla' => $version->propuesta->carrera->sigla,
            'descripcion' => $version->propuesta->descripcion,
            'cant_designaciones' => $version->designaciones_count,
            'solicitante' => $version->remitente->name,
            'gestion_nombre' => $version->propuesta->gestion->nombre,
            'periodo_nombre' => $version->propuesta->periodo->nombre,
            'solicitado_en' => $version->enviado_en?->format('d/m/Y H:i'),
            'hace_tiempo' => $version->enviado_en?->diffForHumans(),
            'estado' => $version->estado,
        ]);
        $counts = [
            'inbox' => (clone $base)->where('estado', 'pendiente')->count(),
            'pendientes' => (clone $base)->where('estado', 'pendiente')->count(),
            'revisadas' => (clone $base)->whereIn('estado', ['aprobada', 'observada'])->count(),
            'todas' => (clone $base)->count(),
        ];

        return view('revisiones.pendientes', compact('counts', 'folder', 'pendientes', 'q'));
    }

    public function revisar(PropuestaVersion $version): View
    {
        Gate::authorize('view', $version);

        $version->load([
            'propuesta.carrera',
            'propuesta.gestion',
            'propuesta.periodo',
            'remitente',
            'revisor',
            'designaciones.decision',
        ]);

        return view('versiones.revisar', [
            'version' => $version,
            'puedeDecidir' => auth()->user()->can('review', $version),
        ]);
    }

    public function decidir(Request $request, PropuestaVersion $version): RedirectResponse
    {
        Gate::authorize('review', $version);

        $data = $request->validate([
            'modo' => ['required', 'in:aprobar_todo,decidir_filas'],
            'observacion_general' => ['nullable', 'string', 'max:2000'],
            'decisiones' => ['nullable', 'array'],
            'decisiones.*.snapshot_id' => ['required_with:decisiones', 'integer', 'exists:propuesta_version_designaciones,id'],
            'decisiones.*.decision' => ['required_with:decisiones', 'in:aprobada,observada'],
            'decisiones.*.observacion' => ['nullable', 'string', 'max:1000'],
        ]);

        $this->revisiones->decidir(
            $version,
            $request->user(),
            $data['modo'],
            $data['observacion_general'] ?? null,
            $data['decisiones'] ?? [],
        );

        return redirect()->route('revisiones.pendientes')
            ->with('success', 'La decisión se registró sobre la versión enviada.');
    }
}
