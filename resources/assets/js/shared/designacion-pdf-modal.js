window.designacionPdfModal = (config = {}) => ({
    abierto: false,
    accion: '',
    filaId: '',
    rutaPlantilla: config.rutaPdf ?? '',

    abrir(id, filaId) {
        this.$refs.form.reset();
        this.accion = this.rutaPlantilla.replace('__ID__', encodeURIComponent(String(id)));
        this.filaId = String(filaId ?? '');
        this.abierto = true;
        this.$nextTick(() => this.$refs.condicion.focus());
    },

    cerrar() {
        this.abierto = false;
    },
});
