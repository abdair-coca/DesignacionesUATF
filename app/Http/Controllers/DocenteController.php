<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Concerns\CatalogoCrud;
use App\Models\Carrera;
use App\Models\Docente;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DocenteController extends Controller
{
    use CatalogoCrud;

    protected function modelo(): string
    {
        return Docente::class;
    }

    protected function nombreEntidad(): string
    {
        return 'Docente';
    }

    protected function rutaIndex(): string
    {
        return 'docentes.index';
    }

    protected function destroyRelacion(): ?string
    {
        return 'designaciones';
    }

    protected function reglas(Request $request, ?int $id = null): array
    {
        return $request->validate([
            'nombre' => ['required', 'string', 'max:150'],
            'ci' => ['required', 'string', 'max:20', 'unique:docentes,ci,'.($id ?? 'NULL')],
        ]);
    }

    public function index(): Response
    {
        $busqueda = request('q');
        $carreraId = request('carrera_origen_id');

        return Inertia::render('Docentes/Index', [
            'docentes' => Docente::query()
                ->when($busqueda, fn ($q, $b) => $q->where('nombre', 'ilike', "%{$b}%")
                    ->orWhere('ci', 'ilike', "%{$b}%"))
                ->when($carreraId, fn ($q, $id) => $q->where('carrera_origen_id', $id))
                ->with('carreraOrigen')
                ->withCount('designaciones')
                ->orderBy('nombre')
                ->paginate(15)
                ->withQueryString(),
            'carreras' => Carrera::orderBy('sigla')->get(),
            'filtros' => [
                'q' => $busqueda ?? '',
                'carrera_origen_id' => $carreraId ?? '',
            ],
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Docentes/Create', [
            'carreras' => Carrera::orderBy('sigla')->get(),
        ]);
    }

    public function edit(Docente $docente): Response
    {
        return Inertia::render('Docentes/Edit', ['docente' => $docente]);
    }

    public function update(Request $request, Docente $docente): RedirectResponse
    {
        return $this->actualizarModelo($request, $docente);
    }

    public function destroy(Docente $docente): RedirectResponse
    {
        return $this->eliminarModelo($docente);
    }
}
