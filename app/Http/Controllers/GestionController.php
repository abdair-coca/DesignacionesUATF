<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CatalogoCrud;
use App\Models\Gestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GestionController extends Controller
{
    use CatalogoCrud;

    protected function modelo(): string
    {
        return Gestion::class;
    }

    protected function nombreEntidad(): string
    {
        return 'Gestión';
    }

    protected function rutaIndex(): string
    {
        return 'gestiones.index';
    }

    protected function destroyRelacion(): ?string
    {
        return 'designaciones';
    }

    protected function reglas(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:20', 'unique:gestiones,nombre,'.($id ?? 'NULL')],
        ]);
    }

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

    public function edit(Gestion $gestion): Response
    {
        return Inertia::render('Gestiones/Edit', ['gestion' => $gestion]);
    }

    public function update(Request $request, Gestion $gestion): RedirectResponse
    {
        return $this->actualizarModelo($request, $gestion);
    }

    public function destroy(Gestion $gestion): RedirectResponse
    {
        return $this->eliminarModelo($gestion);
    }
}
