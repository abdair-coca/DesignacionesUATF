<?php

namespace App\Http\Controllers;

use App\Services\Jachasun\JachasunDesignacionesService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;
use InvalidArgumentException;
use Throwable;

class DesignacionController extends Controller
{
    public function __construct(private JachasunDesignacionesService $designaciones) {}

    public function index(Request $request): View|Response
    {
        $facultadId = $request->user()?->esDecanatura()
            ? $this->facultadAutorizada($request)
            : null;
        $carrera = $facultadId === null
            ? $this->carreraAutorizada($request)
            : $this->carreraFacultad();
        $busqueda = mb_substr(trim($request->string('search')->toString()), 0, 100);

        try {
            $items = $facultadId === null
                ? $this->designaciones->listar($carrera->sigla, '0', '0')
                    ->sortByDesc(fn (array $d): array => [$d['fecha'] ?? null, $d['id'] ?? 0])
                    ->values()
                : $this->designaciones->listarAprobadasPorFacultad($facultadId);
        } catch (Throwable $exception) {
            Log::warning('Lista no disponible.', ['exception' => $exception::class]);

            return response()->view('designaciones.lista', [
                'carrera' => $carrera,
                'designaciones' => collect(),
                'busqueda' => $busqueda,
                'fuentesImportacion' => collect(),
                'contextoActual' => null,
                'errorJachasun' => 'No fue posible consultar las designaciones.',
            ], 503);
        }

        $contextoActual = $items
            ->filter(fn (array $designacion): bool => (int) $designacion['gestion'] > 0
                && (int) $designacion['periodo'] > 0)
            ->sortByDesc(fn (array $designacion): int => (int) $designacion['gestion'] * 100
                + (int) $designacion['periodo'])
            ->map(fn (array $designacion): array => [
                'gestion' => $designacion['gestion'],
                'periodo' => $designacion['periodo'],
            ])
            ->first();

        return view('designaciones.lista', [
            'carrera' => $carrera,
            'designaciones' => $items,
            'busqueda' => $busqueda,
            'contextoActual' => $facultadId === null ? $contextoActual : null,
            'fuentesImportacion' => $facultadId !== null ? collect() : $items
                ->map(fn (array $d): array => [
                    'id' => $d['id'],
                    'detalle' => $d['detalle'],
                    'gestion' => $d['gestion'],
                    'periodo' => $d['periodo'],
                ])
                ->values(),
        ]);
    }

