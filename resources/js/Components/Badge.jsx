import { Icono } from './Icono';

const TONOS = {
    verde: 'border-green-200 text-green-700',
    ambar: 'border-amber-200 text-amber-700',
    rojo: 'border-red-200 text-red-600',
    azul: 'border-blue-200 text-blue-700',
    gris: 'border-gray-300 text-gray-500',
};

export default function Badge({ tono, icono, children }) {
    return (
        <span className={`inline-flex items-center gap-1 rounded-full border bg-white px-2.5 py-1 text-xs font-medium ${TONOS[tono]}`}>
            <Icono tipo={icono} className="h-3.5 w-3.5" />
            {children}
        </span>
    );
}
