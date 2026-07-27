<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CatalogoCrud;
use App\Models\Gestion;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

    protected function destroyRelacion(): array|string|null
    {
        return 'designaciones';
    }

    protected function reglas(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:20', 'unique:gestiones,nombre,'.($id ?? 'NULL')],
        ]);
    }

    public function index(): View
    {
        return view('gestiones.index', [
            'gestiones' => Gestion::withCount('designaciones')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('gestiones.create');
    }

    public function edit(Gestion $gestion): View
    {
        return view('gestiones.edit', ['gestion' => $gestion]);
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
