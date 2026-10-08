@extends('layouts.app')

@section('title', 'Lista de Designaciones — UATF')

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Material+Icons" rel="stylesheet">
    <link href="{{ asset('resources/assets/css/shared/designaciones-lista.css') }}" rel="stylesheet">
@endpush

@section('content')
    @php($esDecanatura = Auth::user()?->esDecanatura() ?? false)
    <div
        class="designaciones-screen"
        x-data="designacionesLista({
            contextoActual: @js($contextoActual ?? ['gestion' => '', 'periodo' => '']),
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
            @if($esDecanatura)
                <small>Designaciones aprobadas de su facultad</small>
            @else
                <small>{{ $carrera->nombre }} ({{ $carrera->sigla }})</small>
            @endif
        </h1>

        @if($errorJachasun ?? null)
            <div class="alert alert-danger" role="alert">
                <strong>No se puede cargar la lista.</strong>
                <div>{{ $errorJachasun }}</div>
            </div>
        @else
            <div class="panel panel-inverse">
                <div class="panel-heading">
                    <h4 class="panel-title">{{ $esDecanatura ? 'Consulta de designaciones aprobadas' : 'Administración de Designaciones' }}</h4>
                </div>

                <div class="panel-body">
                    <div class="panel-toolbar">
                        <div class="toolbar-search">
                            <label for="search">Buscar por descripción</label>
                            <input id="search" name="search" x-model="filtro" @input="pagina = 1" type="search" maxlength="100" placeholder="Ej. SEMESTRAL 1/2023" class="form-control">
                        </div>
                        <div>
                            @if(Auth::user()?->esDirectorCarrera())
                            <button type="button" @click="abrirCrear()" class="btn btn-primary btn_personalizado">
                                <span class="material-icons" aria-hidden="true">add_circle</span>Nuevo
                            </button>
                            @endif
                            <button type="button" x-show="filtro !== ''" @click="limpiarFiltro()" class="btn btn-outline-secondary">Limpiar</button>
                        </div>
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
                                <template x-for="d in paginadas()" :key="d.id">
                                    <tr>
                                        <td class="text-center text-nowrap" x-text="d.fecha || '-'"></td>
                                        <td>
                                            <div class="font-weight-bold" x-text="d.detalle"></div>
                                            @if($esDecanatura)
                                                <small class="text-muted">Carrera de <span x-text="d.programa_nombre"></span> (<span x-text="d.programa_codigo"></span>)</small>
                                            @else
                                                <small class="text-muted">Carrera de {{ $carrera->nombre }}</small>
                                            @endif
                                        </td>
                                        <td class="text-center font-weight-bold" x-text="d.gestion"></td>
                                        <td class="text-center" x-text="d.periodo"></td>
                                        <td x-text="d.observacion || '-'"></td>
                                        <td class="text-center">
                                            <span class="badge badge-success" x-text="d.estado || 'Sin estado'"></span>
                                        </td>
                                        <td class="text-center">
                                            <div class="record-actions">
                                                <a :href="rutaDetalle.replace('__ID__', d.id)" class="btn btn-info">Detalles</a>
                                                <a :href="rutaPdf.replace('__ID__', d.id)" target="_blank" rel="noopener" title="Imprimir designación" aria-label="Imprimir designación" class="btn btn-dark"><span class="material-icons" aria-hidden="true">print</span></a>
                                            </div>
                                        </td>
                                    </tr>
                            </template>
                            <tr x-show="designaciones.length === 0">
                                    <td colspan="7" class="empty-state">{{ $esDecanatura ? 'No existen designaciones aprobadas para esta facultad.' : 'No existen designaciones registradas para esta carrera.' }}</td>
                                </tr>
                                <tr x-show="designaciones.length > 0 && filasFiltradas().length === 0">
                                    <td colspan="7" class="empty-state">No se encontraron designaciones para la búsqueda indicada.</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>

                <div x-show="totalPaginas() > 1" class="panel-footer">
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

        @if(Auth::user()?->esDirectorCarrera())
            <form id="form-crear" method="POST" action="{{ route('designaciones.store') }}">
                @csrf
                <div
                    x-show="crearModalOpen"
                    x-cloak
                    x-transition.opacity
                    class="modal"
                    role="dialog"
                    aria-modal="true"
                    aria-labelledby="crear-designacion-title"
                    @keydown.escape.window="cerrarCrear()"
                >
                    <div class="modal-dialog" @click.outside="cerrarCrear()">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h2 id="crear-designacion-title" class="modal-title"><span class="material-icons" aria-hidden="true">person_add</span> Nueva designación</h2>
                                <button type="button" @click="cerrarCrear()" class="close" aria-label="Cerrar modal">&times;</button>
                            </div>

                            <div class="modal-body modal-form-grid">
                                <div class="form-group modal-field-wide">
                                    <label for="crear-designacion-importar">Importar de una gestión anterior</label>
                                    <select id="crear-designacion-importar" name="importar_desde" x-model="crearForm.importarDesde" class="form-control">
                                        <option value="">No importar (crear vacía)</option>
                                        @foreach($fuentesImportacion as $fuente)
                                            <option value="{{ $fuente['id'] }}">{{ $fuente['detalle'] }} — {{ $fuente['gestion'] }}/{{ $fuente['periodo'] }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="crear-designacion-gestion">Gestión</label>
                                    <input id="crear-designacion-gestion" name="gestion" x-model="crearForm.gestion" type="number" min="2000" max="9999" placeholder="2026" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="crear-designacion-periodo">Periodo</label>
                                    <input id="crear-designacion-periodo" name="periodo" x-model="crearForm.periodo" type="number" min="1" max="2" placeholder="1" class="form-control">
                                </div>
                                <div class="form-group">
                                    <label for="crear-designacion-obs">Observación</label>
                                    <input id="crear-designacion-obs" name="obs" x-model="crearForm.obs" type="text" class="form-control">
                                </div>
                            </div>

                            <div class="modal-footer">
                                <button type="button" @click="cerrarCrear()" class="btn btn-outline-secondary">Cancelar</button>
                                <button type="button" @click="limpiarCrear()" class="btn btn-primary">Limpiar</button>
                                <button type="button" @click="confirmarCrear()" class="btn btn-success">Guardar</button>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        @endif

        @if(Auth::user()?->esDirectorCarrera())
            @include('partials.modal-confirmacion')
        @endif
        @include('partials.modal-notificacion')
    </div>
@endsection

@push('scripts')
    <script defer src="{{ asset('resources/assets/js/designaciones/lista.js') }}"></script>
@endpush
