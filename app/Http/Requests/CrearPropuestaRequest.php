<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CrearPropuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gestion_id' => ['required', 'exists:gestiones,id'],
            'periodo_id' => ['required', 'exists:periodos,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
        ];
    }
}
