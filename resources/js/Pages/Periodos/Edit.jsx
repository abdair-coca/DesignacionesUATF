import { useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';

export default function Edit({ periodo }) {
    const { data, setData, put, processing, errors } = useForm({ nombre: periodo.nombre });

    function enviar(e) {
        e.preventDefault();
        put(route('periodos.update', periodo.id));
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-md">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900">Editar periodo</h1>

                <form onSubmit={enviar} className="space-y-6 rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm">
                    <FormFields data={data} setData={setData} errors={errors} />

                    <div className="flex justify-end gap-3">
                        <a
                            href={route('periodos.index')}
                            className="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50"
                        >
                            Cancelar
                        </a>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Guardar cambios
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
