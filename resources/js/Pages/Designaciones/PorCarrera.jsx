import { useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import Select from '../../Components/Select';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import StatTile from '../../Components/StatTile';
import Badge from '../../Components/Badge';
import paletaIcono from '../../Components/paletaIcono';

function textoPorcentaje(cantidad, total) {
    if (!total) return '0% del total';
    const pct = (cantidad / total) * 100;
    return `${Number.isInteger(pct) ? pct : pct.toFixed(1)}% del total`;
}

function BarraProgreso({ cantidad, total, tono }) {
    const pct = total > 0 ? Math.round((cantidad / total) * 100) : 0;
    const colores = {
        verde: ['text-green-700', 'bg-green-500'],
        naranja: ['text-amber-700', 'bg-amber-400'],
        rojo: ['text-red-700', 'bg-red-500'],
    };
    const [texto, barra] = colores[tono];

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
    const visibles = segmentos.filter((s) => s.cantidad > 0).length;
    const separacion = visibles > 1 ? 1.8 : 0;

    let acumulado = 0;
    const arcos = segmentos.map((segmento) => {
        const inicio = acumulado;
        acumulado += segmento.cantidad;
        return { ...segmento, inicio };
    });

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
                <span className="text-[11px] font-medium uppercase tracking-wide text-gray-400">carreras</span>
            </div>
        </div>
    );
}

