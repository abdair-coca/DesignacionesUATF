window.personalDesignacionesLista = (config = {}) => ({
    ...window.designacionesListaFiltros(config),
    rutaDetalle: config.rutaDetalle ?? '',
    rutaPdf: config.rutaPdf ?? '',

    detalleUrl(id) {
        return this.rutaDetalle.replace('__ID__', id);
    },
    pdfUrl(id) {
        return this.rutaPdf.replace('__ID__', id);
    },
});
