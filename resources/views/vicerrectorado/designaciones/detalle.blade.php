@extends('layouts.app')

@include('vicerrectorado.designaciones._styles')

@push('styles')
    <link href="{{ asset('resources/assets/css/shared/designaciones-detalle.css') }}" rel="stylesheet">
@endpush

@section('title', 'Detalle de designación — Vicerrectorado')

@section('content')
    @php($rutaLista = route('vicerrectorado.designaciones.index', ['gestion' => $gestion] + (($carrera ?? '') !== '' ? ['carrera' => $carrera] : [])))
    <div class="designaciones-detail vicerrectorado-designaciones max-w-6xl mx-auto space-y-4 text-sm text-gray-800" x-data="vicerrectoradoRevision({ filas: @js($filas->pluck('id')->all()), decisiones: @js($decisionesIniciales ?? []), decisionesDisponibles: @js($decisionesDisponibles ?? false), estadosDisponibles: @js($estadosDisponibles ?? false), guardarUrl: @js($asignacion ? route('vicerrectorado.designaciones.decisiones', ['id' => $asignacion['id'], 'gestion' => $gestion]) : ''), estadoDesignacion: @js($asignacion['estado'] ?? 'SOLICITADO'), observacionRevision: @js($observacionRevision ?? ''), guardarRevisionUrl: @js($asignacion ? route('vicerrectorado.designaciones.estado', ['id' => $asignacion['id'], 'gestion' => $gestion]) : '') })">
        <ol class="breadcrumb no-print">
            <li class="breadcrumb-item"><a href="{{ $rutaLista }}">Designaciones universitarias</a></li>
            <li class="breadcrumb-item active">Detalle de designación</li>
        </ol>

        <header class="print-card detail-header">
            <div>
                <h1 class="page-header"><span class="material-icons" aria-hidden="true">fact_check</span> Detalle de designación</h1>
                @if($asignacion)
                    <p class="detail-subtitle">{{ $asignacion['programa_nombre'] ?: 'Carrera' }} ({{ $asignacion['programa_codigo'] ?: 'Sin código' }}) · Revisión de designación · Fecha: {{ $asignacion['fecha'] ?: 'Sin fecha' }}</p>
                @else
                    <p class="detail-subtitle">Vicerrectorado · Consulta de solo lectura</p>
                @endif
            </div>
            <div class="no-print detail-actions">
                <a href="{{ $rutaLista }}" class="detail-btn detail-btn-secondary">Volver</a>
                @if($asignacion)
                    <a href="{{ route('vicerrectorado.designaciones.pdf', ['id' => $asignacion['id'], 'gestion' => $gestion] + (($carrera ?? '') !== '' ? ['carrera' => $carrera] : [])) }}" target="_blank" rel="noopener" title="Imprimir designación" aria-label="Imprimir designación" class="detail-btn detail-btn-dark"><span class="material-icons" aria-hidden="true">print</span></a>
                @endif
            </div>
        </header>

        @if($errorJachasun ?? null)
            <div class="alert alert-danger" role="alert">
                <strong>No se puede cargar el detalle.</strong>
                <div>{{ $errorJachasun }}</div>
            </div>
        @elseif($asignacion)
            <section class="print-card panel panel-inverse">
                <div class="panel-heading">
                    <h2 class="panel-title">Información de la designación</h2>
                </div>
                <div class="detail-summary">
                    <div class="detail-summary-item"><p class="detail-label">Descripción</p><p class="detail-value">{{ $asignacion['detalle'] ?: '-' }}</p></div>
                    <div class="detail-summary-item"><p class="detail-label">Gestión / Periodo</p><p class="detail-value">{{ $asignacion['gestion'] ?: '-' }} / {{ $asignacion['periodo'] ?: '-' }}</p></div>
                    <div class="detail-summary-item"><p class="detail-label">Estado</p><p class="detail-value"><span x-text="estadoDesignacion">{{ $asignacion['estado'] ?: 'Sin estado' }}</span></p></div>
                </div>
                @if($asignacion['observacion'] ?? null)
                    <div class="detail-observation"><strong>Observación:</strong> {{ $asignacion['observacion'] }}</div>
                @endif
                <div x-show="estadoDesignacion === 'OBSERVADA' && observacionRevision" x-cloak class="detail-observation">
                    <strong>Observación de Vicerrectorado:</strong>
                    <span x-text="observacionRevision"></span>
                </div>
                @if($errorRevisionGeneral ?? null)
                    <div class="alert alert-warning" role="status">{{ $errorRevisionGeneral }}</div>
                @endif
                <div class="no-print detail-review-actions">
                    <button type="button" @click="abrirRevisionDesignacion('APROBADO')" :disabled="guardandoDecision || guardandoEstadoDesignacion" class="detail-btn detail-btn-success">Aprobar designación</button>
                    <button type="button" @click="abrirRevisionDesignacion('OBSERVADA')" :disabled="guardandoDecision || guardandoEstadoDesignacion" class="detail-btn detail-btn-danger">Observar designación</button>
                </div>
            </section>

            <section class="print-card panel panel-inverse">
                <div class="panel-heading">
                    <h2 class="panel-title">Asignaciones registradas</h2>
                </div>
                <div class="panel-body">
                    @if($errorDecision ?? null)
                        <div class="alert alert-danger" role="alert">{{ $errorDecision }}</div>
                    @endif
                    <div class="detail-table-wrap">
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th scope="col" class="no-print text-center">
                                        <input type="checkbox" @change="seleccionarTodas($event.target.checked)" :checked="todasSeleccionadas()" :disabled="!decisionesDisponibles || revisionCerrada || guardandoEstadoDesignacion || filas.length === 0" aria-label="Seleccionar todas las asignaciones">
                                    </th>
                                    <th>Docente</th>
                                    <th>CI</th>
                                    <th>Materia</th>
                                    <th>Grupo</th>
                                    <th class="text-right">Teóricas</th>
                                    <th class="text-right">Prácticas</th>
                                    <th class="text-right">Laboratorio</th>
                                    <th>Estado</th>
                                    <th class="no-print text-right">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($filas as $fila)
                                    <tr>
                                        <td class="no-print text-center">
                                            <input type="checkbox" @change="alternarSeleccion({{ (int) $fila['id'] }}, $event.target.checked)" :checked="filasSeleccionadas.includes({{ (int) $fila['id'] }})" :disabled="!decisionesDisponibles || revisionCerrada || guardandoEstadoDesignacion" aria-label="Seleccionar asignación de {{ $fila['docente_nombre'] ?: 'docente sin nombre' }}">
                                        </td>
                                        <td>{{ $fila['docente_nombre'] ?: '-' }}</td>
                                        <td>{{ $fila['ci'] ?: '-' }}</td>
                                        <td>{{ $fila['materia_sigla'] ?: '-' }} — {{ $fila['materia_nombre'] ?: '-' }}</td>
                                        <td>{{ $fila['grupo_id'] ?: '-' }}</td>
                                        <td class="text-right">{{ $fila['horas_teoricas'] ?? '-' }}</td>
                                        <td class="text-right">{{ $fila['horas_practicas'] ?? '-' }}</td>
                                        <td class="text-right">{{ $fila['horas_laboratorio'] ?? '-' }}</td>
                                        <td>
                                            <button
                                                type="button"
                                                class="badge detail-status-badge"
                                                :class="claseEstadoFila({{ (int) $fila['id'] }})"
                                                @click="mostrarEstadoFila({{ (int) $fila['id'] }})"
                                                :aria-label="'Ver estado: ' + etiquetaEstadoFila({{ (int) $fila['id'] }})"
                                                title="Ver estado de la asignación"
                                            ><strong x-text="etiquetaEstadoFila({{ (int) $fila['id'] }})">{{ $estadosDisponibles ? ($fila['estado_decision'] ?: 'Pendiente') : 'Sin estado' }}</strong></button>
                                        </td>
                                        <td class="no-print text-right">
                                            <div class="detail-row-actions">
                                                <button type="button" @click="abrirDecision({{ (int) $fila['id'] }}, 'APROBADA')" :disabled="!decisionesDisponibles || revisionCerrada || guardandoDecision || guardandoEstadoDesignacion" title="Aceptar asignación" aria-label="Aceptar asignación" class="detail-btn detail-btn-success detail-btn-icon">
                                                    <span class="material-icons" aria-hidden="true">check_circle</span>
                                                </button>
                                                <button type="button" @click="abrirDecision({{ (int) $fila['id'] }}, 'RECHAZADA')" :disabled="!decisionesDisponibles || revisionCerrada || guardandoDecision || guardandoEstadoDesignacion" title="Rechazar asignación" aria-label="Rechazar asignación" class="detail-btn detail-btn-danger detail-btn-icon">
                                                    <span class="material-icons" aria-hidden="true">cancel</span>
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="10" class="detail-empty">No existen filas de detalle para esta designación.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="no-print detail-bulk-actions">
                        <p><strong x-text="filasSeleccionadas.length"></strong> filas seleccionadas</p>
                        <div class="detail-row-actions">
                            <button type="button" @click="abrirDecisionSeleccionadas('APROBADA')" :disabled="!decisionesDisponibles || revisionCerrada || guardandoDecision || guardandoEstadoDesignacion || filasSeleccionadas.length === 0" title="Aceptar filas seleccionadas" aria-label="Aceptar filas seleccionadas" class="detail-btn detail-btn-success detail-btn-icon">
                                <span class="material-icons" aria-hidden="true">check_circle</span>
                            </button>
                            <button type="button" @click="abrirDecisionSeleccionadas('RECHAZADA')" :disabled="!decisionesDisponibles || revisionCerrada || guardandoDecision || guardandoEstadoDesignacion || filasSeleccionadas.length === 0" title="Rechazar filas seleccionadas" aria-label="Rechazar filas seleccionadas" class="detail-btn detail-btn-danger detail-btn-icon">
                                <span class="material-icons" aria-hidden="true">cancel</span>
                            </button>
                        </div>
                    </div>
                    <div class="no-print detail-review-footer">
                        <button type="button" @click="confirmarRevision()" :disabled="revisionCerrada || guardandoDecision || guardandoEstadoDesignacion" class="detail-btn detail-btn-primary">Guardar cambios</button>
                    </div>
                    <p x-show="mensajeRevision" x-text="mensajeRevision" role="status" aria-live="polite" class="no-print detail-review-message"></p>
                </div>
            </section>

            <x-modal open="revisionDesignacionModalOpen" title-id="revision-designacion-title" close="cerrarRevisionDesignacion()" close-disabled="guardandoEstadoDesignacion" kicker="Revisión general" form="true" submit="guardarRevisionDesignacion()">
                <x-slot:title>
                    <span x-text="estadoRevisionValue === 'APROBADO' ? 'Aprobar designación' : 'Observar designación'"></span>
                </x-slot:title>
                <x-slot:body>
                    <p x-show="estadoRevisionValue === 'APROBADO'">La designación pasará a estado aprobado. Las decisiones por fila se conservarán.</p>
                    <div x-show="estadoRevisionValue === 'OBSERVADA'">
                        <label for="observacion-designacion">Motivo de observación</label>
                        <textarea id="observacion-designacion" x-model="observacionRevisionTexto" maxlength="1000" rows="4" :required="estadoRevisionValue === 'OBSERVADA'" aria-describedby="ayuda-observacion-designacion" class="detail-form-control"></textarea>
                        <p id="ayuda-observacion-designacion" class="detail-observation-help">El motivo es obligatorio y se guarda separado de las observaciones por fila.</p>
                    </div>
                    <p x-show="errorRevisionDesignacion" x-text="errorRevisionDesignacion" role="alert" class="detail-observation-error"></p>
                </x-slot:body>
                <x-slot:footer>
                    <button type="button" @click="cerrarRevisionDesignacion()" :disabled="guardandoEstadoDesignacion" class="detail-btn detail-btn-secondary">Cancelar</button>
                    <button type="submit" :disabled="guardandoEstadoDesignacion" class="detail-btn detail-btn-primary">
                        <span x-text="guardandoEstadoDesignacion ? 'Guardando…' : (estadoRevisionValue === 'APROBADO' ? 'Aprobar' : 'Observar')"></span>
                    </button>
                </x-slot:footer>
            </x-modal>

            <x-modal open="estadoModalOpen" title-id="estado-fila-title" close="cerrarEstadoFila()" kicker="Estado de asignación">
                <x-slot:title><span x-text="tituloEstadoFila()"></span></x-slot:title>
                <x-slot:body>
                    <p x-show="estadoFila(estadoModalFilaId) === 'APROBADA'">La asignación ha sido aprobada.</p>
                    <p x-show="estadoFila(estadoModalFilaId) === 'PENDIENTE'">Esta asignación aún no ha sido revisada.</p>
                    <p x-show="estadoFila(estadoModalFilaId) === 'RECHAZADA'">La asignación ha sido rechazada.</p>
                    <p x-show="estadoFila(estadoModalFilaId) === 'SIN_ESTADO'">No fue posible consultar el estado de esta asignación.</p>
                    <div x-show="observacionEstadoFila()" class="detail-status-observation">
                        <strong>Observación:</strong>
                        <p x-text="observacionEstadoFila()"></p>
                    </div>
                </x-slot:body>
                <x-slot:footer>
                    <button type="button" @click="cerrarEstadoFila()" class="detail-btn detail-btn-secondary">Cerrar</button>
                </x-slot:footer>
            </x-modal>

            <x-modal open="decisionModalOpen" title-id="decision-fila-title" close="cerrarDecision()" close-disabled="guardandoDecision" kicker="Revisión de asignación" form="true" submit="guardarDecision()">
                <x-slot:title>
                    <span x-text="decisionModalMultiple ? (decisionModalValue === 'APROBADA' ? 'Aceptar asignaciones seleccionadas' : 'Rechazar asignaciones seleccionadas') : (decisionModalValue === 'APROBADA' ? 'Aceptar asignación' : 'Rechazar asignación')"></span>
                </x-slot:title>
                <x-slot:body>
                    <label for="observacion-decision">Observación (opcional)</label>
                    <textarea id="observacion-decision" x-model="observacionTexto" maxlength="1000" rows="4" aria-describedby="ayuda-observacion-decision" class="detail-form-control"></textarea>
                    <p id="ayuda-observacion-decision" class="detail-observation-help">
                        <span x-show="decisionModalMultiple" x-cloak>Si la dejas vacía, se eliminarán las observaciones anteriores de todas las filas seleccionadas.</span>
                        <span x-show="!decisionModalMultiple && decisiones[filasDecisionIds[0]]?.observacion" x-cloak>Si la dejas vacía, se eliminará la observación anterior.</span>
                        <span x-show="!decisionModalMultiple && !decisiones[filasDecisionIds[0]]?.observacion" x-cloak>Puedes dejar la observación vacía.</span>
                    </p>
                    <p x-show="errorGuardado" x-text="errorGuardado" role="alert" class="detail-observation-error"></p>
                </x-slot:body>
                <x-slot:footer>
                    <button type="button" @click="cerrarDecision()" :disabled="guardandoDecision" class="detail-btn detail-btn-secondary">Cancelar</button>
                    <button type="submit" :disabled="guardandoDecision" class="detail-btn detail-btn-primary">
                        <span class="material-icons" aria-hidden="true" x-text="decisionModalValue === 'APROBADA' ? 'check_circle' : 'cancel'"></span>
                        <span x-text="guardandoDecision ? 'Guardando…' : (decisionModalValue === 'APROBADA' ? 'Aceptar' : 'Rechazar')"></span>
                    </button>
                </x-slot:footer>
            </x-modal>
        @endif
    </div>
@endsection

@push('scripts')
    <script defer src="{{ asset('resources/assets/js/vicerrectorado/designaciones/detalle.js') }}"></script>
@endpush
