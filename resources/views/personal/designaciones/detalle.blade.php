@extends('layouts.app')

@section('title', 'Detalle de designación — ' . $carrera->nombre)

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Material+Icons&display=swap" rel="stylesheet">
    <link href="{{ asset('resources/assets/css/shared/designaciones-detalle.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div class="designaciones-detail max-w-6xl mx-auto space-y-4 text-sm text-gray-800" x-data="{}">
        <ol class="breadcrumb no-print">
            <li class="breadcrumb-item"><a href="{{ route('personal.designaciones.index', ['gestion' => $gestion]) }}">Designaciones</a></li>
            <li class="breadcrumb-item active">Detalle de designación</li>
        </ol>

        <header class="print-card detail-header">
            <div>
                <h1 class="page-header"><span class="material-icons" aria-hidden="true">fact_check</span> Detalle de designación</h1>
                <p class="detail-subtitle">{{ $carrera->nombre }} ({{ $carrera->sigla }}) · Consulta de Personal</p>
            </div>
            <div class="no-print detail-actions">
                <a href="{{ route('personal.designaciones.index', ['gestion' => $gestion]) }}" class="detail-btn detail-btn-secondary">Volver</a>
                <a href="{{ route('personal.designaciones.pdf', ['id' => $asignacion['id'], 'gestion' => $gestion]) }}" target="_blank" rel="noopener" title="Imprimir designación" aria-label="Imprimir designación" class="detail-btn detail-btn-dark"><span class="material-icons" aria-hidden="true">print</span> Imprimir designación</a>
            </div>
        </header>

        @if($errorJachasun ?? null)
            <div class="alert alert-danger" role="alert">
                <strong>No se puede cargar el detalle.</strong>
                <div>{{ $errorJachasun }}</div>
            </div>
        @else
            <section class="print-card panel panel-inverse">
                <div class="panel-heading">
                    <h2 class="panel-title">Información de la designación</h2>
                </div>
                <div class="detail-summary">
                    <div class="detail-summary-item"><p class="detail-label">Descripción</p><p class="detail-value">{{ $asignacion['detalle'] ?: '-' }}</p></div>
                    <div class="detail-summary-item"><p class="detail-label">Gestión / Periodo</p><p class="detail-value">{{ $asignacion['gestion'] ?: '-' }} / {{ $asignacion['periodo'] ?: '-' }}</p></div>
                    <div class="detail-summary-item"><p class="detail-label">Estado</p><p class="detail-value">{{ $asignacion['estado'] ?: 'Sin estado' }}</p></div>
                </div>
                @if($asignacion['observacion'] ?? null)
                    <div class="detail-observation"><strong>Observación:</strong> {{ $asignacion['observacion'] }}</div>
                @endif
            </section>

            <section class="print-card panel panel-inverse">
                <div class="panel-heading">
                    <h2 class="panel-title">Asignaciones registradas</h2>
                </div>
                <div class="panel-body">
                    <div class="detail-table-wrap">
                        <table class="detail-table">
                            <thead>
                                <tr>
                                    <th>Docente</th>
                                    <th>CI</th>
                                    <th>Materia</th>
                                    <th>Grupo</th>
                                    <th class="text-right">Teóricas</th>
                                    <th class="text-right">Prácticas</th>
                                    <th class="text-right">Laboratorio</th>
                                    <th class="no-print text-center">Carta</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($filas as $fila)
                                    <tr>
                                        <td>{{ $fila['docente_nombre'] ?? '-' }}</td>
                                        <td>{{ $fila['ci'] ?? '-' }}</td>
                                        <td>{{ $fila['materia_sigla'] ?? '-' }} — {{ $fila['materia_nombre'] ?? '-' }}</td>
                                        <td>{{ $fila['grupo_id'] ?? '-' }}</td>
                                        <td class="text-right">{{ $fila['horas_teoricas'] ?? '-' }}</td>
                                        <td class="text-right">{{ $fila['horas_practicas'] ?? '-' }}</td>
                                        <td class="text-right">{{ $fila['horas_laboratorio'] ?? '-' }}</td>
                                        <td class="no-print text-center">
                                            <button type="button" @click="$dispatch('designacion-pdf-open', { id: {{ (int) $asignacion['id'] }}, filaId: {{ (int) ($fila['id'] ?? 0) }} })" title="Imprimir carta del docente" aria-label="Imprimir carta del docente" class="detail-btn detail-btn-dark">
                                                <span class="material-icons" aria-hidden="true">description</span> Carta
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="detail-empty">No se recibieron filas de detalle.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>
        @endif

        @include('partials.modal-designacion-pdf', ['rutaPdf' => route('personal.designaciones.carta_pdf', ['id' => '__ID__', 'gestion' => $gestion])])
    </div>
@endsection
