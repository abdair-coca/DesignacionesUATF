<?php

namespace App\Http\Controllers;

use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class GrupoController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'materia_id' => ['nullable', 'exists:materias,id'],
            'estado' => ['nullable', 'in:habilitado,deshabilitado'],
        ]);

        $grupos = Grupo::with('materia.carrera')
            ->withCount('designaciones')
            ->when($filtros['materia_id'] ?? null, fn ($q, $materiaId) => $q->where('materia_id', $materiaId))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->orderBy('materia_id')
            ->orderBy('codigo')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Grupos/Index', [
            'grupos' => $grupos,
            'materias' => Materia::orderBy('sigla')->get(),
            'filtros' => [
                'materia_id' => $filtros['materia_id'] ?? '',
                'estado' => $filtros['estado'] ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Grupos/Create', [
            'materias' => Materia::orderBy('sigla')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Grupo::create($this->validarDatos($request));

        return redirect()->route('grupos.index')
            ->with('status', 'Grupo creado correctamente.');
    }

    public function edit(Grupo $grupo): Response
    {
        return Inertia::render('Grupos/Edit', [
            'grupo' => $grupo,
            'materias' => Materia::orderBy('sigla')->get(),
        ]);
    }

    public function update(Request $request, Grupo $grupo): RedirectResponse
    {
        $grupo->update($this->validarDatos($request, $grupo));

        return redirect()->route('grupos.index')
            ->with('status', 'Grupo actualizado correctamente.');
    }

    public function destroy(Grupo $grupo): RedirectResponse
    {
        if ($grupo->designaciones()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el grupo tiene designaciones asociadas.');
        }

        $grupo->delete();

        return redirect()->back()->with('status', 'Grupo eliminado.');
    }

    private function validarDatos(Request $request, ?Grupo $grupo = null): array
    {
        return $request->validate([
            'materia_id' => ['required', 'exists:materias,id'],
            'codigo' => [
                'required',
                'string',
                'max:10',
                Rule::unique('grupos')
                    ->where(fn ($q) => $q->where('materia_id', $request->input('materia_id')))
                    ->ignore($grupo?->id),
            ],
            'estado' => ['required', 'in:habilitado,deshabilitado'],
        ]);
    }
}
