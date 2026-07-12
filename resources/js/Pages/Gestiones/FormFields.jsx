import Field, { inputClass } from '../../Components/Field';

export default function FormFields({ data, setData, errors }) {
    return (
        <Field label="Nombre" error={errors.nombre}>
            <input
                type="text"
                className={inputClass}
                value={data.nombre}
                onChange={(e) => setData('nombre', e.target.value)}
                placeholder="Ej. 2026"
            />
        </Field>
    );
}
