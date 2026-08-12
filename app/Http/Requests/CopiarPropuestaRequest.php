<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class CopiarPropuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'periodo_id' => ['required', 'integer', 'exists:periodos,id'],
            'descripcion' => ['nullable', 'string', 'max:255'],
            'origen_gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'origen_periodo_id' => ['required', 'integer', 'exists:periodos,id'],
        ];
    }
}
