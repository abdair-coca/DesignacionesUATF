/* Fase 1: estado y operaciones del detalle de una designacion. */
window.designacionesCarrera = (config = {}) => ({
    /* Estado inicial y datos dinamicos entregados por Blade. */
    editCabeceraOpen: false,
    editCabeceraForm: {
        obs: config.observacionInicial ?? '',
    },
    editFilaOpen: false,
    editFilaForm: {
        id: '',
        docente_id: '',
        materia_id: '',
        id_grupo: '',
        horasTeoricas: '',
        horasPracticas: '',
        horasLaboratorio: '',
    },
    erroresFila: {},
    docentesBusquedaGlobal: [],
    cargandoDocentesGlobales: false,
    errorDocentesGlobales: '',
    rutaBusquedaDocentes: config.rutaBusquedaDocentes ?? '',
    docenteSeleccionado: null,
    materiasDisponibles: config.materiasDisponibles ?? [],
    materiasOferta: config.materiasOferta ?? [],
    gruposOfertaDisponibles: config.gruposOfertaDisponibles ?? true,
    filas: config.filas ?? [],
    estadoModalOpen: false,
    estadoModalFilaId: null,
    filtro: '',
    filtroDocente: '',
    filtroMateria: '',
    dropdownDocente: false,
    dropdownMateria: false,
    indiceDocente: 0,
    indiceMateria: 0,
    /* Busqueda local de filas de asignacion. */
    limpiarFiltro() {
        this.filtro = '';
    },
    coincide(fila) {
        const termino = this.filtro.trim().toLowerCase();
        if (!termino) return true;
        const texto = [fila.docente_nombre, fila.ci, fila.materia_sigla, fila.materia_nombre]
            .filter(Boolean)
            .join(' ')
            .toLowerCase();
        return texto.includes(termino);
    },
    filasFiltradas() {
        return this.filas.filter((fila) => this.coincide(fila));
    },
    hayCoincidencias() {
        return this.filasFiltradas().length > 0;
    },
    mostrarEstadoFila(id) {
        this.estadoModalFilaId = Number(id);
        this.estadoModalOpen = true;
    },
    cerrarEstadoFila() {
        this.estadoModalOpen = false;
        this.estadoModalFilaId = null;
    },
    estadoFila(id) {
        const fila = this.filas.find((item) => Number(item.id) === Number(id));
        if (!fila || !fila.decisiones_disponibles) return 'SIN_ESTADO';

        return fila.estado_decision || 'PENDIENTE';
    },
    etiquetaEstadoFila(id) {
        const estado = this.estadoFila(id);
        if (estado === 'PENDIENTE') return 'Pendiente';
        if (estado === 'SIN_ESTADO') return 'Sin estado';

        return estado;
    },
    claseEstadoFila(id) {
        const clases = {
            APROBADA: 'badge-success',
            PENDIENTE: 'badge-warning',
            RECHAZADA: 'badge-danger',
            SIN_ESTADO: 'badge-secondary',
        };

        return clases[this.estadoFila(id)] ?? 'badge-secondary';
    },
    tituloEstadoFila() {
        const titulos = {
            APROBADA: 'Asignación aprobada',
            PENDIENTE: 'Asignación pendiente',
            RECHAZADA: 'Asignación rechazada',
            SIN_ESTADO: 'Estado no disponible',
        };

        return titulos[this.estadoFila(this.estadoModalFilaId)] ?? 'Estado no disponible';
    },
    observacionEstadoFila() {
        const fila = this.filas.find((item) => Number(item.id) === this.estadoModalFilaId);

        return this.estadoFila(this.estadoModalFilaId) === 'RECHAZADA'
            ? (fila?.observacion_decision ?? '')
            : '';
    },
    modalConfirmacionOpen: false,
    modalConfirmacionData: { titulo: '', mensaje: '', botonTexto: 'Confirmar', botonColor: 'bg-[#00acac] hover:bg-[#008a8a]' },
    accionConfirmada: null,
    modalNotificacionOpen: false,
    modalNotificacionData: { tipo: 'info', titulo: '', mensaje: '' },
    /* Edicion y confirmacion de la cabecera de la designacion. */
    abrirEdicionCabecera() {
        this.editCabeceraForm.obs = config.observacionInicial ?? '';
        this.editCabeceraOpen = true;
    },
    cerrarEdicionCabecera() {
        this.editCabeceraOpen = false;
    },
    confirmarEdicionCabecera() {
        this.abrirConfirmacion(
            'Guardar cambios',
            '¿Confirma la actualización de esta designación?',
            'Guardar',
            'bg-[#00acac] hover:bg-[#008a8a]',
            () => document.getElementById('form-editar').submit(),
        );
    },
    /* Apertura y cierre de nueva asignacion o reasignacion. */
    abrirEdicionFila(fila) {
        this.limpiarErroresFila();
        this.editFilaForm = {
            id: fila.id,
            docente_id: fila.docente_id,
            materia_id: fila.materia_id,
            id_grupo: fila.grupo_id,
            horasTeoricas: fila.horas_teoricas,
            horasPracticas: fila.horas_practicas,
            horasLaboratorio: fila.horas_laboratorio,
        };
        this.docenteSeleccionado = {
            id: fila.docente_id,
            nombre: fila.docente_nombre,
            ci: fila.ci,
        };
        this.reiniciarCombobox();
        this.editFilaOpen = true;
    },
    cerrarEdicionFila() {
        this.editFilaOpen = false;
        this.limpiarErroresFila();
    },
    abrirNuevaDesignacion() {
        this.limpiarErroresFila();
        this.editFilaForm = {
            id: 0,
            docente_id: '',
            materia_id: '',
            id_grupo: '',
            horasTeoricas: '',
            horasPracticas: '',
            horasLaboratorio: '',
        };
        this.docenteSeleccionado = null;
        this.reiniciarCombobox();
        this.editFilaOpen = true;
    },
    /* Comboboxes buscables de docente y materia. */
    reiniciarCombobox() {
        this.filtroDocente = this.textoDocente();
        this.filtroMateria = this.textoMateria();
        this.docentesBusquedaGlobal = [];
        this.errorDocentesGlobales = '';
        this.dropdownDocente = false;
        this.dropdownMateria = false;
        this.indiceDocente = 0;
        this.indiceMateria = 0;
    },
    normalizarTexto(texto) {
        return String(texto ?? '').toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '').replace(/[^a-z0-9]/g, '');
    },
    docentesFiltrados() {
        const termino = this.normalizarTexto(this.filtroDocente);
        return this.docentesBusquedaGlobal.filter((d) => !termino || this.normalizarTexto(`${d.nombre} ${d.ci ?? ''}`).includes(termino));
    },
    actualizarFiltroDocente(valor) {
        this.filtroDocente = valor;
        this.docentesBusquedaGlobal = [];
        this.cargandoDocentesGlobales = false;
        this.errorDocentesGlobales = '';
        this.abrirDropdownDocente();
    },
    async seleccionarOBuscarDocente() {
        const opciones = this.docentesFiltrados();

        if (opciones.length > 0) {
            this.seleccionarDocente(opciones[this.indiceDocente] ?? opciones[0]);
            return;
        }

        await this.buscarDocentesUniversidad();
    },
    async buscarDocentesUniversidad() {
        const termino = this.filtroDocente.trim();

        if (!termino) {
            this.docentesBusquedaGlobal = [];
            this.errorDocentesGlobales = '';
            this.abrirDropdownDocente();
            return;
        }

        this.cargandoDocentesGlobales = true;
        this.errorDocentesGlobales = '';

        try {
            const respuesta = await fetch(`${this.rutaBusquedaDocentes}?q=${encodeURIComponent(termino)}`, {
                headers: { Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            });

            if (!respuesta.ok) {
                throw new Error('Busqueda no disponible');
            }

            const resultados = await respuesta.json();
            this.docentesBusquedaGlobal = Array.isArray(resultados) ? resultados : [];
            this.dropdownDocente = true;
            this.indiceDocente = 0;
        } catch (error) {
            this.docentesBusquedaGlobal = [];
            this.dropdownDocente = true;
            this.errorDocentesGlobales = 'No fue posible buscar docentes. Intente nuevamente.';
        } finally {
            this.cargandoDocentesGlobales = false;
        }
    },
    /* Oferta autorizada, grupos y horas de la materia. */
    materiasFiltradas() {
        const termino = this.normalizarTexto(this.filtroMateria);
        return this.materiasOferta.filter((m) => {
            const coincide = !termino || this.normalizarTexto(`${m.sigla} ${m.nombre}`).includes(termino);
            const esActual = String(m.id) === String(this.editFilaForm.materia_id);
            const soloEdicion = m.solo_edicion === true;
            return coincide && (!soloEdicion || esActual)
                && (esActual || !this.gruposOfertaDisponibles || this.gruposDisponibles(m).length > 0);
        });
    },
    materiaSeleccionada() {
        return this.materiasOferta.find((m) => String(m.id) === String(this.editFilaForm.materia_id)) ?? null;
    },
    grupoSiguienteMateria() {
        const materia = this.materiaSeleccionada();
        if (!materia) return 0;

        const ultimoGrupoAsignado = Math.max(0, ...this.filas
            .filter((fila) => String(fila.materia_id) === String(materia.id) && Number(fila.docente_id) > 0)
            .map((fila) => Number(fila.grupo_id)));

        return Math.max(Number(materia.grupo_siguiente ?? 0), ultimoGrupoAsignado + 1);
    },
    mensajeGrupoMateria() {
        const materia = this.materiaSeleccionada();
        const siguienteGrupo = this.grupoSiguienteMateria();
        if (!materia || siguienteGrupo < 1) return '';

        const tieneDesignaciones = this.filas.some((fila) =>
            String(fila.materia_id) === String(materia.id) && Number(fila.docente_id) > 0,
        );
        if (!tieneDesignaciones) return `Grupo siguiente oficial: ${siguienteGrupo}.`;

        const filaActual = this.filas.find((fila) => String(fila.id) === String(this.editFilaForm.id));
        const conservaGrupo = Number(this.editFilaForm.id) > 0
            && filaActual
            && String(filaActual.materia_id) === String(materia.id)
            && Number(filaActual.grupo_id) === Number(this.editFilaForm.id_grupo);

        return conservaGrupo
            ? `La materia ya tiene designaciones. Se conserva el grupo actual y el siguiente grupo disponible es ${siguienteGrupo}.`
            : `La materia ya tiene designaciones. Se asignará el grupo siguiente: ${siguienteGrupo}.`;
    },
    gruposDisponibles(materia) {
        if (!materia) return [];

        const grupos = (materia?.grupos ?? []).map((grupo) => Number(grupo));
        const gruposOcupados = this.filas.filter((fila) =>
            String(fila.materia_id) === String(materia.id)
            && Number(fila.docente_id) > 0,
        ).map((fila) => Number(fila.grupo_id));
        const ultimoGrupoAsignado = Math.max(0, ...gruposOcupados);
        const siguienteGrupo = Math.max(Number(materia.grupo_siguiente ?? 0), ultimoGrupoAsignado + 1);

        if (Number(this.editFilaForm.id) === 0) return siguienteGrupo > 0 ? [siguienteGrupo] : [];

        const gruposDisponibles = grupos.filter((grupo) => !this.filas.some((fila) =>
            String(fila.id) !== String(this.editFilaForm.id)
            && String(fila.materia_id) === String(materia.id)
            && Number(fila.grupo_id) === grupo
            && Number(fila.docente_id) > 0,
        ));
        if (siguienteGrupo > 0 && !gruposDisponibles.includes(siguienteGrupo)) gruposDisponibles.push(siguienteGrupo);
        return gruposDisponibles;
    },
    gruposMateriaSeleccionada() {
        const materia = this.materiasOferta.find((m) => String(m.id) === String(this.editFilaForm.materia_id));
        const grupos = this.gruposDisponibles(materia);
        const grupoActual = Number(this.editFilaForm.id_grupo);
        if (materia && grupoActual > 0 && !grupos.includes(grupoActual)) {
            grupos.push(grupoActual);
        }
        return grupos.sort((a, b) => a - b);
    },
    /* Seleccion y navegacion de opciones. */
    textoDocente() {
        const docente = this.docenteSeleccionado;
        return docente ? `${docente.nombre}${docente.ci ? ` (${docente.ci})` : ''}` : '';
    },
    textoMateria() {
        const materia = this.materiasOferta.find((m) => String(m.id) === String(this.editFilaForm.materia_id));
        return materia ? `${materia.sigla} — ${materia.nombre}` : '';
    },
    abrirDropdownDocente() {
        this.dropdownDocente = true;
        this.indiceDocente = 0;
    },
    abrirDropdownMateria() {
        this.dropdownMateria = true;
        this.indiceMateria = 0;
    },
    actualizarFiltroMateria(valor) {
        this.filtroMateria = valor;
        this.indiceMateria = 0;
        this.dropdownMateria = true;
    },
    cerrarDropdownDocente() {
        this.dropdownDocente = false;
    },
    cerrarDropdownMateria() {
        this.dropdownMateria = false;
    },
    seleccionarDocente(docente) {
        this.docenteSeleccionado = docente;
        this.editFilaForm.docente_id = docente.id;
        this.filtroDocente = this.textoDocente();
        this.dropdownDocente = false;
    },
    seleccionarMateria(materia) {
        this.editFilaForm.materia_id = materia.id;
        const grupos = this.gruposDisponibles(materia);
        const grupoActual = Number(this.editFilaForm.id_grupo);
        if (!grupos.includes(grupoActual)) {
            this.editFilaForm.id_grupo = grupos[0] ?? '';
        }
        this.editFilaForm.horasTeoricas = materia.horas_teoricas;
        this.editFilaForm.horasPracticas = materia.horas_practicas;
        this.editFilaForm.horasLaboratorio = materia.horas_laboratorio;
        this.filtroMateria = this.textoMateria();
        this.indiceMateria = 0;
        this.dropdownMateria = false;
    },
    navegarDocente(evento, opciones) {
        if (evento.key === 'ArrowDown') {
            this.indiceDocente = (this.indiceDocente + 1) % Math.max(opciones.length, 1);
        } else if (evento.key === 'ArrowUp') {
            this.indiceDocente = (this.indiceDocente - 1 + Math.max(opciones.length, 1)) % Math.max(opciones.length, 1);
        } else if (evento.key === 'Enter' && opciones[this.indiceDocente]) {
            this.seleccionarDocente(opciones[this.indiceDocente]);
        } else if (evento.key === 'Escape') {
            this.dropdownDocente = false;
        }
    },
    navegarMateria(evento, opciones) {
        if (evento.key === 'Escape') {
            this.dropdownMateria = false;
            return;
        }
        if (opciones.length === 0) return;
        if (evento.key === 'ArrowDown') {
            this.indiceMateria = (this.indiceMateria + 1) % Math.max(opciones.length, 1);
        } else if (evento.key === 'ArrowUp') {
            this.indiceMateria = (this.indiceMateria - 1 + Math.max(opciones.length, 1)) % Math.max(opciones.length, 1);
        } else if (evento.key === 'Enter') {
            const indice = Math.min(this.indiceMateria, opciones.length - 1);
            this.seleccionarMateria(opciones[indice]);
        }
    },
    /* Validacion y envio del formulario de detalle. */
    ciDocenteSeleccionado() {
        const detalle = this.docenteSeleccionado;
        return detalle ? detalle.ci : '';
    },
    limpiarErroresFila() {
        this.erroresFila = {};
    },
    validarFila() {
        const errores = {};
        const horas = [
            this.editFilaForm.horasTeoricas,
            this.editFilaForm.horasPracticas,
            this.editFilaForm.horasLaboratorio,
        ];
        const esEnteroNoNegativo = (valor) => /^\d+$/.test(String(valor ?? ''));
        const esIdentificador = (valor) => /^\d+$/.test(String(valor ?? '')) && Number(valor) > 0;

        if (!esIdentificador(this.editFilaForm.docente_id)) errores.docente = 'Seleccione un docente.';
        if (!esIdentificador(this.editFilaForm.materia_id)) errores.materia = 'Seleccione una materia.';
        if (!esIdentificador(this.editFilaForm.id_grupo)) errores.grupo = 'Seleccione un grupo.';
        if (esIdentificador(this.editFilaForm.materia_id)
            && esIdentificador(this.editFilaForm.id_grupo)
            && !this.gruposMateriaSeleccionada().includes(Number(this.editFilaForm.id_grupo))) {
            errores.grupo = 'Seleccione un grupo valido para la materia.';
        }
        if (horas.some((hora) => !esEnteroNoNegativo(hora))) {
            errores.horas = 'Las horas deben ser enteros no negativos.';
        }

        const materia = this.materiaSeleccionada();
        if (materia && horas.every((hora) => esEnteroNoNegativo(hora))) {
            const valores = horas.map((hora) => Number(hora));
            if (valores[0] > Number(materia.horas_teoricas ?? 0)
                || valores[1] > Number(materia.horas_practicas ?? 0)
                || valores[2] > Number(materia.horas_laboratorio ?? 0)) {
                errores.horasOficiales = 'Las horas no pueden superar las horas oficiales de la materia.';
            }
            if (valores.every((hora) => hora === 0)) errores.horasCero = 'Las horas no pueden ser todas cero.';
        }

        this.erroresFila = errores;
        return Object.keys(errores).length === 0;
    },
    guardarFila() {
        if (!this.validarFila()) return;

        const formulario = document.getElementById('form-editar-fila');
        if (!formulario) return;
        if (typeof formulario.requestSubmit === 'function') {
            formulario.requestSubmit();
        } else {
            formulario.submit();
        }
    },
    /* Confirmaciones y notificaciones reutilizables. */
    abrirConfirmacion(titulo, mensaje, botonTexto, botonColor, accion) {
        this.modalConfirmacionData = { titulo, mensaje, botonTexto, botonColor };
        this.accionConfirmada = accion;
        this.modalConfirmacionOpen = true;
    },
    ejecutarConfirmacion() {
        this.modalConfirmacionOpen = false;
        if (typeof this.accionConfirmada === 'function') {
            this.accionConfirmada();
        }
    },
    mostrarNotificacion(tipo, titulo, mensaje) {
        this.modalNotificacionData = { tipo, titulo, mensaje };
        this.modalNotificacionOpen = true;
    },
    cerrarNotificacion() {
        this.modalNotificacionOpen = false;
    },
});

/* Acciones individuales y grupales de decisión en Vicerrectorado. */