    public function show(Request $request, int $id): View|Response
    {
        $facultadId = $request->user()?->esDecanatura()
            ? $this->facultadAutorizada($request)
            : null;
        $carrera = $facultadId === null
            ? $this->carreraAutorizada($request)
            : $this->carreraFacultad();

        try {
            $asignacion = $facultadId === null
                ? $this->designaciones->listar($carrera->sigla, '0', '0')->firstWhere('id', $id)
                : $this->designaciones->obtenerAprobadaPorFacultad($id, $facultadId);
        } catch (Throwable $exception) {
            Log::warning('Lista no disponible.', ['exception' => $exception::class]);

            return response()->view('designaciones.carrera', [
                'carrera' => $carrera,
                'asignacion' => [
                    'id' => $id,
                    'detalle' => null,
                    'gestion' => null,
                    'periodo' => null,
                    'estado' => null,
                    'observacion' => null,
                ],
                'filas' => collect(),
                'materiasDisponibles' => collect(),
                'materiasOferta' => collect(),
                'gruposOfertaDisponibles' => false,
                'puedeEditar' => false,
                'errorJachasun' => 'No fue posible consultar la designación solicitada.',
            ], 503);
        }

        abort_if($asignacion === null, 404);
        if ($facultadId !== null) {
            $carrera = $this->carreraDeDesignacion($asignacion);
        }
        $puedeEditar = strtoupper(trim((string) ($asignacion['estado'] ?? ''))) !== 'APROBADO';

        try {
            $filas = $this->designaciones->detallar($id);
        } catch (Throwable $exception) {
            Log::warning('Detalle no disponible.', ['exception' => $exception::class]);

            return response()->view('designaciones.carrera', [
                'carrera' => $carrera,
                'asignacion' => $asignacion,
                'filas' => collect(),
                'materiasDisponibles' => collect(),
                'materiasOferta' => collect(),
                'gruposOfertaDisponibles' => false,
                'puedeEditar' => $puedeEditar,
                'errorJachasun' => 'No fue posible consultar el detalle de la designación.',
            ], 503);
        }

        $decisionesPorFila = collect();
        $decisionesDisponibles = false;

        if ($facultadId === null) {
            $decisionesDisponibles = true;

            try {
                $decisionesPorFila = $this->designaciones->listarDecisionesAsignacionDetalle($id)->keyBy('id_detalle');
            } catch (Throwable $exception) {
                Log::warning('Estados de asignacion no disponibles.', ['exception' => $exception::class]);
                $decisionesPorFila = collect();
                $decisionesDisponibles = false;
            }
        }

        $filas = $filas->map(function (array $fila) use ($decisionesPorFila, $decisionesDisponibles): array {
            $decision = $decisionesPorFila->get($fila['id'], []);
            $fila['estado_decision'] = $decision['estado'] ?? null;
            $fila['observacion_decision'] = $decision['observacion'] ?? null;
            $fila['decisiones_disponibles'] = $decisionesDisponibles;

            return $fila;
        })->values();

        $materiasOferta = collect();
        $gruposOfertaDisponibles = false;

        if ($puedeEditar) {
            try {
                $materiasOferta = $this->designaciones->ofertaMaterias(
                    $carrera->sigla,
                    $asignacion['gestion'],
                    $asignacion['periodo'],
                );
                $gruposOfertaDisponibles = $materiasOferta->isEmpty()
                    || $materiasOferta->every(fn (array $materia): bool => $materia['grupos_disponibles'] ?? true);
                $materiasOferta = $this->materiasOfertaParaEdicion($materiasOferta, $filas);
            } catch (Throwable $exception) {
                Log::warning('Oferta de materias no disponible.', ['exception' => $exception::class]);

                return response()->view('designaciones.carrera', [
                    'carrera' => $carrera,
                    'asignacion' => $asignacion,
                    'filas' => $filas,
                    'materiasDisponibles' => $this->materiasDisponibles($filas),
                    'materiasOferta' => collect(),
                    'gruposOfertaDisponibles' => false,
                    'puedeEditar' => $puedeEditar,
                    'errorJachasun' => 'No fue posible consultar la oferta académica.',
                ], 503);
            }
        }

        return view('designaciones.carrera', [
            'carrera' => $carrera,
            'asignacion' => $asignacion,
            'filas' => $filas,
            'materiasDisponibles' => $this->materiasDisponibles($filas),
            'materiasOferta' => $materiasOferta,
            'gruposOfertaDisponibles' => $gruposOfertaDisponibles,
            'puedeEditar' => $puedeEditar,
        ]);
    }

    public function buscarDocentes(Request $request): JsonResponse
    {
        $this->carreraAutorizada($request);
        $termino = mb_substr(trim($request->string('q')->toString()), 0, 100);

        if ($termino === '') {
            return response()->json([]);
        }

        try {
            return response()->json($this->designaciones->buscarDocentes($termino)
                ->map(fn (array $docente): array => [
                    'id' => (int) ($docente['id'] ?? 0),
                    'nombre' => trim((string) ($docente['nombre'] ?? '')),
                    'ci' => trim((string) ($docente['ci'] ?? '')),
                ])
                ->filter(fn (array $docente): bool => $docente['id'] > 0)
                ->values());
        } catch (Throwable $exception) {
            Log::warning('Busqueda de docentes no disponible.', ['exception' => $exception::class]);

            return response()->json(['message' => 'No fue posible buscar docentes.'], 503);
        }
    }

    public function pdf(Request $request, int $id): Response
    {
        $facultadId = $request->user()?->esDecanatura()
            ? $this->facultadAutorizada($request)
            : null;
        $carrera = $facultadId === null
            ? $this->carreraAutorizada($request)
            : $this->carreraFacultad();

        try {
            $asignacion = $facultadId === null
                ? $this->designaciones->listar($carrera->sigla, '0', '0')->firstWhere('id', $id)
                : $this->designaciones->obtenerAprobadaPorFacultad($id, $facultadId);
        } catch (Throwable $exception) {
            Log::warning('Lista no disponible.', ['exception' => $exception::class]);

            return response('No fue posible consultar la designación solicitada.', 503);
        }

        abort_if($asignacion === null, 404);
        if ($facultadId !== null) {
            $carrera = $this->carreraDeDesignacion($asignacion);
        }

        try {
            $filas = $this->designaciones->detallar($id);
        } catch (Throwable $exception) {
            Log::warning('Detalle no disponible.', ['exception' => $exception::class]);

            return response('No fue posible consultar el detalle de la designación.', 503);
        }

        $pdf = Pdf::loadView('designaciones.pdf', [
            'carrera' => $carrera,
            'asignacion' => $asignacion,
            'filas' => $filas,
            'fechaImpresion' => now()->format('d/m/Y H:i'),
        ]);

        return $pdf->stream("designacion-{$id}.pdf");
    }

