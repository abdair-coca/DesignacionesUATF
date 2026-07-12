import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import Select from '../../Components/Select';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import ConfirmDeleteButton from '../../Components/ConfirmDeleteButton';
import paletaIcono from '../../Components/paletaIcono';

export default function Index({ materias, carreras, filtros }) {
    function filtrarPorCarrera(carreraId) {
        router.get(
            route('materias.index'),
            { carrera_id: carreraId },
            { preserveState: true, preserveScroll: true, replace: true, only: ['materias', 'filtros'] }
        );
    }

    const hayFiltro = filtros.carrera_id !== '';

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

            <div className="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
                <div className="w-56">
                    <label className="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">Carrera</label>
                    <Select value={filtros.carrera_id} onChange={(e) => filtrarPorCarrera(e.target.value)}>
                        <option value="">Todas</option>
                        {carreras.map((carrera) => (
                            <option key={carrera.id} value={carrera.id}>
                                {carrera.sigla} — {carrera.nombre}
                            </option>
                        ))}
                    </Select>
                </div>

                <button
                    onClick={() => filtrarPorCarrera('')}
                    disabled={!hayFiltro}
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
                            {materias.data.length === 0 && (
                                <tr>
                                    <td colSpan={5}>
                                        <EmptyState
                                            titulo="Sin resultados"
                                            subtitulo="Ninguna materia coincide con el filtro actual."
                                            accion={hayFiltro ? { label: 'Limpiar filtros', onClick: () => filtrarPorCarrera('') } : undefined}
                                        />
                                    </td>
                                </tr>
                            )}
                            {materias.data.map((materia, indice) => (
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
                                    <td className="px-4 py-3.5">
                                        <div className="flex items-center gap-0.5">
                                            <Link
                                                href={route('materias.edit', materia.id)}
                                                title="Editar"
                                                className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            >
                                                <Icono tipo="lapiz" className="h-[18px] w-[18px]" />
                                            </Link>
                                            <ConfirmDeleteButton
                                                deleteUrl={route('materias.destroy', materia.id)}
                                                mensaje={`¿Eliminar la materia ${materia.nombre}?`}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination paginador={materias} etiqueta="materias" />
            </div>
        </AppLayout>
    );
}
