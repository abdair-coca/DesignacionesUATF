import { Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import EmptyState from '../../Components/EmptyState';
import paletaIcono from '../../Components/paletaIcono';
import DataTable from '@/Components/DataTable';
import FilaAcciones from '@/Components/FilaAcciones';

export default function Index({ carreras }) {
    return (
        <AppLayout>
            <div className="mb-6 flex flex-wrap items-start justify-between gap-4">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">Carreras</h1>
                    <p className="mt-1 text-sm text-gray-500">Catálogo de carreras de la universidad.</p>
                </div>
                <Link
                    href={route('carreras.create')}
                    className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                >
                    <Icono tipo="mas" className="h-4 w-4" />
                    Nueva carrera
                </Link>
            </div>

            <DataTable>
                <thead className="bg-gray-50/80">
                    <tr>
                        {['Carrera', 'Materias', 'Acciones'].map((encabezado) => (
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
                    {carreras.length === 0 && (
                        <tr>
                            <td colSpan={3}>
                                <EmptyState titulo="Sin carreras" subtitulo="Todavía no hay carreras registradas." />
                            </td>
                        </tr>
                    )}
                    {carreras.map((carrera, indice) => (
                        <tr
                            key={carrera.id}
                            className="fila-entra transition-colors hover:bg-gray-50/60"
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
                            <td className="px-4 py-3.5 text-gray-600 tabular-nums">{carrera.materias_count}</td>
                            <FilaAcciones editRoute={route('carreras.edit', carrera.id)} deleteRoute={route('carreras.destroy', carrera.id)} />
                        </tr>
                    ))}
                </tbody>
            </DataTable>
        </AppLayout>
    );
}
