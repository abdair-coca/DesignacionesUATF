import { Icono } from './Icono';
import Badge from './Badge';

/**
 * Componente reutilizable para visualizar validaciones y restricciones académicas.
 * Regla de negocio UATF: Cada docente debe tener un MÍNIMO de 6 horas semanales (sin tope máximo).
 */
export default function PanelRestricciones({
    resumenCarga,
    restriccionesAdicionales = [],
    className = '',
}) {
    if (!resumenCarga) return null;

    const {
        horasActuales = 0,
        horasProyectadas = 0,
        minimo = 6,
        hayChoque = false,
    } = resumenCarga;

    const tieneDocente = horasActuales !== null && horasActuales !== undefined;
    const cumpleMinimo = tieneDocente && horasProyectadas >= minimo;
    const porcentaje = tieneDocente && minimo > 0 ? Math.min(Math.round((horasProyectadas / minimo) * 100), 100) : 0;

    // Modulo 1: Carga horaria mínima (6 horas semanales)
    const estadoCarga = tieneDocente && horasProyectadas > 0
        ? cumpleMinimo
            ? { tono: 'verde', icono: 'check', etiqueta: 'Mínimo cumplido (6h+)', msj: `Carga proyectada de ${horasProyectadas}h cumple con el mínimo exigido de ${minimo}h.` }
            : { tono: 'ambar', icono: 'alerta', etiqueta: 'Carga insuficiente', msj: `Carga proyectada de ${horasProyectadas}h es menor al mínimo semanal de ${minimo}h.` }
        : { tono: 'gris', icono: 'vacio', etiqueta: 'Sin proyección', msj: 'Selecciona docente y materia para verificar carga' };

    // Modulo 2: Choque de horarios (Regla activa actual)
    const estadoChoque = hayChoque
        ? { tono: 'ambar', icono: 'reloj', etiqueta: 'Conflicto', msj: 'El docente ya dicta otro grupo en el mismo periodo' }
        : { tono: 'verde', icono: 'check', etiqueta: 'Sin choques', msj: 'Horario disponible en el periodo seleccionado' };

    return (
        <div className={`rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm space-y-4 ${className}`}>
            <div className="flex items-center justify-between border-b border-gray-100 pb-3">
                <div className="flex items-center gap-2">
                    <span className="flex h-7 w-7 items-center justify-center rounded-lg bg-blue-50 text-blue-900 ring-1 ring-inset ring-blue-200/60">
                        <Icono tipo="alerta" className="h-4 w-4" />
                    </span>
                    <h4 className="text-sm font-semibold tracking-tight text-gray-900">
                        Validación de Restricciones
                    </h4>
                </div>
                <span className="text-[11px] font-semibold uppercase tracking-wider text-gray-400">
                    Reglas UATF
                </span>
            </div>

            {/* Restricción 1: Carga Horaria Mínima de 6h */}
            <div className="space-y-2">
                <div className="flex items-center justify-between text-xs">
                    <span className="font-medium text-gray-700">Mínimo de carga académica</span>
                    <Badge tono={estadoCarga.tono} icono={estadoCarga.icono}>
                        {estadoCarga.etiqueta}
                    </Badge>
                </div>

                {tieneDocente && (
                    <div className="space-y-1">
                        <div className="h-2 w-full overflow-hidden rounded-full bg-gray-100 ring-1 ring-inset ring-gray-200/50">
                            <div
                                className={`h-full transition-[width] duration-500 ease-out ${
                                    cumpleMinimo ? 'bg-green-600' : 'bg-amber-500'
                                }`}
                                style={{ width: `${porcentaje}%` }}
                            />
                        </div>
                        <div className="flex justify-between text-[11px] text-gray-400 tabular-nums">
                            <span>Actuales: {horasActuales}h</span>
                            <span className="font-medium text-gray-600">Proyectadas: {horasProyectadas}h (Mínimo: {minimo}h)</span>
                        </div>
                    </div>
                )}

                <p className="text-xs text-gray-500">{estadoCarga.msj}</p>
            </div>

            {/* Restricción 2: Cruce de Grupos */}
            {hayChoque && (
                <div className="rounded-lg border border-amber-200 bg-amber-50/50 p-3 space-y-1">
                    <div className="flex items-center justify-between text-xs">
                        <span className="font-semibold text-amber-900">Cruce detectado</span>
                        <Badge tono={estadoChoque.tono} icono={estadoChoque.icono}>
                            {estadoChoque.etiqueta}
                        </Badge>
                    </div>
                    <p className="text-xs text-amber-800">{estadoChoque.msj}</p>
                </div>
            )}

            {/* Extensibilidad: Nuevas restricciones que se agreguen en el futuro */}
            {restriccionesAdicionales.map((res, i) => (
                <div key={i} className="border-t border-gray-100 pt-3 space-y-1">
                    <div className="flex items-center justify-between text-xs">
                        <span className="font-medium text-gray-700">{res.titulo}</span>
                        <Badge tono={res.tono} icono={res.icono}>
                            {res.etiqueta}
                        </Badge>
                    </div>
                    {res.descripcion && <p className="text-xs text-gray-500">{res.descripcion}</p>}
                </div>
            ))}
        </div>
    );
}
