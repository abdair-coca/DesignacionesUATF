import { Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import EmptyState from '../../Components/EmptyState';
import Badge from '../../Components/Badge';

export default function Pendientes({ pendientes }) {
    return (
        <AppLayout>
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight text-gray-900">Revisiones pendientes</h1>
                <p className="mt-1 text-sm text-gray-500">
                    Carreras que esperan revisión de sus designaciones.
                </p>
            </div>

            {pendientes.length === 0 ? (
                <EmptyState
                    icono="check"
                    titulo="Todo al día"
                    subtitulo="No hay carreras pendientes de revisión."
                />
            ) : (
                <div className="overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm">
                    <table className="min-w-full divide-y divide-gray-200 text-sm">
                        <thead className="bg-gray-50/80">
                            <tr>
                                {['Carrera', 'Gestión / Periodo', 'Solicitante', 'Solicitado el', 'Acciones'].map((h) => (
                                    <th
                                        key={h}
                                        className="whitespace-nowrap px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400"
                                    >
                                        {h}
                                    </th>
                                ))}
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {pendientes.map((rev) => (
                                <tr key={rev.id} className="transition-colors hover:bg-gray-50/60">
                                    <td className="px-4 py-3.5">
                                        <p className="font-medium text-gray-900">{rev.carrera_nombre}</p>
                                        <p className="text-xs text-gray-400">{rev.carrera_sigla}</p>
                                    </td>
                                    <td className="px-4 py-3.5 text-gray-600">
                                        {rev.gestion_nombre} / {rev.periodo_nombre}
                                    </td>
                                    <td className="px-4 py-3.5 text-gray-600">{rev.solicitante}</td>
                                    <td className="px-4 py-3.5 text-gray-600">{rev.solicitado_en}</td>
                                    <td className="px-4 py-3.5">
                                        <Link
                                            href={route('revisiones.revisar', rev.id)}
                                            className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                                        >
                                            <Icono tipo="ojo" className="h-3.5 w-3.5" />
                                            Revisar
                                        </Link>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}
        </AppLayout>
    );
}
