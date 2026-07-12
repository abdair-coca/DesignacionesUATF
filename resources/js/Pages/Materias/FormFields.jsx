import Field, { inputClass } from '../../Components/Field';
import Select from '../../Components/Select';

export default function FormFields({ data, setData, errors, carreras }) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field label="Sigla" error={errors.sigla}>
                <input
                    type="text"
                    className={inputClass}
                    value={data.sigla}
                    onChange={(e) => setData('sigla', e.target.value)}
                    placeholder="Ej. INF-101"
                />
            </Field>

            <Field label="Nombre" error={errors.nombre}>
                <input
                    type="text"
                    className={inputClass}
                    value={data.nombre}
                    onChange={(e) => setData('nombre', e.target.value)}
                    placeholder="Ej. Introducción a la Programación"
                />
            </Field>

            <Field label="Carrera" error={errors.carrera_id}>
                <Select value={data.carrera_id} onChange={(e) => setData('carrera_id', e.target.value)}>
                    <option value="">Seleccione...</option>
                    {carreras.map((carrera) => (
                        <option key={carrera.id} value={carrera.id}>
                            {carrera.sigla} — {carrera.nombre}
                        </option>
                    ))}
                </Select>
            </Field>

            <Field label="Horas" error={errors.horas}>
                <input
                    type="number"
                    min="0"
                    max="40"
                    className={inputClass}
                    value={data.horas}
                    onChange={(e) => setData('horas', e.target.value)}
                />
            </Field>
        </div>
    );
}
