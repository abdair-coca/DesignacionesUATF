<?php

namespace App\Http\Controllers;

use App\Models\Carrera;
use App\Models\Designacion;
use App\Models\Docente;
use App\Models\Gestion;
use App\Models\Periodo;
use App\Support\CargaAcademicaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class DesignacionMasivaController extends Controller
{
    public function __construct(private CargaAcademicaService $cargaAcademica) {}

    public function copiarForm(Request $request): Response
    {
        $filtros = $request->validate([
            'carrera_id' => ['nullable', 'exists:carreras,id'],
            'gestion_origen_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_origen_id' => ['nullable', 'exists:periodos,id'],
            'gestion_destino_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_destino_id' => ['nullable', 'exists:periodos,id'],
        ]);

        $filas = null;

        $completo = collect($filtros)->only([
            'carrera_id', 'gestion_origen_id', 'periodo_origen_id', 'gestion_destino_id', 'periodo_destino_id',
        ])->every(fn ($v) => ! empty($v));

        if ($completo) {
            $filas = Designacion::with(['docente', 'materia', 'grupo'])
                ->where('Id_gestion', $filtros['gestion_origen_id'])
                ->where('Id_periodo', $filtros['periodo_origen_id'])
                ->where('estado', '!=', 'rechazada')
                ->whereHas('materia', fn ($q) => $q->where('carrera_id', $filtros['carrera_id']))
                ->whereDoesntHave('grupo.designaciones', function ($q) use ($filtros) {
                    $q->where('Id_gestion', $filtros['gestion_destino_id'])
                        ->where('Id_periodo', $filtros['periodo_destino_id'])
                        ->where('estado', '!=', 'rechazada');
                })
                ->orderBy('Id_materia')
                ->get();
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
        $data = $request->validate([
            'Id_gestion' => ['required', 'exists:gestiones,id'],
            'Id_periodo' => ['required', 'exists:periodos,id'],
            'carrera_id' => ['required', 'exists:carreras,id'],
            'gestion_origen_id' => ['required', 'exists:gestiones,id'],
            'periodo_origen_id' => ['required', 'exists:periodos,id'],
            'filas' => ['required', 'array'],
            'filas.*.incluir' => ['required', 'boolean'],
            'filas.*.Id_materia' => ['required', 'exists:materias,id'],
            'filas.*.Id_grupo' => ['required', 'exists:grupos,id'],
            'filas.*.Id_docente' => ['required', 'exists:docentes,id'],
        ]);

        $creadas = $this->crearEnLote($data, $request, fn (array $fila) => $fila['incluir']);

        return redirect()->route('designaciones.copiar', [
            'carrera_id' => $data['carrera_id'],
            'gestion_origen_id' => $data['gestion_origen_id'],
            'periodo_origen_id' => $data['periodo_origen_id'],
            'gestion_destino_id' => $data['Id_gestion'],
            'periodo_destino_id' => $data['Id_periodo'],
        ])->with('status', "{$creadas} designación(es) copiada(s) correctamente.");
    }

    private function crearEnLote(array $data, Request $request, \Closure $debeIncluir): int
    {
        $creadas = 0;

        DB::transaction(function () use ($data, $request, $debeIncluir, &$creadas) {
            foreach ($data['filas'] as $fila) {
                if (! $debeIncluir($fila)) {
                    continue;
                }

                if ($this->cargaAcademica->hayChoque($fila['Id_grupo'], $data['Id_gestion'], $data['Id_periodo'])) {
                    continue;
                }

                Designacion::create([
                    'Id_docente' => $fila['Id_docente'],
                    'Id_materia' => $fila['Id_materia'],
                    'Id_grupo' => $fila['Id_grupo'],
                    'Id_gestion' => $data['Id_gestion'],
                    'Id_periodo' => $data['Id_periodo'],
                    'estado' => 'propuesta',
                    'creado_por' => $request->user()->id,
                ]);

                $creadas++;
            }
        });

        return $creadas;
    }
}
