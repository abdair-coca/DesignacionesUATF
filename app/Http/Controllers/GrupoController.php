<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CatalogoCrud;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GrupoController extends Controller
{
    use CatalogoCrud;

    protected function modelo(): string
    {
        return Grupo::class;
    }

    protected function nombreEntidad(): string
    {
        return 'Grupo';
    }

    protected function rutaIndex(): string
    {
        return 'grupos.index';
    }

    protected function destroyRelacion(): ?string
    {
        return 'designaciones';
    }

    protected function reglas(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'materia_id' => ['required', 'exists:materias,id'],
            'codigo' => ['required', 'string', 'max:10'],
            'estado' => ['required', 'string', 'in:habilitado,deshabilitado'],
        ]);
    }

    public function index(): Response
    {
        $materiaId = request('materia_id');
        $estado = request('estado');

        return Inertia::render('Grupos/Index', [
            'grupos' => Grupo::query()
                ->with('materia.carrera')
                ->withCount('designaciones')
                ->when($materiaId, fn ($q, $id) => $q->where('materia_id', $id))
                ->when($estado, fn ($q, $e) => $q->where('estado', $e))
                ->orderBy('codigo')
                ->get(),
            'materias' => Materia::orderBy('sigla')->get(),
            'filtros' => [
                'materia_id' => $materiaId ?? '',
                'estado' => $estado ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Grupos/Create', [
            'materias' => Materia::orderBy('sigla')->get(),
        ]);
    }

    public function edit(Grupo $grupo): Response
    {
        $grupo->load('materia');

        return Inertia::render('Grupos/Edit', [
            'grupo' => $grupo,
            'materias' => Materia::orderBy('sigla')->get(),
        ]);
    }

    public function update(Request $request, Grupo $grupo): RedirectResponse
    {
        return $this->actualizarModelo($request, $grupo);
    }

    public function destroy(Grupo $grupo): RedirectResponse
    {
        return $this->eliminarModelo($grupo);
    }
}
