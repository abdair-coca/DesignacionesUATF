import { Icono } from './Icono';
import Select from './Select';

export default function FilterBar({ busqueda, onBusquedaChange, placeholder, campos, onChange, onLimpiar, hayFiltrosActivos }) {
    return (
        <div className="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
            {typeof onBusquedaChange === 'function' && (
                <div className="relative min-w-52 flex-1">
                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <Icono tipo="buscar" className="h-4 w-4" />
                    </span>
                    <input
                        type="text"
                        placeholder={placeholder ?? 'Buscar...'}
                        value={busqueda ?? ''}
                        onChange={(e) => onBusquedaChange(e.target.value)}
                        className="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-9 text-sm shadow-sm transition-colors placeholder:text-gray-400 hover:border-gray-300 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                    />
                    {busqueda !== '' && (
                        <button
                            onClick={() => onBusquedaChange('')}
                            className="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-full p-0.5 text-gray-300 transition-colors hover:bg-gray-100 hover:text-gray-500"
                            title="Limpiar búsqueda"
                        >
                            <Icono tipo="cerrar" className="h-3.5 w-3.5" />
                        </button>
                    )}
                </div>
            )}

            {campos.map((campo) => (
                <div key={campo.key} className="w-48">
                    <label className="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">
                        {campo.label}
                    </label>
                    <Select value={campo.valor} onChange={(e) => onChange({ [campo.key]: e.target.value })}>
                        <option value="">{campo.todos ?? 'Todos'}</option>
                        {campo.opciones.map((opcion) => (
                            <option key={opcion.value} value={opcion.value}>
                                {opcion.label}
                            </option>
                        ))}
                    </Select>
                </div>
            ))}

            <button
                onClick={onLimpiar}
                disabled={!hayFiltrosActivos}
                className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                title="Limpiar filtros"
            >
                <Icono tipo="embudo" className="h-4 w-4" />
                Filtros
            </button>
        </div>
    );
}
