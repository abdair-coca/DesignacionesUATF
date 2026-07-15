<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CatalogoCrud;
use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

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

    protected function destroyRelacion(): ?string
    {
        return 'designaciones';
    }

    protected function reglas(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:20', 'unique:periodos,nombre,'.($id ?? 'NULL')],
        ]);
    }

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

    public function edit(Periodo $periodo): Response
    {
        return Inertia::render('Periodos/Edit', ['periodo' => $periodo]);
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
