import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import Select from '../../Components/Select';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import StatTile from '../../Components/StatTile';
import Badge from '../../Components/Badge';
import paletaIcono from '../../Components/paletaIcono';
import { useDebouncedSearch } from '../../Hooks/useDebouncedSearch';
import FilterBar from '@/Components/FilterBar';
import GraficoAnillo from '@/Components/GraficoAnillo';
import BarraProgreso from '@/Components/BarraProgreso';

function textoPorcentaje(cantidad, total) {
    if (!total) return '0% del total';
    const pct = (cantidad / total) * 100;
    return `${Number.isInteger(pct) ? pct : pct.toFixed(1)}% del total`;
}


export default function PorCarrera({ carreras, resumen, gestiones, periodos, filtros }) {
    const [busqueda, buscar] = useDebouncedSearch(filtros, { only: ['carreras', 'resumen', 'filtros'] });

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

    function limpiarFiltros() {
        buscar('');
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

                    <FilterBar
                        busqueda={busqueda}
                        onBusquedaChange={buscar}
                        placeholder="Buscar carrera..."
                        campos={[
                            {
                                key: 'gestion_id',
                                label: 'Gestión',
                                valor: filtros.gestion_id,
                                opciones: gestiones.map((g) => ({ value: g.id, label: g.nombre })),
                            },
                            {
                                key: 'periodo_id',
                                label: 'Periodo',
                                valor: filtros.periodo_id,
                                opciones: periodos.map((p) => ({ value: p.id, label: p.nombre })),
                            },
                            {
                                key: 'estado',
                                label: 'Estado',
                                valor: filtros.estado,
                                todos: 'Todas',
                                opciones: [
                                    { value: 'activas', label: 'Con designaciones activas' },
                                    { value: 'pendientes', label: 'Con pendientes' },
                                    { value: 'sin', label: 'Sin designaciones' },
                                ],
                            },
                        ]}
                        onChange={aplicarFiltros}
                        onLimpiar={limpiarFiltros}
                        hayFiltrosActivos={hayFiltrosActivos}
                    />

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
                            <GraficoAnillo resumen={resumen} />
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
