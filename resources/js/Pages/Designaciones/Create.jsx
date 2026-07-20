import { Link, router, useForm } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import FormFields from './FormFields';
import WarningBanner from '../../Components/WarningBanner';
import FormActions from '@/Components/FormActions';
import { useResumenCarga } from '../../Hooks/useResumenCarga';
import { Icono } from '../../Components/Icono';
import PanelRestricciones from '../../Components/PanelRestricciones';

export default function Create({ carreras, docentes, materias, grupos, gestiones, periodos, gestionActual, periodoActual, prefill, resumenCarga }) {
    const { data, setData, post, processing, errors } = useForm({
        Id_carrera: prefill?.Id_carrera ?? '',
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
                <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                    <nav className="flex items-center gap-1.5 text-sm">
                        <Link href={route('designaciones.index')} className="text-blue-700 transition-colors hover:text-blue-800 hover:underline">
                            Designaciones por carrera
                        </Link>
                        <span className="text-gray-300">›</span>
                        <span className="text-gray-500">Nueva designación</span>
                    </nav>
                    <Link
                        href={route('designaciones.index')}
                        className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50"
                    >
                        <Icono tipo="flechaIzq" className="h-4 w-4" />
                        Volver a carreras
                    </Link>
                </div>

                <h1 className="mb-6 text-2xl font-bold tracking-tight text-gray-900">Nueva designación</h1>

                <form onSubmit={submit} className="space-y-6 rounded-xl border border-gray-200/80 bg-white p-6 shadow-sm">
                    <FormFields
                        data={data}
                        setData={setData}
                        errors={errors}
                        carreras={carreras}
                        docentes={docentes}
                        materias={materias}
                        grupos={grupos}
                        gestiones={gestiones}
                        periodos={periodos}
                        cascading
                    />

                    {data.Id_docente && (
                        <PanelRestricciones resumenCarga={resumenCarga} />
                    )}

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
