<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CatalogoCrud;
use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class MateriaController extends Controller
{
    use CatalogoCrud;

    protected function modelo(): string
    {
        return Materia::class;
    }

    protected function nombreEntidad(): string
    {
        return 'Materia';
    }

    protected function rutaIndex(): string
    {
        return 'materias.index';
    }

    protected function destroyRelacion(): ?string
    {
        return 'mallaCurricular';
    }

    protected function destroyEtiqueta(): string
    {
        return 'mallas curriculares';
    }

    protected function reglas(Request $request, ?int $id = null): array
    {
        $siglaRule = Rule::unique('materias', 'sigla');
        if ($id) {
            $siglaRule->ignore($id);
        }

        return $request->validate([
            'sigla' => ['required', 'string', 'max:20', $siglaRule],
            'nombre' => ['required', 'string', 'max:100'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'horas' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
    }

    public function index(): Response
    {
        $carreraId = request('carrera_id');

        return Inertia::render('Materias/Index', [
            'materias' => Materia::query()
                ->with('carrera')
                ->withCount('grupos')
                ->when($carreraId, fn ($q, $id) => $q->where('carrera_id', $id))
                ->orderBy('sigla')
                ->paginate(15)
                ->withQueryString(),
            'carreras' => Carrera::orderBy('sigla')->get(),
            'filtros' => [
                'carrera_id' => $carreraId ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Materias/Create', [
            'carreras' => Carrera::orderBy('sigla')->get(),
        ]);
    }

    public function edit(Materia $materia): Response
    {
        return Inertia::render('Materias/Edit', [
            'materia' => $materia,
            'carreras' => Carrera::orderBy('sigla')->get(),
        ]);
    }

    public function update(Request $request, Materia $materia): RedirectResponse
    {
        return $this->actualizarModelo($request, $materia);
    }

    public function destroy(Materia $materia): RedirectResponse
    {
        return $this->eliminarModelo($materia);
    }
}
