<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CatalogoCrud;
use App\Models\Carrera;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CarreraController extends Controller
{
    use CatalogoCrud;

    protected function modelo(): string
    {
        return Carrera::class;
    }

    protected function nombreEntidad(): string
    {
        return 'Carrera';
    }

    protected function rutaIndex(): string
    {
        return 'carreras.index';
    }

    protected function destroyRelacion(): array|string|null
    {
        return 'materias';
    }

    protected function reglas(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'sigla' => ['required', 'string', 'max:10', 'unique:carreras,sigla,'.($id ?? 'NULL')],
            'nombre' => ['required', 'string', 'max:100'],
        ]);
    }

    public function index(): View
    {
        return view('carreras.index', [
            'carreras' => Carrera::withCount('materias')->orderBy('sigla')->get(),
        ]);
    }

    public function create(): View
    {
        return view('carreras.create');
    }

    public function edit(Carrera $carrera): View
    {
        $carrera->loadCount('mallaCurricular');

        return view('carreras.edit', ['carrera' => $carrera]);
    }

    public function update(Request $request, Carrera $carrera): RedirectResponse
    {
        return $this->actualizarModelo($request, $carrera);
    }

    public function destroy(Carrera $carrera): RedirectResponse
    {
        return $this->eliminarModelo($carrera);
    }
}
