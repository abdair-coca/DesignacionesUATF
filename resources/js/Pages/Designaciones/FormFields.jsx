import Field from '../../Components/Field';
import Select from '../../Components/Select';

export default function FormFields({ data, setData, errors, docentes, materias, grupos, gestiones, periodos }) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field label="Docente" error={errors.Id_docente}>
                <Select value={data.Id_docente} onChange={(e) => setData('Id_docente', e.target.value)}>
                    <option value="">Seleccione...</option>
                    {docentes.map((docente) => (
                        <option key={docente.id} value={docente.id}>
                            {docente.ci} — {docente.nombre}
                        </option>
                    ))}
                </Select>
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
                    {grupos.map((grupo) => (
                        <option key={grupo.id} value={grupo.id}>
                            {grupo.materia.sigla} — Grupo {grupo.codigo} ({grupo.estado})
                        </option>
                    ))}
                </Select>
            </Field>

            <Field label="Estado" error={errors.estado}>
                <Select value={data.estado} onChange={(e) => setData('estado', e.target.value)}>
                    {['propuesta', 'aprobada', 'rechazada'].map((estado) => (
                        <option key={estado} value={estado}>
                            {estado.charAt(0).toUpperCase() + estado.slice(1)}
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
        </div>
    );
}
