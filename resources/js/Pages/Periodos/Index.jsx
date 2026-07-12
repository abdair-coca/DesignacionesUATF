import { Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import EmptyState from '../../Components/EmptyState';
import ConfirmDeleteButton from '../../Components/ConfirmDeleteButton';

export default function Index({ periodos }) {
    return (
        <AppLayout>
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">Periodos</h1>
                    <p className="mt-1 text-sm text-gray-500">Periodos académicos, compartidos por toda la universidad.</p>
                </div>
                <Link
                    href={route('periodos.create')}
                    className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                >
                    <Icono tipo="mas" className="h-4 w-4" />
                    Nuevo periodo
                </Link>
            </div>

            <div className="overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm">
                <div className="overflow-x-auto">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50/80">
                            <tr>
                                {['Periodo', 'Designaciones', 'Acciones'].map((encabezado) => (
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
                            {periodos.length === 0 && (
                                <tr>
                                    <td colSpan={3}>
                                        <EmptyState titulo="Sin periodos" subtitulo="Todavía no hay periodos registrados." />
                                    </td>
                                </tr>
                            )}
                            {periodos.map((periodo, indice) => (
                                <tr
                                    key={periodo.id}
                                    className="fila-entra transition-colors hover:bg-gray-50/60"
                                    style={{ animationDelay: `${Math.min(indice * 30, 240)}ms` }}
                                >
                                    <td className="px-4 py-3.5 font-medium text-gray-900">{periodo.nombre}</td>
                                    <td className="px-4 py-3.5 text-gray-600 tabular-nums">{periodo.designaciones_count}</td>
                                    <td className="px-4 py-3.5">
                                        <div className="flex items-center gap-0.5">
                                            <Link
                                                href={route('periodos.edit', periodo.id)}
                                                title="Editar"
                                                className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700"
                                            >
                                                <Icono tipo="lapiz" className="h-[18px] w-[18px]" />
                                            </Link>
                                            <ConfirmDeleteButton
                                                deleteUrl={route('periodos.destroy', periodo.id)}
                                                mensaje={`¿Eliminar el periodo ${periodo.nombre}?`}
                                            />
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </AppLayout>
    );
}
