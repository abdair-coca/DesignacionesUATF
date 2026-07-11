import { useEffect } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';

const selectClass =
    'w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500';

export default function Copiar({ carreras, gestiones, periodos, docentes, filtros, filas: filasOrigen }) {
    const { data, setData, post, processing, errors } = useForm({
        Id_gestion: '',
        Id_periodo: '',
        carrera_id: '',
        gestion_origen_id: '',
        periodo_origen_id: '',
        filas: [],
    });

    useEffect(() => {
        setData({
            Id_gestion: filtros.gestion_destino_id ?? '',
            Id_periodo: filtros.periodo_destino_id ?? '',
            carrera_id: filtros.carrera_id ?? '',
            gestion_origen_id: filtros.gestion_origen_id ?? '',
            periodo_origen_id: filtros.periodo_origen_id ?? '',
            filas: (filasOrigen ?? []).map((designacion) => ({
                Id_materia: designacion.materia.id,
                Id_grupo: designacion.grupo.id,
                Id_docente: designacion.docente.id,
                incluir: true,
                _materiaLabel: `${designacion.materia.sigla} — ${designacion.materia.nombre}`,
                _grupoLabel: designacion.grupo.codigo,
                _docenteOriginal: designacion.docente.nombre,
            })),
        });
        // eslint-disable-next-line react-hooks/exhaustive-deps
    }, [filasOrigen]);

    function actualizarFiltro(campo, valor) {
        router.get(route('designaciones.copiar'), { ...filtros, [campo]: valor }, { preserveState: true, replace: true });
    }

    function actualizarFila(index, cambios) {
        const nuevasFilas = [...data.filas];
        nuevasFilas[index] = { ...nuevasFilas[index], ...cambios };
        setData('filas', nuevasFilas);
    }

    function submit(e) {
        e.preventDefault();
        post(route('designaciones.copiar.store'));
    }

    const seleccionCompleta =
        filtros.carrera_id && filtros.gestion_origen_id && filtros.periodo_origen_id && filtros.gestion_destino_id && filtros.periodo_destino_id;

    return (
        <AppLayout title="Copiar designaciones de otra gestión">
            <div className="mb-6 grid grid-cols-1 gap-4 rounded-lg border border-gray-200 bg-white p-4 shadow-sm sm:grid-cols-5">
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
                                {carrera.sigla}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Gestión origen</label>
                    <select
                        className={selectClass}
                        value={filtros.gestion_origen_id ?? ''}
                        onChange={(e) => actualizarFiltro('gestion_origen_id', e.target.value)}
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
                    <label className="block text-sm font-medium text-gray-700 mb-1">Periodo origen</label>
                    <select
                        className={selectClass}
                        value={filtros.periodo_origen_id ?? ''}
                        onChange={(e) => actualizarFiltro('periodo_origen_id', e.target.value)}
                    >
                        <option value="">Seleccione...</option>
                        {periodos.map((periodo) => (
                            <option key={periodo.id} value={periodo.id}>
                                {periodo.nombre}
                            </option>
                        ))}
                    </select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1">Gestión destino</label>
                    <select
                        className={selectClass}
                        value={filtros.gestion_destino_id ?? ''}
                        onChange={(e) => actualizarFiltro('gestion_destino_id', e.target.value)}
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
                    <label className="block text-sm font-medium text-gray-700 mb-1">Periodo destino</label>
                    <select
                        className={selectClass}
                        value={filtros.periodo_destino_id ?? ''}
                        onChange={(e) => actualizarFiltro('periodo_destino_id', e.target.value)}
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

            {!seleccionCompleta && (
                <p className="text-gray-500">
                    Elegí carrera, gestión/periodo de origen y gestión/periodo de destino para ver qué se puede copiar.
                </p>
            )}

            {seleccionCompleta && filasOrigen !== null && filasOrigen.length === 0 && (
                <p className="text-gray-500">
                    No hay designaciones de origen para copiar (o todos los grupos ya tienen designación en el destino).
                </p>
            )}

            {seleccionCompleta && filasOrigen !== null && filasOrigen.length > 0 && (
                <form onSubmit={submit}>
                    <div className="overflow-x-auto rounded-lg border border-gray-200 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50">
                                <tr>
                                    {['', 'Materia', 'Grupo', 'Docente original', 'Docente a asignar'].map((h) => (
                                        <th key={h} className="px-4 py-3 text-left font-medium text-gray-600">
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {data.filas.map((fila, index) => (
                                    <tr key={fila.Id_grupo} className={fila.incluir ? '' : 'opacity-50'}>
                                        <td className="px-4 py-3">
                                            <input
                                                type="checkbox"
                                                checked={fila.incluir}
                                                onChange={(e) => actualizarFila(index, { incluir: e.target.checked })}
                                            />
                                        </td>
                                        <td className="px-4 py-3">{fila._materiaLabel}</td>
                                        <td className="px-4 py-3">{fila._grupoLabel}</td>
                                        <td className="px-4 py-3 text-gray-500">{fila._docenteOriginal}</td>
                                        <td className="px-4 py-3">
                                            <select
                                                className={selectClass}
                                                value={fila.Id_docente}
                                                disabled={!fila.incluir}
                                                onChange={(e) => actualizarFila(index, { Id_docente: e.target.value })}
                                            >
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
                        Copiar designaciones seleccionadas
                    </button>
                </form>
            )}
        </AppLayout>
    );
}
