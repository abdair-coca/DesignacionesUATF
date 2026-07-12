import { Icono } from './Icono';
import Select from './Select';

export default function FilterBar({ campos, onChange, onLimpiar, hayFiltrosActivos }) {
    return (
        <div className="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
            {campos.map((campo) => (
                <div key={campo.key} className="w-48">
                    <label className="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">
                        {campo.label}
                    </label>
                    <Select value={campo.valor} onChange={(e) => onChange(campo.key, e.target.value)}>
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
