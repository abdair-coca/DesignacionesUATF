<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CatalogoCrud;
use App\Models\Grupo;
use App\Models\Materia;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

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

    protected function destroyRelacion(): array|string|null
    {
        return 'designaciones';
    }

    protected function reglas(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'materia_id' => ['required', 'exists:materias,id'],
            'codigo' => [
                'required', 'string', 'max:10',
                Rule::unique('grupos', 'codigo')
                    ->where('materia_id', $request->materia_id)
                    ->ignore($id),
            ],
            'estado' => ['required', 'string', 'in:habilitado,deshabilitado'],
        ]);
    }

    public function index(): View
    {
        $materiaId = request('materia_id');
        $estado = request('estado');

        return view('grupos.index', [
            'grupos' => Grupo::query()
                ->with('materia.carrera')
                ->withCount('designaciones')
                ->when($materiaId, fn ($q, $id) => $q->where('materia_id', $id))
                ->when($estado, fn ($q, $e) => $q->where('estado', $e))
                ->orderBy('codigo')
                ->paginate(15)
                ->withQueryString(),
            'materias' => Materia::orderBy('sigla')->get(),
            'filtros' => [
                'materia_id' => $materiaId ?? '',
                'estado' => $estado ?? '',
            ],
        ]);
    }

    public function create(): View
    {
        return view('grupos.create', [
            'materias' => Materia::orderBy('sigla')->get(),
        ]);
    }

    public function edit(Grupo $grupo): View
    {
        $grupo->load('materia');

        return view('grupos.edit', [
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
