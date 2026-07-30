<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\PropuestaVersion;
use App\Services\PropuestaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PropuestaController extends Controller
{
    public function __construct(private PropuestaService $propuestas) {}

    public function index(Request $request): View
    {
        $propuestas = Propuesta::with(['gestion', 'periodo', 'versiones' => fn ($query) => $query->latest('numero')])
            ->where('carrera_id', $request->user()->carrera_id)
            ->latest('updated_at')
            ->get();

        return view('propuestas.index', [
            'propuestas' => $propuestas,
            'gestiones' => Gestion::orderByDesc('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'gestionActual' => Gestion::where('es_actual', true)->first(),
        ]);
    }

    public function crear(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'gestion_id' => ['required', 'exists:gestiones,id'],
            'periodo_id' => ['required', 'exists:periodos,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ]);

        $propuesta = $this->propuestas->crearBorrador(
            $request->user(),
            Gestion::findOrFail($data['gestion_id']),
            Periodo::findOrFail($data['periodo_id']),
            $data['descripcion'] ?? null,
        );

        return redirect()->route('propuestas.editar', $propuesta)
            ->with('success', 'Borrador listo para editar.');
    }

    public function editar(Propuesta $propuesta): View
    {
        Gate::authorize('view', $propuesta);

        $propuesta->load([
            'carrera',
            'gestion',
            'periodo',
            'designaciones',
            'versiones' => fn ($query) => $query->latest('numero'),
        ]);

        $grupos = Grupo::with('mallaCurricular.materia')
            ->where('estado', 'habilitado')
            ->whereHas('mallaCurricular', fn ($query) => $query->where('carrera_id', $propuesta->carrera_id))
            ->orderBy('malla_curricular_id')
            ->orderBy('codigo')
            ->get();

        return view('propuestas.editar', [
            'propuesta' => $propuesta,
            'grupos' => $grupos,
            'docentes' => Docente::orderBy('nombre')->get(),
            'designacionesPorGrupo' => $propuesta->designaciones->keyBy('grupo_id'),
            'puedeEditar' => auth()->user()->can('update', $propuesta),
        ]);
    }

    public function guardar(Request $request, Propuesta $propuesta): RedirectResponse
    {
        Gate::authorize('update', $propuesta);

        $data = $request->validate([
            'cambios' => ['required', 'array', 'min:1'],
            'cambios.*.grupo_id' => ['required', 'exists:grupos,id'],
            'cambios.*.materia_id' => ['required', 'exists:materias,id'],
            'cambios.*.docente_id' => ['nullable', 'exists:docentes,id'],
        ]);

        $this->propuestas->guardarCambios($propuesta, $data['cambios']);

        return redirect()->route('propuestas.editar', $propuesta)
            ->with('success', 'Borrador actualizado.');
    }

    public function enviar(Request $request, Propuesta $propuesta): RedirectResponse
    {
        Gate::authorize('send', $propuesta);

        $version = $this->propuestas->enviar($propuesta, $request->user());

        return redirect()->route('propuestas.editar', $propuesta)
            ->with('success', "Versión {$version->numero} enviada a revisión.");
    }

    public function retirar(Request $request, PropuestaVersion $version): RedirectResponse
    {
        Gate::authorize('withdraw', $version);

        $this->propuestas->retirar($version, $request->user());

        return redirect()->route('propuestas.editar', $version->propuesta_id)
            ->with('success', 'La versión pendiente fue retirada. El borrador vuelve a estar disponible.');
    }
}
