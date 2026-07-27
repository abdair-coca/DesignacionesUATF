@extends('layouts.app')

@section('title', 'Designaciones por Carrera')

@section('content')
<div class="space-y-4">
    <!-- Encabezado del Módulo -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <h1 class="text-xl font-bold tracking-tight text-gray-900">Designaciones por Carrera</h1>
            <p class="text-xs text-gray-500 mt-0.5">Selecciona una carrera para gestionar las designaciones de sus docentes</p>
        </div>

        <form method="GET" action="{{ route('designaciones.index') }}" class="flex items-center gap-2 bg-white p-2 rounded-lg border border-gray-200 shadow-sm">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider pl-1">Periodo:</span>
            <select name="gestion_id" onchange="this.form.submit()" class="text-xs font-medium border-gray-200 rounded px-2 py-1 bg-gray-50 text-gray-800 focus:ring-1 focus:ring-[#00acac] outline-none">
                @foreach($gestiones as $g)
                    <option value="{{ $g->id }}" {{ (string)$g->id === (string)$filtros['gestion_id'] ? 'selected' : '' }}>Gestión {{ $g->nombre }}</option>
                @endforeach
            </select>

            <select name="periodo_id" onchange="this.form.submit()" class="text-xs font-medium border-gray-200 rounded px-2 py-1 bg-gray-50 text-gray-800 focus:ring-1 focus:ring-[#00acac] outline-none">
                @foreach($periodos as $p)
                    <option value="{{ $p->id }}" {{ (string)$p->id === (string)$filtros['periodo_id'] ? 'selected' : '' }}>Periodo {{ $p->nombre }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Contenedor Principal DataTable - Select (Color Admin v2 Style) -->
    <div class="rounded-lg border border-gray-200/80 bg-white shadow-md overflow-hidden">
        <!-- Panel Header Color Admin style -->
        <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between font-semibold text-xs border-b border-gray-800">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[#00acac]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4" />
                </svg>
                <span>DataTable - Carreras de la Universidad</span>
            </div>
        </div>

        <!-- Tabla estilo Color Admin DataTable -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#f2f4f6] border-b border-gray-200 text-gray-800 font-bold uppercase text-[11px] tracking-wider">
                        <th class="py-3 px-4 border-r border-gray-200/60">Sigla</th>
                        <th class="py-3 px-4 border-r border-gray-200/60">Carrera</th>
                        <th class="py-3 px-4 border-r border-gray-200/60 text-center">Materias</th>
                        <th class="py-3 px-4 border-r border-gray-200/60 text-center">Grupos</th>
                        <th class="py-3 px-4 text-center border-r border-gray-200/60">Situación</th>
                        <th class="py-3 px-4 text-center w-36">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200/70 text-gray-700 font-medium">
                    @foreach($carreras as $c)
                    <tr class="hover:bg-gray-50/80 transition-colors">
                        <td class="py-3 px-4 font-bold text-gray-900 border-r border-gray-200/40">
                            <span class="bg-[#2d353c] text-white text-[10px] font-bold px-2 py-0.5 rounded shadow-xs">{{ $c['sigla'] }}</span>
                        </td>
                        <td class="py-3 px-4 font-bold text-gray-900 border-r border-gray-200/40">{{ $c['nombre'] }}</td>
                        <td class="py-3 px-4 text-center font-semibold text-gray-700 border-r border-gray-200/40 tabular-nums">{{ $c['materias_total'] ?? 0 }}</td>
                        <td class="py-3 px-4 text-center font-semibold text-gray-700 border-r border-gray-200/40 tabular-nums">{{ $c['grupos_total'] ?? 0 }}</td>
                        <td class="py-3 px-4 text-center border-r border-gray-200/40">
                            @if(($c['situacion'] ?? '') === 'activas')
                                <span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded-full text-[10px] font-bold">Designaciones activas</span>
                            @elseif(($c['situacion'] ?? '') === 'pendientes')
                                <span class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded-full text-[10px] font-bold">Con pendientes</span>
                            @else
                                <span class="bg-gray-100 text-gray-700 border border-gray-200 px-2 py-0.5 rounded-full text-[10px] font-bold">Sin designaciones</span>
                            @endif
                        </td>
                        <td class="py-3 px-4 text-center">
                            <a href="{{ route('designaciones.carrera', $c['id']) }}" 
                               class="bg-[#00acac] hover:bg-[#008a8a] text-white text-[11px] font-bold px-3 py-1.5 rounded shadow-sm transition-all duration-150 inline-flex items-center gap-1">
                                <span>Ver Docentes</span>
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                                </svg>
                            </a>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
