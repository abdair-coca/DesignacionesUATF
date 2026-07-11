import { useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

const selectClass =
    'w-full rounded-md border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500';

const paletaIcono = [
    'bg-blue-100 text-blue-700',
    'bg-emerald-100 text-emerald-700',
    'bg-violet-100 text-violet-700',
    'bg-rose-100 text-rose-700',
    'bg-amber-100 text-amber-700',
    'bg-cyan-100 text-cyan-700',
];

function Icono({ tipo, className = 'h-5 w-5' }) {
    const trazos = {
        total: (
            <>
                <rect x="4" y="4" width="7" height="7" rx="1.5" />
                <rect x="13" y="4" width="7" height="7" rx="1.5" />
                <rect x="4" y="13" width="7" height="7" rx="1.5" />
                <rect x="13" y="13" width="7" height="7" rx="1.5" />
            </>
        ),
        check: (
            <>
                <circle cx="12" cy="12" r="9" />
                <path d="M9 12.5l2 2 4-5" />
            </>
        ),
        reloj: (
            <>
                <circle cx="12" cy="12" r="9" />
                <path d="M12 7.5V12l3 2" />
            </>
        ),
        equis: (
            <>
                <circle cx="12" cy="12" r="9" />
                <path d="M9.5 9.5l5 5m0-5l-5 5" />
            </>
        ),
        buscar: (
            <>
                <circle cx="11" cy="11" r="6" />
                <path d="M20 20l-4-4" />
            </>
        ),
        embudo: <path d="M4 5h16l-6.2 7v5.5L10.2 19v-7z" />,
        ojo: (
            <>
                <path d="M2.5 12S6 5.5 12 5.5 21.5 12 21.5 12 18 18.5 12 18.5 2.5 12 2.5 12z" />
                <circle cx="12" cy="12" r="3" />
            </>
        ),
        mas: <path d="M12 5v14m-7-7h14" />,
        lote: (
            <>
                <rect x="4" y="4" width="16" height="6" rx="1.5" />
                <rect x="4" y="14" width="16" height="6" rx="1.5" />
            </>
        ),
        copiar: (
            <>
                <rect x="8" y="8" width="12" height="12" rx="2" />
                <path d="M16 8V6a2 2 0 00-2-2H6a2 2 0 00-2 2v8a2 2 0 002 2h2" />
            </>
        ),
        documento: (
            <>
                <path d="M7 3.5h7L18.5 8v12a1.5 1.5 0 01-1.5 1.5H7A1.5 1.5 0 015.5 20V5A1.5 1.5 0 017 3.5z" />
                <path d="M14 3.5V8h4.5" />
            </>
        ),
    };

    return (
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8" strokeLinecap="round" strokeLinejoin="round" className={className}>
            {trazos[tipo]}
        </svg>
    );
}

function IconoPuntos({ className = 'h-5 w-5' }) {
    return (
        <svg viewBox="0 0 24 24" fill="currentColor" className={className}>
            <circle cx="12" cy="5.5" r="1.7" />
            <circle cx="12" cy="12" r="1.7" />
            <circle cx="12" cy="18.5" r="1.7" />
        </svg>
    );
}

function textoPorcentaje(cantidad, total) {
    if (!total) return '0% del total';
    const pct = (cantidad / total) * 100;
    return `${Number.isInteger(pct) ? pct : pct.toFixed(1)}% del total`;
}

function TarjetaResumen({ tipo, colorIcono, titulo, valor, subtitulo }) {
    return (
        <div className="rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
            <div className="flex items-center gap-2">
                <span className={`flex h-8 w-8 items-center justify-center rounded-lg ${colorIcono}`}>
                    <Icono tipo={tipo} className="h-4.5 w-4.5" />
                </span>
                <span className="text-sm text-gray-500">{titulo}</span>
            </div>
            <p className="mt-2 text-2xl font-bold text-gray-900">{valor}</p>
            <p className="mt-0.5 text-xs text-gray-400">{subtitulo}</p>
        </div>
    );
}

function BarraProgreso({ cantidad, total, tono }) {
    const pct = total > 0 ? Math.round((cantidad / total) * 100) : 0;
    const colores = {
        verde: ['text-green-600', 'bg-green-500'],
        naranja: ['text-amber-600', 'bg-amber-400'],
        rojo: ['text-red-600', 'bg-red-500'],
    };
    const [texto, barra] = colores[tono];

    return (
        <div>
            <span className={`text-sm font-medium ${texto}`}>
                {cantidad} ({pct}%)
            </span>
            <div className="mt-1 h-1.5 w-24 rounded-full bg-gray-100">
                <div className={`h-1.5 rounded-full ${barra}`} style={{ width: `${pct}%` }} />
            </div>
        </div>
    );
}

function Dona({ resumen }) {
    const radio = 40;
    const circunferencia = 2 * Math.PI * radio;
    const total = resumen.total || 1;
    const segmentos = [
        { cantidad: resumen.activas, color: '#22c55e' },
        { cantidad: resumen.pendientes, color: '#f59e0b' },
        { cantidad: resumen.sin, color: '#ef4444' },
    ];

    let acumulado = 0;
    const arcos = segmentos.map((segmento) => {
        const inicio = acumulado;
        acumulado += segmento.cantidad;
        return { ...segmento, inicio };
    });

    return (
        <svg viewBox="0 0 100 100" className="h-36 w-36">
            <circle cx="50" cy="50" r={radio} fill="none" stroke="#e5e7eb" strokeWidth="13" />
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
                            strokeWidth="13"
                            strokeDasharray={`${(arco.cantidad / total) * circunferencia} ${circunferencia}`}
                            strokeDashoffset={-(arco.inicio / total) * circunferencia}
                            transform="rotate(-90 50 50)"
                        />
                    )
            )}
        </svg>
    );
}

