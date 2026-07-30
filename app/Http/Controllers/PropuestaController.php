<?php

namespace App\Http\Controllers;

use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Periodo;
use App\Models\Propuesta;
use App\Models\PropuestaVersion;
use App\Services\ImportacionPropuestaService;
use App\Services\PropuestaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class PropuestaController extends Controller
{
    public function __construct(
        private PropuestaService $propuestas,
        private ImportacionPropuestaService $importaciones,
    ) {}

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
            'versiones' => fn ($query) => $query->with('designaciones.decision')->latest('numero'),
        ]);

        $grupos = Grupo::with('mallaCurricular.materia')
            ->where('estado', 'habilitado')
            ->whereHas('mallaCurricular', fn ($query) => $query->where('carrera_id', $propuesta->carrera_id))
            ->orderBy('malla_curricular_id')
            ->orderBy('codigo')
            ->get();

        $ultimaVersionObservada = $propuesta->versiones->firstWhere('estado', 'observada');
        $observacionesPorGrupo = $ultimaVersionObservada?->designaciones
            ->mapWithKeys(fn ($snapshot) => [$snapshot->grupo_id => $snapshot->decision?->observacion])
            ->filter() ?? collect();

        return view('propuestas.editar', [
            'propuesta' => $propuesta,
            'grupos' => $grupos,
            'docentes' => Docente::orderBy('nombre')->get(),
            'designacionesPorGrupo' => $propuesta->designaciones->keyBy('grupo_id'),
            'observacionesPorGrupo' => $observacionesPorGrupo,
            'ultimaVersionObservada' => $ultimaVersionObservada,
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

    public function importar(Propuesta $propuesta): View
    {
        Gate::authorize('update', $propuesta);

        return view('propuestas.importar', [
            'propuesta' => $propuesta->load('carrera', 'gestion', 'periodo'),
            'gestiones' => Gestion::orderByDesc('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'previsualizacion' => null,
        ]);
    }

    public function previsualizarImportacion(Request $request, Propuesta $propuesta): View
    {
        Gate::authorize('update', $propuesta);
        $data = $this->validarOrigenImportacion($request);
        $gestionOrigen = Gestion::findOrFail($data['origen_gestion_id']);
        $periodoOrigen = Periodo::findOrFail($data['origen_periodo_id']);

        return view('propuestas.importar', [
            'propuesta' => $propuesta->load('carrera', 'gestion', 'periodo'),
            'gestiones' => Gestion::orderByDesc('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'origenGestion' => $gestionOrigen,
            'origenPeriodo' => $periodoOrigen,
            'previsualizacion' => $this->importaciones->previsualizar($propuesta, $gestionOrigen, $periodoOrigen),
        ]);
    }

    public function aplicarImportacion(Request $request, Propuesta $propuesta): RedirectResponse
    {
        Gate::authorize('update', $propuesta);
        $data = $this->validarOrigenImportacion($request);
        $filas = $this->importaciones->aplicar(
            $propuesta,
            Gestion::findOrFail($data['origen_gestion_id']),
            Periodo::findOrFail($data['origen_periodo_id']),
            $request->user(),
        );

        return redirect()->route('propuestas.editar', $propuesta)
            ->with('success', "Importacion aplicada: {$filas} filas actualizadas en el borrador.");
    }

    public function retirar(Request $request, PropuestaVersion $version): RedirectResponse
    {
        Gate::authorize('withdraw', $version);

        $this->propuestas->retirar($version, $request->user());

        return redirect()->route('propuestas.editar', $version->propuesta_id)
            ->with('success', 'La versión pendiente fue retirada. El borrador vuelve a estar disponible.');
    }

    private function validarOrigenImportacion(Request $request): array
    {
        return $request->validate([
            'origen_gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'origen_periodo_id' => ['required', 'integer', 'exists:periodos,id'],
        ]);
    }
}
