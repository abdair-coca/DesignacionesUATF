<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CatalogoCrud;
use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PeriodoController extends Controller
{
    use CatalogoCrud;

    protected function modelo(): string
    {
        return Periodo::class;
    }

    protected function nombreEntidad(): string
    {
        return 'Periodo';
    }

    protected function rutaIndex(): string
    {
        return 'periodos.index';
    }

    protected function destroyRelacion(): array|string|null
    {
        return 'designaciones';
    }

    protected function reglas(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:20', 'unique:periodos,nombre,'.($id ?? 'NULL')],
        ]);
    }

    public function index(): View
    {
        return view('periodos.index', [
            'periodos' => Periodo::withCount('designaciones')->orderBy('nombre')->get(),
        ]);
    }

    public function create(): View
    {
        return view('periodos.create');
    }

    public function edit(Periodo $periodo): View
    {
        return view('periodos.edit', ['periodo' => $periodo]);
    }

    public function update(Request $request, Periodo $periodo): RedirectResponse
    {
        return $this->actualizarModelo($request, $periodo);
    }

    public function destroy(Periodo $periodo): RedirectResponse
    {
        return $this->eliminarModelo($periodo);
    }
}
