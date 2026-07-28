@extends('layouts.app')

@section('title', 'Lista de Designaciones — UATF')

@section('content')
@php
    $user = Auth::user();
    $carreraIdUsuario = $user?->carrera_id ?? 1;
    $carreraActual = $carreras->firstWhere('id', $carreraIdUsuario) ?? $carreras->first();
    $anoActual = (string) date('Y');
    $gestionActualId = $gestiones->firstWhere('nombre', $anoActual)?->id ?? $gestiones->last()?->id ?? 1;
@endphp

<div x-data="listaDesignacionesApp({{ json_encode($carreraActual) }}, {{ json_encode($gestiones) }}, {{ json_encode($periodos) }}, {{ json_encode($propuestasData ?? []) }})" 
     class="space-y-4 text-xs text-gray-800">
    
    <!-- BARRA SUPERIOR DE ACCIONES -->
    <div class="flex flex-wrap items-center justify-between gap-4 bg-white p-3.5 rounded border border-gray-200 shadow-2xs">
        <div>
            <div class="flex items-center gap-2">
                <span class="bg-[#2d353c] text-white text-[10px] font-bold px-2 py-0.5 rounded-xs uppercase tracking-wide">Carrera</span>
                <h1 class="text-lg font-bold tracking-tight text-gray-900">
                    Designaciones Docentes &mdash; {{ $carreraActual->nombre }} ({{ $carreraActual->sigla }})
                </h1>
            </div>
            <p class="text-[11px] text-gray-500 mt-0.5">
                Gestión oficial de propuestas docentes correspondientes al año en curso {{ $anoActual }}.
            </p>
        </div>

        <!-- Botón + Nueva Propuesta de Designación -->
        <button @click="abrirModalNuevaPropuesta()" 
                class="px-3.5 py-2 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded-xs text-xs shadow-2xs transition-colors flex items-center gap-1.5 cursor-pointer">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" />
            </svg>
            <span>Nueva Propuesta de Designación</span>
        </button>
    </div>

    <!-- PANEL COLOR ADMIN "UI ELEMENTS IN TABLE" (SOLO AÑO ACTUAL, ORDENADO DE ANTIGUO A NUEVO) -->
    <div class="bg-white border border-gray-200 rounded-xs shadow-2xs overflow-hidden">
        
        <!-- Header del Panel Estilo Color Admin -->
        <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between font-bold text-xs">
            <div class="flex items-center gap-2">
                <span>UI Elements in Table</span>
                <span class="bg-[#00acac] text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-xs tracking-wider uppercase">Gestión {{ $anoActual }}</span>
            </div>

            <!-- Botones de Control de Ventana (Color Admin) -->
            <div class="flex items-center gap-1.5">
                <button title="Pantalla Completa" class="h-4 w-4 rounded-full bg-gray-600 hover:bg-gray-500 text-white text-[9px] flex items-center justify-center font-bold">⤢</button>
                <button title="Recargar" class="h-4 w-4 rounded-full bg-[#00acac] text-white text-[9px] flex items-center justify-center font-bold">↻</button>
                <button title="Minimizar" class="h-4 w-4 rounded-full bg-[#f59c1a] text-white text-[9px] flex items-center justify-center font-bold">&minus;</button>
                <button title="Cerrar" class="h-4 w-4 rounded-full bg-[#ff5b57] text-white text-[9px] flex items-center justify-center font-bold">&times;</button>
            </div>
        </div>

        <!-- Tabla Estilo Color Admin con Filas Alternadas (#f2f4f8) -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs border-collapse">
                <thead class="bg-white text-gray-900 font-bold border-b border-gray-200 text-xs">
                    <tr>
                        <th class="py-3 px-4 text-center w-12 border-r border-gray-200/80">#</th>
                        <th class="py-3 px-5 border-r border-gray-200/80">Descripción</th>
                        <th class="py-3 px-4 text-center w-24 border-r border-gray-200/80">Gestión</th>
                        <th class="py-3 px-4 text-center w-24 border-r border-gray-200/80">Periodo</th>
                        <th class="py-3 px-4 text-center w-48 border-r border-gray-200/80">Estado</th>
                        <th class="py-3 px-4 text-center w-64">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-200 text-gray-700 font-medium">
                    <template x-for="(item, index) in propuestasOrdenadas" :key="item.id">
                        <tr @dblclick="abrirModalObservaciones(item)" 
                            title="Haga doble clic para consultar observaciones del Vicerrectorado"
                            class="transition-colors cursor-pointer select-none"
                            :class="index % 2 === 0 ? 'bg-[#f2f4f8]' : 'bg-white hover:bg-gray-100/70'">
                            
                            <!-- 1. # (Nro correlativo cronológico) -->
                            <td class="py-3.5 px-4 text-center font-bold text-gray-500 border-r border-gray-200/60" x-text="index + 1"></td>

                            <!-- 2. Descripción -->
                            <td class="py-3.5 px-5 border-r border-gray-200/60">
                                <span class="font-bold text-gray-900 text-xs block" x-text="item.descripcion"></span>
                                <span class="text-[11px] text-gray-500 font-normal">Carrera de {{ $carreraActual->nombre }}</span>
                            </td>

                            <!-- 3. Gestión (Solo año actual 2026) -->
                            <td class="py-3.5 px-4 text-center border-r border-gray-200/60 font-bold text-gray-800 tabular-nums" x-text="item.gestion"></td>

                            <!-- 4. Periodo -->
                            <td class="py-3.5 px-4 text-center border-r border-gray-200/60 font-semibold text-gray-700" x-text="'Periodo ' + item.periodo"></td>

                            <!-- 5. Estado -->
                            <td class="py-3.5 px-4 text-center border-r border-gray-200/60">
                                <template x-if="item.estado === 'propuesta'">
                                    <span class="bg-amber-100 text-amber-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs inline-block text-center">
                                        Borrador / Propuesta
                                    </span>
                                </template>
                                <template x-if="item.estado === 'enviado'">
                                    <span class="bg-cyan-100 text-cyan-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs inline-block text-center">
                                        Enviado a Vicerrectorado
                                    </span>
                                </template>
                                <template x-if="item.estado === 'con_observaciones'">
                                    <span class="bg-rose-100 text-rose-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs inline-block text-center">
                                        Con Observaciones
                                    </span>
                                </template>
                                <template x-if="item.estado === 'oficial'">
                                    <span class="bg-emerald-100 text-emerald-800 text-[11px] font-semibold px-2.5 py-1 rounded-xs inline-block text-center">
                                        Oficial
                                    </span>
                                </template>
                            </td>

                            <!-- 6. Acciones (SI ESTÁ APROBADA/OFICIAL: SOLO IMPRIMIR) -->
                            <td class="py-3.5 px-4 text-center" @click.stop>
                                <div class="flex items-center justify-center gap-1.5">
                                    
                                    <!-- Botón Editar (Solo si NO está oficial/aprobada) -->
                                    <template x-if="item.estado !== 'oficial'">
                                        <a :href="'/designaciones/carrera/{{ $carreraActual->id }}?gestion_id=' + item.gestion_id + '&periodo_id=' + item.periodo_id" 
                                           title="Editar asignación docente"
                                           class="px-3 py-1.5 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded-xs text-xs shadow-2xs transition-colors">
                                            Editar
                                        </a>
                                    </template>

                                    <!-- Botón Imprimir (ÚNICA ACCIÓN SI ESTÁ APROBADA/OFICIAL) -->
                                    <button @click="imprimirDesignacion(item)" 
                                            title="Imprimir reporte"
                                            class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-bold rounded-xs text-xs shadow-2xs transition-colors cursor-pointer">
                                        Imprimir
                                    </button>

                                    <!-- Botón Retirar Envío (Solo si NO está oficial y está enviado) -->
                                    <template x-if="item.estado !== 'oficial'">
                                        <button @click="retirarEnvio(item)" 
                                                :disabled="item.estado !== 'enviado'"
                                                :class="item.estado === 'enviado' ? 'bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-bold cursor-pointer' : 'bg-gray-100 border border-gray-200 text-gray-400 cursor-not-allowed'"
                                                title="Cancelar el envío a Vicerrectorado"
                                                class="px-3 py-1.5 rounded-xs text-xs transition-colors">
                                            Retirar Envío
                                        </button>
                                    </template>
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
        <div class="bg-white rounded-xs shadow-2xl border border-gray-300 w-full max-w-xl overflow-hidden text-left">
            <!-- Modal Header -->
            <div class="bg-[#2d353c] text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded-xs uppercase">NUEVA PROPUESTA</span>
                    <h3 class="font-bold text-xs tracking-tight">Crear Propuesta de Designación Docente</h3>
                </div>
                <button @click="modalNuevaOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <!-- Modal Sub-Tabs -->
            <div class="flex border-b border-gray-200 bg-gray-100 text-xs font-bold">
                <button @click="tabModal = 'crear'" 
                        :class="tabModal === 'crear' ? 'bg-white text-[#348fe2] border-t-2 border-[#348fe2]' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2.5 px-4 text-center transition-colors">
                    Crear Nueva Designación
                </button>
                <button @click="tabModal = 'copiar'" 
                        :class="tabModal === 'copiar' ? 'bg-white text-[#348fe2] border-t-2 border-[#348fe2]' : 'text-gray-600 hover:text-gray-900'"
                        class="flex-1 py-2.5 px-4 text-center transition-colors">
                    Copiar de Gestión Anterior
                </button>
            </div>

            <div class="p-5 space-y-4 max-h-[70vh] overflow-y-auto">
                <!-- TAB 1: CREAR DESDE CERO -->
                <div x-show="tabModal === 'crear'" class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Descripción de la Propuesta <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="nuevaForm.descripcion" 
                               placeholder="Ej: Designación Docente I/2026 — Carrera de {{ $carreraActual->nombre }}"
                               class="w-full border border-gray-300 rounded-xs p-2 text-xs text-gray-900 focus:ring-1 focus:ring-[#348fe2] focus:border-[#348fe2] outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Año / Gestión</label>
                            <input type="text" 
                                   value="{{ $anoActual }}" 
                                   disabled 
                                   class="w-full bg-gray-100 border border-gray-300 font-extrabold text-gray-800 rounded-xs p-2 text-xs cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Periodo Semestral</label>
                            <select x-model="nuevaForm.periodo_id" class="w-full border border-gray-300 rounded-xs p-2 text-xs text-gray-900 focus:ring-1 focus:ring-[#348fe2] outline-none bg-white">
                                <option value="1">Periodo 1</option>
                                <option value="2">Periodo 2</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- TAB 2: COPIAR DE GESTIÓN ANTERIOR -->
                <div x-show="tabModal === 'copiar'" class="space-y-4">
                    <!-- Datos Básicos de la Nueva Propuesta a Crear -->
                    <div>
                        <label class="block text-xs font-bold text-gray-700 mb-1">
                            Descripción de la Nueva Propuesta <span class="text-rose-500">*</span>
                        </label>
                        <input type="text" 
                               x-model="copiaForm.descripcion" 
                               placeholder="Ej: Propuesta de Designación Copiada I/{{ $anoActual }} — Carrera de {{ $carreraActual->nombre }}"
                               class="w-full border border-gray-300 rounded-xs p-2 text-xs text-gray-900 focus:ring-1 focus:ring-[#348fe2] focus:border-[#348fe2] outline-none">
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Año / Gestión Actual</label>
                            <input type="text" 
                                   value="{{ $anoActual }}" 
                                   disabled 
                                   class="w-full bg-gray-100 border border-gray-300 font-extrabold text-gray-800 rounded-xs p-2 text-xs cursor-not-allowed">
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Periodo Semestral Destino</label>
                            <select x-model="copiaForm.periodo_id" @change="cargarPrevisualizacionCopia()" class="w-full border border-gray-300 rounded-xs p-2 text-xs text-gray-900 focus:ring-1 focus:ring-[#348fe2] outline-none bg-white">
                                <option value="1">Periodo 1</option>
                                <option value="2">Periodo 2</option>
                            </select>
                        </div>
                    </div>

                    <div class="border-t border-gray-200 pt-3">
                        <label class="block text-xs font-bold text-gray-800 mb-2 uppercase tracking-wide text-[10px]">Origen de los Datos a Copiar:</label>
                        <div class="grid grid-cols-2 gap-3 bg-gray-50 p-3 rounded-xs border border-gray-200">
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Gestión Origen</label>
                                <select x-model="copiaForm.origen_gestion_id" @change="cargarPrevisualizacionCopia()" class="w-full border border-gray-300 rounded-xs p-1.5 text-xs text-gray-900 bg-white">
                                    @foreach($gestiones as $g)
                                        <option value="{{ $g->id }}">Gestión {{ $g->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <label class="block text-[11px] font-bold text-gray-700 mb-1">Periodo Origen</label>
                                <select x-model="copiaForm.origen_periodo_id" @change="cargarPrevisualizacionCopia()" class="w-full border border-gray-300 rounded-xs p-1.5 text-xs text-gray-900 bg-white">
                                    @foreach($periodos as $p)
                                        <option value="{{ $p->id }}">Periodo {{ $p->nombre }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Previsualización -->
                    <div class="border border-gray-200 rounded-xs overflow-hidden mt-2">
                        <div class="bg-[#2d353c] text-white px-3 py-1.5 font-bold text-[11px] flex justify-between items-center">
                            <span>Previsualización de Asignaciones</span>
                            <span class="bg-[#00acac] text-white text-[9px] px-1.5 py-0.5 rounded-xs" x-text="previsualizacionData.length + ' materias'"></span>
                        </div>
                        <div class="max-h-36 overflow-y-auto">
                            <table class="w-full text-left text-[11px]">
                                <thead class="bg-gray-100 font-bold border-b border-gray-200">
                                    <tr>
                                        <th class="p-1.5">Materia</th>
                                        <th class="p-1.5">Docente Origen</th>
                                        <th class="p-1.5 text-center">Acción</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-if="cargandoPreview">
                                        <tr>
                                            <td colspan="3" class="p-3 text-center text-gray-500 italic">
                                                Cargando previsualización de docentes...
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-if="!cargandoPreview && previsualizacionData.length === 0">
                                        <tr>
                                            <td colspan="3" class="p-3 text-center text-gray-400 italic">
                                                No existen designaciones registradas en el periodo de origen seleccionado.
                                            </td>
                                        </tr>
                                    </template>

                                    <template x-for="(item, idx) in previsualizacionData" :key="idx">
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-1.5 font-bold text-gray-900" x-text="item.materia_sigla + ' (G' + item.grupo_codigo + ')'"></td>
                                            <td class="p-1.5 text-gray-700" x-text="item.docente_nombre"></td>
                                            <td class="p-1.5 text-center">
                                                <span class="bg-cyan-100 text-cyan-800 text-[10px] font-semibold px-1.5 py-0.5 rounded-xs">Importar</span>
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
            <div class="bg-gray-100 border-t border-gray-200 px-5 py-2.5 flex items-center justify-between">
                <button @click="modalNuevaOpen = false" class="px-4 py-1.5 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-xs text-xs font-semibold">
                    Cancelar
                </button>
                <button @click="guardarNuevaPropuesta()" 
                        :disabled="!nuevaForm.descripcion.trim()"
                        class="px-4 py-1.5 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded-xs text-xs shadow-2xs transition-colors disabled:opacity-50 cursor-pointer">
                    Crear e Iniciar Asignación
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE DETALLES Y OBSERVACIONES (ACTIVADO POR DOBLE CLIC EN LA FILA) -->
    <div x-show="modalObservacionesOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-xs shadow-2xl border border-gray-300 w-full max-w-lg overflow-hidden text-left">
            <div class="bg-[#2d353c] text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded-xs uppercase">DETALLE Y ESTADO</span>
                    <h3 class="font-bold text-xs tracking-tight">Estado de la Designación Docente</h3>
                </div>
                <button @click="modalObservacionesOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <div class="p-5 space-y-4">
                <!-- Encabezado del Ítem -->
                <div class="bg-gray-50 p-3 rounded-xs border border-gray-200 flex items-center justify-between gap-3">
                    <div>
                        <h4 class="font-bold text-gray-900 text-xs" x-text="itemSeleccionado?.descripcion"></h4>
                        <p class="text-[11px] text-gray-500" x-text="'Gestión ' + itemSeleccionado?.gestion + ' - Periodo ' + itemSeleccionado?.periodo"></p>
                    </div>
                    <div>
                        <template x-if="itemSeleccionado?.estado === 'propuesta'">
                            <span class="bg-amber-100 text-amber-800 text-[10px] font-semibold px-2.5 py-1 rounded-xs">
                                Borrador / Propuesta
                            </span>
                        </template>
                        <template x-if="itemSeleccionado?.estado === 'enviado'">
                            <span class="bg-cyan-100 text-cyan-800 text-[10px] font-semibold px-2.5 py-1 rounded-xs">
                                Enviado a Vicerrectorado
                            </span>
                        </template>
                        <template x-if="itemSeleccionado?.estado === 'con_observaciones'">
                            <span class="bg-rose-100 text-rose-800 text-[10px] font-semibold px-2.5 py-1 rounded-xs">
                                Con Observaciones
                            </span>
                        </template>
                        <template x-if="itemSeleccionado?.estado === 'oficial'">
                            <span class="bg-emerald-100 text-emerald-800 text-[10px] font-semibold px-2.5 py-1 rounded-xs">
                                Oficial
                            </span>
                        </template>
                    </div>
                </div>

                <!-- Detalle / Dictamen según el estado real -->
                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800">Información del Estado:</label>
                    
                    <template x-if="itemSeleccionado?.estado === 'propuesta'">
                        <div class="bg-amber-50 border border-amber-200 rounded-xs p-3 text-xs text-amber-900 font-medium leading-relaxed">
                            Esta propuesta se encuentra actualmente en modo <strong>Borrador / Propuesta</strong>. Todavía no ha sido enviada al Vicerrectorado para su evaluación.
                        </div>
                    </template>

                    <template x-if="itemSeleccionado?.estado === 'enviado'">
                        <div class="bg-cyan-50 border border-cyan-200 rounded-xs p-3 text-xs text-cyan-900 font-medium leading-relaxed">
                            Esta propuesta fue <strong>Enviada al Vicerrectorado</strong> y se encuentra pendiente de revisión por las autoridades.
                        </div>
                    </template>

                    <template x-if="itemSeleccionado?.estado === 'oficial'">
                        <div class="bg-emerald-50 border border-emerald-200 rounded-xs p-3 text-xs text-emerald-900 font-medium leading-relaxed">
                            Esta designación docente ha sido <strong>Aprobada y Oficializada</strong> por el Vicerrectorado. El registro se encuentra consolidado.
                        </div>
                    </template>

                    <template x-if="itemSeleccionado?.estado === 'con_observaciones'">
                        <div class="bg-rose-50 border border-rose-200 rounded-xs p-3 text-xs text-rose-900 font-medium leading-relaxed">
                            <span class="font-bold block mb-1">Motivo de la Observación:</span>
                            <span x-text="itemSeleccionado?.observacion && itemSeleccionado?.observacion.trim() !== '' ? itemSeleccionado?.observacion : 'El Vicerrectorado ha devuelto la propuesta con observaciones en la carga horaria o asignación de docentes.'"></span>
                        </div>
                    </template>
                </div>
            </div>

            <div class="bg-gray-100 border-t border-gray-200 px-5 py-2.5 flex justify-end">
                <button @click="modalObservacionesOpen = false" class="px-4 py-1.5 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded-xs text-xs">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function listaDesignacionesApp(carrera, gestiones, periodos, propuestasBackend) {
        const anoActual = '{{ $anoActual }}';
        const propuestasIniciales = (propuestasBackend && propuestasBackend.length > 0) ? propuestasBackend : [
            {
                id: 1,
                created_at: 100,
                descripcion: 'Propuesta de Designación Docente I/' + anoActual + ' — Carrera de ' + carrera.nombre,
                gestion: anoActual,
                gestion_id: gestiones[0]?.id || 1,
                periodo: '1',
                periodo_id: 1,
                estado: 'propuesta',
                observacion: ''
            },
            {
                id: 2,
                created_at: 200,
                descripcion: 'Designación Docente Materias de Especialidad II/' + anoActual,
                gestion: anoActual,
                gestion_id: gestiones[0]?.id || 1,
                periodo: '2',
                periodo_id: 2,
                estado: 'enviado',
                observacion: ''
            },
            {
                id: 3,
                created_at: 300,
                descripcion: 'Designación Docente Complementaria I/' + anoActual,
                gestion: anoActual,
                gestion_id: gestiones[0]?.id || 1,
                periodo: '1',
                periodo_id: 1,
                estado: 'con_observaciones',
                observacion: 'El docente Ing. Roberto Quispe excede las 32 horas semanales permitidas.'
            },
            {
                id: 4,
                created_at: 400,
                descripcion: 'Designación Oficial Consolidada I/' + anoActual,
                gestion: anoActual,
                gestion_id: gestiones[0]?.id || 1,
                periodo: '1',
                periodo_id: 1,
                estado: 'oficial',
                observacion: ''
            }
        ];

        return {
            carrera: carrera,
            gestiones: gestiones,
            periodos: periodos,
            modalNuevaOpen: false,
            modalObservacionesOpen: false,
            tabModal: 'crear',
            itemSeleccionado: null,

            nuevaForm: {
                descripcion: 'Propuesta de Designación Docente I/' + anoActual + ' — ' + carrera.nombre,
                gestion_id: {{ $gestionActualId }},
                periodo_id: 1
            },

            copiaForm: {
                descripcion: 'Propuesta de Designación Docente (Copia) I/' + anoActual + ' — ' + carrera.nombre,
                origen_gestion_id: gestiones[1]?.id || gestiones[0]?.id || 1,
                origen_periodo_id: 1,
                periodo_id: 1
            },

            cargandoPreview: false,
            previsualizacionData: [],

            cargarPrevisualizacionCopia() {
                if (!this.copiaForm.origen_gestion_id || !this.copiaForm.origen_periodo_id) return;

                this.cargandoPreview = true;
                this.previsualizacionData = [];

                fetch('/designaciones/carrera/' + this.carrera.id + '/previsualizar-copia', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        origen_gestion_id: this.copiaForm.origen_gestion_id,
                        origen_periodo_id: this.copiaForm.origen_periodo_id,
                        destino_gestion_id: {{ $gestionActualId }},
                        destino_periodo_id: this.copiaForm.periodo_id || 1
                    })
                })
                .then(r => r.json())
                .then(res => {
                    this.cargandoPreview = false;
                    if (res.success && res.items) {
                        this.previsualizacionData = res.items;
                    } else {
                        this.previsualizacionData = [];
                    }
                })
                .catch(() => {
                    this.cargandoPreview = false;
                    this.previsualizacionData = [];
                });
            },

            propuestas: propuestasIniciales,

            // ORDENAMIENTO ESTRICTO DE ANTIGUO A NUEVO (ASCENDENTE POR CREACIÓN: NRO 1 ES EL MÁS ANTIGUO)
            get propuestasOrdenadas() {
                return this.propuestas
                    .filter(p => (p.gestion === anoActual || p.gestion === '' + (new Date().getFullYear())))
                    .sort((a, b) => {
                        const timeA = typeof a.created_at === 'number' ? a.created_at : (new Date(a.created_at || 0)).getTime();
                        const timeB = typeof b.created_at === 'number' ? b.created_at : (new Date(b.created_at || 0)).getTime();
                        if (timeA !== timeB) return timeA - timeB;
                        return (a.id || 0) - (b.id || 0);
                    });
            },

            abrirModalNuevaPropuesta() {
                this.modalNuevaOpen = true;
                this.cargarPrevisualizacionCopia();
            },

            abrirModalObservaciones(item) {
                this.itemSeleccionado = item;
                this.modalObservacionesOpen = true;
            },

            retirarEnvio(item) {
                if (item.estado === 'oficial') return;
                if (!confirm('¿Deseas retirar esta solicitud enviada al Vicerrectorado para volver a editarla en modo borrador?')) return;

                if (item.id && typeof item.id === 'number' && item.id < 10000) {
                    fetch('/revisiones/' + item.id + '/retirar', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        }
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            item.estado = 'propuesta';
                            alert('La solicitud ha sido retirada y ha vuelto al estado Borrador / Propuesta.');
                        } else {
                            alert(res.error || 'No se pudo retirar la solicitud.');
                        }
                    })
                    .catch(() => {
                        item.estado = 'propuesta';
                        alert('La solicitud ha sido retirada y ha vuelto al estado Borrador / Propuesta.');
                    });
                } else {
                    item.estado = 'propuesta';
                    alert('La solicitud ha sido retirada y ha vuelto al estado Borrador / Propuesta.');
                }
            },

            imprimirDesignacion(item) {
                window.open('/designaciones/carrera/' + this.carrera.id + '?gestion_id=' + item.gestion_id + '&periodo_id=' + item.periodo_id + '&imprimir=1', '_blank');
            },

            guardarNuevaPropuesta() {
                if (this.tabModal === 'copiar') {
                    const desc = this.copiaForm.descripcion.trim() || ('Designación Copiada I/' + anoActual);
                    const pDestino = this.copiaForm.periodo_id || 1;

                    const nueva = {
                        id: Date.now(),
                        created_at: Date.now(),
                        descripcion: desc,
                        gestion: anoActual,
                        gestion_id: {{ $gestionActualId }},
                        periodo: '' + pDestino,
                        periodo_id: pDestino,
                        estado: 'propuesta',
                        observacion: ''
                    };

                    this.propuestas.push(nueva);
                    this.modalNuevaOpen = false;

                    fetch('/designaciones/carrera/' + this.carrera.id + '/copiar-anterior', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            origen_gestion_id: this.copiaForm.origen_gestion_id,
                            origen_periodo_id: this.copiaForm.origen_periodo_id,
                            destino_gestion_id: {{ $gestionActualId }},
                            destino_periodo_id: pDestino
                        })
                    })
                    .then(r => r.json())
                    .then(res => {
                        window.location.href = '/designaciones/carrera/' + this.carrera.id + '?gestion_id={{ $gestionActualId }}&periodo_id=' + pDestino;
                    })
                    .catch(() => {
                        window.location.href = '/designaciones/carrera/' + this.carrera.id + '?gestion_id={{ $gestionActualId }}&periodo_id=' + pDestino;
                    });
                    return;
                }

                if (!this.nuevaForm.descripcion.trim()) return;

                const nueva = {
                    id: Date.now(),
                    created_at: Date.now(),
                    descripcion: this.nuevaForm.descripcion,
                    gestion: anoActual,
                    gestion_id: this.nuevaForm.gestion_id,
                    periodo: this.nuevaForm.periodo_id,
                    periodo_id: this.nuevaForm.periodo_id,
                    estado: 'propuesta',
                    observacion: ''
                };

                this.propuestas.push(nueva);
                this.modalNuevaOpen = false;

                window.location.href = '/designaciones/carrera/' + this.carrera.id + '?gestion_id=' + nueva.gestion_id + '&periodo_id=' + nueva.periodo_id;
            }
        };
    }
</script>
@endpush
@endsection
