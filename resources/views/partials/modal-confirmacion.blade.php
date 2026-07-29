<!-- MODAL DE CONFIRMACIÓN DE ACCIÓN REUTILIZABLE (COLOR ADMIN V2) -->
<div x-show="modalConfirmacionOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden text-center">
        <div class="bg-[#2d353c] text-white px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <span class="bg-amber-500 text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">Confirmación</span>
                <span class="font-bold text-xs" x-text="modalConfirmacionData.titulo || 'Confirmar Acción'"></span>
            </div>
            <button @click="modalConfirmacionOpen = false" class="text-gray-400 hover:text-white">&times;</button>
        </div>

        <div class="p-6 space-y-4">
            <div class="h-14 w-14 rounded-full bg-amber-100 text-amber-600 font-bold flex items-center justify-center mx-auto border-2 border-amber-300">
                <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                </svg>
            </div>
            <p class="text-xs text-gray-700 font-semibold leading-relaxed" x-text="modalConfirmacionData.mensaje"></p>
        </div>

        <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex justify-end gap-2">
            <button @click="modalConfirmacionOpen = false" 
                    class="px-4 py-2 bg-white border border-gray-300 text-gray-700 hover:bg-gray-50 rounded-lg text-xs font-bold shadow-xs transition-colors">
                Cancelar
            </button>
            <button @click="ejecutarConfirmacion()" 
                    :class="modalConfirmacionData.botonColor || 'bg-amber-600 hover:bg-amber-700'"
                    class="px-5 py-2 text-white font-bold rounded-lg text-xs shadow-md transition-colors"
                    x-text="modalConfirmacionData.botonTexto || 'Confirmar'">
            </button>
        </div>
    </div>
</div>
