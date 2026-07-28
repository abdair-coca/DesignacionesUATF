@extends('layouts.app')

@section('title', 'Lista de Designaciones por Carrera — UATF')

@section('content')
@php
    $gestionId = $filtros['gestion_id'] ?: ($gestiones->max('id') ?? 1);
    $periodoId = $filtros['periodo_id'] ?: 1;
    $gestionActualNombre = $gestiones->firstWhere('id', $gestionId)?->nombre ?? date('Y');
    $periodoActualNombre = $periodos->firstWhere('id', $periodoId)?->nombre ?? '1';
@endphp

<div class="space-y-5 text-xs text-gray-800">
    <!-- Header del Módulo -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-lg border border-gray-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="bg-[#2d353c] text-white text-xs font-bold px-2 py-0.5 rounded">Designaciones</span>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">Lista de Designaciones por Carrera</h1>
            </div>
            <p class="text-xs text-gray-500 mt-0.5">Selecciona una carrera para gestionar y asignar la carga horaria de los docentes.</p>
        </div>

        <!-- Filtro de Gestión y Periodo -->
        <form method="GET" action="{{ route('designaciones.lista') }}" class="flex items-center gap-2 bg-gray-50 p-2 rounded-lg border border-gray-200">
            <span class="text-xs font-semibold text-gray-500 uppercase tracking-wider pl-1">Periodo:</span>
            <select name="gestion_id" onchange="this.form.submit()" class="text-xs font-medium border-gray-300 rounded px-2.5 py-1 bg-white text-gray-800 focus:ring-1 focus:ring-[#00acac] outline-none shadow-2xs">
                @foreach($gestiones as $g)
                    <option value="{{ $g->id }}" {{ (string)$g->id === (string)$gestionId ? 'selected' : '' }}>Gestión {{ $g->nombre }}</option>
                @endforeach
            </select>

            <select name="periodo_id" onchange="this.form.submit()" class="text-xs font-medium border-gray-300 rounded px-2.5 py-1 bg-white text-gray-800 focus:ring-1 focus:ring-[#00acac] outline-none shadow-2xs">
                @foreach($periodos as $p)
                    <option value="{{ $p->id }}" {{ (string)$p->id === (string)$periodoId ? 'selected' : '' }}>Periodo {{ $p->nombre }}</option>
                @endforeach
            </select>
        </form>
    </div>

    <!-- Grid de Tarjetas por Carrera (Estilo Color Admin Plano) -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        @foreach($carreras as $c)
            @php
                $esMiCarrera = (Auth::user()?->carrera_id && (int)Auth::user()->carrera_id === (int)$c->id);
            @endphp
            <div class="bg-white rounded-lg border {{ $esMiCarrera ? 'border-[#00acac] ring-2 ring-[#00acac]/20 shadow-md' : 'border-gray-200/80 shadow-xs' }} overflow-hidden flex flex-col justify-between hover:border-gray-300 transition-all">
                
                <!-- Card Header -->
                <div class="p-4 bg-gray-50/70 border-b border-gray-200/80 flex items-start justify-between gap-3">
                    <div class="flex items-center gap-3">
                        <div class="h-10 w-10 rounded-full bg-[#2d353c] text-[#00acac] font-black text-sm flex items-center justify-center shrink-0 border border-gray-700">
                            {{ $c->sigla }}
                        </div>
                        <div>
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <h3 class="font-bold text-gray-900 text-sm leading-tight">{{ $c->nombre }}</h3>
                                @if($esMiCarrera)
                                    <span class="bg-[#00acac] text-white text-[9px] font-extrabold px-1.5 py-0.2 rounded uppercase">Mi Carrera</span>
                                @endif
                            </div>
                            <p class="text-[11px] text-gray-500 font-medium uppercase mt-0.5">Sigla: {{ $c->sigla }}</p>
                        </div>
                    </div>
                </div>

                <!-- Card Body -->
                <div class="p-4 space-y-3 flex-1 bg-white">
                    <div class="grid grid-cols-2 gap-2 text-center">
                        <div class="bg-gray-50 p-2 rounded border border-gray-200/60">
                            <span class="block text-[10px] text-gray-400 font-bold uppercase">Gestión / Periodo</span>
                            <span class="font-extrabold text-gray-800 text-xs tabular-nums">{{ $gestionActualNombre }} - P{{ $periodoActualNombre }}</span>
                        </div>
                        <div class="bg-gray-50 p-2 rounded border border-gray-200/60">
                            <span class="block text-[10px] text-gray-400 font-bold uppercase">Evaluación</span>
                            <span class="font-bold text-[#00acac] text-xs">Vicerrectorado</span>
                        </div>
                    </div>
                </div>

                <!-- Card Footer Action Button -->
                <div class="p-3 bg-gray-50 border-t border-gray-200/80 flex items-center justify-between">
                    <span class="text-[11px] font-semibold text-gray-500">Asignación docente</span>

                    <a href="{{ route('designaciones.carrera', ['carrera' => $c->id, 'gestion_id' => $gestionId, 'periodo_id' => $periodoId]) }}" 
                       class="px-3.5 py-1.5 bg-[#2d353c] hover:bg-[#20252a] text-white font-bold rounded text-xs shadow-xs transition-colors flex items-center gap-1.5">
                        <span>Gestionar Asignaciones</span>
                        <svg class="w-3.5 h-3.5 text-[#00acac]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </a>
                </div>
            </div>
        @endforeach
    </div>
</div>
@endsection
