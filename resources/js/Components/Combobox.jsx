import { useEffect, useMemo, useRef, useState } from 'react';
import MenuFlotante from './MenuFlotante';
import { Icono } from './Icono';

export default function Combobox({
    options,
    value,
    onChange,
    placeholder = 'Seleccione...',
    className = 'w-full',
    searchPlaceholder = 'Buscar...',
    emptyText = 'No hay coincidencias.'
}) {
    const [abierto, setAbierto] = useState(false);
    const [anchorEl, setAnchorEl] = useState(null);
    const [busqueda, setBusqueda] = useState('');
    const [resaltado, setResaltado] = useState(0);
    const inputRef = useRef(null);

    const seleccionado = options.find((o) => String(o.value) === String(value)) ?? null;

    const filtrados = useMemo(() => {
        const termino = busqueda.trim().toLowerCase();
        if (termino === '') return options;
        return options.filter((o) => 
            o.label.toLowerCase().includes(termino) || 
            (o.sublabel && o.sublabel.toLowerCase().includes(termino))
        );
    }, [options, busqueda]);

    const opciones = [{ value: '', label: placeholder, esVacio: true }, ...filtrados];

    function abrir(el) {
        setAnchorEl(el);
        setAbierto(true);
        setBusqueda('');
        setResaltado(opciones.findIndex((o) => String(o.value) === String(value)) || 0);
    }

    function cerrar() {
        setAbierto(false);
        setAnchorEl(null);
    }

    function elegir(opcion) {
        onChange(opcion.esVacio ? '' : String(opcion.value));
        cerrar();
    }

    useEffect(() => {
        if (abierto) inputRef.current?.focus();
    }, [abierto]);

    useEffect(() => {
        setResaltado(0);
    }, [busqueda]);

    function alTeclear(e) {
        if (e.key === 'Escape') {
            e.preventDefault();
            cerrar();
        } else if (e.key === 'ArrowDown') {
            e.preventDefault();
            setResaltado((r) => Math.min(r + 1, opciones.length - 1));
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            setResaltado((r) => Math.max(r - 1, 0));
        } else if (e.key === 'Enter') {
            e.preventDefault();
            if (opciones[resaltado]) elegir(opciones[resaltado]);
        }
    }

    return (
        <div className={`relative ${className}`}>
            <button
                type="button"
                onClick={(e) => (abierto ? cerrar() : abrir(e.currentTarget))}
                className="flex w-full items-center justify-between gap-2 rounded-lg border border-gray-200 bg-white py-2 pl-3 pr-2.5 text-left text-sm shadow-sm transition-colors hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            >
                {seleccionado ? (
                    <span className="min-w-0 flex-1 truncate text-gray-900">
                        {seleccionado.sublabel ? `${seleccionado.sublabel} — ` : ''}{seleccionado.label}
                    </span>
                ) : (
                    <span className="flex-1 truncate text-gray-400">{placeholder}</span>
                )}
                <Icono tipo="chevronAbajo" className="h-4 w-4 shrink-0 text-gray-400" />
            </button>

            {abierto && (
                <MenuFlotante anchorEl={anchorEl} onClose={cerrar} width={anchorEl ? anchorEl.offsetWidth : 320} align="left">
                    <div className="relative border-b border-gray-100 p-2">
                        <span className="pointer-events-none absolute left-4.5 top-1/2 -translate-y-1/2 text-gray-400">
                            <Icono tipo="buscar" className="h-3.5 w-3.5" />
                        </span>
                        <input
                            ref={inputRef}
                            type="text"
                            value={busqueda}
                            onChange={(e) => setBusqueda(e.target.value)}
                            onKeyDown={alTeclear}
                            placeholder={searchPlaceholder}
                            className="w-full rounded-md border border-gray-200 py-1.5 pl-8 pr-2 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        />
                    </div>

                    <div className="max-h-64 overflow-y-auto p-1">
                        {opciones.length === 1 && (
                            <p className="px-3 py-6 text-center text-xs text-gray-400">{emptyText}</p>
                        )}
                        {opciones.map((opcion, indice) => {
                            const activo = String(opcion.value) === String(value);
                            return (
                                <button
                                    key={opcion.value || 'vacio'}
                                    type="button"
                                    onMouseEnter={() => setResaltado(indice)}
                                    onClick={() => elegir(opcion)}
                                    className={`flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm transition-colors ${
                                        indice === resaltado ? 'bg-blue-50' : ''
                                    }`}
                                >
                                    <span className={`min-w-0 flex-1 truncate ${opcion.esVacio ? 'text-gray-500 font-medium' : 'text-gray-900'}`}>
                                        {opcion.esVacio ? opcion.label : (
                                            <>
                                                {opcion.sublabel && <span className="font-semibold text-gray-500 mr-1.5">{opcion.sublabel} —</span>}
                                                {opcion.label}
                                            </>
                                        )}
                                    </span>
                                    {activo && (
                                        <span className="shrink-0 text-blue-700">
                                            <Icono tipo="check" className="h-3.5 w-3.5" />
                                        </span>
                                    )}
                                </button>
                            );
                        })}
                    </div>
                </MenuFlotante>
            )}
        </div>
    );
}
