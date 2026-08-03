@extends('layouts.app')

@section('title', 'Revisar versión')

@section('content')
    <div class="max-w-7xl mx-auto space-y-6">
        <div>
            <a href="{{ route('revisiones.pendientes') }}" class="text-sm text-[#007c7c] hover:underline">Volver a la bandeja</a>
            <h1 class="text-xl font-bold text-gray-900 mt-2">{{ $version->propuesta->carrera->nombre }} · Revisión {{ $version->numero }}</h1>
            <p class="text-sm text-gray-600 mt-1">Enviada por {{ $version->remitente->name }} el {{ $version->enviado_en?->format('d/m/Y H:i') }}.</p>
        </div>

        @if($version->observaciones)
            <div class="border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">{{ $version->observaciones }}</div>
        @endif

        @if($puedeDecidir)
            <form method="POST" action="{{ route('revisiones.decidir', $version) }}" class="space-y-5">
                @csrf
                <section class="bg-white border border-gray-200 shadow-sm p-4 rounded-lg">
                    <label for="observacion_general" class="block text-sm font-semibold text-gray-900">Observación general</label>
                    <textarea id="observacion_general" name="observacion_general" rows="3" maxlength="2000" class="w-full mt-2 border border-gray-300 p-2 text-sm" placeholder="Visible para el Director cuando la revisión sea observada."></textarea>
                </section>

                <section class="bg-white border border-gray-200 shadow-sm rounded-lg overflow-hidden">
                    <div class="bg-[#2d353c] text-white px-4 py-2.5 font-bold text-xs flex justify-between items-center">
                        <span>Snapshot de Designaciones Enviadas &bull; Versión {{ $version->numero }}</span>
                        <label class="flex items-center gap-2 bg-[#20252a] px-3 py-1 rounded text-white font-bold text-xs cursor-pointer hover:bg-black/30 transition-colors">
                            <input id="aprobar_todas_filas" type="checkbox" checked onchange="actualizarModoRevision(this)" class="rounded border-gray-300 text-[#00acac] focus:ring-[#00acac]">
                            <span>Aprobar todas las filas</span>
                        </label>
                    </div>
                    <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Docente</th><th class="px-4 py-3">Materia</th><th class="px-4 py-3">Grupo</th>
                                <th class="px-4 py-3">Oficiales</th><th class="px-4 py-3">Pagadas</th><th class="px-4 py-3">No pagadas</th><th class="px-4 py-3">Adicionales</th>
                                <th class="px-4 py-3">Decisión</th><th class="px-4 py-3">Observación por fila</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($version->designaciones as $indice => $snapshot)
                                @php($adicionales = max(0, $snapshot->horas_pagadas + $snapshot->horas_no_pagadas - $snapshot->materia_horas))
                                <tr>
                                    <td class="px-4 py-3">{{ $snapshot->docente_nombre }}</td>
                                    <td class="px-4 py-3"><span class="font-semibold">{{ $snapshot->materia_sigla }}</span> {{ $snapshot->materia_nombre }}</td>
                                    <td class="px-4 py-3">{{ $snapshot->grupo_codigo }}</td>
                                    <td class="px-4 py-3 text-center">{{ $snapshot->materia_horas }} h</td>
                                    <td class="px-4 py-3 text-center text-emerald-700">{{ $snapshot->horas_pagadas }} h</td>
                                    <td class="px-4 py-3 text-center text-amber-700">{{ $snapshot->horas_no_pagadas }} h</td>
                                    <td class="px-4 py-3 text-center font-semibold {{ $adicionales ? 'text-rose-700' : 'text-gray-500' }}">{{ $adicionales }} h</td>
                                    @if($snapshot->estado === 'aprobada_previamente')
                                        <td colspan="2" class="px-4 py-3"><span class="bg-emerald-100 text-emerald-900 text-xs font-semibold px-2 py-1">Aprobada previamente</span></td>
                                    @else
                                        <td class="px-4 py-3 min-w-40">
                                            <input type="hidden" name="decisiones[{{ $indice }}][snapshot_id]" value="{{ $snapshot->id }}">
                                            <select data-decision-fila name="decisiones[{{ $indice }}][decision]" class="w-full border border-gray-300 px-2 py-1.5 rounded" onchange="sincronizarObservacionFila(this)">
                                                <option value="aprobada">Aprobar</option>
                                                <option value="observada">Observar</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 min-w-72"><input data-observacion-fila name="decisiones[{{ $indice }}][observacion]" maxlength="1000" disabled class="w-full border border-gray-300 px-2 py-1.5 disabled:bg-gray-100 disabled:text-gray-400" placeholder="Motivo si se observa"></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    </div>
                </section>

                <div class="flex flex-wrap justify-end gap-3">
                    <input type="hidden" id="modo_revision" name="modo" value="decidir_filas">
                    <button type="submit" class="bg-[#00acac] hover:bg-[#008a8a] text-white font-bold px-6 py-2.5 text-xs rounded shadow-md transition-colors cursor-pointer flex items-center gap-2">
                        <span>Confirmar Revisión</span>
                    </button>
                </div>
            </form>
        @else
            <section class="bg-white border border-gray-200 shadow-sm overflow-x-auto">
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600">
                        <tr>
                            <th class="px-4 py-3">Docente</th><th class="px-4 py-3">Materia</th><th class="px-4 py-3">Grupo</th>
                            <th class="px-4 py-3">Oficiales</th><th class="px-4 py-3">Pagadas</th><th class="px-4 py-3">No pagadas</th><th class="px-4 py-3">Adicionales</th>
                            <th class="px-4 py-3">Decisión</th><th class="px-4 py-3">Observación</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($version->designaciones as $snapshot)
                            @php($adicionales = max(0, $snapshot->horas_pagadas + $snapshot->horas_no_pagadas - $snapshot->materia_horas))
                            <tr>
                                <td class="px-4 py-3">{{ $snapshot->docente_nombre }}</td>
                                <td class="px-4 py-3">{{ $snapshot->materia_sigla }} {{ $snapshot->materia_nombre }}</td>
                                <td class="px-4 py-3">{{ $snapshot->grupo_codigo }}</td>
                                <td class="px-4 py-3 text-center">{{ $snapshot->materia_horas }} h</td>
                                <td class="px-4 py-3 text-center text-emerald-700">{{ $snapshot->horas_pagadas }} h</td>
                                <td class="px-4 py-3 text-center text-amber-700">{{ $snapshot->horas_no_pagadas }} h</td>
                                <td class="px-4 py-3 text-center">{{ $adicionales }} h</td>
                                <td class="px-4 py-3">{{ $snapshot->getRelation('decision')?->getAttribute('decision') ?: ($snapshot->estado === 'aprobada_previamente' ? 'aprobada_previamente' : 'Sin decisión') }}</td>
                                <td class="px-4 py-3">{{ $snapshot->getRelation('decision')?->getAttribute('observacion') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    </div>
@endsection

@push('scripts')
<script>
    function sincronizarObservacionFila(select) {
        const formulario = select.closest('form');
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
            sincronizarObservacionFila(select);
        });
    }
</script>
@endpush
