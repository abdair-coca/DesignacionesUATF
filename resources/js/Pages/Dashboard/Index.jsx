import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import StatTile from '../../Components/StatTile';
import EmptyState from '../../Components/EmptyState';
import DataTable from '@/Components/DataTable';
import GraficoAnillo from '@/Components/GraficoAnillo';
import { Icono } from '../../Components/Icono';
import Select from '@/Components/Select';

function PaginadorCliente({ paginaActual, totalItems, itemsPorPagina, onPaginaChange }) {
    const totalPaginas = Math.ceil(totalItems / itemsPorPagina);
    if (totalPaginas <= 1) return null;

    const from = (paginaActual - 1) * itemsPorPagina + 1;
    const to = Math.min(paginaActual * itemsPorPagina, totalItems);

    return (
        <div className="mt-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200/80 bg-white px-4 py-3 shadow-sm">
            <p className="text-[13px] text-gray-400 tabular-nums">
                Mostrando <span className="font-medium text-gray-600">{from}</span> a{' '}
                <span className="font-medium text-gray-600">{to}</span> de{' '}
                <span className="font-medium text-gray-600">{totalItems}</span> registros
            </p>
            <div className="flex gap-1">
                <button
                    onClick={() => onPaginaChange(paginaActual - 1)}
                    disabled={paginaActual === 1}
                    className={`flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-sm transition-colors ${
                        paginaActual === 1 ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:border-gray-300 hover:bg-gray-50'
                    }`}
                >
                    ‹
                </button>
                {Array.from({ length: totalPaginas }, (_, i) => i + 1).map((pag) => (
                    <button
                        key={pag}
                        onClick={() => onPaginaChange(pag)}
                        className={`flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm transition-colors tabular-nums ${
                            paginaActual === pag
                                ? 'bg-blue-900 font-medium text-white shadow-sm'
                                : 'border border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'
                        }`}
                    >
                        {pag}
                    </button>
                ))}
                <button
                    onClick={() => onPaginaChange(paginaActual + 1)}
                    disabled={paginaActual === totalPaginas}
                    className={`flex h-8 w-8 items-center justify-center rounded-lg border border-gray-200 text-sm transition-colors ${
                        paginaActual === totalPaginas ? 'cursor-not-allowed text-gray-300' : 'text-gray-600 hover:border-gray-300 hover:bg-gray-50'
                    }`}
                >
                    ›
                </button>
            </div>
        </div>
    );
}

const DBOARD_TONOS = {
    azul: 'bg-blue-50 text-blue-600 ring-blue-200/50',
    verde: 'bg-green-50 text-green-600 ring-green-200/50',
    ambar: 'bg-amber-50 text-amber-600 ring-amber-200/50',
    rojo: 'bg-red-50 text-red-600 ring-red-200/50',
};

function DashboardStatTile({ tipo, tono, titulo, valor, subtitulo }) {
    return (
        <div className="group rounded-xl border border-gray-200/80 bg-white p-3.5 shadow-sm transition-all duration-200 hover:-translate-y-0.5 hover:shadow-md h-full flex flex-col justify-between">
            <div>
                <span
                    className={`flex h-8 w-8 shrink-0 items-center justify-center rounded-lg ring-1 ring-inset transition-transform duration-200 group-hover:scale-105 ${DBOARD_TONOS[tono]}`}
                >
                    <Icono tipo={tipo} className="h-4 w-4" />
                </span>
                <span className="block mt-2.5 text-xs font-semibold text-gray-500 leading-tight" title={titulo}>{titulo}</span>
            </div>
            <div className="mt-3">
                <p className="text-2xl font-extrabold tracking-tight text-gray-900 leading-none tabular-nums">{valor}</p>
                {subtitulo && <p className="mt-1.5 text-[10px] text-gray-400 leading-tight" title={subtitulo}>{subtitulo}</p>}
            </div>
        </div>
    );
}

