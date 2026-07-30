@extends('layouts.app')

@section('title', 'Borrador de Propuesta')

@section('content')
    <div class="max-w-7xl mx-auto space-y-6">
        <div class="flex flex-wrap justify-between gap-4 items-start">
            <div>
                <a href="{{ route('propuestas.index') }}" class="text-sm text-[#007c7c] hover:underline">Volver a propuestas</a>
                <h1 class="text-xl font-bold text-gray-900 mt-2">{{ $propuesta->descripcion ?: 'Borrador de designación' }}</h1>
                <p class="text-sm text-gray-600 mt-1">{{ $propuesta->carrera->nombre }} · Gestión {{ $propuesta->gestion->nombre }} · Período {{ $propuesta->periodo->nombre }}</p>
            </div>
            @if($puedeEditar)
                <form method="POST" action="{{ route('propuestas.enviar', $propuesta) }}">
                    @csrf
                    <button type="submit" class="bg-[#00acac] text-white px-4 py-2 text-sm font-semibold hover:bg-[#008a8a]">Enviar versión a revisión</button>
                </form>
            @endif
        </div>

        @if($errors->any())
            <div class="border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900">
                <ul class="list-disc pl-5 space-y-1">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
            </div>
        @endif

        @if($ultimaVersionObservada?->observaciones)
            <div class="border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">
                <span class="font-semibold">Observación de Vicerrectorado:</span> {{ $ultimaVersionObservada->observaciones }}
            </div>
        @endif

        @if($puedeEditar)
            <form method="POST" action="{{ route('propuestas.guardar', $propuesta) }}" class="bg-white border border-gray-200 shadow-sm overflow-x-auto">
                @csrf
                @method('PUT')
                <table class="w-full text-sm">
                    <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600">
                        <tr><th class="px-4 py-3">Materia</th><th class="px-4 py-3">Grupo</th><th class="px-4 py-3">Docente</th></tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($grupos as $indice => $grupo)
                            @php($designacion = $designacionesPorGrupo->get($grupo->id))
                            @php($bloqueada = $designacion?->estado === 'aprobada_previamente')
                            <tr>
                                <td class="px-4 py-3"><span class="font-semibold">{{ $grupo->mallaCurricular->materia->sigla }}</span> <span class="text-gray-600">{{ $grupo->mallaCurricular->materia->nombre }}</span></td>
                                <td class="px-4 py-3">{{ $grupo->codigo }}</td>
                                <td class="px-4 py-3 min-w-64">
                                    <input type="hidden" name="cambios[{{ $indice }}][grupo_id]" value="{{ $grupo->id }}">
                                    <input type="hidden" name="cambios[{{ $indice }}][materia_id]" value="{{ $grupo->mallaCurricular->materia_id }}">
                                    <select name="cambios[{{ $indice }}][docente_id]" @disabled($bloqueada) class="w-full border border-gray-300 px-2 py-1.5 disabled:bg-gray-100">
                                        <option value="">Sin asignar</option>
                                        @foreach($docentes as $docente)
                                            <option value="{{ $docente->id }}" @selected((int) $designacion?->docente_id === $docente->id)>{{ $docente->nombre }}</option>
                                        @endforeach
                                    </select>
                                    @if($bloqueada)
                                        <p class="mt-1 text-xs font-semibold text-emerald-800">Aprobada previamente</p>
                                    @endif
                                    @if($observacionesPorGrupo->get($grupo->id))
                                        <p class="mt-1 text-xs text-amber-800">Observación: {{ $observacionesPorGrupo->get($grupo->id) }}</p>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="border-t border-gray-200 px-4 py-3 flex justify-end"><button type="submit" class="bg-[#348fe2] text-white px-4 py-2 text-sm font-semibold hover:bg-[#2a72b5]">Guardar borrador</button></div>
            </form>
        @else
            <div class="border border-amber-300 bg-amber-50 p-4 text-sm text-amber-900">El borrador está bloqueado mientras una versión esté pendiente de revisión.</div>
        @endif

        <section class="bg-white border border-gray-200 shadow-sm overflow-x-auto">
            <div class="px-4 py-3 border-b border-gray-200"><h2 class="font-bold text-sm text-gray-900">Historial de versiones</h2></div>
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600"><tr><th class="px-4 py-3">Versión</th><th class="px-4 py-3">Estado</th><th class="px-4 py-3">Enviada</th><th class="px-4 py-3 text-right">Acción</th></tr></thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($propuesta->versiones as $version)
                        <tr>
                            <td class="px-4 py-3 font-semibold">{{ $version->numero }}</td><td class="px-4 py-3">{{ ucfirst($version->estado) }}</td><td class="px-4 py-3 text-gray-600">{{ $version->enviado_en?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right">
                                @if($version->estado === 'pendiente' && $version->enviado_por === auth()->id())
                                    <form method="POST" action="{{ route('propuestas.versiones.retirar', $version) }}">@csrf <button type="submit" class="text-amber-800 font-semibold hover:underline">Retirar</button></form>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-4 py-6 text-center text-gray-500">Aún no se enviaron versiones.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
