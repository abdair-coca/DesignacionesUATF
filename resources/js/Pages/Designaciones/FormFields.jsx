function Field({ label, children, error }) {
    return (
        <div>
            <label className="block text-sm font-medium text-gray-700 mb-1">{label}</label>
            {children}
            {error && <p className="mt-1 text-sm text-red-600">{error}</p>}
        </div>
    );
}

const selectClass =
    'w-full rounded-md border border-gray-300 px-3 py-2 text-sm shadow-sm focus:border-gray-500 focus:outline-none focus:ring-1 focus:ring-gray-500';

export default function FormFields({ data, setData, errors, docentes, materias, grupos, gestiones, periodos }) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field label="Docente" error={errors.Id_docente}>
                <select
                    className={selectClass}
                    value={data.Id_docente}
                    onChange={(e) => setData('Id_docente', e.target.value)}
                >
                    <option value="">Seleccione...</option>
                    {docentes.map((docente) => (
                        <option key={docente.id} value={docente.id}>
                            {docente.ci} — {docente.nombre}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Materia" error={errors.Id_materia}>
                <select
                    className={selectClass}
                    value={data.Id_materia}
                    onChange={(e) => setData('Id_materia', e.target.value)}
                >
                    <option value="">Seleccione...</option>
                    {materias.map((materia) => (
                        <option key={materia.id} value={materia.id}>
                            {materia.sigla} — {materia.nombre}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Grupo" error={errors.Id_grupo}>
                <select
                    className={selectClass}
                    value={data.Id_grupo}
                    onChange={(e) => setData('Id_grupo', e.target.value)}
                >
                    <option value="">Seleccione...</option>
                    {grupos.map((grupo) => (
                        <option key={grupo.id} value={grupo.id}>
                            {grupo.materia.sigla} — Grupo {grupo.codigo} ({grupo.estado})
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Estado" error={errors.estado}>
                <select
                    className={selectClass}
                    value={data.estado}
                    onChange={(e) => setData('estado', e.target.value)}
                >
                    {['propuesta', 'aprobada', 'rechazada'].map((estado) => (
                        <option key={estado} value={estado}>
                            {estado.charAt(0).toUpperCase() + estado.slice(1)}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Gestión" error={errors.Id_gestion}>
                <select
                    className={selectClass}
                    value={data.Id_gestion}
                    onChange={(e) => setData('Id_gestion', e.target.value)}
                >
                    <option value="">Seleccione...</option>
                    {gestiones.map((gestion) => (
                        <option key={gestion.id} value={gestion.id}>
                            {gestion.nombre}
                        </option>
                    ))}
                </select>
            </Field>

            <Field label="Periodo" error={errors.Id_periodo}>
                <select
                    className={selectClass}
                    value={data.Id_periodo}
                    onChange={(e) => setData('Id_periodo', e.target.value)}
                >
                    <option value="">Seleccione...</option>
                    {periodos.map((periodo) => (
                        <option key={periodo.id} value={periodo.id}>
                            {periodo.nombre}
                        </option>
                    ))}
                </select>
            </Field>
        </div>
    );
}
