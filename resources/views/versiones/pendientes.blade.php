@extends('layouts.app')

@section('title', 'Bandeja de Revisiones')

@section('content')
    <div class="max-w-6xl mx-auto space-y-6">
        <div>
            <h1 class="text-xl font-bold text-gray-900">Bandeja de Revisiones</h1>
            <p class="text-sm text-gray-600 mt-1">Designaciones enviadas por los Directores y pendientes de decisión.</p>
        </div>

        <section class="bg-white border border-gray-200 shadow-sm overflow-x-auto">
            <table class="w-full text-sm">
                <thead class="bg-gray-50 text-left text-xs uppercase text-gray-600">
                    <tr><th class="px-4 py-3">Carrera</th><th class="px-4 py-3">Revisión</th><th class="px-4 py-3">Director</th><th class="px-4 py-3">Enviada</th><th class="px-4 py-3 text-right">Acción</th></tr>
                </thead>
                <tbody class="divide-y divide-gray-100">
                    @forelse($versiones as $version)
                        <tr>
                            <td class="px-4 py-3"><div class="font-semibold text-gray-900">{{ $version->propuesta->carrera->nombre }}</div><div class="text-xs text-gray-500">{{ $version->propuesta->gestion->nombre }} / {{ $version->propuesta->periodo->nombre }}</div></td>
                            <td class="px-4 py-3">{{ $version->numero }}</td>
                            <td class="px-4 py-3">{{ $version->remitente->name }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ $version->enviado_en?->format('d/m/Y H:i') }}</td>
                            <td class="px-4 py-3 text-right"><a href="{{ route('revisiones.revisar', $version) }}" class="text-[#007c7c] font-semibold hover:underline">Revisar</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="px-4 py-10 text-center text-gray-500">No hay revisiones pendientes.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </section>
    </div>
@endsection
