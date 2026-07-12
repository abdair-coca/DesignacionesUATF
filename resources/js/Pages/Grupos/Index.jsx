import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import Select from '../../Components/Select';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import ConfirmDeleteButton from '../../Components/ConfirmDeleteButton';
import paletaIcono from '../../Components/paletaIcono';

const badgeEstado = {
    habilitado: 'bg-green-50 text-green-700 ring-green-600/20',
    deshabilitado: 'bg-gray-50 text-gray-500 ring-gray-400/30',
};

export default function Index({ grupos, materias, filtros }) {
    function aplicarFiltros(cambios) {
        router.get(
            route('grupos.index'),
            { materia_id: filtros.materia_id, estado: filtros.estado, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true, only: ['grupos', 'filtros'] }
        );
    }

    const hayFiltros = filtros.materia_id !== '' || filtros.estado !== '';

    return (
        <AppLayout>
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">Grupos</h1>
                    <p className="mt-1 text-sm text-gray-500">Grupos por materia, reutilizables entre gestiones y periodos.</p>
                </div>
                <Link
                    href={route('grupos.create')}
                    className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                >
                    <Icono tipo="mas" className="h-4 w-4" />
                    Nuevo grupo
                </Link>
            </div>

            <div className="mb-4 flex flex-wrap items-end gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
                <div className="w-56">
                    <label className="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">Materia</label>
                    <Select value={filtros.materia_id} onChange={(e) => aplicarFiltros({ materia_id: e.target.value })}>
                        <option value="">Todas</option>
                        {materias.map((materia) => (
                            <option key={materia.id} value={materia.id}>
                                {materia.sigla} — {materia.nombre}
                            </option>
                        ))}
                    </Select>
                </div>

                <div className="w-44">
                    <label className="mb-1 block text-[11px] font-medium uppercase tracking-wide text-gray-400">Estado</label>
                    <Select value={filtros.estado} onChange={(e) => aplicarFiltros({ estado: e.target.value })}>
                        <option value="">Todos</option>
                        <option value="habilitado">Habilitado</option>
                        <option value="deshabilitado">Deshabilitado</option>
                    </Select>
                </div>

                <button
                    onClick={() => aplicarFiltros({ materia_id: '', estado: '' })}
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
                                {['Grupo', 'Materia', 'Estado', 'Designaciones', 'Acciones'].map((encabezado) => (
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
                            {grupos.data.length === 0 && (
                                <tr>
                                    <td colSpan={5}>
                                        <EmptyState
                                            titulo="Sin resultados"
                                            subtitulo="Ningún grupo coincide con los filtros actuales."
                                            accion={hayFiltros ? { label: 'Limpiar filtros', onClick: () => aplicarFiltros({ materia_id: '', estado: '' }) } : undefined}
                                        />
                                    </td>
                                </tr>
                            )}
                            {grupos.data.map((grupo, indice) => (
                                <tr
                                    key={grupo.id}
                                    className="fila-entra transition-colors hover:bg-gray-50/60"
                                    style={{ animationDelay: `${Math.min(indice * 30, 240)}ms` }}
                                >
                                    <td className="px-4 py-3.5">
                                        <div className="flex items-center gap-3">
                                            <span
                                                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-semibold ring-1 ring-inset ${paletaIcono[grupo.id % paletaIcono.length]}`}
                                            >
                                                {grupo.codigo}
                                            </span>
                                            <div>
                                                <p className="font-medium text-gray-900">Grupo {grupo.codigo}</p>
                                                <p className="mt-0.5 text-xs text-gray-400">{grupo.materia.carrera.sigla}</p>
                                            </div>
                                        </div>
                                    </td>
                                    <td className="px-4 py-3.5 text-gray-600">
                                        {grupo.materia.sigla} — {grupo.materia.nombre}
                                    </td>
                                    <td className="px-4 py-3.5">
                                        <span className={`inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium ring-1 ring-inset ${badgeEstado[grupo.estado]}`}>
                                            <span className={`h-1.5 w-1.5 rounded-full ${grupo.estado === 'habilitado' ? 'bg-green-500' : 'bg-gray-400'}`} />
                                            {grupo.estado === 'habilitado' ? 'Habilitado' : 'Deshabilitado'}
                                        </span>
                                    </td>
                                    <td className="px-4 py-3.5 text-gray-600 tabular-nums">{grupo.designaciones_count}</td>
                                    <td className="px-4 py-3.5">
                                        <div className="flex items-center gap-0.5">
                                            <Link
                                                href={route('grupos.edit', grupo.id)}
                                                title="Editar"
                                                className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            >
                                                <Icono tipo="lapiz" className="h-[18px] w-[18px]" />
                                            </Link>
                                            <ConfirmDeleteButton
                                                deleteUrl={route('grupos.destroy', grupo.id)}
                                                mensaje={`¿Eliminar el grupo ${grupo.codigo} de ${grupo.materia.sigla}?`}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>

                <Pagination paginador={grupos} etiqueta="grupos" />
            </div>
        </AppLayout>
    );
}
