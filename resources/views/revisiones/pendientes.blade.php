@extends('layouts.app')

@section('title', 'Bandeja de Revisiones — UATF Designación Docente')

@section('content')
<h1 class="sr-only">Bandeja de Revisiones</h1>
<div class="flex flex-col lg:flex-row border border-gray-200/80 rounded-lg shadow-sm bg-white overflow-hidden min-h-[calc(100vh-6.5rem)] text-xs text-gray-800">

    <!-- LEFT SIDEBAR: FOLDERS & LABELS (Estilo Color Admin Inbox) -->
    <div class="w-full lg:w-64 bg-[#f0f3f8] border-r border-gray-200 p-4 shrink-0 flex flex-col justify-between">
        <div class="space-y-6">
            <!-- FOLDERS (Carpetas Inbox) -->
            <div>
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2.5 px-2">Carpetas</h3>
                <nav class="space-y-1">
                    <a href="{{ route('revisiones.pendientes', ['folder' => 'inbox']) }}"
                       class="flex items-center justify-between px-3 py-2 rounded transition-colors font-semibold {{ $folder === 'inbox' ? 'bg-[#d9e0e7] text-gray-900 font-bold' : 'text-gray-700 hover:bg-gray-200/60' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                            </svg>
                            <span>Inbox</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-[#2d353c] text-white">
                            {{ $counts['inbox'] }}
                        </span>
                    </a>

                    <a href="{{ route('revisiones.pendientes', ['folder' => 'pendientes']) }}"
                       class="flex items-center justify-between px-3 py-2 rounded transition-colors font-semibold {{ $folder === 'pendientes' ? 'bg-[#d9e0e7] text-gray-900 font-bold' : 'text-gray-700 hover:bg-gray-200/60' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Pendientes</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-amber-100 text-amber-800 border border-amber-200">
                            {{ $counts['pendientes'] }}
                        </span>
                    </a>

                    <a href="{{ route('revisiones.pendientes', ['folder' => 'revisadas']) }}"
                       class="flex items-center justify-between px-3 py-2 rounded transition-colors font-semibold {{ $folder === 'revisadas' ? 'bg-[#d9e0e7] text-gray-900 font-bold' : 'text-gray-700 hover:bg-gray-200/60' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                            </svg>
                            <span>Revisadas</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-emerald-100 text-emerald-800 border border-emerald-200">
                            {{ $counts['revisadas'] }}
                        </span>
                    </a>

                    <a href="{{ route('revisiones.pendientes', ['folder' => 'todas']) }}"
                       class="flex items-center justify-between px-3 py-2 rounded transition-colors font-semibold {{ $folder === 'todas' ? 'bg-[#d9e0e7] text-gray-900 font-bold' : 'text-gray-700 hover:bg-gray-200/60' }}">
                        <div class="flex items-center gap-2.5">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10" />
                            </svg>
                            <span>Todas las Peticiones</span>
                        </div>
                        <span class="px-2 py-0.5 text-[10px] font-bold rounded-full bg-gray-200 text-gray-700 border border-gray-300">
                            {{ $counts['todas'] }}
                        </span>
                    </a>
                </nav>
            </div>

            <!-- LABELS (Carreras) -->
            <div class="pt-4 border-t border-gray-200/80">
                <h3 class="text-[10px] font-bold text-gray-400 uppercase tracking-wider mb-2.5 px-2">Carreras</h3>
                <div class="space-y-2 text-xs text-gray-700 font-medium px-2">
                    @php
                        $coloresCarrera = ['#00acac', '#348fe2', '#727cb6', '#f59c1a', '#ff5b57', '#2d353c'];
                        $carrerasLista = \App\Models\Carrera::orderBy('sigla')->get();
                    @endphp
                    @foreach($carrerasLista as $c)
                        <div class="flex items-center gap-2">
                            <span class="w-2.5 h-2.5 rounded-full" style="background-color: {{ $coloresCarrera[$loop->index % count($coloresCarrera)] }}"></span>
                            <span>{{ $c->nombre }} ({{ $c->sigla }})</span>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>

        <div class="pt-4 border-t border-gray-200/80 text-[11px] text-gray-400 font-medium text-center">
            UATF &bull; Vicerrectorado
        </div>
    </div>

    <!-- RIGHT MAIN PANEL: BANDEJA DE ENTRADA FLAT STYLE -->
    <div class="flex-1 flex flex-col min-w-0 bg-white">
        <!-- Top Toolbar estilo Color Admin Email Toolbar -->
        <div class="bg-[#f0f3f8] border-b border-gray-200 px-4 py-2.5 flex flex-wrap items-center justify-between gap-3 shrink-0">
            <div class="flex items-center gap-2">
                <label class="flex items-center gap-2">
                    <input type="checkbox" class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                    <span class="sr-only">Seleccionar todo</span>
                </label>

                <!-- Selector de filtro -->
                <select onchange="window.location.href=this.value" aria-label="Filtrar bandeja de solicitudes" class="border border-gray-300 rounded px-3 py-1 bg-white text-gray-700 font-bold focus:outline-none text-xs shadow-2xs">
                    <option value="{{ route('revisiones.pendientes', ['folder' => 'inbox']) }}" {{ $folder === 'inbox' ? 'selected' : '' }}>Bandeja (Pendientes)</option>
                    <option value="{{ route('revisiones.pendientes', ['folder' => 'revisadas']) }}" {{ $folder === 'revisadas' ? 'selected' : '' }}>Ver Revisadas</option>
                    <option value="{{ route('revisiones.pendientes', ['folder' => 'todas']) }}" {{ $folder === 'todas' ? 'selected' : '' }}>Ver Todas (Pendientes)</option>
                </select>

                <!-- Refresh Button ↻ -->
                <a href="{{ route('revisiones.pendientes', ['folder' => $folder]) }}"
                   title="Actualizar Bandeja"
                   class="px-2.5 py-1 border border-gray-300 rounded bg-white hover:bg-gray-50 text-gray-700 font-bold shadow-2xs transition-colors flex items-center justify-center">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                </a>
            </div>

            <!-- Buscador Inbox Flat Style -->
            <form method="GET" action="{{ route('revisiones.pendientes') }}" class="flex items-center gap-2">
                <input type="hidden" name="folder" value="{{ $folder }}">
                <input type="text" name="q" value="{{ $q }}" placeholder="Buscar por director o carrera..."
                       class="px-3 py-1 border border-gray-300 rounded text-xs w-48 sm:w-60 bg-white focus:outline-none focus:ring-1 focus:ring-[#00acac]">
                <button type="submit" class="bg-[#2d353c] hover:bg-[#20252a] text-white px-3 py-1 rounded font-bold text-xs shadow-2xs">
                    Buscar
                </button>
            </form>
        </div>

        <!-- Lista de Mensajes / Solicitudes por Carrera (Diseño Plano Color Admin) -->
        <div class="flex-1 overflow-y-auto divide-y divide-gray-200/70">
            @forelse ($pendientes as $p)
                <div onclick="window.location.href='{{ route('revisiones.revisar', $p['id']) }}'"
                     class="flex items-center justify-between px-4 py-3 hover:bg-[#f0f3f8] cursor-pointer transition-colors group">

                    <div class="flex items-center gap-3 min-w-0 flex-1">
                        <label class="flex items-center" onclick="event.stopPropagation()">
                            <input type="checkbox" class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                            <span class="sr-only">Seleccionar solicitud {{ $p['carrera_sigla'] }}</span>
                        </label>

                        <!-- Circular Avatar con la Inicial de la Carrera -->
                        @php
                            $bgColors = ['INF' => 'bg-[#348fe2]', 'CIV' => 'bg-[#00acac]', 'MED' => 'bg-[#727cb6]', 'IND' => 'bg-[#f59c1a]'];
                            $sigla = strtoupper($p['carrera_sigla'] ?: 'C');
                            $avatarColor = $bgColors[$sigla] ?? 'bg-[#2d353c]';
                        @endphp
                        <div class="h-8 w-8 rounded-full {{ $avatarColor }} text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs">
                            {{ substr($sigla, 0, 1) }}
                        </div>

                        <!-- Sender & Title Info -->
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-2">
                                <h4 class="font-bold text-gray-900 text-xs truncate group-hover:text-[#348fe2] transition-colors">
                                    {{ $p['carrera_nombre'] }} ({{ $p['carrera_sigla'] }})
                                </h4>
                                <span class="bg-gray-100 border border-gray-300 text-gray-700 text-[10px] font-bold px-1.5 py-0.2 rounded">
                                    {{ $p['cant_designaciones'] }} materias
                                </span>
                            </div>
                            <p class="text-[11px] text-gray-700 truncate mt-0.5" title="{{ $p['descripcion'] ?: 'Sin descripción' }}">
                                <span class="font-semibold">Descripción:</span> {{ $p['descripcion'] ?: 'Sin descripción' }}
                            </p>
                            <p class="text-[11px] text-gray-500 truncate mt-0.5">
                                <span class="font-medium text-gray-700">De: {{ $p['solicitante'] }}</span> &bull;
                                Propuesta de Designación Docente — Gestión {{ $p['gestion_nombre'] }} (Periodo {{ $p['periodo_nombre'] }})
                            </p>
                        </div>
                    </div>

                    <!-- Right Column: Date & Status Badge -->
                    <div class="flex items-center gap-4 shrink-0 ml-4">
                        <span class="text-[11px] font-medium text-gray-400 tabular-nums">
                            {{ $p['solicitado_en'] ? $p['solicitado_en'] : $p['hace_tiempo'] }}
                        </span>

                        @if($p['estado'] === 'pendiente')
                            <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                <span class="w-1.5 h-1.5 rounded-full bg-amber-500 animate-pulse"></span>
                                Pendiente
                            </span>
                        @else
                            <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1">
                                ✓ Revisado
                            </span>
                        @endif
                    </div>
                </div>
            @empty
                <div class="py-16 text-center text-gray-400 space-y-2">
                    <svg class="w-12 h-12 text-gray-300 mx-auto" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                    </svg>
                    <p class="font-bold text-gray-700 text-sm">No hay peticiones en esta carpeta</p>
                    <p class="text-xs text-gray-500 font-medium">Las solicitudes enviadas por los Directores de Carrera aparecerán listadas aquí.</p>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
