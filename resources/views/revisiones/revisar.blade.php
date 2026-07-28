@extends('layouts.app')

@section('title', 'Detalle de Propuesta — ' . $revision['carrera_nombre'])

@section('content')
<div x-data="revisarSolicitud({{ json_encode($revision) }}, {{ json_encode($designaciones) }})" class="space-y-4 text-xs text-gray-800">
    
    <!-- TOP TOOLBAR ESTILO COLOR ADMIN EMAIL (Botones planos de acción arriba) -->
    <div class="bg-[#f0f3f8] border border-gray-200 rounded-lg p-3 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2">
            <!-- Botón Volver estilo Email Reply -->
            <a href="{{ route('revisiones.pendientes') }}" 
               class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded text-xs transition-colors flex items-center gap-1.5 shadow-2xs">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Volver a la Bandeja</span>
            </a>

            @if($revision['estado'] === 'pendiente')
                <!-- Botón Aprobar Todo -->
                <button @click="aprobarTodo()" 
                        :disabled="cargando"
                        class="px-3.5 py-1.5 bg-[#00acac] hover:bg-[#008a8a] text-white font-bold rounded text-xs flex items-center gap-1.5 shadow-2xs transition-colors disabled:opacity-50 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Aprobar Toda la Solicitud</span>
                </button>

                <!-- Botón Rechazar / Observar -->
                <button @click="abrirModalRechazoGlobal()" 
                        :disabled="cargando"
                        class="px-3.5 py-1.5 bg-[#ff5b57] hover:bg-[#e04b48] text-white font-bold rounded text-xs flex items-center gap-1.5 shadow-2xs transition-colors disabled:opacity-50 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Observar / Rechazar Con Motivo</span>
                </button>
            @endif
        </div>

        <!-- Botones de Navegación del Correo (↑ ↓ ✕) -->
        <div class="flex items-center gap-1">
            <a href="{{ route('revisiones.pendientes') }}" title="Anterior" class="px-2.5 py-1 bg-white border border-gray-300 hover:bg-gray-100 font-bold rounded text-gray-600 shadow-2xs">↑</a>
            <a href="{{ route('revisiones.pendientes') }}" title="Siguiente" class="px-2.5 py-1 bg-white border border-gray-300 hover:bg-gray-100 font-bold rounded text-gray-600 shadow-2xs">↓</a>
            <a href="{{ route('revisiones.pendientes') }}" title="Cerrar" class="px-2.5 py-1 bg-white border border-gray-300 hover:bg-gray-100 font-bold rounded text-gray-600 shadow-2xs">✕</a>
        </div>
    </div>

    <!-- MAIN EMAIL CONTAINER ESTILO COLOR ADMIN -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden p-6 space-y-6">
        
        <!-- Mail Subject / Title Header -->
        <div class="border-b border-gray-200 pb-4">
            <h1 class="text-xl font-bold text-gray-900 tracking-tight">
                Propuesta de Designación Docente — Carrera de {{ $revision['carrera_nombre'] }} ({{ $revision['carrera_sigla'] }})
            </h1>
        </div>

        <!-- Mail Sender Header Info (Icono Amarillo Avatar) -->
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-3">
                <!-- Circular Avatar (Estilo Icono Amarillo de la imagen) -->
                <div class="h-10 w-10 rounded-full bg-[#f59c1a] text-white font-bold text-base flex items-center justify-center shadow-2xs shrink-0">
                    <svg class="w-6 h-6" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>

                <div>
                    <p class="text-xs font-bold text-gray-900">
                        from {{ $revision['solicitante'] }} <span class="font-normal text-gray-500">&lt;director.{{ strtolower($revision['carrera_sigla']) }}@uatf.edu.bo&gt;</span>
                    </p>
                    <p class="text-[11px] text-gray-500 font-medium mt-0.5">
                        <svg class="w-3.5 h-3.5 text-gray-400 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Enviado el {{ $revision['solicitado_en'] }} &bull; Gestión {{ $revision['gestion_nombre'] }} (Periodo {{ $revision['periodo_nombre'] }})
                    </p>
                    <p class="text-[11px] text-gray-400 font-normal mt-0.5">
                        To: <span class="text-gray-700 font-medium">vicedecanato@uatf.edu.bo</span>
                    </p>
                </div>
            </div>

            <div>
                @if($revision['estado'] === 'pendiente')
                    <span class="bg-amber-100 border border-amber-300 text-amber-800 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1.5 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Pendiente de Revisión
                    </span>
                @else
                    <span class="bg-emerald-100 border border-emerald-300 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1.5 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Revisión Completada
                    </span>
                @endif
            </div>
        </div>

        <!-- ATTACHMENTS SECTION (Estilo exacto de las tarjetas PDF/Imagen adjuntas de la imagen) -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 pt-2">
            
            <!-- Attachment Card 1: Cobertura PDF -->
            <div class="border border-gray-200 rounded overflow-hidden bg-gray-50 flex flex-col justify-between">
                <div class="p-4 flex items-center justify-center bg-gray-100 border-b border-gray-200 min-h-[5.5rem]">
                    <div class="text-center space-y-1">
                        <svg class="w-10 h-10 text-[#348fe2] mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M4 4a2 2 0 012-2h4.586A2 2 0 0112 2.586L15.414 6A2 2 0 0116 7.414V16a2 2 0 01-2 2H6a2 2 0 01-2-2V4zm2 6a1 1 0 011-1h6a1 1 0 110 2H7a1 1 0 01-1-1zm1 3a1 1 0 100 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                        </svg>
                        <span class="bg-[#348fe2] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">PDF</span>
                    </div>
                </div>
                <div class="p-2.5 bg-gray-50 text-center">
                    <p class="font-bold text-gray-800 text-xs">Resumen_Cobertura.pdf</p>
                    <p class="text-[10px] text-gray-500 font-semibold mt-0.5" x-text="stats.cobertura + '% Cobertura (' + stats.grupos_asignados + '/' + stats.total_grupos + ' grupos)'"></p>
                </div>
            </div>

            <!-- Attachment Card 2: Carga Horaria JPG/Mockup -->
            <div class="border border-gray-200 rounded overflow-hidden bg-gray-50 flex flex-col justify-between">
                <div class="p-4 flex items-center justify-center bg-gray-100 border-b border-gray-200 min-h-[5.5rem]">
                    <div class="text-center space-y-1">
                        <svg class="w-10 h-10 text-[#00acac] mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M3 4a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1V4zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2zm0 6a1 1 0 011-1h12a1 1 0 011 1v2a1 1 0 01-1 1H4a1 1 0 01-1-1v-2z" clip-rule="evenodd" />
                        </svg>
                        <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">HOJA</span>
                    </div>
                </div>
                <div class="p-2.5 bg-gray-50 text-center">
                    <p class="font-bold text-gray-800 text-xs">Carga_Horaria_Total.pdf</p>
                    <p class="text-[10px] text-gray-500 font-semibold mt-0.5" x-text="stats.total_horas + ' hrs totales (' + stats.docentes + ' docentes)'"></p>
                </div>
            </div>

            <!-- Attachment Card 3: Plan Curricular -->
            <div class="border border-gray-200 rounded overflow-hidden bg-gray-50 flex flex-col justify-between">
                <div class="p-4 flex items-center justify-center bg-gray-100 border-b border-gray-200 min-h-[5.5rem]">
                    <div class="text-center space-y-1">
                        <svg class="w-10 h-10 text-[#727cb6] mx-auto" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M6 2a1 1 0 00-1 1v1H4a2 2 0 00-2 2v10a2 2 0 002 2h12a2 2 0 002-2V6a2 2 0 00-2-2h-1V3a1 1 0 10-2 0v1H7V3a1 1 0 00-1-1zm0 5a1 1 0 000 2h8a1 1 0 100-2H6z" clip-rule="evenodd" />
                        </svg>
                        <span class="bg-[#727cb6] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">PLAN</span>
                    </div>
                </div>
                <div class="p-2.5 bg-gray-50 text-center">
                    <p class="font-bold text-gray-800 text-xs">Plan_Estudios_{{ $revision['carrera_sigla'] }}.pdf</p>
                    <p class="text-[10px] text-gray-500 font-semibold mt-0.5">Gestión {{ $revision['gestion_nombre'] }} - Periodo {{ $revision['periodo_nombre'] }}</p>
                </div>
            </div>
        </div>

        <!-- Mail Text Body -->
        <div class="space-y-3 text-gray-700 leading-relaxed font-normal pt-2">
            <p>
                Estimada Autoridad del Vicedecanato,
            </p>
            <p>
                Se remite para su conocimiento y correspondiente homologación la propuesta oficial de designación de carga horaria docente para la carrera de <strong>{{ $revision['carrera_nombre'] }} ({{ $revision['carrera_sigla'] }})</strong>, correspondiente a la <strong>Gestión {{ $revision['gestion_nombre'] }} — Periodo {{ $revision['periodo_nombre'] }}</strong>.
            </p>
            <p>
                A continuación se detalla la lista de materias y grupos asignados a cada docente para su revisión individual o general:
            </p>
        </div>

        <!-- Flat Table of Designaciones (DataTable Select Style) -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between font-bold text-xs">
                <span>Detalle de Materias y Docentes Designados</span>
                <span class="bg-[#00acac] text-white text-[10px] font-bold px-2 py-0.5 rounded" x-text="designaciones.length + ' grupos en total'"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-[#f2f4f6] text-gray-800 font-bold border-b border-gray-200">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-8 border-r border-gray-200">#</th>
                            <th class="py-2.5 px-4 border-r border-gray-200">Materia / Sigla</th>
                            <th class="py-2.5 px-3 text-center w-20 border-r border-gray-200">Grupo</th>
                            <th class="py-2.5 px-4 border-r border-gray-200">Docente Designado</th>
                            <th class="py-2.5 px-3 text-center w-36 border-r border-gray-200">Estado Decanato</th>
                            <th class="py-2.5 px-3 text-center w-32">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200/70 text-gray-700 font-medium">
                        <template x-for="(d, index) in designaciones" :key="d.id">
                            <tr class="hover:bg-gray-50 transition-colors">
                                <td class="py-2.5 px-3 text-center font-bold text-gray-400 border-r border-gray-200/40" x-text="index + 1"></td>
                                
                                <td class="py-2.5 px-4 border-r border-gray-200/40">
                                    <span class="font-bold text-gray-900" x-text="d.materia_sigla"></span>
                                    <span class="text-gray-500 font-normal ml-1" x-text="d.materia_nombre"></span>
                                </td>

                                <td class="py-2.5 px-3 text-center border-r border-gray-200/40">
                                    <span class="bg-gray-100 border border-gray-300 font-bold px-2 py-0.5 rounded text-[10px]" x-text="'G' + d.grupo_codigo"></span>
                                </td>

                                <td class="py-2.5 px-4 border-r border-gray-200/40 font-bold text-gray-900">
                                    <span x-text="d.docente_nombre"></span>
                                </td>

                                <td class="py-2.5 px-3 text-center border-r border-gray-200/40">
                                    <template x-if="d.estado === 'aprobada'">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">
                                            ✓ Aprobada
                                        </span>
                                    </template>
                                    <template x-if="d.estado === 'rechazada'">
                                        <span class="bg-rose-100 text-rose-800 border border-rose-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1" :title="d.motivo_rechazo">
                                            ✕ Rechazada
                                        </span>
                                    </template>
                                    <template x-if="d.estado !== 'aprobada' && d.estado !== 'rechazada'">
                                        <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">
                                            ⏱ Propuesta
                                        </span>
                                    </template>
                                </td>

                                <td class="py-2.5 px-3 text-center">
                                    @if($revision['estado'] === 'pendiente')
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="cambiarEstadoIndividual(d.id, 'aprobar')" 
                                                    class="px-2 py-0.5 bg-emerald-50 text-emerald-700 border border-emerald-300 hover:bg-emerald-100 font-bold rounded text-[10px] transition-colors cursor-pointer">
                                                Aprobar
                                            </button>
                                            <button @click="abrirModalRechazoIndividual(d)" 
                                                    class="px-2 py-0.5 bg-rose-50 text-rose-700 border border-rose-300 hover:bg-rose-100 font-bold rounded text-[10px] transition-colors cursor-pointer">
                                                Rechazar
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-gray-400 text-[11px] italic font-normal">Revisión cerrada</span>
                                    @endif
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="pt-2 text-xs text-gray-500 font-medium">
            <p>Atentamente,</p>
            <p class="font-bold text-gray-900 mt-1">{{ $revision['solicitante'] }}</p>
            <p class="text-gray-500">Director de Carrera — {{ $revision['carrera_nombre'] }}</p>
        </div>
    </div>

    <!-- BOTTOM TOOLBAR (Navegación al pie del mensaje) -->
    <div class="bg-[#f0f3f8] border border-gray-200 rounded-lg p-3 flex items-center justify-between shadow-xs">
        <a href="{{ route('revisiones.pendientes') }}" class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded text-xs shadow-2xs">
            ← Volver a la Bandeja
        </a>

        <div class="flex items-center gap-1">
            <a href="{{ route('revisiones.pendientes') }}" title="Anterior" class="px-2.5 py-1 bg-white border border-gray-300 hover:bg-gray-100 font-bold rounded text-gray-600 shadow-2xs">↑</a>
            <a href="{{ route('revisiones.pendientes') }}" title="Siguiente" class="px-2.5 py-1 bg-white border border-gray-300 hover:bg-gray-100 font-bold rounded text-gray-600 shadow-2xs">↓</a>
            <a href="{{ route('revisiones.pendientes') }}" title="Cerrar" class="px-2.5 py-1 bg-white border border-gray-300 hover:bg-gray-100 font-bold rounded text-gray-600 shadow-2xs">✕</a>
        </div>
    </div>

    <!-- MODAL RECHAZO INDIVIDUAL / GLOBAL -->
    <div x-show="modalRechazoOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden text-left">
            <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between">
                <h3 class="font-bold text-xs tracking-tight" x-text="esRechazoGlobal ? 'Observar / Rechazar Solicitud Completa' : 'Rechazar Designación'"></h3>
                <button @click="modalRechazoOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <div class="p-5 space-y-3">
                <p class="text-xs text-gray-600 font-medium">Por favor especifica el motivo o la observación del rechazo:</p>
                <textarea x-model="motivoRechazo" 
                          rows="4" 
                          placeholder="Escribe el motivo detallado de la observación..." 
                          class="w-full border border-gray-300 rounded p-2.5 text-xs text-gray-800 focus:ring-1 focus:ring-rose-500 focus:border-rose-500 outline-none"></textarea>
            </div>

            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex items-center justify-between">
                <button @click="modalRechazoOpen = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-semibold">
                    Cancelar
                </button>
                <button @click="confirmarRechazo()" 
                        :disabled="!motivoRechazo.trim() || cargando"
                        class="px-5 py-2 bg-[#ff5b57] hover:bg-[#e04b48] text-white font-bold rounded text-xs shadow-md transition-colors disabled:opacity-50 cursor-pointer">
                    Confirmar Rechazo
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE ÉXITO -->
    <div x-show="modalExitoOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden text-center">
            <div class="bg-[#2d353c] text-white px-5 py-3 flex items-center justify-between">
                <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">ÉXITO</span>
                <span class="font-bold text-xs">Decanato UATF</span>
            </div>

            <div class="p-6 space-y-4">
                <div class="h-14 w-14 rounded-full bg-emerald-100 text-emerald-600 font-bold flex items-center justify-center mx-auto border-2 border-emerald-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 text-base">¡Operación Registrada Exitosamente!</h3>
                <p class="text-xs text-gray-600 font-medium" x-text="mensajeExito"></p>
            </div>

            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex justify-center">
                <button @click="modalExitoOpen = false; window.location.reload();" 
                        class="px-6 py-2 bg-[#00acac] hover:bg-[#008a8a] text-white font-bold rounded-lg text-xs shadow-md transition-colors">
                    Aceptar
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function revisarSolicitud(revision, designacionesIniciales) {
        return {
            revision: revision,
            designaciones: designacionesIniciales,
            cargando: false,
            modalRechazoOpen: false,
            modalExitoOpen: false,
            mensajeExito: '',
            motivoRechazo: '',
            esRechazoGlobal: false,
            designacionSeleccionadaId: null,
            stats: {{ json_encode($stats) }},

            aprobarTodo() {
                if (!confirm('¿Deseas aprobar todas las designaciones de esta carrera?')) return;
                
                const acciones = this.designaciones.map(d => ({
                    id: d.id,
                    accion: 'aprobar'
                }));

                this.enviarProcesamiento(acciones, 'Se han aprobado todas las designaciones de la carrera exitosamente.');
            },

            abrirModalRechazoGlobal() {
                this.esRechazoGlobal = true;
                this.motivoRechazo = '';
                this.modalRechazoOpen = true;
            },

            abrirModalRechazoIndividual(designacion) {
                this.esRechazoGlobal = false;
                this.designacionSeleccionadaId = designacion.id;
                this.motivoRechazo = '';
                this.modalRechazoOpen = true;
            },

            confirmarRechazo() {
                if (!this.motivoRechazo.trim()) return;

                let acciones = [];
                let msg = '';

                if (this.esRechazoGlobal) {
                    acciones = this.designaciones.map(d => ({
                        id: d.id,
                        accion: 'rechazar',
                        motivo_rechazo: this.motivoRechazo
                    }));
                    msg = 'La solicitud completa de esta carrera ha sido observada y rechazada.';
                } else {
                    acciones = [{
                        id: this.designacionSeleccionadaId,
                        accion: 'rechazar',
                        motivo_rechazo: this.motivoRechazo
                    }];
                    msg = 'La designación seleccionada ha sido rechazada.';
                }

                this.modalRechazoOpen = false;
                this.enviarProcesamiento(acciones, msg);
            },

            cambiarEstadoIndividual(designacionId, accion) {
                const acciones = [{
                    id: designacionId,
                    accion: accion
                }];
                const msg = 'La designación ha sido aprobada.';
                this.enviarProcesamiento(acciones, msg);
            },

            enviarProcesamiento(acciones, mensajeExitoCustom) {
                this.cargando = true;

                fetch('/revisiones/' + this.revision.id + '/procesar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ acciones: acciones })
                })
                .then(r => r.json())
                .then(res => {
                    this.cargando = false;
                    if (res.success) {
                        this.mensajeExito = mensajeExitoCustom;
                        this.modalExitoOpen = true;
                    } else {
                        alert(res.error || 'Ocurrió un error al procesar la revisión.');
                    }
                })
                .catch(() => {
                    this.cargando = false;
                    alert('Ocurrió un error inesperado al conectar con el servidor.');
                });
            }
        };
    }
</script>
@endpush
@endsection
