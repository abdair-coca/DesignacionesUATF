window.vicerrectoradoDesignacionesLista = (config = {}) => ({
    designaciones: config.designaciones ?? [],
    filtro: config.filtro ?? '',
    carrera: config.carrera ?? '',
    pagina: 1,
    porPagina: 10,
    rutaDetalle: config.rutaDetalle ?? '',
    rutaPdf: config.rutaPdf ?? '',

    normalizarTexto(texto) {
        return String(texto ?? '')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLocaleLowerCase('es');
    },
    carreras() {
        const opciones = new Map();

        this.designaciones.forEach((designacion) => {
            const codigo = String(designacion.programa_codigo ?? '').trim();
            const nombre = String(designacion.programa_nombre ?? '').trim();
            const valor = codigo || nombre;

            if (valor && !opciones.has(valor)) {
                opciones.set(valor, {
                    valor,
                    etiqueta: nombre && codigo ? `${nombre} (${codigo})` : (nombre || codigo),
                });
            }
        });

        return [...opciones.values()].sort((a, b) => a.etiqueta.localeCompare(b.etiqueta, 'es'));
    },
    limpiarFiltros() {
        this.filtro = '';
        this.carrera = '';
        this.pagina = 1;
    },
    filasFiltradas() {
        const terminos = this.normalizarTexto(this.filtro).trim().split(/\s+/).filter(Boolean);

        return this.designaciones.filter((designacion) => {
            if (this.carrera) {
                const valorCarrera = String(designacion.programa_codigo || designacion.programa_nombre || '');
                if (valorCarrera !== this.carrera) return false;
            }

            if (terminos.length === 0) return true;

            const texto = this.normalizarTexto([
                designacion.fecha,
                designacion.detalle,
                designacion.gestion,
                designacion.periodo,
                designacion.observacion,
                designacion.estado,
                designacion.programa_nombre,
                designacion.programa_codigo,
            ].join(' '));

            return terminos.every((termino) => texto.includes(termino));
        });
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
    detalleUrl(id) {
        const url = this.rutaDetalle.replace('__ID__', id);

        return this.carrera ? `${url}&carrera=${encodeURIComponent(this.carrera)}` : url;
    },
    pdfUrl(id) {
        const url = this.rutaPdf.replace('__ID__', id);

        return this.carrera ? `${url}&carrera=${encodeURIComponent(this.carrera)}` : url;
    },
});
