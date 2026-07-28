@extends('layouts.app')

@section('title', 'Lista de Designaciones por Carrera — UATF')

@section('content')
@php
    $user = Auth::user();
    $carreraIdUsuario = $user?->carrera_id ?? 1;
    $carreraActual = $carreras->firstWhere('id', $carreraIdUsuario) ?? $carreras->first();
    $anoActual = (string) date('Y');
@endphp

<div x-data="listaDesignacionesApp({{ json_encode($carreraActual) }}, {{ json_encode($gestiones) }}, {{ json_encode($periodos) }})" 
     class="space-y-5 text-xs text-gray-800">
    
    <!-- HEADER CON NOMBRE DE LA CARRERA Y BOTÓN NUEVA PROPUESTA -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-4 rounded-lg border border-gray-200 shadow-xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="bg-[#2d353c] text-white text-xs font-bold px-2 py-0.5 rounded uppercase">Director de Carrera</span>
                <h1 class="text-xl font-bold tracking-tight text-gray-900">
                    Lista de Designaciones — {{ $carreraActual->nombre }} ({{ $carreraActual->sigla }})
                </h1>
            </div>
            <p class="text-xs text-gray-500 mt-0.5">
                Punto de entrada principal para crear, gestionar, retirar y consultar el estado de las propuestas docentes ante el Vicerrectorado.
            </p>
        </div>

        <!-- Botón + Nueva Propuesta de Designación -->
        <button @click="abrirModalNuevaPropuesta()" 
                class="px-4 py-2.5 bg-[#00acac] hover:bg-[#008a8a] text-white font-extrabold rounded-lg text-xs shadow-md transition-all flex items-center gap-2 cursor-pointer">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>+ Nueva Propuesta de Designación</span>
        </button>
    </div>

    <!-- TABLA DE DESIGNACIONES (BOCETO EN PAPEL: Nro, Descripción, Gestión, Periodo, Estado, Acciones) -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden">
        <div class="bg-[#2d353c] text-white px-4 py-3 flex items-center justify-between font-bold text-xs">
            <div class="flex items-center gap-2">
                <svg class="w-4 h-4 text-[#00acac]" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                </svg>
                <span>Propuestas de Designación Registradas</span>
            </div>
            <span class="text-[11px] text-gray-400 font-normal">
                💡 <span class="text-white font-semibold">Tip:</span> Haz doble clic sobre cualquier fila para ver las observaciones del Vicerrectorado.
            </span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-[#f2f4f6] text-gray-800 font-bold border-b border-gray-200 uppercase text-[11px]">
                    <tr>
                        <th class="py-3 px-3 text-center w-12 border-r border-gray-200">Nro</th>
                        <th class="py-3 px-4 border-r border-gray-200">Descripción</th>
                        <th class="py-3 px-3 text-center w-24 border-r border-gray-200">Gestión</th>
                        <th class="py-3 px-3 text-center w-24 border-r border-gray-200">Periodo</th>
                        <th class="py-3 px-4 text-center w-44 border-r border-gray-200">Estado</th>
                        <th class="py-3 px-4 text-center w-64">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-700 font-medium">
                    <template x-for="(item, index) in propuestas" :key="item.id">
                        <tr @dblclick="abrirModalObservaciones(item)" 
                            title="Doble clic para ver observaciones del Vicerrectorado"
                            class="hover:bg-teal-50/40 transition-colors cursor-pointer select-none"
                            :class="item.estado === 'oficial' ? 'bg-emerald-50/30' : (item.estado === 'con_observaciones' ? 'bg-rose-50/30' : '')">
                            
                            <!-- 1. Nro -->
                            <td class="py-3 px-3 text-center font-bold text-gray-400 border-r border-gray-200/60" x-text="index + 1"></td>

                            <!-- 2. Descripción -->
                            <td class="py-3 px-4 border-r border-gray-200/60">
                                <span class="font-bold text-gray-900 text-xs block" x-text="item.descripcion"></span>
                                <span class="text-[10px] text-gray-400 font-normal">Carrera de {{ $carreraActual->nombre }}</span>
                            </td>

                            <!-- 3. Gestión -->
                            <td class="py-3 px-3 text-center border-r border-gray-200/60 font-bold text-gray-800 tabular-nums" x-text="item.gestion"></td>

                            <!-- 4. Periodo -->
                            <td class="py-3 px-3 text-center border-r border-gray-200/60">
                                <span class="bg-gray-100 border border-gray-300 font-bold px-2.5 py-0.5 rounded text-[11px]" x-text="'P' + item.periodo"></span>
                            </td>

                            <!-- 5. Estado -->
                            <td class="py-3 px-4 text-center border-r border-gray-200/60">
                                <template x-if="item.estado === 'propuesta'">
                                    <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                        ⏱ Borrador / Propuesta
                                    </span>
                                </template>
                                <template x-if="item.estado === 'enviado'">
                                    <span class="bg-blue-100 text-blue-800 border border-blue-300 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                        📤 Enviado a Vicerrectorado
                                    </span>
                                </template>
                                <template x-if="item.estado === 'con_observaciones'">
                                    <span class="bg-rose-100 text-rose-800 border border-rose-300 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                        ⚠️ Con Observaciones
                                    </span>
                                </template>
                                <template x-if="item.estado === 'oficial'">
                                    <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2.5 py-1 rounded-full inline-flex items-center gap-1">
                                        🔒 Oficial
                                    </span>
                                </template>
                            </td>

                            <!-- 6. Acciones (ÚNICAMENTE 3 BOTONES: Editar, Imprimir, Retirar Envío) -->
                            <td class="py-3 px-4 text-center" @click.stop>
                                <div class="flex items-center justify-center gap-1.5">
                                    
                                    <!-- Botón 1: Editar (🟦) -->
                                    <a :href="'/designaciones/carrera/{{ $carreraActual->id }}?gestion_id=' + item.gestion_id + '&periodo_id=' + item.periodo_id" 
                                       title="Gestionar Asignación Docente"
                                       class="px-2.5 py-1.5 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded text-[11px] flex items-center gap-1 shadow-2xs transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                        </svg>
                                        <span>Editar</span>
                                    </a>

                                    <!-- Botón 2: Imprimir (🖨️) -->
                                    <button @click="imprimirDesignacion(item)" 
                                            title="Imprimir Reporte de Designación"
                                            class="px-2.5 py-1.5 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded text-[11px] flex items-center gap-1 shadow-2xs transition-colors cursor-pointer">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z" />
                                        </svg>
                                        <span>Imprimir</span>
                                    </button>

                                    <!-- Botón 3: Retirar Envío (🟧) -->
                                    <button @click="retirarEnvio(item)" 
                                            :disabled="item.estado !== 'enviado'"
                                            :class="item.estado === 'enviado' ? 'bg-[#f59c1a] hover:bg-[#d8840e] text-white opacity-100 cursor-pointer' : 'bg-gray-200 text-gray-400 opacity-50 cursor-not-allowed'"
                                            title="Cancelar el envío a Vicerrectorado"
                                            class="px-2.5 py-1.5 font-bold rounded text-[11px] flex items-center gap-1 shadow-2xs transition-colors">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6" />
                                        </svg>
                                        <span>Retirar Envío</span>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- MODAL "+ NUEVA PROPUESTA DE DESIGNACIÓN" -->
    <div x-show="modalNuevaOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-xl overflow-hidden text-left">
            <!-- Modal Header -->
            <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">NUEVA PROPUESTA</span>
                    <h3 class="font-bold text-xs tracking-tight">Crear Propuesta de Designación Docente</h3>
                </div>
                <button @click="modalNuevaOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <!-- Modal Sub-Tabs (Crear vs Copiar de Anterior) -->
            <div class="flex border-b border-gray-200 bg-gray-100 text-xs font-bold">
                <button @click="tabModal = 'crear'" 
                        :class="tabModal === 'crear' ? 'bg-white text-[#00acac] border-t-2 border-[#00acac]' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2.5 px-4 text-center transition-colors">
                    📝 Crear Nueva Designación
                </button>
                <button @click="tabModal = 'copiar'" 
                        :class="tabModal === 'copiar' ? 'bg-white text-[#00acac] border-t-2 border-[#00acac]' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2.5 px-4 text-center transition-colors">
                    📋 Copiar de Gestión Anterior
                </button>
            </div>

            <div class="p-6 space-y-4 max-h-[70vh] overflow-y-auto">
                
                <!-- TAB 1: CREAR DESDE CERO -->
                <div x-show="tabModal === 'crear'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Descripción / Título de la Propuesta <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="nuevaForm.descripcion" 
                               placeholder="Ej: Designación Docente I/2026 — Carrera de {{ $carreraActual->nombre }}"
                               class="w-full border border-gray-300 rounded p-2.5 text-xs text-gray-900 focus:ring-1 focus:ring-[#00acac] focus:border-[#00acac] outline-none">
                        <span class="text-[10px] text-gray-400 mt-1 block">Escribe una descripción descriptiva para identificar la propuesta.</span>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Año / Gestión <span class="text-gray-400 font-normal">(Año Actual)</span>
                            </label>
                            <input type="text" 
                                   value="{{ $anoActual }}" 
                                   disabled 
                                   class="w-full bg-gray-100 border border-gray-300 font-extrabold text-gray-800 rounded p-2.5 text-xs cursor-not-allowed">
                            <span class="text-[10px] text-amber-600 mt-1 block">🔒 Solo se permite crear designaciones en el año actual ({{ $anoActual }}).</span>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">
                                Periodo Semestral <span class="text-rose-500">*</span>
                            </label>
                            <select x-model="nuevaForm.periodo_id" class="w-full border border-gray-300 rounded p-2.5 text-xs text-gray-900 focus:ring-1 focus:ring-[#00acac] outline-none bg-white">
                                <option value="1">Periodo 1</option>
                                <option value="2">Periodo 2</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: COPIAR DE GESTIÓN ANTERIOR CON PREVISUALIZACIÓN -->
                <div x-show="tabModal === 'copiar'" class="space-y-4">
                    <div class="bg-blue-50 border border-blue-200 rounded p-3 text-blue-900 text-xs">
                        💡 Selecciona la gestión y periodo origen para previsualizar los docentes y su tipo de impacto antes de copiar.
                    </div>

                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Gestión Origen</label>
                            <select x-model="copiaForm.origen_gestion_id" @change="previsualizarCopia()" class="w-full border border-gray-300 rounded p-2 text-xs text-gray-900 bg-white">
                                @foreach($gestiones as $g)
                                    <option value="{{ $g->id }}">Gestión {{ $g->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Periodo Origen</label>
                            <select x-model="copiaForm.origen_periodo_id" @change="previsualizarCopia()" class="w-full border border-gray-300 rounded p-2 text-xs text-gray-900 bg-white">
                                @foreach($periodos as $p)
                                    <option value="{{ $p->id }}">Periodo {{ $p->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Tabla de Previsualización en Tiempo Real -->
                    <div class="border border-gray-200 rounded-lg overflow-hidden mt-3">
                        <div class="bg-gray-800 text-white px-3 py-2 font-bold text-[11px] flex justify-between items-center">
                            <span>Previsualización de Asignaciones a Copiar</span>
                            <span class="bg-[#00acac] text-white text-[9px] px-1.5 py-0.5 rounded" x-text="previsualizacionData.length + ' materias'"></span>
                        </div>
                        <div class="max-h-40 overflow-y-auto">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-gray-100 font-bold border-b border-gray-200">
                                    <tr>
                                        <th class="p-1.5">Materia</th>
                                        <th class="p-1.5">Docente Origen</th>
                                        <th class="p-1.5 text-center">Impacto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="item in previsualizacionData" :key="item.materia_sigla">
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-1.5 font-bold text-gray-900" x-text="item.materia_sigla + ' (G' + item.grupo_codigo + ')'"></td>
                                            <td class="p-1.5 text-gray-700" x-text="item.docente_nombre"></td>
                                            <td class="p-1.5 text-center">
                                                <span class="bg-emerald-100 text-emerald-800 text-[9px] font-bold px-1.5 py-0.5 rounded">Nueva asignación</span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex items-center justify-between">
                <button @click="modalNuevaOpen = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-semibold">
                    Cancelar
                </button>
                <button @click="guardarNuevaPropuesta()" 
                        :disabled="!nuevaForm.descripcion.trim()"
                        class="px-5 py-2 bg-[#00acac] hover:bg-[#008a8a] text-white font-extrabold rounded text-xs shadow-md transition-colors disabled:opacity-50 cursor-pointer">
                    Crear e Iniciar Asignación
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE OBSERVACIONES (ACTIVADO POR DOBLE CLIC EN LA FILA DE LA TABLA) -->
    <div x-show="modalObservacionesOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-lg overflow-hidden text-left">
            <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="bg-rose-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">OBSERVACIONES</span>
                    <h3 class="font-bold text-xs tracking-tight">Detalle de Revisión del Vicerrectorado</h3>
                </div>
                <button @click="modalObservacionesOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <div class="p-6 space-y-4">
                <div class="bg-gray-50 p-3 rounded border border-gray-200 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-gray-900 text-xs" x-text="itemSeleccionado?.descripcion"></h4>
                        <p class="text-[11px] text-gray-500" x-text="'Gestión ' + itemSeleccionado?.gestion + ' - Periodo ' + itemSeleccionado?.periodo"></p>
                    </div>
                    <span class="bg-rose-100 text-rose-800 border border-rose-300 text-[10px] font-bold px-2.5 py-1 rounded-full">
                        ⚠️ Con Observaciones
                    </span>
                </div>

                <div class="space-y-2">
                    <label class="block text-xs font-bold text-gray-800">Dictamen y Motivos de Observación:</label>
                    <div class="bg-rose-50/60 border border-rose-200/80 rounded-lg p-3 text-xs text-rose-900 font-medium leading-relaxed" 
                         x-text="itemSeleccionado?.observacion || 'Las asignaciones docentes correspondientes a materias de especialidad requieren revisión por exceder horas en carreras paralelas.'">
                    </div>
                </div>
            </div>

            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex justify-end">
                <button @click="modalObservacionesOpen = false" class="px-5 py-2 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded text-xs">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function listaDesignacionesApp(carrera, gestiones, periodos) {
        return {
            carrera: carrera,
            gestiones: gestiones,
            periodos: periodos,
            modalNuevaOpen: false,
            modalObservacionesOpen: false,
            tabModal: 'crear',
            itemSeleccionado: null,

            nuevaForm: {
                descripcion: 'Designación Docente I/' + (new Date().getFullYear()) + ' — ' + carrera.nombre,
                gestion_id: gestiones[0]?.id || 1,
                periodo_id: 1
            },

            copiaForm: {
                origen_gestion_id: gestiones[1]?.id || gestiones[0]?.id || 1,
                origen_periodo_id: 1
            },

            previsualizacionData: [
                { materia_sigla: 'INF-111', grupo_codigo: '1', docente_nombre: 'ING. CARLOS MENDOZA' },
                { materia_sigla: 'INF-121', grupo_codigo: '1', docente_nombre: 'ING. MARÍA GUTIÉRREZ' },
                { materia_sigla: 'INF-211', grupo_codigo: '1', docente_nombre: 'ING. JAVIER LOZA' }
            ],

            // LISTA MOCK DE PROPUESTAS REGISTRADAS DE LA CARRERA
            propuestas: [
                {
                    id: 1,
                    descripcion: 'Propuestas de Designación Docente I/2026 — Carrera de ' + carrera.nombre,
                    gestion: '2026',
                    gestion_id: gestiones[0]?.id || 1,
                    periodo: '1',
                    periodo_id: 1,
                    estado: 'propuesta',
                    observacion: ''
                },
                {
                    id: 2,
                    descripcion: 'Designación Docente Materias de Especialidad II/2026',
                    gestion: '2026',
                    gestion_id: gestiones[0]?.id || 1,
                    periodo: '2',
                    periodo_id: 2,
                    estado: 'enviado',
                    observacion: ''
                },
                {
                    id: 3,
                    descripcion: 'Designación Docente Complementaria I/2025',
                    gestion: '2025',
                    gestion_id: gestiones[1]?.id || 2,
                    periodo: '1',
                    periodo_id: 1,
                    estado: 'con_observaciones',
                    observacion: 'El docente Ing. Roberto Quispe excede las 32 horas semanales permitidas al sumar las clases asignadas en la carrera de Ingeniería Civil.'
                },
                {
                    id: 4,
                    descripcion: 'Designación Oficial Consolidada I/2024',
                    gestion: '2024',
                    gestion_id: gestiones[2]?.id || 3,
                    periodo: '1',
                    periodo_id: 1,
                    estado: 'oficial',
                    observacion: ''
                }
            ],

            abrirModalNuevaPropuesta() {
                this.modalNuevaOpen = true;
            },

            abrirModalObservaciones(item) {
                this.itemSeleccionado = item;
                this.modalObservacionesOpen = true;
            },

            previsualizarCopia() {
                // Simulación interactiva de previsualización
                console.log('Previsualizando copia desde la gestión ' + this.copiaForm.origen_gestion_id);
            },

            retirarEnvio(item) {
                if (confirm('¿Deseas retirar esta solicitud enviada al Vicerrectorado para volver a editarla en modo borrador?')) {
                    item.estado = 'propuesta';
                    alert('La solicitud ha sido retirada y ha vuelto al estado Borrador / Propuesta.');
                }
            },

            imprimirDesignacion(item) {
                alert('Generando vista previa de impresión para: ' + item.descripcion);
            },

            guardarNuevaPropuesta() {
                if (!this.nuevaForm.descripcion.trim()) return;

                const nueva = {
                    id: Date.now(),
                    descripcion: this.nuevaForm.descripcion,
                    gestion: '2026',
                    gestion_id: this.nuevaForm.gestion_id,
                    periodo: this.nuevaForm.periodo_id,
                    periodo_id: this.nuevaForm.periodo_id,
                    estado: 'propuesta',
                    observacion: ''
                };

                this.propuestas.unshift(nueva);
                this.modalNuevaOpen = false;

                // Redirigir a la pantalla de asignación por docente
                window.location.href = '/designaciones/carrera/' + this.carrera.id + '?gestion_id=' + nueva.gestion_id + '&periodo_id=' + nueva.periodo_id;
            }
        };
    }
</script>
@endpush
@endsection
