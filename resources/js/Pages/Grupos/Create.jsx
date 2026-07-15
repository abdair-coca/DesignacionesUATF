import { useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';
import FormActions from '@/Components/FormActions';

export default function Create({ materias }) {
    const { data, setData, post, processing, errors } = useForm({
        materia_id: '',
        codigo: '',
        estado: 'habilitado',
    });

    function enviar(e) {
        e.preventDefault();
        post(route('grupos.store'));
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-xl">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900">Nuevo grupo</h1>

                <form onSubmit={enviar} className="space-y-6 rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm">
                    <FormFields data={data} setData={setData} errors={errors} materias={materias} />

                    <FormActions onCancel={route('grupos.index')} processing={processing} label="Guardar grupo" />
                </form>
            </div>
        </AppLayout>
    );
}
