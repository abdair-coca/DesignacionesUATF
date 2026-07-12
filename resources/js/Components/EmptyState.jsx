import { Icono } from './Icono';

export default function EmptyState({ icono = 'buscar', titulo, subtitulo, accion }) {
    return (
        <div className="flex flex-col items-center gap-3 px-4 py-12 text-center">
            <span className="flex h-12 w-12 items-center justify-center rounded-full bg-gray-50 text-gray-300 ring-1 ring-inset ring-gray-200/60">
                <Icono tipo={icono} className="h-5 w-5" />
            </span>
            <div>
                <p className="font-medium text-gray-700">{titulo}</p>
                {subtitulo && <p className="mt-0.5 text-sm text-gray-400">{subtitulo}</p>}
            </div>
            {accion && (
                <button onClick={accion.onClick} className="text-sm font-medium text-blue-700 hover:underline">
                    {accion.label}
                </button>
            )}
        </div>
    );
}
