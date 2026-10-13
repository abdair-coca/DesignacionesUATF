window.designacionesListaFiltros = (config = {}) => ({
    designaciones: config.designaciones ?? [],
    filtro: config.filtro ?? '',
    carrera: config.carrera ?? '',
    gestion: config.gestion ?? '',
    pagina: 1,
    porPagina: 10,

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
    gestiones() {
        return [...new Set(this.designaciones.map((designacion) => String(designacion.gestion ?? '').trim()).filter(Boolean))]
            .sort((a, b) => b.localeCompare(a, 'es', { numeric: true }));
    },
    limpiarFiltros() {
        this.filtro = '';
        this.carrera = '';
        this.gestion = '';
        this.pagina = 1;
    },
    filasFiltradas() {
        if (config.soloDescripcion) {
            const termino = this.filtro.trim().toLowerCase();
            if (!termino) return this.designaciones;

            return this.designaciones.filter((designacion) => (designacion.detalle || '').toLowerCase().includes(termino));
        }

        const terminos = this.normalizarTexto(this.filtro).trim().split(/\s+/).filter(Boolean);

        return this.designaciones.filter((designacion) => {
            if (this.carrera) {
                const valorCarrera = String(designacion.programa_codigo || designacion.programa_nombre || '');
                if (valorCarrera !== this.carrera) return false;
            }

            if (this.gestion && String(designacion.gestion ?? '') !== this.gestion) return false;

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
});
