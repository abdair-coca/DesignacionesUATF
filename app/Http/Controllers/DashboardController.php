<?php

namespace App\Http\Controllers;

use App\Models\Gestion;
use App\Models\Periodo;
use App\Models\Revision;
use App\Support\DesignacionReportService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(private DesignacionReportService $reportes) {}

    public function index(Request $request): Response
    {
        $filtros = $request->validate([
            'gestion_id' => ['nullable', 'exists:gestiones,id'],
            'periodo_id' => ['nullable', 'exists:periodos,id'],
        ]);

        $gestionId = (int) ($filtros['gestion_id'] ?? Gestion::max('id') ?? 0);
        $periodoId = (int) ($filtros['periodo_id'] ?? Periodo::min('id') ?? 0);

        $datos = $this->reportes->datosDashboard($gestionId, $periodoId);
        $resumenCarreras = $this->reportes->resumenPorCarrera($gestionId, $periodoId);
        $evolucion = $this->reportes->evolucionDesignaciones($gestionId, $periodoId);

        $revisionesPendientes = Revision::where('estado', 'pendiente')->count();

        return Inertia::render('Dashboard/Index', array_merge([
            'gestiones' => Gestion::orderBy('nombre')->get(),
            'periodos' => Periodo::orderBy('nombre')->get(),
            'filtros' => [
                'gestion_id' => (string) $gestionId,
                'periodo_id' => (string) $periodoId,
            ],
            'resumenCarreras' => $resumenCarreras,
            'evolucion' => $evolucion,
            'is_admin' => $request->user()->is_admin,
            'revisionesPendientes' => $revisionesPendientes,
        ], $datos));
    }
}