export default function PorCarrera({ carreras, resumen, gestiones, periodos, filtros }) {
    const [busqueda, setBusqueda] = useState(filtros.q ?? '');
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
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['carreras', 'resumen', 'filtros'],
            }
        );
    }

    function cambiarBusqueda(valor) {
        setBusqueda(valor);
        clearTimeout(temporizador.current);
        temporizador.current = setTimeout(() => aplicarFiltros({ q: valor }), 350);
    }

    function limpiarFiltros() {
        setBusqueda('');
        clearTimeout(temporizador.current);
        router.get(
            route('designaciones.index'),
            {},
            { preserveState: true, preserveScroll: true, replace: true, only: ['carreras', 'resumen', 'filtros'] }
        );
    }

    const hayFiltrosActivos = busqueda !== '' || filtros.estado !== '';

    const leyendaDona = [
        { etiqueta: 'Activas', cantidad: resumen.activas, punto: 'bg-green-500' },
        { etiqueta: 'Pendientes', cantidad: resumen.pendientes, punto: 'bg-amber-400' },
        { etiqueta: 'Sin designaciones', cantidad: resumen.sin, punto: 'bg-red-500' },
    ];

    const accionesRapidas = [
        { etiqueta: 'Nueva designación', tipo: 'mas', href: route('designaciones.create') },
        { etiqueta: 'Copiar designaciones', tipo: 'copiar', href: route('designaciones.copiar') },
    ];

    return (
        <AppLayout>
            <div className="flex flex-col gap-6 xl:flex-row">
                <div className="min-w-0 flex-1">
                    <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-gray-900">Designaciones por carrera</h1>
                            <p className="mt-1 text-sm text-gray-500">
                                Administra y consulta todas las designaciones académicas organizadas por carrera.
                            </p>
                        </div>
                        <Link
                            href={route('designaciones.create')}
                            className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                        >
                            <Icono tipo="mas" className="h-4 w-4" />
                            Nueva designación
                        </Link>
                    </div>

                    <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        <StatTile
                            tipo="total"
                            tono="azul"
                            titulo="Total carreras"
                            valor={resumen.total}
                            subtitulo="100% del total"
                        />
                        <StatTile
                            tipo="check"
                            tono="verde"
                            titulo="Con designaciones activas"
                            valor={resumen.activas}
                            subtitulo={textoPorcentaje(resumen.activas, resumen.total)}
                        />
                        <StatTile
                            tipo="reloj"
                            tono="ambar"
                            titulo="Con pendientes"
                            valor={resumen.pendientes}
                            subtitulo={textoPorcentaje(resumen.pendientes, resumen.total)}
                        />
                        <StatTile
                            tipo="equis"
                            tono="rojo"
                            titulo="Sin designaciones"
                            valor={resumen.sin}
                            subtitulo={textoPorcentaje(resumen.sin, resumen.total)}
                        />
                    </div>

                    <div className="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
                        <div className="relative min-w-52 flex-1">
                            <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <Icono tipo="buscar" className="h-4 w-4" />
                            </span>
                            <input
                                type="text"
                                placeholder="Buscar carrera..."
                                value={busqueda}
                                onChange={(e) => cambiarBusqueda(e.target.value)}
                                className="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-9 text-sm shadow-sm transition-colors placeholder:text-gray-400 hover:border-gray-300 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            />
                            {busqueda !== '' && (
                                <button
                                    onClick={() => cambiarBusqueda('')}
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-full p-0.5 text-gray-300 transition-colors hover:bg-gray-100 hover:text-gray-500"
                                    title="Limpiar búsqueda"
                                >
                                    <Icono tipo="cerrar" className="h-3.5 w-3.5" />
                                </button>
                            )}
                        </div>

                        <div className="w-32">
                            <label className="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                Gestión
                            </label>
                            <Select value={filtros.gestion_id} onChange={(e) => aplicarFiltros({ gestion_id: e.target.value })}>
                                {gestiones.map((gestion) => (
                                    <option key={gestion.id} value={gestion.id}>
                                        {gestion.nombre}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        <div className="w-32">
                            <label className="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                Periodo
                            </label>
                            <Select value={filtros.periodo_id} onChange={(e) => aplicarFiltros({ periodo_id: e.target.value })}>
                                {periodos.map((periodo) => (
                                    <option key={periodo.id} value={periodo.id}>
                                        {periodo.nombre}
                                    </option>
                                ))}
                            </Select>
                        </div>

                        <div className="w-44">
                            <label className="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                Estado
                            </label>
                            <Select value={filtros.estado} onChange={(e) => aplicarFiltros({ estado: e.target.value })}>
                                <option value="">Todas</option>
                                <option value="activas">Con designaciones activas</option>
                                <option value="pendientes">Con pendientes</option>
                                <option value="sin">Sin designaciones</option>
                            </Select>
                        </div>

                        <button
                            onClick={limpiarFiltros}
                            disabled={!hayFiltrosActivos}
                            className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                            title="Limpiar filtros"
                        >
                            <Icono tipo="embudo" className="h-4 w-4" />
                            Filtros
                        </button>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            <table className="min-w-full divide-y divide-gray-200 text-sm">
                                <thead className="bg-gray-50/80">
                                    <tr>
                                        {['Carrera', 'Materias', 'Grupos', 'Designaciones activas', 'Pendientes', 'Estado', 'Acciones'].map(
                                            (encabezado) => (
                                                <th
                                                    key={encabezado}
                                                    className="whitespace-nowrap px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400"
                                                >
                                                    {encabezado}
                                                </th>
                                            )
                                        )}
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-gray-100">
                                    {carreras.data.length === 0 && (
                                        <tr>
                                            <td colSpan={7}>
                                                <EmptyState
                                                    titulo="Sin resultados"
                                                    subtitulo="Ninguna carrera coincide con los filtros actuales."
                                                    accion={{ label: 'Limpiar filtros', onClick: limpiarFiltros }}
                                                />
                                            </td>
                                        </tr>
                                    )}
                                    {carreras.data.map((carrera, indice) => (
                                        <tr
                                            key={carrera.id}
                                            className="fila-entra group transition-colors hover:bg-gray-50/60"
                                            style={{ animationDelay: `${Math.min(indice * 30, 240)}ms` }}
                                        >
                                            <td className="px-4 py-3.5">
                                                <div className="flex items-center gap-3">
                                                    <span
                                                        className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-semibold ring-1 ring-inset ${paletaIcono[carrera.id % paletaIcono.length]}`}
                                                    >
                                                        {carrera.sigla.charAt(0)}
                                                    </span>
                                                    <div>
                                                        <p className="font-medium text-gray-900">{carrera.nombre}</p>
                                                        <p className="mt-0.5 text-xs text-gray-400">{carrera.sigla}</p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td className="px-4 py-3.5 text-gray-600 tabular-nums">{carrera.materias}</td>
                                            <td className="px-4 py-3.5 text-gray-600 tabular-nums">{carrera.grupos}</td>
                                            <td className="px-4 py-3.5">
                                                <BarraProgreso cantidad={carrera.activas} total={carrera.grupos} tono="verde" />
                                            </td>
                                            <td className="px-4 py-3.5">
                                                <BarraProgreso
                                                    cantidad={carrera.pendientes}
                                                    total={carrera.grupos}
                                                    tono={carrera.situacion === 'sin' ? 'rojo' : 'naranja'}
                                                />
                                            </td>
                                            <td className="px-4 py-3.5">
                                                {carrera.activas > 0 ? (
                                                    <Badge tono="verde" icono="check">Activa</Badge>
                                                ) : (
                                                    <Badge tono="rojo" icono="equis">Sin designaciones</Badge>
                                                )}
                                            </td>
                                            <td className="px-4 py-3.5">
                                                <div className="flex items-center gap-0.5">
                                                    <Link
                                                        href={route('designaciones.carrera', {
                                                            carrera: carrera.id,
                                                            gestion_id: filtros.gestion_id,
                                                            periodo_id: filtros.periodo_id,
                                                        })}
                                                        title="Designar en esta carrera"
                                                        className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                                                    >
                                                        <Icono tipo="ojo" className="h-[18px] w-[18px]" />
                                                    </Link>
                                                    <Link
                                                        href={route('designaciones.copiar', {
                                                            carrera_id: carrera.id,
                                                            gestion_destino_id: filtros.gestion_id,
                                                            periodo_destino_id: filtros.periodo_id,
                                                        })}
                                                        title="Copiar de otra gestión"
                                                        className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-blue-500/40"
                                                    >
                                                        <Icono tipo="copiar" className="h-[18px] w-[18px]" />
                                                    </Link>
                                                </div>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        <Pagination paginador={carreras} etiqueta="carreras" />
                    </div>
                </div>

                <aside className="w-full shrink-0 space-y-5 xl:w-80">
                    <div className="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm">
                        <h3 className="font-semibold tracking-tight text-gray-900">Resumen general</h3>
                        <div className="mt-4 flex justify-center">
                            <Dona resumen={resumen} />
                        </div>
                        <ul className="mt-5 space-y-1">
                            {leyendaDona.map((fila) => (
                                <li
                                    key={fila.etiqueta}
                                    className="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm transition-colors hover:bg-gray-50"
                                >
                                    <span className="flex items-center gap-2.5 text-gray-600">
                                        <span className={`h-2.5 w-2.5 rounded-full ${fila.punto}`} />
                                        {fila.etiqueta}
                                    </span>
                                    <span className="font-semibold text-gray-900 tabular-nums">
                                        {fila.cantidad}{' '}
                                        <span className="font-normal text-gray-400">
                                            ({resumen.total ? Math.round((fila.cantidad / resumen.total) * 1000) / 10 : 0}%)
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <div className="mt-4 flex items-center justify-between border-t border-gray-100 pt-3.5 text-sm">
                            <span className="text-gray-500">Total carreras</span>
                            <span className="font-semibold text-gray-900 tabular-nums">{resumen.total}</span>
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm">
                        <h3 className="mb-3 font-semibold tracking-tight text-gray-900">Acciones rápidas</h3>
                        <ul className="space-y-0.5">
                            {accionesRapidas.map((accion) => (
                                <li key={accion.etiqueta}>
                                    <Link
                                        href={accion.href}
                                        className="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50"
                                    >
                                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-50 text-gray-400 ring-1 ring-inset ring-gray-200/60 transition-colors group-hover:bg-blue-50 group-hover:text-blue-600 group-hover:ring-blue-200/60">
                                            <Icono tipo={accion.tipo} className="h-4 w-4" />
                                        </span>
                                        <span className="flex-1 font-medium">{accion.etiqueta}</span>
                                        <span className="text-gray-300 transition-all group-hover:translate-x-0.5 group-hover:text-gray-400">
                                            <Icono tipo="chevronDerecha" className="h-3.5 w-3.5" />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="rounded-xl border border-blue-100 bg-blue-50/50 p-5 shadow-sm">
                        <div className="flex items-start justify-between gap-3">
                            <div>
                                <h3 className="font-semibold tracking-tight text-gray-900">¿Necesitas ayuda?</h3>
                                <p className="mt-1 text-sm leading-relaxed text-gray-500">
                                    Consulta la guía rápida para aprender a gestionar las designaciones por carrera.
                                </p>
                                <a
                                    href="#"
                                    onClick={(e) => e.preventDefault()}
                                    className="mt-2.5 inline-flex items-center gap-1 text-sm font-medium text-blue-700 transition-colors hover:text-blue-800"
                                >
                                    Ver guía
                                    <Icono tipo="chevronDerecha" className="h-3.5 w-3.5" />
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
