@extends('layouts.app')

@section('title', 'Importar designaciones')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <div>
            <a href="{{ route('propuestas.editar', $propuesta) }}" class="text-sm text-[#007c7c] hover:underline">Volver al borrador</a>
            <h1 class="text-xl font-bold text-gray-900 mt-2">Importar designaciones historicas</h1>
            <p class="text-sm text-gray-600 mt-1">{{ $propuesta->carrera->nombre }} - Destino: {{ $propuesta->gestion->nombre }} / {{ $propuesta->periodo->nombre }}</p>
        </div>

        @if($errors->any())
            <div class="border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900"><ul class="list-disc pl-5 space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif

        <section class="bg-white border border-gray-200 p-5 shadow-sm">
            <form method="POST" action="{{ route('propuestas.importar.previsualizar', $propuesta) }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_auto] gap-3 items-end">
                @csrf
                <div>
                    <label for="origen_gestion_id" class="block text-xs font-semibold text-gray-700 mb-1">Gestion de origen</label>
                    <select id="origen_gestion_id" name="origen_gestion_id" required class="w-full border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Seleccione una gestion</option>
                        @foreach($gestiones as $gestion)
                            <option value="{{ $gestion->id }}" @selected(old('origen_gestion_id', $origenGestion->id ?? null) == $gestion->id)>{{ $gestion->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label for="origen_periodo_id" class="block text-xs font-semibold text-gray-700 mb-1">Periodo de origen</label>
                    <select id="origen_periodo_id" name="origen_periodo_id" required class="w-full border border-gray-300 px-3 py-2 text-sm">
                        <option value="">Seleccione un periodo</option>
                        @foreach($periodos as $periodo)
                            <option value="{{ $periodo->id }}" @selected(old('origen_periodo_id', $origenPeriodo->id ?? null) == $periodo->id)>{{ $periodo->nombre }}</option>
                        @endforeach
                    </select>
                </div>
                <button type="submit" class="bg-[#348fe2] text-white px-4 py-2 text-sm font-semibold hover:bg-[#2a72b5]">Previsualizar</button>
            </form>
        </section>

        @if($previsualizacion !== null)
            <section class="bg-white border border-gray-200 shadow-sm overflow-x-auto">
                <div class="px-4 py-3 border-b border-gray-200 flex justify-between gap-4 items-center">
                    <h2 class="font-bold text-sm text-gray-900">Cambios a aplicar</h2>
                    <span class="text-xs text-gray-600">{{ $previsualizacion->count() }} filas encontradas</span>
                </div>
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600"><tr><th class="px-4 py-3">Materia</th><th class="px-4 py-3">Grupo</th><th class="px-4 py-3">Docente origen</th><th class="px-4 py-3">Impacto</th></tr></thead>
                    <tbody class="divide-y divide-gray-100">
                        @forelse($previsualizacion as $fila)
                            <tr class="{{ $fila['importable'] ? '' : 'bg-gray-50 text-gray-500' }}">
                                <td class="px-4 py-3"><span class="font-semibold">{{ $fila['materia_sigla'] }}</span> {{ $fila['materia_nombre'] }}</td>
                                <td class="px-4 py-3">{{ $fila['grupo_codigo'] }}</td>
                                <td class="px-4 py-3">{{ $fila['docente_nombre'] }}</td>
                                <td class="px-4 py-3">{{ $fila['impacto'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">No hay filas importables para este origen.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                @if($previsualizacion->contains('importable', true))
                    <form method="POST" action="{{ route('propuestas.importar.aplicar', $propuesta) }}" class="border-t border-gray-200 px-4 py-3 flex justify-end">
                        @csrf
                        <input type="hidden" name="origen_gestion_id" value="{{ $origenGestion->id }}">
                        <input type="hidden" name="origen_periodo_id" value="{{ $origenPeriodo->id }}">
                        <button type="submit" class="bg-[#00acac] text-white px-4 py-2 text-sm font-semibold hover:bg-[#008a8a]">Confirmar importacion</button>
                    </form>
                @endif
            </section>
        @endif
    </div>
@endsection
