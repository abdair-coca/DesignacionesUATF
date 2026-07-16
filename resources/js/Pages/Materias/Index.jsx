import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import paletaIcono from '../../Components/paletaIcono';
import DataTable from '@/Components/DataTable';
import FilaAcciones from '@/Components/FilaAcciones';
import FilterBar from '@/Components/FilterBar';

export default function Index({ materias, carreras, filtros }) {
    function filtrarPor(cambios) {
        router.get(
            route('materias.index'),
            { carrera_id: filtros.carrera_id, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true, only: ['materias', 'filtros'] }
        );
    }

    const hayFiltro = filtros.carrera_id !== '';
    const filas = materias.data;

    return (
        <AppLayout>
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">Materias</h1>
                    <p className="mt-1 text-sm text-gray-500">Catálogo de materias por carrera, con su carga horaria.</p>
                </div>
                <Link
                    href={route('materias.create')}
                    className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                >
                    <Icono tipo="mas" className="h-4 w-4" />
                    Nueva materia
                </Link>
            </div>

            <FilterBar
                campos={[
                    {
                        key: 'carrera_id',
                        label: 'Carrera',
                        valor: filtros.carrera_id,
                        todos: 'Todas',
                        opciones: carreras.map((c) => ({ value: c.id, label: `${c.sigla} — ${c.nombre}` })),
                    },
                ]}
                onChange={filtrarPor}
                onLimpiar={() => filtrarPor({ carrera_id: '' })}
                hayFiltrosActivos={hayFiltro}
            />

            <DataTable>
                <thead className="bg-gray-50/80">
                    <tr>
                        {['Materia', 'Carrera', 'Horas', 'Grupos', 'Acciones'].map((encabezado) => (
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
                            <td colSpan={5}>
                                <EmptyState
                                    titulo="Sin resultados"
                                    subtitulo="Ninguna materia coincide con el filtro actual."
                                    accion={hayFiltro ? { label: 'Limpiar filtros', onClick: () => filtrarPor({ carrera_id: '' }) } : undefined}
                                />
                            </td>
                        </tr>
                    )}
                    {filas.map((materia, indice) => (
                        <tr
                            key={materia.id}
                            className="fila-entra transition-colors hover:bg-gray-50/60"
                            style={{ animationDelay: `${Math.min(indice * 30, 240)}ms` }}
                        >
                            <td className="px-4 py-3.5">
                                <div className="flex items-center gap-3">
                                    <span
                                        className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-semibold ring-1 ring-inset ${paletaIcono[materia.id % paletaIcono.length]}`}
                                    >
                                        {materia.sigla.charAt(0)}
                                    </span>
                                    <div>
                                        <p className="font-medium text-gray-900">{materia.nombre}</p>
                                        <p className="mt-0.5 text-xs text-gray-400">{materia.sigla}</p>
                                    </div>
                                </div>
                            </td>
                            <td className="px-4 py-3.5 text-gray-600">{materia.carrera.sigla}</td>
                            <td className="px-4 py-3.5 text-gray-600 tabular-nums">{materia.horas}h</td>
                            <td className="px-4 py-3.5 text-gray-600 tabular-nums">{materia.grupos_count}</td>
                            <FilaAcciones editRoute={route('materias.edit', materia.id)} deleteRoute={route('materias.destroy', materia.id)} />
                        </tr>
                    ))}
                </tbody>
            </DataTable>

            <Pagination paginador={materias} etiqueta="materias" />
        </AppLayout>
    );
}
