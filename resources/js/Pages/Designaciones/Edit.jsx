import { useEffect } from 'react';
import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';
import WarningBanner from '../../Components/WarningBanner';

export default function Edit({ docentes, materias, grupos, gestiones, periodos, designacion, resumenCarga }) {
    const { data, setData, put, processing, errors } = useForm({
        Id_docente: String(designacion.Id_docente),
        Id_materia: String(designacion.Id_materia),
        Id_grupo: String(designacion.Id_grupo),
        Id_gestion: String(designacion.Id_gestion),
        Id_periodo: String(designacion.Id_periodo),
        estado: designacion.estado,
    });

    useEffect(() => {
        router.reload({
            only: ['resumenCarga'],
            data: {
                Id_docente: data.Id_docente,
                Id_materia: data.Id_materia,
                Id_grupo: data.Id_grupo,
                Id_gestion: data.Id_gestion,
                Id_periodo: data.Id_periodo,
            },
            preserveState: true,
            preserveScroll: true,
        });
    }, [data.Id_docente, data.Id_materia, data.Id_grupo, data.Id_gestion, data.Id_periodo]);

    function submit(e) {
        e.preventDefault();
        put(route('designaciones.update', designacion.id));
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-3xl">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900">Editar designación</h1>

                <form onSubmit={submit} className="space-y-6 rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm">
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

                    {resumenCarga?.excedeLimite && (
                        <WarningBanner
                            mensaje={`El docente quedaría con ${resumenCarga.horasProyectadas}h asignadas en esta gestión y periodo (supera el límite recomendado de ${resumenCarga.limite}h).`}
                        />
                    )}
                    {resumenCarga?.hayChoque && (
                        <WarningBanner mensaje="Este grupo ya tiene otra designación activa en esta gestión y periodo." />
                    )}

                    <div className="flex justify-end gap-3">
                        <Link
                            href={route('designaciones.index')}
                            className="rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50"
                        >
                            Cancelar
                        </Link>
                        <button
                            type="submit"
                            disabled={processing}
                            className="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40"
                        >
                            Guardar
                        </button>
                    </div>
                </form>
            </div>
        </AppLayout>
    );
}
