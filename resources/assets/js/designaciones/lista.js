window.designacionesLista = (config = {}) => ({
    /* Modal de creacion y datos que provienen del servidor. */
    crearModalOpen: false,
    crearForm: { gestion: '', periodo: '', obs: '', importarDesde: '' },
    contextoActual: config.contextoActual ?? { gestion: '', periodo: '' },
    modalConfirmacionOpen: false,
    modalConfirmacionData: { titulo: '', mensaje: '', botonTexto: 'Confirmar', botonColor: 'bg-[#00acac] hover:bg-[#008a8a]' },
    accionConfirmada: null,
    modalNotificacionOpen: false,
    modalNotificacionData: { tipo: 'info', titulo: '', mensaje: '' },
    designaciones: config.designaciones ?? [],
    filtro: config.filtro ?? '',
    pagina: 1,
    porPagina: 10,
    rutaDetalle: config.rutaDetalle ?? '',
    rutaPdf: config.rutaPdf ?? '',

    /* Filtro y paginacion local de la tabla. */
    limpiarFiltro() {
        this.filtro = '';
        this.pagina = 1;
    },
    filasFiltradas() {
        const termino = this.filtro.trim().toLowerCase();
        if (!termino) return this.designaciones;
        return this.designaciones.filter((d) => (d.detalle || '').toLowerCase().includes(termino));
    },
    totalPaginas() {
        return Math.max(1, Math.ceil(this.filasFiltradas().length / this.porPagina));
    },
    paginaInicio() {
        return (this.pagina - 1) * this.porPagina;
    },
    paginadas() {
        const inicio = this.paginaInicio();
        return this.filasFiltradas().slice(inicio, inicio + this.porPagina);
    },
    irAPagina(pagina) {
        this.pagina = Math.min(this.totalPaginas(), Math.max(1, pagina));
    },

    /* Apertura, limpieza y validacion previa del formulario de creacion. */
    abrirCrear() {
        this.crearForm = {
            gestion: this.contextoActual.gestion || '',
            periodo: this.contextoActual.periodo || '',
            obs: '',
            importarDesde: '',
        };
        this.crearModalOpen = true;
    },
    cerrarCrear() {
        this.crearModalOpen = false;
    },
    limpiarCrear() {
        this.crearForm = { gestion: '', periodo: '', obs: '', importarDesde: '' };
    },
    confirmarCrear() {
        if (!this.crearForm.gestion || !this.crearForm.periodo) {
            this.mostrarNotificacion('error', 'Datos incompletos', 'No existe un contexto académico vigente para crear la designación.');
            return;
        }
        if (this.crearForm.importarDesde) {
            this.abrirConfirmacion(
                'Importar designación',
                '¿Desea importar esta designación de la gestión anterior a la nueva?',
                'Importar',
                'bg-[#00acac] hover:bg-[#008a8a]',
                () => document.getElementById('form-crear').submit(),
            );
            return;
        }
        this.abrirConfirmacion(
            'Crear designación',
            '¿Confirma el registro de la nueva designación?',
            'Crear',
            'bg-[#00acac] hover:bg-[#008a8a]',
            () => document.getElementById('form-crear').submit(),
        );
    },

    /* Confirmaciones y notificaciones compartidas por la vista. */
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