    public function store(Request $request): RedirectResponse
    {
        $carrera = $this->carreraAutorizada($request);

        $reglas = [
            'fecha' => ['nullable', 'string', 'max:60'],
            'obs' => ['nullable', 'string', 'max:500'],
            'importar_desde' => ['nullable', 'integer', 'min:1'],
        ];

        $reglas['gestion'] = $request->filled('gestion') || $request->filled('periodo')
            ? ['required', 'regex:/^\d{4}$/']
            : ['nullable', 'regex:/^\d{4}$/'];
        $reglas['periodo'] = $request->filled('gestion') || $request->filled('periodo')
            ? ['required', 'regex:/^\d{1,2}$/']
            : ['nullable', 'regex:/^\d{1,2}$/'];

        $data = $request->validate($reglas);

        $importarDesde = (int) ($data['importar_desde'] ?? 0);

        try {
            $contextoActual = $this->designaciones->contextoActual($carrera->sigla);
            $gestion = (string) ($data['gestion'] ?? $contextoActual['gestion']);
            $periodo = (string) ($data['periodo'] ?? $contextoActual['periodo']);

            if ($gestion !== $contextoActual['gestion'] || $periodo !== $contextoActual['periodo']) {
                throw new InvalidArgumentException('La designacion debe pertenecer al contexto academico actual.');
            }

            if ($importarDesde > 0) {
                $this->designaciones->copiar(
                    $carrera->sigla,
                    $importarDesde,
                    $gestion,
                    $periodo,
                    $data['obs'] ?? '',
                );
            } else {
                $this->designaciones->insertar(
                    $carrera->sigla,
                    $data['fecha'] ?? '',
                    $gestion,
                    $periodo,
                    $data['obs'] ?? '',
                );
            }
        } catch (Throwable $exception) {
            Log::warning('Creacion de designacion no disponible.', ['exception' => $exception::class]);

            return back()->with('error', 'No fue posible crear la designación.');
        }

        return back()->with('success', $importarDesde > 0
            ? 'Designación importada desde una gestión anterior.'
            : 'Designación creada correctamente.');
    }

    public function update(Request $request, int $id): RedirectResponse
    {
        $carrera = $this->carreraAutorizada($request);

        $data = $request->validate([
            'fecha' => ['nullable', 'string', 'max:60'],
            'obs' => ['nullable', 'string', 'max:500'],
        ]);

        try {
            $actual = $this->designaciones->listar($carrera->sigla, '0', '0')
                ->firstWhere('id', $id);
        } catch (Throwable $exception) {
            Log::warning('Actualizacion de designacion no disponible.', ['exception' => $exception::class]);

            return back()->with('error', 'No fue posible actualizar la designación.');
        }

        abort_if($actual === null, 404);

        if (strtoupper(trim((string) ($actual['estado'] ?? ''))) === 'APROBADO') {
            return back()->with('error', 'La designación aprobada no se puede modificar.');
        }

        try {
            $fecha = trim((string) ($data['fecha'] ?? ''));
            $this->designaciones->actualizar(
                $id,
                $carrera->sigla,
                $fecha !== '' ? $fecha : (string) ($actual['fecha'] ?? ''),
                $actual['gestion'],
                $actual['periodo'],
                $data['obs'] ?? '',
            );
        } catch (Throwable $exception) {
            Log::warning('Actualizacion de designacion no disponible.', ['exception' => $exception::class]);

            return back()->with('error', 'No fue posible actualizar la designación.');
        }

        return back()->with('success', 'Designación actualizada correctamente.');
    }

