import { router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';
import WarningBanner from '../../Components/WarningBanner';
import FormActions from '@/Components/FormActions';
import { useResumenCarga } from '../../Hooks/useResumenCarga';

export default function Create({ docentes, materias, grupos, gestiones, periodos, gestionActual, periodoActual, prefill, resumenCarga }) {
    const { data, setData, post, processing, errors } = useForm({
        Id_docente: prefill?.Id_docente ?? '',
        Id_materia: prefill?.Id_materia ?? '',
        Id_grupo: prefill?.Id_grupo ?? '',
        Id_gestion: prefill?.Id_gestion ?? String(gestionActual),
        Id_periodo: prefill?.Id_periodo ?? String(periodoActual),
    });

    useResumenCarga(data);

    function submit(e) {
        e.preventDefault();
        post(route('designaciones.store'));
    }

    return (
        <AppLayout>
            <div className="mx-auto max-w-3xl">
                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900">Nueva designación</h1>

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

                    <FormActions onCancel={route('designaciones.index')} processing={processing} label="Guardar" />
                </form>
            </div>
        </AppLayout>
    );
}
