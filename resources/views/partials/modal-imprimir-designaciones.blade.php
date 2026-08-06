<div x-show="modalImprimirOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="modal-imprimir-designaciones-title">
    <div @click.away="modalImprimirOpen = false" class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden">
        <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between">
            <h2 id="modal-imprimir-designaciones-title" class="font-bold text-sm">Imprimir</h2>
            <button type="button" @click="modalImprimirOpen = false" class="text-gray-300 hover:text-white" aria-label="Cerrar">&times;</button>
        </div>
        <div class="p-5 text-sm text-gray-700">
            <p class="font-semibold text-gray-900" x-text="modalImprimirTitulo || 'Reporte de designaciones'"></p>
            <p class="mt-2">La generación del reporte para impresión estará disponible próximamente.</p>
        </div>
        <div class="bg-gray-50 border-t border-gray-200 px-5 py-3 flex justify-end">
            <button type="button" @click="modalImprimirOpen = false" class="px-4 py-1.5 bg-gray-700 hover:bg-gray-800 text-white rounded text-xs font-bold">Cerrar</button>
        </div>
    </div>
</div>
