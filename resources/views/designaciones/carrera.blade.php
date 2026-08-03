@extends('layouts.app')

@section('title', 'Designación por Docente — ' . $carrera->nombre)

@section('content')
@php
    // Organizar datos de docentes y sus designaciones asignadas
    $docentesProcesados = [];
    foreach ($docentes as $d) {
        $desigDocente = $designaciones->filter(fn($des) => (string)$des->docente_id === (string)$d['id']);
        $horasPagadasLocal = $desigDocente->sum(fn($des) => (int) ($des->horas_pagadas ?? ($des->materia?->horas ?? 0)));
        $horasNoPagadasLocal = $desigDocente->sum(fn($des) => (int) ($des->horas_no_pagadas ?? 0));
        $horasLocal = $horasPagadasLocal + $horasNoPagadasLocal;
        $horasOtrasCarreras = (int)($d['horasOtrasCarreras'] ?? 0);
        $horasTotalGlobal = $horasLocal + $horasOtrasCarreras;
        $materiasSiglas = $desigDocente->map(fn($des) => ($des->materia?->sigla ?? '') . ' (G' . ($des->grupo?->codigo ?? '') . ')')->filter()->values()->all();

        $estadoCarga = 'optimo';
        $estadoEtiqueta = 'Óptimo';
        $estadoColor = 'bg-emerald-100 text-emerald-800 border-emerald-200';

        if ($horasTotalGlobal == 0) {
            $estadoCarga = 'sin_asignacion';
            $estadoEtiqueta = 'Sin asignación';
            $estadoColor = 'bg-gray-100 text-gray-700 border-gray-200';
        } elseif ($horasTotalGlobal < 6) {
            $estadoCarga = 'bajo_minimo';
            $estadoEtiqueta = 'Bajo mínimo (< 6h)';
            $estadoColor = 'bg-amber-100 text-amber-800 border-amber-200';
        } elseif ($horasTotalGlobal > 32) {
            $estadoCarga = 'sobrecarga';
            $estadoEtiqueta = 'Sobrecarga (> 32h)';
            $estadoColor = 'bg-rose-100 text-rose-800 border-rose-200';
        }

        $docentesProcesados[] = [
            'id' => $d['id'],
            'nombre' => $d['nombre'],
            'carreraSigla' => $d['carreraSigla'] ?? $carrera->sigla,
            'horasLocal' => $horasLocal,
            'horasPagadasLocal' => $horasPagadasLocal,
            'horasNoPagadasLocal' => $horasNoPagadasLocal,
            'horasOtrasCarreras' => $horasOtrasCarreras,
            'horas' => $horasTotalGlobal,
            'materias' => $materiasSiglas,
            'estado' => $estadoCarga,
            'estadoEtiqueta' => $estadoEtiqueta,
            'estadoColor' => $estadoColor,
            'prioridad' => $d['prioridad'] ?? 3,
            'grupos_ids' => $desigDocente->pluck('grupo_id')->toArray(),
        ];
    }

    $rosterGrupos = $roster->map(fn($r) => [
        'id' => $r['id'],
        'materia_id' => $r['materia']['id'],
        'materia_nombre' => $r['materia']['nombre'],
        'materia_sigla' => $r['materia']['sigla'],
        'codigo' => $r['codigo'],
        'horas' => $r['horas'],
        'docente_actual_id' => $r['designacion']['docente']['id'] ?? null,
        'horas_pagadas' => $r['designacion']['horas_pagadas'] ?? $r['horas'],
        'horas_no_pagadas' => $r['designacion']['horas_no_pagadas'] ?? 0,
        'observacion_remuneracion' => $r['designacion']['observacion_remuneracion'] ?? null,
        'bloqueada' => $r['bloqueada'],
        'observada' => $r['observada'] ?? false,
        'observacion_revision' => $r['observacion_revision'] ?? null,
    ])->values();

    $observacionesRevision = $rosterGrupos->filter(fn ($grupo) => $grupo['observada'])->values();
    $gruposObservados = $observacionesRevision->pluck('id');
    $docentesProcesados = collect($docentesProcesados)->map(function (array $docente) use ($gruposObservados): array {
        $docente['observada'] = collect($docente['grupos_ids'])->intersect($gruposObservados)->isNotEmpty();

        return $docente;
    })->values()->all();

@endphp

