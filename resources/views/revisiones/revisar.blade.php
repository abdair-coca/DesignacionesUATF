@extends('layouts.app')

@section('title', 'Detalle de Propuesta — ' . $revision['carrera_nombre'])

@section('content')
<div x-data="revisarSolicitud({{ json_encode($revision) }}, {{ json_encode($designaciones) }}, {{ json_encode($historialPrevio ?? []) }})" class="space-y-4 text-xs text-gray-800">
    
    <!-- TOP TOOLBAR ESTILO COLOR ADMIN EMAIL (Botones planos de acción arriba) -->
    <div class="bg-[#f0f3f8] border border-gray-200 rounded-lg p-3 flex flex-wrap items-center justify-between gap-3 shadow-xs">
        <div class="flex items-center gap-2">
            <!-- Botón Volver a la Bandeja -->
            <a href="{{ route('revisiones.pendientes') }}" 
               class="px-3.5 py-1.5 bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold rounded text-xs transition-colors flex items-center gap-1.5 shadow-2xs">
                <svg class="w-4 h-4 text-gray-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Volver a la Bandeja</span>
            </a>

            @if($revision['estado'] === 'pendiente')
                <!-- Acciones Principales Explícitas -->
                <button @click="aprobarPropuestaCompleta()" 
                        :disabled="cargando"
                        class="px-4 py-1.5 bg-[#00acac] hover:bg-[#008a8a] text-white font-extrabold rounded text-xs flex items-center gap-1.5 shadow-md transition-all disabled:opacity-50 cursor-pointer">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    <span>Aprobar Propuesta Completa</span>
                </button>

                <button @click="abrirModalRechazoGlobal()" 
                        :disabled="cargando"
                        class="px-3.5 py-1.5 bg-[#ff5b57] hover:bg-[#e04b48] text-white font-bold rounded text-xs flex items-center gap-1.5 shadow-2xs transition-colors cursor-pointer disabled:opacity-50">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                    <span>Devolver con Observaciones</span>
                </button>
            @endif
        </div>

        <div class="flex items-center gap-1">
            <a href="{{ route('revisiones.pendientes') }}" title="Volver a Bandeja" class="px-2.5 py-1 bg-white border border-gray-300 hover:bg-gray-100 font-bold rounded text-gray-600 shadow-2xs">✕ Salir sin cambiar estado</a>
        </div>
    </div>

    <!-- TARJETAS DE ESTADÍSTICAS Y RESUMEN (STATS TILES) -->
    <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
        <div class="bg-white p-3.5 rounded-lg border border-gray-200 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block">Cobertura Docente</span>
            <div class="flex items-center justify-between mt-1">
                <span class="text-2xl font-black text-gray-900 tabular-nums">{{ $stats['cobertura'] }}%</span>
                <span class="bg-emerald-100 text-emerald-800 text-[10px] font-bold px-2 py-0.5 rounded-full">100% Asignado</span>
            </div>
        </div>

        <div class="bg-white p-3.5 rounded-lg border border-gray-200 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block">Total Materias / Grupos</span>
            <div class="flex items-center justify-between mt-1">
                <span class="text-2xl font-black text-gray-900 tabular-nums">{{ $stats['total_grupos'] }}</span>
                <span class="text-xs font-semibold text-gray-600">Grupos Habilitados</span>
            </div>
        </div>

        <div class="bg-white p-3.5 rounded-lg border border-gray-200 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block">Total Horas Asignadas</span>
            <div class="flex items-center justify-between mt-1">
                <span class="text-2xl font-black text-gray-900 tabular-nums">{{ $stats['total_horas'] }} hrs</span>
                <span class="text-xs font-semibold text-gray-600">Carga Académica</span>
            </div>
        </div>

        <div class="bg-white p-3.5 rounded-lg border border-gray-200 shadow-xs">
            <span class="text-[11px] font-bold uppercase tracking-wider text-gray-500 block">Docentes Designados</span>
            <div class="flex items-center justify-between mt-1">
                <span class="text-2xl font-black text-gray-900 tabular-nums">{{ $stats['docentes'] }}</span>
                <span class="text-xs font-semibold text-gray-600">Plantel Docente</span>
            </div>
        </div>
    </div>

    <!-- MAIN EMAIL CONTAINER MINIMALISTA -->
    <div class="bg-white border border-gray-200 rounded-lg shadow-sm overflow-hidden p-6 space-y-5">
        
        <!-- Mail Subject / Title Header -->
        <div class="border-b border-gray-200 pb-3 flex flex-wrap items-center justify-between gap-3">
            <div>
                <span class="text-[10px] font-bold uppercase tracking-wider text-[#00acac] block mb-0.5">Propuesta Específica</span>
                <h1 class="text-lg font-bold text-gray-900 tracking-tight">
                    {{ $revision['descripcion'] }}
                </h1>
                <p class="text-xs text-gray-500 font-medium mt-0.5">
                    Carrera de {{ $revision['carrera_nombre'] }} ({{ $revision['carrera_sigla'] }}) &bull; Gestión {{ $revision['gestion_nombre'] }} — Periodo {{ $revision['periodo_nombre'] }}
                </p>
            </div>

            <div>
                @if($revision['estado'] === 'pendiente')
                    <span class="bg-amber-100 border border-amber-300 text-amber-800 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1.5 shadow-2xs">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        Pendiente de Revisión por Vicerrectorado
                    </span>
                @elseif($revision['estado'] === 'revisado')
                    <span class="bg-emerald-100 border border-emerald-300 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1.5 shadow-2xs">
                        <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        Aprobada / Oficializada
                    </span>
                @elseif($revision['estado'] === 'observado')
                    <span class="bg-rose-100 border border-rose-300 text-rose-800 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1.5 shadow-2xs">
                        Con Observaciones
                    </span>
                @else
                    <span class="bg-gray-100 border border-gray-300 text-gray-700 text-xs font-bold px-3 py-1 rounded-full">
                        Borrador / Propuesta
                    </span>
                @endif
            </div>
        </div>

        <!-- Mail Sender Header Info -->
        <div class="flex items-center justify-between gap-4 bg-gray-50/70 p-3 rounded-lg border border-gray-200/80">
            <div class="flex items-center gap-3">
                <div class="h-9 w-9 rounded-full bg-[#f59c1a] text-white font-bold text-sm flex items-center justify-center shadow-2xs shrink-0">
                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M10 9a3 3 0 100-6 3 3 0 000 6zm-7 9a7 7 0 1114 0H3z" clip-rule="evenodd" />
                    </svg>
                </div>

                <div>
                    <p class="text-xs font-bold text-gray-900">
                        Enviado por {{ $revision['solicitante'] }} <span class="font-normal text-gray-500">&lt;director.{{ strtolower($revision['carrera_sigla']) }}@uatf.edu.bo&gt;</span>
                    </p>
                    <p class="text-[11px] text-gray-500 font-medium mt-0.5">
                        <svg class="w-3.5 h-3.5 text-gray-400 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                        </svg>
                        Fecha de solicitud: {{ $revision['solicitado_en'] }}
                    </p>
                </div>
            </div>

            <div class="text-right text-[11px] text-gray-500">
                Para: <span class="text-gray-900 font-bold">Vicerrectorado UATF</span>
            </div>
        </div>

        <!-- SECCIÓN DE HISTORIAL DE OBSERVACIONES PREVIAS (SI EXISTEN) -->
        @if(!empty($historialPrevio) && count($historialPrevio) > 0)
            <div class="bg-amber-50/80 border border-amber-200 rounded-lg p-4 space-y-2">
                <div class="flex items-center gap-2 text-amber-900 font-bold text-xs">
                    <svg class="w-4 h-4 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                    <span>Historial de Envíos u Observaciones Previas para esta Carrera:</span>
                </div>
                <div class="divide-y divide-amber-200/60">
                    @foreach($historialPrevio as $hist)
                        <div class="py-2 text-[11px] text-amber-950 flex flex-wrap items-center justify-between gap-2">
                            <div>
                                <span class="font-bold">{{ $hist['descripcion'] }}</span>
                                <span class="text-amber-700 ml-1">({{ $hist['solicitado_en'] }})</span>
                                @if(!empty($hist['observaciones']))
                                    <p class="text-rose-800 italic mt-0.5">Motivo de observación anterior: "{{ $hist['observaciones'] }}"</p>
                                @endif
                            </div>
                            <div>
                                <span class="px-2 py-0.5 rounded text-[10px] font-bold border border-amber-300 bg-white text-amber-800 uppercase">
                                    {{ $hist['estado'] }}
                                </span>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        <!-- Flat Table of Designaciones (Foco Principal Minimalista) -->
        <div class="border border-gray-200 rounded-lg overflow-hidden">
            <div class="bg-[#2d353c] text-white px-4 py-2.5 flex items-center justify-between font-bold text-xs">
                <span>Detalle de Materias y Carga Docente Asignada</span>
                <span class="bg-[#00acac] text-white text-[10px] font-bold px-2 py-0.5 rounded" x-text="designaciones.length + ' materias registradas'"></span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs border-collapse">
                    <thead class="bg-[#f2f4f6] text-gray-800 font-bold border-b border-gray-200">
                        <tr>
                            <th class="py-2.5 px-3 text-center w-8 border-r border-gray-200">#</th>
                            <th class="py-2.5 px-4 border-r border-gray-200">Materia / Sigla</th>
                            <th class="py-2.5 px-3 text-center w-20 border-r border-gray-200">Grupo</th>
                            <th class="py-2.5 px-3 text-center w-24 border-r border-gray-200">Horas</th>
                            <th class="py-2.5 px-4 border-r border-gray-200">Docente Designado</th>
                            <th class="py-2.5 px-3 text-center w-36 border-r border-gray-200">Carga Horaria</th>
                            <th class="py-2.5 px-3 text-center w-36">Estado materia</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200/70 text-gray-700 font-medium">
                        <template x-for="(d, index) in designaciones" :key="d.id">
                            <tr class="hover:bg-gray-50 transition-colors" :class="d.es_sobrecarga ? 'bg-rose-50/50' : ''">
                                <td class="py-2.5 px-3 text-center font-bold text-gray-400 border-r border-gray-200/40" x-text="index + 1"></td>
                                
                                <td class="py-2.5 px-4 border-r border-gray-200/40">
                                    <span class="font-bold text-gray-900" x-text="d.materia_sigla"></span>
                                    <span class="text-gray-500 font-normal ml-1" x-text="d.materia_nombre"></span>
                                </td>

                                <td class="py-2.5 px-3 text-center border-r border-gray-200/40">
                                    <span class="bg-gray-100 border border-gray-300 font-bold px-2 py-0.5 rounded text-[10px]" x-text="'G' + d.grupo_codigo"></span>
                                </td>

                                <td class="py-2.5 px-3 text-center font-bold text-gray-900 border-r border-gray-200/40 tabular-nums" x-text="d.materia_horas + ' hrs'"></td>

                                <td class="py-2.5 px-4 border-r border-gray-200/40 font-bold text-gray-900">
                                    <span x-text="d.docente_nombre"></span>
                                </td>

                                <td class="py-2.5 px-3 text-center border-r border-gray-200/40">
                                    <template x-if="d.es_sobrecarga">
                                        <span class="bg-rose-100 text-rose-800 border border-rose-300 text-[10px] font-bold px-2 py-0.5 rounded-full inline-flex items-center gap-1" :title="'Total en UATF: ' + d.carga_global + ' hrs (> 32h máximo permitidas)'" x-text="'⚠️ Sobrecarga (' + d.carga_global + 'h)'">
                                        </span>
                                    </template>
                                    <template x-if="!d.es_sobrecarga && d.carga_global > 0">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="d.carga_global + ' hrs totales'">
                                        </span>
                                    </template>
                                    <template x-if="!d.docente_id">
                                        <span class="text-gray-400 text-[10px] italic">Sin docente</span>
                                    </template>
                                </td>

                                <td class="py-2.5 px-3 text-center">
                                    <template x-if="d.estado === 'aprobada'">
                                        <span class="bg-emerald-100 text-emerald-800 border border-emerald-300 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            ✓ Aprobada
                                        </span>
                                    </template>
                                    <template x-if="d.estado === 'rechazada'">
                                        <span class="bg-rose-100 text-rose-800 border border-rose-300 text-[10px] font-bold px-2 py-0.5 rounded-full" :title="d.motivo_rechazo">
                                            ✕ Rechazada
                                        </span>
                                    </template>
                                    <template x-if="d.estado === 'propuesta' || !d.estado">
                                        <span class="bg-amber-100 text-amber-800 border border-amber-300 text-[10px] font-bold px-2 py-0.5 rounded-full">
                                            Propuesta
                                        </span>
                                    </template>
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

    <!-- MODAL DEVOLVER CON OBSERVACIONES -->
    <div x-show="modalRechazoOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden text-left">
            <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between">
                <h3 class="font-bold text-xs tracking-tight">Devolver Propuesta con Observaciones</h3>
                <button @click="modalRechazoOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <div class="p-5 space-y-3">
                <p class="text-xs text-gray-600 font-medium">Por favor especifica las observaciones para el Director de Carrera:</p>
                <textarea x-model="motivoRechazo" 
                          rows="4" 
                          placeholder="Ejemplo: Se solicita reducir la carga del docente X debido a sobrecarga de horas..." 
                          class="w-full border border-gray-300 rounded p-2.5 text-xs text-gray-800 focus:ring-1 focus:ring-rose-500 focus:border-rose-500 outline-none"></textarea>
            </div>

            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex items-center justify-between">
                <button @click="modalRechazoOpen = false" class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded text-xs font-semibold">
                    Cancelar
                </button>
                <button @click="confirmarDevolverConObservaciones()" 
                        :disabled="!motivoRechazo.trim()"
                        class="px-5 py-2 bg-[#ff5b57] hover:bg-[#e04b48] text-white font-bold rounded text-xs shadow-md transition-colors disabled:opacity-50 cursor-pointer">
                    Devolver al Director
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
    function revisarSolicitud(revision, designacionesIniciales, historialPrevio) {
        return {
            revision: revision,
            cargando: false,
            modalRechazoOpen: false,
            modalExitoOpen: false,
            mensajeExito: '',
            motivoRechazo: '',
            historialPrevio: historialPrevio || [],
            designaciones: designacionesIniciales || [],

            aprobarPropuestaCompleta() {
                this.cargando = true;

                fetch('/revisiones/' + this.revision.id + '/completar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        decision: 'aprobar_todo'
                    })
                })
                .then(r => r.json())
                .then(res => {
                    this.cargando = false;
                    if (res.success) {
                        this.mensajeExito = `La propuesta "${this.revision.descripcion}" de la Carrera de ${this.revision.carrera_nombre} ha sido APROBADA Y OFICIALIZADA exitosamente.`;
                        this.modalExitoOpen = true;
                    } else {
                        alert(res.error || 'Ocurrió un error al aprobar la propuesta.');
                    }
                })
                .catch(() => {
                    this.cargando = false;
                    alert('Ocurrió un error inesperado al procesar la aprobación.');
                });
            },

            abrirModalRechazoGlobal() {
                this.motivoRechazo = '';
                this.modalRechazoOpen = true;
            },

            confirmarDevolverConObservaciones() {
                if (!this.motivoRechazo.trim()) return;

                this.cargando = true;
                this.modalRechazoOpen = false;

                fetch('/revisiones/' + this.revision.id + '/completar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ 
                        decision: 'observar',
                        observaciones: this.motivoRechazo.trim()
                    })
                })
                .then(r => r.json())
                .then(res => {
                    this.cargando = false;
                    if (res.success) {
                        this.mensajeExito = `La propuesta "${this.revision.descripcion}" ha sido devuelta al Director con las observaciones registradas.`;
                        this.modalExitoOpen = true;
                    } else {
                        alert(res.error || 'Ocurrió un error al registrar las observaciones.');
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