    public function actualizarDetalle(Request $request, int $id): RedirectResponse
    {
        abort_unless($request->user()?->esDirectorCarrera(), 403);

        $carrera = $this->carreraAutorizada($request);

        try {
            $actual = $this->designaciones->listar($carrera->sigla, '0', '0')
                ->firstWhere('id', $id);
        } catch (Throwable $exception) {
            Log::warning('Actualizacion de detalle no disponible.', ['exception' => $exception::class]);

            return back()->with('error', 'No fue posible actualizar la asignación.');
        }

        abort_if($actual === null, 404);

        if (strtoupper(trim((string) ($actual['estado'] ?? ''))) === 'APROBADO') {
            return back()->with('error', 'La designación aprobada no se puede modificar.');
        }

        $data = $request->validate([
            'id_detalle' => ['required', 'integer', 'min:0'],
            'id_docente' => ['required', 'integer', 'min:1'],
            'id_materia' => ['required', 'integer', 'min:1'],
            'id_grupo' => ['required', 'integer', 'min:1'],
            'hrs_teoria' => ['required', 'integer', 'min:0'],
            'hrs_practica' => ['required', 'integer', 'min:0'],
            'hrs_laboratorio' => ['required', 'integer', 'min:0'],
        ]);

        try {
            $this->designaciones->guardarDetalle(
                $id,
                $carrera->sigla,
                (int) $data['id_detalle'],
                (int) $data['id_docente'],
                (int) $data['id_materia'],
                (int) $data['id_grupo'],
                (int) $data['hrs_teoria'],
                (int) $data['hrs_practica'],
                (int) $data['hrs_laboratorio'],
            );
        } catch (Throwable $exception) {
            Log::warning('Actualizacion de detalle no disponible.', ['exception' => $exception::class]);

            return back()->with('error', 'No fue posible actualizar la asignación.');
        }

        return back()->with('success', 'Asignación actualizada correctamente.');
    }

    /**
     * @param  Collection<int, array<string, int|string|null>>  $filas
     * @return Collection<int, array<string, int|string|null>>
     */
    private function materiasDisponibles(Collection $filas): Collection
    {
        return $filas
            ->filter(fn (array $fila): bool => $fila['materia_id'] !== null)
            ->unique('materia_id')
            ->values()
            ->map(fn (array $fila): array => [
                'id' => $fila['materia_id'],
                'sigla' => $fila['materia_sigla'],
                'nombre' => $fila['materia_nombre'],
            ]);
    }

    /**
     * Conserva en el modal las materias ya asignadas que no aparezcan en la
     * oferta vigente, pero solo como opciones de edicion de su propia fila.
     *
     * @param  Collection<int, array<string, mixed>>  $materiasOferta
     * @param  Collection<int, array<string, mixed>>  $filas
     * @return Collection<int, array<string, mixed>>
     */
    private function materiasOfertaParaEdicion(Collection $materiasOferta, Collection $filas): Collection
    {
        $idsOferta = $materiasOferta
            ->pluck('id')
            ->map(fn (mixed $id): string => (string) $id)
            ->all();

        $materiasActuales = $filas
            ->filter(fn (array $fila): bool => ! empty($fila['materia_id'])
                && ! in_array((string) $fila['materia_id'], $idsOferta, true))
            ->unique('materia_id')
            ->map(function (array $fila) use ($filas): array {
                $materiaId = (int) $fila['materia_id'];
                $grupos = $filas
                    ->filter(fn (array $actual): bool => (int) ($actual['materia_id'] ?? 0) === $materiaId)
                    ->pluck('grupo_id')
                    ->filter(fn (mixed $grupo): bool => $grupo !== null)
                    ->map(fn (mixed $grupo): int => (int) $grupo)
                    ->unique()
                    ->sort()
                    ->values()
                    ->all();

                return [
                    'id' => $materiaId,
                    'sigla' => $fila['materia_sigla'],
                    'nombre' => $fila['materia_nombre'],
                    'nivel_academico' => null,
                    'mencion_id' => null,
                    'horas_teoricas' => (int) ($fila['horas_teoricas'] ?? 0),
                    'horas_practicas' => (int) ($fila['horas_practicas'] ?? 0),
                    'horas_laboratorio' => (int) ($fila['horas_laboratorio'] ?? 0),
                    'grupos' => $grupos,
                    'grupo_siguiente' => null,
                    'grupos_disponibles' => false,
                    'solo_edicion' => true,
                ];
            })
            ->values();

        return $materiasOferta->concat($materiasActuales)->values();
    }

    private function carreraAutorizada(Request $request): object
    {
        abort_unless($request->user()?->esDirectorCarrera(), 403);

        $carrera = $request->user()?->carrera;

        if (! $carrera?->sigla) {
            abort(403);
        }

        return $carrera;
    }

    private function facultadAutorizada(Request $request): int
    {
        $user = $request->user();
        $facultadId = (int) ($user?->facultad_id ?? 0);

        abort_unless($user?->esDecanatura() && $facultadId > 0, 403);

        return $facultadId;
    }

    private function carreraFacultad(): object
    {
        return (object) ['nombre' => 'Facultad', 'sigla' => ''];
    }

    /** @param array<string, mixed> $asignacion */
    private function carreraDeDesignacion(array $asignacion): object
    {
        return (object) [
            'nombre' => (string) ($asignacion['programa_nombre'] ?? ''),
            'sigla' => (string) ($asignacion['programa_codigo'] ?? ''),
        ];
    }
}
