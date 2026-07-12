import Field, { inputClass } from '../../Components/Field';

export default function FormFields({ data, setData, errors }) {
    return (
        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
            <Field label="Sigla" error={errors.sigla}>
                <input
                    type="text"
                    className={inputClass}
                    value={data.sigla}
                    onChange={(e) => setData('sigla', e.target.value)}
                    placeholder="Ej. INF"
                />
            </Field>

            <Field label="Nombre" error={errors.nombre}>
                <input
                    type="text"
                    className={inputClass}
                    value={data.nombre}
                    onChange={(e) => setData('nombre', e.target.value)}
                    placeholder="Ej. Ingeniería Informática"
                />
            </Field>
        </div>
    );
}
