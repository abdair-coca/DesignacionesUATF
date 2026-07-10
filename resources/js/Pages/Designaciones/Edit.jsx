import { Link, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';

export default function Edit({ docentes, materias, grupos, gestiones, periodos, designacion }) {
    const { data, setData, put, processing, errors } = useForm({
        Id_docente: String(designacion.Id_docente),
        Id_materia: String(designacion.Id_materia),
        Id_grupo: String(designacion.Id_grupo),
        Id_gestion: String(designacion.Id_gestion),
        Id_periodo: String(designacion.Id_periodo),
        estado: designacion.estado,
    });

    function submit(e) {
        e.preventDefault();
        put(route('designaciones.update', designacion.id));
    }

    return (
        <AppLayout title="Editar designación">
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