export default function Index({ gestiones, periodos, filtros, gruposSinDesignar, conteoEstado, docentesBajoLimite, limiteHoras, resumenCarreras, evolucion, is_admin, revisionesPendientes }) {
    const [tabActiva, setTabActiva] = useState('grupos');
    const [paginaGrupos, setPaginaGrupos] = useState(1);
    const [paginaDocentes, setPaginaDocentes] = useState(1);
    const [carreraOrden, setCarreraOrden] = useState('activas-desc');

    const itemsPorPagina = 10;

    function aplicarFiltros(cambios) {
        setPaginaGrupos(1);
        setPaginaDocentes(1);
        router.get(
            route('dashboard'),
            { gestion_id: filtros.gestion_id, periodo_id: filtros.periodo_id, ...cambios },
            { preserveState: true, preserveScroll: true, replace: true, only: ['gruposSinDesignar', 'conteoEstado', 'docentesBajoLimite', 'filtros', 'resumenCarreras', 'evolucion'] }
        );
    }

    function usarValoresPorDefecto() {
        aplicarFiltros({ gestion_id: '', periodo_id: '' });
    }

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



    const gruposPaginados = gruposSinDesignar.slice((paginaGrupos - 1) * itemsPorPagina, paginaGrupos * itemsPorPagina);
    const docentesPaginados = docentesBajoLimite.slice((paginaDocentes - 1) * itemsPorPagina, paginaDocentes * itemsPorPagina);

    // Progreso general calculations
    const porcentajeCompletado = totalDesignaciones > 0 ? Math.round((conteoEstado.aprobada / totalDesignaciones) * 100) : 0;
    const pendientesDeCompletar = conteoEstado.propuesta + conteoEstado.rechazada;

    const radio = 36;
    const circunferencia = 2 * Math.PI * radio;
    const segmentos = [
        { cantidad: conteoEstado.aprobada, color: '#22c55e' },
        { cantidad: conteoEstado.propuesta, color: '#f59e0b' },
        { cantidad: conteoEstado.rechazada, color: '#ef4444' }
    ];
    const visibles = segmentos.filter((s) => s.cantidad > 0).length;
    const separacion = visibles > 1 ? 1.8 : 0;

    let acumulado = 0;
    const arcos = segmentos.map((segmento) => {
        const inicio = acumulado;
        acumulado += segmento.cantidad;
        return { ...segmento, inicio };
    });

    const gestionSeleccionada = filtros.gestion_id ? gestiones.find(g => String(g.id) === filtros.gestion_id)?.nombre : '2026';
    const periodoSeleccionado = filtros.periodo_id ? periodos.find(p => String(p.id) === filtros.periodo_id)?.nombre : '1';

    // Chart calculations
    const maxEvolucion = Math.max(...evolucion.map((e) => e.valor), 1);
    const yMax = Math.max(maxEvolucion * 1.15, 10);
    const N = evolucion.length - 1;

    const puntosChart = evolucion.map((pt, i) => {
        const x = N > 0 ? (i / N) * 900 + 50 : 50;
        const y = 200 - (pt.valor / yMax) * 160 + 20;
        return { x, y, label: pt.label, valor: pt.valor };
    });

    const dPath = puntosChart.length > 0
        ? `M ${puntosChart[0].x} ${puntosChart[0].y} ` + puntosChart.slice(1).map((p) => `L ${p.x} ${p.y}`).join(' ')
        : '';

    const dArea = puntosChart.length > 0
        ? `${dPath} L ${puntosChart[puntosChart.length - 1].x} 220 L ${puntosChart[0].x} 220 Z`
        : '';
    const resumenCarrerasOrdenado = [...resumenCarreras].sort((a, b) => {
        if (carreraOrden === 'activas-desc') {
            return b.activas - a.activas;
        }
        if (carreraOrden === 'activas-asc') {
            return a.activas - b.activas;
        }
        if (carreraOrden === 'nombre') {
            return a.nombre.localeCompare(b.nombre);
        }
        if (carreraOrden === 'avance-desc') {
            const pctA = a.grupos > 0 ? (a.activas / a.grupos) : 0;
            const pctB = b.grupos > 0 ? (b.activas / b.grupos) : 0;
            return pctB - pctA;
        }
        if (carreraOrden === 'avance-asc') {
            const pctA = a.grupos > 0 ? (a.activas / a.grupos) : 0;
            const pctB = b.grupos > 0 ? (b.activas / b.grupos) : 0;
            return pctA - pctB;
        }
        return 0;
    });

    return (
        <AppLayout>
            <div className="flex flex-col gap-6 xl:flex-row items-stretch mb-6">
                <div className="min-w-0 flex-1 flex flex-col justify-between">
                    <div className="mb-6 flex flex-wrap items-start justify-between gap-3">
                        <div>
                            <h1 className="text-2xl font-bold tracking-tight text-gray-900">Dashboard</h1>
                            <p className="mt-1 text-sm text-gray-500">
                                Estado de designaciones para la gestión y periodo seleccionados.
                            </p>
                        </div>
                        <div className="flex items-center gap-2 shrink-0">
                            {is_admin && (
                                <Link
                                    href={route('revisiones.pendientes')}
                                    className="inline-flex items-center gap-2 rounded-lg border border-amber-200 bg-amber-50 px-4 py-2 text-sm font-medium text-amber-800 shadow-sm transition-colors hover:bg-amber-100 active:scale-[0.98]"
                                >
                                    <Icono tipo="reloj" className="h-4 w-4" />
                                    Revisiones pendientes ({revisionesPendientes})
                                </Link>
                            )}
                        </div>
                    </div>
                    <div className="mb-6 grid grid-cols-1 gap-6 xl:grid-cols-3 2xl:grid-cols-7">
                        {/* Progreso General Card */}
                        <div className="flex flex-col justify-between rounded-2xl bg-[#0b1329] p-4 text-white shadow-md xl:col-span-1 2xl:col-span-2 h-full">
                            <div className="flex flex-wrap items-center justify-between gap-2">
                                <span className="text-[11px] font-bold uppercase tracking-wider text-slate-400">Progreso General</span>
                                <span className="rounded-lg bg-slate-800/60 px-2 py-0.5 text-[10px] text-slate-300">
                                    G. {gestionSeleccionada} - P. {periodoSeleccionado}
                                </span>
                            </div>

                            <div className="my-4 flex items-center gap-5">
                                <div className="relative h-20 w-20 shrink-0">
                                    <svg viewBox="0 0 100 100" className="h-full w-full">
                                        <circle cx="50" cy="50" r={radio} fill="none" stroke="#233554" strokeWidth="10" />
                                        {arcos.map((arco, i) => arco.cantidad > 0 && (
                                            <circle
                                                key={i}
                                                cx="50"
                                                cy="50"
                                                r={radio}
                                                fill="none"
                                                stroke={arco.color}
                                                strokeWidth="10"
                                                strokeLinecap={visibles > 1 ? 'round' : 'butt'}
                                                strokeDasharray={`${Math.max((arco.cantidad / (totalDesignaciones || 1)) * circunferencia - separacion, 0.6)} ${circunferencia}`}
                                                strokeDashoffset={-(arco.inicio / (totalDesignaciones || 1)) * circunferencia - separacion / 2}
                                                transform="rotate(-90 50 50)"
                                                className="transition-all duration-500 ease-out"
                                            />
                                        ))}
                                    </svg>
                                    <div className="absolute inset-0 flex flex-col items-center justify-center text-white">
                                        <span className="text-lg font-bold tracking-tight">{porcentajeCompletado}%</span>
                                        <span className="text-[8px] font-medium uppercase text-slate-400">hecho</span>
                                    </div>
                                </div>
                                <div>
                                    <p className="text-3xl font-extrabold tracking-tight text-white tabular-nums">{totalDesignaciones}</p>
                                    <p className="text-[11px] text-slate-400 mt-0.5">Designaciones totales</p>
                                </div>
                            </div>

                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5 border-t border-slate-800/80 pt-3 text-[11px]">
                                <div className="flex items-center gap-1.5">
                                    <span className="h-2 w-2 rounded-full bg-green-500" />
                                    <span className="font-semibold">{conteoEstado.aprobada}</span>
                                    <span className="text-slate-400">Aprobadas</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <span className="h-2 w-2 rounded-full bg-amber-400" />
                                    <span className="font-semibold">{conteoEstado.propuesta}</span>
                                    <span className="text-slate-400">Propuestas</span>
                                </div>
                                <div className="flex items-center gap-1.5">
                                    <span className="h-2 w-2 rounded-full bg-red-500" />
                                    <span className="font-semibold">{conteoEstado.rechazada}</span>
                                    <span className="text-slate-400">Rechazadas</span>
                                </div>
                            </div>

                            <div className="mt-3">
                                <span className="inline-block rounded-full bg-slate-800/50 px-2.5 py-0.5 text-[10px] font-medium text-slate-300">
                                    Quedan {pendientesDeCompletar} por completar
                                </span>
                            </div>
                        </div>

                        {/* StatTiles Container */}
                        <div className="xl:col-span-2 2xl:col-span-5 flex flex-col xl:justify-between xl:h-full gap-4 xl:gap-0">
                            <div className="grid grid-cols-2 gap-4 sm:grid-cols-3 md:grid-cols-5 xl:grid-cols-3 2xl:grid-cols-5 items-start">
                                <DashboardStatTile
                                    tipo="capas"
                                    tono="rojo"
                                    titulo="Grupos sin cubrir"
                                    valor={gruposSinDesignar.length}
                                    subtitulo="habilitados, sin designación"
                                />
                                <DashboardStatTile
                                    tipo="reloj"
                                    tono="ambar"
                                    titulo="Propuestas"
                                    valor={conteoEstado.propuesta}
                                    subtitulo={`de ${totalDesignaciones} designaciones`}
                                />
                                <DashboardStatTile
                                    tipo="check"
                                    tono="verde"
                                    titulo="Aprobadas"
                                    valor={conteoEstado.aprobada}
                                    subtitulo={`de ${totalDesignaciones} designaciones`}
                                />
                                <DashboardStatTile
                                    tipo="equis"
                                    tono="rojo"
                                    titulo="Rechazadas"
                                    valor={conteoEstado.rechazada}
                                    subtitulo={`de ${totalDesignaciones} designaciones`}
                                />
                                <DashboardStatTile
                                    tipo="alerta"
                                    tono="ambar"
                                    titulo={`Docentes bajo ${limiteHoras}h`}
                                    valor={docentesBajoLimite.length}
                                    subtitulo="carga académica incompleta"
                                />
                            </div>

                            {/* Resumen de Pendientes y Meta Estimada */}
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2">
                                <div className="flex items-center justify-between rounded-xl border border-gray-200/80 bg-white px-4 py-3 shadow-sm">
                                    <div className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full bg-slate-400" />
                                        <span className="text-xs font-semibold text-gray-500">Designaciones pendientes de completar</span>
                                    </div>
                                    <span className="text-base font-bold text-slate-600 tabular-nums">{pendientesDeCompletar}</span>
                                </div>
                                <div className="flex items-center justify-between rounded-xl border border-gray-200/80 bg-white px-4 py-3 shadow-sm">
                                    <div className="flex items-center gap-2">
                                        <span className="h-2 w-2 rounded-full bg-blue-600" />
                                        <span className="text-xs font-semibold text-gray-500">Meta estimada (Total + Pendientes)</span>
                                    </div>
                                    <span className="text-base font-bold text-blue-950 tabular-nums">{totalDesignaciones + pendientesDeCompletar}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    {/* Custom Filter Bar */}
                    <div className="mb-6 flex items-center justify-between gap-4 rounded-xl border border-gray-200/80 bg-white p-3 shadow-sm overflow-x-auto scrollbar-none">
                        <div className="flex items-center gap-4 shrink-0">
                            <div className="flex items-center gap-1.5 shrink-0">
                                <label className="text-[10px] font-bold uppercase tracking-wider text-gray-400">Gestión</label>
                                <div className="w-24">
                                    <Select
                                        value={filtros.gestion_id}
                                        onChange={(e) => aplicarFiltros({ gestion_id: e.target.value })}
                                    >
                                        <option value="">Más reciente</option>
                                        {gestiones.map((g) => (
                                            <option key={g.id} value={g.id}>{g.nombre}</option>
                                        ))}
                                    </Select>
                                </div>
                            </div>

                            <div className="flex items-center gap-1.5 shrink-0">
                                <label className="text-[10px] font-bold uppercase tracking-wider text-gray-400">Periodo</label>
                                <div className="w-24">
                                    <Select
                                        value={filtros.periodo_id}
                                        onChange={(e) => aplicarFiltros({ periodo_id: e.target.value })}
                                    >
                                        <option value="">Primero disponible</option>
                                        {periodos.map((p) => (
                                            <option key={p.id} value={p.id}>{p.nombre}</option>
                                        ))}
                                    </Select>
                                </div>
                            </div>

                            <div className="flex items-center gap-1.5 shrink-0">
                                <label className="text-[10px] font-bold uppercase tracking-wider text-gray-400">Ordenar carreras</label>
                                <div className="w-36">
                                    <Select
                                        value={carreraOrden}
                                        onChange={(e) => setCarreraOrden(e.target.value)}
                                    >
                                        <option value="activas-desc">Más designaciones</option>
                                        <option value="activas-asc">Menos designaciones</option>
                                        <option value="nombre">Por nombre</option>
                                        <option value="avance-desc">Mayor cobertura (%)</option>
                                        <option value="avance-asc">Menor cobertura (%)</option>
                                    </Select>
                                </div>
                            </div>

                            <button
                                onClick={usarValoresPorDefecto}
                                disabled={!filtros.gestion_id && !filtros.periodo_id}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40 shrink-0"
                            >
                                <Icono tipo="embudo" className="h-3.5 w-3.5" />
                                Filtros
                            </button>
                        </div>

                        <div className="flex items-center gap-2 text-xs text-gray-400 shrink-0 ml-4">
                            <span>Actualizado hace 5 min</span>
                            <button onClick={() => aplicarFiltros({})} className="text-gray-400 hover:text-gray-600 transition-colors">
                                <Icono tipo="reloj" className="h-3.5 w-3.5" />
                            </button>
                        </div>
                    </div>
                </div>

                <div className="w-full shrink-0 xl:w-80 flex flex-col justify-end xl:pt-[76px]">
                    {/* Resumen de designaciones */}
                    <div className="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm h-full flex flex-col justify-between">
                        <div>
                            <h3 className="font-semibold tracking-tight text-gray-900">Resumen de designaciones</h3>
                            <div className="mt-4 flex justify-center">
                                <GraficoAnillo resumen={resumenDashboard} etiqueta="total" />
                            </div>
                        </div>
                        <div>
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
                    </div>
                </div>
            </div>

            {/* Fila de Carreras y Estados */}
            <div className="grid grid-cols-1 gap-6 mb-6">
                        {/* Designaciones por carrera */}
                        <div className="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm">
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="font-semibold tracking-tight text-gray-900">Designaciones por carrera</h3>
                                <Link
                                    href={route('designaciones.index')}
                                    className="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
                                >
                                    Ver detalle
                                </Link>
                            </div>
                            <div className="space-y-4">
                                {resumenCarrerasOrdenado.slice(0, 5).map((carrera) => {
                                    const pct = carrera.grupos > 0 ? Math.round((carrera.activas / carrera.grupos) * 100) : 0;
                                    const barColor = pct >= 60 ? 'bg-green-500' : pct >= 40 ? 'bg-amber-500' : 'bg-red-500';
                                    return (
                                        <div key={carrera.id} className="grid grid-cols-12 items-center gap-3">
                                            <div className="col-span-4 min-w-0">
                                                <p className="text-xs font-semibold text-gray-900 truncate" title={carrera.nombre}>
                                                    {carrera.nombre}
                                                </p>
                                                <p className="text-[10px] text-gray-400 mt-0.5">{carrera.sigla}</p>
                                            </div>
                                            <div className="col-span-7">
                                                <div className="relative h-2 w-full rounded-full bg-gray-100 ring-1 ring-inset ring-gray-200/40 overflow-hidden">
                                                    <div
                                                        className={`absolute left-0 top-0 h-full rounded-full ${barColor} transition-[width] duration-500`}
                                                        style={{ width: `${pct}%` }}
                                                    />
                                                </div>
                                            </div>
                                            <span className="col-span-1 text-right text-xs font-bold text-gray-900 tabular-nums">{pct}%</span>
                                        </div>
                                    );
                                })}
                            </div>
                            
                            <div className="mt-5 grid grid-cols-12 text-[10px] font-semibold text-gray-400 border-t border-gray-100 pt-3.5">
                                <div className="col-span-4" />
                                <div className="col-span-7 flex justify-between">
                                    <span>0%</span>
                                    <span>25%</span>
                                    <span>50%</span>
                                    <span>75%</span>
                                    <span>100%</span>
                                </div>
                                <div className="col-span-1" />
                            </div>
                        </div>

                        {/* Designaciones por estado */}
                        <div className="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm flex flex-col justify-between">
                            <div className="mb-4 flex items-center justify-between">
                                <h3 className="font-semibold tracking-tight text-gray-900">Designaciones por estado</h3>
                                <Link
                                    href={route('designaciones.index')}
                                    className="rounded-lg border border-gray-200 bg-white px-2.5 py-1 text-xs font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50"
                                >
                                    Ver detalle
                                </Link>
                            </div>
                            <div className="flex flex-col sm:flex-row items-center gap-6 my-auto justify-center">
                                <div className="shrink-0">
                                    <GraficoAnillo resumen={resumenDashboard} etiqueta="total" />
                                </div>
                                <ul className="flex-1 space-y-1.5 w-full">
                                    {leyendaDona.map((fila) => (
                                        <li
                                            key={fila.etiqueta}
                                            className="flex items-center justify-between rounded-lg px-2.5 py-1 text-xs transition-colors hover:bg-gray-50"
                                        >
                                            <span className="flex items-center gap-2.5 text-gray-600">
                                                <span className={`h-2 w-2 rounded-full ${fila.punto}`} />
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
                            </div>
                            <div className="mt-4 flex items-center justify-between border-t border-gray-100 pt-3 text-xs">
                                <span className="text-gray-500">Total</span>
                                <span className="font-semibold text-gray-900 tabular-nums">{totalDesignaciones}</span>
                            </div>
                        </div>
                    </div>




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
                            <>
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
                                        {gruposPaginados.map((grupo) => (
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
                                <PaginadorCliente
                                    paginaActual={paginaGrupos}
                                    totalItems={gruposSinDesignar.length}
                                    itemsPorPagina={itemsPorPagina}
                                    onPaginaChange={setPaginaGrupos}
                                />
                            </>
                        )}

                        {tabActiva === 'docentes' && (
                            <>
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
                                        {docentesPaginados.map((docente) => (
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
                                <PaginadorCliente
                                    paginaActual={paginaDocentes}
                                    totalItems={docentesBajoLimite.length}
                                    itemsPorPagina={itemsPorPagina}
                                    onPaginaChange={setPaginaDocentes}
                                />
                            </>
                        )}
                </div>
        </AppLayout>
    );
}
