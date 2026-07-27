@extends('layouts.app')

@section('title', 'Designación por Docente — ' . $carrera->nombre)

@section('content')
@php
    // Organizar datos de docentes y sus designaciones asignadas
    $docentesProcesados = [];
    foreach ($docentes as $d) {
        $desigDocente = $designaciones->filter(fn($des) => (string)$des->Id_docente === (string)$d['id']);
        $horasTotal = $desigDocente->sum(fn($des) => $des->grupo?->horas ?? 0);
        $materiasSiglas = $desigDocente->map(fn($des) => ($des->materia?->sigla ?? '') . ' (G' . ($des->grupo?->codigo ?? '') . ')')->filter()->values()->all();

        $estadoCarga = 'optimo';
        $estadoEtiqueta = 'Óptimo';
        $estadoColor = 'bg-emerald-100 text-emerald-800 border-emerald-200';

        if ($horasTotal == 0) {
            $estadoCarga = 'sin_asignacion';
            $estadoEtiqueta = 'Sin asignación';
            $estadoColor = 'bg-gray-100 text-gray-700 border-gray-200';
        } elseif ($horasTotal < 6) {
            $estadoCarga = 'bajo_minimo';
            $estadoEtiqueta = 'Bajo mínimo (< 6h)';
            $estadoColor = 'bg-amber-100 text-amber-800 border-amber-200';
        } elseif ($horasTotal > 32) {
            $estadoCarga = 'sobrecarga';
            $estadoEtiqueta = 'Sobrecarga (> 32h)';
            $estadoColor = 'bg-rose-100 text-rose-800 border-rose-200';
        }

        $docentesProcesados[] = [
            'id' => $d['id'],
            'nombre' => $d['nombre'],
            'carreraSigla' => $d['carreraSigla'] ?? $carrera->sigla,
            'horas' => $horasTotal,
            'materias' => $materiasSiglas,
            'estado' => $estadoCarga,
            'estadoEtiqueta' => $estadoEtiqueta,
            'estadoColor' => $estadoColor,
            'grupos_ids' => $desigDocente->pluck('Id_grupo')->toArray(),
        ];
    }
@endphp

