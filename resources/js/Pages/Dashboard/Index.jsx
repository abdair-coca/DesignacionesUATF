import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import StatTile from '../../Components/StatTile';
import FilterBar from '../../Components/FilterBar';
import EmptyState from '../../Components/EmptyState';

export default function Index({ gestiones, periodos, filtros, gruposSinDesignar, conteoEstado, docentesBajoLimite, limiteHoras }) {
    function aplicarFiltros(cambios) {
        router.get(
            route('dashboard'),
            { gestion_id: filtros.gestion_id, periodo_id: filtros.periodo_id, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true, only: ['gruposSinDesignar', 'conteoEstado', 'docentesBajoLimite', 'filtros'] }
        );
    }

    function usarValoresPorDefecto() {
        aplicarFiltros({ gestion_id: '', periodo_id: '' });
    }

    const campos = [
        {
            key: 'gestion_id',
            label: 'Gestión',
            valor: filtros.gestion_id,
            todos: 'Más reciente',
            opciones: gestiones.map((g) => ({ value: g.id, label: g.nombre })),
        },
        {
            key: 'periodo_id',
            label: 'Periodo',
            valor: filtros.periodo_id,
            todos: 'Primero disponible',
            opciones: periodos.map((p) => ({ value: p.id, label: p.nombre })),
        },
    ];

    const totalDesignaciones = conteoEstado.propuesta + conteoEstado.aprobada + conteoEstado.rechazada;

    return (
        <AppLayout>
            <div className="mb-6">
                <h1 className="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                <p className="mt-1 text-sm text-gray-500">
                    Estado de designaciones para la gestión y periodo seleccionados.
                </p>
            </div>

            <FilterBar
                campos={campos}
                onChange={(key, valor) => aplicarFiltros({ [key]: valor })}
                onLimpiar={usarValoresPorDefecto}
                hayFiltrosActivos
            />

            <div className="mb-6 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-5">
                <StatTile
                    tipo="capas"
                    tono="rojo"
                    titulo="Grupos sin cubrir"
                    valor={gruposSinDesignar.length}
                    subtitulo="habilitados, sin designación"
                />
                <StatTile tipo="reloj" tono="ambar" titulo="Propuestas" valor={conteoEstado.propuesta} subtitulo={`de ${totalDesignaciones} designaciones`} />
                <StatTile tipo="check" tono="verde" titulo="Aprobadas" valor={conteoEstado.aprobada} subtitulo={`de ${totalDesignaciones} designaciones`} />
                <StatTile tipo="equis" tono="rojo" titulo="Rechazadas" valor={conteoEstado.rechazada} subtitulo={`de ${totalDesignaciones} designaciones`} />
                <StatTile
                    tipo="alerta"
                    tono="ambar"
                    titulo={`Docentes bajo ${limiteHoras}h`}
                    valor={docentesBajoLimite.length}
                    subtitulo="carga académica incompleta"
                />
            </div>

            <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
                <div className="overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm">
                    <div className="border-b border-gray-100 px-4 py-3">
                        <h3 className="font-semibold tracking-tight text-gray-900">Grupos sin cubrir</h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50/80">
                                <tr>
                                    {['Materia', 'Carrera', 'Grupo', ''].map((encabezado) => (
                                        <th
                                            key={encabezado}
                                            className="whitespace-nowrap px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400"
                                        >
                                            {encabezado}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {gruposSinDesignar.length === 0 && (
                                    <tr>
                                        <td colSpan={4}>
                                            <EmptyState
                                                icono="check"
                                                titulo="Todo cubierto"
                                                subtitulo="No hay grupos habilitados sin designación en este periodo."
                                            />
                                        </td>
                                    </tr>
                                )}
                                {gruposSinDesignar.map((grupo) => (
                                    <tr key={grupo.id} className="transition-colors hover:bg-gray-50/60">
                                        <td className="px-4 py-3">
                                            <p className="font-medium text-gray-900">{grupo.materia.nombre}</p>
                                            <p className="text-xs text-gray-400">{grupo.materia.sigla}</p>
                                        </td>
                                        <td className="px-4 py-3 text-gray-600">{grupo.carrera.sigla}</td>
                                        <td className="px-4 py-3 text-gray-600">{grupo.codigo}</td>
                                        <td className="px-4 py-3 text-right">
                                            <Link
                                                href={route('designaciones.create', {
                                                    Id_materia: grupo.materia.id,
                                                    Id_grupo: grupo.id,
                                                    Id_gestion: filtros.gestion_id,
                                                    Id_periodo: filtros.periodo_id,
                                                })}
                                                className="rounded-lg bg-blue-900 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                                            >
                                                Designar
                                            </Link>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>

                <div className="overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm">
                    <div className="border-b border-gray-100 px-4 py-3">
                        <h3 className="font-semibold tracking-tight text-gray-900">Docentes por debajo de {limiteHoras}h</h3>
                    </div>
                    <div className="overflow-x-auto">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50/80">
                                <tr>
                                    {['Docente', 'Horas asignadas'].map((encabezado) => (
                                        <th
                                            key={encabezado}
                                            className="whitespace-nowrap px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400"
                                        >
                                            {encabezado}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {docentesBajoLimite.length === 0 && (
                                    <tr>
                                        <td colSpan={2}>
                                            <EmptyState
                                                icono="check"
                                                titulo="Todo cubierto"
                                                subtitulo={`Ningún docente está por debajo de ${limiteHoras}h en este periodo.`}
                                            />
                                        </td>
                                    </tr>
                                )}
                                {docentesBajoLimite.map((docente) => (
                                    <tr key={docente.id} className="transition-colors hover:bg-gray-50/60">
                                        <td className="px-4 py-3 font-medium text-gray-900">{docente.nombre}</td>
                                        <td className="px-4 py-3 tabular-nums">
                                            <span className={docente.horas === 0 ? 'text-gray-400' : 'text-amber-700'}>
                                                {docente.horas}h
                                            </span>{' '}
                                            <span className="text-xs text-gray-400">de {limiteHoras}h</span>
                                        </td>
                                    </tr>
                                ))}
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
