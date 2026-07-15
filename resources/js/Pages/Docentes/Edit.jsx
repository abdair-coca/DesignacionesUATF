import { useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';
import FormActions from '@/Components/FormActions';

export default function Edit({ docente, carreras }) {
    const { data, setData, put, processing, errors } = useForm({
        nombre: docente.nombre,
        ci: docente.ci,
        carrera_origen_id: docente.carrera_origen_id ?? '',
    });

    function enviar(e) {
        e.preventDefault();
        put(route('docentes.update', docente.id));
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-xl">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900">Editar docente</h1>

                <form onSubmit={enviar} className="space-y-6 rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm">
                    <FormFields data={data} setData={setData} errors={errors} carreras={carreras} />

                    <FormActions onCancel={route('docentes.index')} processing={processing} label="Guardar cambios" />
                </form>
            </div>
        </AppLayout>
    );
}
