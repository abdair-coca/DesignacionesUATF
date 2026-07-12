<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class CarreraController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Carreras/Index', [
            'carreras' => Carrera::withCount('materias')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Carreras/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Carrera::create($this->validarDatos($request));

        return redirect()->route('carreras.index')
            ->with('status', 'Carrera creada correctamente.');
    }

    public function edit(Carrera $carrera): Response
    {
        return Inertia::render('Carreras/Edit', ['carrera' => $carrera]);
    }

    public function update(Request $request, Carrera $carrera): RedirectResponse
    {
        $carrera->update($this->validarDatos($request, $carrera));

        return redirect()->route('carreras.index')
            ->with('status', 'Carrera actualizada correctamente.');
    }

    public function destroy(Carrera $carrera): RedirectResponse
    {
        if ($carrera->materias()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar: la carrera tiene materias asociadas.');
        }

        $carrera->delete();

        return redirect()->back()->with('status', 'Carrera eliminada.');
    }

    private function validarDatos(Request $request, ?Carrera $carrera = null): array
    {
        return $request->validate([
            'sigla' => ['required', 'string', 'max:20', 'unique:carreras,sigla,' . ($carrera?->id ?? 'NULL')],
            'nombre' => ['required', 'string', 'max:150'],
        ]);
    }
}