<div x-data="{
    busqueda: '',
    perPage: 10,
    currentPage: 1,
    docenteSeleccionadoId: null,
    modalAbierta: false,
    docenteActual: null,
    gruposSeleccionados: [],
    
    // Todos los docentes procesados
    todosDocentes: {{ json_encode($docentesProcesados) }},
    // Todos los grupos/materias disponibles para asignar
    todosGrupos: {{ json_encode($roster->map(fn($r) => [
        'id' => $r['id'],
        'materia_id' => $r['materia']['id'],
        'materia_nombre' => $r['materia']['nombre'],
        'materia_sigla' => $r['materia']['sigla'],
        'codigo' => $r['codigo'],
        'horas' => $r['horas'],
        'docente_actual_id' => $r['designacion']['docente']['id'] ?? null,
    ])) }},

    get docentesFiltrados() {
        if (!this.busqueda.trim()) return this.todosDocentes;
        const q = this.busqueda.toLowerCase();
        return this.todosDocentes.filter(d => d.nombre.toLowerCase().includes(q) || d.carreraSigla.toLowerCase().includes(q));
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
        // Cargar los grupos asignados a este docente
        this.gruposSeleccionados = [...docente.grupos_ids];
        this.modalAbierta = true;
    },

    cerrarModal() {
        this.modalAbierta = false;
        this.docenteActual = null;
        this.gruposSeleccionados = [];
    },

    get totalHorasSeleccionadas() {
        if (!this.gruposSeleccionados.length) return 0;
        return this.todosGrupos
            .filter(g => this.gruposSeleccionados.includes(g.id))
            .reduce((sum, g) => sum + g.horas, 0);
    },

    toggleGrupo(grupoId) {
        if (this.gruposSeleccionados.includes(grupoId)) {
            this.gruposSeleccionados = this.gruposSeleccionados.filter(id => id !== grupoId);
        } else {
            this.gruposSeleccionados.push(grupoId);
        }
    }
}" class="space-y-4">

    <!-- Encabezado del Módulo -->
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="bg-[#00acac] text-white text-xs font-bold px-2 py-0.5 rounded">Carrera</span>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">{{ $carrera->nombre }} ({{ $carrera->sigla }})</h1>
            </div>
            <p class="text-xs text-gray-500 mt-0.5">Asignación de carga horaria docente — {{ $carrera->nombre }}</p>
        </div>

        <form method="GET" action="{{ route('designaciones.carrera', $carrera->id) }}" class="flex items-center gap-2 bg-white p-2 rounded-lg border border-gray-200 shadow-sm">
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
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M3 14h18M9 4v16m6-16v16" />
                </svg>
                <span>DataTable - Select Docentes</span>
            </div>
            <div class="flex items-center gap-1.5 opacity-80">
                <span class="w-2.5 h-2.5 rounded-full bg-amber-400 inline-block"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 inline-block"></span>
                <span class="w-2.5 h-2.5 rounded-full bg-rose-400 inline-block"></span>
            </div>
        </div>

        <!-- Top Controls Bar -->
        <div class="p-3.5 border-b border-gray-200/80 bg-white flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <select x-model="perPage" @change="currentPage = 1" class="border border-gray-300 rounded px-2 py-1 bg-white text-gray-700 shadow-sm focus:border-[#00acac] focus:ring-1 focus:ring-[#00acac] outline-none">
                    <option value="10">10</option>
                    <option value="25">25</option>
                    <option value="50">50</option>
                </select>
                <span class="text-gray-600 font-medium">registros por página</span>
            </div>

            <div class="flex items-center gap-2">
                <span class="text-gray-600 font-medium">Buscar docente:</span>
                <input type="text" x-model="busqueda" placeholder="Nombre o carrera..." class="border border-gray-300 rounded px-2.5 py-1 text-xs w-48 shadow-sm focus:border-[#00acac] focus:ring-1 focus:ring-[#00acac] outline-none">
            </div>
        </div>

        <!-- Tabla estilo Color Admin DataTable -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead>
                    <tr class="bg-[#f2f4f6] border-b border-gray-200 text-gray-800 font-bold uppercase text-[11px] tracking-wider">
                        <th class="py-3 px-3 text-center w-12 border-r border-gray-200/60">#</th>
                        <th class="py-3 px-4 border-r border-gray-200/60">Docente</th>
                        <th class="py-3 px-4 border-r border-gray-200/60">Materias / Grupos Asignados</th>
                        <th class="py-3 px-4 text-center w-36 border-r border-gray-200/60">Carga Horaria</th>
                        <th class="py-3 px-4 text-center w-36 border-r border-gray-200/60">Estado</th>
                        <th class="py-3 px-4 text-center w-32">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200/70 text-gray-700 font-medium">
                    <template x-if="docentesPaginados.length === 0">
                        <tr>
                            <td colspan="6" class="py-8 text-center text-gray-400 italic">No se encontraron docentes coincidentes.</td>
                        </tr>
                    </template>

                    <template x-for="(d, index) in docentesPaginados" :key="d.id">
                        <tr @click="docenteSeleccionadoId = (docenteSeleccionadoId === d.id ? null : d.id)" 
                            :class="docenteSeleccionadoId === d.id ? 'bg-[#fff9d6] transition-colors' : 'hover:bg-gray-50/80 transition-colors cursor-pointer'">
                            
                            <!-- Index -->
                            <td class="py-3 px-3 text-center font-bold text-gray-500 border-r border-gray-200/40" x-text="(currentPage - 1) * perPage + index + 1"></td>

                            <!-- Docente Profile -->
                            <td class="py-3 px-4 border-r border-gray-200/40">
                                <div class="flex items-center gap-3">
                                    <div class="h-9 w-9 rounded-full bg-[#2d353c] text-white font-bold text-xs flex items-center justify-center shrink-0 border border-white shadow-sm">
                                        <span x-text="d.nombre.charAt(0)"></span>
                                    </div>
                                    <div>
                                        <p class="font-bold text-gray-900 text-xs" x-text="d.nombre"></p>
                                        <p class="text-[10px] text-gray-400 font-normal">Origen: <span x-text="d.carreraSigla"></span></p>
                                    </div>
                                </div>
                            </td>

                            <!-- Materias Asignadas -->
                            <td class="py-3 px-4 border-r border-gray-200/40">
                                <div class="flex flex-wrap gap-1">
                                    <template x-if="d.materias.length === 0">
                                        <span class="text-gray-400 italic text-[11px]">Sin materias asignadas</span>
                                    </template>
                                    <template x-for="mat in d.materias" :key="mat">
                                        <span class="bg-blue-50 text-blue-800 border border-blue-200/80 px-2 py-0.5 rounded text-[10px] font-semibold" x-text="mat"></span>
                                    </template>
                                </div>
                            </td>

                            <!-- Carga Horaria Numérica Simple (Sin barra de progreso) -->
                            <td class="py-3 px-4 text-center border-r border-gray-200/40">
                                <span class="font-bold text-gray-900 text-sm tabular-nums" x-text="d.horas + ' hrs'"></span>
                                <span class="text-[10px] text-gray-400 block font-normal">Máx. 32 hrs</span>
                            </td>

                            <!-- Estado Badge -->
                            <td class="py-3 px-4 text-center border-r border-gray-200/40">
                                <span class="px-2.5 py-1 rounded-full text-[10px] font-bold border shadow-xs" :class="d.estadoColor" x-text="d.estadoEtiqueta"></span>
                            </td>

                            <!-- Acciones -->
                            <td class="py-3 px-4 text-center" @click.stop>
                                <button @click="abrirModalAsignacion(d)" 
                                        class="bg-[#00acac] hover:bg-[#008a8a] text-white text-[11px] font-bold px-3 py-1.5 rounded shadow-sm transition-all duration-150 flex items-center justify-center gap-1 mx-auto">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" />
                                    </svg>
                                    <span>Asignar</span>
                                </button>
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
        <div @click.away="cerrarModal()" class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-2xl overflow-hidden flex flex-col max-h-[90vh]">
            
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

            <!-- Modal Subheader: Muestra de Carga Horaria Numérica (Sin barra de progreso) -->
            <div class="bg-gray-50 border-b border-gray-200 px-5 py-3 flex items-center justify-between text-xs shrink-0">
                <div>
                    <span class="text-gray-500 font-medium">Carga horaria seleccionada:</span>
                    <span class="font-bold text-gray-900 text-sm ml-1 tabular-nums" x-text="totalHorasSeleccionadas + ' hrs'"></span>
                    <span class="text-gray-400 text-[11px] font-normal ml-1">(Límite máximo: 32 hrs)</span>
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
                                <th class="py-2.5 px-3 text-center w-20">Horas</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200/70 text-gray-700">
                            <template x-for="g in todosGrupos" :key="g.id">
                                <tr @click="toggleGrupo(g.id)" 
                                    :class="gruposSeleccionados.includes(g.id) ? 'bg-[#fff9d6] transition-colors cursor-pointer font-semibold' : 'hover:bg-gray-50 transition-colors cursor-pointer'">
                                    
                                    <!-- Checkbox -->
                                    <td class="py-2.5 px-3 text-center border-r border-gray-200/40" @click.stop>
                                        <input type="checkbox" 
                                               :checked="gruposSeleccionados.includes(g.id)" 
                                               @change="toggleGrupo(g.id)" 
                                               class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                                    </td>

                                    <!-- Materia -->
                                    <td class="py-2.5 px-4 border-r border-gray-200/40">
                                        <p class="font-bold text-gray-900" x-text="g.materia_nombre"></p>
                                        <p class="text-[10px] text-gray-400 font-normal" x-text="g.materia_sigla"></p>
                                    </td>

                                    <!-- Grupo -->
                                    <td class="py-2.5 px-3 text-center font-bold text-gray-800 border-r border-gray-200/40" x-text="'G' + g.codigo"></td>

                                    <!-- Horas -->
                                    <td class="py-2.5 px-3 text-center font-bold text-gray-900 tabular-nums" x-text="g.horas + ' hrs'"></td>
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

                <button @click="
                    // Guardar asignaciones para el docente actual
                    alert('Asignación actualizada para ' + docenteActual.nombre + ' (' + totalHorasSeleccionadas + ' hrs)');
                    cerrarModal();
                " class="px-5 py-2 bg-[#00acac] hover:bg-[#008a8a] text-white rounded text-xs font-bold shadow-md transition-colors flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Guardar Designación</span>
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
