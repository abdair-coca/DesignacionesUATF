import { useCallback, useEffect, useRef, useState } from 'react';
import { Link, router } from '@inertiajs/react';
import AppLayout from '../../Layouts/AppLayout';
import { Icono } from '../../Components/Icono';
import Select from '../../Components/Select';
import EmptyState from '../../Components/EmptyState';
import MenuFlotante from '../../Components/MenuFlotante';
import ComboboxDocente from '../../Components/ComboboxDocente';
import Badge from '../../Components/Badge';
import paletaIcono from '../../Components/paletaIcono';
import { useSelection } from '../../Hooks/useSelection';
import { clipboardCopy, clipboardRead, clipboardClear } from '../../Hooks/useClipboard';

const badgesDesignacion = {
    aprobada: { tono: 'verde', icono: 'check', etiqueta: 'Aprobada' },
    rechazada: { tono: 'rojo', icono: 'equis', etiqueta: 'Rechazada' },
    propuesta: { tono: 'ambar', icono: 'reloj', etiqueta: 'Propuesta' },
    sin_asignar: { tono: 'gris', icono: 'vacio', etiqueta: 'Sin asignar' },
};

function AnilloProgreso({ asignadas, porAsignar, sinGrupos }) {
    const total = asignadas + porAsignar + sinGrupos;
    const radio = 40;
    const circunferencia = 2 * Math.PI * radio;
    const pct = total > 0 ? Math.round((asignadas / total) * 100) : 0;
    const segmentos = [
        { cantidad: asignadas, color: '#22c55e' },
        { cantidad: porAsignar, color: '#3b82f6' },
        { cantidad: sinGrupos, color: '#d1d5db' },
    ];
    const visibles = segmentos.filter((s) => s.cantidad > 0).length;
    const separacion = visibles > 1 ? 1.8 : 0;

    let acumulado = 0;
    const arcos = segmentos.map((segmento) => {
        const inicio = acumulado;
        acumulado += segmento.cantidad;
        return { ...segmento, inicio };
    });

    return (
        <div className="relative shrink-0">
            <svg viewBox="0 0 100 100" className="h-28 w-28">
                <circle cx="50" cy="50" r={radio} fill="none" stroke="#f3f4f6" strokeWidth="11" />
                {arcos.map(
                    (arco, i) =>
                        arco.cantidad > 0 && (
                            <circle
                                key={i}
                                cx="50"
                                cy="50"
                                r={radio}
                                fill="none"
                                stroke={arco.color}
                                strokeWidth="11"
                                strokeLinecap={visibles > 1 ? 'round' : 'butt'}
                                strokeDasharray={`${Math.max((arco.cantidad / (total || 1)) * circunferencia - separacion, 0.6)} ${circunferencia}`}
                                strokeDashoffset={-(arco.inicio / (total || 1)) * circunferencia - separacion / 2}
                                transform="rotate(-90 50 50)"
                                className="[transition:stroke-dasharray_.5s_ease,stroke-dashoffset_.5s_ease]"
                            />
                        )
                )}
            </svg>
            <div className="pointer-events-none absolute inset-0 flex items-center justify-center">
                <span className="text-xl font-semibold tracking-tight text-gray-900 tabular-nums">{pct}%</span>
            </div>
        </div>
    );
}

