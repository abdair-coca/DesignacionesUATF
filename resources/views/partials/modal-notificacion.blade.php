<!-- MODAL DE NOTIFICACIÓN REUTILIZABLE (COLOR ADMIN V2) -->
<div x-show="modalNotificacionOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;">
    <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden text-center">
        <div class="bg-[#2d353c] text-white px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <template x-if="modalNotificacionData.tipo === 'exito'">
                    <span class="bg-[#00acac] text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">Éxito</span>
                </template>
                <template x-if="modalNotificacionData.tipo === 'error'">
                    <span class="bg-rose-600 text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">Error</span>
                </template>
                <template x-if="modalNotificacionData.tipo === 'info'">
                    <span class="bg-[#348fe2] text-white text-[9px] font-bold px-1.5 py-0.5 rounded uppercase">Aviso</span>
                </template>
                <span class="font-bold text-xs" x-text="modalNotificacionData.titulo || 'Notificación del Sistema'"></span>
            </div>
            <button @click="cerrarNotificacion()" class="text-gray-400 hover:text-white">&times;</button>
        </div>

        <div class="p-6 space-y-4">
            <template x-if="modalNotificacionData.tipo === 'exito'">
                <div class="h-14 w-14 rounded-full bg-emerald-100 text-emerald-600 font-bold flex items-center justify-center mx-auto border-2 border-emerald-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7" />
                    </svg>
                </div>
            </template>

            <template x-if="modalNotificacionData.tipo === 'error'">
                <div class="h-14 w-14 rounded-full bg-rose-100 text-rose-600 font-bold flex items-center justify-center mx-auto border-2 border-rose-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" />
                    </svg>
                </div>
            </template>

            <template x-if="modalNotificacionData.tipo === 'info'">
                <div class="h-14 w-14 rounded-full bg-blue-100 text-blue-600 font-bold flex items-center justify-center mx-auto border-2 border-blue-300">
                    <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z" />
                    </svg>
                </div>
            </template>

            <p class="text-xs text-gray-700 font-semibold leading-relaxed" x-text="modalNotificacionData.mensaje"></p>
        </div>

        <div class="bg-gray-100 border-t border-gray-200 px-5 py-3 flex justify-center">
            <button @click="cerrarNotificacion()" 
                    class="px-6 py-2 bg-[#2d353c] hover:bg-gray-800 text-white font-bold rounded-lg text-xs shadow-md transition-colors">
                Aceptar
            </button>
        </div>
    </div>
</div>
