@extends('layouts.app')

@include('vicerrectorado.designaciones._styles')

@push('styles')
    <link href="{{ asset('resources/assets/css/shared/designaciones-lista.css') }}" rel="stylesheet">
@endpush

@section('title', 'Designaciones universitarias — UATF')

@section('content')
    <div
        class="vicerrectorado-designaciones designaciones-screen"
        x-data="vicerrectoradoDesignacionesLista({
            designaciones: @js($designaciones),
            carrera: @js($carrera ?? ''),
            rutaDetalle: @js(route('vicerrectorado.designaciones.show', ['id' => '__ID__', 'gestion' => $gestion])),
            rutaPdf: @js(route('vicerrectorado.designaciones.pdf', ['id' => '__ID__', 'gestion' => $gestion])),
        })"
    >
        <ol class="breadcrumb float-xl-right">
            <li class="breadcrumb-item"><a href="{{ url('/') }}">Principal</a></li>
            <li class="breadcrumb-item active">Designaciones universitarias</li>
        </ol>

        <h1 class="page-header">
            <span class="material-icons" aria-hidden="true">fact_check</span>
            Designaciones universitarias <small>Vicerrectorado</small>
        </h1>

        @if($errorJachasun ?? null)
            <div class="alert alert-danger" role="alert">
                <strong>No se puede cargar la lista.</strong>
                <div>{{ $errorJachasun }}</div>
            </div>
        @else
            <div class="panel panel-inverse">
                <div class="panel-heading">
                    <h4 class="panel-title">Consulta de designaciones</h4>
                </div>

                <div class="panel-body">
                    <div class="panel-toolbar">
                        <form method="GET" action="{{ route('vicerrectorado.designaciones.index') }}" class="toolbar-filter">
                            <input type="hidden" name="carrera" x-model="carrera">
                            <div class="form-group">
                                <label for="gestion">Gestión</label>
                                <input id="gestion" name="gestion" value="{{ $gestion }}" inputmode="numeric" pattern="[0-9]{4}" maxlength="4" required class="form-control">
                            </div>
                            <button type="submit" class="btn btn-primary">
                                <span class="material-icons" aria-hidden="true">search</span>Consultar
                            </button>
                        </form>
                        <div class="toolbar-search">
                            <label for="search">Buscar designaciones</label>
                            <input id="search" name="search" x-model="filtro" @input="pagina = 1" type="search" maxlength="100" placeholder="Descripción, observación, carrera, estado…" class="form-control">
                        </div>
                        <div class="form-group">
                            <label for="carrera">Carrera</label>
                            <select id="carrera" x-model="carrera" @change="pagina = 1" class="form-control">
                                <option value="">Todas las carreras</option>
                                <template x-for="opcion in carreras()" :key="opcion.valor">
                                    <option :value="opcion.valor" x-text="opcion.etiqueta"></option>
                                </template>
                            </select>
                        </div>
                        <button type="button" x-show="filtro !== '' || carrera !== ''" @click="limpiarFiltros()" class="btn btn-outline-secondary">Limpiar</button>
                        <span class="small text-muted">Ordenadas por fecha más reciente · <span x-text="filasFiltradas().length">{{ $total }}</span> designaciones</span>
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
                                        <td class="text-center text-nowrap" x-text="designacion.fecha || 'Sin fecha'"></td>
                                        <td>
                                            <div class="font-weight-bold" x-text="designacion.detalle || '-' "></div>
                                            <small class="text-muted"><span x-text="designacion.programa_nombre || 'Sin nombre'"></span> (<span x-text="designacion.programa_codigo || 'Sin código'"></span>)</small>
                                        </td>
                                        <td class="text-center font-weight-bold" x-text="designacion.gestion || '-'"></td>
                                        <td class="text-center" x-text="designacion.periodo || '-'"></td>
                                        <td x-text="designacion.observacion || '-'"></td>
                                        <td class="text-center">
                                            <span class="badge" :class="{
                                                'badge-success': String(designacion.estado || '').toUpperCase() === 'APROBADO',
                                                'badge-warning': String(designacion.estado || '').toUpperCase() === 'OBSERVADA',
                                                'badge-info': String(designacion.estado || '').toUpperCase() === 'SOLICITADO',
                                                'badge-secondary': !['APROBADO', 'OBSERVADA', 'SOLICITADO'].includes(String(designacion.estado || '').toUpperCase()),
                                            }" x-text="designacion.estado || 'Sin estado'"></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="record-actions">
                                                <a :href="detalleUrl(designacion.id)" class="btn btn-info">Detalles</a>
                                                <a :href="pdfUrl(designacion.id)" target="_blank" rel="noopener" title="Imprimir designación" aria-label="Imprimir designación" class="btn btn-dark"><span class="material-icons" aria-hidden="true">print</span></a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="designaciones.length === 0">
                                    <td colspan="7" class="empty-state">No existen designaciones para la gestión indicada.</td>
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
                    <nav class="pagination" aria-label="Paginación de designaciones universitarias">
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
    <script defer src="{{ asset('resources/assets/js/vicerrectorado/designaciones/lista.js') }}"></script>
@endpush
