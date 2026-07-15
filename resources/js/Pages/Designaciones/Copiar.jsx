import { useEffect } from 'react';
import { router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import Select from '@/Components/Select';

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
            <div className="mb-6 grid grid-cols-1 gap-4 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm sm:grid-cols-5">
                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">Carrera</label>
                    <Select
                        value={filtros.carrera_id ?? ''}
                        onChange={(e) => actualizarFiltro('carrera_id', e.target.value)}
                    >
                        <option value="">Seleccione...</option>
                        {carreras.map((carrera) => (
                            <option key={carrera.id} value={carrera.id}>
                                {carrera.sigla}
                            </option>
                        ))}
                    </Select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">Gestión origen</label>
                    <Select
                        value={filtros.gestion_origen_id ?? ''}
                        onChange={(e) => actualizarFiltro('gestion_origen_id', e.target.value)}
                    >
                        <option value="">Seleccione...</option>
                        {gestiones.map((gestion) => (
                            <option key={gestion.id} value={gestion.id}>
                                {gestion.nombre}
                            </option>
                        ))}
                    </Select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">Periodo origen</label>
                    <Select
                        value={filtros.periodo_origen_id ?? ''}
                        onChange={(e) => actualizarFiltro('periodo_origen_id', e.target.value)}
                    >
                        <option value="">Seleccione...</option>
                        {periodos.map((periodo) => (
                            <option key={periodo.id} value={periodo.id}>
                                {periodo.nombre}
                            </option>
                        ))}
                    </Select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">Gestión destino</label>
                    <Select
                        value={filtros.gestion_destino_id ?? ''}
                        onChange={(e) => actualizarFiltro('gestion_destino_id', e.target.value)}
                    >
                        <option value="">Seleccione...</option>
                        {gestiones.map((gestion) => (
                            <option key={gestion.id} value={gestion.id}>
                                {gestion.nombre}
                            </option>
                        ))}
                    </Select>
                </div>

                <div>
                    <label className="block text-sm font-medium text-gray-700 mb-1.5">Periodo destino</label>
                    <Select
                        value={filtros.periodo_destino_id ?? ''}
                        onChange={(e) => actualizarFiltro('periodo_destino_id', e.target.value)}
                    >
                        <option value="">Seleccione...</option>
                        {periodos.map((periodo) => (
                            <option key={periodo.id} value={periodo.id}>
                                {periodo.nombre}
                            </option>
                        ))}
                    </Select>
                </div>
            </div>

            {!seleccionCompleta && (
                <p className="text-gray-500 text-sm">
                    Elegí carrera, gestión/periodo de origen y gestión/periodo de destino para ver qué se puede copiar.
                </p>
            )}

            {seleccionCompleta && filasOrigen !== null && filasOrigen.length === 0 && (
                <p className="text-gray-500 text-sm">
                    No hay designaciones de origen para copiar (o todos los grupos ya tienen designación en el destino).
                </p>
            )}

            {seleccionCompleta && filasOrigen !== null && data.filas.length > 0 && (
                <form onSubmit={submit} className="space-y-6">
                    <div className="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
                        {data.filas.map((fila, index) => {
                            const seleccionado = fila.incluir;
                            return (
                                <div
                                    key={fila.Id_grupo}
                                    onClick={() => actualizarFila(index, { incluir: !seleccionado })}
                                    className={`relative flex flex-col justify-between rounded-xl border p-4 shadow-sm cursor-pointer transition-all hover:border-blue-500/30 hover:bg-blue-50/10 ${
                                        seleccionado ? 'border-blue-500 bg-blue-50/5 ring-1 ring-blue-500' : 'border-gray-200/80 bg-white'
                                    }`}
                                >
                                    <div className="flex items-start justify-between gap-3">
                                        <div>
                                            <p className="font-semibold text-gray-900 text-sm leading-tight">{fila._materiaLabel}</p>
                                            <p className="mt-1 text-xs text-gray-400">Grupo {fila._grupoLabel}</p>
                                        </div>
                                        <input
                                            type="checkbox"
                                            checked={seleccionado}
                                            onChange={(e) => {
                                                e.stopPropagation();
                                                actualizarFila(index, { incluir: e.target.checked });
                                            }}
                                            className="h-5 w-5 rounded-full border-gray-300 text-blue-900 focus:ring-blue-500/20 cursor-pointer"
                                        />
                                    </div>

                                    <div className="mt-3 border-t border-gray-100 pt-3">
                                        <label className="block text-[11px] font-medium uppercase tracking-wide text-gray-400">Docente original</label>
                                        <p className="mt-0.5 text-sm font-medium text-gray-700">{fila._docenteOriginal}</p>
                                    </div>

                                    <div className="mt-4" onClick={(e) => e.stopPropagation()}>
                                        <label className="block text-[11px] font-medium uppercase tracking-wide text-gray-400 mb-1">Docente a asignar</label>
                                        <select
                                            className="w-full rounded-lg border border-gray-200 px-3 py-2 text-sm shadow-sm transition-colors hover:border-gray-300 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20 disabled:cursor-not-allowed disabled:opacity-40"
                                            value={fila.Id_docente}
                                            disabled={!seleccionado}
                                            onChange={(e) => actualizarFila(index, { Id_docente: e.target.value })}
                                        >
                                            {docentes.map((docente) => (
                                                <option key={docente.id} value={docente.id}>
                                                    {docente.ci} — {docente.nombre}
                                                </option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            );
                        })}
                    </div>

                    {errors.filas && <p className="text-sm text-red-600">{errors.filas}</p>}

                    <button
                        type="submit"
                        disabled={processing}
                        className="inline-flex items-center justify-center gap-2 rounded-lg bg-blue-900 px-5 py-2.5 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50"
                    >
                        Copiar designaciones seleccionadas
                    </button>
                </form>
            )}
        </AppLayout>
    );
}
