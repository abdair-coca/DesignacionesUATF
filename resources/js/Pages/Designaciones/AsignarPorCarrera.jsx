import { useEffect } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

const selectClass =
    'w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500';

export default function AsignarPorCarrera({ carreras, gestiones, periodos, docentes, filtros, pendientes }) {
    const { data, setData, post, processing, errors } = useForm({
        Id_gestion: '',
        Id_periodo: '',
        carrera_id: '',
        filas: [],
    });

    useEffect(() => {
        setData({
            Id_gestion: filtros.gestion_id ?? '',
            Id_periodo: filtros.periodo_id ?? '',
            carrera_id: filtros.carrera_id ?? '',
            filas: (pendientes ?? []).map((grupo) => ({
                Id_materia: grupo.materia.id,
                Id_grupo: grupo.id,
                Id_docente: '',
                _materiaLabel: `${grupo.materia.sigla} — ${grupo.materia.nombre}`,
                _grupoLabel: grupo.codigo,
            })),
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [pendientes]);

    function actualizarFiltro(campo, valor) {
        router.get(
            route('designaciones.asignar'),
            { ...filtros, [campo]: valor },
            { preserveState: true, replace: true }
        );
    }

    function actualizarDocente(index, docenteId) {
        const nuevasFilas = [...data.filas];
        nuevasFilas[index] = { ...nuevasFilas[index], Id_docente: docenteId };
        setData('filas', nuevasFilas);
    }

    function submit(e) {
        e.preventDefault();
        post(route('designaciones.asignar.store'));
    }

    return (
        <AppLayout title="Asignar por carrera">
            <div className="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-3">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Carrera</label>
                    <select
                        className={selectClass}
                        value={filtros.carrera_id ?? ''}
                        onChange={(e) => actualizarFiltro('carrera_id', e.target.value)}
                    >
                        <option value="">Seleccione...</option>
                        {carreras.map((carrera) => (
                            <option key={carrera.id} value={carrera.id}>
                                {carrera.sigla} — {carrera.nombre}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Gestión</label>
                    <select
                        className={selectClass}
                        value={filtros.gestion_id ?? ''}
                        onChange={(e) => actualizarFiltro('gestion_id', e.target.value)}
                    >
                        <option value="">Seleccione...</option>
                        {gestiones.map((gestion) => (
                            <option key={gestion.id} value={gestion.id}>
                                {gestion.nombre}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Periodo</label>
                    <select
                        className={selectClass}
                        value={filtros.periodo_id ?? ''}
                        onChange={(e) => actualizarFiltro('periodo_id', e.target.value)}
                    >
                        <option value="">Seleccione...</option>
                        {periodos.map((periodo) => (
                            <option key={periodo.id} value={periodo.id}>
                                {periodo.nombre}
                            </option>
                        ))}
                    </select>
                </div>
            </div>

            {pendientes === null && (
                <p className="text-gray-500">Elegí carrera, gestión y periodo para ver los grupos pendientes.</p>
            )}

            {pendientes !== null && pendientes.length === 0 && (
                <p className="text-gray-500">No quedan grupos sin asignar para esta carrera, gestión y periodo.</p>
            )}

            {pendientes !== null && pendientes.length > 0 && (
                <form onSubmit={submit}>
                    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    {['Materia', 'Grupo', 'Docente'].map((h) => (
                                        <th key={h} className="px-4 py-3 text-left font-medium text-gray-600">
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {data.filas.map((fila, index) => (
                                    <tr key={fila.Id_grupo}>
                                        <td className="px-4 py-3">{fila._materiaLabel}</td>
                                        <td className="px-4 py-3">{fila._grupoLabel}</td>
                                        <td className="px-4 py-3">
                                            <select
                                                className={selectClass}
                                                value={fila.Id_docente}
                                                onChange={(e) => actualizarDocente(index, e.target.value)}
                                            >
                                                <option value="">Sin asignar</option>
                                                {docentes.map((docente) => (
                                                    <option key={docente.id} value={docente.id}>
                                                        {docente.ci} — {docente.nombre}
                                                    </option>
                                                ))}
                                            </select>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>

                    {errors.filas && <p className="mt-2 text-sm text-red-600">{errors.filas}</p>}

                    <button
                        type="submit"
                        disabled={processing}
                        className="mt-4 rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                    >
                        Guardar asignaciones
                    </button>
                </form>
            )}
        </AppLayout>
    );
}
