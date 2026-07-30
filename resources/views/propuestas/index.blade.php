@extends('layouts.app')

@section('title', 'Designaciones')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <div class="flex flex-wrap items-start justify-between gap-4">
            <div>
                <h1 class="text-xl font-bold text-gray-900">Designaciones</h1>
                <p class="text-sm text-gray-600 mt-1">Lista de designaciones de su carrera por gestión y período.</p>
            </div>
        </div>

        @if($gestionActual)
            <section class="bg-white border border-gray-200 p-5 shadow-sm">
                <h2 class="text-sm font-bold text-gray-900">Abrir designaciones</h2>
                <form method="POST" action="{{ route('designaciones.crear') }}" class="mt-4 grid grid-cols-1 md:grid-cols-[1fr_180px_auto] gap-3 items-end">
                    @csrf
                    <div>
                        <label for="descripcion" class="block text-xs font-semibold text-gray-700 mb-1">Descripción</label>
                        <input id="descripcion" name="descripcion" value="{{ old('descripcion') }}" maxlength="255" class="w-full border border-gray-300 px-3 py-2 text-sm" placeholder="Designaciones docente">
                    </div>
                    <div>
                        <label for="periodo_id" class="block text-xs font-semibold text-gray-700 mb-1">Período</label>
                        <select id="periodo_id" name="periodo_id" class="w-full border border-gray-300 px-3 py-2 text-sm" required>
                            @foreach($periodos as $periodo)
                                <option value="{{ $periodo->id }}">{{ $periodo->nombre }}</option>
                            @endforeach
                        </select>
                    </div>
                    <input type="hidden" name="gestion_id" value="{{ $gestionActual->id }}">
                    <button type="submit" class="bg-[#00acac] text-white px-4 py-2 text-sm font-semibold hover:bg-[#008a8a]">Abrir designaciones</button>
                </form>
                <p class="mt-2 text-xs text-gray-500">Gestión actual: {{ $gestionActual->nombre }}.</p>
            </section>
        @else
            <div class="border border-amber-300 bg-amber-50 text-amber-900 px-4 py-3 text-sm">
                No existe una gestión marcada como actual. No es posible abrir borradores nuevos.
            </div>
        @endif

        <section class="bg-white border border-gray-200 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600">
                    <tr>
                        <th class="px-4 py-3">Descripción</th>
                        <th class="px-4 py-3">Gestión</th>
                        <th class="px-4 py-3">Estado</th>
                        <th class="px-4 py-3">Revisiones</th>
                        <th class="px-4 py-3 text-right">Acción</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($propuestas as $propuesta)
                        @php($pendiente = $propuesta->versiones->firstWhere('estado', 'pendiente'))
                        <tr>
                            <td class="px-4 py-3 font-medium text-gray-900">{{ $propuesta->descripcion ?: 'Designaciones sin descripción' }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $propuesta->gestion->nombre }} / {{ $propuesta->periodo->nombre }}</td>
                            <td class="px-4 py-3">
                                <span class="px-2 py-1 text-xs font-semibold {{ $pendiente ? 'bg-amber-100 text-amber-900' : 'bg-sky-100 text-sky-900' }}">
                                    {{ $pendiente ? 'Enviada' : 'Borrador' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-gray-600">{{ $propuesta->versiones->count() }}</td>
                            <td class="px-4 py-3 text-right"><a class="text-[#007c7c] font-semibold hover:underline" href="{{ route('designaciones.editar', $propuesta) }}">Abrir</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-8 text-center text-gray-500">Aún no existen designaciones para su carrera.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
