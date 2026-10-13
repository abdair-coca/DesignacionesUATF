@push('styles')
    <link href="{{ asset('resources/assets/css/shared/designacion-pdf-modal.css') }}" rel="stylesheet">
@endpush

@pushOnce('scripts', 'designacion-pdf-modal')
    <script defer src="{{ asset('resources/assets/js/shared/designacion-pdf-modal.js') }}?v={{ filemtime(resource_path('assets/js/shared/designacion-pdf-modal.js')) }}"></script>
@endPushOnce

<div
    x-data="designacionPdfModal({ rutaPdf: @js($rutaPdf) })"
    x-show="abierto"
    x-cloak
    x-transition.opacity
    class="app-modal no-print"
    role="dialog"
    aria-modal="true"
    aria-labelledby="designacion-pdf-title"
    @designacion-pdf-open.window="abrir($event.detail.id, $event.detail.filaId)"
    @keydown.escape.window="cerrar()"
>
    <div class="app-modal-dialog designacion-pdf-dialog" @click.outside="cerrar()">
        <form x-ref="form" class="app-modal-content" method="POST" :action="accion" target="_blank" @submit="cerrar()">
            @csrf
            <input type="hidden" name="fila_id" x-model="filaId">
            <div class="app-modal-header">
                <div>
                    <span class="app-modal-kicker">Impresión de designación</span>
                    <h2 id="designacion-pdf-title" class="app-modal-title">Datos de la carta docente</h2>
                </div>
                <button type="button" @click="cerrar()" class="app-modal-close" aria-label="Cerrar modal">&times;</button>
            </div>

            <div class="app-modal-body">
                <p class="designacion-pdf-note">Los valores precargados son ficticios; reemplácelos antes de generar. Se incluirán las asignaturas de esta designación y los cambios se usarán solo para este PDF.</p>
                <div class="designacion-pdf-form-grid">
                    <div class="designacion-pdf-field designacion-pdf-field-wide">
                        <label for="pdf-condicion">Condición del docente</label>
                        <select id="pdf-condicion" name="condicion" x-ref="condicion" required>
                            <option value="">Seleccione una condición</option>
                            <option value="DOC_ORD_TITULAR" selected>Docente ordinario titular (ejemplo)</option>
                            <option value="DOC_ORD_CONTRATADO">Docente ordinario contratado (ejemplo)</option>
                            <option value="DOC_EXTR">Docente extraordinario (ejemplo)</option>
                        </select>
                    </div>
                    <div class="designacion-pdf-field designacion-pdf-field-wide">
                        <label for="pdf-leyenda-revision">Leyenda superior de la carta (editable)</label>
                        <input id="pdf-leyenda-revision" name="leyenda_revision" type="text" value="EJEMPLO FICTICIO - REEMPLAZAR" maxlength="120">
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-grado">Último grado académico</label>
                        <input id="pdf-grado" name="grado_academico" type="text" value="M.Sc. Ing. Nombre Ficticio" maxlength="120" required>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-facultad">Facultad</label>
                        <input id="pdf-facultad" name="facultad" type="text" value="Facultad Ficticia" maxlength="180" required>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-fecha-resolucion">Fecha de emisión de la resolución</label>
                        <input id="pdf-fecha-resolucion" name="fecha_resolucion" type="date" value="2000-01-01" required>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-numero-cite">Número de cite de la Facultad</label>
                        <input id="pdf-numero-cite" name="numero_cite" type="text" value="CITE-FICTICIO-001" maxlength="80" required>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-numero-resolucion">Número de resolución</label>
                        <input id="pdf-numero-resolucion" name="numero_resolucion" type="text" value="001-FICTICIO" maxlength="80" required>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-modalidad">Modalidad de dedicación</label>
                        <select id="pdf-modalidad" name="modalidad" required>
                            <option value="">Seleccione una modalidad</option>
                            <option value="HORARIO" selected>Tiempo Horario (ejemplo)</option>
                            <option value="COMPLETO">Tiempo Completo</option>
                        </select>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-carrera-semestralizada">¿La carrera es semestralizada?</label>
                        <select id="pdf-carrera-semestralizada" name="carrera_semestralizada" required>
                            <option value="SI" selected>Sí (ejemplo)</option>
                            <option value="NO">No</option>
                        </select>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-fecha-inicio">Inicio de actividades</label>
                        <input id="pdf-fecha-inicio" name="fecha_inicio" type="date" value="2000-02-01" required>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-decan-nombre">Nombre completo del decano o decana</label>
                        <input id="pdf-decan-nombre" name="decan_nombre" type="text" value="M.Sc. Ing. Decano Ficticio" maxlength="160" required>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-vicerrector-nombre">Nombre del vicerrector</label>
                        <input id="pdf-vicerrector-nombre" name="vicerrector_nombre" type="text" value="M.Sc. Ing. Vicerrector Ficticio" maxlength="160" required>
                    </div>
                    <div class="designacion-pdf-field">
                        <label for="pdf-vicerrector-cargo">Cargo del vicerrector</label>
                        <input id="pdf-vicerrector-cargo" name="vicerrector_cargo" type="text" value="Vicerrector Ficticio" maxlength="120" required>
                    </div>
                </div>
            </div>

            <div class="app-modal-footer">
                <button type="button" @click="cerrar()" class="btn btn-outline-secondary">Cancelar</button>
                <button type="submit" class="btn btn-dark">Generar PDF</button>
            </div>
        </form>
    </div>
</div>
