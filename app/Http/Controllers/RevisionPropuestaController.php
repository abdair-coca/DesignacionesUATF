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

    public function pendientes(): View
    {
        $versiones = PropuestaVersion::with(['propuesta.carrera', 'propuesta.gestion', 'propuesta.periodo', 'remitente'])
            ->where('estado', 'pendiente')
            ->latest('enviado_en')
            ->get();

        return view('versiones.pendientes', compact('versiones'));
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

        return redirect()->route('versiones.pendientes')
            ->with('success', 'La decisión se registró sobre la versión enviada.');
    }
}
