@extends('layouts.app')

@section('title', 'Dashboard General — UATF Designación Docente')

@section('content')
<div class="space-y-6">
    <!-- Header de Bienvenida & Filtros Rápidos Académicos -->
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 bg-white p-5 rounded-lg border border-gray-200 shadow-sm">
        <div>
            <div class="flex items-center gap-2">
                <h1 class="text-xl font-bold text-gray-900 tracking-tight">
                    Panel Principal de Designaciones
                </h1>
                <span class="bg-[#00acac] text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-sm uppercase tracking-wider">
                    {{ Auth::user()?->is_admin ? 'Modo Administrador' : 'Director de Carrera' }}
                </span>
            </div>
            <p class="text-xs text-gray-500 font-medium mt-1">
                Bienvenido, <strong class="text-gray-800">{{ Auth::user()?->name }}</strong>. Revisa las métricas globales y el estado de cobertura docente.
            </p>
        </div>

        <!-- Filtros de Gestión y Periodo Académico -->
        <form method="GET" action="{{ route('dashboard') }}" class="flex items-center gap-3">
            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Gestión</label>
                <select name="gestion_id" onchange="this.form.submit()" 
                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold bg-white text-gray-800 focus:ring-2 focus:ring-[#00acac] focus:outline-none">
                    @foreach($gestiones as $g)
                        <option value="{{ $g->id }}" {{ (string)$g->id === (string)$filtros['gestion_id'] ? 'selected' : '' }}>
                            Gestión {{ $g->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-0.5">Periodo</label>
                <select name="periodo_id" onchange="this.form.submit()" 
                        class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs font-semibold bg-white text-gray-800 focus:ring-2 focus:ring-[#00acac] focus:outline-none">
                    @foreach($periodos as $p)
                        <option value="{{ $p->id }}" {{ (string)$p->id === (string)$filtros['periodo_id'] ? 'selected' : '' }}>
                            Periodo {{ $p->nombre }}
                        </option>
                    @endforeach
                </select>
            </div>
        </form>
    </div>

    <!-- FILA 1: 4 STAT TILES estilo Color Admin v2 (con barra de progreso inferior) -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-5">
        <!-- Tile 1: Total Docentes (Turquesa #00acac) -->
        <div class="bg-[#00acac] rounded-lg p-5 text-white shadow-md relative overflow-hidden flex flex-col justify-between min-h-[120px]">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider opacity-90">Total Docentes</span>
                    <svg class="w-8 h-8 opacity-30 absolute right-4 top-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M12 12c2.21 0 4-1.79 4-4s-1.79-4-4-4-4 1.79-4 4 1.79 4 4 4zm0 2c-2.67 0-8 1.34-8 4v2h16v-2c0-2.66-5.33-4-8-4z"/>
                    </svg>
                </div>
                <div class="text-3xl font-black tabular-nums mt-2">
                    {{ number_format($totalDocentes ?? 0) }}
                </div>
            </div>
            
            <div class="mt-4">
                <!-- Barra de progreso inferior -->
                <div class="w-full bg-black/20 rounded-full h-1.5 overflow-hidden mb-1.5">
                    <div class="bg-white h-1.5 rounded-full" style="width: 85%;"></div>
                </div>
                <div class="flex justify-between items-center text-[10px] font-medium text-white/90">
                    <span>Docentes registrados</span>
                    <span>85% activos</span>
                </div>
            </div>
        </div>

        <!-- Tile 2: Cobertura Académica (Azul #348fe2) -->
        <div class="bg-[#348fe2] rounded-lg p-5 text-white shadow-md relative overflow-hidden flex flex-col justify-between min-h-[120px]">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider opacity-90">Cobertura Académica</span>
                    <svg class="w-8 h-8 opacity-30 absolute right-4 top-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-5 14H7v-2h7v2zm3-4H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                </div>
                <div class="text-3xl font-black tabular-nums mt-2">
                    {{ $porcentajeCobertura ?? 0 }}%
                </div>
            </div>

            <div class="mt-4">
                <!-- Barra de progreso inferior -->
                <div class="w-full bg-black/20 rounded-full h-1.5 overflow-hidden mb-1.5">
                    <div class="bg-white h-1.5 rounded-full" style="width: {{ $porcentajeCobertura ?? 0 }}%;"></div>
                </div>
                <div class="flex justify-between items-center text-[10px] font-medium text-white/90">
                    <span>{{ $designacionesActivas ?? 0 }} / {{ $totalGruposHabilitados ?? 0 }} grupos</span>
                    <span>Meta: 100%</span>
                </div>
            </div>
        </div>

        <!-- Tile 3: Designaciones Aprobadas (Indigo #727cb6) -->
        <div class="bg-[#727cb6] rounded-lg p-5 text-white shadow-md relative overflow-hidden flex flex-col justify-between min-h-[120px]">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider opacity-90">Aprobadas por Vicedecanato</span>
                    <svg class="w-8 h-8 opacity-30 absolute right-4 top-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M9 16.17L4.83 12l-1.42 1.41L9 19 21 7l-1.41-1.41z"/>
                    </svg>
                </div>
                <div class="text-3xl font-black tabular-nums mt-2">
                    {{ number_format($conteoEstado['aprobada'] ?? 0) }}
                </div>
            </div>

            <div class="mt-4">
                <!-- Barra de progreso inferior -->
                <div class="w-full bg-black/20 rounded-full h-1.5 overflow-hidden mb-1.5">
                    <div class="bg-white h-1.5 rounded-full" style="width: 70%;"></div>
                </div>
                <div class="flex justify-between items-center text-[10px] font-medium text-white/90">
                    <span>Finalizadas formalmente</span>
                    <span>70% completadas</span>
                </div>
            </div>
        </div>

        <!-- Tile 4: Solicitudes en Inbox (Naranja #f59c1a) -->
        <div class="bg-[#f59c1a] rounded-lg p-5 text-white shadow-md relative overflow-hidden flex flex-col justify-between min-h-[120px]">
            <div>
                <div class="flex items-center justify-between">
                    <span class="text-[11px] font-bold uppercase tracking-wider opacity-90">Solicitudes Pendientes</span>
                    <svg class="w-8 h-8 opacity-30 absolute right-4 top-4" fill="currentColor" viewBox="0 0 24 24">
                        <path d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
                    </svg>
                </div>
                <div class="text-3xl font-black tabular-nums mt-2">
                    {{ $revisionesPendientes ?? 0 }}
                </div>
            </div>

            <div class="mt-4">
                <!-- Barra de progreso inferior -->
                <div class="w-full bg-black/20 rounded-full h-1.5 overflow-hidden mb-1.5">
                    <div class="bg-white h-1.5 rounded-full" style="width: {{ ($revisionesPendientes ?? 0) > 0 ? 40 : 100 }}%;"></div>
                </div>
                <div class="flex justify-between items-center text-[10px] font-medium text-white/90">
                    <span>En bandeja de revisión</span>
                    @if(Auth::user()?->is_admin)
                        <a href="{{ route('revisiones.pendientes') }}" class="underline font-bold hover:text-white">Ver Inbox &rarr;</a>
                    @else
                        <span>Actualizado</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <!-- FILA 2: GRÁFICOS ANALÍTICOS (Estilo Color Admin Analytics Widget) -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Gráfico 1: Evolución de Designaciones (Líneas) -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <!-- Header Widget estilo Color Admin -->
            <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between text-xs font-bold border-b border-gray-800">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#00acac]"></span>
                    <span>Evolución de Cobertura y Designaciones por Periodo</span>
                </div>
                <!-- Window Controls estilo Color Admin -->
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 cursor-pointer"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 cursor-pointer"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 cursor-pointer"></span>
                </div>
            </div>

            <!-- Contenido del Gráfico -->
            <div class="p-5 flex-1 flex flex-col justify-center">
                <canvas id="chartEvolucion" class="w-full max-h-[260px]"></canvas>
            </div>
        </div>

        <!-- Gráfico 2: Distribución de Carga Horaria Docente (Donut Chart) -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <!-- Header Widget estilo Color Admin -->
            <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between text-xs font-bold border-b border-gray-800">
                <div class="flex items-center gap-2">
                    <span class="w-2 h-2 rounded-full bg-[#348fe2]"></span>
                    <span>Distribución Carga Docente</span>
                </div>
                <div class="flex items-center gap-1.5">
                    <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 cursor-pointer"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-amber-500 cursor-pointer"></span>
                    <span class="w-2.5 h-2.5 rounded-full bg-rose-500 cursor-pointer"></span>
                </div>
            </div>

            <!-- Contenido del Gráfico Donut -->
            <div class="p-5 flex-1 flex flex-col items-center justify-center">
                <canvas id="chartDonutCarga" class="max-h-[220px]"></canvas>
            </div>
        </div>
    </div>

    <!-- FILA 3: WIDGET MENSAJES DE ACTIVIDAD + TABLA DE RESUMEN POR CARRERA -->
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Widget 1: Actividad Reciente estilo Color Admin Message -->
        <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between text-xs font-bold border-b border-gray-800">
                <span>Actividad de Solicitudes</span>
                <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">RECIENTE</span>
            </div>

            <div class="p-4 space-y-4 flex-1 overflow-y-auto max-h-[320px]">
                <div class="flex items-start gap-3 text-xs pb-3 border-b border-gray-100">
                    <div class="h-8 w-8 rounded-full bg-[#00acac] text-white font-black text-xs flex items-center justify-center shrink-0">
                        I
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900">Director Ingeniería Informática</h4>
                        <p class="text-gray-600 mt-0.5">Envió propuesta de designaciones para el Periodo 2.</p>
                        <span class="text-[10px] text-gray-400 font-medium">Hace 25 min</span>
                    </div>
                </div>

                <div class="flex items-start gap-3 text-xs pb-3 border-b border-gray-100">
                    <div class="h-8 w-8 rounded-full bg-[#348fe2] text-white font-black text-xs flex items-center justify-center shrink-0">
                        C
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900">Director Ingeniería Civil</h4>
                        <p class="text-gray-600 mt-0.5">Asignó 14 materias y completó la carga horaria del roster.</p>
                        <span class="text-[10px] text-gray-400 font-medium">Hace 2 horas</span>
                    </div>
                </div>

                <div class="flex items-start gap-3 text-xs">
                    <div class="h-8 w-8 rounded-full bg-[#727cb6] text-white font-black text-xs flex items-center justify-center shrink-0">
                        A
                    </div>
                    <div>
                        <h4 class="font-bold text-gray-900">Vicedecanato UATF</h4>
                        <p class="text-gray-600 mt-0.5">Aprobó la propuesta final de Medicina Veterinaria.</p>
                        <span class="text-[10px] text-gray-400 font-medium">Ayer a las 16:30</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Widget 2: DataTable de Estado por Carrera estilo Color Admin -->
        <div class="lg:col-span-2 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
            <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between text-xs font-bold border-b border-gray-800">
                <span>Estado de Cobertura por Carrera</span>
                @if(Auth::user()?->is_admin)
                    <a href="{{ route('designaciones.index') }}" class="text-[#00acac] hover:underline">Ver todas &rarr;</a>
                @endif
            </div>

            <div class="overflow-x-auto flex-1">
                <table class="w-full text-left text-xs font-medium text-gray-700 divide-y divide-gray-200">
                    <thead class="bg-[#f2f4f6] text-gray-800 font-bold uppercase text-[11px] tracking-wider">
                        <tr>
                            <th class="px-4 py-3">Carrera</th>
                            <th class="px-4 py-3 text-center">Grupos Habilitados</th>
                            <th class="px-4 py-3 text-center">Designados</th>
                            <th class="px-4 py-3 text-center">Situación</th>
                            <th class="px-4 py-3 text-right">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100 bg-white">
                        @foreach($resumenCarreras->take(5) as $c)
                            <tr class="hover:bg-[#fff9d6]/40 transition-colors">
                                <td class="px-4 py-3">
                                    <span class="font-bold text-gray-900">{{ $c['nombre'] }}</span>
                                    <span class="text-gray-400 text-[11px] ml-1">({{ $c['sigla'] }})</span>
                                </td>
                                <td class="px-4 py-3 text-center font-bold tabular-nums text-gray-800">
                                    {{ $c['grupos'] }}
                                </td>
                                <td class="px-4 py-3 text-center font-bold tabular-nums text-gray-800">
                                    {{ $c['activas'] }}
                                </td>
                                <td class="px-4 py-3 text-center">
                                    @if($c['situacion'] === 'activas')
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            Al día
                                        </span>
                                    @elseif($c['situacion'] === 'pendientes')
                                        <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            Pendiente
                                        </span>
                                    @else
                                        <span class="bg-gray-100 text-gray-600 border border-gray-300 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            Sin propuesta
                                        </span>
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-right">
                                    <a href="{{ route('designaciones.carrera', $c['id']) }}" 
                                       class="px-2.5 py-1 bg-[#2d353c] hover:bg-[#20252a] text-white font-bold rounded text-[11px] transition-colors">
                                        Designar &rarr;
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<!-- CDN Chart.js para Gráficos -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // 1. Gráfico de Líneas: Evolución de Cobertura
        const ctxEvolucion = document.getElementById('chartEvolucion').getContext('2d');
        new Chart(ctxEvolucion, {
            type: 'line',
            data: {
                labels: ['Gestión 2024-1', 'Gestión 2024-2', 'Gestión 2025-1', 'Gestión 2025-2', 'Gestión 2026-1', 'Gestión 2026-2'],
                datasets: [{
                    label: 'Grupos Designados',
                    data: [45, 58, 62, 75, 82, {{ $designacionesActivas ?? 85 }}],
                    borderColor: '#00acac',
                    backgroundColor: 'rgba(0, 172, 172, 0.1)',
                    fill: true,
                    tension: 0.3,
                    borderWidth: 2,
                    pointRadius: 4,
                    pointBackgroundColor: '#00acac'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: { beginAtZero: true, grid: { borderDash: [2, 2] } },
                    x: { grid: { display: false } }
                }
            }
        });

        // 2. Gráfico Donut: Distribución Carga Docente
        const ctxDonut = document.getElementById('chartDonutCarga').getContext('2d');
        new Chart(ctxDonut, {
            type: 'doughnut',
            data: {
                labels: ['Carga Óptima (6h-32h)', 'Bajo Mínimo (<6h)', 'Sobrecarga (>32h)', 'Sin Asignación'],
                datasets: [{
                    data: [65, 15, 5, 15],
                    backgroundColor: ['#00acac', '#f59c1a', '#ff5b57', '#e2e8f0'],
                    borderWidth: 0
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { position: 'bottom', labels: { boxWidth: 12, font: { size: 10 } } }
                },
                cutout: '70%'
            }
        });
    });
</script>
@endpush
@endsection
