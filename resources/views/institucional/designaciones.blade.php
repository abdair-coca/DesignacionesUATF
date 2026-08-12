@extends('layouts.app')

@section('title', 'Consulta institucional')

@php
    $programaSeleccionado = $parametros['programa'] ?? (Auth::user()->esDirectorCarrera() ? $programaCarrera : '');
    $gestionSeleccionada = $parametros['gestion'] ?? '';
    $periodoSeleccionado = $parametros['periodo'] ?? '';
@endphp

@section('content')
    <div class="max-w-7xl mx-auto space-y-6">
        <div>
            <p class="text-xs font-semibold uppercase tracking-wider text-[#007c7c]">Fuente institucional</p>
            <h1 class="text-2xl font-bold text-gray-900 mt-1">Consulta institucional</h1>
            <p class="text-sm text-gray-600 mt-1">
                Consulta de registros retornados por <code>designaciones.f_asignaciones</code>.
                Esta pantalla es de solo lectura y no modifica propuestas locales.
            </p>
        </div>

        @if($error)
            <div class="border border-rose-300 bg-rose-50 p-4 text-sm text-rose-900" role="alert">
                {{ $error }}
            </div>
        @endif

        <section class="bg-white border border-gray-200 p-5 shadow-sm">
            <form method="GET" action="{{ route('institucional.designaciones.consulta') }}" class="grid grid-cols-1 md:grid-cols-[1fr_1fr_1fr_auto] gap-3 items-end">
                <div>
                    <label for="programa" class="block text-xs font-semibold text-gray-700 mb-1">Programa o universidad</label>
                    <input
                        id="programa"
                        name="programa"
                        value="{{ $programaSeleccionado }}"
                        pattern="[A-Za-z0-9_-]{2,20}"
                        maxlength="20"
                        required
                        @if(Auth::user()->esDirectorCarrera()) readonly @endif
                        class="w-full border border-gray-300 px-3 py-2 text-sm read-only:bg-gray-100 read-only:text-gray-600"
                        placeholder="INF o UATF"
                    >
                </div>
                <div>
                    <label for="gestion" class="block text-xs font-semibold text-gray-700 mb-1">Gestión</label>
                    <input
                        id="gestion"
                        name="gestion"
                        value="{{ $gestionSeleccionada }}"
                        pattern="(?:0|\d{4})"
                        maxlength="4"
                        inputmode="numeric"
                        required
                        class="w-full border border-gray-300 px-3 py-2 text-sm"
                        placeholder="2024 o 0"
                    >
                </div>
                <div>
                    <label for="periodo" class="block text-xs font-semibold text-gray-700 mb-1">Periodo</label>
                    <input
                        id="periodo"
                        name="periodo"
                        value="{{ $periodoSeleccionado }}"
                        pattern="(?:0|\d{1,2})"
                        maxlength="2"
                        inputmode="numeric"
                        required
                        class="w-full border border-gray-300 px-3 py-2 text-sm"
                        placeholder="1 o 0"
                    >
                </div>
                <button type="submit" class="bg-[#00acac] text-white px-4 py-2 text-sm font-semibold hover:bg-[#008a8a]">
                    Consultar
                </button>
            </form>
            @if(Auth::user()->esDirectorCarrera())
                <p class="text-xs text-gray-500 mt-3">Tu consulta está limitada al programa {{ $programaCarrera }}. Usa gestión y periodo <code>0</code> para consultar todo el historial disponible.</p>
            @else
                <p class="text-xs text-gray-500 mt-3">Vicerrectorado puede consultar un programa o la universidad completa con <code>UATF</code>. Usa gestión y periodo <code>0</code> para consultar todo lo disponible.</p>
            @endif
        </section>

        @if(!$consultado)
            <div class="bg-white border border-dashed border-gray-300 p-8 text-center text-sm text-gray-500">
                No se ha ejecutado una consulta.
            </div>
        @elseif(!$error && $items->isEmpty())
            <div class="bg-white border border-gray-200 p-8 text-center text-sm text-gray-500">
                No se encontraron registros para los parámetros indicados.
            </div>
        @elseif(!$error)
            <section class="bg-white border border-gray-200 shadow-sm overflow-x-auto">
                <div class="px-4 py-3 border-b border-gray-200 flex items-center justify-between gap-3">
                    <h2 class="font-bold text-sm text-gray-900">Registros institucionales</h2>
                    <span class="text-xs text-gray-500">{{ $items->count() }} registros</span>
                </div>
                <table class="w-full min-w-[1500px] text-xs">
                    <thead class="bg-gray-50 text-left text-[10px] uppercase tracking-wide text-gray-600">
                        <tr>
                            <th class="px-3 py-3">ID <code>r_id</code></th>
                            <th class="px-3 py-3">Código <code>r_id_programa</code></th>
                            <th class="px-3 py-3">Programa <code>r_programa</code></th>
                            <th class="px-3 py-3">Detalle <code>r_detalle</code></th>
                            <th class="px-3 py-3">Fecha <code>r_fecha</code></th>
                            <th class="px-3 py-3">Gestión <code>r_id_gestion</code></th>
                            <th class="px-3 py-3">Periodo <code>r_id_periodo</code></th>
                            <th class="px-3 py-3">Observación <code>r_obs</code></th>
                            <th class="px-3 py-3">Estado <code>r_estado</code></th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        @foreach($items as $item)
                            <tr class="align-top">
                                <td class="px-3 py-3 font-semibold">{{ $item['id'] }}</td>
                                <td class="px-3 py-3">{{ $item['programa_codigo'] }}</td>
                                <td class="px-3 py-3">{{ $item['programa_nombre'] }}</td>
                                <td class="px-3 py-3">{{ $item['detalle'] }}</td>
                                <td class="px-3 py-3 whitespace-nowrap">{{ $item['fecha'] ?? '—' }}</td>
                                <td class="px-3 py-3">{{ $item['gestion'] }}</td>
                                <td class="px-3 py-3">{{ $item['periodo'] }}</td>
                                <td class="px-3 py-3">{{ $item['observacion'] ?? '—' }}</td>
                                <td class="px-3 py-3">{{ $item['estado'] ?? '—' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </section>
        @endif
    </div>
@endsection
