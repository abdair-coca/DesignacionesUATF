import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import StatTile from '../../Components/StatTile';
import FilterBar from '../../Components/FilterBar';
import EmptyState from '../../Components/EmptyState';
import DataTable from '@/Components/DataTable';
import GraficoAnillo from '@/Components/GraficoAnillo';
import { Icono } from '../../Components/Icono';

export default function Index({ gestiones, periodos, filtros, gruposSinDesignar, conteoEstado, docentesBajoLimite, limiteHoras }) {
    const [tabActiva, setTabActiva] = useState('grupos');

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

    const resumenDashboard = {
        total: totalDesignaciones,
        activas: conteoEstado.aprobada,
        pendientes: conteoEstado.propuesta,
        sin: conteoEstado.rechazada
    };

    const leyendaDona = [
        { etiqueta: 'Aprobadas', cantidad: conteoEstado.aprobada, punto: 'bg-green-500' },
        { etiqueta: 'Propuestas', cantidad: conteoEstado.propuesta, punto: 'bg-amber-400' },
        { etiqueta: 'Rechazadas', cantidad: conteoEstado.rechazada, punto: 'bg-red-500' }
    ];

    const accionesRapidas = [
        { etiqueta: 'Nueva designación', tipo: 'mas', href: route('designaciones.create') },
        { etiqueta: 'Copiar designaciones', tipo: 'copiar', href: route('designaciones.copiar') }
    ];

    return (
        <AppLayout>
            <div className="flex flex-col gap-6 xl:flex-row">
                <div className="min-w-0 flex-1">
                    <div className="mb-6">
                        <h1 className="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                        <p className="mt-1 text-sm text-gray-500">
                            Estado de designaciones para la gestión y periodo seleccionados.
                        </p>
                    </div>

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

                    <FilterBar
                        campos={campos}
                        onChange={(key, valor) => aplicarFiltros({ [key]: valor })}
                        onLimpiar={usarValoresPorDefecto}
                        hayFiltrosActivos
                    />

                    <div className="mb-4 border-b border-gray-200">
                        <nav className="-mb-px flex gap-6" aria-label="Tabs">
                            <button
                                onClick={() => setTabActiva('grupos')}
                                className={`border-b-2 pb-3 text-sm font-medium transition-colors ${
                                    tabActiva === 'grupos'
                                        ? 'border-blue-700 text-blue-700'
                                        : 'border-transparent text-gray-500 hover:border-gray-200 hover:text-gray-700'
                                }`}
                            >
                                Grupos sin cubrir ({gruposSinDesignar.length})
                            </button>
                            <button
                                onClick={() => setTabActiva('docentes')}
                                className={`border-b-2 pb-3 text-sm font-medium transition-colors ${
                                    tabActiva === 'docentes'
                                        ? 'border-blue-700 text-blue-700'
                                        : 'border-transparent text-gray-500 hover:border-gray-200 hover:text-gray-700'
                                }`}
                            >
                                Docentes bajo {limiteHoras}h ({docentesBajoLimite.length})
                            </button>
                        </nav>
                    </div>

                    <div className="mt-4">
                        {tabActiva === 'grupos' && (
                            <DataTable>
                                <thead className="bg-gray-50/80">
                                    <tr>
                                        {['Materia', 'Carrera', 'Grupo', 'Acciones'].map((encabezado) => (
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
                                            <td className="px-4 py-3.5">
                                                <p className="font-medium text-gray-900">{grupo.materia.nombre}</p>
                                                <p className="text-xs text-gray-400">{grupo.materia.sigla}</p>
                                            </td>
                                            <td className="px-4 py-3.5 text-gray-600">{grupo.carrera.sigla}</td>
                                            <td className="px-4 py-3.5 text-gray-600">{grupo.codigo}</td>
                                            <td className="px-4 py-3.5 text-right">
                                                <Link
                                                    href={route('designaciones.create', {
                                                        Id_materia: grupo.materia.id,
                                                        Id_grupo: grupo.id,
                                                        Id_gestion: filtros.gestion_id,
                                                        Id_periodo: filtros.periodo_id,
                                                    })}
                                                    className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-3.5 py-1.5 text-xs font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                                                >
                                                    Designar
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </DataTable>
                        )}

                        {tabActiva === 'docentes' && (
                            <DataTable>
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
                                            <td className="px-4 py-3.5 font-medium text-gray-900">{docente.nombre}</td>
                                            <td className="px-4 py-3.5 tabular-nums">
                                                <span className={docente.horas === 0 ? 'text-gray-400' : 'text-amber-700'}>
                                                    {docente.horas}h
                                                </span>{' '}
                                                <span className="text-xs text-gray-400">de {limiteHoras}h</span>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </DataTable>
                        )}
                    </div>
                </div>

                <aside className="w-full shrink-0 space-y-5 xl:w-80">
                    <div className="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm">
                        <h3 className="font-semibold tracking-tight text-gray-900">Resumen de designaciones</h3>
                        <div className="mt-4 flex justify-center">
                            <GraficoAnillo resumen={resumenDashboard} etiqueta="designaciones" />
                        </div>
                        <ul className="mt-5 space-y-1">
                            {leyendaDona.map((fila) => (
                                <li
                                    key={fila.etiqueta}
                                    className="flex items-center justify-between rounded-lg px-2 py-1.5 text-sm transition-colors hover:bg-gray-50"
                                >
                                    <span className="flex items-center gap-2.5 text-gray-600">
                                        <span className={`h-2.5 w-2.5 rounded-full ${fila.punto}`} />
                                        {fila.etiqueta}
                                    </span>
                                    <span className="font-semibold text-gray-900 tabular-nums">
                                        {fila.cantidad}{' '}
                                        <span className="font-normal text-gray-400">
                                            ({resumenDashboard.total ? Math.round((fila.cantidad / resumenDashboard.total) * 1000) / 10 : 0}%)
                                        </span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                        <div className="mt-4 flex items-center justify-between border-t border-gray-100 pt-3.5 text-sm">
                            <span className="text-gray-500">Total designaciones</span>
                            <span className="font-semibold text-gray-900 tabular-nums">{totalDesignaciones}</span>
                        </div>
                    </div>

                    <div className="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm">
                        <h3 className="mb-3 font-semibold tracking-tight text-gray-900">Acciones rápidas</h3>
                        <ul className="space-y-0.5">
                            {accionesRapidas.map((accion) => (
                                <li key={accion.etiqueta}>
                                    <Link
                                        href={accion.href}
                                        className="group flex items-center gap-3 rounded-lg px-3 py-2.5 text-sm text-gray-700 transition-colors hover:bg-gray-50"
                                    >
                                        <span className="flex h-8 w-8 items-center justify-center rounded-lg bg-gray-50 text-gray-400 ring-1 ring-inset ring-gray-200/60 transition-colors group-hover:bg-blue-50 group-hover:text-blue-600 group-hover:ring-blue-200/60">
                                            <Icono tipo={accion.tipo} className="h-4 w-4" />
                                        </span>
                                        <span className="flex-1 font-medium">{accion.etiqueta}</span>
                                        <span className="text-gray-300 transition-all group-hover:translate-x-0.5 group-hover:text-gray-400">
                                            <Icono tipo="chevronDerecha" className="h-3.5 w-3.5" />
                                        </span>
                                    </Link>
                                </li>
                            ))}
                        </ul>
                    </div>
                </aside>
            </div>
        </AppLayout>
    );
}
