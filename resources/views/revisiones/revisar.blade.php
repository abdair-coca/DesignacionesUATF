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
                <!-- Botón Aprobar Todo (Local) -->
                <button @click="aprobarTodoLocal()" 
                        class="px-3 py-1.5 bg-[#348fe2] hover:bg-[#2a72b5] text-white font-bold rounded text-xs flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Aprobar Todo</span>
                </button>

                <!-- Botón Rechazar / Observar Todo (Local) -->
                <button @click="abrirModalRechazoGlobal()" 
                        class="px-3 py-1.5 bg-[#ff5b57] hover:bg-[#e04b48] text-white font-bold rounded text-xs flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                    <span>Observar Todo</span>
                </button>

                <!-- Botón Principal: TERMINAR REVISIÓN (Envío en lote al Decanato) -->
                <button @click="terminarRevision()" 
                        :disabled="cargando"
                        class="px-4 py-1.5 bg-[#00acac] hover:bg-[#008a8a] text-white font-extrabold rounded text-xs flex items-center gap-1.5 shadow-md transition-all disabled:opacity-50 cursor-pointer ml-2">
                    <svg x-show="!cargando" class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <svg x-show="cargando" class="w-4 h-4 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15" />
                    </svg>
                    <span x-text="cargando ? 'Guardando Revisión...' : 'Terminar Revisión'"></span>
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

    <!-- MAIN EMAIL CONTAINER MINIMALISTA -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden p-6 space-y-5">
        
        <!-- Mail Subject / Title Header -->
        <div class="border-b border-gray-200 pb-3 flex items-center justify-between">
            <h1 class="text-lg font-bold text-gray-900 tracking-tight">
                Propuesta de Designación Docente — {{ $revision['carrera_nombre'] }} ({{ $revision['carrera_sigla'] }})
            </h1>

            @if($revision['estado'] === 'pendiente')
                <span class="bg-amber-100 border border-amber-300 text-amber-800 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1.5 shadow-2xs">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    En Revisión Decanato
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

        <!-- Mail Sender Header Info (Icono Amarillo Avatar) -->
        <div class="flex items-center justify-between gap-4 bg-gray-50/70 p-3 rounded-lg border border-gray-200/80">
            <div class="flex items-center gap-3">
                <!-- Circular Avatar Icono Amarillo -->
                <div class="h-9 w-9 rounded-full bg-[#f59c1a] text-white font-bold text-sm flex items-center justify-center shadow-2xs shrink-0">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
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
                </div>
            </div>

            <div class="text-right text-[11px] text-gray-400">
                To: <span class="text-gray-700 font-semibold">vicedecanato@uatf.edu.bo</span>
            </div>
        </div>

        <!-- Flat Table of Designaciones (Foco Principal Minimalista) -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between font-bold text-xs">
                <span>Detalle de Materias y Docentes Designados</span>
                <span class="bg-[#00acac] text-white text-[10px] font-bold px-2 py-0.5 rounded" x-text="itemsModificadosCount > 0 ? itemsModificadosCount + ' evaluadas' : designaciones.length + ' materias'"></span>
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
                            <th class="py-2.5 px-3 text-center w-36">Acción Decanato</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200/70 text-gray-700 font-medium">
                        <template x-for="(d, index) in designaciones" :key="d.id">
                            <tr class="hover:bg-gray-50 transition-colors" :class="d.estado_local === 'aprobada' ? 'bg-emerald-50/40' : (d.estado_local === 'rechazada' ? 'bg-rose-50/40' : '')">
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
                                    <template x-if="d.estado_local === 'aprobada'">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">
                                            ✓ Aprobada
                                        </span>
                                    </template>
                                    <template x-if="d.estado_local === 'rechazada'">
                                        <span class="bg-rose-100 text-rose-800 border border-rose-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1" :title="d.motivo_local">
                                            ✕ Rechazada
                                        </span>
                                    </template>
                                    <template x-if="d.estado_local === 'propuesta' || !d.estado_local">
                                        <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold px-2.5 py-0.5 rounded-full inline-flex items-center gap-1">
                                            ⏱ Propuesta
                                        </span>
                                    </template>
                                </td>

                                <td class="py-2.5 px-3 text-center">
                                    @if($revision['estado'] === 'pendiente')
                                        <div class="flex items-center justify-center gap-1">
                                            <button @click="marcarAprobarLocal(d)" 
                                                    :class="d.estado_local === 'aprobada' ? 'bg-emerald-600 text-white font-black border-emerald-700' : 'bg-white text-emerald-700 border-gray-300 hover:bg-emerald-50'"
                                                    class="px-2 py-1 border font-bold rounded text-[10px] transition-colors cursor-pointer">
                                                Aprobar
                                            </button>
                                            <button @click="abrirModalRechazoIndividual(d)" 
                                                    :class="d.estado_local === 'rechazada' ? 'bg-rose-600 text-white font-black border-rose-700' : 'bg-white text-rose-700 border-gray-300 hover:bg-rose-50'"
                                                    class="px-2 py-1 border font-bold rounded text-[10px] transition-colors cursor-pointer">
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
    </div>

    <!-- BOTTOM TOOLBAR (Navegación al pie del mensaje) -->
    <div class="bg-[#f0f3f8] border border-gray-200 rounded-lg p-3 flex items-center justify-between shadow-xs">
        <a href="{{ route('revisiones.pendientes') }}" class="px-3 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded text-xs shadow-2xs">
            ← Volver a la Bandeja
        </a>

        @if($revision['estado'] === 'pendiente')
            <button @click="terminarRevision()" 
                    :disabled="cargando"
                    class="px-5 py-2 bg-[#00acac] hover:bg-[#008a8a] text-white font-black rounded text-xs shadow-md transition-all disabled:opacity-50 cursor-pointer flex items-center gap-2">
                <span>Terminar Revisión y Enviar al Decanato</span>
            </button>
        @endif

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
                <h3 class="font-bold text-xs tracking-tight" x-text="esRechazoGlobal ? 'Observar / Rechazar Solicitud Completa' : 'Motivo del Rechazo de Designación'"></h3>
                <button @click="modalRechazoOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <div class="p-5 space-y-3">
                <p class="text-xs text-gray-600 font-medium">Por favor especifica la observación o motivo del rechazo:</p>
                <textarea x-model="motivoRechazo" 
                          rows="4" 
                          placeholder="Escribe el motivo detallado..." 
                          class="w-full border border-gray-300 rounded p-2.5 text-xs text-gray-800 focus:ring-1 focus:ring-rose-500 focus:border-rose-500 outline-none"></textarea>
            </div>

            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex items-center justify-between">
                <button @click="modalRechazoOpen = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-semibold">
                    Cancelar
                </button>
                <button @click="confirmarRechazoLocal()" 
                        :disabled="!motivoRechazo.trim()"
                        class="px-5 py-2 bg-[#ff5b57] hover:bg-[#e04b48] text-white font-bold rounded text-xs shadow-md transition-colors disabled:opacity-50 cursor-pointer">
                    Aplicar Rechazo
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL DE ÉXITO FINALIZAR REVISIÓN -->
    <div x-show="modalExitoOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden text-center">
            <div class="bg-[#2d353c] text-white px-5 py-3 flex items-center justify-between">
                <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded">ÉXITO</span>
                <span class="font-bold text-xs">Vicedecanato UATF</span>
            </div>

            <div class="p-6 space-y-4">
                <div class="h-14 w-14 rounded-full bg-emerald-100 text-emerald-600 font-bold flex items-center justify-center mx-auto border-2 border-emerald-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
                <h3 class="font-bold text-gray-900 text-base">¡Revisión Finalizada Exitosamente!</h3>
                <p class="text-xs text-gray-600 font-medium" x-text="mensajeExito"></p>
            </div>

            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex justify-center">
                <button @click="modalExitoOpen = false; window.location.href='{{ route('revisiones.pendientes') }}';" 
                        class="px-6 py-2 bg-[#00acac] hover:bg-[#008a8a] text-white font-bold rounded-lg text-xs shadow-md transition-colors">
                    Aceptar y Volver a la Bandeja
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
            cargando: false,
            modalRechazoOpen: false,
            modalExitoOpen: false,
            mensajeExito: '',
            motivoRechazo: '',
            esRechazoGlobal: false,
            itemSeleccionado: null,

            designaciones: designacionesIniciales.map(d => ({
                ...d,
                estado_local: d.estado,
                motivo_local: d.motivo_rechazo || ''
            })),

            get itemsModificadosCount() {
                return this.designaciones.filter(d => d.estado_local === 'aprobada' || d.estado_local === 'rechazada').length;
            },

            marcarAprobarLocal(d) {
                d.estado_local = 'aprobada';
                d.motivo_local = '';
            },

            aprobarTodoLocal() {
                this.designaciones.forEach(d => {
                    d.estado_local = 'aprobada';
                    d.motivo_local = '';
                });
            },

            abrirModalRechazoGlobal() {
                this.esRechazoGlobal = true;
                this.motivoRechazo = '';
                this.modalRechazoOpen = true;
            },

            abrirModalRechazoIndividual(designacion) {
                this.esRechazoGlobal = false;
                this.itemSeleccionado = designacion;
                this.motivoRechazo = designacion.motivo_local || '';
                this.modalRechazoOpen = true;
            },

            confirmarRechazoLocal() {
                if (!this.motivoRechazo.trim()) return;

                if (this.esRechazoGlobal) {
                    this.designaciones.forEach(d => {
                        d.estado_local = 'rechazada';
                        d.motivo_local = this.motivoRechazo;
                    });
                } else if (this.itemSeleccionado) {
                    this.itemSeleccionado.estado_local = 'rechazada';
                    this.itemSeleccionado.motivo_local = this.motivoRechazo;
                }

                this.modalRechazoOpen = false;
            },

            terminarRevision() {
                if (!confirm('¿Deseas finalizar la revisión de esta carrera y guardar todas las decisiones en la base de datos?')) return;
                
                this.cargando = true;

                const acciones = this.designaciones.map(d => ({
                    id: d.id,
                    accion: d.estado_local === 'rechazada' ? 'rechazar' : 'aprobar',
                    motivo_rechazo: d.estado_local === 'rechazada' ? d.motivo_local : null
                }));

                fetch('/revisiones/' + this.revision.id + '/completar', {
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
                        this.mensajeExito = `La revisión de la propuesta de la Carrera de ${this.revision.carrera_nombre} fue registrada y finalizada con éxito.`;
                        this.modalExitoOpen = true;
                    } else {
                        alert(res.error || 'Ocurrió un error al completar la revisión.');
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
