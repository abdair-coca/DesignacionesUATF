import { useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import Badge from '../../Components/Badge';
import EmptyState from '../../Components/EmptyState';

const BADGES_ESTADO = {
    aprobada: { tono: 'verde', icono: 'check', etiqueta: 'Aprobada' },
    rechazada: { tono: 'rojo', icono: 'equis', etiqueta: 'Rechazada' },
    propuesta: { tono: 'ambar', icono: 'reloj', etiqueta: 'Propuesta' },
};

export default function Revisar({ revision, designaciones }) {
    const [acciones, setAcciones] = useState({});
    const [procesando, setProcesando] = useState(false);
    const [completando, setCompletando] = useState(false);
    const [completada, setCompletada] = useState(false);

    const pendientes = designaciones.filter((d) => d.estado === 'propuesta');
    const procesadas = designaciones.filter((d) => d.estado !== 'propuesta');
    const accionesCount = Object.keys(acciones).length;

    function marcar(id, accion) {
        setAcciones((prev) => ({ ...prev, [id]: accion }));
    }

    function desmarcar(id) {
        setAcciones((prev) => {
            const copy = { ...prev };
            delete copy[id];
            return copy;
        });
    }

    function accionPara(id) {
        return acciones[id] ?? null;
    }

    async function enviarAcciones() {
        const entries = Object.entries(acciones);
        if (entries.length === 0) return;

        setProcesando(true);

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content
                ?? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
                ?? '';

            const res = await fetch(route('revisiones.procesar', revision.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(token),
                },
                body: JSON.stringify({
                    acciones: entries.map(([id, accion]) => ({ id, accion })),
                }),
            });

            const json = await res.json();

            if (json.success) {
                setAcciones({});
                router.reload({ only: ['designaciones'] });
            }
        } catch {
            alert('Error al procesar. Intente de nuevo.');
        } finally {
            setProcesando(false);
        }
    }

    async function completarRevision() {
        if (procesadas.length === 0 && accionesCount === 0) {
            alert('Debe aprobar o rechazar al menos una designación.');
            return;
        }

        setCompletando(true);

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content
                ?? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
                ?? '';

            const res = await fetch(route('revisiones.completar', revision.id), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(token),
                },
            });

            const json = await res.json();

            if (json.success) {
                setCompletada(true);
            }
        } catch {
            alert('Error al completar. Intente de nuevo.');
        } finally {
            setCompletando(false);
        }
    }

    if (completada) {
        return (
            <AppLayout>
                <div className="flex flex-col items-center justify-center py-20">
                    <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-green-50">
                        <Icono tipo="check" className="h-8 w-8 text-green-600" />
                    </div>
                    <h2 className="text-xl font-semibold text-gray-900">Revisión completada</h2>
                    <p className="mt-1 text-sm text-gray-500">
                        {revision.carrera_nombre} — {revision.gestion_nombre} / {revision.periodo_nombre}
                    </p>
                    <Link
                        href={route('revisiones.pendientes')}
                        className="mt-6 inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800"
                    >
                        Volver a pendientes
                    </Link>
                </div>
            </AppLayout>
        );
    }

    return (
        <AppLayout>
            <div className="mb-4">
                <nav className="flex items-center gap-1.5 text-sm">
                    <Link href={route('revisiones.pendientes')} className="text-blue-700 transition-colors hover:text-blue-800 hover:underline">
                        Revisiones pendientes
                    </Link>
                    <span className="text-gray-300">›</span>
                    <span className="text-gray-500">{revision.carrera_nombre}</span>
                </nav>
            </div>

            <div className="mb-6 flex flex-wrap items-center justify-between gap-3">
                <div>
                    <h1 className="text-2xl font-bold tracking-tight text-gray-900">Revisar: {revision.carrera_nombre}</h1>
                    <p className="mt-1 text-sm text-gray-500">
                        {revision.gestion_nombre} / {revision.periodo_nombre} · Solicitado por {revision.solicitante} el {revision.solicitado_en}
                    </p>
                </div>
                <Link
                    href={route('revisiones.pendientes')}
                    className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50"
                >
                    <Icono tipo="flechaIzq" className="h-4 w-4" />
                    Volver
                </Link>
            </div>

            {designaciones.length === 0 ? (
                <EmptyState
                    icono="vacio"
                    titulo="Sin designaciones"
                    subtitulo="No hay designaciones registradas en esta carrera para el periodo seleccionado."
                />
            ) : (
                <>
                    <div className="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-gray-200/80 bg-white p-3 shadow-sm">
                        <div className="flex items-center gap-4 text-sm text-gray-600">
                            <span>
                                <span className="font-semibold text-gray-900">{pendientes.length}</span> pendientes
                            </span>
                            <span>
                                <span className="font-semibold text-green-700">{procesadas.filter(d => d.estado === 'aprobada').length}</span> aprobadas
                            </span>
                            <span>
                                <span className="font-semibold text-red-700">{procesadas.filter(d => d.estado === 'rechazada').length}</span> rechazadas
                            </span>
                        </div>

                        <div className="flex gap-2">
                            {pendientes.length > 0 && (
                                <>
                                    <button
                                        onClick={() => {
                                            pendientes.forEach((d) => marcar(d.id, 'aprobar'));
                                        }}
                                        className="inline-flex items-center gap-1.5 rounded-lg border border-green-200 bg-green-50 px-3 py-1.5 text-xs font-medium text-green-800 transition-colors hover:bg-green-100"
                                    >
                                        <Icono tipo="check" className="h-3.5 w-3.5" />
                                        Aprobar todas
                                    </button>
                                    <button
                                        onClick={() => {
                                            pendientes.forEach((d) => marcar(d.id, 'rechazar'));
                                        }}
                                        className="inline-flex items-center gap-1.5 rounded-lg border border-red-200 bg-red-50 px-3 py-1.5 text-xs font-medium text-red-800 transition-colors hover:bg-red-100"
                                    >
                                        <Icono tipo="equis" className="h-3.5 w-3.5" />
                                        Rechazar todas
                                    </button>
                                </>
                            )}
                            <button
                                onClick={() => setAcciones({})}
                                disabled={accionesCount === 0}
                                className="inline-flex items-center gap-1.5 rounded-lg border border-gray-200 bg-white px-3 py-1.5 text-xs font-medium text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                Limpiar
                            </button>
                        </div>
                    </div>

                    <div className="overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm">
                        <table className="min-w-full divide-y divide-gray-200 text-sm">
                            <thead className="bg-gray-50/80">
                                <tr>
                                    {['Docente', 'Materia', 'Grupo', 'Estado actual', 'Acción'].map((h) => (
                                        <th
                                            key={h}
                                            className="whitespace-nowrap px-4 py-3 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400"
                                        >
                                            {h}
                                        </th>
                                    ))}
                                </tr>
                            </thead>
                            <tbody className="divide-y divide-gray-100">
                                {designaciones.map((d) => {
                                    const accion = accionPara(d.id);
                                    const badge = BADGES_ESTADO[d.estado];
                                    return (
                                        <tr
                                            key={d.id}
                                            className={`transition-colors ${
                                                accion === 'aprobar'
                                                    ? 'bg-green-50/50'
                                                    : accion === 'rechazar'
                                                        ? 'bg-red-50/50'
                                                        : 'hover:bg-gray-50/60'
                                            }`}
                                        >
                                            <td className="px-4 py-3.5 font-medium text-gray-900">{d.docente_nombre}</td>
                                            <td className="px-4 py-3.5 text-gray-600">
                                                {d.materia_sigla} — {d.materia_nombre}
                                            </td>
                                            <td className="px-4 py-3.5 text-gray-600">{d.grupo_codigo}</td>
                                            <td className="px-4 py-3.5">
                                                <Badge tono={badge.tono} icono={badge.icono}>
                                                    {badge.etiqueta}
                                                </Badge>
                                            </td>
                                            <td className="px-4 py-3.5">
                                                {d.estado === 'propuesta' ? (
                                                    <div className="flex gap-1.5">
                                                        <button
                                                            onClick={() => marcar(d.id, 'aprobar')}
                                                            disabled={accion === 'aprobar'}
                                                            className={`rounded-lg px-2.5 py-1 text-xs font-medium transition-colors ${
                                                                accion === 'aprobar'
                                                                    ? 'bg-green-100 text-green-800 ring-1 ring-inset ring-green-300'
                                                                    : 'border border-green-200 text-green-700 hover:bg-green-50'
                                                            }`}
                                                        >
                                                            {accion === 'aprobar' ? '✓ Aprobada' : 'Aprobar'}
                                                        </button>
                                                        <button
                                                            onClick={() => marcar(d.id, 'rechazar')}
                                                            disabled={accion === 'rechazar'}
                                                            className={`rounded-lg px-2.5 py-1 text-xs font-medium transition-colors ${
                                                                accion === 'rechazar'
                                                                    ? 'bg-red-100 text-red-800 ring-1 ring-inset ring-red-300'
                                                                    : 'border border-red-200 text-red-700 hover:bg-red-50'
                                                            }`}
                                                        >
                                                            {accion === 'rechazar' ? '✗ Rechazada' : 'Rechazar'}
                                                        </button>
                                                        <button
                                                            onClick={() => desmarcar(d.id)}
                                                            disabled={!accion}
                                                            className="rounded-lg px-2 py-1 text-xs text-gray-400 transition-colors hover:text-gray-600 disabled:opacity-0"
                                                        >
                                                            <Icono tipo="cerrar" className="h-3.5 w-3.5" />
                                                        </button>
                                                    </div>
                                                ) : (
                                                    <span className="text-xs text-gray-400">—</span>
                                                )}
                                            </td>
                                        </tr>
                                    );
                                })}
                            </tbody>
                        </table>
                    </div>

                    <div className="mt-4 flex items-center justify-between rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
                        <p className="text-sm text-gray-600">
                            {accionesCount > 0 ? (
                                <span>
                                    <span className="font-semibold text-gray-900">{accionesCount}</span> designaciones por procesar
                                </span>
                            ) : (
                                <span className="text-gray-400">Seleccione aprobar o rechazar para cada designación</span>
                            )}
                        </p>
                        <div className="flex gap-2">
                            <button
                                onClick={enviarAcciones}
                                disabled={procesando || accionesCount === 0}
                                className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-4 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                {procesando ? 'Procesando…' : `Aplicar (${accionesCount})`}
                            </button>
                            <button
                                onClick={completarRevision}
                                disabled={completando || procesando}
                                className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-40"
                            >
                                {completando ? 'Completando…' : 'Completar revisión'}
                            </button>
                        </div>
                    </div>
                </>
            )}
        </AppLayout>
    );
}
