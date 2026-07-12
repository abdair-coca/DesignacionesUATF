<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GestionController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Gestiones/Index', [
            'gestiones' => Gestion::withCount('designaciones')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Gestiones/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        Gestion::create($this->validarDatos($request));

        return redirect()->route('gestiones.index')
            ->with('status', 'Gestión creada correctamente.');
    }

    public function edit(Gestion $gestion): Response
    {
        return Inertia::render('Gestiones/Edit', ['gestion' => $gestion]);
    }

    public function update(Request $request, Gestion $gestion): RedirectResponse
    {
        $gestion->update($this->validarDatos($request, $gestion));

        return redirect()->route('gestiones.index')
            ->with('status', 'Gestión actualizada correctamente.');
    }

    public function destroy(Gestion $gestion): RedirectResponse
    {
        if ($gestion->designaciones()->exists()) {
            return redirect()->back()
                ->with('error', 'No se puede eliminar: la gestión tiene designaciones asociadas.');
        }

        $gestion->delete();

        return redirect()->back()->with('status', 'Gestión eliminada.');
    }

    private function validarDatos(Request $request, ?Gestion $gestion = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:20', 'unique:gestiones,nombre,' . ($gestion?->id ?? 'NULL')],
        ]);
    }
}
