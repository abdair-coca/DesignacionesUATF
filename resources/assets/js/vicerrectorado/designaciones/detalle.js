window.vicerrectoradoRevision = (config = {}) => ({
    filas: (config.filas ?? []).map((id) => Number(id)),
    filasSeleccionadas: [],
    decisiones: config.decisiones ?? {},
    decisionesDisponibles: config.decisionesDisponibles ?? false,
    estadosDisponibles: config.estadosDisponibles ?? false,
    guardarUrl: config.guardarUrl ?? '',
    estadoDesignacion: config.estadoDesignacion ?? 'SOLICITADO',
    observacionRevision: config.observacionRevision ?? '',
    guardarRevisionUrl: config.guardarRevisionUrl ?? '',
    estadoModalOpen: false,
    estadoModalFilaId: null,
    decisionModalOpen: false,
    filasDecisionIds: [],
    decisionModalMultiple: false,
    decisionModalValue: '',
    observacionTexto: '',
    guardandoDecision: false,
    errorGuardado: '',
    revisionCerrada: false,
    mensajeRevision: '',
    revisionDesignacionModalOpen: false,
    estadoRevisionValue: '',
    observacionRevisionTexto: '',
    guardandoEstadoDesignacion: false,
    errorRevisionDesignacion: '',
    abrirRevisionDesignacion(estado) {
        if (this.guardandoDecision || this.guardandoEstadoDesignacion) return;

        this.estadoRevisionValue = estado;
        this.observacionRevisionTexto = estado === 'OBSERVADA' ? (this.observacionRevision ?? '') : '';
        this.errorRevisionDesignacion = '';
        this.revisionDesignacionModalOpen = true;
    },
    async guardarRevisionDesignacion() {
        if (this.guardandoDecision || this.guardandoEstadoDesignacion) return;

        const estado = this.estadoRevisionValue;
        const observacion = this.observacionRevisionTexto.trim();

        if (estado === 'OBSERVADA' && observacion === '') {
            this.errorRevisionDesignacion = 'Escriba el motivo de la observación.';
            return;
        }

        this.guardandoEstadoDesignacion = true;
        this.errorRevisionDesignacion = '';

        try {
            const respuesta = await fetch(this.guardarRevisionUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ estado, observacion }),
            });

            if (!respuesta.ok) throw new Error('No fue posible completar la operación.');

            const resultado = await respuesta.json();
            this.estadoDesignacion = resultado.estado_designacion ?? estado;
            this.observacionRevision = this.estadoDesignacion === 'APROBADO'
                ? ''
                : (resultado.observacion_revision ?? observacion);
            this.revisionDesignacionModalOpen = false;
            this.mensajeRevision = resultado.message ?? 'Operación confirmada.';
        } catch (error) {
            this.errorRevisionDesignacion = 'No fue posible completar la operación. Intente nuevamente.';
        } finally {
            this.guardandoEstadoDesignacion = false;
        }
    },
    cerrarRevisionDesignacion() {
        if (this.guardandoEstadoDesignacion) return;

        this.revisionDesignacionModalOpen = false;
        this.estadoRevisionValue = '';
        this.observacionRevisionTexto = '';
        this.errorRevisionDesignacion = '';
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
        const decision = this.decisionFila(id);

        if (decision) return decision;
        if (!this.estadosDisponibles) return 'SIN_ESTADO';

        return 'PENDIENTE';
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
        const estado = this.estadoFila(this.estadoModalFilaId);
        const titulos = {
            APROBADA: 'Asignación aprobada',
            PENDIENTE: 'Asignación pendiente',
            RECHAZADA: 'Asignación rechazada',
            SIN_ESTADO: 'Estado no disponible',
        };

        return titulos[estado] ?? 'Estado no disponible';
    },
    observacionEstadoFila() {
        const id = this.estadoModalFilaId;

        return this.estadoFila(id) === 'RECHAZADA' ? this.motivoFila(id) : '';
    },
    abrirDecision(id, decision) {
        if (!this.decisionesDisponibles || this.revisionCerrada || this.guardandoDecision || this.guardandoEstadoDesignacion) return;

        const filaId = Number(id);
        this.filasDecisionIds = [filaId];
        this.decisionModalMultiple = false;
        this.decisionModalValue = decision;
        this.observacionTexto = this.decisiones[filaId]?.decision === decision
            ? (this.decisiones[filaId].observacion ?? '')
            : '';
        this.errorGuardado = '';
        this.decisionModalOpen = true;
    },
    alternarSeleccion(id, seleccionada) {
        if (!this.decisionesDisponibles || this.revisionCerrada || this.guardandoDecision || this.guardandoEstadoDesignacion) return;

        const filaId = Number(id);
        this.filasSeleccionadas = seleccionada
            ? [...new Set([...this.filasSeleccionadas, filaId])]
            : this.filasSeleccionadas.filter((seleccionado) => seleccionado !== filaId);
    },
    seleccionarTodas(seleccionadas) {
        if (!this.decisionesDisponibles || this.revisionCerrada || this.guardandoDecision || this.guardandoEstadoDesignacion) return;

        this.filasSeleccionadas = seleccionadas ? [...this.filas] : [];
    },
    todasSeleccionadas() {
        return this.filas.length > 0 && this.filasSeleccionadas.length === this.filas.length;
    },
    abrirDecisionSeleccionadas(decision) {
        if (!this.decisionesDisponibles || this.revisionCerrada || this.guardandoDecision || this.guardandoEstadoDesignacion || this.filasSeleccionadas.length === 0) return;

        this.filasDecisionIds = [...this.filasSeleccionadas];
        this.decisionModalMultiple = true;
        this.decisionModalValue = decision;
        this.observacionTexto = '';
        this.errorGuardado = '';
        this.decisionModalOpen = true;
    },
    async guardarDecision() {
        if (!this.decisionesDisponibles || this.revisionCerrada || this.guardandoDecision || this.guardandoEstadoDesignacion || this.filasDecisionIds.length === 0) return;

        const filasDecididas = [...this.filasDecisionIds];
        const decision = this.decisionModalValue;
        const observacion = this.observacionTexto.trim();
        this.guardandoDecision = true;
        this.errorGuardado = '';

        try {
            const respuesta = await fetch(this.guardarUrl, {
                method: 'POST',
                headers: {
                    Accept: 'application/json',
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    filas: filasDecididas,
                    estado: decision,
                    observacion,
                }),
            });

            if (!respuesta.ok) throw new Error('No fue posible guardar las decisiones.');

            const resultado = await respuesta.json();
            const actualizadas = (resultado.actualizadas ?? []).map(Number);
            const fallidas = (resultado.fallidas ?? []).map(Number);

            if (resultado.estado_designacion) {
                this.estadoDesignacion = resultado.estado_designacion;
                if (this.estadoDesignacion === 'APROBADO') this.observacionRevision = '';
            }

            actualizadas.forEach((id) => {
                this.decisiones[id] = { decision, observacion };
            });
            this.filasSeleccionadas = [...new Set([
                ...this.filasSeleccionadas.filter((id) => !filasDecididas.includes(id)),
                ...fallidas,
            ])];

            if (actualizadas.length === 0) {
                this.filasSeleccionadas = fallidas.length > 0 ? fallidas : filasDecididas;
                this.errorGuardado = 'No se guardó ninguna decisión. Corrige o reintenta las filas seleccionadas.';
                return;
            }

            this.guardandoDecision = false;
            this.cerrarDecision();
            this.filasSeleccionadas = fallidas;
            this.mensajeRevision = fallidas.length > 0
                ? `Se guardaron ${actualizadas.length} decisiones; ${fallidas.length} filas no se guardaron y siguen seleccionadas.`
                : `${actualizadas.length} decisiones guardadas correctamente.`;
        } catch (error) {
            this.errorGuardado = 'No fue posible guardar las decisiones. Intente nuevamente.';
        } finally {
            this.guardandoDecision = false;
        }
    },
    cerrarDecision() {
        if (this.guardandoDecision) return;

        this.decisionModalOpen = false;
        this.filasDecisionIds = [];
        this.decisionModalMultiple = false;
        this.decisionModalValue = '';
        this.observacionTexto = '';
        this.errorGuardado = '';
    },
    decisionFila(id) {
        return this.decisiones[id]?.decision ?? '';
    },
    motivoFila(id) {
        return this.decisiones[id]?.observacion ?? '';
    },
    confirmarRevision() {
        if (this.revisionCerrada || this.guardandoDecision || this.guardandoEstadoDesignacion) return;

        this.revisionCerrada = true;
        this.cerrarDecision();
        this.filasSeleccionadas = [];
        this.mensajeRevision = 'Revisión cerrada. Las decisiones confirmadas se guardaron correctamente.';
    },
});

/* Fase 2: estado y operaciones del listado de designaciones. */
