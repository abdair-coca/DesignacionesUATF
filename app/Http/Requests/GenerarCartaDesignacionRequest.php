<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class GenerarCartaDesignacionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->esDirectorCarrera() ?? false;
    }

    /** @return array<string, array<int, string>> */
    public function rules(): array
    {
        return [
            'condicion' => ['required', 'in:DOC_ORD_TITULAR,DOC_ORD_CONTRATADO,DOC_EXTR'],
            'fila_id' => ['required', 'integer', 'min:1'],
            'grado_academico' => ['required', 'string', 'max:120'],
            'facultad' => ['required', 'string', 'max:180'],
            'fecha_resolucion' => ['required', 'date_format:Y-m-d'],
            'carrera_semestralizada' => ['required', 'in:SI,NO'],
            'numero_cite' => ['required', 'string', 'max:80'],
            'numero_resolucion' => ['required', 'string', 'max:80'],
            'leyenda_revision' => ['nullable', 'string', 'max:120'],
            'modalidad' => ['required', 'in:HORARIO,COMPLETO'],
            'fecha_inicio' => ['required', 'date_format:Y-m-d'],
            'decan_nombre' => ['required', 'string', 'max:160'],
            'vicerrector_nombre' => ['required', 'string', 'max:160'],
            'vicerrector_cargo' => ['required', 'string', 'max:120'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response('No fue posible completar la operación.', 422));
    }
}
