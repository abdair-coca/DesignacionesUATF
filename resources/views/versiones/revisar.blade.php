@extends('layouts.app')

@section('title', 'Revisar versión')

@section('content')
    <div class="max-w-7xl mx-auto space-y-3">
        <div class="flex items-start justify-between gap-3">
            <div class="min-w-0">
                <a href="{{ route('revisiones.pendientes') }}" class="text-sm text-[#007c7c] hover:underline">Volver a la bandeja</a>
                <h1 class="text-xl font-bold text-gray-900 mt-1 truncate">{{ $version->propuesta->carrera->nombre }} · Revisión {{ $version->numero }}</h1>
                <p class="text-sm text-gray-600 mt-1 truncate">Enviada por {{ $version->remitente->name }} el {{ $version->enviado_en?->format('d/m/Y H:i') }}.</p>
                @if(filled($version->propuesta->descripcion))
                    <p class="text-sm text-gray-700 mt-1 truncate"><span class="font-semibold">Descripci&oacute;n:</span> {{ $version->propuesta->descripcion }}</p>
                @endif
            </div>
            <button type="button" onclick="abrirModalImprimirRevision()" class="shrink-0 px-3 py-1.5 bg-gray-700 hover:bg-gray-800 text-white font-bold rounded text-xs cursor-pointer">Imprimir Reporte</button>
        </div>

        @if($version->observaciones)
            <div class="border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">{{ $version->observaciones }}</div>
        @endif

        @if($puedeDecidir)
            @if($errors->any())
                <div class="border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900" role="alert">
                    {{ $errors->first() }}
                </div>
            @endif

            <form id="revision-form" data-revision-form data-has-old-input="{{ old('decisiones') ? '1' : '0' }}" method="POST" action="{{ route('revisiones.decidir', $version) }}" class="space-y-3" onsubmit="return validarRevisionAntesDeEnviar(event)">
                @csrf
                <section class="bg-white border border-gray-200 shadow-sm p-1.5 rounded-lg">
                    <div class="flex items-center gap-2">
                        <label for="observacion_general" class="shrink-0 text-xs font-semibold text-gray-900">Observación general</label>
                        <textarea id="observacion_general" name="observacion_general" rows="1" maxlength="2000" class="w-full border border-gray-300 px-1.5 py-1 text-xs" placeholder="Visible para el Director cuando la revisión sea observada.">{{ old('observacion_general') }}</textarea>
                    </div>
                </section>

                <section data-revision-paginada class="bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
                    <div class="bg-[#2d353c] text-white px-3 py-1.5 font-bold text-[11px] flex justify-between items-center">
                        <span>Snapshot de Designaciones Enviadas &bull; Versión {{ $version->numero }}</span>
                        <label class="flex items-center gap-2 bg-[#20252a] px-3 py-1 rounded text-white font-bold text-xs cursor-pointer hover:bg-black/30 transition-colors">
                            <input id="aprobar_todas_filas" type="checkbox" checked onchange="actualizarModoRevision(this)" class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac] scale-90">
                            <span class="text-[11px]">Aprobar todas las filas</span>
                        </label>
                    </div>
                    <div class="overflow-hidden">
                    <table class="w-full table-fixed text-[10px] leading-[10px]">
                        <colgroup>
                            <col style="width: 13%"><col style="width: 14%"><col style="width: 5%">
                            <col style="width: 6%"><col style="width: 6%"><col style="width: 7%"><col style="width: 7%">
                            <col style="width: 14%"><col style="width: 12%"><col style="width: 16%">
                        </colgroup>
                        <thead class="bg-gray-50 text-left text-[9px] uppercase text-gray-600">
                            <tr>
                                <th class="px-1 py-1 align-middle break-words">Docente</th><th class="px-1 py-1 align-middle break-words">Materia</th><th class="px-1 py-1 align-middle break-words">Grupo</th>
                                <th class="px-1 py-1 align-middle break-words">Oficiales</th><th class="px-1 py-1 align-middle break-words">Pagadas</th><th class="px-1 py-1 align-middle break-words">No pagadas</th><th class="px-1 py-1 align-middle break-words">Adicionales</th><th class="px-1 py-1 align-middle break-words">Justificación de remuneración</th>
                                <th class="px-1 py-1 align-middle break-words">Decisión</th><th class="px-1 py-1 align-middle break-words">Observación por fila</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($version->designaciones as $indice => $snapshot)
                                @php($adicionales = max(0, $snapshot->horas_pagadas + $snapshot->horas_no_pagadas - $snapshot->materia_horas))
                                @php($decisionAnterior = old("decisiones.{$indice}.decision", 'aprobada'))
                                @php($observacionAnterior = old("decisiones.{$indice}.observacion"))
                                <tr data-revision-row>
                                    <td class="px-1 py-1 align-top break-words">{{ $snapshot->docente_nombre }}</td>
                                    <td class="px-1 py-1 align-top break-words"><span class="font-semibold">{{ $snapshot->materia_sigla }}</span> {{ $snapshot->materia_nombre }}</td>
                                    <td class="px-1 py-1 align-top text-center break-words">{{ $snapshot->grupo_codigo }}</td>
                                    <td class="px-1 py-1 align-top text-center whitespace-nowrap">{{ $snapshot->materia_horas }} h</td>
                                    <td class="px-1 py-1 align-top text-center text-emerald-700 whitespace-nowrap">{{ $snapshot->horas_pagadas }} h</td>
                                    <td class="px-1 py-1 align-top text-center text-amber-700 whitespace-nowrap">{{ $snapshot->horas_no_pagadas }} h</td>
                                    <td class="px-1 py-1 align-top text-center font-semibold whitespace-nowrap {{ $adicionales ? 'text-rose-700' : 'text-gray-500' }}">{{ $adicionales }} h</td>
                                    <td data-justificacion-remuneracion class="px-1 py-1 align-top whitespace-pre-line break-words">{{ filled($snapshot->observacion_remuneracion) ? $snapshot->observacion_remuneracion : '—' }}</td>
                                    @if($snapshot->estado === 'aprobada_previamente')
                                        <td colspan="2" class="px-1 py-1 align-top"><span class="bg-emerald-100 text-emerald-900 text-[9px] font-semibold px-1 py-0.5">Aprobada previamente</span></td>
                                    @else
                                        <td class="px-1 py-1 align-top">
                                            <input type="hidden" name="decisiones[{{ $indice }}][snapshot_id]" value="{{ $snapshot->id }}">
                                            <select data-decision-fila name="decisiones[{{ $indice }}][decision]" class="w-full border border-gray-300 px-1 py-0.5 rounded text-[10px]" onchange="sincronizarObservacionFila(this, true)">
                                                <option value="aprobada" @selected($decisionAnterior === 'aprobada')>Aprobar</option>
                                                <option value="observada" @selected($decisionAnterior === 'observada')>Observar</option>
                                            </select>
                                        </td>
                                        <td class="px-1 py-1 align-top"><input data-observacion-fila name="decisiones[{{ $indice }}][observacion]" maxlength="1000" value="{{ $observacionAnterior }}" @disabled($decisionAnterior !== 'observada') class="w-full border border-gray-300 px-1 py-0.5 disabled:bg-gray-100 disabled:text-gray-400 text-[10px]" placeholder="Motivo si se observa"></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                    <div data-revision-pagination class="flex items-center justify-between gap-2 border-t border-gray-100 bg-gray-50 px-2 py-1 text-[10px] text-gray-500">
                        <span data-revision-page-status></span>
                        <div class="flex items-center gap-1">
                            <button type="button" data-revision-first class="rounded border border-gray-300 bg-white px-1.5 py-0.5 font-semibold hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">Primera</button>
                            <button type="button" data-revision-previous class="rounded border border-gray-300 bg-white px-1.5 py-0.5 font-semibold hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">Anterior</button>
                            <button type="button" data-revision-next class="rounded border border-gray-300 bg-white px-1.5 py-0.5 font-semibold hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">Siguiente</button>
                            <button type="button" data-revision-last class="rounded border border-gray-300 bg-white px-1.5 py-0.5 font-semibold hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">Última</button>
                        </div>
                    </div>
                </section>

                <div class="flex flex-wrap justify-end gap-3">
                    <input type="hidden" id="modo_revision" name="modo" value="decidir_filas">
                    <button type="submit" class="bg-[#00acac] hover:bg-[#008a8a] text-white font-bold px-6 py-1.5 text-xs rounded shadow-md transition-colors cursor-pointer flex items-center gap-2">
                        <span>Confirmar Revisión</span>
                    </button>
                </div>
            </form>

            <div id="modal-error-revision" hidden class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modal-error-revision-title">
                <div class="bg-white rounded-lg shadow-2xl border border-rose-200 w-full max-w-md overflow-hidden">
                    <div class="bg-rose-700 text-white px-5 py-3.5 flex items-center justify-between">
                        <h2 id="modal-error-revision-title" class="font-bold text-sm">No se puede enviar la revisión</h2>
                        <button type="button" onclick="cerrarModalErrorRevision()" class="text-rose-100 hover:text-white" aria-label="Cerrar">&times;</button>
                    </div>
                    <div class="p-6 flex items-start gap-3 text-sm text-gray-700">
                        <svg class="w-6 h-6 text-rose-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v3m0 4h.01M5.25 19.5h13.5a1.5 1.5 0 001.3-2.25l-6.75-12a1.5 1.5 0 00-2.6 0l-6.75 12a1.5 1.5 0 001.3 2.25z" />
                        </svg>
                        <p id="modal-error-revision-message">El motivo es obligatorio para cada fila observada, o puedes escribir una observación general.</p>
                    </div>
                    <div class="bg-gray-50 border-t border-gray-200 px-5 py-3 flex justify-end">
                        <button type="button" onclick="cerrarModalErrorRevision()" class="px-5 py-2 bg-rose-700 hover:bg-rose-800 text-white rounded text-xs font-bold">Entendido</button>
                    </div>
                </div>
            </div>

            <div id="modal-imprimir-revision" hidden class="hidden fixed inset-0 z-50 bg-black/60 backdrop-blur-xs flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-labelledby="modal-imprimir-revision-title">
                <div class="bg-white rounded-lg shadow-2xl border border-gray-300 w-full max-w-md overflow-hidden">
                    <div class="bg-[#2d353c] text-white px-5 py-3.5 flex items-center justify-between">
                        <h2 id="modal-imprimir-revision-title" class="font-bold text-sm">Imprimir reporte</h2>
                        <button type="button" onclick="cerrarModalImprimirRevision()" class="text-gray-300 hover:text-white" aria-label="Cerrar">&times;</button>
                    </div>
                    <div class="p-5 text-sm text-gray-700">
                        <p class="font-semibold text-gray-900">Reporte de la revisión</p>
                        <p class="mt-2">La generación del reporte para impresión estará disponible próximamente.</p>
                    </div>
                    <div class="bg-gray-50 border-t border-gray-200 px-5 py-3 flex justify-end">
                        <button type="button" onclick="cerrarModalImprimirRevision()" class="px-4 py-1.5 bg-gray-700 hover:bg-gray-800 text-white rounded text-xs font-bold">Cerrar</button>
                    </div>
                </div>
            </div>
        @else
            <section data-revision-paginada class="bg-white border border-gray-200 shadow-sm overflow-hidden">
                <table class="w-full table-fixed text-[10px] leading-[10px]">
                    <colgroup>
                        <col style="width: 13%"><col style="width: 14%"><col style="width: 5%">
                        <col style="width: 6%"><col style="width: 6%"><col style="width: 7%"><col style="width: 7%">
                        <col style="width: 14%"><col style="width: 12%"><col style="width: 16%">
                    </colgroup>
                    <thead class="bg-gray-50 text-left text-[9px] uppercase text-gray-600">
                        <tr>
                            <th class="px-1 py-1 align-middle break-words">Docente</th><th class="px-1 py-1 align-middle break-words">Materia</th><th class="px-1 py-1 align-middle break-words">Grupo</th>
                            <th class="px-1 py-1 align-middle break-words">Oficiales</th><th class="px-1 py-1 align-middle break-words">Pagadas</th><th class="px-1 py-1 align-middle break-words">No pagadas</th><th class="px-1 py-1 align-middle break-words">Adicionales</th><th class="px-1 py-1 align-middle break-words">Justificación de remuneración</th>
                            <th class="px-1 py-1 align-middle break-words">Decisión</th><th class="px-1 py-1 align-middle break-words">Observación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($version->designaciones as $snapshot)
                            @php($adicionales = max(0, $snapshot->horas_pagadas + $snapshot->horas_no_pagadas - $snapshot->materia_horas))
                            <tr data-revision-row>
                                <td class="px-1 py-1 align-top break-words">{{ $snapshot->docente_nombre }}</td>
                                <td class="px-1 py-1 align-top break-words">{{ $snapshot->materia_sigla }} {{ $snapshot->materia_nombre }}</td>
                                <td class="px-1 py-1 align-top text-center break-words">{{ $snapshot->grupo_codigo }}</td>
                                <td class="px-1 py-1 align-top text-center whitespace-nowrap">{{ $snapshot->materia_horas }} h</td>
                                <td class="px-1 py-1 align-top text-center text-emerald-700 whitespace-nowrap">{{ $snapshot->horas_pagadas }} h</td>
                                <td class="px-1 py-1 align-top text-center text-amber-700 whitespace-nowrap">{{ $snapshot->horas_no_pagadas }} h</td>
                                <td class="px-1 py-1 align-top text-center whitespace-nowrap">{{ $adicionales }} h</td>
                                <td data-justificacion-remuneracion class="px-1 py-1 align-top whitespace-pre-line break-words">{{ filled($snapshot->observacion_remuneracion) ? $snapshot->observacion_remuneracion : '—' }}</td>
                                <td class="px-1 py-1 align-top break-words">{{ $snapshot->getRelation('decision')?->getAttribute('decision') ?: ($snapshot->estado === 'aprobada_previamente' ? 'aprobada_previamente' : 'Sin decisión') }}</td>
                                <td class="px-1 py-1 align-top break-words">{{ $snapshot->getRelation('decision')?->getAttribute('observacion') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div data-revision-pagination class="flex items-center justify-between gap-2 border-t border-gray-100 bg-gray-50 px-2 py-1 text-[10px] text-gray-500">
                    <span data-revision-page-status></span>
                    <div class="flex items-center gap-1">
                        <button type="button" data-revision-first class="rounded border border-gray-300 bg-white px-1.5 py-0.5 font-semibold hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">Primera</button>
                        <button type="button" data-revision-previous class="rounded border border-gray-300 bg-white px-1.5 py-0.5 font-semibold hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">Anterior</button>
                        <button type="button" data-revision-next class="rounded border border-gray-300 bg-white px-1.5 py-0.5 font-semibold hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">Siguiente</button>
                        <button type="button" data-revision-last class="rounded border border-gray-300 bg-white px-1.5 py-0.5 font-semibold hover:bg-gray-100 disabled:cursor-not-allowed disabled:opacity-40">Última</button>
                    </div>
                </div>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function validarRevisionAntesDeEnviar(event) {
        const formulario = event.currentTarget;
        const observacionGeneral = formulario.querySelector('#observacion_general')?.value.trim() || '';
        const faltaMotivo = [...formulario.querySelectorAll('[data-decision-fila]')].some((select) => {
            if (select.value !== 'observada') return false;

            const observacion = select.closest('tr')?.querySelector('[data-observacion-fila]')?.value.trim() || '';

            return observacion === '' && observacionGeneral === '';
        });

        if (!faltaMotivo) return true;

        event.preventDefault();
        abrirModalErrorRevision();

        return false;
    }

    function abrirModalErrorRevision() {
        const modal = document.getElementById('modal-error-revision');

        if (!modal) return;

        modal.hidden = false;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarModalErrorRevision() {
        const modal = document.getElementById('modal-error-revision');

        if (!modal) return;

        modal.hidden = true;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    function abrirModalImprimirRevision() {
        const modal = document.getElementById('modal-imprimir-revision');

        if (!modal) return;

        modal.hidden = false;
        modal.classList.remove('hidden');
        document.body.classList.add('overflow-hidden');
    }

    function cerrarModalImprimirRevision() {
        const modal = document.getElementById('modal-imprimir-revision');

        if (!modal) return;

        modal.hidden = true;
        modal.classList.add('hidden');
        document.body.classList.remove('overflow-hidden');
    }

    document.addEventListener('keydown', (event) => {
        if (event.key === 'Escape') cerrarModalErrorRevision();
    });

    function inicializarPaginacionRevision() {
        document.querySelectorAll('[data-revision-paginada]').forEach((contenedor) => {
            const filas = [...contenedor.querySelectorAll('[data-revision-row]')];
            const paginacion = contenedor.querySelector('[data-revision-pagination]');
            const estado = contenedor.querySelector('[data-revision-page-status]');
            const primera = contenedor.querySelector('[data-revision-first]');
            const anterior = contenedor.querySelector('[data-revision-previous]');
            const siguiente = contenedor.querySelector('[data-revision-next]');
            const ultima = contenedor.querySelector('[data-revision-last]');
            const porPagina = 10;
            const totalPaginas = Math.max(1, Math.ceil(filas.length / porPagina));
            let paginaActual = 1;

            if (!paginacion || !estado || !primera || !anterior || !siguiente || !ultima) return;

            const actualizar = () => {
                const inicio = (paginaActual - 1) * porPagina;
                const fin = inicio + porPagina;

                filas.forEach((fila, indice) => {
                    fila.hidden = indice < inicio || indice >= fin;
                });

                estado.textContent = `Filas ${filas.length ? inicio + 1 : 0}-${Math.min(fin, filas.length)} de ${filas.length}`;
                primera.disabled = paginaActual === 1;
                anterior.disabled = paginaActual === 1;
                siguiente.disabled = paginaActual === totalPaginas;
                ultima.disabled = paginaActual === totalPaginas;
                paginacion.hidden = totalPaginas === 1;
            };

            primera.addEventListener('click', () => {
                paginaActual = 1;
                actualizar();
            });
            anterior.addEventListener('click', () => {
                paginaActual = Math.max(1, paginaActual - 1);
                actualizar();
            });
            siguiente.addEventListener('click', () => {
                paginaActual = Math.min(totalPaginas, paginaActual + 1);
                actualizar();
            });
            ultima.addEventListener('click', () => {
                paginaActual = totalPaginas;
                actualizar();
            });

            actualizar();
        });
    }

    document.addEventListener('DOMContentLoaded', () => {
        inicializarPaginacionRevision();
        const formulario = document.querySelector('[data-revision-form]');

        if (!formulario || formulario.dataset.hasOldInput !== '1') return;

        formulario.querySelectorAll('[data-decision-fila]').forEach((select) => sincronizarObservacionFila(select, false));
    });

    function sincronizarObservacionFila(select, cambioManual = true) {
        const formulario = select.closest('form');
        const checkbox = formulario?.querySelector('#aprobar_todas_filas');

        if (cambioManual && checkbox?.checked) checkbox.checked = false;

        const aprobarTodas = formulario?.querySelector('#aprobar_todas_filas')?.checked ?? false;
        const observacion = select.closest('tr')?.querySelector('[data-observacion-fila]');

        if (!observacion) return;

        observacion.disabled = aprobarTodas || select.value !== 'observada';
        if (observacion.disabled && select.value !== 'observada') observacion.value = '';
    }

    function actualizarModoRevision(checkbox) {
        const formulario = checkbox.closest('form');
        const modo = formulario?.querySelector('#modo_revision');

        if (!formulario || !modo) return;

        modo.value = 'decidir_filas';
        formulario.querySelectorAll('[data-decision-fila]').forEach((select) => {
            select.value = checkbox.checked ? 'aprobada' : 'observada';
            sincronizarObservacionFila(select, false);
        });
    }
</script>
@endpush
