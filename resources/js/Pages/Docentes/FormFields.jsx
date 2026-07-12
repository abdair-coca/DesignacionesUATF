import Field, { inputClass } from '../../Components/Field';
import Select from '../../Components/Select';

export default function FormFields({ data, setData, errors, carreras }) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field label="Nombre completo" error={errors.nombre}>
                <input
                    type="text"
                    className={inputClass}
                    value={data.nombre}
                    onChange={(e) => setData('nombre', e.target.value)}
                    placeholder="Ej. Juan Carlos Mamani Quispe"
                />
            </Field>

            <Field label="CI" error={errors.ci}>
                <input
                    type="text"
                    className={inputClass}
                    value={data.ci}
                    onChange={(e) => setData('ci', e.target.value)}
                    placeholder="Ej. 4521367"
                />
            </Field>

            <Field label="Carrera de origen" error={errors.carrera_origen_id}>
                <Select value={data.carrera_origen_id} onChange={(e) => setData('carrera_origen_id', e.target.value)}>
                    <option value="">Sin especificar</option>
                    {carreras.map((carrera) => (
                        <option key={carrera.id} value={carrera.id}>
                            {carrera.sigla} — {carrera.nombre}
                        </option>
                    ))}
                </Select>
            </Field>
        </div>
    );
}
