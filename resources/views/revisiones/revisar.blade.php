@extends('layouts.app')

@section('title', 'Detalle de Solicitud de Designación — UATF')

@section('content')
<div x-data="revisarSolicitud({{ json_encode($revision) }}, {{ json_encode($designaciones) }})" class="space-y-6">
    <!-- Header / Volver a Bandeja -->
    <div class="flex items-center justify-between bg-white p-4 rounded-lg border border-gray-200 shadow-sm">
        <div class="flex items-center gap-3">
            <a href="{{ route('revisiones.pendientes') }}" 
               class="p-2 bg-gray-100 hover:bg-gray-200 rounded-lg text-gray-700 transition-colors flex items-center gap-1 text-xs font-bold">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18" />
                </svg>
                <span>Volver a la Bandeja</span>
            </a>
            <div>
                <h1 class="text-base font-bold text-gray-900 leading-tight">
                    Solicitud de Designación: Carrera de {{ $revision['carrera_nombre'] }}
                </h1>
                <p class="text-xs text-gray-500 font-medium mt-0.5">
                    Gestión {{ $revision['gestion_nombre'] }} — Periodo {{ $revision['periodo_nombre'] }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            @if($revision['estado'] === 'pendiente')
                <span class="bg-amber-100 border border-amber-300 text-amber-800 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1.5 shadow-sm">
                    <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                    Pendiente de Revisión
                </span>
            @else
                <span class="bg-emerald-100 border border-emerald-300 text-emerald-800 text-xs font-bold px-3 py-1 rounded-full flex items-center gap-1.5 shadow-sm">
                    <svg class="w-3.5 h-3.5 text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                    Revisión Completada
                </span>
            @endif
        </div>
    </div>

    <!-- Cabecera del Mensaje estilo Lectura de Correo Gmail / Color Admin -->
    <div class="bg-white rounded-lg border border-gray-200 shadow-sm overflow-hidden">
        <div class="bg-[#2d353c] text-white p-5 border-b border-gray-800 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="h-10 w-10 rounded-full bg-[#00acac] text-white font-black text-base flex items-center justify-center border-2 border-white/20 shadow">
                    {{ strtoupper(substr($revision['carrera_sigla'] ?: 'C', 0, 1)) }}
                </div>
                <div>
                    <h2 class="font-bold text-sm text-white">Director de Carrera ({{ $revision['carrera_sigla'] }})</h2>
                    <p class="text-xs text-gray-300">{{ $revision['solicitante'] }} &bull; Enviado el {{ $revision['solicitado_en'] }}</p>
                </div>
            </div>
            <div class="text-right text-xs text-gray-300">
                <span>Para: <strong>Vicedecanato UATF</strong></span>
            </div>
        </div>

        <!-- Barra de Acciones Globales estilo Toolbar Inbox -->
        <div class="bg-[#f8f9fa] border-b border-gray-200 p-4 flex flex-wrap items-center justify-between gap-3 text-xs">
            <div class="flex items-center gap-2">
                <span class="font-bold text-gray-700">Resumen de Propuestas:</span>
                <span class="bg-gray-200 text-gray-800 font-bold px-2 py-0.5 rounded text-xs">
                    {{ count($designaciones) }} materias/grupos
                </span>
            </div>

            @if($revision['estado'] === 'pendiente')
                <div class="flex items-center gap-3">
                    <button @click="aprobarTodo()" 
                            :disabled="cargando"
                            class="bg-[#00acac] hover:bg-[#008a8a] text-white font-bold px-4 py-2 rounded-lg text-xs flex items-center gap-1.5 shadow transition-all disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                        <span>Aprobar Toda la Solicitud</span>
                    </button>

                    <button @click="abrirModalRechazoGlobal()" 
                            :disabled="cargando"
                            class="bg-rose-600 hover:bg-rose-700 text-white font-bold px-4 py-2 rounded-lg text-xs flex items-center gap-1.5 shadow transition-all disabled:opacity-50">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                        </svg>
                        <span>Observar / Rechazar Con Motivo</span>
                    </button>
                </div>
            @endif
        </div>

        <!-- Tabla de Designaciones estilo DataTable - Select -->
        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs font-medium text-gray-700 divide-y divide-gray-200">
                <thead class="bg-[#f2f4f6] text-gray-800 font-bold uppercase text-[11px] tracking-wider">
                    <tr>
                        <th class="px-4 py-3">Materia</th>
                        <th class="px-4 py-3">Grupo</th>
                        <th class="px-4 py-3">Docente Designado</th>
                        <th class="px-4 py-3 text-center">Estado Decanato</th>
                        <th class="px-4 py-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 bg-white">
                    <template x-for="(d, index) in designaciones" :key="d.id">
                        <tr class="hover:bg-[#fff9d6]/30 transition-colors">
                            <td class="px-4 py-3">
                                <span class="font-bold text-gray-900" x-text="d.materia_sigla"></span>
                                <span class="text-gray-500 font-normal ml-1" x-text="d.materia_nombre"></span>
                            </td>
                            <td class="px-4 py-3">
                                <span class="bg-gray-100 border border-gray-300 font-bold px-2 py-0.5 rounded text-[11px]" x-text="'Grupo ' + d.grupo_codigo"></span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex items-center gap-2">
                                    <div class="h-6 w-6 rounded-full bg-[#2d353c] text-white font-bold text-[10px] flex items-center justify-center shrink-0">
                                        <span x-text="d.docente_nombre.charAt(0)"></span>
                                    </div>
                                    <span class="font-semibold text-gray-800" x-text="d.docente_nombre"></span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-center">
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
                            <td class="px-4 py-3 text-right space-x-1">
                                @if($revision['estado'] === 'pendiente')
                                    <button @click="cambiarEstadoIndividual(d.id, 'aprobar')" 
                                            class="px-2.5 py-1 bg-emerald-50 text-emerald-700 border border-emerald-300 hover:bg-emerald-100 font-bold rounded text-[11px] transition-colors">
                                        Aprobar
                                    </button>
                                    <button @click="abrirModalRechazoIndividual(d)" 
                                            class="px-2.5 py-1 bg-rose-50 text-rose-700 border border-rose-300 hover:bg-rose-100 font-bold rounded text-[11px] transition-colors">
                                        Rechazar
                                    </button>
                                @else
                                    <span class="text-gray-400 font-semibold text-[11px]">Finalizado</span>
                                @endif
                            </td>
                        </tr>
                    </template>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Modal de Observación / Motivo de Rechazo estilo Color Admin v2 -->
    <div x-show="modalRechazoOpen" x-transition class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/60 backdrop-blur-sm">
        <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden">
            <!-- Header Modal -->
            <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between font-bold text-xs">
                <span>Registrar Motivo de Observación / Rechazo</span>
                <button @click="modalRechazoOpen = false" class="text-gray-400 hover:text-white">&times;</button>
            </div>

            <!-- Body Modal -->
            <div class="p-5 space-y-4 text-xs">
                <p class="text-gray-600">
                    Ingresa las observaciones o el motivo del rechazo para que el Director de Carrera realice las correcciones:
                </p>
                <div>
                    <label class="block font-bold text-gray-700 uppercase mb-1 text-[11px]">Motivo de Rechazo</label>
                    <textarea x-model="motivoTexto" rows="4" 
                              class="w-full p-3 border border-gray-300 rounded-lg text-xs focus:ring-2 focus:ring-rose-500 focus:border-rose-500 focus:outline-none"
                              placeholder="Ej: El docente supera la carga horaria máxima establecida..."></textarea>
                </div>
            </div>

            <!-- Footer Modal -->
            <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex justify-end gap-2 text-xs">
                <button @click="modalRechazoOpen = false" class="px-4 py-2 border border-gray-300 rounded-lg bg-white text-gray-700 hover:bg-gray-50 font-bold">
                    Cancelar
                </button>
                <button @click="confirmarRechazo()" class="px-4 py-2 bg-rose-600 hover:bg-rose-700 text-white font-bold rounded-lg shadow">
                    Confirmar Rechazo
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
            modoRechazo: 'global', // 'global' o 'individual'
            itemRechazoId: null,
            motivoTexto: '',

            aprobarTodo() {
                if (!confirm('¿Confirmas la aprobación de todas las designaciones de esta carrera?')) return;
                this.cargando = true;

                const acciones = this.designaciones.map(d => ({
                    id: d.id,
                    accion: 'aprobar',
                    motivo_rechazo: null
                }));

                fetch(`/revisiones/${this.revision.id}/completar`, {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    },
                    body: JSON.stringify({ acciones: acciones })
                })
                .then(r => r.json())
                .then(res => {
                    if (res.success) {
                        window.location.href = "{{ route('revisiones.pendientes') }}";
                    } else {
                        alert(res.error || 'Ocurrió un error');
                        this.cargando = false;
                    }
                })
                .catch(() => { this.cargando = false; });
            },

            cambiarEstadoIndividual(id, accion, motivo = null) {
                const item = this.designaciones.find(d => d.id === id);
                if (item) {
                    item.estado = accion === 'aprobar' ? 'aprobada' : 'rechazada';
                    item.motivo_rechazo = motivo;
                }
            },

            abrirModalRechazoGlobal() {
                this.modoRechazo = 'global';
                this.motivoTexto = '';
                this.modalRechazoOpen = true;
            },

            abrirModalRechazoIndividual(item) {
                this.modoRechazo = 'individual';
                this.itemRechazoId = item.id;
                this.motivoTexto = item.motivo_rechazo || '';
                this.modalRechazoOpen = true;
            },

            confirmarRechazo() {
                if (this.modoRechazo === 'individual') {
                    this.cambiarEstadoIndividual(this.itemRechazoId, 'rechazar', this.motivoTexto);
                    this.modalRechazoOpen = false;
                } else {
                    // Rechazo Global
                    this.cargando = true;
                    const acciones = this.designaciones.map(d => ({
                        id: d.id,
                        accion: 'rechazar',
                        motivo_rechazo: this.motivoTexto
                    }));

                    fetch(`/revisiones/${this.revision.id}/completar`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        },
                        body: JSON.stringify({ acciones: acciones })
                    })
                    .then(r => r.json())
                    .then(res => {
                        if (res.success) {
                            window.location.href = "{{ route('revisiones.pendientes') }}";
                        } else {
                            alert(res.error || 'Ocurrió un error');
                            this.cargando = false;
                        }
                    });
                }
            }
        }
    }
</script>
@endpush
@endsection