export default function Carrera({
    carrera,
    materias,
    designaciones,
    roster,
    historialPorGrupo,
    docentes,
    gestiones,
    periodos,
    limiteHoras,
    filtros,
}) {
    const [tab, setTab] = useState('roster');
    const [busqueda, setBusqueda] = useState('');
    const [pagina, setPagina] = useState(1);
    const [selectorPeriodo, setSelectorPeriodo] = useState(false);
    const [cambios, setCambios] = useState({});
    const [histoAbierto, setHistoAbierto] = useState(null);
    const [guardando, setGuardando] = useState(false);
    const porPagina = 10;

    const seleccion = useSelection();
    const seleccionRef = useRef(seleccion);
    seleccionRef.current = seleccion;

    const copiarSeleccionadas = useCallback(() => {
        const sel = seleccionRef.current;
        if (sel.count === 0) return;

        const filasACopiar = sel.selectedIds.map((id) => {
            const fila = roster.find((f) => String(f.id) === String(id));
            if (!fila) return null;
            return {
                Id_docente: fila.designacion?.docente?.id ?? null,
                Id_materia: fila.materia.id,
                Id_grupo: fila.id,
                materia_sigla: fila.materia.sigla,
                materia_nombre: fila.materia.nombre,
                docente_nombre: fila.designacion?.docente?.nombre ?? 'Sin asignar',
                horas: fila.horas,
                carrera_sigla: carrera.sigla,
                carrera_id: carrera.id,
                grupo_codigo: fila.codigo,
            };
        }).filter(Boolean);

        if (filasACopiar.length === 0) return;

        clipboardCopy(filasACopiar, {
            gestion_id: filtros.gestion_id,
            gestion_nombre: gestionNombre,
            periodo_id: filtros.periodo_id,
            periodo_nombre: periodoNombre,
            carrera_id: carrera.id,
        });

        sel.clearAll();
    }, [roster, carrera, filtros, gestionNombre, periodoNombre]);

    useEffect(() => {
        function handleKeyDown(e) {
            if (e.key === 'Escape') {
                seleccionRef.current.clearAll();
            }
            if ((e.ctrlKey || e.metaKey) && e.key === 'c') {
                if (seleccionRef.current.count > 0 && !window.getSelection().toString()) {
                    e.preventDefault();
                    copiarSeleccionadas();
                }
            }
        }
        window.addEventListener('keydown', handleKeyDown);
        return () => window.removeEventListener('keydown', handleKeyDown);
    }, [copiarSeleccionadas]);

    const gestionNombre = gestiones.find((g) => String(g.id) === filtros.gestion_id)?.nombre ?? '';
    const periodoNombre = periodos.find((p) => String(p.id) === filtros.periodo_id)?.nombre ?? '';

    const asignadas = materias.filter((m) => m.estado === 'asignada').length;
    const porAsignar = materias.filter((m) => m.estado === 'por_asignar').length;
    const sinGrupos = materias.filter((m) => m.estado === 'sin_grupos').length;
    const gruposTotales = materias.reduce((suma, m) => suma + m.grupos_total, 0);

    function cambiarPeriodo(cambios) {
        router.get(
            route('designaciones.carrera', carrera.id),
            { gestion_id: filtros.gestion_id, periodo_id: filtros.periodo_id, ...cambios },
            {
                preserveState: true,
                preserveScroll: true,
                replace: true,
                only: ['materias', 'designaciones', 'roster', 'historialPorGrupo', 'filtros'],
            }
        );
    }

    function cambiarTab(nuevoTab) {
        setTab(nuevoTab);
        setPagina(1);
        setHistoAbierto(null);
    }

    function cambiarBusqueda(valor) {
        setBusqueda(valor);
        setPagina(1);
    }

    function valorDocente(fila) {
        if (fila.id in cambios) return cambios[fila.id];
        return fila.designacion ? String(fila.designacion.docente.id) : '';
    }

    function cambiarDocente(fila, valor) {
        const original = fila.designacion ? String(fila.designacion.docente.id) : '';
        setCambios((actual) => {
            const copia = { ...actual };
            if (valor === original) {
                delete copia[fila.id];
            } else {
                copia[fila.id] = valor;
            }
            return copia;
        });
        setHistoAbierto(null);
    }

    function descartarCambios() {
        setCambios({});
    }

    function guardarCambios() {
        const entradas = Object.entries(cambios);
        if (entradas.length === 0) return;

        setGuardando(true);
        router.post(
            route('designaciones.carrera.guardar', carrera.id),
            {
                Id_gestion: filtros.gestion_id,
                Id_periodo: filtros.periodo_id,
                cambios: entradas.map(([grupoId, docenteId]) => {
                    const fila = roster.find((f) => String(f.id) === grupoId);
                    return { Id_grupo: fila.id, Id_materia: fila.materia.id, Id_docente: docenteId === '' ? null : docenteId };
                }),
            },
            {
                preserveScroll: true,
                onSuccess: () => setCambios({}),
                onFinish: () => setGuardando(false),
            }
        );
    }

    const terminoBusqueda = busqueda.trim().toLowerCase();

    const filasRoster = roster.filter((fila) => {
        if (terminoBusqueda === '') return true;
        return (
            fila.materia.nombre.toLowerCase().includes(terminoBusqueda) ||
            fila.materia.sigla.toLowerCase().includes(terminoBusqueda) ||
            (fila.designacion?.docente.nombre.toLowerCase().includes(terminoBusqueda) ?? false)
        );
    });

    const filasDesignaciones = designaciones.filter((designacion) => {
        if (terminoBusqueda === '') return true;
        return (
            designacion.docente.nombre.toLowerCase().includes(terminoBusqueda) ||
            designacion.materia.sigla.toLowerCase().includes(terminoBusqueda) ||
            designacion.materia.nombre.toLowerCase().includes(terminoBusqueda)
        );
    });

    const filas = tab === 'resumen' ? filasDesignaciones : filasRoster;
    const totalFilas = filas.length;
    const totalPaginas = Math.max(Math.ceil(totalFilas / porPagina), 1);
    const paginaActual = Math.min(pagina, totalPaginas);
    const visibles = filas.slice((paginaActual - 1) * porPagina, paginaActual * porPagina);
    const desde = totalFilas === 0 ? 0 : (paginaActual - 1) * porPagina + 1;
    const hasta = Math.min(paginaActual * porPagina, totalFilas);

    const tabs = [
        { clave: 'roster', etiqueta: 'Asignación de docentes' },
        { clave: 'resumen', etiqueta: 'Resumen de designaciones' },
    ];

    const leyendaProgreso = [
        { etiqueta: 'Asignadas', cantidad: asignadas, punto: 'bg-green-500' },
        { etiqueta: 'Por asignar', cantidad: porAsignar, punto: 'bg-blue-500' },
        { etiqueta: 'Sin grupos', cantidad: sinGrupos, punto: 'bg-gray-300' },
    ];

    const infoCarrera = [
        { etiqueta: 'Código', valor: carrera.sigla },
        { etiqueta: 'Materias totales', valor: materias.length },
        { etiqueta: 'Grupos habilitados', valor: gruposTotales },
        { etiqueta: 'Designaciones activas', valor: designaciones.filter((d) => d.estado !== 'rechazada').length },
    ];

    const accionesRapidas = [
        {
            etiqueta: 'Ver designaciones',
            tipo: 'ojo',
            href: route('designaciones.lista', { carrera_id: carrera.id }),
        },
    ];

    const pctTotal = (cantidad) => (materias.length ? Math.round((cantidad / materias.length) * 100) : 0);
    const cantidadCambios = Object.keys(cambios).length;

    return (
        <AppLayout>
            <div className="mb-4 flex flex-wrap items-center justify-between gap-3">
                <nav className="flex items-center gap-1.5 text-sm">
                    <Link href={route('designaciones.index')} className="text-blue-700 transition-colors hover:text-blue-800 hover:underline">
                        Designaciones por carrera
                    </Link>
                    <span className="text-gray-300">›</span>
                    <span className="text-gray-500">{carrera.nombre}</span>
                </nav>
                <Link
                    href={route('designaciones.index')}
                    className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50"
                >
                    <Icono tipo="flechaIzq" className="h-4 w-4" />
                    Volver a carreras
                </Link>
            </div>

            <div className="flex flex-col gap-6 xl:flex-row">
                <div className="min-w-0 flex-1">
                    <div className="mb-1 flex flex-wrap items-center gap-3">
                        <h1 className="text-2xl font-bold tracking-tight text-gray-900">Designar en: {carrera.nombre}</h1>
                        {asignadas + porAsignar > 0 && designaciones.some((d) => d.estado !== 'rechazada') ? (
                            <Badge tono="verde" icono="check">
                                Activa
                            </Badge>
                        ) : (
                            <Badge tono="rojo" icono="equis">
                                Sin designaciones
                            </Badge>
                        )}
                    </div>
                    <p className="mb-6 text-sm text-gray-500">
                        Asigna docentes a las materias del plan de estudio. Selecciona el periodo y configura las designaciones.
                    </p>

                    <div className="relative mb-6 flex flex-wrap items-center gap-6 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-blue-50 text-blue-600 ring-1 ring-inset ring-blue-200/50">
                                <Icono tipo="calendario" className="h-[18px] w-[18px]" />
                            </span>
                            <div>
                                <p className="text-[11px] font-medium uppercase tracking-wide text-gray-400">Periodo académico</p>
                                <p className="text-sm font-semibold text-gray-900 tabular-nums">
                                    {gestionNombre} - {periodoNombre}
                                </p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-violet-50 text-violet-600 ring-1 ring-inset ring-violet-200/50">
                                <Icono tipo="capas" className="h-[18px] w-[18px]" />
                            </span>
                            <div>
                                <p className="text-[11px] font-medium uppercase tracking-wide text-gray-400">Grupos habilitados</p>
                                <p className="text-sm font-semibold text-gray-900 tabular-nums">{gruposTotales}</p>
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <span className="flex h-9 w-9 items-center justify-center rounded-lg bg-emerald-50 text-emerald-600 ring-1 ring-inset ring-emerald-200/50">
                                <Icono tipo="libro" className="h-[18px] w-[18px]" />
                            </span>
                            <div>
                                <p className="text-[11px] font-medium uppercase tracking-wide text-gray-400">Total de materias</p>
                                <p className="text-sm font-semibold text-gray-900 tabular-nums">{materias.length}</p>
                            </div>
                        </div>

                        <div className="ml-auto">
                            <button
                                onClick={() => setSelectorPeriodo(!selectorPeriodo)}
                                className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50"
                            >
                                <Icono tipo="calendario" className="h-4 w-4" />
                                Cambiar periodo
                            </button>

                            {selectorPeriodo && (
                                <>
                                    <div className="fixed inset-0 z-10" onClick={() => setSelectorPeriodo(false)} />
                                    <div className="menu-pop absolute right-4 top-full z-20 mt-2 w-64 rounded-lg border border-gray-200/80 bg-white p-4 shadow-lg shadow-gray-200/60">
                                        <p className="mb-3 text-[11px] font-medium uppercase tracking-wide text-gray-400">
                                            Periodo académico
                                        </p>
                                        <div className="space-y-3">
                                            <div>
                                                <label className="mb-1 block text-xs text-gray-500">Gestión</label>
                                                <Select
                                                    value={filtros.gestion_id}
                                                    onChange={(e) => cambiarPeriodo({ gestion_id: e.target.value })}
                                                >
                                                    {gestiones.map((gestion) => (
                                                        <option key={gestion.id} value={gestion.id}>
                                                            {gestion.nombre}
                                                        </option>
                                                    ))}
                                                </Select>
                                            </div>
                                            <div>
                                                <label className="mb-1 block text-xs text-gray-500">Periodo</label>
                                                <Select
                                                    value={filtros.periodo_id}
                                                    onChange={(e) => cambiarPeriodo({ periodo_id: e.target.value })}
                                                >
                                                    {periodos.map((periodo) => (
                                                        <option key={periodo.id} value={periodo.id}>
                                                            {periodo.nombre}
                                                        </option>
                                                    ))}
                                                </Select>
                                            </div>
                                        </div>
                                    </div>
                                </>
                            )}
                        </div>
                    </div>

                    <div className="mb-4 border-b border-gray-200">
                        <nav className="-mb-px flex gap-6">
                            {tabs.map((t) => (
                                <button
                                    key={t.clave}
                                    onClick={() => cambiarTab(t.clave)}
                                    className={`border-b-2 pb-3 text-sm font-medium transition-colors ${
                                        tab === t.clave
                                            ? 'border-blue-700 text-blue-700'
                                            : 'border-transparent text-gray-500 hover:border-gray-200 hover:text-gray-700'
                                    }`}
                                >
                                    {t.etiqueta}
                                </button>
                            ))}
                        </nav>
                    </div>

                    <div className="mb-4 flex flex-wrap items-center gap-3 rounded-xl border border-gray-200/80 bg-white p-4 shadow-sm">
                        <div className="relative min-w-52 flex-1">
                            <span className="pointer-events-none absolute left-3 top-1/2 -translate-y-1/2 text-gray-400">
                                <Icono tipo="buscar" className="h-4 w-4" />
                            </span>
                            <input
                                type="text"
                                placeholder={tab === 'resumen' ? 'Buscar docente o materia...' : 'Buscar materia o docente...'}
                                value={busqueda}
                                onChange={(e) => cambiarBusqueda(e.target.value)}
                                className="w-full rounded-lg border border-gray-200 py-2 pl-9 pr-9 text-sm shadow-sm transition-colors placeholder:text-gray-400 hover:border-gray-300 focus:border-blue-500 focus:outline-none focus:ring-2 focus:ring-blue-500/20"
                            />
                            {busqueda !== '' && (
                                <button
                                    onClick={() => cambiarBusqueda('')}
                                    className="absolute right-2.5 top-1/2 -translate-y-1/2 rounded-full p-0.5 text-gray-300 transition-colors hover:bg-gray-100 hover:text-gray-500"
                                    title="Limpiar búsqueda"
                                >
                                    <Icono tipo="cerrar" className="h-3.5 w-3.5" />
                                </button>
                            )}
                        </div>

                        <button
                            onClick={() => cambiarBusqueda('')}
                            disabled={busqueda === ''}
                            className="inline-flex items-center gap-2 rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-all hover:border-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                            title="Limpiar filtros"
                        >
                            <Icono tipo="embudo" className="h-4 w-4" />
                            Filtros
                        </button>

                        {seleccion.count > 0 && (
                            <button
                                onClick={copiarSeleccionadas}
                                className="inline-flex items-center gap-2 rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98]"
                            >
                                <Icono tipo="copiar" className="h-4 w-4" />
                                Copiar ({seleccion.count})
                            </button>
                        )}
                    </div>

                    <div className="overflow-hidden rounded-xl border border-gray-200/80 bg-white shadow-sm">
                        <div className="overflow-x-auto">
                            {tab === 'roster' ? (
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50/80">
                                        <tr>
                                            {['Materia', 'Grupo', 'Horas', 'Docente', 'Estado', 'Historial'].map((encabezado) => (
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
                                        {visibles.length === 0 && (
                                            <tr>
                                                <td colSpan={6}>
                                                    <EmptyState
                                                        titulo="Sin resultados"
                                                        subtitulo="Ningún grupo coincide con la búsqueda."
                                                    />
                                                </td>
                                            </tr>
                                        )}
                                        {visibles.map((fila, indice) => {
                                            const historial = historialPorGrupo[fila.id] ?? [];
                                            const esDirty = fila.id in cambios;
                                            const estadoBadge = badgesDesignacion[fila.designacion?.estado ?? 'sin_asignar'];
                                            const estaSeleccionada = seleccion.isSelected(String(fila.id));

                                            return (
                                                <tr
                                                    key={fila.id}
                                                    onClick={(e) => seleccion.toggle(String(fila.id), e.ctrlKey || e.metaKey)}
                                                    className={`fila-entra transition-colors cursor-pointer ${
                                                        estaSeleccionada
                                                            ? 'bg-blue-50 ring-1 ring-inset ring-blue-300'
                                                            : esDirty
                                                                ? 'bg-blue-50/40 hover:bg-blue-50/60'
                                                                : 'hover:bg-gray-50/60'
                                                    }`}
                                                    style={{ animationDelay: `${Math.min(indice * 30, 240)}ms` }}
                                                >
                                                    <td className="px-4 py-3.5">
                                                        <div className="flex items-center gap-3">
                                                            <span
                                                                className={`flex h-9 w-9 shrink-0 items-center justify-center rounded-lg text-sm font-semibold ring-1 ring-inset ${paletaIcono[fila.materia.id % paletaIcono.length]}`}
                                                            >
                                                                {fila.materia.sigla.charAt(0)}
                                                            </span>
                                                            <div>
                                                                <p className="font-medium text-gray-900">{fila.materia.nombre}</p>
                                                                <p className="mt-0.5 text-xs text-gray-400">{fila.materia.sigla}</p>
                                                            </div>
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-3.5 text-gray-600">{fila.codigo}</td>
                                                    <td className="px-4 py-3.5 text-gray-600 tabular-nums">{fila.horas}h</td>
                                                    <td className="px-4 py-3.5">
                                                        <ComboboxDocente
                                                            docentes={docentes}
                                                            value={valorDocente(fila)}
                                                            onChange={(valor) => cambiarDocente(fila, valor)}
                                                            carreraSigla={carrera.sigla}
                                                            materiaId={fila.materia.id}
                                                            carreraId={carrera.id}
                                                        />
                                                        {!esDirty && fila.aviso?.excedeLimite && (
                                                            <p className="mt-1.5 flex items-center gap-1 text-[11px] text-amber-700">
                                                                <Icono tipo="alerta" className="h-3 w-3 shrink-0" />
                                                                {fila.aviso.horasProyectadas}h en esta gestión — supera el límite de{' '}
                                                                {limiteHoras}h
                                                            </p>
                                                        )}
                                                        {!esDirty && fila.aviso?.hayChoque && (
                                                            <p className="mt-1.5 flex items-center gap-1 text-[11px] text-amber-700">
                                                                <Icono tipo="alerta" className="h-3 w-3 shrink-0" />
                                                                Este docente ya dicta otro grupo en este periodo
                                                            </p>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-3.5">
                                                        <Badge tono={estadoBadge.tono} icono={estadoBadge.icono}>
                                                            {estadoBadge.etiqueta}
                                                        </Badge>
                                                    </td>
                                                    <td className="px-4 py-3.5">
                                                        <button
                                                            onClick={(e) =>
                                                                setHistoAbierto(
                                                                    histoAbierto?.grupoId === fila.id
                                                                        ? null
                                                                        : { grupoId: fila.id, el: e.currentTarget }
                                                                )
                                                            }
                                                            disabled={historial.length === 0}
                                                            className="rounded-lg p-1.5 text-gray-400 transition-colors hover:bg-gray-100 hover:text-gray-700 disabled:cursor-not-allowed disabled:opacity-40"
                                                            title={historial.length === 0 ? 'Todavía no tiene historial' : 'Ver historial de docentes'}
                                                        >
                                                            <Icono tipo="reloj" className="h-[18px] w-[18px]" />
                                                        </button>

                                                        {histoAbierto?.grupoId === fila.id && (
                                                            <MenuFlotante anchorEl={histoAbierto.el} onClose={() => setHistoAbierto(null)} width={320}>
                                                                <div className="border-b border-gray-100 px-4 py-2.5">
                                                                    <p className="text-xs font-semibold text-gray-900">
                                                                        {fila.materia.nombre} · Grupo {fila.codigo}
                                                                    </p>
                                                                    <p className="mt-0.5 text-[11px] text-gray-400">
                                                                        Más reciente primero · clic para proponer
                                                                    </p>
                                                                </div>
                                                                <div className="max-h-72 overflow-y-auto p-1.5">
                                                                    {historial.map((h, i) => (
                                                                        <button
                                                                            key={i}
                                                                            type="button"
                                                                            onClick={() => cambiarDocente(fila, String(h.docente.id))}
                                                                            className="group flex w-full items-center gap-2.5 rounded-lg px-2.5 py-2 text-left transition-colors hover:bg-blue-50"
                                                                        >
                                                                            <span className="shrink-0 rounded-md bg-gray-50 px-2 py-1 text-[10px] font-semibold tabular-nums text-gray-500 ring-1 ring-inset ring-gray-200">
                                                                                {h.gestion}-{h.periodo}
                                                                            </span>
                                                                            <span className="min-w-0 flex-1 truncate text-xs font-medium text-gray-900">
                                                                                {h.docente.nombre}
                                                                            </span>
                                                                            <span className="shrink-0 text-[10px] font-semibold text-blue-700 opacity-0 transition-opacity group-hover:opacity-100">
                                                                                Proponer →
                                                                            </span>
                                                                        </button>
                                                                    ))}
                                                                </div>
                                                            </MenuFlotante>
                                                        )}
                                                    </td>
                                                </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            ) : (
                                <table className="min-w-full divide-y divide-gray-200 text-sm">
                                    <thead className="bg-gray-50/80">
                                        <tr>
                                            {['Docente', 'Materia', 'Grupo', 'Estado'].map((encabezado) => (
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
                                        {visibles.length === 0 && (
                                            <tr>
                                                <td colSpan={4}>
                                                    <EmptyState
                                                        titulo="Sin designaciones"
                                                        subtitulo="No hay designaciones registradas para este periodo."
                                                    />
                                                </td>
                                            </tr>
                                        )}
                                        {visibles.map((designacion, indice) => {
                                            const estaSeleccionada = seleccion.isSelected(String(designacion.id));
                                            return (
                                            <tr
                                                key={designacion.id}
                                                onClick={(e) => seleccion.toggle(String(designacion.id), e.ctrlKey || e.metaKey)}
                                                className={`fila-entra transition-colors cursor-pointer ${
                                                    estaSeleccionada
                                                        ? 'bg-blue-50 ring-1 ring-inset ring-blue-300'
                                                        : 'hover:bg-gray-50/60'
                                                }`}
                                                style={{ animationDelay: `${Math.min(indice * 30, 240)}ms` }}
                                            >
                                                <td className="px-4 py-3.5 font-medium text-gray-900">{designacion.docente.nombre}</td>
                                                <td className="px-4 py-3.5 text-gray-600">
                                                    {designacion.materia.sigla} — {designacion.materia.nombre}
                                                </td>
                                                <td className="px-4 py-3.5 text-gray-600">{designacion.grupo.codigo}</td>
                                                <td className="px-4 py-3.5">
                                                    <Badge
                                                        tono={badgesDesignacion[designacion.estado].tono}
                                                        icono={badgesDesignacion[designacion.estado].icono}
                                                    >
                                                        {badgesDesignacion[designacion.estado].etiqueta}
                                                    </Badge>
                                                </td>
                                            </tr>
                                            );
                                        })}
                                    </tbody>
                                </table>
                            )}
                        </div>

                        <div className="flex flex-wrap items-center justify-between gap-3 border-t border-gray-100 px-4 py-3">
                            <p className="text-[13px] text-gray-400 tabular-nums">
                                Mostrando <span className="font-medium text-gray-600">{desde}</span> a{' '}
                                <span className="font-medium text-gray-600">{hasta}</span> de{' '}
                                <span className="font-medium text-gray-600">{totalFilas}</span>{' '}
                                {tab === 'resumen' ? 'designaciones' : 'grupos'}
                            </p>
                            {totalPaginas > 1 && (
                                <div className="flex gap-1">
                                    <button
                                        onClick={() => setPagina(Math.max(paginaActual - 1, 1))}
                                        disabled={paginaActual === 1}
                                        className="flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 px-2 text-sm text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:border-transparent disabled:text-gray-300"
                                    >
                                        ‹
                                    </button>
                                    {Array.from({ length: totalPaginas }, (_, i) => i + 1).map((numero) => (
                                        <button
                                            key={numero}
                                            onClick={() => setPagina(numero)}
                                            className={`flex h-8 min-w-8 items-center justify-center rounded-lg px-2 text-sm transition-colors tabular-nums ${
                                                numero === paginaActual
                                                    ? 'bg-blue-900 font-medium text-white shadow-sm'
                                                    : 'border border-gray-200 text-gray-600 hover:border-gray-300 hover:bg-gray-50'
                                            }`}
                                        >
                                            {numero}
                                        </button>
                                    ))}
                                    <button
                                        onClick={() => setPagina(Math.min(paginaActual + 1, totalPaginas))}
                                        disabled={paginaActual === totalPaginas}
                                        className="flex h-8 min-w-8 items-center justify-center rounded-lg border border-gray-200 px-2 text-sm text-gray-600 transition-colors hover:bg-gray-50 disabled:cursor-not-allowed disabled:border-transparent disabled:text-gray-300"
                                    >
                                        ›
                                    </button>
                                </div>
                            )}
                        </div>
                    </div>

                    {tab === 'roster' && cantidadCambios > 0 && (
                        <div className="sticky bottom-4 mt-4 flex items-center justify-between rounded-xl border border-gray-200 bg-white px-4 py-3 shadow-lg shadow-gray-200/60">
                            <p className="text-sm text-gray-600">
                                <span className="font-semibold text-gray-900 tabular-nums">{cantidadCambios}</span>{' '}
                                {cantidadCambios === 1 ? 'cambio sin guardar' : 'cambios sin guardar'}
                            </p>
                            <div className="flex gap-2">
                                <button
                                    onClick={descartarCambios}
                                    disabled={guardando}
                                    className="rounded-lg border border-gray-200 bg-white px-3 py-2 text-sm font-medium text-gray-600 shadow-sm transition-colors hover:border-gray-300 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    Descartar
                                </button>
                                <button
                                    onClick={guardarCambios}
                                    disabled={guardando}
                                    className="rounded-lg bg-blue-900 px-4 py-2 text-sm font-medium text-white shadow-sm transition-colors hover:bg-blue-800 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40"
                                >
                                    {guardando ? 'Guardando…' : 'Guardar cambios'}
                                </button>
                            </div>
                        </div>
                    )}
                </div>

                <aside className="w-full shrink-0 space-y-5 xl:w-80">
                    <div className="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm">
                        <h3 className="font-semibold tracking-tight text-gray-900">Progreso de designación</h3>
                        <div className="mt-4 flex items-center gap-4">
                            <AnilloProgreso asignadas={asignadas} porAsignar={porAsignar} sinGrupos={sinGrupos} />
                            <div>
                                <p className="text-lg font-semibold tracking-tight text-gray-900 tabular-nums">
                                    {asignadas} / {materias.length}
                                </p>
                                <p className="text-sm text-gray-500">Materias asignadas</p>
                            </div>
                        </div>
                        <ul className="mt-4 space-y-1">
                            {leyendaProgreso.map((fila) => (
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
                                        <span className="font-normal text-gray-400">({pctTotal(fila.cantidad)}%)</span>
                                    </span>
                                </li>
                            ))}
                        </ul>
                    </div>

                    <div className="rounded-xl border border-gray-200/80 bg-white p-5 shadow-sm">
                        <h3 className="mb-3 font-semibold tracking-tight text-gray-900">Información de la carrera</h3>
                        <p className="mb-3 text-sm font-medium text-gray-900">{carrera.nombre}</p>
                        <dl className="space-y-2.5">
                            {infoCarrera.map((fila) => (
                                <div key={fila.etiqueta} className="flex items-center justify-between text-sm">
                                    <dt className="text-gray-500">{fila.etiqueta}</dt>
                                    <dd className="font-medium text-gray-900 tabular-nums">{fila.valor}</dd>
                                </div>
                            ))}
                        </dl>
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
