import { Link, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';

export default function Create({ docentes, materias, grupos, gestiones, periodos }) {
    const { data, setData, post, processing, errors } = useForm({
        Id_docente: '',
        Id_materia: '',
        Id_grupo: '',
        Id_gestion: '',
        Id_periodo: '',
        estado: 'propuesta',
    });

    function submit(e) {
        e.preventDefault();
        post(route('designaciones.store'));
    }

    return (
        <AppLayout title="Nueva designación">
            <form onSubmit={submit} className="max-w-3xl rounded-lg border border-gray-200 bg-white p-6 shadow-sm">
                <FormFields
                    data={data}
                    setData={setData}
                    errors={errors}
                    docentes={docentes}
                    materias={materias}
                    grupos={grupos}
                    gestiones={gestiones}
                    periodos={periodos}
                />

                <div className="mt-6 flex gap-3">
                    <button
                        type="submit"
                        disabled={processing}
                        className="rounded-md bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                    >
                        Guardar
                    </button>
                    <Link
                        href={route('designaciones.index')}
                        className="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        Cancelar
                    </Link>
                </div>
            </form>
        </AppLayout>
    );
}
