import { Icono } from './Icono';
import Badge from './Badge';

/**
 * Componente reutilizable para visualizar validaciones y restricciones académicas.
 * Diseñado de forma extensible para incorporar nuevas reglas del negocio en el futuro
 * (ej. dedicación, traslape de horario, categoría docente, etc.).
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
        limite = 6,
        excedeLimite = false,
        hayChoque = false,
    } = resumenCarga;

    const tieneDocente = horasActuales !== null && horasActuales !== undefined;
    const porcentaje = tieneDocente && limite > 0 ? Math.min(Math.round((horasProyectadas / limite) * 100), 100) : 0;

    // Modulo 1: Carga horaria (Regla activa actual)
    const estadoCarga = excedeLimite
        ? { tono: 'rojo', icono: 'equis', etiqueta: 'Excede límite', msj: `Proyección de ${horasProyectadas}h supera el máximo de ${limite}h` }
        : tieneDocente && horasProyectadas > 0
            ? { tono: 'verde', icono: 'check', etiqueta: 'Carga permitida', msj: `${horasProyectadas}h asignadas de ${limite}h permitidas` }
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

            {/* Restricción 1: Carga Horaria de Docente */}
            <div className="space-y-2">
                <div className="flex items-center justify-between text-xs">
                    <span className="font-medium text-gray-700">Carga académica acumulada</span>
                    <Badge tono={estadoCarga.tono} icono={estadoCarga.icono}>
                        {estadoCarga.etiqueta}
                    </Badge>
                </div>

                {tieneDocente && (
                    <div className="space-y-1">
                        <div className="h-2 w-full overflow-hidden rounded-full bg-gray-100 ring-1 ring-inset ring-gray-200/50">
                            <div
                                className={`h-full transition-[width] duration-500 ease-out ${
                                    excedeLimite ? 'bg-red-500' : 'bg-blue-900'
                                }`}
                                style={{ width: `${porcentaje}%` }}
                            />
                        </div>
                        <div className="flex justify-between text-[11px] text-gray-400 tabular-nums">
                            <span>Actuales: {horasActuales}h</span>
                            <span className="font-medium text-gray-600">Proyectadas: {horasProyectadas}h / {limite}h</span>
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
