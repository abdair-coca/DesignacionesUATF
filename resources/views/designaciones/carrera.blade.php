@extends('layouts.app')

@section('title', 'Detalle de designación — ' . $carrera->nombre)

@push('styles')
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700;900&family=Material+Icons&display=swap" rel="stylesheet">
    <link href="{{ asset('resources/assets/css/shared/designaciones-detalle.css') }}" rel="stylesheet">
@endpush

@section('content')
    @php($esDecanatura = Auth::user()?->esDecanatura() ?? false)
    <div
        class="designaciones-detail max-w-6xl mx-auto space-y-4 text-sm text-gray-800"
        x-data="designacionesCarrera({
            observacionInicial: @js($asignacion['observacion'] ?? ''),
            rutaBusquedaDocentes: @js($esDecanatura ? '' : route('designaciones.docentes.buscar')),
            materiasDisponibles: @js($materiasDisponibles ?? collect()),
            materiasOferta: @js($materiasOferta ?? collect()),
            gruposOfertaDisponibles: @js($gruposOfertaDisponibles ?? true),
            filas: @js($filas ?? collect()),
        })"
    >
        <ol class="breadcrumb no-print">
            <li class="breadcrumb-item"><a href="{{ route('designaciones.index') }}">Designaciones</a></li>
            <li class="breadcrumb-item active">Detalle de designación</li>
        </ol>
        <header class="print-card detail-header">
            <div>
                <h1 class="page-header"><span class="material-icons" aria-hidden="true">fact_check</span> Detalle de designación</h1>
                <p class="detail-subtitle">{{ $carrera->nombre }} ({{ $carrera->sigla }}) · {{ ($puedeEditar ?? false) ? 'Consulta y edición' : 'Consulta' }}</p>
            </div>
            <div class="no-print detail-actions">
                <a href="{{ route('designaciones.index') }}" class="detail-btn detail-btn-secondary">Volver</a>
                <a href="{{ route('designaciones.pdf', ['id' => $asignacion['id']]) }}" target="_blank" rel="noopener" title="Imprimir designación" aria-label="Imprimir designación" class="detail-btn detail-btn-dark"><span class="material-icons" aria-hidden="true">print</span></a>
                @if(($puedeEditar ?? false) && !$esDecanatura && !($errorJachasun ?? null))
                    <button type="button" @click="abrirEdicionCabecera()" class="detail-btn detail-btn-primary">Editar</button>
                @endif
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
                 @unless($esDecanatura)
                 <form class="no-print detail-toolbar" @submit.prevent>
                     <div class="detail-search">
                         <label for="filtro-asignaciones">Buscar por docente, materia o CI</label>
                         <div class="detail-search-controls">
                             <input id="filtro-asignaciones" x-model="filtro" type="search" maxlength="100" placeholder="Ej. Juan Pérez, MATE-101 o 1234567" class="detail-form-control">
                             <button type="submit" class="detail-btn detail-btn-primary">Buscar</button>
                             <button type="button" x-show="filtro !== ''" @click="limpiarFiltro()" class="detail-btn detail-btn-secondary">Limpiar</button>
                         </div>
                     </div>
                     <div class="detail-actions">
                          @if(($puedeEditar ?? false) && !$esDecanatura)
                             <button type="button" @click="abrirNuevaDesignacion()" class="detail-btn detail-btn-success">Nueva designación</button>
                         @endif
                     </div>
                 </form>
                 @endunless
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
                                    @unless($esDecanatura)
                                        <th>Estado</th>
                                        <th class="no-print text-right">Acciones</th>
                                    @endunless
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="fila in filasFiltradas()" :key="fila.id">
                                <tr>
                                    <td x-text="fila.docente_nombre || '-'"></td>
                                    <td x-text="fila.ci || '-'"></td>
                                    <td x-text="(fila.materia_sigla || '-') + ' — ' + (fila.materia_nombre || '-')"></td>
                                    <td x-text="fila.grupo_id || '-'"></td>
                                    <td class="text-right" x-text="fila.horas_teoricas ?? '-'"></td>
                                    <td class="text-right" x-text="fila.horas_practicas ?? '-'"></td>
                                    <td class="text-right" x-text="fila.horas_laboratorio ?? '-'"></td>
                                        @unless($esDecanatura)
                                        <td>
                                            <button
                                                type="button"
                                                class="badge detail-status-badge"
                                                :class="claseEstadoFila(fila.id)"
                                                @click="mostrarEstadoFila(fila.id)"
                                                :aria-label="'Ver estado: ' + etiquetaEstadoFila(fila.id)"
                                                title="Ver estado de la asignación"
                                            ><strong x-text="etiquetaEstadoFila(fila.id)"></strong></button>
                                        </td>
                                        @endunless
                                        @unless($esDecanatura)
                                    <td class="no-print text-right text-nowrap">
                                        <div class="detail-row-actions">
                                            @if(($puedeEditar ?? false) && !$esDecanatura)
                                            <button
                                                type="button"
                                                @click="abrirEdicionFila(fila)"
                                                title="Editar asignación"
                                                aria-label="Editar fila de asignación"
                                                class="detail-btn detail-btn-primary"
                                            >
                                                Reasignar
                                            </button>
                                            <button
                                                type="button"
                                                title="Desasignar docente"
                                                aria-label="Desasignar docente de la fila"
                                                class="detail-btn detail-btn-danger detail-btn-icon"
                                            >
                                                <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                                                </svg>
                                            </button>
                                            @endif
                                            <a href="" target="_blank" rel="noopener" title="Imprimir designación" aria-label="Imprimir designación" class="detail-btn detail-btn-dark">
                                                <span class="material-icons" aria-hidden="true">print</span>
                                            </a>
                                        </div>
                                    </td>
                                        @endunless
                                </tr>
                            </template>
                            <tr x-show="filtro !== '' && filas.length > 0 && !hayCoincidencias()">
                                <td colspan="{{ $esDecanatura ? 7 : 9 }}" class="detail-empty">No se encontraron asignaciones para la búsqueda indicada.</td>
                            </tr>
                            <tr x-show="filas.length === 0">
                                <td colspan="{{ $esDecanatura ? 7 : 9 }}" class="detail-empty">No se recibieron filas de detalle.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                </div>
            </section>

            @unless($esDecanatura)
            <x-modal open="estadoModalOpen" title-id="estado-fila-title" close="cerrarEstadoFila()" kicker="Estado de asignación">
                <x-slot:title><span x-text="tituloEstadoFila()"></span></x-slot:title>
                <x-slot:body>
                    <p x-show="estadoFila(estadoModalFilaId) === 'APROBADA'">La asignación ha sido aprobada.</p>
                    <p x-show="estadoFila(estadoModalFilaId) === 'PENDIENTE'">Esta asignación aún no ha sido revisada.</p>
                    <p x-show="estadoFila(estadoModalFilaId) === 'RECHAZADA'">La asignación ha sido rechazada.</p>
                    <p x-show="estadoFila(estadoModalFilaId) === 'SIN_ESTADO'">No fue posible consultar el estado de esta asignación.</p>
                    <div x-show="observacionEstadoFila()" class="detail-status-observation">
                        <strong>Observación de la revisión:</strong>
                        <p x-text="observacionEstadoFila()"></p>
                    </div>
                </x-slot:body>
                <x-slot:footer>
                    <button type="button" @click="cerrarEstadoFila()" class="detail-btn detail-btn-secondary">Cerrar</button>
                </x-slot:footer>
            </x-modal>
            @endunless
        @endif

        @if(($puedeEditar ?? false) && !$esDecanatura)
        <form id="form-editar" method="POST" action="{{ route('designaciones.update', ['id' => $asignacion['id']]) }}">
            @csrf
            <div
                x-show="editCabeceraOpen"
                x-cloak
                x-transition.opacity
                class="detail-modal no-print"
                role="dialog"
                aria-modal="true"
                aria-labelledby="editar-designacion-title"
                @keydown.escape.window="cerrarEdicionCabecera()"
            >
            <div class="detail-modal-dialog" @click.outside="cerrarEdicionCabecera()">
                <div class="detail-modal-content">
                <div class="detail-modal-header">
                    <div>
                        <span class="detail-modal-kicker">Editar cabecera</span>
                        <h2 id="editar-designacion-title" class="detail-modal-title">Editar designación</h2>
                    </div>
                    <button type="button" @click="cerrarEdicionCabecera()" class="detail-modal-close" aria-label="Cerrar modal">&times;</button>
                </div>

                <div class="detail-modal-body detail-form-grid">
                    <div>
                        <label for="editar-designacion-obs">Observación</label>
                        <input id="editar-designacion-obs" name="obs" x-model="editCabeceraForm.obs" type="text" class="detail-form-control">
                    </div>
                </div>

                <div class="detail-modal-footer">
                    <button type="button" @click="cerrarEdicionCabecera()" class="detail-btn detail-btn-secondary">Cancelar</button>
                    <button type="button" @click="confirmarEdicionCabecera()" class="detail-btn detail-btn-success">Guardar cambios</button>
                </div>
                </div>
            </div>
        </div>
        </form>

        <form id="form-editar-fila" method="POST" action="{{ route('designaciones.actualizar_detalle', ['id' => $asignacion['id']]) }}">
            @csrf
            <input type="hidden" name="id_detalle" x-model="editFilaForm.id">
            <input type="hidden" name="id_docente" x-model="editFilaForm.docente_id">
            <input type="hidden" name="id_materia" x-model="editFilaForm.materia_id">
        </form>

        <div
            x-show="editFilaOpen"
            x-cloak
            x-transition.opacity
            class="detail-modal no-print"
            role="dialog"
            aria-modal="true"
            aria-labelledby="editar-fila-title"
            @keydown.escape.window="cerrarEdicionFila()"
        >
            <div class="detail-modal-dialog detail-modal-dialog-wide" @click.outside="cerrarEdicionFila()">
                <div class="detail-modal-content">
                <div class="detail-modal-header">
                    <div>
                         <span class="detail-modal-kicker" x-text="editFilaForm.id ? 'Reasignación' : 'Nueva designación'"></span>
                         <h2 id="editar-fila-title" class="detail-modal-title" x-text="editFilaForm.id ? 'Editar asignación' : 'Nueva designación'"></h2>
                    </div>
                    <button type="button" @click="cerrarEdicionFila()" class="detail-modal-close" aria-label="Cerrar modal">&times;</button>
                </div>

                <div class="detail-modal-body detail-form-grid">
                    <div x-show="Object.keys(erroresFila).length > 0" x-cloak role="alert" class="col-span-full rounded border border-rose-200 bg-rose-50 px-3 py-2 text-xs text-rose-800">
                        <template x-for="mensaje in Object.values(erroresFila)" :key="mensaje">
                            <p x-text="mensaje"></p>
                        </template>
                    </div>
                    <div>
                        <label for="editar-fila-docente" class="font-bold text-gray-700">Docente</label>
                        <div class="relative mt-1" @click.outside="cerrarDropdownDocente()">
                            <form @submit.prevent="buscarDocentesUniversidad()" class="flex gap-1">
                                <input
                                    id="editar-fila-docente"
                                    type="text"
                                    role="combobox"
                                    aria-expanded="dropdownDocente"
                                    aria-controls="lista-docentes"
                                    :aria-activedescendant="docentesFiltrados()[indiceDocente] ? 'opcion-docente-' + docentesFiltrados()[indiceDocente].id : null"
                                    :value="filtroDocente"
                                    @input="actualizarFiltroDocente($event.target.value)"
                                    @focus="abrirDropdownDocente(); $el.select()"
                                    @keydown.arrow-down.prevent="navegarDocente($event, docentesFiltrados())"
                                    @keydown.arrow-up.prevent="navegarDocente($event, docentesFiltrados())"
                                     @keydown.enter.prevent="seleccionarOBuscarDocente()"
                                    @keydown.escape.stop="navegarDocente($event, docentesFiltrados())"
                                    placeholder="Buscar docente o CI…"
                                    autocomplete="off"
                                    class="min-w-0 flex-1 rounded border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-[#00acac] focus:outline-none focus:ring-1 focus:ring-[#00acac]"
                                >
                                <button
                                    type="submit"
                                    class="whitespace-nowrap rounded bg-[#00acac] px-2.5 py-2 text-[11px] font-bold text-white hover:bg-[#008a8a] disabled:cursor-wait disabled:opacity-60"
                                    :disabled="cargandoDocentesGlobales"
                                     aria-label="Buscar docente"
                                >
                                    <span x-show="!cargandoDocentesGlobales">
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                                            <circle cx="11" cy="11" r="7" stroke-width="2"></circle>
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="m20 20-4-4"></path>
                                        </svg>
                                    </span>
                                    <span x-show="cargandoDocentesGlobales" x-cloak>Buscando...</span>
                                </button>
                            </form>
                             <p x-show="errorDocentesGlobales" x-text="errorDocentesGlobales" class="mt-1 text-[10px] text-rose-700"></p>
                            <ul
                                id="lista-docentes"
                                x-show="dropdownDocente"
                                x-cloak
                                role="listbox"
                                 class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded border border-gray-300 bg-white py-1 shadow-lg"
                             >
                                <template x-for="(docente, indice) in docentesFiltrados()" :key="docente.id">
                                    <li
                                        :id="'opcion-docente-' + docente.id"
                                        role="option"
                                        :aria-selected="String(docente.id) === String(editFilaForm.docente_id)"
                                        :class="indice === indiceDocente ? 'bg-[#00acac] text-white' : 'text-gray-900 hover:bg-gray-100'"
                                        class="cursor-pointer px-3 py-2 text-xs"
                                        @click="seleccionarDocente(docente)"
                                        @mouseenter="indiceDocente = indice"
                                    >
                                        <span x-text="docente.nombre"></span><span x-text="docente.ci ? ' (' + docente.ci + ')' : ''" class="opacity-70"></span>
                                    </li>
                                </template>
                                <li x-show="cargandoDocentesGlobales" class="px-3 py-2 text-xs text-gray-500">Buscando docentes...</li>
                                <li x-show="!cargandoDocentesGlobales && errorDocentesGlobales" class="px-3 py-2 text-xs text-rose-700" x-text="errorDocentesGlobales"></li>
                                <li x-show="docentesFiltrados().length === 0" class="px-3 py-2 text-xs text-gray-500">Sin coincidencias</li>
                            </ul>
                        </div>
                    </div>
                    <div>
                        <label for="editar-fila-materia" class="font-bold text-gray-700">Materia</label>
                        <div class="relative mt-1" @click.outside="cerrarDropdownMateria()">
                            <input
                                id="editar-fila-materia"
                                type="text"
                                role="combobox"
                                aria-expanded="dropdownMateria"
                                aria-controls="lista-materias"
                                :aria-activedescendant="materiasFiltradas()[indiceMateria] ? 'opcion-materia-' + materiasFiltradas()[indiceMateria].id : null"
                                :value="filtroMateria"
                                 @input="actualizarFiltroMateria($event.target.value)"
                                @focus="abrirDropdownMateria(); $el.select()"
                                @keydown.arrow-down.prevent="navegarMateria($event, materiasFiltradas())"
                 @keydown.arrow-up.prevent="navegarMateria($event, materiasFiltradas())"
                                   @keydown.enter.prevent="navegarMateria($event, materiasFiltradas())"
                                   @keydown.escape.stop="navegarMateria($event, materiasFiltradas())"
                                  placeholder="Buscar materia o sigla…"
                                autocomplete="off"
                                class="w-full rounded border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-[#00acac] focus:outline-none focus:ring-1 focus:ring-[#00acac]"
                            >
                            <ul
                                id="lista-materias"
                                x-show="dropdownMateria"
                                x-cloak
                                role="listbox"
                                class="absolute z-10 mt-1 max-h-48 w-full overflow-auto rounded border border-gray-300 bg-white py-1 shadow-lg"
                            >
                                <template x-for="(materia, indice) in materiasFiltradas()" :key="materia.id">
                                    <li
                                        :id="'opcion-materia-' + materia.id"
                                        role="option"
                                        :aria-selected="String(materia.id) === String(editFilaForm.materia_id)"
                                        :class="indice === indiceMateria ? 'bg-[#00acac] text-white' : 'text-gray-900 hover:bg-gray-100'"
                                        class="cursor-pointer px-3 py-2 text-xs"
                                        @click="seleccionarMateria(materia)"
                                        @mouseenter="indiceMateria = indice"
                                    >
                                        <span x-text="materia.sigla + ' — ' + materia.nombre"></span>
                                    </li>
                                </template>
                                <li x-show="materiasFiltradas().length === 0" class="px-3 py-2 text-xs text-gray-500">Sin materias disponibles</li>
                            </ul>
                        </div>
                    </div>
                     <div>
                         <label for="editar-fila-grupo" class="font-bold text-gray-700">Grupo</label>
                          <select id="editar-fila-grupo" name="id_grupo" form="form-editar-fila" x-model="editFilaForm.id_grupo" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-[#00acac] focus:outline-none focus:ring-1 focus:ring-[#00acac]">
                            <option value="">Seleccionar grupo</option>
                            <template x-for="grupo in gruposMateriaSeleccionada()" :key="grupo">
                                <option :value="grupo" x-text="'Grupo ' + grupo"></option>
                             </template>
                         </select>
                         <p x-show="mensajeGrupoMateria()" x-text="mensajeGrupoMateria()" class="mt-1 text-[10px] text-slate-600" aria-live="polite"></p>
                     </div>
                    <div>
                         <label for="editar-fila-teoricas" class="font-bold text-gray-700">Horas teóricas</label>
                         <input id="editar-fila-teoricas" name="hrs_teoria" form="form-editar-fila" x-model="editFilaForm.horasTeoricas" type="number" min="0" :max="materiaSeleccionada()?.horas_teoricas ?? undefined" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-[#00acac] focus:outline-none focus:ring-1 focus:ring-[#00acac]">
                    </div>
                    <div>
                         <label for="editar-fila-practicas" class="font-bold text-gray-700">Horas prácticas</label>
                         <input id="editar-fila-practicas" name="hrs_practica" form="form-editar-fila" x-model="editFilaForm.horasPracticas" type="number" min="0" :max="materiaSeleccionada()?.horas_practicas ?? undefined" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-[#00acac] focus:outline-none focus:ring-1 focus:ring-[#00acac]">
                    </div>
                    <div>
                         <label for="editar-fila-laboratorio" class="font-bold text-gray-700">Horas laboratorio</label>
                         <input id="editar-fila-laboratorio" name="hrs_laboratorio" form="form-editar-fila" x-model="editFilaForm.horasLaboratorio" type="number" min="0" :max="materiaSeleccionada()?.horas_laboratorio ?? undefined" class="mt-1 w-full rounded border border-gray-300 px-3 py-2 text-xs text-gray-900 focus:border-[#00acac] focus:outline-none focus:ring-1 focus:ring-[#00acac]">
                    </div>
                 </div>

                   <div class="detail-modal-footer">
                      <button type="button" @click="cerrarEdicionFila()" class="detail-btn detail-btn-secondary">Cancelar</button>
                       <button type="button" @click="guardarFila()" class="detail-btn detail-btn-success" x-text="editFilaForm.id ? 'Guardar cambios' : 'Guardar designación'"></button>
                  </div>
                </div>
            </div>
        </div>

        @endif

        @unless($esDecanatura)
            @include('partials.modal-confirmacion')
            @include('partials.modal-notificacion')
        @endunless
    </div>
@endsection

@push('scripts')
    <script defer src="{{ asset('resources/assets/js/designaciones/carrera.js') }}"></script>
@endpush