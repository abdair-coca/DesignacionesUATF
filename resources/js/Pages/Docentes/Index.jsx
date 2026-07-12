import { useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import Select from '../../Components/Select';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import ConfirmDeleteButton from '../../Components/ConfirmDeleteButton';
import paletaIcono from '../../Components/paletaIcono';

export default function Index({ docentes, carreras, filtros }) {
    const [busqueda, setBusqueda] = useState(filtros.q ?? '');
    const temporizador = useRef(null);

    function aplicarFiltros(cambios) {
        router.get(
            route('docentes.index'),
            { q: busqueda, carrera_origen_id: filtros.carrera_origen_id, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true, only: ['docentes', 'filtros'] }
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
        aplicarFiltros({ q: '', carrera_origen_id: '' });
    }

    const hayFiltros = busqueda !== '' || filtros.carrera_origen_id !== '';

    return (
        <AppLayout>
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">Docentes</h1>
                    <p className="mt-1 text-sm text-gray-500">Catálogo de docentes de toda la universidad.</p>
                </div>
                <Link
                    href={route('docentes.create')}
                    className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                >
                    <Icono tipo="mas" className="h-4 w-4" />
                    Nuevo docente
                </Link>
            </div>

            <div className="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
                <div className="relative min-w-52 flex-1">
                    <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                        <Icono tipo="buscar" className="h-4 w-4" />
                    </span>
                    <input
                        type="text"
                        placeholder="Buscar por nombre o CI..."
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

                <div className="w-56">
                    <label className="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">
                        Carrera de origen
                    </label>
                    <Select
                        value={filtros.carrera_origen_id}
                        onChange={(e) => aplicarFiltros({ carrera_origen_id: e.target.value })}
                    >
                        <option value="">Todas</option>
                        {carreras.map((carrera) => (
                            <option key={carrera.id} value={carrera.id}>
                                {carrera.sigla} — {carrera.nombre}
                            </option>
                        ))}
                    </Select>
                </div>

                <button
                    onClick={limpiarFiltros}
                    disabled={!hayFiltros}
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
                                {['Docente', 'Carrera de origen', 'Designaciones', 'Acciones'].map((encabezado) => (
                                    <th
                                        key={encabezado}
                                        className="whitespace-nowrap px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400"
                                    >
                                        {encabezado}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {docentes.data.length === 0 && (
                                <tr>
                                    <td colSpan={4}>
                                        <EmptyState
                                            titulo="Sin resultados"
                                            subtitulo="Ningún docente coincide con los filtros actuales."
                                            accion={hayFiltros ? { label: 'Limpiar filtros', onClick: limpiarFiltros } : undefined}
                                        />
                                    </td>
                                </tr>
                            )}
                            {docentes.data.map((docente, indice) => (
                                <tr
                                    key={docente.id}
                                    className="fila-entra transition-colors hover:bg-gray-50/60"
                                    style={{ animationDelay: `${Math.min(indice * 30, 240)}ms` }}
                                >
                                    <td className="px-4 py-3.5">
                                        <div className="flex items-center gap-3">
                                            <span
                                                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-semibold ring-1 ring-inset ${paletaIcono[docente.id % paletaIcono.length]}`}
                                            >
                                                {docente.nombre.charAt(0)}
                                            </span>
                                            <div>
                                                <p className="font-medium text-gray-900">{docente.nombre}</p>
                                                <p className="mt-0.5 text-xs text-gray-400">{docente.ci}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3.5 text-gray-600">
                                        {docente.carrera_origen ? docente.carrera_origen.sigla : '—'}
                                    </td>
                                    <td className="px-4 py-3.5 text-gray-600 tabular-nums">{docente.designaciones_count}</td>
                                    <td className="px-4 py-3.5">
                                        <div className="flex items-center gap-0.5">
                                            <Link
                                                href={route('docentes.edit', docente.id)}
                                                title="Editar"
                                                className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            >
                                                <Icono tipo="lapiz" className="h-[18px] w-[18px]" />
                                            </Link>
                                            <ConfirmDeleteButton
                                                deleteUrl={route('docentes.destroy', docente.id)}
                                                mensaje={`¿Eliminar al docente ${docente.nombre}?`}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination paginador={docentes} etiqueta="docentes" />
            </div>
        </AppLayout>
    );
}
