import { useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';
import FormActions from '@/Components/FormActions';

export default function Edit({ gestion }) {
    const { data, setData, put, processing, errors } = useForm({ nombre: gestion.nombre });

    function enviar(e) {
        e.preventDefault();
        put(route('gestiones.update', gestion.id));
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-md">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900">Editar gestión</h1>

                <form onSubmit={enviar} className="space-y-6 rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm">
                    <FormFields data={data} setData={setData} errors={errors} />

                    <FormActions onCancel={route('gestiones.index')} processing={processing} label="Guardar cambios" />
                </form>
            </div>
        </AppLayout>
    );
}
