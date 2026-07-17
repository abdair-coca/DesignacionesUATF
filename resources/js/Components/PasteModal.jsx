import { useState, useEffect } from 'react';
import { router } from '@inertiajs/react';
import { Icono } from './Icono';
import { clipboardRead, clipboardClear } from '../Hooks/useClipboard';

export default function PasteModal({ abierto, onCerrar, gestionNombre, periodoNombre, filtros }) {
    const [data, setData] = useState(null);
    const [incluir, setIncluir] = useState({});
    const [procesando, setProcesando] = useState(false);
    const [resultado, setResultado] = useState(null);

    useEffect(() => {
        if (abierto) {
            const clip = clipboardRead();
            setData(clip);
            setResultado(null);
            if (clip?.filas) {
                const mapa = {};
                clip.filas.forEach((_, i) => { mapa[i] = true; });
                setIncluir(mapa);
            }
        } else {
            setData(null);
            setIncluir({});
            setResultado(null);
        }
    }, [abierto]);

    if (!abierto || !data) return null;

    const filas = data.filas ?? [];
    const seleccionadas = Object.entries(incluir).filter(([, v]) => v).map(([k]) => filas[Number(k)]);

    function toggleFila(idx) {
        setIncluir((prev) => ({ ...prev, [idx]: !prev[idx] }));
    }

    function toggleTodas() {
        const todas = filas.every((_, i) => incluir[i]);
        const mapa = {};
        filas.forEach((_, i) => { mapa[i] = !todas; });
        setIncluir(mapa);
    }

    async function pegar() {
        if (seleccionadas.length === 0) return;
        setProcesando(true);

        try {
            const token = document.querySelector('meta[name="csrf-token"]')?.content
                ?? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
                ?? '';

            const res = await fetch(route('designaciones.pegar'), {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-XSRF-TOKEN': decodeURIComponent(token),
                },
                body: JSON.stringify({
                    Id_gestion: filtros.gestion_id,
                    Id_periodo: filtros.periodo_id,
                    filas: seleccionadas.map((f) => ({
                        Id_docente: f.Id_docente,
                        Id_materia: f.Id_materia,
                        Id_grupo: f.Id_grupo,
                    })),
                }),
            });

            const json = await res.json();
            clipboardClear();
            setResultado(json);

            if (json.creadas > 0) {
                router.reload({ only: ['materias', 'designaciones', 'roster', 'historialPorGrupo'] });
            }
        } catch {
            setResultado({ error: 'Error de red. Intente de nuevo.' });
        } finally {
            setProcesando(false);
        }
    }

    function deshacer() {
        if (!resultado?.creadas_ids?.length) return;

        const token = document.querySelector('meta[name="csrf-token"]')?.content
            ?? document.cookie.match(/XSRF-TOKEN=([^;]+)/)?.[1]
            ?? '';

        fetch(route('designaciones.deshacer-pegado'), {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest',
                'X-XSRF-TOKEN': decodeURIComponent(token),
            },
            body: JSON.stringify({ ids: resultado.creadas_ids }),
        }).then(() => {
            setResultado(null);
            router.reload({ only: ['materias', 'designaciones', 'roster', 'historialPorGrupo'] });
        });
    }

    const esMismaCarrera = data.origen?.carrera_id === filtros.carrera_id;

    return (
        <div className="fixed inset-0 z-[60] flex items-center justify-center bg-black/30 p-4">
            <div className="fixed inset-0" onClick={onCerrar} />

            <div className="relative z-10 w-full max-w-2xl rounded-xl border border-gray-200 bg-white shadow-2xl">
                <div className="flex items-center justify-between border-b border-gray-100 px-5 py-4">
                    <div>
                        <h2 className="text-base font-semibold text-gray-900">Pegar designaciones</h2>
                        <p className="mt-0.5 text-sm text-gray-500">
                            {filas.length} copiada{filas.length !== 1 ? 's' : ''} de {data.origen?.carrera_nombre || 'otra carrera'} ({data.origen?.gestion_nombre}/{data.origen?.periodo_nombre})
                        </p>
                    </div>
                    <button onClick={onCerrar} className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-600">
                        <Icono tipo="cerrar" className="h-5 w-5" />
                    </button>
                </div>

                {!esMismaCarrera && filas.length > 0 && (
                    <div className="mx-5 mt-3 rounded-lg border border-amber-200 bg-amber-50 px-3 py-2 text-xs text-amber-800">
                        <Icono tipo="alerta" className="mr-1 inline h-3 w-3" />
                        Las filas provienen de otra carrera. Verifique que los grupos sean compatibles.
                    </div>
                )}

                {!resultado ? (
                    <>
                        <div className="max-h-80 overflow-y-auto px-5 py-3">
                            {filas.length === 0 ? (
                                <p className="py-6 text-center text-sm text-gray-400">No hay filas para pegar.</p>
                            ) : (
                                <table className="min-w-full text-sm">
                                    <thead>
                                        <tr className="border-b border-gray-100">
                                            <th className="py-2 pr-2 text-left">
                                                <input
                                                    type="checkbox"
                                                    checked={filas.every((_, i) => incluir[i])}
                                                    onChange={toggleTodas}
                                                    className="h-4 w-4 rounded border-gray-300 text-blue-900"
                                                />
                                            </th>
                                            <th className="py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400">Materia</th>
                                            <th className="py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400">Grupo</th>
                                            <th className="py-2 text-left text-[11px] font-semibold uppercase tracking-wider text-gray-400">Docente</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-50">
                                        {filas.map((fila, i) => (
                                            <tr
                                                key={i}
                                                onClick={() => toggleFila(i)}
                                                className={`cursor-pointer transition-colors ${incluir[i] ? 'bg-blue-50/50' : 'hover:bg-gray-50'}`}
                                            >
                                                <td className="py-2.5 pr-2">
                                                    <input
                                                        type="checkbox"
                                                        checked={!!incluir[i]}
                                                        onChange={() => toggleFila(i)}
                                                        onClick={(e) => e.stopPropagation()}
                                                        className="h-4 w-4 rounded border-gray-300 text-blue-900"
                                                    />
                                                </td>
                                                <td className="py-2.5">
                                                    <span className="font-medium text-gray-900">{fila.materia_sigla}</span>
                                                    <span className="ml-1.5 text-gray-500">{fila.materia_nombre}</span>
                                                </td>
                                                <td className="py-2.5 text-gray-600">{fila.grupo_codigo}</td>
                                                <td className="py-2.5 text-gray-600">{fila.docente_nombre}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            )}
                        </div>

                        <div className="flex items-center justify-between border-t border-gray-100 px-5 py-3">
                            <p className="text-sm text-gray-500">
                                <span className="font-medium text-gray-900">{seleccionadas.length}</span> de {filas.length} seleccionadas
                                {' · '}
                                Destino: {gestionNombre}/{periodoNombre}
                            </p>
                            <div className="flex gap-2">
                                <button
                                    onClick={onCerrar}
                                    className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                                >
                                    Cancelar
                                </button>
                                <button
                                    onClick={pegar}
                                    disabled={procesando || seleccionadas.length === 0}
                                    className="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    {procesando ? 'Pegando…' : `Pegar (${seleccionadas.length})`}
                                </button>
                            </div>
                        </div>
                    </>
                ) : (
                    <div className="px-5 py-6 text-center">
                        {resultado.error ? (
                            <p className="text-sm text-red-600">{resultado.error}</p>
                        ) : (
                            <>
                                <div className="mx-auto mb-3 flex h-12 w-12 items-center justify-center rounded-full bg-green-50">
                                    <Icono tipo="check" className="h-6 w-6 text-green-600" />
                                </div>
                                <p className="text-sm font-medium text-gray-900">
                                    {resultado.creadas} designación{resultado.creadas !== 1 ? 'es' : ''} creada{resultado.creadas !== 1 ? 's' : ''}
                                </p>
                                {resultado.saltadas > 0 && (
                                    <p className="mt-1 text-xs text-gray-400">
                                        {resultado.saltadas} omitida{resultado.saltadas !== 1 ? 's' : ''} (grupo ya ocupado o límite de horas)
                                    </p>
                                )}
                                <div className="mt-4 flex justify-center gap-2">
                                    {resultado.creadas > 0 && (
                                        <button
                                            onClick={deshacer}
                                            className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 transition-colors hover:bg-gray-50"
                                        >
                                            Deshacer
                                        </button>
                                    )}
                                    <button
                                        onClick={onCerrar}
                                        className="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white transition-colors hover:bg-blue-800"
                                    >
                                        Cerrar
                                    </button>
                                </div>
                            </>
                        )}
                    </div>
                )}
            </div>
        </div>
    );
}
