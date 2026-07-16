import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import EmptyState from '../../Components/EmptyState';
import Badge from '../../Components/Badge';
import paletaIcono from '../../Components/paletaIcono';
import DataTable from '@/Components/DataTable';
import FilaAcciones from '@/Components/FilaAcciones';
import FilterBar from '@/Components/FilterBar';

const badgeEstado = {
    habilitado: { tono: 'verde', icono: 'check', etiqueta: 'Habilitado' },
    deshabilitado: { tono: 'gris', icono: 'vacio', etiqueta: 'Deshabilitado' },
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

            <FilterBar
                campos={[
                    {
                        key: 'materia_id',
                        label: 'Materia',
                        valor: filtros.materia_id,
                        todos: 'Todas',
                        opciones: materias.map((m) => ({ value: m.id, label: `${m.sigla} — ${m.nombre}` })),
                    },
                    {
                        key: 'estado',
                        label: 'Estado',
                        valor: filtros.estado,
                        todos: 'Todos',
                        opciones: [
                            { value: 'habilitado', label: 'Habilitado' },
                            { value: 'deshabilitado', label: 'Deshabilitado' },
                        ],
                    },
                ]}
                onChange={aplicarFiltros}
                onLimpiar={() => aplicarFiltros({ materia_id: '', estado: '' })}
                hayFiltrosActivos={hayFiltros}
            />

            <DataTable>
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
                    {grupos.length === 0 && (
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
                    {grupos.map((grupo, indice) => (
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
                                <Badge tono={badgeEstado[grupo.estado].tono} icono={badgeEstado[grupo.estado].icono}>
                                    {badgeEstado[grupo.estado].etiqueta}
                                </Badge>
                            </td>
                            <td className="px-4 py-3.5 text-gray-600 tabular-nums">{grupo.designaciones_count}</td>
                            <FilaAcciones editRoute={route('grupos.edit', grupo.id)} deleteRoute={route('grupos.destroy', grupo.id)} />
                        </tr>
                    ))}
                </tbody>
            </DataTable>
        </AppLayout>
    );
}