export default function PorCarrera({ carreras, resumen, gestiones, periodos, filtros }) {
    const [busqueda, setBusqueda] = useState(filtros.q ?? '');
    const [menuAbierto, setMenuAbierto] = useState(null);
    const temporizador = useRef(null);

    function aplicarFiltros(cambios) {
        router.get(
            route('designaciones.index'),
            {
                q: busqueda,
                gestion_id: filtros.gestion_id,
                periodo_id: filtros.periodo_id,
                estado: filtros.estado,
                ...cambios,
            },
            { preserveState: true, replace: true }
        );
    }

    function cambiarBusqueda(valor) {
        setBusqueda(valor);
        clearTimeout(temporizador.current);
        temporizador.current = setTimeout(() => aplicarFiltros({ q: valor }), 350);
    }

    function limpiarFiltros() {
        setBusqueda('');
        router.get(route('designaciones.index'), {}, { preserveState: true, replace: true });
    }

    const leyendaDona = [
        { etiqueta: 'Activas', cantidad: resumen.activas, punto: 'bg-green-500' },
        { etiqueta: 'Pendientes', cantidad: resumen.pendientes, punto: 'bg-amber-400' },
        { etiqueta: 'Sin designaciones', cantidad: resumen.sin, punto: 'bg-red-500' },
    ];

    const accionesRapidas = [
        { etiqueta: 'Nueva designación', tipo: 'mas', href: route('designaciones.create') },
        { etiqueta: 'Asignar por carrera', tipo: 'lote', href: route('designaciones.asignar') },
        { etiqueta: 'Copiar designaciones', tipo: 'copiar', href: route('designaciones.copiar') },
    ];

    return (
        <AppLayout>
            <div className="flex flex-col gap-6 xl:flex-row">
                <div className="min-w-0 flex-1">
                    <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold text-gray-900">Designaciones por carrera</h1>
                            <p className="mt-1 text-sm text-gray-500">
                                Administra y consulta todas las designaciones académicas organizadas por carrera.
                            </p>
                        </div>
                        <Link
                            href={route('designaciones.create')}
                            className="inline-flex items-center gap-2 rounded-md bg-blue-900 px-4 py-2 text-sm font-medium text-white hover:bg-blue-800"
                        >
                            <Icono tipo="mas" className="h-4 w-4" />
                            Nueva designación
                        </Link>
                    </div>

                    <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <TarjetaResumen
                            tipo="total"
                            colorIcono="bg-blue-100 text-blue-600"
                            titulo="Total carreras"
                            valor={resumen.total}
                            subtitulo="100% del total"
                        />
                        <TarjetaResumen
                            tipo="check"
                            colorIcono="bg-green-100 text-green-600"
                            titulo="Con designaciones activas"
                            valor={resumen.activas}
                            subtitulo={textoPorcentaje(resumen.activas, resumen.total)}
                        />
                        <TarjetaResumen
                            tipo="reloj"
                            colorIcono="bg-amber-100 text-amber-600"
                            titulo="Con pendientes"
                            valor={resumen.pendientes}
                            subtitulo={textoPorcentaje(resumen.pendientes, resumen.total)}
                        />
                        <TarjetaResumen
                            tipo="equis"
                            colorIcono="bg-red-100 text-red-600"
                            titulo="Sin designaciones"
                            valor={resumen.sin}
                            subtitulo={textoPorcentaje(resumen.sin, resumen.total)}
                        />
                    </div>

                    <div className="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200 bg-white p-4 shadow-sm">
                        <div className="relative min-w-52 flex-1">
                            <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <Icono tipo="buscar" className="h-4 w-4" />
                            </span>
                            <input
                                type="text"
                                placeholder="Buscar carrera..."
                                value={busqueda}
                                onChange={(e) => cambiarBusqueda(e.target.value)}
                                className="w-full rounded-md border border-gray-300 py-2 pl-9 pr-3 text-sm shadow-sm focus:border-blue-500 focus:outline-none focus:ring-1 focus:ring-blue-500"
                            />
                        </div>

                        <div className="w-32">
                            <label className="mb-1 block text-xs text-gray-500">Gestión</label>
                            <select
                                className={selectClass}
                                value={filtros.gestion_id}
                                onChange={(e) => aplicarFiltros({ gestion_id: e.target.value })}
                            >
                                {gestiones.map((gestion) => (
                                    <option key={gestion.id} value={gestion.id}>
                                        {gestion.nombre}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="w-32">
                            <label className="mb-1 block text-xs text-gray-500">Periodo</label>
                            <select
                                className={selectClass}
                                value={filtros.periodo_id}
                                onChange={(e) => aplicarFiltros({ periodo_id: e.target.value })}
                            >
                                {periodos.map((periodo) => (
                                    <option key={periodo.id} value={periodo.id}>
                                        {periodo.nombre}
                                    </option>
                                ))}
                            </select>
                        </div>

                        <div className="w-44">
                            <label className="mb-1 block text-xs text-gray-500">Estado</label>
                            <select
                                className={selectClass}
                                value={filtros.estado}
                                onChange={(e) => aplicarFiltros({ estado: e.target.value })}
                            >
                                <option value="">Todas</option>
                                <option value="activas">Con designaciones activas</option>
                                <option value="pendientes">Con pendientes</option>
                                <option value="sin">Sin designaciones</option>
                            </select>
                        </div>

                        <button
                            onClick={limpiarFiltros}
                            className="inline-flex items-center gap-2 rounded-md border border-gray-300 bg-white px-3 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                            title="Limpiar filtros"
                        >
                            <Icono tipo="embudo" className="h-4 w-4" />
                            Filtros
                        </button>
                    </div>

                    <div className="overflow-x-auto rounded-xl border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    {['Carrera', 'Materias', 'Grupos', 'Designaciones activas', 'Pendientes', 'Estado', 'Acciones'].map((h) => (
                                        <th key={h} className="px-4 py-3 text-left font-medium text-gray-500">
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {carreras.data.length === 0 && (
                                    <tr>
                                        <td colSpan={7} className="px-4 py-6 text-center text-gray-500">
                                            No se encontraron carreras con los filtros actuales.
                                        </td>
                                    </tr>
                                )}
                                {carreras.data.map((carrera) => (
                                    <tr key={carrera.id}>
                                        <td className="px-4 py-3">
                                            <div className="flex items-center gap-3">
                                                <span
                                                    className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-semibold ${paletaIcono[carrera.id % paletaIcono.length]}`}
                                                >
                                                    {carrera.sigla.charAt(0)}
                                                </span>
                                                <div>
                                                    <p className="font-medium text-gray-900">{carrera.nombre}</p>
                                                    <p className="text-xs text-gray-400">{carrera.sigla}</p>
                                                </div>
                                            </div>
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">{carrera.materias}</td>
                                        <td className="px-4 py-3 text-gray-600">{carrera.grupos}</td>
                                        <td className="px-4 py-3">
                                            <BarraProgreso cantidad={carrera.activas} total={carrera.grupos} tono="verde" />
                                        </td>
                                        <td className="px-4 py-3">
                                            <BarraProgreso
                                                cantidad={carrera.pendientes}
                                                total={carrera.grupos}
                                                tono={carrera.situacion === 'sin' ? 'rojo' : 'naranja'}
                                            />
                                        </td>
                                        <td className="px-4 py-3">
                                            {carrera.activas > 0 ? (
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-green-100 px-2.5 py-1 text-xs font-medium text-green-700">
                                                    <span className="h-1.5 w-1.5 rounded-full bg-green-500" />
                                                    Activa
                                                </span>
                                            ) : (
                                                <span className="inline-flex items-center gap-1.5 rounded-full bg-red-100 px-2.5 py-1 text-xs font-medium text-red-600">
                                                    <span className="h-1.5 w-1.5 rounded-full bg-red-500" />
                                                    Sin designaciones
                                                </span>
                                            )}
                                        </td>
                                        <td className="px-4 py-3">
                                            <div className="relative flex items-center gap-1">
                                                <Link
                                                    href={route('designaciones.lista', { carrera_id: carrera.id })}
                                                    title="Ver designaciones"
                                                    className="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                                >
                                                    <Icono tipo="ojo" className="h-4.5 w-4.5" />
                                                </Link>
                                                <button
                                                    onClick={() => setMenuAbierto(menuAbierto === carrera.id ? null : carrera.id)}
                                                    className="rounded-md p-1.5 text-gray-500 hover:bg-gray-100 hover:text-gray-700"
                                                >
                                                    <IconoPuntos className="h-4.5 w-4.5" />
                                                </button>

                                                {menuAbierto === carrera.id && (
                                                    <>
                                                        <div className="fixed inset-0 z-10" onClick={() => setMenuAbierto(null)} />
                                                        <div className="absolute right-0 top-9 z-20 w-56 rounded-md border border-gray-200 bg-white py-1 shadow-lg">
                                                            <Link
                                                                href={route('designaciones.asignar', {
                                                                    carrera_id: carrera.id,
                                                                    gestion_id: filtros.gestion_id,
                                                                    periodo_id: filtros.periodo_id,
                                                                })}
                                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                            >
                                                                Asignar docentes
                                                            </Link>
                                                            <Link
                                                                href={route('designaciones.copiar', {
                                                                    carrera_id: carrera.id,
                                                                    gestion_destino_id: filtros.gestion_id,
                                                                    periodo_destino_id: filtros.periodo_id,
                                                                })}
                                                                className="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                                            >
                                                                Copiar de otra gestión
                                                            </Link>
                                                        </div>
                                                    </>
                                                )}
                                            </div>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-200 px-4 py-3">
                            <p className="text-sm text-gray-500">
                                Mostrando {carreras.from ?? 0} a {carreras.to ?? 0} de {carreras.total} carreras
                            </p>
                            <div className="flex gap-1">
                                {carreras.links.map((link, i) => {
                                    const etiqueta = link.label.includes('Previous')
                                        ? '‹'
                                        : link.label.includes('Next')
                                          ? '›'
                                          : link.label;
                                    return (
                                        <Link
                                            key={i}
                                            href={link.url || '#'}
                                            dangerouslySetInnerHTML={{ __html: etiqueta }}
                                            className={`flex h-8 min-w-8 items-center justify-center rounded-md px-2 text-sm ${
                                                link.active
                                                    ? 'bg-blue-900 text-white'
                                                    : link.url
                                                      ? 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                                      : 'cursor-not-allowed text-gray-300'
                                            }`}
                                        />
                                    );
                                })}
                            </div>
                        </div>
                    </div>
                </div>

                <aside className="w-full shrink-0 space-y-6 xl:w-80">
                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 className="mb-4 font-semibold text-gray-900">Resumen general</h3>
                        <div className="flex justify-center">
                            <Dona resumen={resumen} />
                        </div>
                        <ul className="mt-4 space-y-2">
                            {leyendaDona.map((fila) => (
                                <li key={fila.etiqueta} className="flex items-center justify-between text-sm">
                                    <span className="flex items-center gap-2 text-gray-600">
                                        <span className={`h-2.5 w-2.5 rounded-full ${fila.punto}`} />
                                        {fila.etiqueta}
                                    </span>
                                    <span className="font-medium text-gray-900">
                                        {fila.cantidad}{' '}
                                        <span className="font-normal text-gray-400">
                                            ({resumen.total ? Math.round((fila.cantidad / resumen.total) * 1000) / 10 : 0}%)
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <div className="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-sm">
                            <span className="text-gray-600">Total carreras</span>
                            <span className="font-semibold text-gray-900">{resumen.total}</span>
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <h3 className="mb-3 font-semibold text-gray-900">Acciones rápidas</h3>
                        <ul className="space-y-1">
                            {accionesRapidas.map((accion) => (
                                <li key={accion.etiqueta}>
                                    <Link
                                        href={accion.href}
                                        className="flex items-center gap-3 rounded-md px-3 py-2 text-sm text-gray-700 hover:bg-gray-50"
                                    >
                                        <span className="text-gray-400">
                                            <Icono tipo={accion.tipo} className="h-4.5 w-4.5" />
                                        </span>
                                        {accion.etiqueta}
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="rounded-xl border border-gray-200 bg-white p-5 shadow-sm">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold text-gray-900">¿Necesitas ayuda?</h3>
                                <p className="mt-1 text-sm text-gray-500">
                                    Consulta la guía rápida para aprender a gestionar las designaciones por carrera.
                                </p>
                                <a href="#" onClick={(e) => e.preventDefault()} className="mt-2 inline-block text-sm font-medium text-blue-700 hover:underline">
                                    Ver guía →
                                </a>
                            </div>
                            <span className="text-blue-200">
                                <Icono tipo="documento" className="h-10 w-10" />
                            </span>
                        </div>
                    </div>
                </aside>
            </div>
        </AppLayout>
    );
}
