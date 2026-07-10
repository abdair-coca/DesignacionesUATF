<?php

namespace App\Http\Controllers;

use App\Models\Designacion;
use App\Models\DesignacionHistorial;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DesignacionController extends Controller
{
    private const CAMPOS_RASTREADOS = [
        'Id_docente',
        'Id_materia',
        'Id_grupo',
        'Id_gestion',
        'Id_periodo',
        'estado',
    ];

    public function index(): Response
    {
        $designaciones = Designacion::with(['docente', 'materia', 'grupo', 'gestion', 'periodo'])
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Designaciones/Index', [
            'designaciones' => $designaciones,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Designaciones/Create', $this->catalogos());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarDatos($request);

        Designacion::create($data);

        return redirect()->route('designaciones.index')
            ->with('status', 'Designación creada correctamente.');
    }

    public function edit(Designacion $designacion): Response
    {
        return Inertia::render('Designaciones/Edit', array_merge(
            $this->catalogos(),
            ['designacion' => $designacion]
        ));
    }

    public function update(Request $request, Designacion $designacion): RedirectResponse
    {
        $data = $this->validarDatos($request);

        foreach (self::CAMPOS_RASTREADOS as $campo) {
            if ((string) $designacion->$campo !== (string) $data[$campo]) {
                DesignacionHistorial::create([
                    'designacion_id' => $designacion->id,
                    'campo' => $campo,
                    'valor_anterior' => (string) $designacion->$campo,
                    'valor_nuevo' => (string) $data[$campo],
                    'fecha' => now(),
                ]);
            }
        }

        $designacion->update($data);

        return redirect()->route('designaciones.index')
            ->with('status', 'Designación actualizada correctamente.');
    }

    public function destroy(Designacion $designacion): RedirectResponse
    {
        $designacion->delete();

        return redirect()->route('designaciones.index')
            ->with('status', 'Designación eliminada.');
    }

    public function historial(Designacion $designacion): Response
    {
        $designacion->load(['docente', 'materia', 'grupo', 'gestion', 'periodo']);
        $historial = $designacion->historial()->orderByDesc('fecha')->get();

        return Inertia::render('Designaciones/Historial', compact('designacion', 'historial'));
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'Id_docente' => ['required', 'exists:docentes,id'],
            'Id_materia' => ['required', 'exists:materias,id'],
            'Id_grupo' => ['required', 'exists:grupos,id'],
            'Id_gestion' => ['required', 'exists:gestiones,id'],
            'Id_periodo' => ['required', 'exists:periodos,id'],
            'estado' => ['required', 'in:propuesta,aprobada,rechazada'],
        ]);
    }

    private function catalogos(): array
    {
        return [
            'docentes' => Docente::orderBy('nombre')->get(),
            'materias' => Materia::orderBy('sigla')->get(),
            'grupos' => Grupo::with('materia')->get(),
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
        ];
    }
}
