import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import EmptyState from '../../Components/EmptyState';
import Pagination from '../../Components/Pagination';
import FilterBar from '../../Components/FilterBar';
import Badge from '../../Components/Badge';
import DataTable from '@/Components/DataTable';
import FilaAcciones from '@/Components/FilaAcciones';

const badgeEstado = {
    aprobada: { tono: 'verde', icono: 'check' },
    rechazada: { tono: 'rojo', icono: 'equis' },
    propuesta: { tono: 'ambar', icono: 'reloj' },
};

export default function Lista({ designaciones, carreras, gestiones, periodos, filtros }) {
    function aplicarFiltros(cambios) {
        router.get(
            route('designaciones.lista'),
            {
                carrera_id: filtros.carrera_id,
                materia_id: filtros.materia_id,
                gestion_id: filtros.gestion_id,
                periodo_id: filtros.periodo_id,
                estado: filtros.estado,
                ...cambios,
            },
            { preserveState: true, preserveScroll: true, replace: true, only: ['designaciones', 'filtros'] }
        );
    }

    function limpiarFiltros() {
        aplicarFiltros({ carrera_id: '', materia_id: '', gestion_id: '', periodo_id: '', estado: '' });
    }

    const hayFiltrosActivos = Object.values(filtros).some((v) => v !== '');

    const campos = [
        {
            key: 'carrera_id',
            label: 'Carrera',
            valor: filtros.carrera_id,
            opciones: carreras.map((c) => ({ value: c.id, label: `${c.sigla} — ${c.nombre}` })),
        },
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
            opciones: [
                { value: 'propuesta', label: 'Propuesta' },
                { value: 'aprobada', label: 'Aprobada' },
                { value: 'rechazada', label: 'Rechazada' },
            ],
        },
    ];

    return (
        <AppLayout>
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">Designaciones</h1>
                    <p className="mt-1 text-sm text-gray-500">Listado completo, filtrable por carrera, gestión, periodo y estado.</p>
                </div>
                <div className="flex flex-wrap items-center gap-2">
                    <Link
                        href={route('designaciones.index')}
                        className="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50"
                    >
                        ← Volver a carreras
                    </Link>
                    <Link
                        href={route('designaciones.create')}
                        className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                    >
                        <Icono tipo="mas" className="h-4 w-4" />
                        Nueva designación
                    </Link>
                </div>
            </div>

            <FilterBar
                campos={campos}
                onChange={(key, valor) => aplicarFiltros({ [key]: valor })}
                onLimpiar={limpiarFiltros}
                hayFiltrosActivos={hayFiltrosActivos}
            />

            <DataTable>
                <thead className="bg-gray-50/80">
                    <tr>
                        {['Docente', 'Materia', 'Grupo', 'Gestión', 'Periodo', 'Estado', 'Acciones'].map((encabezado) => (
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
                    {designaciones.data.length === 0 && (
                        <tr>
                            <td colSpan={7}>
                                <EmptyState
                                    titulo="Sin resultados"
                                    subtitulo="Ninguna designación coincide con los filtros actuales."
                                    accion={hayFiltrosActivos ? { label: 'Limpiar filtros', onClick: limpiarFiltros } : undefined}
                                />
                            </td>
                        </tr>
                    )}
                    {designaciones.data.map((designacion, indice) => (
                        <tr
                            key={designacion.id}
                            className="fila-entra transition-colors hover:bg-gray-50/60"
                            style={{ animationDelay: `${Math.min(indice * 30, 240)}ms` }}
                        >
                            <td className="px-4 py-3.5 font-medium text-gray-900">{designacion.docente.nombre}</td>
                            <td className="px-4 py-3.5 text-gray-600">
                                {designacion.materia.sigla} — {designacion.materia.nombre}
                            </td>
                            <td className="px-4 py-3.5 text-gray-600">{designacion.grupo.codigo}</td>
                            <td className="px-4 py-3.5 text-gray-600">{designacion.gestion.nombre}</td>
                            <td className="px-4 py-3.5 text-gray-600">{designacion.periodo.nombre}</td>
                            <td className="px-4 py-3.5">
                                <Badge tono={badgeEstado[designacion.estado].tono} icono={badgeEstado[designacion.estado].icono}>
                                    {designacion.estado.charAt(0).toUpperCase() + designacion.estado.slice(1)}
                                </Badge>
                            </td>
                            <FilaAcciones editRoute={route('designaciones.edit', designacion.id)} deleteRoute={route('designaciones.destroy', designacion.id)}>
                                <Link
                                    href={route('designaciones.historial', designacion.id)}
                                    title="Historial"
                                    className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                >
                                    <Icono tipo="reloj" className="h-[18px] w-[18px]" />
                                </Link>
                            </FilaAcciones>
                        </tr>
                    ))}
                </tbody>
            </DataTable>

            <Pagination paginador={designaciones} etiqueta="designaciones" />
        </AppLayout>
    );
}
