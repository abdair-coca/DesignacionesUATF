import Field, { inputClass } from '../../Components/Field';
import Select from '../../Components/Select';

export default function FormFields({ data, setData, errors, materias }) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field label="Materia" error={errors.materia_id}>
                <Select value={data.materia_id} onChange={(e) => setData('materia_id', e.target.value)}>
                    <option value="">Seleccione...</option>
                    {materias.map((materia) => (
                        <option key={materia.id} value={materia.id}>
                            {materia.sigla} — {materia.nombre}
                        </option>
                    ))}
                </Select>
            </Field>

            <Field label="Código" error={errors.codigo}>
                <input
                    type="text"
                    className={inputClass}
                    value={data.codigo}
                    onChange={(e) => setData('codigo', e.target.value)}
                    placeholder="Ej. A"
                />
            </Field>

            <Field label="Estado" error={errors.estado}>
                <Select value={data.estado} onChange={(e) => setData('estado', e.target.value)}>
                    <option value="habilitado">Habilitado</option>
                    <option value="deshabilitado">Deshabilitado</option>
                </Select>
            </Field>
        </div>
    );
}
