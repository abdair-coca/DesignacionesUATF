@extends('layouts.app')

@section('title', 'Designaciones aprobadas — UATF')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Material+Icons" rel="stylesheet">
    <link href="{{ asset('resources/assets/css/shared/designaciones-lista.css') }}" rel="stylesheet">
@endpush

@section('content')
    <div
        class="designaciones-screen"
        x-data="decanaturaDesignacionesLista({
            designaciones: @js($designaciones ?? collect()),
            filtro: @js($busqueda),
            rutaDetalle: @js(route('designaciones.show', ['id' => '__ID__'])),
            rutaPdf: @js(route('designaciones.pdf', ['id' => '__ID__'])),
        })"
    >
        <ol class="breadcrumb">
            <li class="breadcrumb-item active">Designaciones</li>
        </ol>

        <h1 class="page-header">
            <span class="material-icons" aria-hidden="true">fact_check</span>
            Designaciones Docentes
            <small>Decanatura · Designaciones aprobadas de su facultad</small>
        </h1>

        @if($errorJachasun ?? null)
            <div class="alert alert-danger" role="alert">
                <strong>No se puede cargar la lista.</strong>
                <div>{{ $errorJachasun }}</div>
            </div>
        @else
            <div class="panel panel-inverse">
                <div class="panel-heading">
                    <h2 class="panel-title">Consulta de designaciones aprobadas</h2>
                </div>

                <div class="panel-body">
                    <div class="panel-toolbar">
                        <div class="toolbar-search">
                            <label for="search">Buscar designaciones</label>
                            <input id="search" x-model="filtro" @input="pagina = 1" type="search" maxlength="100" placeholder="Descripción, observación, carrera, estado…" class="form-control">
                        </div>
                        <div class="toolbar-filter">
                            <div class="form-group">
                                <label for="carrera">Carrera</label>
                                <select id="carrera" x-model="carrera" @change="pagina = 1" class="form-control">
                                    <option value="">Todas las carreras</option>
                                    <template x-for="opcion in carreras()" :key="opcion.valor">
                                        <option :value="opcion.valor" x-text="opcion.etiqueta"></option>
                                    </template>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="gestion">Gestión</label>
                                <select id="gestion" x-model="gestion" @change="pagina = 1" class="form-control">
                                    <option value="">Todas las gestiones</option>
                                    <template x-for="opcion in gestiones()" :key="opcion">
                                        <option :value="opcion" x-text="opcion"></option>
                                    </template>
                                </select>
                            </div>
                        </div>
                        <div>
                            <button type="button" x-show="filtro !== '' || carrera !== '' || gestion !== ''" @click="limpiarFiltros()" class="btn btn-outline-secondary">Limpiar</button>
                        </div>
                        <span class="small text-muted"><span x-text="filasFiltradas().length"></span> designaciones</span>
                    </div>

                    <div class="table-responsive">
                        <table id="tdLista" class="table table-striped table-bordered table-td-valign-middle dt-responsive">
                            <thead>
                                <tr>
                                    <th scope="col" class="text-nowrap text-center">Fecha</th>
                                    <th scope="col" class="text-nowrap">Descripción</th>
                                    <th scope="col" class="text-nowrap text-center">Gestión</th>
                                    <th scope="col" class="text-nowrap text-center">Periodo</th>
                                    <th scope="col" class="text-nowrap">Observación</th>
                                    <th scope="col" class="text-nowrap text-center">Estado</th>
                                    <th scope="col" class="text-nowrap text-center">Acciones</th>
                                </tr>
                            </thead>
                            <tbody>
                                <template x-for="designacion in paginadas()" :key="designacion.id">
                                    <tr>
                                        <td class="text-center text-nowrap" x-text="designacion.fecha || '-' "></td>
                                        <td>
                                            <div class="font-weight-bold" x-text="designacion.detalle || '-' "></div>
                                            <small class="text-muted">Carrera de <span x-text="designacion.programa_nombre || '-' "></span> (<span x-text="designacion.programa_codigo || '-' "></span>)</small>
                                        </td>
                                        <td class="text-center font-weight-bold" x-text="designacion.gestion || '-' "></td>
                                        <td class="text-center" x-text="designacion.periodo || '-' "></td>
                                        <td x-text="designacion.observacion || '-' "></td>
                                        <td class="text-center"><span class="badge badge-success" x-text="designacion.estado || 'Sin estado'"></span></td>
                                        <td class="text-center">
                                            <div class="record-actions">
                                                <a :href="detalleUrl(designacion.id)" class="btn btn-info">Detalles</a>
                                                <a :href="rutaPdf.replace('__ID__', designacion.id)" target="_blank" rel="noopener" title="Imprimir reporte anterior" aria-label="Imprimir reporte anterior" class="btn btn-dark"><span class="material-icons" aria-hidden="true">print</span></a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="designaciones.length === 0">
                                    <td colspan="7" class="empty-state">No existen designaciones aprobadas para esta facultad.</td>
                                </tr>
                                <tr x-show="designaciones.length > 0 && filasFiltradas().length === 0">
                                    <td colspan="7" class="empty-state">No se encontraron designaciones para los filtros indicados.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="designaciones.length > 0 && filasFiltradas().length > 0" class="panel-footer">
                    <p class="pagination-label">
                        Mostrando <span x-text="paginaInicio() + 1"></span>–<span x-text="Math.min(paginaInicio() + porPagina, filasFiltradas().length)"></span> de <span x-text="filasFiltradas().length"></span> designaciones
                    </p>
                    <nav class="pagination" aria-label="Paginación de designaciones">
                        <button type="button" x-show="pagina > 1" @click="irAPagina(pagina - 1)" class="btn btn-secondary">Anterior</button>
                        <span x-show="pagina === 1" class="btn disabled">Anterior</span>
                        <span class="small">Página <span x-text="pagina"></span> de <span x-text="totalPaginas()"></span></span>
                        <button type="button" x-show="pagina < totalPaginas()" @click="irAPagina(pagina + 1)" class="btn btn-secondary">Siguiente</button>
                        <span x-show="pagina >= totalPaginas()" class="btn disabled">Siguiente</span>
                    </nav>
                </div>
            </div>
        @endif
    </div>
@endsection

@push('scripts')
    <script defer src="{{ asset('resources/assets/js/shared/designaciones-filtros.js') }}"></script>
    <script defer src="{{ asset('resources/assets/js/decanatura/designaciones/lista.js') }}"></script>
@endpush
