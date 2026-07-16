export default function GraficoAnillo({ resumen, etiqueta = 'carreras' }) {
    const radio = 40
    const circunferencia = 2 * Math.PI * radio
    const total = resumen.total || 1
    const segmentos = [
        { cantidad: resumen.activas, color: '#22c55e' },
        { cantidad: resumen.pendientes, color: '#f59e0b' },
        { cantidad: resumen.sin, color: '#ef4444' }
    ]
    const visibles = segmentos.filter((s) => s.cantidad > 0).length
    const separacion = visibles > 1 ? 1.8 : 0

    let acumulado = 0
    const arcos = segmentos.map((segmento) => {
        const inicio = acumulado
        acumulado += segmento.cantidad
        return { ...segmento, inicio }
    })

    return (
        <div className="relative">
            <svg viewBox="0 0 100 100" className="h-40 w-40">
                <circle cx="50" cy="50" r={radio} fill="none" stroke="#f3f4f6" strokeWidth="12" />
                {arcos.map(
                    (arco, i) =>
                        arco.cantidad > 0 && (
                            <circle
                                key={i}
                                cx="50"
                                cy="50"
                                r={radio}
                                fill="none"
                                stroke={arco.color}
                                strokeWidth="12"
                                strokeLinecap={visibles > 1 ? 'round' : 'butt'}
                                strokeDasharray={`${Math.max((arco.cantidad / total) * circunferencia - separacion, 0.6)} ${circunferencia}`}
                                strokeDashoffset={-(arco.inicio / total) * circunferencia - separacion / 2}
                                transform="rotate(-90 50 50)"
                                className="[transition:stroke-dasharray_.5s_ease,stroke-dashoffset_.5s_ease]"
                            />
                        )
                )}
            </svg>
            <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                <span className="text-2xl font-semibold tracking-tight text-gray-900 tabular-nums">{resumen.total}</span>
                <span className="text-[11px] font-medium uppercase tracking-wide text-gray-400">{etiqueta}</span>
            </div>
        </div>
    )
}
