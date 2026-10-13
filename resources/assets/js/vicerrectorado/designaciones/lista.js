window.vicerrectoradoDesignacionesLista = (config = {}) => ({
    ...window.designacionesListaFiltros(config),
    rutaDetalle: config.rutaDetalle ?? '',
    rutaPdf: config.rutaPdf ?? '',

    detalleUrl(id) {
        const url = this.rutaDetalle.replace('__ID__', id);

        return this.carrera ? `${url}&carrera=${encodeURIComponent(this.carrera)}` : url;
    },
    pdfUrl(id) {
        const url = this.rutaPdf.replace('__ID__', id);

        return this.carrera ? `${url}&carrera=${encodeURIComponent(this.carrera)}` : url;
    },
});
