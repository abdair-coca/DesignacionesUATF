<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class GuardarAsignacionesRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('propuesta')) ?? false;
    }

    public function rules(): array
    {
        return [
            'cambios' => ['required', 'array', 'min:1'],
            'cambios.*.grupo_id' => ['required', 'exists:grupos,id'],
            'cambios.*.materia_id' => ['required', 'exists:materias,id'],
            'cambios.*.docente_id' => ['nullable', 'exists:docentes,id'],
            'cambios.*.horas_pagadas' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'cambios.*.horas_no_pagadas' => ['sometimes', 'nullable', 'integer', 'min:0'],
            'cambios.*.observacion_remuneracion' => ['sometimes', 'nullable', 'string', 'max:1000'],
        ];
    }
}
