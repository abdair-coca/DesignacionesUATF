<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Docente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocenteController extends Controller
{
    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'carrera_origen_id' => ['nullable', 'exists:carreras,id'],
        ]);

        $docentes = Docente::with('carreraOrigen')
            ->withCount('designaciones')
            ->when($filtros['q'] ?? null, fn ($q, $texto) => $q->where(
                fn ($sub) => $sub->where('nombre', 'ilike', "%{$texto}%")->orWhere('ci', 'ilike', "%{$texto}%")
            ))
            ->when($filtros['carrera_origen_id'] ?? null, fn ($q, $carreraId) => $q->where('carrera_origen_id', $carreraId))
            ->orderBy('nombre')
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Docentes/Index', [
            'docentes' => $docentes,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'filtros' => [
                'q' => $filtros['q'] ?? '',
                'carrera_origen_id' => $filtros['carrera_origen_id'] ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Docentes/Create', [
            'carreras' => Carrera::orderBy('nombre')->get(),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        Docente::create($this->validarDatos($request));

        return redirect()->route('docentes.index')
            ->with('status', 'Docente creado correctamente.');
    }

    public function edit(Docente $docente): Response
    {
        return Inertia::render('Docentes/Edit', [
            'docente' => $docente,
            'carreras' => Carrera::orderBy('nombre')->get(),
        ]);
    }

    public function update(Request $request, Docente $docente): RedirectResponse
    {
        $docente->update($this->validarDatos($request, $docente));

        return redirect()->route('docentes.index')
            ->with('status', 'Docente actualizado correctamente.');
    }

    public function destroy(Docente $docente): RedirectResponse
    {
        if ($docente->designaciones()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el docente tiene designaciones asociadas.');
        }

        $docente->delete();

        return redirect()->back()->with('status', 'Docente eliminado.');
    }

    private function validarDatos(Request $request, ?Docente $docente = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'ci' => ['required', 'string', 'max:20', 'unique:docentes,ci,' . ($docente?->id ?? 'NULL')],
            'carrera_origen_id' => ['nullable', 'exists:carreras,id'],
        ]);
    }
}
