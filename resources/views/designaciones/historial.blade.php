@extends('layouts.app')

@section('title', 'Historial de designación')

@section('content')
    <div class="card mb-4">
        <div class="card-body">
            <h5 class="card-title">
                {{ $designacion->docente->nombre }} — {{ $designacion->materia->sigla }}
                (Grupo {{ $designacion->grupo->codigo }}, {{ $designacion->gestion->nombre }}-{{ $designacion->periodo->nombre }})
            </h5>
            <p class="card-text text-muted mb-0">Estado actual: {{ ucfirst($designacion->estado) }}</p>
        </div>
    </div>

    <table class="table table-striped table-bordered">
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Campo</th>
                <th>Valor anterior</th>
                <th>Valor nuevo</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($historial as $registro)
                <tr>
                    <td>{{ $registro->fecha->format('d/m/Y H:i') }}</td>
                    <td>{{ $registro->campo }}</td>
                    <td>{{ $registro->valor_anterior }}</td>
                    <td>{{ $registro->valor_nuevo }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4" class="text-center text-muted">Sin cambios registrados todavía.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <a href="{{ route('designaciones.index') }}" class="btn btn-outline-secondary">Volver al listado</a>
@endsection
