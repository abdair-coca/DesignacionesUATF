@php
    $seleccionado = fn (string $campo, $valor) => old($campo, $designacion->$campo ?? '') == $valor ? 'selected' : '';
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">Docente</label>
        <select name="Id_docente" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach ($docentes as $docente)
                <option value="{{ $docente->id }}" {{ $seleccionado('Id_docente', $docente->id) }}>
                    {{ $docente->ci }} — {{ $docente->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Materia</label>
        <select name="Id_materia" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach ($materias as $materia)
                <option value="{{ $materia->id }}" {{ $seleccionado('Id_materia', $materia->id) }}>
                    {{ $materia->sigla }} — {{ $materia->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-6">
        <label class="form-label">Grupo</label>
        <select name="Id_grupo" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach ($grupos as $grupo)
                <option value="{{ $grupo->id }}" {{ $seleccionado('Id_grupo', $grupo->id) }}>
                    {{ $grupo->materia->sigla }} — Grupo {{ $grupo->codigo }}
                    ({{ $grupo->estado }})
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Gestión</label>
        <select name="Id_gestion" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach ($gestiones as $gestion)
                <option value="{{ $gestion->id }}" {{ $seleccionado('Id_gestion', $gestion->id) }}>
                    {{ $gestion->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-3">
        <label class="form-label">Periodo</label>
        <select name="Id_periodo" class="form-select" required>
            <option value="">Seleccione...</option>
            @foreach ($periodos as $periodo)
                <option value="{{ $periodo->id }}" {{ $seleccionado('Id_periodo', $periodo->id) }}>
                    {{ $periodo->nombre }}
                </option>
            @endforeach
        </select>
    </div>

    <div class="col-md-4">
        <label class="form-label">Estado</label>
        <select name="estado" class="form-select" required>
            @foreach (['propuesta', 'aprobada', 'rechazada'] as $estado)
                <option value="{{ $estado }}" {{ $seleccionado('estado', $estado) }}>
                    {{ ucfirst($estado) }}
                </option>
            @endforeach
        </select>
    </div>
</div>

<div class="mt-4">
    <button type="submit" class="btn btn-primary">Guardar</button>
    <a href="{{ route('designaciones.index') }}" class="btn btn-outline-secondary">Cancelar</a>
</div>
