@extends('layouts.app')

@section('title', 'Lista de Designaciones — UATF')

@section('content')
@php
    $user = Auth::user();
    $carreraIdUsuario = $user?->carrera_id ?? 1;
    $carreraActual = $carreras->firstWhere('id', $carreraIdUsuario) ?? $carreras->first();
    $anoActual = (string) date('Y');
@endphp

<div x-data="listaDesignacionesApp({{ json_encode($carreraActual) }}, {{ json_encode($gestiones) }}, {{ json_encode($periodos) }})" 
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
                Gestión y consulta formal de propuestas de carga horaria docente para el Vicerrectorado.
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

    <!-- PANEL COLOR ADMIN "UI ELEMENTS IN TABLE" -->
    <div class="bg-white border border-gray-200 rounded-xs shadow-2xs overflow-hidden">
        
        <!-- Header del Panel Estilo Color Admin -->
        <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between font-bold text-xs">
            <div class="flex items-center gap-2">
                <span>UI Elements in Table</span>
                <span class="bg-[#00acac] text-white text-[9px] font-extrabold px-1.5 py-0.5 rounded-xs tracking-wider uppercase">UATF</span>
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
                    <template x-for="(item, index) in propuestas" :key="item.id">
                        <tr @dblclick="abrirModalObservaciones(item)" 
                            title="Haga doble clic para consultar detalles u observaciones del Vicerrectorado"
                            class="transition-colors cursor-pointer select-none"
                            :class="index % 2 === 0 ? 'bg-[#f2f4f8]' : 'bg-white hover:bg-gray-100/70'">
                            
                            <!-- 1. # (Nro) -->
                            <td class="py-3.5 px-4 text-center font-bold text-gray-500 border-r border-gray-200/60" x-text="index + 1"></td>

                            <!-- 2. Descripción -->
                            <td class="py-3.5 px-5 border-r border-gray-200/60">
                                <span class="font-bold text-gray-900 text-xs block" x-text="item.descripcion"></span>
                                <span class="text-[11px] text-gray-500 font-normal">Carrera de {{ $carreraActual->nombre }}</span>
                            </td>

                            <!-- 3. Gestión -->
                            <td class="py-3.5 px-4 text-center border-r border-gray-200/60 font-bold text-gray-800 tabular-nums" x-text="item.gestion"></td>

                            <!-- 4. Periodo -->
                            <td class="py-3.5 px-4 text-center border-r border-gray-200/60 font-semibold text-gray-700" x-text="'Periodo ' + item.periodo"></td>

                            <!-- 5. Estado (Estilo Imagen 2: Rectángulos suaves sin estar completamente redondeados, sin emojis) -->
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

                            <!-- 6. Acciones (ÚNICAMENTE 3 BOTONES: Editar, Imprimir, Retirar Envío) -->
                            <td class="py-3.5 px-4 text-center" @click.stop>
                                <div class="flex items-center justify-center gap-1.5">
                                    
                                    <!-- Botón 1: Editar (Botón azul rectangular sólido de la imagen 1) -->
                                    <a :href="'/designaciones/carrera/{{ $carreraActual->id }}?gestion_id=' + item.gestion_id + '&periodo_id=' + item.periodo_id" 
                                       title="Editar asignación docente"
                                       class="px-3 py-1.5 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded-xs text-xs shadow-2xs transition-colors">
                                        Editar
                                    </a>

                                    <!-- Botón 2: Imprimir (Botón blanco con borde plano) -->
                                    <button @click="imprimirDesignacion(item)" 
                                            title="Imprimir reporte"
                                            class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-bold rounded-xs text-xs shadow-2xs transition-colors cursor-pointer">
                                        Imprimir
                                    </button>

                                    <!-- Botón 3: Retirar Envío (Botón plano con borde) -->
                                    <button @click="retirarEnvio(item)" 
                                            :disabled="item.estado !== 'enviado'"
                                            :class="item.estado === 'enviado' ? 'bg-white border border-gray-300 hover:bg-gray-50 text-gray-800 font-bold cursor-pointer' : 'bg-gray-100 border border-gray-200 text-gray-400 cursor-not-allowed'"
                                            title="Cancelar el envío a Vicerrectorado"
                                            class="px-3 py-1.5 rounded-xs text-xs transition-colors">
                                        Retirar Envío
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
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Gestión Origen</label>
                            <select x-model="copiaForm.origen_gestion_id" class="w-full border border-gray-300 rounded-xs p-2 text-xs text-gray-900 bg-white">
                                @foreach($gestiones as $g)
                                    <option value="{{ $g->id }}">Gestión {{ $g->nombre }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Periodo Origen</label>
                            <select x-model="copiaForm.origen_periodo_id" class="w-full border border-gray-300 rounded-xs p-2 text-xs text-gray-900 bg-white">
                                @foreach($periodos as $p)
                                    <option value="{{ $p->id }}">Periodo {{ $p->nombre }}</option>
                                @endforeach
                            </select>
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
                                        <th class="p-1.5 text-center">Impacto</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    <template x-for="item in previsualizacionData" :key="item.materia_sigla">
                                        <tr class="hover:bg-gray-50">
                                            <td class="p-1.5 font-bold text-gray-900" x-text="item.materia_sigla + ' (G' + item.grupo_codigo + ')'"></td>
                                            <td class="p-1.5 text-gray-700" x-text="item.docente_nombre"></td>
                                            <td class="p-1.5 text-center">
                                                <span class="bg-cyan-100 text-cyan-800 text-[10px] font-semibold px-1.5 py-0.5 rounded-xs">Nueva asignación</span>
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

    <!-- MODAL DE OBSERVACIONES (ACTIVADO POR DOBLE CLIC EN LA FILA DE LA TABLA) -->
    <div x-show="modalObservacionesOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-xs shadow-2xl border border-gray-300 w-full max-w-lg overflow-hidden text-left">
            <div class="bg-[#2d353c] text-white px-4 py-3 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <span class="bg-rose-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded-xs uppercase">OBSERVACIONES</span>
                    <h3 class="font-bold text-xs tracking-tight">Detalle de Revisión del Vicerrectorado</h3>
                </div>
                <button @click="modalObservacionesOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <div class="p-5 space-y-3">
                <div class="bg-gray-50 p-3 rounded-xs border border-gray-200 flex items-center justify-between">
                    <div>
                        <h4 class="font-bold text-gray-900 text-xs" x-text="itemSeleccionado?.descripcion"></h4>
                        <p class="text-[11px] text-gray-500" x-text="'Gestión ' + itemSeleccionado?.gestion + ' - Periodo ' + itemSeleccionado?.periodo"></p>
                    </div>
                    <span class="bg-rose-100 text-rose-800 text-[10px] font-semibold px-2.5 py-1 rounded-xs">
                        Con Observaciones
                    </span>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-xs font-bold text-gray-800">Dictamen del Vicerrectorado:</label>
                    <div class="bg-rose-50 border border-rose-200 rounded-xs p-3 text-xs text-rose-900 font-medium leading-relaxed" 
                         x-text="itemSeleccionado?.observacion || 'Las asignaciones docentes corresponden a materias de especialidad que excede el límite de horas.'">
                    </div>
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

            propuestas: [
                {
                    id: 1,
                    descripcion: 'Propuesta de Designación Docente I/2026 — Carrera de ' + carrera.nombre,
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

                window.location.href = '/designaciones/carrera/' + this.carrera.id + '?gestion_id=' + nueva.gestion_id + '&periodo_id=' + nueva.periodo_id;
            }
        };
    }
</script>
@endpush
@endsection
