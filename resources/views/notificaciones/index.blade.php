@extends('layouts.app')

@section('title', 'Notificaciones')

@section('content')
    <div class="max-w-4xl mx-auto space-y-4">
        <h1 class="text-xl font-bold text-gray-900">Notificaciones</h1>
        <section class="bg-white border border-gray-200 shadow-sm divide-y divide-gray-100">
            @forelse($notificaciones as $notificacion)
                <div class="p-4 flex flex-wrap justify-between gap-3 {{ $notificacion->read_at ? 'text-gray-600' : 'bg-teal-50 text-gray-900' }}">
                    <div><p class="font-semibold text-sm">{{ $notificacion->data['titulo'] ?? 'Actualizacion' }}</p><p class="text-xs mt-1">{{ $notificacion->data['detalle'] ?? '' }}</p><p class="text-xs mt-1 text-gray-500">{{ $notificacion->created_at->format('d/m/Y H:i') }}</p></div>
                    <form method="POST" action="{{ route('notificaciones.leer', $notificacion) }}">@csrf <button type="submit" class="text-[#007c7c] text-sm font-semibold hover:underline">{{ $notificacion->read_at ? 'Abrir' : 'Marcar leida y abrir' }}</button></form>
                </div>
            @empty
                <p class="p-6 text-sm text-gray-500">No tiene notificaciones.</p>
            @endforelse
        </section>
        {{ $notificaciones->links() }}
    </div>
@endsection
