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
use App\Support\CargaAcademicaService;
use App\Support\DesignacionReportService;
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

    public function __construct(
        private CargaAcademicaService $cargaAcademica,
        private DesignacionReportService $reportes,
    ) {}

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

        $todas = $this->reportes->resumenPorCarrera($gestionId, $periodoId);

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
        $filtros = $request->validate([
            'carrera_id' => ['nullable', 'exists:carreras,id'],
            'materia_id' => ['nullable', 'exists:materias,id'],
            'gestion_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_id' => ['nullable', 'exists:periodos,id'],
            'estado' => ['nullable', 'in:propuesta,aprobada,rechazada'],
        ]);

        $designaciones = Designacion::with(['docente', 'materia', 'grupo', 'gestion', 'periodo'])
            ->when($filtros['carrera_id'] ?? null, fn ($q, $carreraId) => $q->whereHas('materia', fn ($m) => $m->where('carrera_id', $carreraId)))
            ->when($filtros['materia_id'] ?? null, fn ($q, $materiaId) => $q->where('Id_materia', $materiaId))
            ->when($filtros['gestion_id'] ?? null, fn ($q, $gestionId) => $q->where('Id_gestion', $gestionId))
            ->when($filtros['periodo_id'] ?? null, fn ($q, $periodoId) => $q->where('Id_periodo', $periodoId))
            ->when($filtros['estado'] ?? null, fn ($q, $estado) => $q->where('estado', $estado))
            ->latest()
            ->paginate(15)
            ->withQueryString();

        return Inertia::render('Designaciones/Lista', [
            'designaciones' => $designaciones,
            'carreras' => Carrera::orderBy('nombre')->get(),
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'filtros' => [
                'carrera_id' => $filtros['carrera_id'] ?? '',
                'materia_id' => $filtros['materia_id'] ?? '',
                'gestion_id' => $filtros['gestion_id'] ?? '',
                'periodo_id' => $filtros['periodo_id'] ?? '',
                'estado' => $filtros['estado'] ?? '',
            ],
        ]);
    }

    public function carrera(Request $request, Carrera $carrera): Response
    {
        $filtros = $request->validate([
            'gestion_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_id' => ['nullable', 'exists:periodos,id'],
        ]);

        $gestionId = (int) ($filtros['gestion_id'] ?? Gestion::max('id') ?? 0);
        $periodoId = (int) ($filtros['periodo_id'] ?? Periodo::min('id') ?? 0);

        $materias = $this->reportes->resumenPorMateria($carrera, $gestionId, $periodoId);

        $designaciones = Designacion::with(['docente', 'materia', 'grupo'])
            ->where('Id_gestion', $gestionId)
            ->where('Id_periodo', $periodoId)
            ->whereHas('materia', fn ($q) => $q->where('carrera_id', $carrera->id))
            ->orderBy('Id_materia')
            ->get();

        return Inertia::render('Designaciones/Carrera', [
            'carrera' => $carrera,
            'materias' => $materias,
            'designaciones' => $designaciones,
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'filtros' => [
                'gestion_id' => (string) $gestionId,
                'periodo_id' => (string) $periodoId,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        return Inertia::render('Designaciones/Create', array_merge($this->catalogos(), [
            'prefill' => $request->only(['Id_docente', 'Id_materia', 'Id_grupo', 'Id_gestion', 'Id_periodo']),
            'resumenCarga' => $this->resumenCarga($request),
        ]));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $this->validarDatos($request);
        $data['creado_por'] = $request->user()->id;

        Designacion::create($data);

        return redirect()->route('designaciones.index')
            ->with('status', 'Designación creada correctamente.');
    }

    public function edit(Request $request, Designacion $designacion): Response
    {
        return Inertia::render('Designaciones/Edit', array_merge(
            $this->catalogos(),
            [
                'designacion' => $designacion,
                'resumenCarga' => $this->resumenCarga($request, $designacion->id),
            ]
        ));
    }

    public function update(Request $request, Designacion $designacion): RedirectResponse
    {
        $data = $this->validarDatos($request, $designacion->id);

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

    private function validarDatos(Request $request, ?int $excludeId = null): array
    {
        return $request->validate([
            'Id_docente' => ['required', 'exists:docentes,id', function ($attribute, $value, $fail) use ($request, $excludeId) {
                $existe = Designacion::where('Id_docente', $value)
                    ->where('Id_materia', $request->input('Id_materia'))
                    ->where('Id_grupo', $request->input('Id_grupo'))
                    ->where('Id_gestion', $request->input('Id_gestion'))
                    ->where('Id_periodo', $request->input('Id_periodo'))
                    ->when($excludeId, fn ($q) => $q->where('id', '!=', $excludeId))
                    ->exists();

                if ($existe) {
                    $fail('Esta designación ya existe.');
                }
            }],
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

    private function resumenCarga(Request $request, ?int $excluirDesignacionId = null): array
    {
        $docenteId = $request->filled('Id_docente') ? (int) $request->input('Id_docente') : null;
        $materiaId = $request->filled('Id_materia') ? (int) $request->input('Id_materia') : null;
        $grupoId = $request->filled('Id_grupo') ? (int) $request->input('Id_grupo') : null;
        $gestionId = $request->filled('Id_gestion') ? (int) $request->input('Id_gestion') : null;
        $periodoId = $request->filled('Id_periodo') ? (int) $request->input('Id_periodo') : null;

        $horasActuales = null;
        $horasMateria = $materiaId ? (int) (Materia::find($materiaId)?->horas ?? 0) : 0;
        $hayChoque = false;

        if ($docenteId && $gestionId && $periodoId) {
            $horasActuales = $this->cargaAcademica->horasAsignadas($docenteId, $gestionId, $periodoId, $excluirDesignacionId);
        }

        if ($grupoId && $gestionId && $periodoId) {
            $hayChoque = $this->cargaAcademica->hayChoque($grupoId, $gestionId, $periodoId, $excluirDesignacionId);
        }

        $horasProyectadas = $horasActuales === null ? null : $horasActuales + $horasMateria;

        return [
            'horasActuales' => $horasActuales,
            'horasProyectadas' => $horasProyectadas,
            'limite' => CargaAcademicaService::LIMITE_HORAS,
            'excedeLimite' => $horasProyectadas !== null && $horasProyectadas > CargaAcademicaService::LIMITE_HORAS,
            'hayChoque' => $hayChoque,
        ];
    }
}