<div x-data="designacionesCarreraData(
        {{ json_encode($docentesProcesados) }},
        {{ json_encode($rosterGrupos) }},
        {{ $propuesta->id }},
        {{ $filtros['gestion_id'] }},
        {{ $filtros['periodo_id'] }},
        {{ $puedeEditar ? 'true' : 'false' }},
        {{ json_encode($revision) }}
    )"
     class="space-y-4">

    <!-- Encabezado del Módulo -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="bg-[#00acac] text-white text-xs font-bold px-2 py-0.5 rounded">Carrera</span>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">{{ $carrera->nombre }} ({{ $carrera->sigla }})</h1>
            </div>
            <p class="text-xs text-gray-500 mt-0.5">Asignación de carga horaria docente — {{ $carrera->nombre }}</p>
        </div>

        <div class="flex items-center gap-3">
            <div class="flex items-center gap-2 bg-white p-2 rounded-lg border border-gray-200 shadow-sm text-xs text-gray-700">
                <span class="font-semibold">Gestión {{ $propuesta->gestion->nombre }}</span>
                <span class="text-gray-300">|</span>
                <span class="font-semibold">Periodo {{ $propuesta->periodo->nombre }}</span>
            </div>

            @if($puedeEditar)
            <button @click="abrirModalCopiarAnterior()"
                    class="bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold px-3.5 py-2 rounded-lg text-xs flex items-center gap-1.5 shadow transition-all cursor-pointer">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                </svg>
                <span>Copiar de Gestión Anterior</span>
            </button>

            <template x-if="revisionData && revisionData.estado === 'pendiente'">
                <button @click="retirarEnvioRevision()"
                        class="bg-amber-600 hover:bg-amber-700 text-white font-bold px-3.5 py-2 rounded-lg text-xs flex items-center gap-1.5 shadow transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Retirar Envío</span>
                </button>
            </template>

            <template x-if="!revisionData || revisionData.estado !== 'pendiente'">
                <button @click="modalSolicitarRevisionOpen = true"
                        class="bg-[#00acac] hover:bg-[#008a8a] text-white font-bold px-3.5 py-2 rounded-lg text-xs flex items-center gap-1.5 shadow transition-all cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8" />
                    </svg>
                    <span>Enviar Propuesta a Vicerrectorado</span>
                </button>
            </template>

            @endif
        </div>
    </div>

    @if(!$puedeEditar)
        <div class="border border-amber-300 bg-amber-50 px-4 py-3 text-sm text-amber-900">
            El borrador esta bloqueado mientras una version este pendiente de revision.
        </div>
    @endif

    @if(filled($observacionRevisionGeneral) || $observacionesRevision->isNotEmpty())
        <section class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-900 shadow-sm">
            <h2 class="font-bold">Observaciones de Vicerrectorado</h2>
            @if(filled($observacionRevisionGeneral))
                <p class="mt-1">{{ $observacionRevisionGeneral }}</p>
            @endif
            @if($observacionesRevision->isNotEmpty())
                <div class="mt-2 space-y-1 text-xs">
                    @foreach($observacionesRevision as $grupo)
                        <p><strong>{{ $grupo['materia_sigla'] }} (G{{ $grupo['codigo'] }}):</strong> {{ $grupo['observacion_revision'] ?: 'Revisar esta designacion.' }}</p>
                    @endforeach
                </div>
            @endif
        </section>
    @endif

    <section class="rounded-lg border border-gray-200 bg-white shadow-sm overflow-hidden">
        <div class="border-b border-gray-200 px-4 py-3">
            <h2 class="font-bold text-sm text-gray-900">Historial de revisiones</h2>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-gray-50 text-gray-600 uppercase">
                    <tr>
                        <th class="px-4 py-3">Version</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Enviada</th>
                        <th class="px-4 py-3">Observacion</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 text-gray-700">
                    @forelse($propuesta->versiones as $version)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $version->numero }}</td>
                            <td class="px-4 py-3">{{ ucfirst($version->estado) }}</td>
                            <td class="px-4 py-3">{{ $version->enviado_en?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3">{{ $version->observaciones ?: '-' }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-5 text-center text-gray-500">Aun no se enviaron versiones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Contenedor Principal DataTable - Select (Color Admin v2 Style) -->
    <div class="rounded-lg border border-gray-200/80 bg-white shadow-md overflow-hidden">
        <!-- Header del Panel Estilo Color Admin -->
        <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between font-bold text-xs">
            <div class="flex items-center gap-2">
                <span>Asignación por Carrera</span>
                <span class="bg-[#00acac] text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-xs tracking-wider uppercase">{{ $carrera->sigla }}</span>
            </div>
        </div>

        <!-- Top Controls Bar -->
        <div class="p-3.5 border-b border-gray-200 bg-white flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <label for="carrera_per_page" class="sr-only">Registros por página</label>
                <select id="carrera_per_page" x-model="perPage" @change="currentPage = 1" aria-label="Registros por página" class="border border-gray-300 rounded-xs px-2 py-1 bg-white text-gray-700 shadow-2xs focus:border-[#348fe2] focus:ring-1 focus:ring-[#348fe2] outline-none">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <span class="text-gray-600 font-medium">registros por página</span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-gray-600 font-medium">Buscar docente:</span>
                <input type="text" x-model="busqueda" placeholder="Nombre o carrera..." class="border border-gray-300 rounded-xs px-2.5 py-1 text-xs w-48 shadow-2xs focus:border-[#348fe2] focus:ring-1 focus:ring-[#348fe2] outline-none">
            </div>
        </div>

        <!-- Tabla estilo Color Admin DataTable con Filas Alternadas (#f2f4f8) -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-white text-gray-900 font-bold border-b border-gray-200 text-xs">
                    <tr>
                        <th class="py-3 px-4 text-center w-12 border-r border-gray-200/80">#</th>
                        <th class="py-3 px-4 border-r border-gray-200/80">Docente</th>
                        <th class="py-3 px-4 border-r border-gray-200/80">Materias / Grupos Asignados</th>
                        <th class="py-3 px-4 text-center w-36 border-r border-gray-200/80">Carga Horaria</th>
                        <th class="py-3 px-4 text-center w-40 border-r border-gray-200/80">Estado</th>
                        <th class="py-3 px-4 text-center w-32">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-700 font-medium">
                    <template x-if="docentesPaginados.length === 0">
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-400 italic">No se encontraron docentes coincidentes.</td>
                        </tr>
                    </template>

                    <template x-for="(d, index) in docentesPaginados" :key="d.id">
                        <tr @click="docenteSeleccionadoId = (docenteSeleccionadoId === d.id ? null : d.id)"
                            class="transition-colors cursor-pointer select-none"
                            :class="d.observada ? (docenteSeleccionadoId === d.id ? 'bg-rose-100 border-l-4 border-rose-500' : 'bg-rose-50 hover:bg-rose-100 border-l-4 border-rose-500') : (docenteSeleccionadoId === d.id ? 'bg-[#fff9d6]' : (index % 2 === 0 ? 'bg-[#f2f4f8]' : 'bg-white hover:bg-gray-100/70'))">

                            <!-- Index -->
                            <td class="py-3.5 px-4 text-center font-bold text-gray-500 border-r border-gray-200/60" x-text="(currentPage - 1) * perPage + index + 1"></td>

                            <!-- Docente Profile -->
                            <td class="py-3.5 px-4 border-r border-gray-200/60">
                                <div class="flex items-center gap-3">
                                    <div class="h-8 w-8 rounded-full text-white font-bold text-xs flex items-center justify-center shrink-0 shadow-2xs"
                                         :class="d.observada ? 'bg-rose-600' : 'bg-[#00acac]'"
                                         x-text="d.nombre.charAt(0)"></div>
                                    <div>
                                        <div class="flex items-center gap-1.5 flex-wrap">
                                            <p class="font-bold" :class="d.observada ? 'text-rose-700' : 'text-gray-900'" x-text="d.nombre"></p>
                                            <template x-if="d.observada">
                                                <span class="bg-rose-100 text-rose-700 border border-rose-200 text-[10px] font-bold px-2 py-0.5 rounded-xs">Con observaciones</span>
                                            </template>
                                            <template x-if="d.prioridad === 1">
                                                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold px-2 py-0.5 rounded-xs">Titular Carrera</span>
                                            </template>
                                            <template x-if="d.prioridad === 2">
                                                <span class="bg-cyan-100 text-cyan-800 text-[10px] font-semibold px-2 py-0.5 rounded-xs">Histórico Carrera</span>
                                            </template>
                                        </div>
                                        <p class="text-[10px] text-gray-400 font-medium uppercase" x-text="'Origen: ' + (d.carreraSigla || 'General')"></p>
                                    </div>
                                </div>
                            </td>

                            <!-- Materias asignadas en badges -->
                            <td class="py-3.5 px-4 border-r border-gray-200/60">
                                <div class="flex flex-wrap gap-1">
                                    <template x-if="d.materias.length === 0">
                                        <span class="text-gray-400 italic text-[11px]">Sin materias asignadas</span>
                                    </template>
                                    <template x-for="mat in d.materias" :key="mat">
                                        <span class="bg-gray-100 text-gray-800 border border-gray-300 font-bold px-2 py-0.5 rounded-xs text-[10px]" x-text="mat"></span>
                                    </template>
                                </div>
                            </td>

                            <!-- Carga Horaria Numérica Global -->
                            <td class="py-3.5 px-4 text-center border-r border-gray-200/60">
                                <span class="block text-[10px] text-gray-600" x-text="'Local: ' + d.horasLocal + 'h'"></span>
                                <span class="block text-[10px] text-cyan-800" x-text="'Otras carreras: ' + d.horasOtrasCarreras + 'h'"></span>
                                <span class="block font-black text-gray-900 text-xs tabular-nums" x-text="'Global: ' + d.horas + 'h'"></span>
                                <template x-if="d.horasNoPagadasLocal > 0">
                                    <span class="block text-[9px] text-amber-700 font-bold mt-0.5" x-text="d.horasNoPagadasLocal + 'h no pagadas'"></span>
                                </template>
                            </td>

                            <!-- Estado Carga (Badges Rectangulares Suaves Pastel sin Emojis) -->
                            <td class="py-3.5 px-4 text-center border-r border-gray-200/60">
                                <template x-if="d.horas === 0">
                                    <span class="bg-gray-100 text-gray-700 text-[11px] font-semibold px-2.5 py-1 rounded-xs inline-block text-center">Sin horas</span>
                                </template>
                                <template x-if="d.horas > 0 && d.horas < 6">
                                    <span class="bg-amber-100 text-amber-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs inline-block text-center">Bajo mínimo (< 6h)</span>
                                </template>
                                <template x-if="d.horas >= 6 && d.horas <= 32">
                                    <span class="bg-emerald-100 text-emerald-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs inline-block text-center">Carga óptima</span>
                                </template>
                                <template x-if="d.horas > 32">
                                    <span class="bg-rose-100 text-rose-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs inline-block text-center">Exceso de horas (> 32h)</span>
                                </template>
                            </td>

                            <!-- Botón de Acción Azul Rectangular Sólido -->
                            <td class="py-3.5 px-4 text-center" @click.stop>
                                @if($puedeEditar)
                                <button @click="abrirModalAsignacion(d)"
                                        class="px-3 py-1.5 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded-xs text-xs transition-colors shadow-2xs cursor-pointer">
                                    Asignar
                                </button>
                                @else
                                <span class="text-gray-400">Solo lectura</span>
                                @endif
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>

        <!-- Footer Paginador estilo DataTable -->
        <div class="p-3 border-t border-gray-200 bg-gray-50/50 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="text-gray-600 font-medium">
                Mostrando <span class="font-bold text-gray-900" x-text="Math.min((currentPage - 1) * perPage + 1, docentesFiltrados.length)"></span>
                a <span class="font-bold text-gray-900" x-text="Math.min(currentPage * perPage, docentesFiltrados.length)"></span>
                de <span class="font-bold text-gray-900" x-text="docentesFiltrados.length"></span> docentes
                <template x-if="docenteSeleccionadoId">
                    <span class="ml-2 text-amber-800 font-semibold bg-amber-100 px-2 py-0.5 rounded border border-amber-200">1 docente seleccionado</span>
                </template>
            </div>

            <!-- Botones Paginador Cuadrados Color Admin -->
            <div class="flex items-center gap-1">
                <button @click="currentPage = 1" :disabled="currentPage === 1" class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-40 text-xs font-bold">«</button>
                <button @click="currentPage--" :disabled="currentPage === 1" class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-40 text-xs font-bold">‹</button>

                <span class="px-3 py-1 rounded bg-[#2d353c] text-white text-xs font-bold" x-text="currentPage"></span>

                <button @click="currentPage++" :disabled="currentPage >= totalPages" class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-40 text-xs font-bold">›</button>
                <button @click="currentPage = totalPages" :disabled="currentPage >= totalPages" class="px-2.5 py-1 rounded border border-gray-300 bg-white hover:bg-gray-100 disabled:opacity-40 text-xs font-bold">»</button>
            </div>
        </div>
    </div>

    <!-- MODAL ESTILO COLOR ADMIN V2 (Asignación de Materias a Docente) -->
    <div x-show="modalAbierta" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div @click.away="cerrarModal()" class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-5xl overflow-hidden flex flex-col max-h-[90vh]">

            <!-- Modal Header Color Admin v2 Style -->
            <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="bg-[#00acac] text-white text-[10px] font-bold px-1.5 py-0.5 rounded uppercase">Docente</span>
                    <h3 class="font-bold text-sm tracking-tight" x-text="'Asignar Materias — ' + (docenteActual?.nombre || '')"></h3>
                </div>
                <button @click="cerrarModal()" class="text-gray-400 hover:text-white transition-colors p-1">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>

            <!-- Modal Subheader: Muestra de Carga Horaria Numérica Global -->
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3 flex items-center justify-between text-xs shrink-0">
                <div>
                    <span class="text-gray-500 font-medium">Carga horaria global seleccionada:</span>
                    <span class="font-bold text-gray-900 text-sm ml-1 tabular-nums" x-text="totalHorasSeleccionadas + ' hrs'"></span>
                    <template x-if="docenteActual?.horasOtrasCarreras > 0">
                        <span class="text-sky-700 font-bold text-[11px] ml-1" x-text="'(incluye ' + (docenteActual?.horasOtrasCarreras || 0) + 'h en otras carreras)'"></span>
                    </template>
                    <span class="text-gray-500 text-[11px] font-normal ml-1" x-text="'(' + horasPagadasSeleccionadas + 'h pagadas + ' + horasNoPagadasSeleccionadas + 'h no pagadas; carga informativa)'"></span>
                </div>
                <div>
                    <template x-if="totalHorasSeleccionadas === 0">
                        <span class="bg-gray-200 text-gray-700 px-2 py-0.5 rounded text-[10px] font-bold">Sin horas</span>
                    </template>
                    <template x-if="totalHorasSeleccionadas > 0 && totalHorasSeleccionadas < 6">
                        <span class="bg-amber-100 text-amber-800 border border-amber-200 px-2 py-0.5 rounded text-[10px] font-bold">Bajo mínimo (< 6h)</span>
                    </template>
                    <template x-if="totalHorasSeleccionadas >= 6 && totalHorasSeleccionadas <= 32">
                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-200 px-2 py-0.5 rounded text-[10px] font-bold">Carga óptima</span>
                    </template>
                    <template x-if="totalHorasSeleccionadas > 32">
                        <span class="bg-rose-100 text-rose-800 border border-rose-200 px-2 py-0.5 rounded text-[10px] font-bold">Exceso de horas (> 32h)</span>
                    </template>
                </div>
            </div>

            <!-- Modal Body: Tabla de Materias y Grupos con Checkboxes -->
            <div class="p-5 overflow-y-auto flex-1 space-y-3">
                <p class="text-xs text-gray-500 font-medium mb-2">Selecciona los grupos y materias que dictará este docente:</p>

                <div class="border border-gray-200 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs border-collapse">
                        <thead class="bg-[#f2f4f6] text-gray-800 font-bold border-b border-gray-200">
                            <tr>
                                <th class="py-2.5 px-3 text-center w-10 border-r border-gray-200">Sel.</th>
                                <th class="py-2.5 px-4 border-r border-gray-200">Materia</th>
                                <th class="py-2.5 px-3 text-center w-16 border-r border-gray-200">Grupo</th>
                                <th class="py-2.5 px-3 text-center w-20 border-r border-gray-200">Oficiales</th>
                                <th class="py-2.5 px-3 text-center w-24 border-r border-gray-200">Pagadas</th>
                                <th class="py-2.5 px-3 text-center w-24 border-r border-gray-200">No pagadas</th>
                                <th class="py-2.5 px-3 text-center w-44">Justificación</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200/70 text-gray-700">
                            <template x-if="gruposDisponiblesParaDocente.length === 0">
                                <tr>
                                    <td colspan="7" class="py-6 text-center text-gray-400 italic font-normal">
                                        No hay materias ni grupos disponibles. Todos los grupos libres ya han sido asignados a otros docentes.
                                    </td>
                                </tr>
                            </template>

                            <template x-for="g in gruposDisponiblesParaDocente" :key="g.id">
                                <tr @click="!g.bloqueada && toggleGrupo(g.id)"
                                    :class="g.bloqueada ? 'bg-gray-100 text-gray-500' : (gruposSeleccionados.includes(g.id) ? 'bg-[#fff9d6] transition-colors cursor-pointer font-semibold' : 'hover:bg-gray-50 transition-colors cursor-pointer')">

                                    <!-- Checkbox -->
                                    <td class="py-2.5 px-3 text-center border-r border-gray-200/40" @click.stop>
                                        <input type="checkbox"
                                               :checked="gruposSeleccionados.includes(g.id)"
                                               :disabled="g.bloqueada"
                                               @change="toggleGrupo(g.id)"
                                               class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                                    </td>

                                    <!-- Materia -->
                                    <td class="py-2.5 px-4 border-r border-gray-200/40">
                                        <p class="font-bold text-gray-900" x-text="g.materia_nombre"></p>
                                        <p class="text-[10px] text-gray-400 font-normal" x-text="g.materia_sigla"></p>
                                        <template x-if="g.bloqueada">
                                            <p class="mt-1 text-[10px] font-semibold text-emerald-800">Aprobada previamente</p>
                                        </template>
                                        <template x-if="g.observada">
                                            <p class="mt-1 text-[10px] font-semibold text-rose-700">Observada: <span x-text="g.observacion_revision || 'Revisar esta designacion.'"></span></p>
                                        </template>
                                    </td>

                                    <!-- Grupo -->
                                    <td class="py-2.5 px-3 text-center font-bold text-gray-800 border-r border-gray-200/40" x-text="'G' + g.codigo"></td>

                                    <!-- Horas -->
                                    <td class="py-2.5 px-3 text-center font-bold text-gray-900 tabular-nums border-r border-gray-200/40" x-text="g.horas + ' hrs'"></td>

                                    <td class="py-2.5 px-2 border-r border-gray-200/40" @click.stop>
                                        <input type="number" min="0" step="1" :max="g.horas"
                                               x-model.number="modalDistribuciones[g.id].pagadas"
                                               :disabled="!gruposSeleccionados.includes(g.id) || g.bloqueada"
                                               class="w-full rounded border border-gray-300 px-2 py-1 text-center text-xs disabled:bg-gray-100 disabled:text-gray-400">
                                    </td>
                                    <td class="py-2.5 px-2 border-r border-gray-200/40" @click.stop>
                                        <input type="number" min="0" step="1"
                                               x-model.number="modalDistribuciones[g.id].noPagadas"
                                               :disabled="!gruposSeleccionados.includes(g.id) || g.bloqueada"
                                               class="w-full rounded border border-gray-300 px-2 py-1 text-center text-xs disabled:bg-gray-100 disabled:text-gray-400">
                                    </td>
                                    <td class="py-2.5 px-2" @click.stop>
                                        <input type="text" maxlength="1000" placeholder="Motivo opcional"
                                               x-model="modalDistribuciones[g.id].observacion"
                                               :disabled="!gruposSeleccionados.includes(g.id) || g.bloqueada"
                                               class="w-full rounded border border-gray-300 px-2 py-1 text-xs disabled:bg-gray-100 disabled:text-gray-400">
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Modal Footer Color Admin Style -->
            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex items-center justify-between shrink-0">
                <button @click="cerrarModal()" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-semibold shadow-xs">
                    Cancelar
                </button>

                <button @click="guardarDesignacionDocente()"
                        :disabled="cargandoGuardar"
                        class="px-5 py-2 bg-[#00acac] hover:bg-[#008a8a] text-white rounded text-xs font-bold shadow-md transition-colors flex items-center gap-1.5 disabled:opacity-50">
                    <svg x-show="!cargandoGuardar" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg x-show="cargandoGuardar" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="cargandoGuardar ? 'Guardando...' : 'Guardar Designación'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL COPIAR DE GESTIÓN ANTERIOR CON PREVISUALIZACIÓN DE DOCENTES -->
    <div x-show="modalCopiarOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div @click.away="modalCopiarOpen = false" class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            <!-- Header -->
            <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between shrink-0">
                <div class="flex items-center gap-2">
                    <span class="bg-[#348fe2] text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">Importar</span>
                    <h3 class="font-bold text-sm tracking-tight">Copiar & Previsualizar Designaciones de Gestión Anterior</h3>
                </div>
                <button @click="modalCopiarOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <!-- Body -->
            <div class="p-5 overflow-y-auto flex-1 space-y-4 text-xs text-gray-700">
                <p class="text-gray-600 font-medium">
                    Selecciona el periodo de origen para ver la previsualización de docentes que se asignarán a la gestión actual:
                </p>

                <!-- Selectores de Periodo Origen -->
                <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3.5 rounded-lg border border-gray-200">
                    <div>
                        <label for="copiar_origen_gestion_id" class="block font-bold text-gray-700 mb-1">Gestión Origen:</label>
                        <select id="copiar_origen_gestion_id" x-model="copiarOrigenGestionId" @change="cargarPrevisualizacionCopia()" aria-label="Seleccionar gestión origen" class="w-full text-xs font-medium border border-gray-300 rounded px-2.5 py-1.5 bg-white text-gray-800 focus:ring-1 focus:ring-[#348fe2] outline-none">
                            @foreach($gestiones as $g)
                                <option value="{{ $g->id }}">Gestión {{ $g->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div>
                        <label for="copiar_origen_periodo_id" class="block font-bold text-gray-700 mb-1">Periodo Origen:</label>
                        <select id="copiar_origen_periodo_id" x-model="copiarOrigenPeriodoId" @change="cargarPrevisualizacionCopia()" aria-label="Seleccionar periodo origen" class="w-full text-xs font-medium border border-gray-300 rounded px-2.5 py-1.5 bg-white text-gray-800 focus:ring-1 focus:ring-[#348fe2] outline-none">
                            @foreach($periodos as $p)
                                <option value="{{ $p->id }}">Periodo {{ $p->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Tabla Previsualización -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <span class="font-bold text-gray-800 text-xs">Previsualización de Cambios:</span>
                        <template x-if="previewItems.length > 0">
                            <span class="bg-[#348fe2] text-white text-[10px] font-bold px-2 py-0.5 rounded" x-text="previewItems.length + ' designaciones a importar'"></span>
                        </template>
                    </div>

                    <div class="border border-gray-200 rounded-lg overflow-hidden max-h-60 overflow-y-auto">
                        <table class="w-full text-left text-xs border-collapse">
                            <thead class="bg-[#f2f4f6] text-gray-800 font-bold border-b border-gray-200 sticky top-0">
                                <tr>
                                    <th class="py-2 px-3 text-center w-8 border-r border-gray-200">#</th>
                                    <th class="py-2 px-3 border-r border-gray-200">Materia / Grupo</th>
                                    <th class="py-2 px-3 border-r border-gray-200">Docente a Asignar</th>
                                    <th class="py-2 px-3 text-center w-16 border-r border-gray-200">Oficiales</th>
                                    <th class="py-2 px-3 text-center w-16 border-r border-gray-200">Pagadas</th>
                                    <th class="py-2 px-3 text-center w-16 border-r border-gray-200">No pagadas</th>
                                    <th class="py-2 px-3 text-center border-r border-gray-200">Impacto</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200/70 text-gray-700">
                                <template x-if="cargandoPreview">
                                    <tr>
                                        <td colspan="7" class="py-6 text-center text-gray-500 italic">
                                            <div class="flex items-center justify-center gap-2">
                                                <svg class="w-4 h-4 animate-spin text-[#348fe2]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                                                </svg>
                                                <span>Cargando previsualización de docentes...</span>
                                            </div>
                                        </td>
                                    </tr>
                                </template>

                                <template x-if="!cargandoPreview && previewItems.length === 0">
                                    <tr>
                                        <td colspan="7" class="py-6 text-center text-gray-400 italic">No hay designaciones en la gestión/periodo origen seleccionado.</td>
                                    </tr>
                                </template>

                                <template x-if="!cargandoPreview && previewItems.length > 0">
                                    <template x-for="(item, idx) in previewItems" :key="item.grupo_id">
                                        <tr class="hover:bg-gray-50">
                                            <td class="py-2 px-3 text-center font-bold text-gray-400 border-r border-gray-200/40" x-text="idx + 1"></td>
                                            <td class="py-2 px-3 border-r border-gray-200/40">
                                                <span class="font-bold text-gray-900" x-text="item.materia_sigla + ' (' + item.grupo_codigo + ')'"></span>
                                                <span class="block text-[10px] text-gray-500" x-text="item.materia_nombre"></span>
                                            </td>
                                            <td class="py-2 px-3 font-bold text-gray-900 border-r border-gray-200/40" x-text="item.docente_nombre"></td>
                                            <td class="py-2 px-3 text-center font-bold text-gray-800 border-r border-gray-200/40 tabular-nums" x-text="item.horas + ' hrs'"></td>
                                            <td class="py-2 px-3 text-center font-bold text-emerald-700 border-r border-gray-200/40 tabular-nums" x-text="item.horas_pagadas + ' hrs'"></td>
                                            <td class="py-2 px-3 text-center font-bold text-amber-700 border-r border-gray-200/40 tabular-nums" x-text="item.horas_no_pagadas + ' hrs'"></td>
                                            <td class="py-2 px-3 text-center">
                                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border" :class="item.impactoColor" x-text="item.impacto"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </template>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex items-center justify-between shrink-0">
                <button @click="modalCopiarOpen = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-semibold">
                    Cancelar
                </button>

                <button @click="ejecutarCopiarAnterior()"
                        :disabled="cargandoCopiar || previewItems.length === 0"
                        class="px-5 py-2 bg-[#348fe2] hover:bg-[#2a72b5] text-white rounded text-xs font-bold shadow-md transition-colors flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                    <svg x-show="!cargandoCopiar" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7v8a2 2 0 002 2h6M8 7V5a2 2 0 012-2h4.586a1 1 0 01.707.293l4.414 4.414a1 1 0 01.293.707V15a2 2 0 01-2 2h-2M8 7H6a2 2 0 00-2 2v10a2 2 0 002 2h8a2 2 0 002-2v-2" />
                    </svg>
                    <svg x-show="cargandoCopiar" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="cargandoCopiar ? 'Importando...' : 'Confirmar e Importar (' + previewItems.length + ')'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN DE SOLICITUD DE REVISIÓN (COLOR ADMIN V2) -->
    <div x-show="modalSolicitarRevisionOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden text-center">
            <!-- Header -->
            <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">SOLICITAR REVISIÓN</span>
                    <span class="font-bold text-xs">Vicerrectorado UATF</span>
                </div>
                <button @click="modalSolicitarRevisionOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <!-- Body -->
            <div class="p-6 space-y-4">
                <div class="h-14 w-14 rounded-full bg-amber-100 text-amber-600 font-bold flex items-center justify-center mx-auto border-2 border-amber-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 text-base">¿Enviar propuesta a revisión?</h3>
                <p class="text-xs text-gray-600 font-medium">
                    Se enviarán todas las designaciones docentes de <span class="font-bold text-gray-900">{{ $carrera->nombre }}</span> correspondientes a la <span class="font-bold text-gray-900">Gestión {{ $propuesta->gestion->nombre }}</span> al Vicerrectorado para su evaluación.
                </p>
            </div>

            <!-- Footer -->
            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex items-center justify-between">
                <button @click="modalSolicitarRevisionOpen = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-semibold">
                    Cancelar
                </button>

                <button @click="confirmarEnviarSolicitudVicedecanato()"
                        :disabled="cargandoSolicitar"
                        class="px-5 py-2 bg-[#00acac] hover:bg-[#008a8a] text-white rounded text-xs font-bold shadow-md transition-colors flex items-center gap-1.5 disabled:opacity-50 cursor-pointer">
                    <svg x-show="!cargandoSolicitar" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <svg x-show="cargandoSolicitar" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="cargandoSolicitar ? 'Enviando...' : 'Confirmar y Enviar'"></span>
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE CONFIRMACIÓN DE ÉXITO O MENSAJES (COLOR ADMIN V2) -->
    <div x-show="modalExitoOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden text-center">
            <!-- Header Modal Éxito -->
            <div class="bg-[#2d353c] text-white px-5 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">ÉXITO</span>
                    <span class="font-bold text-xs">Confirmación del Sistema</span>
                </div>
                <button @click="modalExitoOpen = false; window.location.reload();" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <!-- Body Modal Éxito -->
            <div class="p-6 space-y-4">
                <div class="h-14 w-14 rounded-full bg-emerald-100 text-emerald-600 font-bold flex items-center justify-center mx-auto border-2 border-emerald-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 text-base">¡Operación Completada Exitosamente!</h3>
                <p class="text-xs text-gray-600 font-medium" x-text="mensajeExito"></p>
            </div>

            <!-- Footer Modal Éxito -->
            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex justify-center">
                <button @click="modalExitoOpen = false; window.location.reload();"
                        class="px-6 py-2 bg-[#00acac] hover:bg-[#008a8a] text-white font-bold rounded-lg text-xs shadow-md transition-colors">
                    Aceptar y Actualizar
                </button>
            </div>
        </div>
    </div>

    @include('partials.modal-notificacion')
    @include('partials.modal-confirmacion')
</div>

@push('scripts')
<script>
    function designacionesCarreraData(docentesProcesados, rosterGrupos, propuestaId, gestionId, periodoId, puedeEditar, revisionData) {
        return {
            busqueda: '',
            perPage: 10,
            currentPage: 1,
            docenteSeleccionadoId: null,
            modalAbierta: false,
            modalExitoOpen: false,
            modalCopiarOpen: false,
            modalSolicitarRevisionOpen: false,
            copiarOrigenGestionId: '{{ $gestiones->first()?->id }}',
            copiarOrigenPeriodoId: '{{ $periodos->first()?->id }}',
            cargandoCopiar: false,
            cargandoPreview: false,
            cargandoSolicitar: false,
            previewItems: [],
            mensajeExito: '',
            cargandoGuardar: false,
            docenteActual: null,
            gruposSeleccionados: [],
            modalDistribuciones: {},
            puedeEditar: puedeEditar,
            revisionData: revisionData,

            todosDocentes: docentesProcesados,
            todosGrupos: rosterGrupos,

            init() {
                this.todosGrupos.forEach(g => this.inicializarDistribucion(g));
            },

            modalNotificacionOpen: false,
            modalNotificacionData: { titulo: '', mensaje: '', tipo: 'info', reload: false },

            mostrarNotificacion(titulo, mensaje, tipo = 'info', reload = false) {
                this.modalNotificacionData = { titulo, mensaje, tipo, reload };
                this.modalNotificacionOpen = true;
            },

            cerrarNotificacion() {
                const reload = this.modalNotificacionData.reload;
                this.modalNotificacionOpen = false;
                if (reload) {
                    window.location.reload();
                }
            },

            modalConfirmacionOpen: false,
            modalConfirmacionData: { titulo: '', mensaje: '', botonTexto: 'Confirmar', botonColor: '', callback: null },

            mostrarConfirmacion(titulo, mensaje, botonTexto, botonColor, callback) {
                this.modalConfirmacionData = { titulo, mensaje, botonTexto, botonColor, callback };
                this.modalConfirmacionOpen = true;
            },

            ejecutarConfirmacion() {
                const cb = this.modalConfirmacionData.callback;
                this.modalConfirmacionOpen = false;
                if (typeof cb === 'function') {
                    cb();
                }
            },

            retirarEnvioRevision() {
                if (!this.revisionData || !this.revisionData.id) return;

                this.mostrarConfirmacion(
                    'Retirar Solicitud de Envío',
                    '¿Deseas retirar esta propuesta enviada al Vicerrectorado para volver a editarla en modo borrador?',
                    'Retirar Envío',
                    'bg-amber-600 hover:bg-amber-700',
                    () => {
                        fetch('/designacion-versiones/' + this.revisionData.id + '/retirar', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': CSRF_TOKEN,
                                'Accept': 'application/json'
                            }
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.success) {
                                this.mostrarNotificacion('Solicitud Retirada', 'La solicitud ha sido retirada exitosamente.', 'exito', true);
                            } else {
                                this.mostrarNotificacion('Error al Retirar', res.error || 'No se pudo retirar la solicitud.', 'error');
                            }
                        })
                        .catch(() => {
                            this.mostrarNotificacion('Error de Conexión', 'Ocurrió un error al intentar retirar la solicitud.', 'error');
                        });
                    }
                );
            },

            get docentesFiltrados() {
                if (!this.busqueda.trim()) return this.todosDocentes;
                const q = this.busqueda.toLowerCase();
                return this.todosDocentes.filter(d => d.nombre.toLowerCase().includes(q) || (d.carreraSigla || '').toLowerCase().includes(q));
            },

            get docentesPaginados() {
                const start = (this.currentPage - 1) * this.perPage;
                return this.docentesFiltrados.slice(start, start + parseInt(this.perPage));
            },

            get totalPages() {
                return Math.ceil(this.docentesFiltrados.length / parseInt(this.perPage)) || 1;
            },

            abrirModalAsignacion(docente) {
                this.docenteActual = docente;
                this.gruposSeleccionados = [...docente.grupos_ids];
                this.modalDistribuciones = {};
                this.todosGrupos.forEach(g => this.inicializarDistribucion(g));
                this.modalAbierta = true;
            },

            cerrarModal() {
                this.modalAbierta = false;
                this.docenteActual = null;
                this.gruposSeleccionados = [];
                this.modalDistribuciones = {};
            },

            abrirModalCopiarAnterior() {
                this.modalCopiarOpen = true;
                this.cargarPrevisualizacionCopia();
            },

            cargarPrevisualizacionCopia() {
                if (!this.copiarOrigenGestionId || !this.copiarOrigenPeriodoId) return;

                this.cargandoPreview = true;
                this.previewItems = [];

                fetch('/designaciones/' + propuestaId + '/importar/previsualizar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        origen_gestion_id: this.copiarOrigenGestionId,
                        origen_periodo_id: this.copiarOrigenPeriodoId,
                        destino_gestion_id: gestionId,
                        destino_periodo_id: periodoId
                    })
                })
                .then(r => r.json())
                .then(res => {
                    this.cargandoPreview = false;
                    if (res.success) {
                        this.previewItems = res.items;
                    } else {
                        this.previewItems = [];
                    }
                })
                .catch(() => {
                    this.cargandoPreview = false;
                    this.previewItems = [];
                });
            },

            get gruposDisponiblesParaDocente() {
                if (!this.docenteActual) return this.todosGrupos;

                const gruposOcupadosPorOtros = new Set();
                this.todosDocentes.forEach(d => {
                    if (d.id !== this.docenteActual.id) {
                        d.grupos_ids.forEach(gid => gruposOcupadosPorOtros.add(gid));
                    }
                });

                return this.todosGrupos.filter(g => {
                    const asignadoAlActual = (g.docente_actual_id === this.docenteActual.id) || this.gruposSeleccionados.includes(g.id);
                    const ocupadoPorOtro = gruposOcupadosPorOtros.has(g.id) || (g.docente_actual_id && g.docente_actual_id !== this.docenteActual.id);

                    return g.observada || asignadoAlActual || !ocupadoPorOtro;
                });
            },

            get totalHorasSeleccionadas() {
                return this.horasPagadasSeleccionadas + this.horasNoPagadasSeleccionadas + (this.docenteActual?.horasOtrasCarreras || 0);
            },

            get horasPagadasSeleccionadas() {
                return this.gruposSeleccionados.reduce((sum, id) => sum + Number(this.modalDistribuciones[id]?.pagadas || 0), 0);
            },

            get horasNoPagadasSeleccionadas() {
                return this.gruposSeleccionados.reduce((sum, id) => sum + Number(this.modalDistribuciones[id]?.noPagadas || 0), 0);
            },

            inicializarDistribucion(grupo) {
                if (!this.modalDistribuciones[grupo.id]) {
                    this.modalDistribuciones[grupo.id] = {
                        pagadas: Number(grupo.horas_pagadas ?? grupo.horas),
                        noPagadas: Number(grupo.horas_no_pagadas ?? 0),
                        observacion: grupo.observacion_remuneracion || '',
                    };
                }
            },

            toggleGrupo(grupoId) {
                const grupo = this.todosGrupos.find(item => item.id === grupoId);
                if (grupo?.bloqueada) return;
                this.inicializarDistribucion(grupo);

                if (this.gruposSeleccionados.includes(grupoId)) {
                    this.gruposSeleccionados = this.gruposSeleccionados.filter(id => id !== grupoId);
                } else {
                    this.gruposSeleccionados.push(grupoId);
                }
            },

            guardarDesignacionDocente() {
                if (!this.docenteActual) return;

                const cambios = [];
                let distribucionInvalida = false;
                this.todosGrupos.forEach(g => {
                    const estaSeleccionado = this.gruposSeleccionados.includes(g.id);
                    const asignadoPreviamente = (g.docente_actual_id === this.docenteActual.id);

                    if (estaSeleccionado) {
                        const distribucion = this.modalDistribuciones[g.id];
                        const horasPagadas = Number(distribucion?.pagadas);
                        const horasNoPagadas = Number(distribucion?.noPagadas);

                        if (!Number.isInteger(horasPagadas) || !Number.isInteger(horasNoPagadas) || horasPagadas < 0 || horasNoPagadas < 0) {
                            this.mostrarNotificacion('Distribución inválida', 'Las horas pagadas y no pagadas deben ser enteros no negativos.', 'error');
                            distribucionInvalida = true;
                            return;
                        }

                        if (horasPagadas > g.horas || horasPagadas + horasNoPagadas < g.horas) {
                            this.mostrarNotificacion('Distribución inválida', 'La distribución debe cubrir horas oficiales y las pagadas no pueden superarlas.', 'error');
                            distribucionInvalida = true;
                            return;
                        }

                        cambios.push({
                            grupo_id: g.id,
                            materia_id: g.materia_id,
                            docente_id: this.docenteActual.id,
                            horas_pagadas: horasPagadas,
                            horas_no_pagadas: horasNoPagadas,
                            observacion_remuneracion: distribucion?.observacion || null,
                        });
                    } else if (asignadoPreviamente) {
                        cambios.push({
                            grupo_id: g.id,
                            materia_id: g.materia_id,
                            docente_id: null
                        });
                    }
                });

                if (distribucionInvalida) {
                    this.cargandoGuardar = false;
                    return;
                }

                this.cargandoGuardar = true;

                if (cambios.length === 0) {
                    const nombreDocente = this.docenteActual.nombre;
                    const horas = this.totalHorasSeleccionadas;
                    this.cerrarModal();
                    this.mensajeExito = `Las materias de ${nombreDocente} están al día (${horas} hrs totales).`;
                    this.modalExitoOpen = true;
                    this.cargandoGuardar = false;
                    return;
                }

                fetch('/designaciones/' + propuestaId + '/asignaciones', {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        Id_gestion: gestionId,
                        Id_periodo: periodoId,
                        cambios: cambios
                    })
                })
                .then(r => r.json())
                .then(res => {
                    this.cargandoGuardar = false;
                    if (res.success) {
                        const nombreDocente = this.docenteActual.nombre;
                        const horas = this.totalHorasSeleccionadas;
                        this.cerrarModal();
                        this.mensajeExito = `Las materias asignadas a ${nombreDocente} fueron guardadas exitosamente en la base de datos (${horas} hrs totales).`;
                        this.modalExitoOpen = true;
                    } else {
                        this.mostrarNotificacion('Error al Guardar', res.error || 'Ocurrió un error al guardar las designaciones.', 'error');
                    }
                })
                .catch(() => {
                    this.cargandoGuardar = false;
                    this.mostrarNotificacion('Error de Conexión', 'Ocurrió un error inesperado al guardar las designaciones.', 'error');
                });
            },

            guardarCambiosGeneral() {
                if (this.docenteActual) {
                    this.guardarDesignacionDocente();
                } else {
                    this.mensajeExito = 'Las asignaciones de docentes para esta carrera están guardadas y al día en la base de datos.';
                    this.modalExitoOpen = true;
                }
            },

            ejecutarCopiarAnterior() {
                if (!this.copiarOrigenGestionId || !this.copiarOrigenPeriodoId) {
                    this.mostrarNotificacion('Selección Requerida', 'Por favor selecciona la gestión y periodo origen.', 'info');
                    return;
                }

                this.cargandoCopiar = true;

                fetch('/designaciones/' + propuestaId + '/importar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        origen_gestion_id: this.copiarOrigenGestionId,
                        origen_periodo_id: this.copiarOrigenPeriodoId,
                        destino_gestion_id: gestionId,
                        destino_periodo_id: periodoId
                    })
                })
                .then(r => r.json())
                .then(res => {
                    this.cargandoCopiar = false;
                    if (res.success) {
                        this.modalCopiarOpen = false;
                        this.mensajeExito = res.message;
                        this.modalExitoOpen = true;
                    } else {
                        this.mostrarNotificacion('Error al Copiar', res.error || 'Ocurrió un error al copiar las designaciones.', 'error');
                    }
                })
                .catch(() => {
                    this.cargandoCopiar = false;
                    this.mostrarNotificacion('Error de Conexión', 'Ocurrió un error inesperado al procesar la copia de asignaciones.', 'error');
                });
            },

            confirmarEnviarSolicitudVicedecanato() {
                this.cargandoSolicitar = true;

                fetch('/designaciones/' + propuestaId + '/enviar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': CSRF_TOKEN,
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        gestion_id: gestionId,
                        periodo_id: periodoId
                    })
                })
                .then(r => r.json())
                .then(res => {
                    this.cargandoSolicitar = false;
                    if (res.success) {
                        this.modalSolicitarRevisionOpen = false;
                        this.mensajeExito = '¡Las designaciones docentes han sido enviadas a revisión por el Vicerrectorado exitosamente!';
                        this.modalExitoOpen = true;
                    } else {
                        this.mostrarNotificacion('Error al Enviar', res.error || 'Ocurrió un error al enviar la solicitud a revisión.', 'error');
                    }
                })
                .catch(() => {
                    this.cargandoSolicitar = false;
                    this.mostrarNotificacion('Error de Conexión', 'Ocurrió un error inesperado al procesar la solicitud.', 'error');
                });
            }
        };
    }
</script>
@endpush
@endsection
