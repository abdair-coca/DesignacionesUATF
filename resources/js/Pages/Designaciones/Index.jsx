import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

const estadoBadge = {
    aprobada: 'bg-green-100 text-green-800',
    rechazada: 'bg-red-100 text-red-800',
    propuesta: 'bg-gray-100 text-gray-800',
};

export default function Index({ designaciones }) {
    function eliminar(designacion) {
        if (!confirm('¿Eliminar esta designación?')) {
            return;
        }
        router.delete(route('designaciones.destroy', designacion.id));
    }

    return (
        <AppLayout title="Designación de Docentes">
            <Link
                href={route('designaciones.create')}
                className="mb-4 inline-block rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700"
            >
                Nueva designación
            </Link>

            <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                <table className="min-w-full divide-y divide-gray-200 text-sm">
                    <thead className="bg-gray-50">
                        <tr>
                            {['Docente', 'Materia', 'Grupo', 'Gestión', 'Periodo', 'Estado', 'Acciones'].map((h) => (
                                <th key={h} className="px-4 py-3 text-left font-medium text-gray-600">
                                    {h}
                                </th>
                            ))}
                        </tr>
                    </thead>
                    <tbody className="divide-y divide-gray-100">
                        {designaciones.data.length === 0 && (
                            <tr>
                                <td colSpan={7} className="px-4 py-6 text-center text-gray-500">
                                    No hay designaciones registradas todavía.
                                </td>
                            </tr>
                        )}
                        {designaciones.data.map((designacion) => (
                            <tr key={designacion.id}>
                                <td className="px-4 py-3">{designacion.docente.nombre}</td>
                                <td className="px-4 py-3">
                                    {designacion.materia.sigla} — {designacion.materia.nombre}
                                </td>
                                <td className="px-4 py-3">{designacion.grupo.codigo}</td>
                                <td className="px-4 py-3">{designacion.gestion.nombre}</td>
                                <td className="px-4 py-3">{designacion.periodo.nombre}</td>
                                <td className="px-4 py-3">
                                    <span
                                        className={`rounded-full px-2 py-1 text-xs font-medium ${estadoBadge[designacion.estado]}`}
                                    >
                                        {designacion.estado.charAt(0).toUpperCase() + designacion.estado.slice(1)}
                                    </span>
                                </td>
                                <td className="px-4 py-3 space-x-2 whitespace-nowrap">
                                    <Link
                                        href={route('designaciones.edit', designacion.id)}
                                        className="text-sm font-medium text-gray-700 hover:underline"
                                    >
                                        Editar
                                    </Link>
                                    <Link
                                        href={route('designaciones.historial', designacion.id)}
                                        className="text-sm font-medium text-gray-700 hover:underline"
                                    >
                                        Historial
                                    </Link>
                                    <button
                                        onClick={() => eliminar(designacion)}
                                        className="text-sm font-medium text-red-600 hover:underline"
                                    >
                                        Eliminar
                                    </button>
                                </td>
                            </tr>
                        ))}
                    </tbody>
                </table>
            </div>

            {designaciones.links.length > 3 && (
                <div className="mt-4 flex flex-wrap gap-1">
                    {designaciones.links.map((link, i) => (
                        <Link
                            key={i}
                            href={link.url || '#'}
                            dangerouslySetInnerHTML={{ __html: link.label }}
                            className={`rounded-md px-3 py-1 text-sm ${
                                link.active
                                    ? 'bg-gray-900 text-white'
                                    : link.url
                                      ? 'border border-gray-300 text-gray-700 hover:bg-gray-50'
                                      : 'cursor-not-allowed text-gray-400'
                            }`}
                        />
                    ))}
                </div>
            )}
        </AppLayout>
    );
}
