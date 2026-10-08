<div
    x-show="modalConfirmacionOpen"
    x-cloak
    x-transition.opacity
    class="designacion-confirmacion-modal"
    role="dialog"
    aria-modal="true"
    aria-labelledby="modal-confirmacion-title"
    @keydown.escape.window="modalConfirmacionOpen = false"
>
    <div class="confirmacion-dialog" @click.outside="modalConfirmacionOpen = false">
        <div class="confirmacion-header">
            <div>
                <span class="confirmacion-kicker">Confirmación</span>
                <h2 id="modal-confirmacion-title" class="confirmacion-title" x-text="modalConfirmacionData.titulo || 'Confirmar acción'"></h2>
            </div>
            <button type="button" @click="modalConfirmacionOpen = false" class="confirmacion-close" aria-label="Cerrar confirmación">&times;</button>
        </div>

        <div class="confirmacion-body">
            <span class="confirmacion-icon" aria-hidden="true">
                <svg fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </span>
            <p class="confirmacion-message" x-text="modalConfirmacionData.mensaje"></p>
        </div>

        <div class="confirmacion-footer">
            <button type="button" @click="modalConfirmacionOpen = false" class="confirmacion-button confirmacion-cancel">Cancelar</button>
            <button type="button" @click="ejecutarConfirmacion()" class="confirmacion-button confirmacion-submit" x-text="modalConfirmacionData.botonTexto || 'Confirmar'"></button>
        </div>
    </div>
</div>
