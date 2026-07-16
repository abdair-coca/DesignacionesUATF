import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import paletaIcono from '../../Components/paletaIcono';
import { useDebouncedSearch } from '../../Hooks/useDebouncedSearch';
import DataTable from '@/Components/DataTable';
import FilaAcciones from '@/Components/FilaAcciones';
import FilterBar from '@/Components/FilterBar';

export default function Index({ docentes, carreras, filtros }) {
    const [busqueda, buscar] = useDebouncedSearch(filtros, { only: ['docentes', 'carreras', 'filtros'] });

    function aplicarFiltros(cambios) {
        router.get(
            route('docentes.index'),
            { q: busqueda, carrera_origen_id: filtros.carrera_origen_id, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true, only: ['docentes', 'carreras', 'filtros'] }
        );
    }

    function limpiarFiltros() {
        buscar('');
        aplicarFiltros({ q: '', carrera_origen_id: '' });
    }

    const hayFiltros = busqueda !== '' || filtros.carrera_origen_id !== '';
    const filas = docentes.data;

    return (
        <AppLayout>
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">Docentes</h1>
                    <p className="mt-1 text-sm text-gray-500">Catálogo de docentes de la universidad.</p>
                </div>
                <Link
                    href={route('docentes.create')}
                    className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                >
                    <Icono tipo="mas" className="h-4 w-4" />
                    Nuevo docente
                </Link>
            </div>

            <FilterBar
                busqueda={busqueda}
                onBusquedaChange={buscar}
                placeholder="Buscar por nombre o CI..."
                campos={[
                    {
                        key: 'carrera_origen_id',
                        label: 'Carrera de origen',
                        valor: filtros.carrera_origen_id,
                        todos: 'Todas',
                        opciones: carreras.map((c) => ({ value: c.id, label: `${c.sigla} — ${c.nombre}` })),
                    },
                ]}
                onChange={aplicarFiltros}
                onLimpiar={limpiarFiltros}
                hayFiltrosActivos={hayFiltros}
            />

            <DataTable>
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
                    {filas.length === 0 && (
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
                    {filas.map((docente, indice) => (
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
                            <FilaAcciones editRoute={route('docentes.edit', docente.id)} deleteRoute={route('docentes.destroy', docente.id)} />
                        </tr>
                    ))}
                </tbody>
            </DataTable>

            <Pagination paginador={docentes} etiqueta="docentes" />
        </AppLayout>
    );
}
