<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class MateriaController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'carrera_id' => ['nullable', 'exists:carreras,id'],
        ]);

        $materias = Materia::with('carrera')
            ->withCount('grupos')
            ->when($filtros['carrera_id'] ?? null, fn ($q, $carreraId) => $q->where('carrera_id', $carreraId))
            ->orderBy('sigla')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Materias/Index', [
            'materias' => $materias,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'filtros' => ['carrera_id' => $filtros['carrera_id'] ?? ''],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Materias/Create', [
            'carreras' => Carrera::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Materia::create($this->validarDatos($request));

        return redirect()->route('materias.index')
            ->with('status', 'Materia creada correctamente.');
    }

    public function edit(Materia $materia): Response
    {
        return Inertia::render('Materias/Edit', [
            'materia' => $materia,
            'carreras' => Carrera::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Materia $materia): RedirectResponse
    {
        $materia->update($this->validarDatos($request, $materia));

        return redirect()->route('materias.index')
            ->with('status', 'Materia actualizada correctamente.');
    }

    public function destroy(Materia $materia): RedirectResponse
    {
        if ($materia->grupos()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar: la materia tiene grupos asociados.');
        }

        $materia->delete();

        return redirect()->back()->with('status', 'Materia eliminada.');
    }

    private function validarDatos(Request $request, ?Materia $materia = null): array
    {
        return $request->validate([
            'sigla' => ['required', 'string', 'max:20', 'unique:materias,sigla,' . ($materia?->id ?? 'NULL')],
            'nombre' => ['required', 'string', 'max:150'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'horas' => ['required', 'integer', 'min:0', 'max:40'],
        ]);
    }
}
