export default function BarraProgreso({ cantidad, total, tono }) {
    const pct = total > 0 ? Math.round((cantidad / total) * 100) : 0
    const colores = {
        verde: ['text-green-700', 'bg-green-500'],
        naranja: ['text-amber-700', 'bg-amber-400'],
        rojo: ['text-red-700', 'bg-red-500']
    }
    const [texto, barra] = colores[tono]

    return (
        <div>
            <p className="text-sm tabular-nums">
                <span className={`font-semibold ${texto}`}>{cantidad}</span>{' '}
                <span className="text-xs text-gray-400">({pct}%)</span>
            </p>
            <div className="mt-1.5 h-1.5 w-24 overflow-hidden rounded-full bg-gray-100 ring-1 ring-inset ring-gray-200/50">
                <div
                    className={`h-full rounded-full transition-[width] duration-500 ease-out ${barra}`}
                    style={{ width: `${pct}%` }}
                />
            </div>
        </div>
    )
}
