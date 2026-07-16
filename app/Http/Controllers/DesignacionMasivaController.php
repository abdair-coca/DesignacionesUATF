<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Periodo;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Inertia\Inertia;
use Inertia\Response;

class DesignacionMasivaController extends Controller
{
    public function copiarForm(Request $request): Response
    {
        $raw = $request->only([
            'carrera_id', 'gestion_origen_id', 'periodo_origen_id',
            'gestion_destino_id', 'periodo_destino_id',
        ]);

        $input = array_map(fn ($v) => $v === '' ? null : $v, $raw);

        $filtros = Validator::make($input, [
            'carrera_id' => ['nullable', 'exists:carreras,id'],
            'gestion_origen_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_origen_id' => ['nullable', 'exists:periodos,id'],
            'gestion_destino_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_destino_id' => ['nullable', 'exists:periodos,id'],
        ])->validate();

        // Asegurar que todas las claves existan (nullable no las incluye si no vienen)
        $filtros = array_merge([
            'carrera_id' => null,
            'gestion_origen_id' => null,
            'periodo_origen_id' => null,
            'gestion_destino_id' => null,
            'periodo_destino_id' => null,
        ], $filtros);

        $filas = null;

        $origenCompleto = collect($filtros)->only([
            'gestion_origen_id', 'periodo_origen_id',
        ])->every(fn ($v) => ! empty($v));

        $destinoCompleto = collect($filtros)->only([
            'gestion_destino_id', 'periodo_destino_id',
        ])->every(fn ($v) => ! empty($v));

        if ($origenCompleto && $destinoCompleto) {
            $query = Designacion::with(['docente', 'materia', 'grupo'])
                ->where('Id_gestion', $filtros['gestion_origen_id'])
                ->where('Id_periodo', $filtros['periodo_origen_id'])
                ->where('estado', '!=', 'rechazada')
                ->whereDoesntHave('grupo.designaciones', function ($q) use ($filtros) {
                    $q->where('Id_gestion', $filtros['gestion_destino_id'])
                        ->where('Id_periodo', $filtros['periodo_destino_id'])
                        ->where('estado', '!=', 'rechazada');
                });

            if (! empty($filtros['carrera_id'])) {
                $query->whereHas('materia', fn ($q) => $q->where('carrera_id', $filtros['carrera_id']));
            }

            $filas = $query->orderBy('Id_materia')->get();
        }

        return Inertia::render('Designaciones/Copiar', [
            'carreras' => Carrera::orderBy('nombre')->get(),
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'docentes' => Docente::orderBy('nombre')->get(),
            'filtros' => $filtros,
            'filas' => $filas,
        ]);
    }

    public function copiarStore(Request $request): RedirectResponse
    {
        $input = array_map(fn ($v) => $v === '' ? null : $v, $request->all());

        $data = Validator::make($input, [
            'Id_gestion' => ['required', 'exists:gestiones,id'],
            'Id_periodo' => ['required', 'exists:periodos,id'],
            'gestion_origen_id' => ['required', 'exists:gestiones,id'],
            'periodo_origen_id' => ['required', 'exists:periodos,id'],
            'carrera_id' => ['nullable', 'exists:carreras,id'],
            'filas' => ['nullable', 'array'],
            'filas.*.incluir' => ['required', 'boolean'],
            'filas.*.Id_materia' => ['required', 'exists:materias,id'],
            'filas.*.Id_grupo' => ['required', 'exists:grupos,id'],
            'filas.*.Id_docente' => ['required', 'exists:docentes,id'],
        ])->validate();

        if (! empty($data['filas'])) {
            // Copiar selección individual
            $creadas = $this->crearEnLote($data, $request, fn (array $fila) => $fila['incluir']);
        } else {
            // Copiar todas las designaciones del origen
            $creadas = $this->copiarTodas($data, $request);
        }

        return redirect()->route('designaciones.copiar', [
            'carrera_id' => $data['carrera_id'] ?? '',
            'gestion_origen_id' => $data['gestion_origen_id'],
            'periodo_origen_id' => $data['periodo_origen_id'],
            'gestion_destino_id' => $data['Id_gestion'],
            'periodo_destino_id' => $data['Id_periodo'],
        ])->with('status', "{$creadas} designación(es) copiada(s) correctamente.");
    }

    private function crearEnLote(array $data, Request $request, \Closure $debeIncluir): int
    {
        $ahora = now();
        $userId = $request->user()->id;
        $registros = [];

        foreach ($data['filas'] as $fila) {
            if (! $debeIncluir($fila)) {
                continue;
            }

            $registros[] = [
                'Id_docente' => $fila['Id_docente'],
                'Id_materia' => $fila['Id_materia'],
                'Id_grupo' => $fila['Id_grupo'],
                'Id_gestion' => $data['Id_gestion'],
                'Id_periodo' => $data['Id_periodo'],
                'estado' => 'propuesta',
                'creado_por' => $userId,
                'created_at' => $ahora,
                'updated_at' => $ahora,
            ];
        }

        if (empty($registros)) {
            return 0;
        }

        // Excluir grupos que ya tienen designación activa en destino
        $gruposDestino = Designacion::where('Id_gestion', $data['Id_gestion'])
            ->where('Id_periodo', $data['Id_periodo'])
            ->where('estado', '!=', 'rechazada')
            ->pluck('Id_grupo')
            ->toArray();

        $registros = array_values(array_filter($registros, fn ($r) => ! in_array($r['Id_grupo'], $gruposDestino)));

        if (empty($registros)) {
            return 0;
        }

        Designacion::insert($registros);

        return count($registros);
    }

    private function copiarTodas(array $data, Request $request): int
    {
        $query = Designacion::query()
            ->where('Id_gestion', $data['gestion_origen_id'])
            ->where('Id_periodo', $data['periodo_origen_id'])
            ->where('estado', '!=', 'rechazada')
            ->whereDoesntHave('grupo.designaciones', function ($q) use ($data) {
                $q->where('Id_gestion', $data['Id_gestion'])
                    ->where('Id_periodo', $data['Id_periodo'])
                    ->where('estado', '!=', 'rechazada');
            });

        if (! empty($data['carrera_id'])) {
            $query->whereHas('materia', fn ($q) => $q->where('carrera_id', $data['carrera_id']));
        }

        $origen = $query->get(['Id_docente', 'Id_materia', 'Id_grupo']);

        if ($origen->isEmpty()) {
            return 0;
        }

        $ahora = now();
        $userId = $request->user()->id;

        $registros = $origen->map(fn ($fila) => [
            'Id_docente' => $fila->Id_docente,
            'Id_materia' => $fila->Id_materia,
            'Id_grupo' => $fila->Id_grupo,
            'Id_gestion' => $data['Id_gestion'],
            'Id_periodo' => $data['Id_periodo'],
            'estado' => 'propuesta',
            'creado_por' => $userId,
            'created_at' => $ahora,
            'updated_at' => $ahora,
        ])->toArray();

        Designacion::insert($registros);

        return count($registros);
    }
}
