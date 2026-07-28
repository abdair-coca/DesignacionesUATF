@extends('layouts.app')

@section('title', 'Solicitudes y Revisiones — UATF Designación Docente')

@section('content')
<div class="flex flex-col lg:flex-row gap-6 min-h-[calc(100vh-6rem)]">
    <!-- Panel Izquierdo: FOLDERS & LABELS estilo Color Admin v2 Inbox -->
    <div class="w-full lg:w-64 bg-white rounded-lg border border-gray-200 shadow-sm p-4 shrink-0 flex flex-col justify-between">
        <div class="space-y-6">
            <!-- Botón Superior de Acción -->
            <div class="pb-3 border-b border-gray-100">
                <a href="{{ route('designaciones.index') }}" 
                   class="w-full bg-[#2d353c] hover:bg-[#20252a] text-white font-bold py-2 px-4 rounded-lg text-xs flex items-center justify-center gap-2 shadow transition-all">
                    <svg class="w-4 h-4 text-[#00acac]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                    </svg>
                    <span>Ver Resumen General</span>
                </a>
            </div>

            <!-- FOLDERS (Carpetas) -->
            <div>
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-2">Carpetas</h3>
                <nav class="space-y-1 text-xs">
                    <a href="{{ route('revisiones.pendientes', ['folder' => 'inbox']) }}" 
                       class="flex items-center justify-between px-3 py-2 rounded-lg transition-colors font-medium {{ $folder === 'inbox' ? 'bg-[#20252a] text-white font-bold' : 'text-gray-700 hover:bg-gray-100' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 {{ $folder === 'inbox' ? 'text-[#00acac]' : 'text-gray-500' }}" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <span>Bandeja de Entrada</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full {{ $folder === 'inbox' ? 'bg-[#00acac] text-white' : 'bg-gray-100 text-gray-600' }}">
                            {{ $counts['inbox'] }}
                        </span>
                    </a>

                    <a href="{{ route('revisiones.pendientes', ['folder' => 'pendientes']) }}" 
                       class="flex items-center justify-between px-3 py-2 rounded-lg transition-colors font-medium {{ $folder === 'pendientes' ? 'bg-[#20252a] text-white font-bold' : 'text-gray-700 hover:bg-gray-100' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Pendientes</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800">
                            {{ $counts['pendientes'] }}
                        </span>
                    </a>

                    <a href="{{ route('revisiones.pendientes', ['folder' => 'revisadas']) }}" 
                       class="flex items-center justify-between px-3 py-2 rounded-lg transition-colors font-medium {{ $folder === 'revisadas' ? 'bg-[#20252a] text-white font-bold' : 'text-gray-700 hover:bg-gray-100' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Revisadas</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800">
                            {{ $counts['revisadas'] }}
                        </span>
                    </a>

                    <a href="{{ route('revisiones.pendientes', ['folder' => 'todas']) }}" 
                       class="flex items-center justify-between px-3 py-2 rounded-lg transition-colors font-medium {{ $folder === 'todas' ? 'bg-[#20252a] text-white font-bold' : 'text-gray-700 hover:bg-gray-100' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>Todas las Peticiones</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-100 text-gray-600">
                            {{ $counts['todas'] }}
                        </span>
                    </a>
                </nav>
            </div>

            <!-- LABELS (Etiquetas por Tipo) -->
            <div class="pt-4 border-t border-gray-100">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2 px-2">Etiquetas de Carrera</h3>
                <div class="space-y-1.5 text-xs text-gray-600 px-2">
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-[#00acac]"></span>
                        <span>Informática (INF)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-blue-500"></span>
                        <span>Civil (CIV)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-purple-500"></span>
                        <span>Medicina (MED)</span>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="w-2.5 h-2.5 rounded-full bg-amber-500"></span>
                        <span>Otras Carreras</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-100 text-[11px] text-gray-400 text-center">
            Sistema de Designación UATF
        </div>
    </div>

    <!-- Panel Principal: BANDEJA DE PETICIONES estilo Gmail Inbox -->
    <div class="flex-1 bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden flex flex-col">
        <!-- Toolbar Superior estilo Color Admin Inbox -->
        <div class="bg-[#f8f9fa] border-b border-gray-200 px-4 py-3 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-3">
                <input type="checkbox" class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                <select onchange="window.location.href=this.value" class="border border-gray-300 rounded px-2 py-1 bg-white text-gray-700 font-semibold focus:outline-none text-xs">
                    <option value="{{ route('revisiones.pendientes', ['folder' => 'inbox']) }}" {{ $folder === 'inbox' ? 'selected' : '' }}>Ver Pendientes</option>
                    <option value="{{ route('revisiones.pendientes', ['folder' => 'revisadas']) }}" {{ $folder === 'revisadas' ? 'selected' : '' }}>Ver Revisadas</option>
                    <option value="{{ route('revisiones.pendientes', ['folder' => 'todas']) }}" {{ $folder === 'todas' ? 'selected' : '' }}>Ver Todas</option>
                </select>

                <a href="{{ route('revisiones.pendientes', ['folder' => $folder]) }}" 
                   title="Actualizar"
                   class="p-1.5 border border-gray-300 rounded bg-white hover:bg-gray-100 text-gray-600 transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </a>
            </div>

            <!-- Buscador estilo Inbox -->
            <form method="GET" action="{{ route('revisiones.pendientes') }}" class="flex items-center gap-2">
                <input type="hidden" name="folder" value="{{ $folder }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por director o carrera..." 
                       class="px-3 py-1.5 border border-gray-300 rounded-lg text-xs w-48 sm:w-64 focus:outline-none focus:ring-1 focus:ring-[#00acac]">
                <button type="submit" class="bg-[#2d353c] hover:bg-[#20252a] text-white px-3 py-1.5 rounded-lg font-bold text-xs">
                    Buscar
                </button>
            </form>
        </div>

        <!-- Lista de Mensajes / Peticiones -->
        <div class="flex-1 overflow-y-auto divide-y divide-gray-100">
            @forelse ($pendientes as $p)
                <div onclick="window.location.href='{{ route('revisiones.revisar', $p['id']) }}'" 
                     class="flex items-center justify-between p-4 hover:bg-[#fff9d6]/40 cursor-pointer transition-colors group">
                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <input type="checkbox" onclick="event.stopPropagation()" class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                        
                        <!-- Avatar con la inicial de la Carrera -->
                        <div class="h-9 w-9 rounded-full bg-[#00acac] text-white font-black text-sm flex items-center justify-center shrink-0 shadow-sm">
                            {{ strtoupper(substr($p['carrera_sigla'] ?: $p['carrera_nombre'] ?: 'C', 0, 1)) }}
                        </div>

                        <!-- Remitente y Resunto estilo Inbox Gmail -->
                        <div class="min-w-0 flex-1 pr-4">
                            <div class="flex items-center gap-2">
                                <span class="font-bold text-gray-900 text-xs truncate">
                                    {{ $p['solicitante'] }} ({{ $p['carrera_sigla'] }})
                                </span>
                                @if($p['estado'] === 'pendiente')
                                    <span class="bg-amber-100 text-amber-800 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">
                                        Pendiente
                                    </span>
                                @else
                                    <span class="bg-emerald-100 text-emerald-800 text-[9px] font-bold px-1.5 py-0.5 rounded uppercase tracking-wider">
                                        Revisado
                                    </span>
                                @endif
                            </div>
                            <p class="text-xs text-gray-700 mt-0.5 truncate font-medium">
                                <span class="text-gray-900 font-semibold">Solicitud de Designación Docente</span>
                                <span class="text-gray-400"> — </span>
                                <span class="text-gray-500 font-normal">
                                    Carrera {{ $p['carrera_nombre'] }} (Gestión {{ $p['gestion_nombre'] }}-{{ $p['periodo_nombre'] }}) • {{ $p['cant_designaciones'] }} asignaciones enviadas
                                </span>
                            </p>
                        </div>
                    </div>

                    <!-- Fecha / Tiempo y Acción al Hover -->
                    <div class="flex items-center gap-3 shrink-0 text-right">
                        <span class="text-[11px] font-semibold text-gray-400 group-hover:text-gray-700">
                            {{ $p['solicitado_en'] }}
                        </span>
                        <svg class="w-4 h-4 text-gray-400 group-hover:text-[#00acac] transition-transform group-hover:translate-x-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </div>
                </div>
            @empty
                <div class="p-12 text-center text-gray-400">
                    <svg class="w-12 h-12 mx-auto text-gray-300 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                    <p class="font-bold text-sm text-gray-600">No hay solicitudes en esta carpeta</p>
                    <p class="text-xs text-gray-400 mt-1">Las peticiones enviadas por los Directores de Carrera aparecerán aquí.</p>
                </div>
            @endforelse
        </div>

        <!-- Footer / Paginación estilo Inbox -->
        <div class="bg-[#f8f9fa] border-t border-gray-200 px-4 py-2.5 flex items-center justify-between text-xs text-gray-500 font-medium">
            <div>
                Mostrando {{ count($pendientes) }} solicitud(es)
            </div>
            <div class="flex items-center gap-1">
                <button disabled class="px-2 py-1 border border-gray-300 rounded bg-white disabled:opacity-40 font-bold">&lsaquo;</button>
                <button disabled class="px-2 py-1 border border-gray-300 rounded bg-white disabled:opacity-40 font-bold">&rsaquo;</button>
            </div>
        </div>
    </div>
</div>
@endsection
