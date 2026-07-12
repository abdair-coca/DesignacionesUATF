<?php

namespace App\Http\Controllers;

use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PeriodoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Periodos/Index', [
            'periodos' => Periodo::withCount('designaciones')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Periodos/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Periodo::create($this->validarDatos($request));

        return redirect()->route('periodos.index')
            ->with('status', 'Periodo creado correctamente.');
    }

    public function edit(Periodo $periodo): Response
    {
        return Inertia::render('Periodos/Edit', ['periodo' => $periodo]);
    }

    public function update(Request $request, Periodo $periodo): RedirectResponse
    {
        $periodo->update($this->validarDatos($request, $periodo));

        return redirect()->route('periodos.index')
            ->with('status', 'Periodo actualizado correctamente.');
    }

    public function destroy(Periodo $periodo): RedirectResponse
    {
        if ($periodo->designaciones()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar: el periodo tiene designaciones asociadas.');
        }

        $periodo->delete();

        return redirect()->back()->with('status', 'Periodo eliminado.');
    }

    private function validarDatos(Request $request, ?Periodo $periodo = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:20', 'unique:periodos,nombre,' . ($periodo?->id ?? 'NULL')],
        ]);
    }
}
