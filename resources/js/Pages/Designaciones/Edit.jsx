import { router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';
import WarningBanner from '../../Components/WarningBanner';
import FormActions from '@/Components/FormActions';
import { useResumenCarga } from '../../Hooks/useResumenCarga';

export default function Edit({ docentes, materias, grupos, gestiones, periodos, designacion, resumenCarga }) {
    const { data, setData, put, processing, errors } = useForm({
        Id_docente: String(designacion.Id_docente),
        Id_materia: String(designacion.Id_materia),
        Id_grupo: String(designacion.Id_grupo),
        Id_gestion: String(designacion.Id_gestion),
        Id_periodo: String(designacion.Id_periodo),
        estado: designacion.estado,
    });

    useResumenCarga(data);

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
                        showEstado
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
