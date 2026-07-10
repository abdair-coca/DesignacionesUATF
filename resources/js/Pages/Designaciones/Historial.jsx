import { Link } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

export default function Historial({ designacion, historial }) {
    return (
        <AppLayout title="Historial de designación">
            <div className="mb-6 rounded-lg border border-gray-200 bg-white p-4 shadow-sm">
                <h3 className="font-medium">
                    {designacion.docente.nombre} — {designacion.materia.sigla} (Grupo{' '}
                    {designacion.grupo.codigo}, {designacion.gestion.nombre}-{designacion.periodo.nombre})
                </h3>
                <p className="mt-1 text-sm text-gray-500">
                    Estado actual: {designacion.estado.charAt(0).toUpperCase() + designacion.estado.slice(1)}
                </p>
            </div>

            <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-gray-200 text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            {['Fecha', 'Campo', 'Valor anterior', 'Valor nuevo'].map((h) => (
                                <th key={h} className="px-4 py-3 text-left font-medium text-gray-600">
                                    {h}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {historial.length === 0 && (
                            <tr>
                                <td colSpan={4} className="px-4 py-6 text-center text-gray-500">
                                    Sin cambios registrados todavía.
                                </td>
                            </tr>
                        )}
                        {historial.map((registro) => (
                            <tr key={registro.id}>
                                <td className="px-4 py-3">{new Date(registro.fecha).toLocaleString('es-BO')}</td>
                                <td className="px-4 py-3">{registro.campo}</td>
                                <td className="px-4 py-3">{registro.valor_anterior}</td>
                                <td className="px-4 py-3">{registro.valor_nuevo}</td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            <Link
                href={route('designaciones.index')}
                className="mt-4 inline-block rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
            >
                Volver al listado
            </Link>
        </AppLayout>
    );
}
