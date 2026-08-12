<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ImportarPropuestaRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('update', $this->route('propuesta')) ?? false;
    }

    public function rules(): array
    {
        return [
            'origen_gestion_id' => ['required', 'integer', 'exists:gestiones,id'],
            'origen_periodo_id' => ['required', 'integer', 'exists:periodos,id'],
        ];
    }
}
