import { Icono } from './Icono';

const tonos = {
    azul: 'bg-blue-50 text-blue-600 ring-blue-200/50',
    verde: 'bg-green-50 text-green-600 ring-green-200/50',
    ambar: 'bg-amber-50 text-amber-600 ring-amber-200/50',
    rojo: 'bg-red-50 text-red-600 ring-red-200/50',
};

export default function StatTile({ tipo, tono, titulo, valor, subtitulo }) {
    return (
        <div className="group rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md">
            <div className="flex items-center gap-2.5">
                <span
                    className={`flex h-9 w-9 items-center justify-center rounded-lg ring-1 ring-inset transition-transform duration-200 group-hover:scale-105 ${tonos[tono]}`}
                >
                    <Icono tipo={tipo} className="h-[18px] w-[18px]" />
                </span>
                <span className="text-[13px] font-medium text-gray-500">{titulo}</span>
            </div>
            <p className="mt-3 text-[26px] font-semibold leading-8 tracking-tight text-gray-900 tabular-nums">{valor}</p>
            {subtitulo && <p className="mt-0.5 text-xs text-gray-400 tabular-nums">{subtitulo}</p>}
        </div>
    );
}
