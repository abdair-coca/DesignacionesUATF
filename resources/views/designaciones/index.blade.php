@extends('layouts.app')

@section('title', 'Designación de Docentes')

@section('content')
    <a href="{{ route('designaciones.create') }}" class="btn btn-primary mb-3">Nueva designación</a>

    <table class="table table-striped table-bordered align-middle">
        <thead>
            <tr>
                <th>Docente</th>
                <th>Materia</th>
                <th>Grupo</th>
                <th>Gestión</th>
                <th>Periodo</th>
                <th>Estado</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($designaciones as $designacion)
                <tr>
                    <td>{{ $designacion->docente->nombre }}</td>
                    <td>{{ $designacion->materia->sigla }} — {{ $designacion->materia->nombre }}</td>
                    <td>{{ $designacion->grupo->codigo }}</td>
                    <td>{{ $designacion->gestion->nombre }}</td>
                    <td>{{ $designacion->periodo->nombre }}</td>
                    <td>
                        <span class="badge {{ match($designacion->estado) {
                            'aprobada' => 'bg-success',
                            'rechazada' => 'bg-danger',
                            default => 'bg-secondary',
                        } }}">
                            {{ ucfirst($designacion->estado) }}
                        </span>
                    </td>
                    <td class="text-nowrap">
                        <a href="{{ route('designaciones.edit', $designacion) }}" class="btn btn-sm btn-outline-primary">Editar</a>
                        <a href="{{ route('designaciones.historial', $designacion) }}" class="btn btn-sm btn-outline-secondary">Historial</a>
                        <form action="{{ route('designaciones.destroy', $designacion) }}" method="POST" class="d-inline"
                              onsubmit="return confirm('¿Eliminar esta designación?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-sm btn-outline-danger">Eliminar</button>
                        </form>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center text-muted">No hay designaciones registradas todavía.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    {{ $designaciones->links() }}
@endsection
