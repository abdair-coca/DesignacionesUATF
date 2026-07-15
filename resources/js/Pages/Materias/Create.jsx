import { useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';
import FormActions from '@/Components/FormActions';

export default function Create({ carreras }) {
    const { data, setData, post, processing, errors } = useForm({
        sigla: '',
        nombre: '',
        carrera_id: '',
        horas: 0,
    });

    function enviar(e) {
        e.preventDefault();
        post(route('materias.store'));
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-xl">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900">Nueva materia</h1>

                <form onSubmit={enviar} className="space-y-6 rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm">
                    <FormFields data={data} setData={setData} errors={errors} carreras={carreras} />

                    <FormActions onCancel={route('materias.index')} processing={processing} label="Guardar materia" />
                </form>
            </div>
        </AppLayout>
    );
}
