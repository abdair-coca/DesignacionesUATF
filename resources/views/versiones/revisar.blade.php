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
                <section class="bg-white border border-gray-200 shadow-sm p-4">
                    <label for="observacion_general" class="block text-sm font-semibold text-gray-900">Observación general</label>
                    <textarea id="observacion_general" name="observacion_general" rows="3" maxlength="2000" class="w-full mt-2 border border-gray-300 p-2 text-sm" placeholder="Visible para el Director cuando la revisión sea observada."></textarea>
                </section>

                <section class="bg-white border border-gray-200 shadow-sm overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600"><tr><th class="px-4 py-3">Docente</th><th class="px-4 py-3">Materia</th><th class="px-4 py-3">Grupo</th><th class="px-4 py-3">Decisión</th><th class="px-4 py-3">Observación por fila</th></tr></thead>
                        <tbody class="divide-y divide-gray-100">
                            @foreach($version->designaciones as $indice => $snapshot)
                                <tr>
                                    <td class="px-4 py-3">{{ $snapshot->docente_nombre }}</td>
                                    <td class="px-4 py-3"><span class="font-semibold">{{ $snapshot->materia_sigla }}</span> {{ $snapshot->materia_nombre }}</td>
                                    <td class="px-4 py-3">{{ $snapshot->grupo_codigo }}</td>
                                    @if($snapshot->estado === 'aprobada_previamente')
                                        <td colspan="2" class="px-4 py-3"><span class="bg-emerald-100 text-emerald-900 text-xs font-semibold px-2 py-1">Aprobada previamente</span></td>
                                    @else
                                        <td class="px-4 py-3 min-w-40">
                                            <input type="hidden" name="decisiones[{{ $indice }}][snapshot_id]" value="{{ $snapshot->id }}">
                                            <select name="decisiones[{{ $indice }}][decision]" class="w-full border border-gray-300 px-2 py-1.5">
                                                <option value="aprobada">Aprobar</option>
                                                <option value="observada">Observar</option>
                                            </select>
                                        </td>
                                        <td class="px-4 py-3 min-w-72"><input name="decisiones[{{ $indice }}][observacion]" maxlength="1000" class="w-full border border-gray-300 px-2 py-1.5" placeholder="Motivo si se observa"></td>
                                    @endif
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </section>

                <div class="flex flex-wrap justify-end gap-3">
                    <button type="submit" name="modo" value="decidir_filas" class="bg-amber-600 text-white px-4 py-2 text-sm font-semibold hover:bg-amber-700">Registrar decisiones por fila</button>
                    <button type="submit" name="modo" value="aprobar_todo" class="bg-[#00acac] text-white px-4 py-2 text-sm font-semibold hover:bg-[#008a8a]">Aprobar revisión completa</button>
                </div>
            </form>
        @else
            <section class="bg-white border border-gray-200 shadow-sm overflow-x-auto">
                <table class="w-full text-sm"><thead class="bg-gray-50 text-left text-xs uppercase text-gray-600"><tr><th class="px-4 py-3">Docente</th><th class="px-4 py-3">Materia</th><th class="px-4 py-3">Grupo</th><th class="px-4 py-3">Decisión</th><th class="px-4 py-3">Observación</th></tr></thead><tbody class="divide-y divide-gray-100">
                    @foreach($version->designaciones as $snapshot)
                        <tr><td class="px-4 py-3">{{ $snapshot->docente_nombre }}</td><td class="px-4 py-3">{{ $snapshot->materia_sigla }} {{ $snapshot->materia_nombre }}</td><td class="px-4 py-3">{{ $snapshot->grupo_codigo }}</td><td class="px-4 py-3">{{ $snapshot->decision?->decision ?: ($snapshot->estado === 'aprobada_previamente' ? 'aprobada_previamente' : 'Sin decisión') }}</td><td class="px-4 py-3">{{ $snapshot->decision?->observacion }}</td></tr>
                    @endforeach
                </tbody></table>
            </section>
        @endif
    </div>
@endsection
