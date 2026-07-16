import { useMemo } from 'react'
import Field from '../../Components/Field'
import Select from '../../Components/Select'
import ComboboxDocente from '../../Components/ComboboxDocente'

export default function FormFields({
    data,
    setData,
    errors,
    docentes,
    materias,
    grupos,
    gestiones,
    periodos,
    carreras = [],
    showEstado = false,
    cascading = false,
}) {
    // --- Cascading filters (Create mode) ---
    const materiasFiltradas = useMemo(() => {
        if (!cascading || !data.Id_carrera) return materias
        return materias.filter(m => String(m.carrera_id) === String(data.Id_carrera))
    }, [cascading, data.Id_carrera, materias])

    const gruposFiltrados = useMemo(() => {
        if (!cascading || !data.Id_materia) return grupos.filter(g => g.estado === 'habilitado')
        return grupos.filter(g =>
            g.estado === 'habilitado' && String(g.materia_id) === String(data.Id_materia)
        )
    }, [cascading, data.Id_materia, grupos])

    const docentesOrdenados = useMemo(() => {
        if (!cascading || !data.Id_materia) return docentes
        const materiaId = String(data.Id_materia)
        const carreraId = data.Id_carrera ? String(data.Id_carrera) : null

        const historial = []
        const mismaCarrera = []
        const resto = []

        for (const docente of docentes) {
            const tieneHistorial = (docente.historial_materias || []).some(id => String(id) === materiaId)
            if (tieneHistorial) {
                historial.push(docente)
            } else if (carreraId && String(docente.carrera_origen_id) === carreraId) {
                mismaCarrera.push(docente)
            } else {
                resto.push(docente)
            }
        }

        return [...historial, ...mismaCarrera, ...resto]
    }, [cascading, data.Id_materia, data.Id_carrera, docentes])

    // --- Reset cascading selections ---
    function handleCarreraChange(carreraId) {
        setData('Id_carrera', carreraId)
        setData('Id_materia', '')
        setData('Id_grupo', '')
        setData('Id_docente', '')
    }

    function handleMateriaChange(materiaId) {
        setData('Id_materia', materiaId)
        setData('Id_grupo', '')
        setData('Id_docente', '')
    }

    // --- Flat layout (Edit mode) ---
    if (!cascading) {
        return (
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field label="Docente" error={errors.Id_docente}>
                    <ComboboxDocente
                        docentes={docentes}
                        value={data.Id_docente}
                        onChange={(valor) => setData('Id_docente', valor)}
                        placeholder="Seleccione un docente..."
                        className="w-full"
                    />
                </Field>

                <Field label="Materia" error={errors.Id_materia}>
                    <Select value={data.Id_materia} onChange={(e) => setData('Id_materia', e.target.value)}>
                        <option value="">Seleccione...</option>
                        {materias.map((materia) => (
                            <option key={materia.id} value={materia.id}>
                                {materia.sigla} — {materia.nombre} ({materia.horas}h)
                            </option>
                        ))}
                    </Select>
                </Field>

                <Field label="Grupo" error={errors.Id_grupo}>
                    <Select value={data.Id_grupo} onChange={(e) => setData('Id_grupo', e.target.value)}>
                        <option value="">Seleccione...</option>
                        {grupos.filter(g => g.estado === 'habilitado').map((grupo) => (
                            <option key={grupo.id} value={grupo.id}>
                                {grupo.materia.sigla} — Grupo {grupo.codigo}
                            </option>
                        ))}
                    </Select>
                </Field>

                <Field label="Gestión" error={errors.Id_gestion}>
                    <Select value={data.Id_gestion} onChange={(e) => setData('Id_gestion', e.target.value)}>
                        <option value="">Seleccione...</option>
                        {gestiones.map((gestion) => (
                            <option key={gestion.id} value={gestion.id}>
                                {gestion.nombre}
                            </option>
                        ))}
                    </Select>
                </Field>

                <Field label="Periodo" error={errors.Id_periodo}>
                    <Select value={data.Id_periodo} onChange={(e) => setData('Id_periodo', e.target.value)}>
                        <option value="">Seleccione...</option>
                        {periodos.map((periodo) => (
                            <option key={periodo.id} value={periodo.id}>
                                {periodo.nombre}
                            </option>
                        ))}
                    </Select>
                </Field>

                {showEstado && (
                    <Field label="Estado" error={errors.estado}>
                        <Select value={data.estado} onChange={(e) => setData('estado', e.target.value)}>
                            {['propuesta', 'aprobada', 'rechazada'].map((estado) => (
                                <option key={estado} value={estado}>
                                    {estado.charAt(0).toUpperCase() + estado.slice(1)}
                                </option>
                            ))}
                        </Select>
                    </Field>
                )}

                <div className="sm:col-span-2">
                    <div className="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                        Las designaciones se crean como <strong>propuesta</strong> y deben ser aprobadas por un superior.
                    </div>
                </div>
            </div>
        )
    }

    // --- Cascading layout (Create mode) ---
    return (
        <div className="space-y-4">
            {/* Carrera */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field label="Carrera" error={errors.Id_carrera}>
                    <Select value={data.Id_carrera} onChange={(e) => handleCarreraChange(e.target.value)}>
                        <option value="">Todas las carreras</option>
                        {carreras.map((carrera) => (
                            <option key={carrera.id} value={carrera.id}>
                                {carrera.sigla} — {carrera.nombre}
                            </option>
                        ))}
                    </Select>
                </Field>
            </div>

            {/* Materia */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field label="Materia" error={errors.Id_materia}>
                    <Select value={data.Id_materia} onChange={(e) => handleMateriaChange(e.target.value)}>
                        <option value="">Seleccione una materia...</option>
                        {materiasFiltradas.map((materia) => (
                            <option key={materia.id} value={materia.id}>
                                {materia.sigla} — {materia.nombre} ({materia.horas}h)
                            </option>
                        ))}
                    </Select>
                </Field>
            </div>

            {/* Grupo */}
            {data.Id_materia && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field label="Grupo" error={errors.Id_grupo}>
                        <Select value={data.Id_grupo} onChange={(e) => setData('Id_grupo', e.target.value)}>
                            <option value="">Seleccione un grupo...</option>
                            {gruposFiltrados.map((grupo) => (
                                <option key={grupo.id} value={grupo.id}>
                                    Grupo {grupo.codigo}
                                </option>
                            ))}
                        </Select>
                    </Field>
                </div>
            )}

            {/* Docente */}
            {data.Id_materia && (
                <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                    <Field label="Docente" error={errors.Id_docente}>
                    <ComboboxDocente
                        docentes={docentesOrdenados}
                        value={data.Id_docente}
                        onChange={(valor) => setData('Id_docente', valor)}
                        carreraSigla={carreras.find(c => String(c.id) === String(data.Id_carrera))?.sigla ?? ''}
                        placeholder="Seleccione un docente..."
                        className="w-full"
                    />
                    </Field>
                </div>
            )}

            {/* Gestión + Periodo */}
            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                <Field label="Gestión" error={errors.Id_gestion}>
                    <Select value={data.Id_gestion} onChange={(e) => setData('Id_gestion', e.target.value)}>
                        <option value="">Seleccione...</option>
                        {gestiones.map((gestion) => (
                            <option key={gestion.id} value={gestion.id}>
                                {gestion.nombre}
                            </option>
                        ))}
                    </Select>
                </Field>

                <Field label="Periodo" error={errors.Id_periodo}>
                    <Select value={data.Id_periodo} onChange={(e) => setData('Id_periodo', e.target.value)}>
                        <option value="">Seleccione...</option>
                        {periodos.map((periodo) => (
                            <option key={periodo.id} value={periodo.id}>
                                {periodo.nombre}
                            </option>
                        ))}
                    </Select>
                </Field>
            </div>

            <div className="rounded-lg border border-blue-200 bg-blue-50 px-3 py-2 text-xs text-blue-700">
                Las designaciones se crean como <strong>propuesta</strong> y deben ser aprobadas por un superior.
            </div>
        </div>
    )
}
