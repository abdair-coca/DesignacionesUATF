import { useEffect, useMemo, useRef, useState } from 'react';
import MenuFlotante from './MenuFlotante';
import { Icono } from './Icono';
import paletaIcono from './paletaIcono';

const VACIO = { id: '', nombre: '— Sin asignar —', esVacio: true };

export default function ComboboxDocente({ docentes, value, onChange, carreraSigla, placeholder = '— Sin asignar —', className = 'max-w-56', materiaId = null, carreraId = null }) {
    const [abierto, setAbierto] = useState(false);
    const [anchorEl, setAnchorEl] = useState(null);
    const [busqueda, setBusqueda] = useState('');
    const [resaltado, setResaltado] = useState(0);
    const inputRef = useRef(null);

    const seleccionado = docentes.find((d) => String(d.id) === String(value)) ?? null;

    const opciones = useMemo(() => {
        const termino = busqueda.trim().toLowerCase();
        const coincidentes = termino === '' 
            ? docentes 
            : docentes.filter((d) => d.nombre.toLowerCase().includes(termino));

        const historial = [];
        const mismaCarrera = [];
        const resto = [];

        for (const d of coincidentes) {
            const tieneHistorial = materiaId && (d.historial_materias || []).some(id => String(id) === String(materiaId));
            const esMismaCarrera = (carreraId && String(d.carrera_origen_id) === String(carreraId)) || 
                                   (carreraSigla && d.carreraSigla === carreraSigla);

            if (tieneHistorial) {
                historial.push(d);
            } else if (esMismaCarrera) {
                mismaCarrera.push(d);
            } else {
                resto.push(d);
            }
        }

        const sortByName = (a, b) => a.nombre.localeCompare(b.nombre);
        historial.sort(sortByName);
        mismaCarrera.sort(sortByName);
        resto.sort(sortByName);

        const lista = [{ ...VACIO, esVacio: true }];

        if (historial.length > 0) {
            lista.push({ esCabecera: true, titulo: 'Dictó esta materia antes' });
            lista.push(...historial);
        }
        if (mismaCarrera.length > 0) {
            lista.push({ esCabecera: true, titulo: 'Docentes de la carrera' });
            lista.push(...mismaCarrera);
        }
        if (resto.length > 0) {
            const tituloResto = (historial.length > 0 || mismaCarrera.length > 0) ? 'Otros docentes' : 'Docentes';
            lista.push({ esCabecera: true, titulo: tituloResto });
            lista.push(...resto);
        }

        return lista;
    }, [docentes, busqueda, materiaId, carreraId, carreraSigla]);

    function abrir(el) {
        setAnchorEl(el);
        setAbierto(true);
        setBusqueda('');
        setResaltado(opciones.findIndex((o) => String(o.id) === String(value)) || 0);
    }

    function cerrar() {
        setAbierto(false);
        setAnchorEl(null);
    }

    function elegir(opcion) {
        onChange(opcion.esVacio ? '' : String(opcion.id));
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
            let proximo = resaltado + 1;
            while (proximo < opciones.length && opciones[proximo].esCabecera) {
                proximo++;
            }
            if (proximo < opciones.length) setResaltado(proximo);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            let anterior = resaltado - 1;
            while (anterior >= 0 && opciones[anterior].esCabecera) {
                anterior--;
            }
            if (anterior >= 0) setResaltado(anterior);
        } else if (e.key === 'Enter') {
            e.preventDefault();
            const seleccionada = opciones[resaltado];
            if (seleccionada && !seleccionada.esCabecera) elegir(seleccionada);
        }
    }

    return (
        <div className={`relative ${className}`}>
            <button
                type="button"
                onClick={(e) => (abierto ? cerrar() : abrir(e.currentTarget))}
                className="flex w-full items-center gap-2 rounded-lg border border-gray-200 bg-white py-1.5 pl-1.5 pr-2.5 text-left text-sm shadow-sm transition-colors hover:border-gray-300 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
            >
                {seleccionado ? (
                    <>
                        <span
                            className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold ring-1 ring-inset ${paletaIcono[seleccionado.id % paletaIcono.length]}`}
                        >
                            {seleccionado.nombre.charAt(0)}
                        </span>
                        <span className="min-w-0 flex-1 truncate text-gray-900">{seleccionado.nombre}</span>
                    </>
                ) : (
                    <span className="flex-1 truncate pl-1 text-gray-400">{placeholder}</span>
                )}
                <Icono tipo="chevronAbajo" className="h-3.5 w-3.5 shrink-0 text-gray-400" />
            </button>

            {abierto && (
                <MenuFlotante anchorEl={anchorEl} onClose={cerrar} width={272} align="left">
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
                            placeholder="Buscar docente..."
                            className="w-full rounded-md border border-gray-200 py-1.5 pl-8 pr-2 text-sm shadow-sm transition-colors placeholder:text-gray-400 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                        />
                    </div>

                    <div className="max-h-64 overflow-y-auto p-1">
                        {opciones.length === 1 && (
                            <p className="px-3 py-6 text-center text-xs text-gray-400">Ningún docente coincide.</p>
                        )}
                        {opciones.map((opcion, indice) => {
                            if (opcion.esCabecera) {
                                return (
                                    <div
                                        key={`header-${opcion.titulo}`}
                                        className="px-2.5 py-1.5 text-[10px] font-bold uppercase tracking-wider text-gray-400 bg-gray-50/50 sticky top-0 backdrop-blur-sm first:mt-0 mt-2 rounded-md"
                                    >
                                        {opcion.titulo}
                                    </div>
                                );
                            }
                            const activo = String(opcion.id) === String(value);
                            return (
                                <button
                                    key={opcion.id || 'vacio'}
                                    type="button"
                                    onMouseEnter={() => setResaltado(indice)}
                                    onClick={() => elegir(opcion)}
                                    className={`flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left text-sm transition-colors ${
                                        indice === resaltado ? 'bg-blue-50' : ''
                                    }`}
                                >
                                    {opcion.esVacio ? (
                                        <span className="flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-gray-50 text-gray-400 ring-1 ring-inset ring-gray-200">
                                            <Icono tipo="vacio" className="h-3.5 w-3.5" />
                                        </span>
                                    ) : (
                                        <span
                                            className={`flex h-6 w-6 shrink-0 items-center justify-center rounded-full text-[11px] font-semibold ring-1 ring-inset ${paletaIcono[opcion.id % paletaIcono.length]}`}
                                        >
                                            {opcion.nombre.charAt(0)}
                                        </span>
                                    )}
                                    <span className={`min-w-0 flex-1 truncate ${opcion.esVacio ? 'text-gray-500' : 'text-gray-900'}`}>
                                        {opcion.nombre}
                                    </span>
                                    {!opcion.esVacio && opcion.carreraSigla && (
                                        <span
                                            className={`shrink-0 text-[10px] font-semibold uppercase tracking-wide ${
                                                opcion.carreraSigla === carreraSigla ? 'text-blue-600' : 'text-gray-300'
                                            }`}
                                        >
                                            {opcion.carreraSigla}
                                        </span>
                                    )}
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
