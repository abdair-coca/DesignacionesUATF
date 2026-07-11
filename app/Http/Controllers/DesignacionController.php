<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\DesignacionHistorial;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Grupo;
use App\Models\Materia;
use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Inertia\Inertia;
use Inertia\Response;

class DesignacionController extends Controller
{
    private const CAMPOS_RASTREADOS = [
        'Id_docente',
        'Id_materia',
        'Id_grupo',
        'Id_gestion',
        'Id_periodo',
        'estado',
    ];

    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'q' => ['nullable', 'string', 'max:100'],
            'gestion_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_id' => ['nullable', 'exists:periodos,id'],
            'estado' => ['nullable', 'in:activas,pendientes,sin'],
        ]);

        $gestionId = (int) ($filtros['gestion_id'] ?? Gestion::max('id') ?? 0);
        $periodoId = (int) ($filtros['periodo_id'] ?? Periodo::min('id') ?? 0);

        $materiasPorCarrera = Materia::selectRaw('carrera_id, count(*) as total')
            ->groupBy('carrera_id')
            ->pluck('total', 'carrera_id');

        $gruposPorCarrera = Grupo::where('grupos.estado', 'habilitado')
            ->join('materias', 'materias.id', '=', 'grupos.materia_id')
            ->selectRaw('materias.carrera_id, count(*) as total')
            ->groupBy('materias.carrera_id')
            ->pluck('total', 'carrera_id');

        $asignadosPorCarrera = Designacion::where('Id_gestion', $gestionId)
            ->where('Id_periodo', $periodoId)
            ->where('designaciones.estado', '!=', 'rechazada')
            ->join('materias', 'materias.id', '=', 'designaciones.Id_materia')
            ->join('grupos', function ($join) {
                $join->on('grupos.id', '=', 'designaciones.Id_grupo')
                    ->where('grupos.estado', 'habilitado');
            })
            ->selectRaw('materias.carrera_id, count(distinct designaciones."Id_grupo") as total')
            ->groupBy('materias.carrera_id')
            ->pluck('total', 'carrera_id');

        $todas = Carrera::orderBy('nombre')->get()->map(function (Carrera $carrera) use ($materiasPorCarrera, $gruposPorCarrera, $asignadosPorCarrera) {
            $grupos = (int) ($gruposPorCarrera[$carrera->id] ?? 0);
            $activas = min((int) ($asignadosPorCarrera[$carrera->id] ?? 0), $grupos);
            $pendientes = $grupos - $activas;
            $situacion = $activas === 0 ? 'sin' : ($pendientes > 0 ? 'pendientes' : 'activas');

            return [
                'id' => $carrera->id,
                'sigla' => $carrera->sigla,
                'nombre' => $carrera->nombre,
                'materias' => (int) ($materiasPorCarrera[$carrera->id] ?? 0),
                'grupos' => $grupos,
                'activas' => $activas,
                'pendientes' => $pendientes,
                'situacion' => $situacion,
            ];
        });

        $resumen = [
            'total' => $todas->count(),
            'activas' => $todas->where('situacion', 'activas')->count(),
            'pendientes' => $todas->where('situacion', 'pendientes')->count(),
            'sin' => $todas->where('situacion', 'sin')->count(),
        ];

        $filtradas = $todas
            ->when($filtros['q'] ?? null, fn ($carreras, $q) => $carreras->filter(
                fn ($fila) => stripos($fila['nombre'], $q) !== false || stripos($fila['sigla'], $q) !== false
            ))
            ->when($filtros['estado'] ?? null, fn ($carreras, $estado) => $carreras->where('situacion', $estado))
            ->values();

        $porPagina = 10;
        $paginaActual = LengthAwarePaginator::resolveCurrentPage();
        $carreras = new LengthAwarePaginator(
            $filtradas->forPage($paginaActual, $porPagina)->values(),
            $filtradas->count(),
            $porPagina,
            $paginaActual,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Designaciones/PorCarrera', [
            'carreras' => $carreras,
            'resumen' => $resumen,
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'filtros' => [
                'q' => $filtros['q'] ?? '',
                'gestion_id' => (string) $gestionId,
                'periodo_id' => (string) $periodoId,
                'estado' => $filtros['estado'] ?? '',
            ],
        ]);
    }

    public function lista(Request $request): Response
    {
        $carrera = $request->filled('carrera_id')
            ? Carrera::find($request->integer('carrera_id'))
            : null;

        $designaciones = Designacion::with(['docente', 'materia', 'grupo', 'gestion', 'periodo'])
            ->when($carrera, fn ($q) => $q->whereHas('materia', fn ($m) => $m->where('carrera_id', $carrera->id)))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Designaciones/Lista', [
            'designaciones' => $designaciones,
            'carrera' => $carrera,
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('Designaciones/Create', $this->catalogos());
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarDatos($request);
        $data['creado_por'] = $request->user()->id;

        Designacion::create($data);

        return redirect()->route('designaciones.index')
            ->with('status', 'Designación creada correctamente.');
    }

    public function edit(Designacion $designacion): Response
    {
        return Inertia::render('Designaciones/Edit', array_merge(
            $this->catalogos(),
            ['designacion' => $designacion]
        ));
    }

    public function update(Request $request, Designacion $designacion): RedirectResponse
    {
        $data = $this->validarDatos($request);

        foreach (self::CAMPOS_RASTREADOS as $campo) {
            if ((string) $designacion->$campo !== (string) $data[$campo]) {
                DesignacionHistorial::create([
                    'designacion_id' => $designacion->id,
                    'campo' => $campo,
                    'valor_anterior' => (string) $designacion->$campo,
                    'valor_nuevo' => (string) $data[$campo],
                    'fecha' => now(),
                    'usuario_id' => $request->user()->id,
                ]);
            }
        }

        $designacion->update($data);

        return redirect()->route('designaciones.index')
            ->with('status', 'Designación actualizada correctamente.');
    }

    public function destroy(Designacion $designacion): RedirectResponse
    {
        $designacion->delete();

        return redirect()->back()
            ->with('status', 'Designación eliminada.');
    }

    public function historial(Designacion $designacion): Response
    {
        $designacion->load(['docente', 'materia', 'grupo', 'gestion', 'periodo']);
        $historial = $designacion->historial()->orderByDesc('fecha')->get();

        return Inertia::render('Designaciones/Historial', compact('designacion', 'historial'));
    }

    private function validarDatos(Request $request): array
    {
        return $request->validate([
            'Id_docente' => ['required', 'exists:docentes,id'],
            'Id_materia' => ['required', 'exists:materias,id'],
            'Id_grupo' => ['required', 'exists:grupos,id'],
            'Id_gestion' => ['required', 'exists:gestiones,id'],
            'Id_periodo' => ['required', 'exists:periodos,id'],
            'estado' => ['required', 'in:propuesta,aprobada,rechazada'],
        ]);
    }

    private function catalogos(): array
    {
        return [
            'docentes' => Docente::orderBy('nombre')->get(),
            'materias' => Materia::orderBy('sigla')->get(),
            'grupos' => Grupo::with('materia')->get(),
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
        ];
    }
}
