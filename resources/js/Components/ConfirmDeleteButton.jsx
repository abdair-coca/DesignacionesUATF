import { router } from '@inertiajs/react';
import { Icono } from './Icono';

export default function ConfirmDeleteButton({ deleteUrl, mensaje = '¿Eliminar este registro?', titulo = 'Eliminar' }) {
    function eliminar() {
        if (confirm(mensaje)) {
            router.delete(deleteUrl);
        }
    }

    return (
        <button
            onClick={eliminar}
            title={titulo}
            className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-red-50 hover:text-red-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
        >
            <Icono tipo="papelera" className="h-[18px] w-[18px]" />
        </button>
    );
}
